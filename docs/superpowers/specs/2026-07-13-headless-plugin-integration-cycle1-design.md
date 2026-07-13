# Headless Plugin Integration — Cycle 1: Foundation + Public Marketing Site

**Date:** 2026-07-13
**Status:** Design — approved for planning
**Repo:** `bluegroup_project_blueworx` (Next.js headless front-end)
**Consumes:** `blueworx_labs_wordpress` plugin v1.10.1 — see its `HEADLESS_INTEGRATION.md`

---

## 1. Background & problem

The BlueWorx WordPress plugin is now live and exposes a **generic headless-CMS API**, not the
bespoke marketing API this front-end was scaffolded against. The current data layer
([`lib/api/`](../../../lib/api), [`docs/API_CONTRACT.md`](../../API_CONTRACT.md)) assumes endpoints
that **do not exist** in the plugin: `GET /tools`, `/plans`, `/faqs`, `/testimonials`,
`/portal/me`, `POST /contact`, plus a server-side session cookie and a `NEXT_PUBLIC_WP_API_URL`
pointed at `/blueworx/v1`.

What the plugin actually ships:

- **`/wp-json/blueworx/v1/`** — auth (JWT + rotating refresh cookie), `/menus/{location}`,
  `/site`, `/resolve`, `/acf-options`, optional SureCart proxy.
- **`/wp-json/wp/v2/`** — the content bodies (pages/posts/CPTs), with ACF fields attached as an
  `acf` object on each item.

The model is: `/resolve?uri=/path` → returns a `wp/v2` URL → fetch the body.

This work is split into two cycles. **This spec covers Cycle 1 only.**

- **Cycle 1 (this spec):** Foundation + the public, unauthenticated marketing site, rendered
  statically / ISR against the real plugin. No auth. Achievable today against v1.10.1.
- **Cycle 2 (separate spec):** The client portal — browser-only JWT auth, SureCart, and new
  normalized plugin endpoints for bespoke project data. Depends on plugin changes.

## 2. Goals / non-goals

**Goals (Cycle 1):**

1. Re-base the config layer on the plugin's two-base model (`NEXT_PUBLIC_WORDPRESS_URL`).
2. Add the browser auth client (`lib/wp-client.ts`) from the guide's §10 — infrastructure only,
   consumed in Cycle 2, shipped now so it is reviewed once.
3. Add the on-demand revalidation receiver (`app/api/revalidate/route.ts`, §8).
4. Wire the generic content the plugin serves today: `/site`, `/menus/{location}`, and
   `/resolve` → `wp/v2` for real WordPress pages.
5. Move marketing content (Tools, Plans, FAQs, Testimonials) to WordPress **CPTs + ACF**, fetched
   from `wp/v2` and **mapped** into the existing front-end types — no UI/component changes.
6. Preserve a working build at every step: while the CMS is unconfigured or a CPT is absent, the
   front-end falls back to today's static data. Nothing renders empty.
7. Produce a precise **CMS setup document** (CPT keys, ACF field names/types) for the plugin/CMS
   team to create the content model.

**Non-goals (deferred to Cycle 2 or later):**

