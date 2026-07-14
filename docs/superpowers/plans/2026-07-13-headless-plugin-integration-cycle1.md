# Headless Plugin Integration — Cycle 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Re-base the headless front-end's data layer on the real `blueworx_labs_wordpress` v1.10.1 plugin (two-base config, generic content fetchers, marketing content via WP CPTs+ACF with static fallback), and ship the auth + revalidation infrastructure Cycle 2 will use — without changing any rendered UI.

**Architecture:** A single origin env var (`NEXT_PUBLIC_WORDPRESS_URL`) derives two REST bases: `blueworx/v1` (auth, menus, site, resolve) and `wp/v2` (content bodies). Low-level fetchers live in `lib/api/wp.ts`; pure ACF→front-end mappers in `lib/api/mappers.ts`; the existing `lib/api/content.ts` keeps its public signatures but now fetches+maps live, falling back to today's static `lib/data.ts` whenever the CMS is unconfigured, empty, or failing. Auth (`lib/wp-client.ts`) and revalidation (`app/api/revalidate/route.ts`) ship as reviewed infrastructure; the portal stays on mock data (Cycle 2).

**Tech Stack:** Next.js 15 (App Router) + React 19 + TypeScript, Playwright for tests. No new runtime dependencies (uses built-in `fetch`, `next/cache`, `node:crypto`).

## Global Constraints

- **No new dependencies** — anything outside `package.json` needs `approved-deps.json` approval first. This plan adds none.
- **CI guardrails (every PR):** lint passes · build passes · **version bumped** · **changelog updated** · Playwright test for new functionality.
- **Version:** currently `0.3.2`. This is a feature → **minor bump to `0.4.0`** (final task).
- **Branch:** `headless-plugin-integration` (already created; spec committed there). Never commit to `main`.
- **No UI changes:** `getTools/getToolboxPlans/getRetainerPlans/getFaqs/getTestimonials/getSoloPrices/getToolBySlug` keep their exact current signatures and return types so no page/component changes.
- **Icon keys** in any `icon` field must be one of: `chat, mail, chart, clock, sms, doc, server, users, plug, book, cart, calendar, phone, sparkles, code, zap, git, palette, workflow, gauge, shield` (see [`lib/icons.ts`](../../../lib/icons.ts)).
- **Money/dates** render as-is; presentation fields (`btn`, `initials`) are derived front-end, never stored in the CMS.

### Test harness (read once)

Tests are Playwright specs. Two kinds are used here:

- **Server-free specs** (most tasks): the `test()` body runs in Node and imports modules / stubs `globalThis.fetch` directly — it does not use a browser or the app server. Modelled on the existing [`tests/fixtures-parity.spec.ts`](../../../tests/fixtures-parity.spec.ts).
- **HTTP/browser specs**: hit the running app server (revalidate route, catch-all 404).

Playwright boots a `webServer` (via `next start`) for the `app` project before running. To keep the red/green loop fast during development, **start a dev server once and let Playwright reuse it** (memory: this project uses **PORT=3100** for tests):

```bash
# one time, in a separate terminal — Playwright reuses it (reuseExistingServer, non-CI)
PORT=3100 npm run dev
```

Per-test command used throughout this plan:

```bash
PORT=3100 npx playwright test tests/<file> --project=app --reporter=list
```

For the final full CI-parity check: `npm run lint && npm run build && PORT=3100 npx playwright test`.

---

## File Structure

**Create:**
- `lib/api/wp.ts` — low-level fetchers for the plugin's generic endpoints + `rewriteMenuUrl`. HTTP in, typed JSON out. No marketing mapping.
- `lib/api/mappers.ts` — pure functions mapping WP CPT+ACF payloads to front-end `Tool`/`Plan`/`Faq`/`Testimonial`. No I/O.
- `lib/wp-client.ts` — browser auth client (in-memory JWT + single-flight refresh). Infra for Cycle 2.
- `app/api/revalidate/route.ts` — on-demand ISR receiver (secret-verified).
- `app/[...slug]/page.tsx` — catch-all that resolves a path to `wp/v2` content; `notFound()` otherwise (and always in mock mode).
- `docs/cms-content-model.md` — CPT/ACF contract for the CMS team.
- `tests/config-derivation.spec.ts`, `tests/wp-fetchers.spec.ts`, `tests/content-mapping.spec.ts`, `tests/content-live.spec.ts`, `tests/wp-client.spec.ts`, `tests/revalidate.spec.js`, `tests/wp-page-404.spec.js` — tests per task.

**Modify:**
- `lib/config.ts` — rewrite to the two-base model.
- `.env.example` — rewrite to the new vars.
- `lib/data.ts` — add `soloPrice?: number` to the `Tool` type.
- `lib/api/content.ts` — rewrite bodies to fetch+map with static fallback (signatures unchanged).
- `lib/api/portal.ts` — stop referencing removed config fields; keep mock (Cycle 2 note).
- `docs/API_CONTRACT.md` — prepend a reconciliation note pointing at the real plugin contract.
- `package.json` + `CHANGELOG.md` — version bump.

---

## Task 1: Config — two-base model

**Files:**
- Modify: `lib/config.ts` (full rewrite)
- Modify: `lib/api/portal.ts:13,188-196` (remove references to deleted fields)
- Modify: `.env.example` (full rewrite)
- Test: `tests/config-derivation.spec.ts`

**Interfaces:**
- Produces: `WP_ORIGIN: string`, `BLUEWORX_API: string`, `WP_API: string` (named exports); `config` object with `{ wpOrigin, blueworxApi, wpApi, revalidateSecret, contactForwardUrl, portalRequireAuth }`; `useMockData: boolean` (`true` when `WP_ORIGIN === ""`).
- Consumed by: every later task, `lib/api/portal.ts`, `lib/auth.ts` (unchanged — uses `config.portalRequireAuth`), `app/api/contact/route.ts` (unchanged — uses `config.contactForwardUrl`).

- [ ] **Step 1: Write the failing test**

```ts
// tests/config-derivation.spec.ts
import { test, expect } from '@playwright/test';

test.describe('config base derivation', () => {
  test('derives both REST bases from a live origin and disables mock', async () => {
    process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io/';
    const mod = await import(`../lib/config?live=${Date.now()}`);
    expect(mod.WP_ORIGIN).toBe('https://cms.blueworx.io');           // trailing slash stripped
    expect(mod.BLUEWORX_API).toBe('https://cms.blueworx.io/wp-json/blueworx/v1');
    expect(mod.WP_API).toBe('https://cms.blueworx.io/wp-json/wp/v2');
    expect(mod.useMockData).toBe(false);
  });

  test('empty origin means mock mode and empty bases', async () => {
    delete process.env.NEXT_PUBLIC_WORDPRESS_URL;
    const mod = await import(`../lib/config?mock=${Date.now()}`);
    expect(mod.WP_ORIGIN).toBe('');
    expect(mod.BLUEWORX_API).toBe('');
    expect(mod.useMockData).toBe(true);
  });
});
```

