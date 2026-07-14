# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.4.1] - 2026-07-14

### Fixed

- `lib/wp-client.ts` `login()` now throws a typed `WpAuthError` (carrying `code`, `status`, and `retryAfter`) parsed from the plugin's WP_Error envelope, instead of throwing the raw parsed JSON body. Adds `errorFromResponse()` for reuse by the Cycle 2 auth UI.
- `app/[...slug]/page.tsx` wraps `resolve()` and `getByRestUrl()` in try/catch so a live CMS outage degrades to `notFound()` (404) instead of a 500. The resolve→render decision is extracted to a pure, unit-tested `decideOutcome()` in `lib/api/resolve-page.ts`.
- `app/api/revalidate/route.ts` validates that `paths[]` entries are strings before `revalidatePath()`, via a pure `filterPaths()` helper (`lib/revalidate.ts`). Non-string entries are ignored; the route still returns `200`.

## [0.4.0] - 2026-07-13

### Changed

- **Config layer re-based on the live `blueworx_labs_wordpress` plugin (v1.10.1).** `lib/config.ts` now derives two REST bases (`blueworx/v1` + `wp/v2`) from a single `NEXT_PUBLIC_WORDPRESS_URL` origin, replacing the invented `NEXT_PUBLIC_WP_API_URL`/`WP_API_TOKEN`.
- **Marketing content stays static.** Tools, plans, FAQs, and testimonials remain in `lib/data.ts`, edited via a code change/PR — there are no custom post types behind them. `lib/api/content.ts` keeps its exact async signatures so no page/component changed. `docs/API_CONTRACT.md` reconciled to the real plugin.

### Added

- `lib/api/wp.ts` — low-level fetchers for the plugin's generic endpoints (site config, nav menus, `/resolve`, `wp/v2` page bodies, ACF options) + `rewriteMenuUrl`.
- `app/api/revalidate/route.ts` — secret-verified on-demand ISR receiver (constant-time compare, fails closed without a configured secret).
- `lib/wp-client.ts` — browser auth client (in-memory JWT access token + single-flight refresh on 401). Infrastructure for the Cycle 2 portal; not yet consumed by any page.
- `app/[...slug]/page.tsx` — catch-all that resolves a path via `/resolve` and renders the `wp/v2` body; unmatched paths (and all of mock mode) still 404.
- Tests: config derivation, wp fetchers, revalidate auth, auth-client refresh, and catch-all 404 behaviour.

### Notes

- Portal (auth-gated §5 data), auth, SureCart, and the contact-form backend are deferred to Cycle 2; the portal continues to render demo data.

## [0.3.2] - 2026-07-12

### Changed

- `Testimonials.tsx` now renders from the content data layer (`getTestimonials()`) instead of a hardcoded inline list, closing the follow-up noted in 0.3.0. The homepage/services/contact testimonials are now the `HOME_REVIEWS` set — consistent with the rest of the app's cast — and will switch to the plugin's `/testimonials` endpoint with no component change once it goes live. Covered by a new test in `tests/site.spec.js` that asserts the rendered cards match `HOME_REVIEWS` field-for-field.

## [0.3.1] - 2026-07-12

### Fixed

- Contact form now sends the selected `countryCode` with the submission. The country `<select>` was uncontrolled and its value was dropped before the POST, even though the API contract (§4) and `/api/contact` already expect the field. Covered by a new test that intercepts the request and asserts the chosen code is included.

### Added

- `tests/portal-auth.spec.js` + a dedicated `portal-auth` Playwright project — starts a second server with `PORTAL_REQUIRE_AUTH=true` and asserts `/portal` redirects unauthenticated visitors to home without leaking the demo client's data. Closes the auth-redirect test gap noted in 0.3.0 (the harness now runs two servers, so the env can differ per project).
- `tests/fixtures-parity.spec.ts` — pins the content data layer's golden payloads (tool/plan/FAQ/testimonial shapes) against the API contract, and guards that the tools list and `SOLO_PRICES` stay in lockstep, so any future front-end/plugin drift fails loudly.
- `docs/API_CONTRACT.md` §10 — the front-end team's recommended defaults answering the open questions in §9 (normalise SureCart rather than proxy, session-cookie portal auth, SureCart as the pricing source of truth, `soloPrice` on each tool).

## [0.3.0] - 2026-07-11

### Added

