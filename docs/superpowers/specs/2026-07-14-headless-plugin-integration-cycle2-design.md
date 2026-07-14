# Headless Plugin Integration — Cycle 2: Client Portal (Auth + SureCart Billing)

**Date:** 2026-07-14
**Status:** Design — approved for planning
**Repo:** `bluegroup_project_blueworx` (Next.js headless front-end)
**Consumes:** `blueworx_labs_wordpress` plugin v1.10.1 — authoritative contract is its `HEADLESS_INTEGRATION.md`
**Follows:** Cycle 1 (`docs/superpowers/specs/2026-07-13-headless-plugin-integration-cycle1-design.md`, merged PR #7, v0.4.0)

---

## 1. Background & problem

Cycle 1 re-based the front-end onto the plugin's generic headless API and shipped the public
marketing site plus dormant infrastructure: the two-base config, `lib/api/wp.ts` fetchers,
`/api/revalidate`, and `lib/wp-client.ts` (browser JWT auth client — **shipped but not yet
consumed**). Marketing content is intentionally **static** in `lib/data.ts`; there are no CPTs.

Cycle 2 builds the **authenticated client portal**. The plugin's auth model (guide §4, §11)
forces a specific shape:

- The **access token is browser-only** (in-memory JWT). The **refresh token** is an `HttpOnly`,
  `Secure`, `SameSite=None` cookie **path-scoped to `/wp-json/blueworx/v1/auth/`** — JavaScript
  never sees it, and it is only sent to `/auth/*` routes.
- Therefore **per-user data cannot be read in a Server Component** (guide §11). The current portal
  (`app/portal/page.tsx` → `getSession()` → `getPortalData()`, server-rendered) is the wrong model.

The portal must become a **client component tree** with an auth provider that restores the session
on mount. This is the deliberate, chosen architecture ("Option 1 — fully headless portal"), not a
fallback: one brand, one origin, one design system, with the auth ceremony owned by the front-end.

### What the plugin actually provides (verified against `HEADLESS_INTEGRATION.md`)

- **Auth** (`blueworx/v1/auth/*`): `POST /auth/login` `{login, password}` → `{access_token,
  token_type, expires_in, user}` + refresh cookie · `POST /auth/refresh` (cookie) → new access +
  rotated cookie · `POST /auth/logout` · `POST /auth/logout-all` (access) · `GET /auth/me` (access)
  → user **+ `capabilities`**.
- **Accounts** (`blueworx/v1/account/*`): `register` (mode-gated, non-enumerating) · `verify`
  `{token}` · `resend-verification` `{email}` · `password/forgot` `{email}` · `password/reset`
  `{token, password}` · `password/change` (access) `{current_password, new_password}` · `PATCH
  /account` (access) · `DELETE /account` (access+reauth). **Password minimum 8 chars.**
- **SureCart proxy** (`blueworx/v1/surecart/*`, only when enabled + API key set): `GET
  /surecart/me/subscriptions` · `GET /surecart/me/invoices` · `GET /surecart/me/orders` · `POST
  /surecart/checkout` · `POST /surecart/me/subscriptions/{id}/cancel`, all `access`-scoped to the
  caller's mapped customer, **fail-closed**. **Responses are SureCart's own raw shapes, passed
  through — NOT normalized** to the front-end's `Subscription`/`Invoice` types. `/me/*` returns
  `{ data: [] }` when the user has no SureCart customer yet.
- **No `/contact` endpoint exists** in the plugin.
- **No `/portal/me`** — the bespoke project data (sites, hours, onboarding, tickets, team, partner,
  activity, time log) has no plugin endpoint and is not in SureCart.
- **Error envelope** (guide §7): WP_Error JSON `{ code, message, data: { status, retry_after? } }`.

---

## 2. Goals / non-goals

**Goals (Cycle 2):**

1. Harden the three dormant Cycle-1 follow-ups (small, independent — ships as PR A).
2. Convert the portal to a **client component tree** with an auth provider (`restoreSession()` on
   mount → hydrate via `GET /auth/me`). Enforce sign-in when `PORTAL_REQUIRE_AUTH`.
3. Build the auth UI against `blueworx/v1`: login, register, email verify, forgot/reset password,
   change password — honouring non-enumerating responses, the documented error codes, and the
   8-char password minimum.
4. Wire **real SureCart billing** (subscriptions, invoices) into the portal, fetched client-side
   via `lib/wp-client.ts`, mapped from SureCart's raw shapes on the front-end.
5. Keep the contact form working via `CONTACT_FORWARD_URL`; write up `POST /blueworx/v1/contact`
   as a **plugin-side deliverable** and point the env at it when it ships.
6. Preserve a working CMS-less preview: with no `NEXT_PUBLIC_WORDPRESS_URL` (or
   `PORTAL_REQUIRE_AUTH=false`), the portal renders the demo payload exactly as today.

**Non-goals (deferred):**

- A `/portal/me` endpoint and a WordPress data model for bespoke project data. The bespoke portal
  sections (sites, hours, onboarding, tickets, team, partner, activity, time log, stats, platform,
  learning) **stay on demo data this cycle**, clearly labelled as sample. They go live in a later
  cycle once a normalized endpoint exists.
- Plugin-side SureCart normalization (guide §9 / API_CONTRACT §10 default). Mapping lives on the
  front-end this cycle, structured so it can be retired if the plugin later normalizes.
- SureCart checkout / subscription-cancel actions, orders tab, account deletion UI (`DELETE
  /account`), `logout-all` UI. Read-only billing + the core auth flows only this cycle.
- Converting the bespoke marketing/portal design pages to CMS-authored content (out of scope, as
  in Cycle 1).

---

## 3. Architecture

The plugin's §11 rule is followed exactly: **marketing stays server-rendered; everything
authenticated is a client concern.** The access token never reaches a Server Component.

```
app/portal/page.tsx  (server shell)
  └─ fetches public `tools` (static, server-side) and renders:
       components/portal/PortalClient.tsx  ("use client")
         └─ <AuthProvider>          restoreSession() on mount → GET /auth/me
              ├─ status 'loading' → portal skeleton
              ├─ status 'anon'    → <SignInForm/> gate   (no portal data rendered → no leak)
              └─ status 'authed'  → <Portal/>  (existing presentational component, unchanged shape)
                                      ├─ identity            ← /auth/me user
                                      ├─ subscriptions/invoices ← SureCart (client fetch + map)
                                      └─ bespoke sections    ← demo data (labelled "sample")
```

### 3.1 PR A — Cycle-1 hardening (independent, ships first)

Three isolated fixes. All are dormant in mock mode today, so they change no current behaviour but
close real live-path gaps.

**(a) `lib/wp-client.ts` — typed error.** `login()` currently does `throw await res.json()` (a raw
object, not an `Error`). Extract:

```ts
export class WpAuthError extends Error {
  code: string;
  status: number;
  retryAfter?: number;
  constructor(code: string, message: string, status: number, retryAfter?: number) { … }
}
/** Parse the WP_Error envelope from a non-2xx response into a typed error. */
export async function errorFromResponse(res: Response): Promise<WpAuthError> { … }
```

`login()` throws `await errorFromResponse(res)`. Reused by the auth UI (§3.3). Falls back to a
generic code/message if the body isn't the expected envelope.

**(b) `app/[...slug]/page.tsx` — graceful CMS outage.** Today `resolve()`/`getByRestUrl()` are
awaited with no guard: a network error or upstream 5xx throws → the route 500s. Wrap them so a
failure degrades to `notFound()` (404), not a 500. To keep this **unit-testable under CI's static
imports**, extract the decision into a pure function that maps a resolve result (or thrown error)
to an outcome:

```ts
// lib/api/resolve-page.ts (new)
export type PageOutcome =
  | { kind: "notFound" }
  | { kind: "render"; restUrl: string };
export function decideOutcome(r: ResolveResult | null): PageOutcome { … } // null/404/empty → notFound
```

The component calls `resolve()` inside try/catch (catch → `notFound()`), then `decideOutcome(r)`,
then `getByRestUrl()` inside try/catch (catch → `notFound()`). `decideOutcome` is the tested unit.

**(c) `app/api/revalidate/route.ts` — validate `paths[]`.** Filter to strings before
`revalidatePath`: `const paths = Array.isArray(body.paths) ? body.paths.filter((p): p is string =>
typeof p === "string") : []`. Non-string entries are ignored; the route still returns `200 {
revalidated }` (only the valid paths). Secret check unchanged.

### 3.2 Auth token core — `lib/wp-client.ts` (extend, stay thin)

Keep `wp-client.ts` focused on the **token lifecycle** only: `login`, `logout`, `refresh`, `api`,
`restoreSession`, `setAccessToken`/`getAccessToken`, plus `WpAuthError`/`errorFromResponse`. No
account/business operations here. `api()` already does in-memory bearer + single-flight
refresh-once-on-401; unchanged except `login()` now throws typed. All calls keep
`credentials: 'include'` (required for the refresh cookie, guide §4 rule 2).

### 3.3 Account operations — `lib/api/account.ts` (new)

Thin wrappers over the plugin's `account/*` and `auth/*` endpoints. Public operations use a plain
`credentials:'include'` fetch; authed operations go through `api()` from `wp-client.ts`. Every
non-2xx is turned into a `WpAuthError` via `errorFromResponse`.

```ts
register(email, password, inviteToken?): Promise<RegisterResult>   // POST /account/register
verifyEmail(token): Promise<void>                                  // POST /account/verify
resendVerification(email): Promise<void>                           // POST /account/resend-verification
forgotPassword(email): Promise<void>                               // POST /account/password/forgot
resetPassword(token, password): Promise<void>                      // POST /account/password/reset
changePassword(current, next): Promise<void>                       // POST /account/password/change (access)
getMe(): Promise<Me>                                               // GET  /auth/me (access) → user + capabilities
```

`RegisterResult` distinguishes "verification required → generic success" from "verification off →
session returned" (guide §5.2). `Me` mirrors the `/auth/me` payload including `capabilities`.

### 3.4 Auth provider — `lib/auth/AuthProvider.tsx` (new, client)

React context that owns session state for the portal subtree.

```ts
type AuthStatus = "loading" | "authed" | "anon";
type AuthContext = {
  user: Me | null;
  status: AuthStatus;
  login(login: string, password: string): Promise<void>;   // wp-client.login → setUser → 'authed'
  logout(): Promise<void>;                                  // wp-client.logout → 'anon'
  refreshMe(): Promise<void>;                               // re-fetch /auth/me
};
```

On mount: `restoreSession()` (single-flight refresh). If it returns `true` → `getMe()` →
`status='authed'`; else `status='anon'`. Starts at `'loading'` so the gate shows a skeleton rather
than flashing the sign-in form. **Demo/preview mode:** when `useMockData` (no CMS) or
`!portalRequireAuth`, the provider short-circuits to a synthetic **demo authed session** (the
existing `Hannah Whitfield` identity) so the CMS-less portal preview keeps working unchanged.

### 3.5 Error mapping — `lib/auth/errors.ts` (new, pure)

A pure `errorMessage(code: string): string` map plus small helpers, unit-tested per code:

- `blueworx_invalid_login` → "Incorrect email or password."
- `blueworx_email_unverified` → verify-your-email message; the caller shows a **resend** action.
- `blueworx_registration_closed` → the register form disables submission and shows a closed notice.
- `blueworx_weak_password` → "Use at least 8 characters." (also enforced client-side pre-submit).
- `blueworx_invalid_token` → "That link has expired or already been used."
- `blueworx_refresh_reuse` → force full re-login.
- `blueworx_rate_limited` → back-off message; respect `data.retry_after`.
- `blueworx_auth_unconfigured` (503) → generic "sign-in is temporarily unavailable" (config error).
- Unknown → generic fallback.

**Non-enumeration (guide §5.2):** register, forgot-password, and resend-verification **always**
render the same generic "check your email" outcome regardless of the response — the UI never
branches on "email exists". This is a UI rule enforced in those three forms, not a code lookup.

### 3.6 Auth UI — components + routes

Shared presentational form primitives + a shared `<SignInForm/>`. Routes exist because the plugin's
verification / reset emails link to `/verify?token=` and `/reset-password?token=` (guide §2,
"Frontend URL").

| Route | Component | Endpoint | Notes |
|---|---|---|---|
| `app/login` | `SignInForm` | `POST /auth/login` | Also embedded inline by the portal gate (shared component). `?next=` supported. |
| `app/register` | `RegisterForm` | `POST /account/register` | Non-enumerating success; disables on `registration_closed`. 8-char min. |
| `app/verify` | `VerifyEmail` | `POST /account/verify` | Reads `?token=`; shows success / invalid-token. |
| `app/forgot-password` | `ForgotPasswordForm` | `POST /account/password/forgot` | Always generic "check your email". |
| `app/reset-password` | `ResetPasswordForm` | `POST /account/password/reset` | Reads `?token=`; 8-char min; on success → link to sign in. |
| (in portal) | `ChangePassword` | `POST /account/password/change` | Authed account section inside the portal. |

Accessibility (house rule): real `<label>`s, keyboard access, correct heading order, readable
contrast, meaningful error text.

**Gate behaviour change (explicitly approved):** today `PORTAL_REQUIRE_AUTH=true` → redirect to
`/`. New behaviour → render the **sign-in screen in place** (no redirect, still renders **no**
portal data → no other client's data leaks). The existing `tests/portal-auth.spec.js` is updated
to assert the sign-in screen renders and no `.pt-welcome`/portal shell data is present.

### 3.7 SureCart billing — `lib/api/surecart.ts` (new)

Client-side functions using `api()`, each through a **pure mapper** — the single place SureCart
shape knowledge lives:

```ts
getSubscriptions(): Promise<Subscription[]>   // GET /surecart/me/subscriptions → data.map(mapSubscription)
getInvoices(): Promise<Invoice[]>             // GET /surecart/me/invoices      → data.map(mapInvoice)
export function mapSubscription(raw: unknown): Subscription   // pure, fixture-tested
export function mapInvoice(raw: unknown): Invoice             // pure, fixture-tested
```

Target shapes are the existing `Subscription` / `Invoice` in `lib/api/portal.ts` (unchanged, so
`Portal.tsx` rendering is untouched). `{ data: [] }` (no customer yet) → `[]`.

**Known shape gaps (flagged, not hidden).** SureCart's raw objects do not carry `site` or `icon`
(Portal's `Subscription` wants them), and exact field names cannot be verified without the live
SureCart API. The mappers derive defensively and are the correction point during integration:

- `price` — formatted from the SureCart amount + currency (display string, e.g. `"$490"`).
- `cycle` — `"/mo"` / `"/yr"` from the price interval.
- `status` — mapped from SureCart status to the Portal's chip strings (`Active` / `Trial ends soon`
  / `Past due` / `Cancelled`).
- `renews` — formatted from `current_period_end` (display date, e.g. `"Apr 1, 2026"`).
- `site` — from subscription metadata if present, else `"All sites"`.
- `icon` — defaults to `plug` (a known `lib/icons.ts` key); refined by product mapping later.
- `Invoice.url` — the SureCart hosted invoice/PDF link if present (the Portal already links it).

A short `docs/plugin-endpoints-cycle2.md` note records that if these gaps prove load-bearing, the
clean fix is plugin-side normalization (guide §9 / API_CONTRACT §10) and the mappers retire.

### 3.8 Portal data flow — `components/portal/PortalClient.tsx` (new) + `lib/api/portal.ts` (adjust)

`app/portal/page.tsx` becomes a thin **server shell**: `const tools = await getTools()` (public,
static) then `return <PortalClient tools={tools} />`. `getSession()`/`getPortalData()`/the
server-side redirect are removed from the page (`lib/auth.ts`'s `getSession` is retired; the demo
identity moves into the provider's demo mode).

`PortalClient` ("use client") wraps `<AuthProvider>` and, when authed, **assembles** the
`PortalData` the presentational `<Portal/>` needs:

- `client` (identity) ← `/auth/me` (`display_name`→`name`/`first`, initials derived). `company` and
  `tier` are **not** in the WP user payload → fall back to placeholder/demo values (flagged; a
  later cycle sources them from SureCart customer / user meta).
- `subscriptions`, `invoices` ← SureCart (client fetch, §3.7) with **per-section loading + inline
  error** states (guide §7 / API_CONTRACT §7: never render another client's data; show "couldn't
  load your account" inline). Empty (no customer) → empty tables, not an error.
- All bespoke sections ← the existing `MOCK_PORTAL` demo data (imported), rendered with a
  **"sample data" label** so it's honest about what's live vs demo.

`Portal.tsx` stays presentational; it keeps receiving a fully-assembled `PortalData` prop. The only
change is that billing arrays may arrive after a loading state, handled by `PortalClient` (it can
render the portal with billing placeholders and swap in real data, or gate the two billing tabs on
their own loading flag — planning picks the least-intrusive wiring).

`lib/api/portal.ts`: `getPortalData()` (server) is retired; `MOCK_PORTAL` and the type exports stay
(demo data + the shared `PortalData`/sub-shapes the client assembles).

### 3.9 Contact — front-end unchanged, plugin deliverable written up

`app/api/contact/route.ts` keeps its current validate-then-forward behaviour (forwards to
`CONTACT_FORWARD_URL` server-side, keeping any key off the browser). No front-end change beyond
confirming the flow. `docs/plugin-endpoints-cycle2.md` specifies `POST /blueworx/v1/contact` for
the plugin repo (public, rate-limited, validates the `ContactSubmission` shape from API_CONTRACT
§4, returns `{ ok, id? }` / `400 { ok:false, errors }`). Once it ships, `CONTACT_FORWARD_URL` is
pointed at it — no further front-end work.

---

## 4. Data flow

```
Portal (client)
  └─ AuthProvider.mount → restoreSession() [POST /auth/refresh, cookie]
       ├─ ok  → getMe() [GET /auth/me, bearer] → status 'authed'
       │          └─ PortalClient assembles PortalData:
       │               ├─ identity      ← /auth/me
       │               ├─ subscriptions ← api('/surecart/me/subscriptions') → map
       │               ├─ invoices      ← api('/surecart/me/invoices')      → map
       │               └─ bespoke       ← MOCK_PORTAL (demo, labelled)
       └─ fail → status 'anon' → <SignInForm/>  (PORTAL_REQUIRE_AUTH) or demo session (else)

Auth actions (client): login / register / verify / forgot / reset / change
  → lib/api/account.ts + wp-client.login → WpAuthError on failure → errorMessage(code)

Demo/preview (no CMS or !PORTAL_REQUIRE_AUTH): AuthProvider short-circuits to the demo session;
  billing falls back to MOCK subscriptions/invoices. Site renders exactly as Cycle 1.
```

---

## 5. Error handling & fallback

- **Auth:** every failure surfaces as a `WpAuthError`; the UI renders `errorMessage(code)`.
  Non-enumerating flows always show generic "check your email". `rate_limited` respects
  `retry_after`. `refresh_reuse` / repeated 401 → force sign-out to `'anon'`.
- **Billing:** a failed `/surecart/me/*` call → inline "couldn't load your billing" in that tab
  only; the rest of the portal still renders. `{ data: [] }` → empty state, not an error. Never
  render another customer's data (endpoints already fail-closed server-side).
- **Session restore:** `restoreSession()` failure → `'anon'` (not an error screen).
- **CMS-less / preview:** provider demo mode → the portal renders demo data with no network calls.

---

## 6. Testing (Playwright + pure unit — CI-safe)

Honours the harness rules (project memory): **static imports only** (no dynamic `import('../x.ts?query')`),
extract pure functions to vary inputs, reset module state via exported setters in `beforeEach`,
run with **`PORT=3100`**, never two `next dev` on the repo.

**PR A:**

- `errorFromResponse` — WP_Error envelope → `WpAuthError` (code/status/retryAfter); malformed body
  → generic. Pure unit.
- `wp-client.login()` throws `WpAuthError` on non-2xx (mocked fetch, `setAccessToken(null)` reset).
- `decideOutcome` (resolve-page) — 404/empty/null → `notFound`; valid → `render` with `restUrl`.
  Pure unit.
- `revalidate` route — extend `tests/revalidate.spec.js`: body with mixed `paths` (strings +
  non-strings) → `200`, only strings revalidated; correct/absent secret unchanged.

**PR B:**

- `errorMessage(code)` — each documented code → its message + flags; unknown → fallback. Pure unit.
- `mapSubscription` / `mapInvoice` — representative SureCart fixtures → exact `Subscription` /
  `Invoice` shapes, including the derived `price`/`cycle`/`renews`/`site`/`icon` fallbacks and the
  `{ data: [] }` → `[]` case. Pure unit.
- `AuthProvider` bootstrap — mocked fetch: `restoreSession` ok → `getMe` → `'authed'`; refresh fail
  → `'anon'`. `setAccessToken` reset in `beforeEach`.
- **Playwright (primary new flow):** with `/auth/*` and `/surecart/me/*` route-intercepted, a
  sign-in on `/login` (or the gate) → portal renders with **mapped** subscriptions/invoices; a
  failed `/surecart/me/*` → inline billing error while the rest of the portal renders.
- **Playwright (gate):** `PORTAL_REQUIRE_AUTH=true`, unauthenticated → the **sign-in screen**
  renders and **no** portal data (`.pt-welcome`, tables) is present. Updates the existing
  `tests/portal-auth.spec.js`.

At least one Playwright test covers the primary new functionality (login → portal), per CI.

---

## 7. Config / environment

No new env vars. Existing `NEXT_PUBLIC_WORDPRESS_URL`, `REVALIDATE_SECRET`, `CONTACT_FORWARD_URL`,
`PORTAL_REQUIRE_AUTH` cover Cycle 2. `.env.example` comments updated: `PORTAL_REQUIRE_AUTH` now
gates a real sign-in screen (not a redirect); note that SureCart billing requires the CMS's
**SureCart proxy enabled + `BLUEWORX_LABS_SURECART_API_KEY`** set.

**Prerequisites to verify before the live paths work** (CMS-side, per guide §2/§13 — the user is
configuring these):

- `BLUEWORX_LABS_JWT_SECRET` defined (else all `/auth/*` return `503 blueworx_auth_unconfigured`).
- **Allowed origins** contains the exact frontend origin (scheme + host, no trailing slash).
- **Frontend URL** set (verification / reset emails link to `/verify` and `/reset-password`).
- Registration mode + email-verification policy set as intended.
- CMS on a **subdomain of the frontend's registrable domain** (else the `SameSite=None` refresh
  cookie is dropped by Safari/Chrome and sessions won't persist — guide §4 gotcha).
- SureCart proxy enabled + API key set (for billing).

---

## 8. House conventions / CI

Next.js App Router + TS · Radix Themes · lucide-react · Tailwind · Netlify. Branch off `main` → PR
→ checks. Guardrails per PR: lint · build · **version bump** · **changelog** · no new dependency
without `approved-deps.json` · Playwright test for new functionality. **No new runtime dependency
is expected** (built-in `fetch`, React context, `next/navigation`). Each change starts from an
approved GitHub Issue; never commit to `main`.

---

## 9. PR plan

- **PR A — hardening (small, independent):** `WpAuthError`/`errorFromResponse` + `login()` typed
  throw; `[...slug]` try/catch → `notFound()` via extracted `decideOutcome`; revalidate `paths[]`
  string validation. Tests, version **patch** bump + changelog. Mergeable immediately.
- **PR B — portal auth + SureCart billing + contact:** `lib/api/account.ts`, `lib/auth/`
  (`AuthProvider`, `errors.ts`), auth routes + components, `lib/api/surecart.ts` (+ mappers),
  `components/portal/PortalClient.tsx`, `app/portal/page.tsx` server shell, `lib/api/portal.ts`
  adjustment, `lib/auth.ts` retirement, `portal-auth.spec.js` update, `docs/plugin-endpoints-cycle2.md`,
  `.env.example` + `docs/API_CONTRACT.md` doc updates. Tests per §6, version **minor** bump +
  changelog.

## 10. Deliverables

1. **PR A:** the three hardening fixes + tests, version bump + changelog.
2. **PR B:** the full client portal (auth + real SureCart billing) behind a configured origin, the
   contact flow confirmed, tests, version bump + changelog.
3. `docs/plugin-endpoints-cycle2.md` — plugin-side deliverables written up for the plugin repo:
   `POST /blueworx/v1/contact`, and a note that SureCart normalization is deferred (front-end maps
   this cycle).
4. Updated `docs/API_CONTRACT.md` §5.1/§5.2/§5.3 reconciled to the client-side auth + front-end
   SureCart mapping; `.env.example` comments updated.

## 11. Risks & open items

- **No live WordPress/SureCart here.** All live-path tests are mock/route-intercept. The SureCart
  mapper field names (§3.7) are the highest-risk unknown and must be verified against the live
  SureCart proxy during integration; the mapper is the single correction point.
- **Cross-site refresh cookie** (guide §4): if the CMS is not on a subdomain of the frontend's
  registrable domain, sessions silently expire at the access-token TTL. Infra requirement, flagged.
- **Identity gaps:** `company` / `tier` are not in the WP user payload; demo/placeholder values are
  used until a later cycle sources them.
- **Bespoke portal sections remain demo** this cycle — labelled sample so it's not mistaken for
  live account data.
