# Cycle 2 — PR A: Cycle-1 Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the three dormant live-path gaps from Cycle 1 — a typed auth error, a graceful catch-all page on CMS outage, and `paths[]` validation on the revalidate route.

**Architecture:** Three isolated fixes, each with a pure function extracted so it is unit-testable under CI's static-import harness. No behaviour changes in mock mode; these only affect the live CMS path.

**Tech Stack:** Next.js 15 (App Router) · TypeScript · `@playwright/test` (test runner, `globalThis.fetch` mocking + pure-unit).

## Global Constraints

- Work on a branch, never `main`. This plan's branch: `headless-cycle2-hardening` (already created; the Cycle 2 spec commit is on it).
- Version: **patch** bump `0.4.0 → 0.4.1`. `package.json` version MUST equal the newest `## [x.y.z]` heading in `CHANGELOG.md` (enforced by `tests/release-hygiene.spec.js`).
- No new runtime dependency (would need `approved-deps.json`).
- Tests are **static imports only** — never `await import('../x.ts?query')` (breaks CI's Node 24 + Playwright). Vary inputs via extracted pure functions; reset module state via exported setters in `beforeEach`.
- Run tests with `PORT=3100`: `PORT=3100 npx playwright test`. A production build must exist first: `npm run build`. Never run two `next dev` on the repo.
- Lint once at the end; present findings, don't auto-loop.

---

### Task 1: Typed auth error (`WpAuthError` + `errorFromResponse`)

**Files:**
- Modify: `lib/wp-client.ts` (add `WpAuthError`, `errorFromResponse`; change `login()`)
- Test: `tests/wp-client-errors.spec.ts` (create)

**Interfaces:**
- Consumes: existing `login`, `setAccessToken` from `lib/wp-client.ts`.
- Produces:
  - `class WpAuthError extends Error { code: string; status: number; retryAfter?: number }`
  - `errorFromResponse(res: Response): Promise<WpAuthError>`
  - `login()` now throws `WpAuthError` instead of the raw parsed JSON body.
  - (PR B consumes all three.)

- [ ] **Step 1: Write the failing test**

Create `tests/wp-client-errors.spec.ts`:

```ts
import { test, expect } from '@playwright/test';
import { login, errorFromResponse, WpAuthError, setAccessToken } from '../lib/wp-client';

// Static imports (CI-safe). wp-client holds the access token in module state.
const realFetch = globalThis.fetch;
test.afterEach(() => { globalThis.fetch = realFetch; });
test.beforeEach(() => { setAccessToken(null); });

test('errorFromResponse maps the WP_Error envelope into a typed error', async () => {
  const res = { status: 429, json: async () => ({
    code: 'blueworx_rate_limited', message: 'Too many attempts.',
    data: { status: 429, retry_after: 480 },
  }) };
  const err = await errorFromResponse(res as unknown as Response);
  expect(err).toBeInstanceOf(WpAuthError);
  expect(err.code).toBe('blueworx_rate_limited');
  expect(err.status).toBe(429);
  expect(err.retryAfter).toBe(480);
});

test('errorFromResponse falls back to a generic error for a non-JSON body', async () => {
  const res = { status: 500, json: async () => { throw new Error('not json'); } };
  const err = await errorFromResponse(res as unknown as Response);
  expect(err.code).toBe('blueworx_request_failed');
  expect(err.status).toBe(500);
});

test('login throws a WpAuthError (not the raw body) on a non-2xx response', async () => {
  globalThis.fetch = (async () => ({
    ok: false, status: 401,
    json: async () => ({ code: 'blueworx_invalid_login', message: 'Invalid username/email or password.', data: { status: 401 } }),
  })) as unknown as typeof fetch;
  await expect(login('jane@example.com', 'wrong')).rejects.toBeInstanceOf(WpAuthError);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `PORT=3100 npx playwright test tests/wp-client-errors.spec.ts`
Expected: FAIL — `errorFromResponse`/`WpAuthError` are not exported.

- [ ] **Step 3: Add `WpAuthError` + `errorFromResponse` to `lib/wp-client.ts`**

Insert after the imports (top of file, below `import { config } …`):

```ts
export class WpAuthError extends Error {
  code: string;
  status: number;
  retryAfter?: number;
  constructor(code: string, message: string, status: number, retryAfter?: number) {
    super(message);
    this.name = "WpAuthError";
    this.code = code;
    this.status = status;
    this.retryAfter = retryAfter;
  }
}

/**
 * Parse the plugin's WP_Error envelope from a non-2xx response into a typed
 * error. Falls back to a generic code/message when the body isn't the expected
 * shape (HTML error pages, empty bodies, network layers).
 */
export async function errorFromResponse(res: Response): Promise<WpAuthError> {
  let code = "blueworx_request_failed";
  let message = "Something went wrong. Please try again.";
  let retryAfter: number | undefined;
  try {
    const body = (await res.json()) as {
      code?: unknown; message?: unknown; data?: { retry_after?: unknown };
    };
    if (typeof body.code === "string") code = body.code;
    if (typeof body.message === "string") message = body.message;
    if (typeof body.data?.retry_after === "number") retryAfter = body.data.retry_after;
  } catch {
    // non-JSON body — keep the generic defaults
  }
  return new WpAuthError(code, message, res.status, retryAfter);
}
```

- [ ] **Step 4: Change `login()` to throw the typed error**

In `lib/wp-client.ts`, replace the line in `login()`:

```ts
  if (!res.ok) throw await res.json();
```

with:

```ts
  if (!res.ok) throw await errorFromResponse(res);
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `PORT=3100 npx playwright test tests/wp-client-errors.spec.ts tests/wp-client.spec.ts`
Expected: PASS (new spec passes; existing `wp-client.spec.ts` still green — the success-path `login` test is unaffected).

- [ ] **Step 6: Commit**

```bash
git add lib/wp-client.ts tests/wp-client-errors.spec.ts
git commit -m "fix: wp-client login throws a typed WpAuthError, not the raw body

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: Graceful catch-all page (`decideOutcome` + try/catch)

**Files:**
- Create: `lib/api/resolve-page.ts`
- Modify: `app/[...slug]/page.tsx`
- Test: `tests/resolve-page.spec.ts` (create)

**Interfaces:**
- Consumes: `ResolveResult` from `lib/api/wp.ts`; `resolve`, `getByRestUrl` from `lib/api/wp.ts`.
- Produces:
  - `type PageOutcome = { kind: "notFound" } | { kind: "render"; restUrl: string }`
  - `decideOutcome(r: ResolveResult | null): PageOutcome`

- [ ] **Step 1: Write the failing test**

Create `tests/resolve-page.spec.ts`:

```ts
import { test, expect } from '@playwright/test';
import { decideOutcome } from '../lib/api/resolve-page';

test('null (fetch failed) → notFound', () => {
  expect(decideOutcome(null)).toEqual({ kind: 'notFound' });
});

test('a 404 resolve result → notFound', () => {
  expect(decideOutcome({ type: '404', id: 0, slug: '', rest_url: '', template: '404' }))
    .toEqual({ kind: 'notFound' });
});

test('a resolvable page with an empty rest_url → notFound', () => {
  expect(decideOutcome({ type: 'page', id: 12, slug: 'about', rest_url: '', template: 'single' }))
    .toEqual({ kind: 'notFound' });
});

test('a resolvable page → render with its rest_url', () => {
  expect(decideOutcome({ type: 'page', id: 12, slug: 'about', rest_url: 'https://cms/wp-json/wp/v2/pages/12', template: 'single' }))
    .toEqual({ kind: 'render', restUrl: 'https://cms/wp-json/wp/v2/pages/12' });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `PORT=3100 npx playwright test tests/resolve-page.spec.ts`
Expected: FAIL — `lib/api/resolve-page.ts` does not exist.

- [ ] **Step 3: Create the pure decision module**

Create `lib/api/resolve-page.ts`:

```ts
// Pure decision for the catch-all WordPress page: given a /resolve result (or
// null when the fetch failed), decide whether to render a wp/v2 body or 404.
// Extracted so it is unit-testable without a running server; the component
// wraps resolve()/getByRestUrl() in try/catch and defers the decision here.
import type { ResolveResult } from "@/lib/api/wp";

export type PageOutcome =
  | { kind: "notFound" }
  | { kind: "render"; restUrl: string };

export function decideOutcome(r: ResolveResult | null): PageOutcome {
  if (!r || r.type === "404" || !r.rest_url) return { kind: "notFound" };
  return { kind: "render", restUrl: r.rest_url };
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `PORT=3100 npx playwright test tests/resolve-page.spec.ts`
Expected: PASS.

- [ ] **Step 5: Wire the component with try/catch → `notFound()`**

Replace the body of `app/[...slug]/page.tsx` with:

```tsx
import { notFound } from "next/navigation";
import { useMockData } from "@/lib/config";
import { resolve, getByRestUrl } from "@/lib/api/wp";
import { decideOutcome } from "@/lib/api/resolve-page";

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

  let resolved: Awaited<ReturnType<typeof resolve>> | null = null;
  try {
    resolved = await resolve(uri);
  } catch {
    notFound(); // live CMS outage → 404, not a 500
  }

  const outcome = decideOutcome(resolved);
  if (outcome.kind === "notFound") notFound();

  let page: Awaited<ReturnType<typeof getByRestUrl>>;
  try {
    page = await getByRestUrl(outcome.restUrl);
  } catch {
    notFound();
  }

  return (
    <main className="wp-page">
      <h1 dangerouslySetInnerHTML={{ __html: page.title.rendered }} />
      <div dangerouslySetInnerHTML={{ __html: page.content.rendered }} />
    </main>
  );
}
```

(`notFound()` is typed `(): never`, so TypeScript narrows `resolved`/`page` as assigned after each guard.)

- [ ] **Step 6: Rebuild and confirm the existing 404 behaviour still holds**

Run: `npm run build && PORT=3100 npx playwright test tests/wp-page-404.spec.js tests/resolve-page.spec.ts`
Expected: PASS (mock-mode 404 behaviour unchanged; new unit tests pass).

- [ ] **Step 7: Commit**

```bash
git add lib/api/resolve-page.ts app/[...slug]/page.tsx tests/resolve-page.spec.ts
git commit -m "fix: catch-all page degrades to 404 on CMS outage instead of 500

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: Validate revalidate `paths[]` (`filterPaths`)

**Files:**
- Create: `lib/revalidate.ts`
- Modify: `app/api/revalidate/route.ts`
- Test: `tests/revalidate-paths.spec.ts` (create)

**Interfaces:**
- Produces: `filterPaths(input: unknown): string[]` — keeps only string entries; non-array → `[]`.
- The route imports and uses `filterPaths` before calling `revalidatePath`.

> Why a pure helper and not a route test: the Playwright test server sets **no** `REVALIDATE_SECRET`, so the route always returns `401` (fail-closed) and the happy path can't be exercised through the running server. The string-filtering logic is unit-tested directly.

- [ ] **Step 1: Write the failing test**

Create `tests/revalidate-paths.spec.ts`:

```ts
import { test, expect } from '@playwright/test';
import { filterPaths } from '../lib/revalidate';

test('keeps only string entries', () => {
  expect(filterPaths(['/a', 1, null, '/b', { p: '/c' }, true])).toEqual(['/a', '/b']);
});

test('non-array input → empty array', () => {
  expect(filterPaths(undefined)).toEqual([]);
  expect(filterPaths('/a')).toEqual([]);
  expect(filterPaths(null)).toEqual([]);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `PORT=3100 npx playwright test tests/revalidate-paths.spec.ts`
Expected: FAIL — `lib/revalidate.ts` does not exist.

- [ ] **Step 3: Create the helper**

Create `lib/revalidate.ts`:

```ts
/** Keep only string entries from an untrusted revalidation paths payload. */
export function filterPaths(input: unknown): string[] {
  return Array.isArray(input)
    ? input.filter((p): p is string => typeof p === "string")
    : [];
}
```

- [ ] **Step 4: Use it in the route**

In `app/api/revalidate/route.ts`, add the import at the top:

```ts
import { filterPaths } from "@/lib/config" // WRONG — see next line
```

Actually add:

```ts
import { filterPaths } from "@/lib/revalidate";
```

Then replace these two lines:

```ts
  const body = (await req.json().catch(() => ({}))) as { paths?: string[] };
  const paths = Array.isArray(body.paths) ? body.paths : [];
```

with:

```ts
  const body = (await req.json().catch(() => ({}))) as { paths?: unknown };
  const paths = filterPaths(body.paths);
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `PORT=3100 npx playwright test tests/revalidate-paths.spec.ts tests/revalidate.spec.js`
Expected: PASS (new unit test passes; existing fail-closed 401 tests unchanged).

- [ ] **Step 6: Commit**

```bash
git add lib/revalidate.ts app/api/revalidate/route.ts tests/revalidate-paths.spec.ts
git commit -m "fix: revalidate route validates paths[] entries are strings

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: Version bump + changelog

**Files:**
- Modify: `package.json` (version)
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Bump the version**

In `package.json`, change `"version": "0.4.0"` → `"version": "0.4.1"`.

- [ ] **Step 2: Add the changelog entry**

Insert directly under the intro paragraph in `CHANGELOG.md` (above `## [0.4.0]`):

```markdown
## [0.4.1] - 2026-07-14

### Fixed

- `lib/wp-client.ts` `login()` now throws a typed `WpAuthError` (carrying `code`, `status`, and `retryAfter`) parsed from the plugin's WP_Error envelope, instead of throwing the raw parsed JSON body. Adds `errorFromResponse()` for reuse by the Cycle 2 auth UI.
- `app/[...slug]/page.tsx` wraps `resolve()` and `getByRestUrl()` in try/catch so a live CMS outage degrades to `notFound()` (404) instead of a 500. The resolve→render decision is extracted to a pure, unit-tested `decideOutcome()` in `lib/api/resolve-page.ts`.
- `app/api/revalidate/route.ts` validates that `paths[]` entries are strings before `revalidatePath()`, via a pure `filterPaths()` helper (`lib/revalidate.ts`). Non-string entries are ignored; the route still returns `200`.
```

- [ ] **Step 3: Run the full suite**

Run: `npm run build && PORT=3100 npx playwright test`
Expected: PASS, including `tests/release-hygiene.spec.js` (version `0.4.1` matches the newest changelog heading).

- [ ] **Step 4: Lint once**

Run: `npm run lint`
Expected: clean. Present any findings to the user; do not auto-fix in a loop.

- [ ] **Step 5: Commit and open the PR**

```bash
git add package.json CHANGELOG.md
git commit -m "chore: release 0.4.1 — Cycle-1 hardening follow-ups

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
git push -u origin headless-cycle2-hardening
gh pr create --title "Cycle 2 PR A: Cycle-1 hardening follow-ups (v0.4.1)" --body "$(cat <<'EOF'
Closes the three dormant Cycle-1 live-path gaps.

- `wp-client` `login()` throws a typed `WpAuthError` (+ reusable `errorFromResponse`).
- `[...slug]` catch-all degrades to 404 on CMS outage (pure `decideOutcome`, try/catch).
- `/api/revalidate` validates `paths[]` are strings (pure `filterPaths`).

Spec: `docs/superpowers/specs/2026-07-14-headless-plugin-integration-cycle2-design.md` §3.1.
Tests: `wp-client-errors`, `resolve-page`, `revalidate-paths` (all CI-safe static imports).
Version bumped 0.4.0 → 0.4.1; changelog updated.

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

---

## Self-Review

- **Spec coverage (§3.1 a/b/c):** Task 1 = typed error (a); Task 2 = catch-all try/catch + `decideOutcome` (b); Task 3 = `paths[]` validation (c); Task 4 = version + changelog (§8/§9). All covered.
- **Placeholder scan:** none — every step has real code/commands. (Step-4 of Task 3 deliberately shows the WRONG import line then the correction, to prevent the common `@/lib/config` slip; the final import is `@/lib/revalidate`.)
- **Type consistency:** `WpAuthError`/`errorFromResponse` signatures match between Task 1 and their use in PR B; `PageOutcome`/`decideOutcome` and `filterPaths` names are used identically in tests and consumers.