- Headless integration layer so the site is ready to consume the in-progress BlueWorx WordPress plugin (content + SureCart subscriptions) with no UI changes when it ships:
  - `docs/API_CONTRACT.md` — the endpoint and JSON-shape spec the plugin builds against, derived from the current mock data and categorised by source (WordPress content vs SureCart vs the custom plugin).
  - `lib/config.ts` + `.env.example` — one place for every integration env var, with a `useMockData` kill-switch that flips the whole site from mock to live once `NEXT_PUBLIC_WP_API_URL` is set.
  - `lib/api/content.ts` — async content data layer (tools, plans, FAQs, testimonials, solo prices) wrapping the mock data; swaps to real fetches with no page/component changes.
  - `lib/api/portal.ts` — authenticated portal data layer (`getPortalData()`), with subscriptions/invoices earmarked for SureCart and the rest for the plugin's account/project endpoints.
  - `lib/auth.ts` — `getSession()` auth seam for the portal, returning a demo session until `PORTAL_REQUIRE_AUTH=true` wires real SureCart/WordPress auth.
- `app/api/contact/route.ts` — real server-side contact endpoint that validates submissions and forwards to `CONTACT_FORWARD_URL` (plugin/SureForms) when set. Covered by `tests/contact-api.spec.js` (400 invalid / 200 valid).

### Changed

- Pages now read content through the data layer and pass it into client components as props (`Nav`, `FaqList`, `SavingsCalc`); `ToolboxGrid` self-fetches its tool list.
- The contact form POSTs to `/api/contact`, surfaces server-side field errors, and shows a submitting state instead of faking success locally.
- The client portal fetches its data server-side (`/portal` is now dynamic) and renders from props; presentation-derived fields (status chips, hours text, percentages) are computed in the component from raw API values.

### Notes

- Testimonials still render inline copy in `Testimonials.tsx`; wiring them to the data layer is a follow-up pending the "real" testimonial content (the inline copy differs from `HOME_REVIEWS`).
- The portal's auth-enforced redirect path (when `PORTAL_REQUIRE_AUTH=true`) needs an integration test once the plugin's auth backend exists — the current single-server test harness can't toggle the env per test.

## [0.2.1] - 2026-07-07

### Fixed

- Mobile design audit cleanup: removed the horizontal page scroll at phone widths caused by the logos-band tagline (`white-space: nowrap`), and made the home feature tab bar wrap so every tab stays reachable on small screens.
- Replaced inline `gridTemplateColumns` styles (which overrode the responsive CSS) with stylesheet classes so grids collapse properly on mobile: About "Why BlueWorx" split and cards, home collaboration checklist, toolbox savings-calculator tool list, and the portal's toolbox/learning grids, subscription stats, tier picker, and partner calculator columns.
- Added a site favicon (`app/icon.svg`) — every page previously 404'd on `/favicon.ico`.
- Toolbox mega menu items no longer spill outside the dropdown panel: the items inherited `white-space: nowrap` from the top-level `.nav-links a` rule, which stopped descriptions wrapping and forced the grid wider than its fixed-width container.

### Changed

- Redesigned the nav "New" and mega-menu "Popular" tags to match the section-eyebrow language (mono uppercase, bordered pill, glowing dot), keeping them inline beside their menu-item labels.

### Added

- Playwright mobile regression tests: no horizontal overflow on any page at 375px, and all home feature tabs remain reachable.

## [0.2.0] - 2026-07-07

### Added

- Implemented the full BlueWorx site from the Claude Design handoff (`BlueWorx Site v4.dc.html`) as a Next.js App Router + TypeScript app: home, services, about, work, contact, toolbox (plans, comparison table, savings calculator), tool detail pages for all 12 toolbox tools, pricing (retainers + pricing calculator), AI Powered page (animated prompt→code→site demo and pipeline), and the client portal mock (overview, onboarding, websites, toolbox, learning center, subscriptions, hours, invoices, support, partner portal).
- Shared chrome and components: sticky hide-on-scroll nav with toolbox mega menu and mobile menu, footer, CTA band, logos marquee, FAQ accordion, testimonials, and a ported design-token stylesheet in `app/globals.css` (Sora via `next/font`).
- Playwright browser tests covering navigation, feature tabs, billing toggles, the pricing calculator, contact-form validation, and portal tab/site switching, with a `webServer` config so `npx playwright test` runs against the production build.

### Changed

- Scaffolded the approved headless stack (`next`, `react`, `react-dom`, TypeScript, ESLint with `eslint-config-next`) and recorded it in `approved-deps.json`; `npm run lint` and `npm run build` are now real commands.

## [0.1.1] - 2026-07-07

### Changed

- Synced `CLAUDE.md` with the updated foundation template — adds the standard headless framework line (Next.js App Router + TypeScript).

### Fixed

- Made the Playwright CI step runnable: installed `@playwright/test` (approved in `approved-deps.json`), added a `.gitignore`, and added a release-hygiene smoke test so `npx playwright test` has a real test to run.

## [0.1.0] - 2026-07-07

### Added

- Initial project scaffold: CI guardrail workflow (pointing at `bluegroup_core_foundation`), Claude Code settings, PR/issue templates, `approved-deps.json`, and a basic Playwright config.