- Any authenticated / per-user data, login UI, or portal rewrite.
- SureCart integration.
- The contact-form backend endpoint (keeps working via `CONTACT_FORWARD_URL` as-is).
- Category/tag/date archive routing (the plugin's `/resolve` returns `404` for these by design).

## 3. Architecture

### 3.1 Config layer — `lib/config.ts` (rewrite)

Replace the single-base config with the plugin's origin-plus-two-bases model.

```ts
export const WP_ORIGIN   = process.env.NEXT_PUBLIC_WORDPRESS_URL?.replace(/\/$/, "") || "";
export const BLUEWORX_API = WP_ORIGIN ? `${WP_ORIGIN}/wp-json/blueworx/v1` : "";
export const WP_API       = WP_ORIGIN ? `${WP_ORIGIN}/wp-json/wp/v2` : "";

export const config = {
  wpOrigin: WP_ORIGIN,
  blueworxApi: BLUEWORX_API,
  wpApi: WP_API,
  revalidateSecret: process.env.REVALIDATE_SECRET || "",   // server-only
  contactForwardUrl: process.env.CONTACT_FORWARD_URL || "",
} as const;

/** True while there is no live CMS to talk to. Single kill-switch: mock ⇄ live. */
export const useMockData = WP_ORIGIN === "";
```

Retired env vars: `NEXT_PUBLIC_WP_API_URL`, `WP_API_TOKEN` (public content is unauthenticated;
no bearer token). `SURECART_API_TOKEN` and `PORTAL_REQUIRE_AUTH` move to Cycle 2 (leave in
`.env.example` documented as Cycle 2, or remove — see §7). `.env.example` updated to match.

### 3.2 Low-level WordPress fetchers — `lib/api/wp.ts` (new)

One focused module wrapping the plugin's generic endpoints. Single responsibility: talk HTTP to
the CMS and return typed JSON. No mapping to front-end shapes here.

```ts
getSite(): Promise<WpSite>                         // GET blueworx/v1/site
getMenu(location: string): Promise<MenuItem[]>     // GET blueworx/v1/menus/{location}
resolve(uri: string): Promise<ResolveResult>       // GET blueworx/v1/resolve?uri=
getAcfOptions(): Promise<Record<string, unknown>>  // GET blueworx/v1/acf-options
getByRestUrl<T>(restUrl: string): Promise<T>       // GET an absolute wp/v2 URL
listCpt<T>(type: string, params?): Promise<T[]>    // GET wp/v2/{type}?...
```

- All content fetches use `next: { revalidate: N, tags: [...] }` for ISR (default 300s; site/menus
  can be longer). No `Authorization` header — public content.
- `MenuItem.url` values point at the WP origin. A `rewriteMenuUrl(url)` helper strips the origin to
  a path so `<Link>`s stay on the front-end (guide §5.3).
- Types (`WpSite`, `MenuItem`, `ResolveResult`) mirror the guide's §5–§6 shapes.

### 3.3 Marketing content — `lib/api/content.ts` (rewrite mapping)

The public functions keep their **existing signatures and return types** (`getTools`,
`getToolboxPlans`, `getRetainerPlans`, `getFaqs`, `getTestimonials`, `getSoloPrices`) so no page or
component changes. Internally each now:

1. If `useMockData` → return today's static data from `lib/data.ts` (unchanged fallback).
2. Else fetch the corresponding CPT from `wp/v2` via `lib/api/wp.ts` and **map** each item's
   `acf` object into the front-end type.
3. On fetch failure or empty result → fall back to the static data and log a warning (never throw
   an empty marketing page — see §5).

Mapping is isolated in small pure functions (`mapTool`, `mapPlan`, `mapFaq`, `mapTestimonial`) so
they are unit/parity-testable. `soloPrice` is read from the tool's ACF; `getSoloPrices` derives the
`slug → number` map from the tools list (retiring the separate source once CPTs are live, guarded by
the existing `tests/fixtures-parity.spec.ts`). `btn` and `initials` are **derived front-end**, not
stored in the CMS (presentation, per API_CONTRACT §3.2 / §8).

### 3.4 Real WordPress pages — `/resolve` → `wp/v2`

Add a catch-all route (`app/[...slug]/page.tsx`) OR wire `/resolve` into existing routes where a
page maps to WordPress content. Flow (guide §6.1):

1. `resolve(path)` → `{ type, rest_url, template }`.
2. `type === "404"` → `notFound()`.
3. `type === "front"` → render the configured front page's content.
4. else `getByRestUrl(rest_url)` → render `title.rendered`, `content.rendered`, `acf`.

Scope note: the current site's marketing pages are bespoke React (About, Pricing, Toolbox, etc.),
not WordPress-authored bodies. Cycle 1 adds the resolve→wp/v2 capability and demonstrates it on
**one** real WordPress page (proof of the pipe) without converting the existing designed pages.
Converting bespoke pages to CMS-authored content is out of scope.

### 3.5 On-demand revalidation — `app/api/revalidate/route.ts` (new)

Exactly the guide's §8 receiver: read `X-Blueworx-Revalidate`, constant-time compare against
`REVALIDATE_SECRET`, `revalidatePath(p)` for each path in the body, return `{ ok, revalidated }`.
`401` on mismatch.

### 3.6 Browser auth client — `lib/wp-client.ts` (new, infra for Cycle 2)

Drop in the guide's §10 reference client verbatim-adapted: in-memory access token, single-flight
`refresh()` on `401`, `login` / `logout` / `restoreSession` / `api()`. All calls use
`credentials: 'include'`. **Not consumed by any Cycle 1 page** — shipped now so the auth core is
reviewed once and Cycle 2 builds on a stable base. Covered by a light unit test of the
refresh-once-then-retry logic (mocked fetch), not an E2E flow.

## 4. Data flow

```
Public page (Server Component)
  └─ getTools()/getFaqs()/… (lib/api/content.ts)
       ├─ useMockData → lib/data.ts (static)            [CMS unconfigured]
       └─ live → lib/api/wp.ts → GET wp/v2/{cpt}
                    → map acf → front-end type
                    (on error/empty → static fallback)

Real WP page (Server Component)
  └─ resolve(path) → rest_url → getByRestUrl → render

CMS content change → plugin POST → /api/revalidate → revalidatePath
```

Auth (Cycle 2, client only): `restoreSession()` on mount → `api()` with in-memory bearer.

## 5. Error handling & fallback

- **Marketing content:** fetch failure or empty array → log a warning, return the static
  `lib/data.ts` value. A public page must never render blank because the CMS hiccuped. (Differs
  from API_CONTRACT §7's "fail the build loudly" — during the migration window we prefer graceful
  degradation to a hard fail; revisit once CPTs are the sole source.)
- **`/resolve`:** `type === "404"` → Next `notFound()`. Network error → error boundary / 500.
- **Revalidate route:** secret mismatch → `401`; malformed body → ignore unknown paths, `200`.
- **Menu:** `404` from `/menus/{location}` (no menu assigned) → render the static nav fallback.

## 6. Testing (Playwright + light unit)

- **Parity/unit:** `mapTool`/`mapPlan`/`mapFaq`/`mapTestimonial` map a representative `wp/v2`+ACF
  payload to the exact front-end shape (fixtures). Extend `tests/fixtures-parity.spec.ts` so
  tools ⇄ solo-prices stay in lockstep.
- **Fallback:** with `NEXT_PUBLIC_WORDPRESS_URL` unset, the site renders identically to today
  (mock path) — guards the kill-switch.
- **Live-path (mocked):** Playwright route-intercepts `wp/v2/*` to assert pages render mapped CMS
  data and that a failed CMS response falls back to static without a blank page.
- **Revalidate route:** correct secret → `200 { revalidated }`; wrong/absent secret → `401`.
- **`wp-client` refresh:** unit test — a `401` triggers one refresh then one retry; concurrent
  calls share a single refresh (single-flight).

At least one Playwright test covers the primary new functionality, per CI guardrails.

## 7. CMS-side setup (deliverable for the plugin/CMS team)

Written as `docs/cms-content-model.md` in this repo (and mirrored to the plugin team). The
front-end maps **from** these; the CMS must register them with `show_in_rest: true` and the CPT
keys listed in **BlueWorx → Headless → CPTs in REST** (guide §2/§6.4). Post **title** is the
display name and post **slug** is the URL slug unless noted.

**`bw_tool`** (Tool) — title = `name`, slug = `slug`. ACF:
`desc` (text), `domain` (text), `category` (select: Build|Grow|Sell|Automate|Support),
`popular` (true/false), `tagline` (textarea), `solo_price` (number),
`features` (repeater of `{ icon (select — icon keys below), title (text), desc (textarea) }`).

**`bw_plan`** (Plan) — title = `name`. ACF:
`plan_group` (select: toolbox|retainer), `desc` (textarea),
`price_monthly` (number), `price_annual` (number),
`featured` (true/false), `popular` (true/false),
`features` (repeater/textarea, one bullet per line). `btn` is **not** stored (derived front-end).

**`bw_faq`** (Faq) — title = `q` (question). ACF: `answer` (wysiwyg/textarea) — or post content.

**`bw_testimonial`** (Testimonial) — title = author `name`. ACF:
`quote` (textarea → `text`), `role` (text). `initials` is **not** stored (derived front-end).

**Allowed `icon` values** (must match [`lib/icons.ts`](../../../lib/icons.ts); adding an icon is a
front-end PR first): `chat, mail, chart, clock, sms, doc, server, users, plug, book, cart,
calendar, phone, sparkles, code, zap, git, palette, workflow, gauge, shield`.

Until these CPTs exist and are in-REST, the front-end serves the static fallback — so the CMS work
and the front-end work can land independently.

## 8. Environment variables (Netlify)

```bash
# WordPress origin (scheme + host, no trailing slash). Public — browser hits wp/v2 + auth.
NEXT_PUBLIC_WORDPRESS_URL=https://cms.blueworx.io

# Server-only. Matches BLUEWORX_LABS_REVALIDATE_SECRET on the CMS. Never NEXT_PUBLIC_.
REVALIDATE_SECRET=<same value as the CMS constant>

# Existing — where /api/contact forwards (unchanged this cycle).
CONTACT_FORWARD_URL=
```

`.env.example` rewritten to these. `NEXT_PUBLIC_WP_API_URL` / `WP_API_TOKEN` removed.

## 9. House conventions / CI

Next.js App Router + TS · Radix Themes · lucide-react · Tailwind · Netlify deploy. Branch off
`main` → PR → checks. CI guardrails: lint · build · **version bump** · **changelog** · no new dep
without `approved-deps.json` · Playwright test for new functionality. No new runtime dependency is
expected (uses built-in `fetch`, `next/cache`, `node:crypto`).

## 10. Risks & open items

- **No live WordPress to test against here.** All live-path tests are mock/route-intercept; real
  verification happens once the CMS is stood up with the CPTs. Flagged, not hidden.
- **Cross-site refresh cookie** (guide §4) matters only in Cycle 2, but the infra requirement —
  CMS on a subdomain of the front-end's registrable domain — should be raised with infra now.
- **Menu/nav:** the current nav is bespoke; wiring `/menus` is included as capability + fallback,
  not a forced replacement of the designed nav. Confirm during planning whether to switch the live
  nav to CMS-driven or keep it static this cycle.

## 11. Deliverables

1. `lib/config.ts` rewritten to the two-base model + `.env.example`.
2. `lib/api/wp.ts` (generic fetchers + `rewriteMenuUrl`).
3. `lib/api/content.ts` rewritten to fetch+map with static fallback.
4. `app/api/revalidate/route.ts`.
5. `lib/wp-client.ts` (auth infra for Cycle 2).
6. `/resolve` → `wp/v2` capability demonstrated on one real WP page.
7. `docs/cms-content-model.md` (CPT/ACF spec for the CMS team).
8. Updated `docs/API_CONTRACT.md` reconciled to the real plugin contract (or superseded by a
   pointer to the plugin's `HEADLESS_INTEGRATION.md`).
9. Tests per §6; version bump + changelog.