> Note: the `?live=...`/`?mock=...` query busts the ESM module cache so each test re-evaluates `config.ts` against the current env.

- [ ] **Step 2: Run test to verify it fails**

Run: `PORT=3100 npx playwright test tests/config-derivation.spec.ts --project=app --reporter=list`
Expected: FAIL — `WP_ORIGIN`/`BLUEWORX_API` are undefined (module not yet rewritten).

- [ ] **Step 3: Rewrite `lib/config.ts`**

```ts
// Central runtime configuration for the headless front-end.
//
// Integration points with the BlueWorx WordPress plugin derive from a single origin
// env var. The plugin exposes two REST bases:
//   blueworx/v1 — auth, menus, site, resolve, acf-options, surecart proxy
//   wp/v2       — the content bodies (pages/posts/CPTs), ACF attached as `acf`
// See the plugin's HEADLESS_INTEGRATION.md and
// docs/superpowers/specs/2026-07-13-headless-plugin-integration-cycle1-design.md.

/** WordPress origin, scheme + host, no trailing slash. Empty until the CMS is live. */
export const WP_ORIGIN = process.env.NEXT_PUBLIC_WORDPRESS_URL?.replace(/\/$/, "") || "";

/** BlueWorx headless namespace base (auth, menus, site, resolve). Empty in mock mode. */
export const BLUEWORX_API = WP_ORIGIN ? `${WP_ORIGIN}/wp-json/blueworx/v1` : "";

/** Core WordPress REST base (content bodies). Empty in mock mode. */
export const WP_API = WP_ORIGIN ? `${WP_ORIGIN}/wp-json/wp/v2` : "";

export const config = {
  wpOrigin: WP_ORIGIN,
  blueworxApi: BLUEWORX_API,
  wpApi: WP_API,

  /** Server-only shared secret matching BLUEWORX_LABS_REVALIDATE_SECRET on the CMS. */
  revalidateSecret: process.env.REVALIDATE_SECRET || "",

  /** Where /api/contact forwards validated submissions. Empty = accept but don't forward. */
  contactForwardUrl: process.env.CONTACT_FORWARD_URL || "",

  /** Cycle 2 (portal). Enforces a real session on /portal when true. */
  portalRequireAuth: process.env.PORTAL_REQUIRE_AUTH === "true",
} as const;

/**
 * True while there is no live CMS to talk to. The data-access layer uses this to
 * choose between static fallback data and real fetches. Single mock⇄live switch.
 */
export const useMockData = WP_ORIGIN === "";
```

- [ ] **Step 4: Fix `lib/api/portal.ts` so it compiles (removed fields)**

Change the import at line 13 and the `getPortalData` body (lines ~188-196) to:

```ts
import { useMockData } from "@/lib/config";
```

```ts
/**
 * Fetch the portal payload for the authenticated customer. Cycle 2: the plugin's
 * browser-only JWT auth makes portal data a client concern, and there is no
 * /portal/me endpoint yet — so until Cycle 2 wires the §10 client + a normalized
 * endpoint, always serve the demo payload.
 */
export async function getPortalData(): Promise<PortalData> {
  return MOCK_PORTAL;
}
```

(Leave the `MOCK_PORTAL` const and all types untouched. `useMockData` import is retained only if still referenced; if TypeScript/ESLint flags it as unused after this change, drop it from the import.)

- [ ] **Step 5: Rewrite `.env.example`**

```bash
# BlueWorx headless front-end — environment configuration.
#
# Copy to `.env.local` for local development (git-ignored). In production these are
# set in Netlify. While NEXT_PUBLIC_WORDPRESS_URL is empty the site serves built-in
# static data and every integration is a no-op — safe to run as-is.

# ---------------------------------------------------------------------------
# WordPress origin (scheme + host, no trailing slash). Public — the browser hits
# wp/v2 and blueworx/v1/auth directly. Leave empty until the CMS is deployed.
# ---------------------------------------------------------------------------
NEXT_PUBLIC_WORDPRESS_URL=

# ---------------------------------------------------------------------------
# On-demand revalidation (ISR). Server-only. Must match BLUEWORX_LABS_REVALIDATE_SECRET
# on the CMS. Never prefix NEXT_PUBLIC_.
# ---------------------------------------------------------------------------
REVALIDATE_SECRET=

# ---------------------------------------------------------------------------
# Contact form — where /api/contact forwards validated submissions.
# Empty = accept and validate but don't forward (dev/demo behaviour).
# ---------------------------------------------------------------------------
CONTACT_FORWARD_URL=

# ---------------------------------------------------------------------------
# Portal auth (Cycle 2). Set "true" once the plugin's auth/session is wired to
# enforce sign-in on /portal. Defaults to false so the demo portal renders.
# ---------------------------------------------------------------------------
PORTAL_REQUIRE_AUTH=false
```

- [ ] **Step 6: Run test + build + existing suites to verify green**

Run: `PORT=3100 npx playwright test tests/config-derivation.spec.ts --project=app --reporter=list`
Expected: PASS (both tests).

Run: `npm run build`
Expected: build succeeds (confirms `portal.ts`/`content.ts` consumers still compile — note `content.ts` is rewritten in Task 4; if building before Task 4, it still references the old config and will fail, so run the full build at the end of Task 4, and here just run `npx tsc --noEmit` is NOT configured — instead run lint: `npm run lint`).

Run: `npm run lint`
Expected: no new errors.

- [ ] **Step 7: Commit**

```bash
git add lib/config.ts lib/api/portal.ts .env.example tests/config-derivation.spec.ts
git commit -m "config: re-base on plugin two-base model (blueworx/v1 + wp/v2)"
```

---

## Task 2: Low-level WordPress fetchers — `lib/api/wp.ts`

**Files:**
- Create: `lib/api/wp.ts`
- Test: `tests/wp-fetchers.spec.ts`

**Interfaces:**
- Consumes: `config` (Task 1).
- Produces: types `WpSite`, `MenuItem`, `ResolveResult`, `WpRendered`, `WpContent<A>`; functions `getSite()`, `getMenu(location)`, `resolve(uri)`, `getAcfOptions()`, `getByRestUrl<A>(restUrl)`, `listCpt<A>(type, params?)`, `rewriteMenuUrl(url)`.

- [ ] **Step 1: Write the failing test**

```ts
// tests/wp-fetchers.spec.ts
import { test, expect } from '@playwright/test';

test.describe('wp fetchers', () => {
  test.beforeEach(() => { process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io'; });

  test('getMenu unwraps items and builds the namespaced URL', async () => {
    let calledUrl = '';
    globalThis.fetch = (async (url: string) => {
      calledUrl = url;
      return { ok: true, json: async () => ({ location: 'primary', items: [{ id: 1, title: 'About', url: 'https://cms.blueworx.io/about/', target: '', object: 'page', object_id: 12, children: [] }] }) };
    }) as unknown as typeof fetch;

    const wp = await import(`../lib/api/wp?menu=${Date.now()}`);
    const items = await wp.getMenu('primary');
    expect(calledUrl).toBe('https://cms.blueworx.io/wp-json/blueworx/v1/menus/primary');
    expect(items).toHaveLength(1);
    expect(items[0].title).toBe('About');
  });

  test('resolve builds the encoded query URL', async () => {
    let calledUrl = '';
    globalThis.fetch = (async (url: string) => { calledUrl = url; return { ok: true, json: async () => ({ type: 'page', id: 12, slug: 'about', rest_url: 'x', template: 'single' }); }; }) as unknown as typeof fetch;
    const wp = await import(`../lib/api/wp?res=${Date.now()}`);
    await wp.resolve('/about');
    expect(calledUrl).toBe('https://cms.blueworx.io/wp-json/blueworx/v1/resolve?uri=%2Fabout');
  });

  test('rewriteMenuUrl strips the WP origin to a path', async () => {
    const wp = await import(`../lib/api/wp?rw=${Date.now()}`);
    expect(wp.rewriteMenuUrl('https://cms.blueworx.io/about/')).toBe('/about/');
    expect(wp.rewriteMenuUrl('https://cms.blueworx.io')).toBe('/');
    expect(wp.rewriteMenuUrl('/already/a/path')).toBe('/already/a/path');
  });

  test('a non-2xx response throws', async () => {
    globalThis.fetch = (async () => ({ ok: false, status: 500, statusText: 'Server Error' })) as unknown as typeof fetch;
    const wp = await import(`../lib/api/wp?err=${Date.now()}`);
    await expect(wp.getSite()).rejects.toThrow(/failed: 500/);
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `PORT=3100 npx playwright test tests/wp-fetchers.spec.ts --project=app --reporter=list`
Expected: FAIL — cannot resolve module `../lib/api/wp`.

- [ ] **Step 3: Create `lib/api/wp.ts`**

```ts
// Low-level fetchers for the BlueWorx WordPress plugin's generic endpoints.
// Single responsibility: HTTP to the CMS, typed JSON out. No marketing mapping
// (see lib/api/mappers.ts). Shapes mirror HEADLESS_INTEGRATION.md §5–§6.

import { config } from "@/lib/config";

export type WpSite = {
  name: string; description: string; url: string; admin_url: string;
  language: string; timezone: string; date_format: string; time_format: string;
  posts_per_page: number; show_on_front: string;
  page_on_front: number; page_for_posts: number; site_logo: string | null;
};

export type MenuItem = {
  id: number; title: string; url: string; target: string;
  object: string; object_id: number; children: MenuItem[];
};

export type ResolveResult = {
  type: string; id: number; slug: string; rest_url: string; template: string;
};

export type WpRendered = { rendered: string };
export type WpContent<A = Record<string, unknown>> = {
  id: number; slug: string; title: WpRendered; content: WpRendered;
  excerpt?: WpRendered; acf?: A;
};

const REVALIDATE_DEFAULT = 300;

async function getJson<T>(url: string, revalidate = REVALIDATE_DEFAULT): Promise<T> {
  const res = await fetch(url, { next: { revalidate } });
  if (!res.ok) throw new Error(`WordPress GET ${url} failed: ${res.status} ${res.statusText}`);
  return res.json() as Promise<T>;
}

export function getSite(): Promise<WpSite> {
  return getJson<WpSite>(`${config.blueworxApi}/site`, 3600);
}

export async function getMenu(location: string): Promise<MenuItem[]> {
  const data = await getJson<{ location: string; items: MenuItem[] }>(
    `${config.blueworxApi}/menus/${encodeURIComponent(location)}`, 3600,
  );
  return data.items;
}

export function resolve(uri: string): Promise<ResolveResult> {
  return getJson<ResolveResult>(`${config.blueworxApi}/resolve?uri=${encodeURIComponent(uri)}`);
}

export function getAcfOptions(): Promise<Record<string, unknown>> {
  return getJson<Record<string, unknown>>(`${config.blueworxApi}/acf-options`, 3600);
}

export function getByRestUrl<A = Record<string, unknown>>(restUrl: string): Promise<WpContent<A>> {
  return getJson<WpContent<A>>(restUrl);
}

export function listCpt<A = Record<string, unknown>>(
  type: string, params: Record<string, string | number> = {},
): Promise<WpContent<A>[]> {
  const stringParams = Object.fromEntries(
    Object.entries(params).map(([k, v]) => [k, String(v)]),
  );
  const qs = new URLSearchParams({ per_page: "100", ...stringParams }).toString();
  return getJson<WpContent<A>[]>(`${config.wpApi}/${type}?${qs}`);
}

/** Menu URLs point at the WP origin; strip it so <Link>s stay on the front-end (§5.3). */
export function rewriteMenuUrl(url: string): string {
  if (config.wpOrigin && url.startsWith(config.wpOrigin)) {
    return url.slice(config.wpOrigin.length) || "/";
  }
  try {
    return new URL(url).pathname;
  } catch {
    return url; // already a relative path
  }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `PORT=3100 npx playwright test tests/wp-fetchers.spec.ts --project=app --reporter=list`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add lib/api/wp.ts tests/wp-fetchers.spec.ts
git commit -m "feat: add low-level wp/v2 + blueworx/v1 fetchers (lib/api/wp.ts)"
```

---

## Task 3: Pure mappers — `lib/api/mappers.ts`

**Files:**
- Modify: `lib/data.ts` (add `soloPrice?: number` to `Tool`)
- Create: `lib/api/mappers.ts`
- Test: `tests/content-mapping.spec.ts`

**Interfaces:**
- Consumes: `Tool`, `Plan` (from `lib/data`); `WpContent` (from `lib/api/wp`).
- Produces: types `Faq`, `Testimonial`, `WpTool`, `WpPlan`, `WpFaq`, `WpTestimonial`; functions `mapTool(WpTool): Tool`, `mapPlan(WpPlan): Plan`, `planGroup(WpPlan): "toolbox"|"retainer"`, `mapFaq(WpFaq): Faq`, `mapTestimonial(WpTestimonial): Testimonial`.

- [ ] **Step 1: Add `soloPrice` to the `Tool` type**

In `lib/data.ts`, extend the `Tool` type (leave the `TOOLBOX_TOOLS` data unchanged — the field is optional):

```ts
export type Tool = {
  slug: string;
  name: string;
  desc: string;
  domain: string;
  category: string;
  popular?: boolean;
  tagline: string;
  features: ToolFeature[];
  soloPrice?: number;
};
```

- [ ] **Step 2: Write the failing test**

```ts
// tests/content-mapping.spec.ts
import { test, expect } from '@playwright/test';
import { mapTool, mapPlan, planGroup, mapFaq, mapTestimonial } from '../lib/api/mappers';

test('mapTool maps a CPT+ACF item to the Tool shape', () => {
  const item = {
    id: 1, slug: 'sureforms', title: { rendered: 'SureForms' }, content: { rendered: '' },
    acf: {
      desc: 'Flexible form builder.', domain: 'sureforms.com', category: 'Build',
      popular: true, tagline: 'Build forms that convert.', solo_price: 9,
      features: [{ icon: 'workflow', title: 'Conditional logic', desc: 'Show or hide fields.' }],
    },
  };
  expect(mapTool(item as any)).toEqual({
    slug: 'sureforms', name: 'SureForms', desc: 'Flexible form builder.',
    domain: 'sureforms.com', category: 'Build', popular: true,
    tagline: 'Build forms that convert.', soloPrice: 9,
    features: [{ icon: 'workflow', title: 'Conditional logic', desc: 'Show or hide fields.' }],
  });
});

test('mapTool omits popular/soloPrice when absent and decodes entities', () => {
  const item = { id: 2, slug: 'x', title: { rendered: 'A &amp; B' }, content: { rendered: '' },
    acf: { desc: 'd', domain: 'x.com', category: 'Grow', tagline: 't', features: [] } };
  const out = mapTool(item as any);
  expect(out.name).toBe('A & B');
  expect('popular' in out).toBe(false);
  expect('soloPrice' in out).toBe(false);
  expect(out.features).toEqual([]);
});

test('mapPlan derives btn/pop and splits newline features; planGroup reads plan_group', () => {
  const item = { id: 3, slug: 'business', title: { rendered: 'Business' }, content: { rendered: '' },
    acf: { plan_group: 'toolbox', desc: 'For businesses.', price_monthly: 60, price_annual: 50,
      featured: true, popular: true, features: 'All 12+ tools\nUp to 5 websites' } };
  expect(planGroup(item as any)).toBe('toolbox');
  expect(mapPlan(item as any)).toEqual({
    name: 'Business', desc: 'For businesses.', priceM: 60, priceA: 50,
    feat: true, pop: true, btn: 'plan-btn dark', features: ['All 12+ tools', 'Up to 5 websites'],
  });
});

test('mapPlan non-featured plan gets the outline button and no pop key', () => {
  const item = { id: 4, slug: 'personal', title: { rendered: 'Personal' }, content: { rendered: '' },
    acf: { plan_group: 'toolbox', desc: 'd', price_monthly: 30, price_annual: 25,
      featured: false, features: ['A', 'B'] } };
  const out = mapPlan(item as any);
  expect(out.btn).toBe('plan-btn out');
  expect('pop' in out).toBe(false);
});

test('mapFaq uses the title as question and ACF answer as answer', () => {
  const item = { id: 5, slug: 'q', title: { rendered: 'How do payments work?' }, content: { rendered: '' },
    acf: { answer: 'Pay and forget!' } };
  expect(mapFaq(item as any)).toEqual({ q: 'How do payments work?', a: 'Pay and forget!' });
});

test('mapTestimonial derives a single-letter initial from the name', () => {
  const item = { id: 6, slug: 't', title: { rendered: 'Hannah Whitfield' }, content: { rendered: '' },
    acf: { quote: 'It just works.', role: 'Owner, Bloom & Co.' } };
  expect(mapTestimonial(item as any)).toEqual({
    text: 'It just works.', initials: 'H', name: 'Hannah Whitfield', role: 'Owner, Bloom & Co.',
  });
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `PORT=3100 npx playwright test tests/content-mapping.spec.ts --project=app --reporter=list`
Expected: FAIL — cannot resolve `../lib/api/mappers`.

- [ ] **Step 4: Create `lib/api/mappers.ts`**

```ts
// Pure mappers: WordPress CPT + ACF payloads → front-end marketing types.
// No I/O — unit-testable in isolation. The CMS content model these expect is
// documented in docs/cms-content-model.md.

import type { Tool, Plan } from "@/lib/data";
import type { WpContent } from "@/lib/api/wp";

export type Faq = { q: string; a: string };
export type Testimonial = { text: string; initials: string; name: string; role: string };

type ToolAcf = {
  desc: string; domain: string; category: string; popular?: boolean;
  tagline: string; solo_price?: number;
  features: { icon: string; title: string; desc: string }[];
};
type PlanAcf = {
  plan_group: string; desc: string;
  price_monthly: number; price_annual: number;
  featured?: boolean; popular?: boolean;
  features: string[] | string;
};
type FaqAcf = { answer?: string };
type TestimonialAcf = { quote: string; role: string };

export type WpTool = WpContent<ToolAcf> & { acf: ToolAcf };
export type WpPlan = WpContent<PlanAcf> & { acf: PlanAcf };
export type WpFaq = WpContent<FaqAcf> & { acf: FaqAcf };
export type WpTestimonial = WpContent<TestimonialAcf> & { acf: TestimonialAcf };

/** Minimal HTML-entity decode for WP `rendered` strings used as plain text. */
function decode(s: string): string {
  return s
    .replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">")
    .replace(/&#8217;/g, "’").replace(/&#8211;/g, "–").replace(/&quot;/g, '"');
}

export function mapTool(item: WpTool): Tool {
  const a = item.acf;
  return {
    slug: item.slug,
    name: decode(item.title.rendered),
    desc: a.desc,
    domain: a.domain,
    category: a.category,
    ...(a.popular ? { popular: true } : {}),
    tagline: a.tagline,
    features: (a.features ?? []).map((f) => ({ icon: f.icon, title: f.title, desc: f.desc })),
    ...(typeof a.solo_price === "number" ? { soloPrice: a.solo_price } : {}),
  };
}

export function planGroup(item: WpPlan): "toolbox" | "retainer" {
  return item.acf.plan_group === "retainer" ? "retainer" : "toolbox";
}

export function mapPlan(item: WpPlan): Plan {
  const a = item.acf;
  const featured = !!a.featured;
  const features = Array.isArray(a.features)
    ? a.features
    : String(a.features ?? "").split("\n").map((s) => s.trim()).filter(Boolean);
  return {
    name: decode(item.title.rendered),
    desc: a.desc,
    priceM: a.price_monthly,
    priceA: a.price_annual,
    feat: featured,
    ...(a.popular ? { pop: true } : {}),
    btn: featured ? "plan-btn dark" : "plan-btn out", // presentation, derived front-end
    features,
  };
}

export function mapFaq(item: WpFaq): Faq {
  return { q: decode(item.title.rendered), a: decode(item.acf.answer ?? item.content?.rendered ?? "") };
}

export function mapTestimonial(item: WpTestimonial): Testimonial {
  const name = decode(item.title.rendered);
  return {
    text: decode(item.acf.quote),
    initials: name.trim().charAt(0).toUpperCase(), // matches the single-letter mock convention
    name,
    role: decode(item.acf.role),
  };
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `PORT=3100 npx playwright test tests/content-mapping.spec.ts --project=app --reporter=list`
Expected: PASS (6 tests).

- [ ] **Step 6: Commit**

```bash
git add lib/data.ts lib/api/mappers.ts tests/content-mapping.spec.ts
git commit -m "feat: add pure CPT+ACF → front-end mappers, add Tool.soloPrice"
```

---

## Task 4: Content layer — fetch + map + fallback

**Files:**
- Modify: `lib/api/content.ts` (rewrite bodies; keep signatures)
- Test: `tests/content-live.spec.ts`

**Interfaces:**
- Consumes: `useMockData` (Task 1); `listCpt` (Task 2); mappers + `Wp*` types (Task 3); static data from `lib/data`.
- Produces (unchanged signatures): `getTools(): Promise<Tool[]>`, `getToolBySlug(slug): Promise<Tool|undefined>`, `getToolboxPlans(): Promise<Plan[]>`, `getRetainerPlans(): Promise<Plan[]>`, `getFaqs(): Promise<Faq[]>`, `getTestimonials(): Promise<Testimonial[]>`, `getSoloPrices(): Promise<Record<string,number>>`; re-exports `type Faq`, `type Testimonial`.

- [ ] **Step 1: Write the failing test**

```ts
// tests/content-live.spec.ts
import { test, expect } from '@playwright/test';
import { TOOLBOX_TOOLS } from '../lib/data';

const realFetch = globalThis.fetch;
test.afterEach(() => { globalThis.fetch = realFetch; });

test('mock mode returns the static tools unchanged', async () => {
  delete process.env.NEXT_PUBLIC_WORDPRESS_URL;
  const c = await import(`../lib/api/content?m=${Date.now()}`);
  expect(await c.getTools()).toEqual(TOOLBOX_TOOLS);
});

test('live mode maps CPT items from wp/v2', async () => {
  process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io';
  globalThis.fetch = (async () => ({ ok: true, json: async () => ([
    { id: 1, slug: 'demo', title: { rendered: 'Demo' }, content: { rendered: '' },
      acf: { desc: 'd', domain: 'demo.com', category: 'Build', tagline: 't', features: [] } },
  ]) })) as unknown as typeof fetch;
  const c = await import(`../lib/api/content?l=${Date.now()}`);
  const tools = await c.getTools();
  expect(tools).toHaveLength(1);
  expect(tools[0]).toMatchObject({ slug: 'demo', name: 'Demo', category: 'Build' });
});

test('live fetch failure falls back to static data (no throw, no blank)', async () => {
  process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io';
  globalThis.fetch = (async () => ({ ok: false, status: 500, statusText: 'err' })) as unknown as typeof fetch;
  const c = await import(`../lib/api/content?f=${Date.now()}`);
  expect(await c.getTools()).toEqual(TOOLBOX_TOOLS);
});

test('empty CPT result falls back to static data', async () => {
  process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io';
  globalThis.fetch = (async () => ({ ok: true, json: async () => ([]) })) as unknown as typeof fetch;
  const c = await import(`../lib/api/content?e=${Date.now()}`);
  expect(await c.getTools()).toEqual(TOOLBOX_TOOLS);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `PORT=3100 npx playwright test tests/content-live.spec.ts --project=app --reporter=list`
Expected: FAIL — current `content.ts` calls `config.wpApiUrl` (removed) / has no fallback-on-error; the live and fallback tests fail.

- [ ] **Step 3: Rewrite `lib/api/content.ts`**

```ts
// Content data-access layer (server-side). Marketing content flows through here.
// Live: fetch CPTs from wp/v2 and map ACF → front-end types (lib/api/mappers.ts).
// Fallback: static data from lib/data.ts whenever the CMS is unconfigured, a CPT
// is absent/empty, or a fetch fails — a public page must never render blank.

import {
  TOOLBOX_TOOLS, TOOLBOX_PLANS, RETAINER_PLANS, FAQS, HOME_REVIEWS, SOLO_PRICES,
  type Tool, type Plan,
} from "@/lib/data";
import { useMockData } from "@/lib/config";
import { listCpt } from "@/lib/api/wp";
import {
  mapTool, mapPlan, planGroup, mapFaq, mapTestimonial,
  type Faq, type Testimonial, type WpTool, type WpPlan, type WpFaq, type WpTestimonial,
} from "@/lib/api/mappers";

export type { Faq, Testimonial };

/** Run `fetcher` live; on mock mode, empty result, or any error, return `fallback`. */
async function liveOrFallback<T>(fallback: T, label: string, fetcher: () => Promise<T>): Promise<T> {
  if (useMockData) return fallback;
  try {
    const value = await fetcher();
    if (Array.isArray(value) && value.length === 0) {
      console.warn(`[content] ${label} returned empty; using static fallback`);
      return fallback;
    }
    return value;
  } catch (err) {
    console.warn(`[content] ${label} failed; using static fallback:`, err);
    return fallback;
  }
}

export async function getTools(): Promise<Tool[]> {
  return liveOrFallback(TOOLBOX_TOOLS, "getTools", async () =>
    ((await listCpt("bw_tool")) as WpTool[]).map(mapTool));
}

export async function getToolBySlug(slug: string): Promise<Tool | undefined> {
  return (await getTools()).find((t) => t.slug === slug);
}

async function getPlans(group: "toolbox" | "retainer"): Promise<Plan[]> {
  const fallback = group === "retainer" ? RETAINER_PLANS : TOOLBOX_PLANS;
  return liveOrFallback(fallback, `getPlans:${group}`, async () => {
    const items = (await listCpt("bw_plan")) as WpPlan[];
    return items.filter((i) => planGroup(i) === group).map(mapPlan);
  });
}

export function getToolboxPlans(): Promise<Plan[]> { return getPlans("toolbox"); }
export function getRetainerPlans(): Promise<Plan[]> { return getPlans("retainer"); }

export async function getFaqs(): Promise<Faq[]> {
  return liveOrFallback(FAQS, "getFaqs", async () =>
    ((await listCpt("bw_faq")) as WpFaq[]).map(mapFaq));
}

export async function getTestimonials(): Promise<Testimonial[]> {
  return liveOrFallback(HOME_REVIEWS, "getTestimonials", async () =>
    ((await listCpt("bw_testimonial")) as WpTestimonial[]).map(mapTestimonial));
}

/** slug → per-tool solo price, for the savings calculator. Derived from tools when live. */
export async function getSoloPrices(): Promise<Record<string, number>> {
  if (useMockData) return SOLO_PRICES;
  const tools = await getTools();
  const fromTools = Object.fromEntries(
    tools.filter((t) => typeof t.soloPrice === "number").map((t) => [t.slug, t.soloPrice as number]),
  );
  return Object.keys(fromTools).length ? fromTools : SOLO_PRICES;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `PORT=3100 npx playwright test tests/content-live.spec.ts --project=app --reporter=list`
Expected: PASS (4 tests).

- [ ] **Step 5: Verify no UI drift — build + full existing suites**

Run: `npm run build`
Expected: build succeeds (all `content.ts` consumers still typecheck).

Run: `PORT=3100 npx playwright test tests/fixtures-parity.spec.ts tests/site.spec.js --project=app --reporter=list`
Expected: PASS — mock-mode rendering is byte-for-byte unchanged.

- [ ] **Step 6: Commit**

```bash
git add lib/api/content.ts tests/content-live.spec.ts
git commit -m "feat: content layer fetches+maps CPTs live with static fallback"
```

---

## Task 5: Revalidation receiver — `app/api/revalidate/route.ts`

**Files:**
- Create: `app/api/revalidate/route.ts`
- Test: `tests/revalidate.spec.js`

**Interfaces:**
- Consumes: `config.revalidateSecret` (Task 1); `revalidatePath` (`next/cache`); `timingSafeEqual` (`node:crypto`).
- Produces: `POST` handler at `/api/revalidate`.

- [ ] **Step 1: Write the failing test**

```js
// tests/revalidate.spec.js
import { test, expect } from '@playwright/test';

// The test server does not set REVALIDATE_SECRET, so the route must fail closed:
// every request is rejected until a secret is configured. This pins the
// security-critical branch (constant-time compare, reject on mismatch/absence).
test.describe('POST /api/revalidate', () => {
  test('rejects a request with no secret header (fails closed)', async ({ request }) => {
    const res = await request.post('/api/revalidate', { data: { paths: ['/about'] } });
    expect(res.status()).toBe(401);
  });

  test('rejects a request with a wrong secret', async ({ request }) => {
    const res = await request.post('/api/revalidate', {
      headers: { 'x-blueworx-revalidate': 'definitely-wrong' },
      data: { paths: ['/about'] },
    });
    expect(res.status()).toBe(401);
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `PORT=3100 npx playwright test tests/revalidate.spec.js --project=app --reporter=list`
Expected: FAIL — route does not exist (404, not 401).

> Requires a rebuild for the new route: `npm run build` before running if using `next start`; if reusing a `next dev` server on 3100, the route is picked up automatically.

- [ ] **Step 3: Create `app/api/revalidate/route.ts`**

```ts
import { revalidatePath } from "next/cache";
import { NextRequest, NextResponse } from "next/server";
import { timingSafeEqual } from "node:crypto";
import { config } from "@/lib/config";

// CMS → frontend on-demand ISR. The plugin POSTs { paths: [...] } with the shared
// secret in X-Blueworx-Revalidate. See HEADLESS_INTEGRATION.md §8.
export async function POST(req: NextRequest) {
  const provided = req.headers.get("x-blueworx-revalidate") ?? "";
  const expected = config.revalidateSecret;
  const a = Buffer.from(provided);
  const b = Buffer.from(expected);
  if (!expected || a.length !== b.length || !timingSafeEqual(a, b)) {
    return NextResponse.json({ ok: false }, { status: 401 });
  }
  const body = (await req.json().catch(() => ({}))) as { paths?: string[] };
  const paths = Array.isArray(body.paths) ? body.paths : [];
  for (const p of paths) revalidatePath(p);
  return NextResponse.json({ ok: true, revalidated: paths });
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm run build && PORT=3100 npx playwright test tests/revalidate.spec.js --project=app --reporter=list`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/api/revalidate/route.ts tests/revalidate.spec.js
git commit -m "feat: add secret-verified /api/revalidate ISR receiver"
```

---

## Task 6: Browser auth client — `lib/wp-client.ts`

**Files:**
- Create: `lib/wp-client.ts`
- Test: `tests/wp-client.spec.ts`

**Interfaces:**
- Consumes: `config.blueworxApi` (Task 1).
- Produces: `setAccessToken(t)`, `getAccessToken()`, `api(path, init?)`, `login(loginId, password)`, `logout()`, `restoreSession()`. (Infrastructure — not imported by any Cycle 1 page.)

- [ ] **Step 1: Write the failing test**

```ts
// tests/wp-client.spec.ts
import { test, expect } from '@playwright/test';

const realFetch = globalThis.fetch;
test.afterEach(() => { globalThis.fetch = realFetch; });

test('api() refreshes once on 401 then retries with the new token', async () => {
  process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io';
  const calls: string[] = [];
  let authed = false;
  globalThis.fetch = (async (url: string, init?: RequestInit) => {
    calls.push(url);
    if (url.endsWith('/auth/refresh')) { authed = true; return { ok: true, json: async () => ({ access_token: 'tok' }) }; }
    if (url.endsWith('/auth/me')) return { status: authed ? 200 : 401, ok: authed, json: async () => ({ id: 1 }) };
    return { status: 404, ok: false };
  }) as unknown as typeof fetch;

  const wc = await import(`../lib/wp-client?a=${Date.now()}`);
  const res = await wc.api('/auth/me');
  expect(res.status).toBe(200);
  expect(calls.filter((u) => u.endsWith('/auth/me'))).toHaveLength(2); // initial + retry
  expect(calls.filter((u) => u.endsWith('/auth/refresh'))).toHaveLength(1);
});

test('login stores the access token and returns the user', async () => {
  process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io';
  globalThis.fetch = (async () => ({ ok: true, json: async () => ({ access_token: 't', user: { id: 7 } }) })) as unknown as typeof fetch;
  const wc = await import(`../lib/wp-client?b=${Date.now()}`);
  const user = await wc.login('jane@example.com', 'pw');
  expect(user).toEqual({ id: 7 });
  expect(wc.getAccessToken()).toBe('t');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `PORT=3100 npx playwright test tests/wp-client.spec.ts --project=app --reporter=list`
Expected: FAIL — cannot resolve `../lib/wp-client`.

- [ ] **Step 3: Create `lib/wp-client.ts`**

```ts
"use client";

// Browser auth client for the plugin: in-memory JWT access token + single-flight
// refresh on 401. All calls use credentials:'include' so the HttpOnly refresh
// cookie is sent. See HEADLESS_INTEGRATION.md §10. Cycle 2 consumes this; Cycle 1
// ships it as reviewed infrastructure.

import { config } from "@/lib/config";

let accessToken: string | null = null;
let refreshing: Promise<boolean> | null = null;

export function setAccessToken(t: string | null) { accessToken = t; }
export function getAccessToken() { return accessToken; }

async function refresh(): Promise<boolean> {
  refreshing ??= (async () => {
    try {
      const res = await fetch(`${config.blueworxApi}/auth/refresh`, {
        method: "POST",
        credentials: "include",
      });
      if (!res.ok) return false;
      const data = await res.json();
      accessToken = data.access_token;
      return true;
    } catch {
      return false;
    } finally {
      refreshing = null;
    }
  })();
  return refreshing;
}

/** Authenticated fetch against blueworx/v1 with one automatic refresh+retry. */
export async function api(path: string, init: RequestInit = {}): Promise<Response> {
  const call = () =>
    fetch(`${config.blueworxApi}${path}`, {
      ...init,
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
        ...(accessToken ? { Authorization: `Bearer ${accessToken}` } : {}),
        ...init.headers,
      },
    });

  let res = await call();
  if (res.status === 401 && (await refresh())) res = await call();
  return res;
}

export async function login(loginId: string, password: string) {
  const res = await fetch(`${config.blueworxApi}/auth/login`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ login: loginId, password }),
  });
  if (!res.ok) throw await res.json();
  const data = await res.json();
  accessToken = data.access_token;
  return data.user;
}

export async function logout() {
  await fetch(`${config.blueworxApi}/auth/logout`, { method: "POST", credentials: "include" });
  accessToken = null;
}

/** Restore a session on app load (a hard reload loses the in-memory token). */
export async function restoreSession(): Promise<boolean> {
  return refresh();
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `PORT=3100 npx playwright test tests/wp-client.spec.ts --project=app --reporter=list`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add lib/wp-client.ts tests/wp-client.spec.ts
git commit -m "feat: add browser auth client (in-memory JWT + single-flight refresh)"
```

---

## Task 7: Catch-all WordPress page — `app/[...slug]/page.tsx`

**Files:**
- Create: `app/[...slug]/page.tsx`
- Test: `tests/wp-page-404.spec.js`

**Interfaces:**
- Consumes: `useMockData` (Task 1); `resolve`, `getByRestUrl` (Task 2); `notFound` (`next/navigation`).
- Produces: a dynamic catch-all route. In mock mode (no CMS), any unmatched path 404s — preserving today's behavior. When live, it resolves the path and renders the `wp/v2` body, or 404s.

- [ ] **Step 1: Write the failing test**

```js
// tests/wp-page-404.spec.js
import { test, expect } from '@playwright/test';

// The demo server runs in mock mode (no NEXT_PUBLIC_WORDPRESS_URL), so the catch-all
// must not hijack unknown paths — they still 404 exactly as before. (The live
// resolve→wp/v2 path is verified against a real CMS, not here: Server-Component
// fetches run server-side and can't be intercepted from the browser context.)
test('an unmatched path returns 404 in mock mode', async ({ page }) => {
  const res = await page.goto('/this-path-does-not-exist-xyz');
  expect(res.status()).toBe(404);
});

test('a real front-end route still renders (catch-all does not shadow it)', async ({ page }) => {
  const res = await page.goto('/pricing');
  expect(res.status()).toBe(200);
});
```

- [ ] **Step 2: Run test to verify it fails (or confirm baseline)**

Run: `npm run build && PORT=3100 npx playwright test tests/wp-page-404.spec.js --project=app --reporter=list`
Expected: the `/pricing` test passes at baseline; write the route so the 404 test also passes once the catch-all exists. (If the catch-all is added incorrectly and swallows `/pricing`, the second test catches it.)

- [ ] **Step 3: Create `app/[...slug]/page.tsx`**

```tsx
import { notFound } from "next/navigation";
import { useMockData } from "@/lib/config";
import { resolve, getByRestUrl } from "@/lib/api/wp";

// Catch-all for CMS-authored pages. Existing front-end routes (about, pricing, …)
// are more specific and take precedence. See HEADLESS_INTEGRATION.md §6.1.
export const dynamic = "force-dynamic";

export default async function WordPressPage({
  params,
}: {
  params: Promise<{ slug: string[] }>;
}) {
  const { slug } = await params;
  const uri = "/" + (slug?.join("/") ?? "");

  // No CMS configured → behave exactly like today: unmatched routes 404.
  if (useMockData) notFound();

  const r = await resolve(uri);
  if (r.type === "404" || !r.rest_url) notFound();

  const page = await getByRestUrl(r.rest_url);
  return (
    <main className="wp-page">
      <h1 dangerouslySetInnerHTML={{ __html: page.title.rendered }} />
      <div dangerouslySetInnerHTML={{ __html: page.content.rendered }} />
    </main>
  );
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm run build && PORT=3100 npx playwright test tests/wp-page-404.spec.js --project=app --reporter=list`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/[...slug]/page.tsx tests/wp-page-404.spec.js
git commit -m "feat: add catch-all resolve→wp/v2 page (mock mode preserves 404s)"
```

---

## Task 8: CMS content model doc + API_CONTRACT reconciliation

**Files:**
- Create: `docs/cms-content-model.md`
- Modify: `docs/API_CONTRACT.md` (prepend reconciliation note)

**Interfaces:** documentation only — no code.

- [ ] **Step 1: Create `docs/cms-content-model.md`**

```markdown
# BlueWorx CMS Content Model (Cycle 1)

The headless front-end maps **from** these WordPress CPTs + ACF fields into its
marketing types. The CMS must register each CPT with `show_in_rest: true` and list
its key under **BlueWorx → Headless → CPTs in REST** (plugin HEADLESS_INTEGRATION.md
§2/§6.4). Until these exist, the front-end serves static fallback data — so CMS and
front-end work can land independently.

Post **title** = display name; post **slug** = URL slug, unless noted.
`icon` values must be one of: chat, mail, chart, clock, sms, doc, server, users,
plug, book, cart, calendar, phone, sparkles, code, zap, git, palette, workflow,
gauge, shield (front-end `lib/icons.ts`; adding one is a front-end PR first).

## `bw_tool` → Tool
- title = `name`, slug = `slug`
- ACF: `desc` (text), `domain` (text), `category` (select: Build|Grow|Sell|Automate|Support),
  `popular` (true/false), `tagline` (textarea), `solo_price` (number),
  `features` (repeater of `{ icon (select from icon keys), title (text), desc (textarea) }`)

## `bw_plan` → Plan
- title = `name`
- ACF: `plan_group` (select: toolbox|retainer), `desc` (textarea),
  `price_monthly` (number), `price_annual` (number),
  `featured` (true/false), `popular` (true/false),
  `features` (repeater OR textarea — one bullet per line)
- `btn` is NOT stored (derived front-end from `featured`)

## `bw_faq` → Faq
- title = `q` (question)
- ACF: `answer` (wysiwyg/textarea) — falls back to post content if empty

## `bw_testimonial` → Testimonial
- title = author `name`
- ACF: `quote` (textarea → `text`), `role` (text)
- `initials` is NOT stored (derived front-end from `name`)

Mapping is implemented in `lib/api/mappers.ts`; the fetch/fallback orchestration in
`lib/api/content.ts`. If a field is renamed or dropped CMS-side, update both.
```

- [ ] **Step 2: Prepend a reconciliation note to `docs/API_CONTRACT.md`**

Insert immediately after the H1 (`# BlueWorx Headless API Contract`):

```markdown
> **⚠️ Reconciled 2026-07-13.** The shipped `blueworx_labs_wordpress` plugin exposes a
> **generic** headless-CMS API, not the bespoke `/tools`,`/plans`,`/faqs`,`/testimonials`,
> `/portal/me` endpoints this document originally assumed. The authoritative contract is
> the plugin's `HEADLESS_INTEGRATION.md`. Cycle 1 wires marketing content via WordPress
> CPTs+ACF (see `docs/cms-content-model.md`) mapped in `lib/api/`; the portal (§5) and
> SureCart move to Cycle 2. Sections below are retained for the field shapes the UI still
> requires, but endpoint names/auth here are superseded by the plugin guide + the Cycle 1
> spec (`docs/superpowers/specs/2026-07-13-headless-plugin-integration-cycle1-design.md`).
```

- [ ] **Step 3: Commit**

```bash
git add docs/cms-content-model.md docs/API_CONTRACT.md
git commit -m "docs: add CMS content model, reconcile API_CONTRACT to real plugin"
```

---

## Task 9: Version bump + changelog + full verification

**Files:**
- Modify: `package.json` (`0.3.2` → `0.4.0`)
- Modify: `CHANGELOG.md` (new `## [0.4.0]` entry at top)

**Interfaces:** release hygiene — satisfies the CI guardrail checked by `tests/release-hygiene.spec.js`.

- [ ] **Step 1: Bump the version**

In `package.json`, change `"version": "0.3.2"` to `"version": "0.4.0"`.

- [ ] **Step 2: Add the changelog entry**

Insert below the intro paragraph in `CHANGELOG.md`, above `## [0.3.2]`:

```markdown
## [0.4.0] - 2026-07-13

### Changed

- **Data layer re-based on the live `blueworx_labs_wordpress` plugin (v1.10.1).** `lib/config.ts` now derives two REST bases (`blueworx/v1` + `wp/v2`) from a single `NEXT_PUBLIC_WORDPRESS_URL` origin, replacing the invented `NEXT_PUBLIC_WP_API_URL`/`WP_API_TOKEN`. Marketing content (tools, plans, FAQs, testimonials) fetches from WordPress CPTs+ACF via `lib/api/wp.ts` + pure mappers in `lib/api/mappers.ts`, and `lib/api/content.ts` keeps its exact signatures so no page/component changed. Every function falls back to the static `lib/data.ts` values while the CMS is unconfigured, a CPT is empty, or a fetch fails — public pages never render blank. `docs/API_CONTRACT.md` reconciled to the real plugin; the content model the CMS must create is documented in `docs/cms-content-model.md`.

### Added

- `app/api/revalidate/route.ts` — secret-verified on-demand ISR receiver (constant-time compare, fails closed without a configured secret).
- `lib/wp-client.ts` — browser auth client (in-memory JWT access token + single-flight refresh on 401). Infrastructure for the Cycle 2 portal; not yet consumed by any page.
- `app/[...slug]/page.tsx` — catch-all that resolves a path via `/resolve` and renders the `wp/v2` body; unmatched paths (and all of mock mode) still 404.
- Tests: config derivation, wp fetchers, CPT→type mappers, content fetch/fallback, revalidate auth, auth-client refresh, and catch-all 404 behaviour.

### Notes

- Portal (auth-gated §5 data), SureCart, and the contact-form backend are deferred to Cycle 2; the portal continues to render demo data.
```

- [ ] **Step 3: Full CI-parity verification**

Run: `npm run lint`
Expected: passes (no new errors).

Run: `npm run build`
Expected: build succeeds.

Run: `PORT=3100 npx playwright test`
Expected: all projects pass — the new specs plus the existing `fixtures-parity`, `site`, `contact-api`, `portal-auth`, and `release-hygiene` suites.

- [ ] **Step 4: Commit**

```bash
git add package.json CHANGELOG.md
git commit -m "chore: bump to 0.4.0 for headless plugin integration (Cycle 1)"
```

---

## Self-Review

**Spec coverage** (spec §11 deliverables → tasks):
1. Config two-base + `.env.example` → Task 1 ✓
2. `lib/api/wp.ts` fetchers + `rewriteMenuUrl` → Task 2 ✓
3. `content.ts` fetch+map+fallback → Task 4 (mappers Task 3) ✓
4. `app/api/revalidate/route.ts` → Task 5 ✓
5. `lib/wp-client.ts` → Task 6 ✓
6. `/resolve`→`wp/v2` demonstrated → Task 7 ✓
7. `docs/cms-content-model.md` → Task 8 ✓
8. `API_CONTRACT.md` reconciled → Task 8 ✓
9. Tests + version bump + changelog → every task + Task 9 ✓

Spec §5 fallback-not-fail behavior → Task 4 `liveOrFallback` ✓. Spec §3.2 `rewriteMenuUrl` provided but nav-switch deferred (spec §10 open item — capability shipped, live nav unchanged this cycle; consistent with "no UI changes"). SureCart / portal / contact backend explicitly out of scope → untouched.

**Placeholder scan:** No "TBD"/"handle edge cases"/"similar to Task N" — every code and test block is complete.

**Type consistency:** `WpTool/WpPlan/WpFaq/WpTestimonial` defined in Task 3 are the exact casts used in Task 4. `useMockData`, `config.blueworxApi`, `config.wpApi`, `config.revalidateSecret`, `config.wpOrigin` defined in Task 1 are used consistently in Tasks 2/4/5/6/7. `listCpt`/`resolve`/`getByRestUrl` signatures in Task 2 match their calls in Tasks 4/7. `Tool.soloPrice` added in Task 3 before `mapTool`/`getSoloPrices` use it. Mapper output keys (`btn`, `pop`, `feat`, `priceM`, `priceA`, `initials`) match the `Plan`/`Testimonial` shapes in `lib/data.ts`/`portal.ts`.

**One caveat carried from the spec (not a gap):** live `/resolve`→`wp/v2` rendering and the revalidate 200-path are only fully verifiable against a real WordPress instance (Server-Component fetches can't be browser-intercepted; the test server has no secret). Both are noted in the relevant tests rather than hidden.
