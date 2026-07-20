# Assessment: moving BlueWorx from headless Next.js to a WordPress plugin

**Date:** 2026-07-20
**Repos in scope:** `bluegroup_project_blueworx` (headless front-end), `blueworx_labs_wordpress` (plugin), `bluegroup_core_foundation` (CI/governance)
**Status:** Report for decision — no code changed.

---

## 1. Verdict

**The shortcode problem is real, but it does not on its own justify the migration — and the migration is much cheaper than it looks, for a reason nobody has written down yet.**

Two findings drive everything below:

1. **This is barely a headless site.** WordPress supplies content on exactly **one** route — the catch-all `app/[...slug]/page.tsx`. Home, about, services, work, pricing, toolbox, AI, contact and the entire portal are hardcoded React reading from `lib/data.ts`. There is no block parser, no shortcode handling, no ACF consumption. WP content is injected as raw `dangerouslySetInnerHTML` into a bare `.wp-page` div.
2. **The plugin has no front-end surface whatsoever.** `blueworx_labs_wordpress` has never registered a template, block, or public-side asset. It is ~4,600 lines of admin re-skin plus ~2,000 lines of REST endpoints that exist solely to feed the Next.js app.

So the migration is not "port a headless site into a plugin". It is "delete a headless integration that is barely used, delete the JWT layer that only exists because of it, and build a public-rendering capability the plugin has never had". The cost sits almost entirely in **CSS and the portal**, not in WordPress integration.

**Recommendation: Option B (staged migration), starting with the shortcode fix as a standalone spike so you get the benefit before committing to the move.** See §6.

---

## 2. Why shortcodes are broken today

There is no shortcode bug to fix in code — there is a missing capability.

- The plugin registers **zero** shortcodes and has **no shortcode-render or proxy endpoint** (full-repo grep: no `add_shortcode`, no `do_shortcode`).
- The front-end renders `content.rendered` from `wp/v2`. That *does* run shortcodes server-side, so simple output-only shortcodes appear as HTML — but their **CSS and JS never load**, because those are enqueued by the owning plugin on `wp_enqueue_scripts`, a hook that never fires in a headless request.
- Consequence: anything interactive — SureCart pricing tables, forms, galleries, sliders, booking widgets — arrives as inert markup or empty containers. Third-party plugins that render client-side output nothing at all.
- This is already an acknowledged gap. `CLAUDE.md:89` names *"WordPress shortcodes on a headless site"* as a Recipe Book topic — and the Recipe Book entry does not exist on disk (see §5.6).
- Secondary routing gap, same root cause: `/resolve` **does not handle archives or term pages — they return 404** (`HEADLESS_INTEGRATION.md` §6). Any archive route needs a plugin change today. In-plugin, WP's router handles it for free.

**In a plugin, this problem disappears entirely** — `the_content()` runs shortcodes and their enqueues in the normal WordPress lifecycle. No proxy, no asset scraping, no allowlist.

---

## 3. What migration would require

### 3.1 Deleted outright — the genuine win

| Side | Removed | Approx. |
|---|---|---|
| Plugin | `jwt.php`, `tokens.php`, `cors.php`, `revalidate.php`, `rate-limit.php`, `auth.php`, most of `account.php`, `/resolve`, `/menus`, `/site`, `/acf-options` | **~1,800 of ~2,000 REST lines** |
| Plugin | Bundled `firebase/php-jwt`, the Composer runtime dependency, both custom DB tables (`blueworx_refresh_tokens`, `blueworx_invites`), the Headless settings page, the daily GC cron | — |
| Front-end | `lib/wp-client.ts`, `lib/auth/*`, all 7 `components/auth/*`, 5 auth routes, `app/api/revalidate/route.ts`, `lib/config.ts` base derivation | **~600+ lines** |
| Both | The entire cross-site-cookie problem class (`SameSite=None`, exact-origin CORS echo, refresh-token rotation/reuse detection, the subdomain requirement documented at length in `HEADLESS_INTEGRATION.md` §4) | — |

Also deletable regardless of decision: `getSite`, `getMenu`, `getAcfOptions`, `rewriteMenuUrl` in `lib/api/wp.ts` are **written, unit-tested, and never called**.

WordPress's own cookie auth, nonces, `wp_nav_menu()` and router replace all of the above at zero cost. Native auth also fixes the current structural limitation that **per-user data cannot be read in a Server Component** — the constraint that forced the whole portal to be a client tree.

### 3.2 The actual cost — three items, in order

**1. CSS and layout — the largest single line item.**
`app/globals.css` is 962 lines of hand-written CSS, plus pervasive inline `style={{}}` objects across ~2,260 component lines. There is **no Tailwind and no design-token system in this project** despite the house standard. None of it carries across to PHP templates automatically; it must be re-homed into plugin-enqueued stylesheets and template markup. The plugin has no precedent for this — it has never enqueued a public asset.

**2. The portal — `components/Portal.tsx`, 736 lines, 10 tabs.**
Only **Subscriptions and Invoices are real** (SureCart). Sites, hours packages, onboarding, activity, time log, tickets, team and the whole partner/commission portal are the hardcoded `DEMO_PORTAL` fixture with no backend — confirmed by `docs/plugin-endpoints-cycle2.md` §3, where `/portal/me` was deferred twice. Migrating the portal means **finally building that data model** (user-meta/options, explicitly *not* CPTs). This work is identical either way; it is not a migration cost so much as an outstanding one. Treat it as a separate project.

**3. Interactive components — ~1,600 client-side lines.**
`Nav` (221 lines, hover-timer mega-menu), `FeatureTabs`, `Plans` + billing context, `PricingCalc`, `SavingsCalc`, `FaqList`, `AiDemo`, `AiPipeline`, `ContactForm`. In-plugin these become vanilla JS or small islands. The plugin already demonstrates the no-JS-where-possible instinct (the admin top bar uses native `<details>`), so this is consistent with house style — but it is a rewrite, not a port.

### 3.3 Cheap or free

- WP content rendering: one catch-all doing `dangerouslySetInnerHTML` → `the_content()`. Trivially thin.
- `/api/revalidate` and the outbound revalidation hook: simply delete.
- `/api/contact`: becomes a plugin REST route that was **already specced** in `docs/plugin-endpoints-cycle2.md` §1.
- Static content in `lib/data.ts` (tools, plans, FAQs, reviews, prices): becomes PHP arrays or options. Mechanical.

### 3.4 What you lose

Be explicit about this, because it is the real argument against:

- **Netlify's CDN, preview deploys, and ISR.** You move to WordPress hosting performance and whatever page caching the host provides. The plugin has cache modules but that is not the same thing.
- **Preview-per-PR.** Headless CI has a Netlify preview (still a documented placeholder in the foundation, but intended). WordPress CI points at a single staging URL.
- **React/TypeScript type safety** across ~20 portal types in `lib/api/portal.ts`, and the Playwright unit-style suite that tests them.
- **The stated house stack.** `CLAUDE.md` mandates Next.js App Router + Radix Themes + Tailwind for headless projects. A plugin build is outside that lane — though note this project already violates it (no Tailwind, no Radix).

---

## 4. The cheaper alternative — solve shortcodes without migrating

If shortcodes are the *only* driver, a shortcode-render endpoint is roughly **one to two days**, versus weeks for a migration:

`POST blueworx/v1/render` → run `do_shortcode()` inside an output buffer, capture the enqueued styles/scripts registered during that render (`wp_styles()`/`wp_scripts()` diff), and return `{ html, styles: [], scripts: [] }`. Front-end injects the assets alongside the markup.

**Honest caveats:** it is a well-known pattern but a leaky one. Shortcodes that depend on `wp_head`, localized script data, inline `wp_add_inline_script`, or the loop context will still misbehave, and every third-party plugin is a fresh compatibility test. It buys time; it does not make the class of problem go away. **The plugin route makes it go away permanently.**

This should also be proposed as the missing Recipe Book entry either way (§5.6).

---

## 5. Foundation alignment (`bluegroup_core_foundation`)

Everything here is verified against the foundation repo at `main` (`ecc6e14`).

### 5.1 CI changes if you switch project type

The project would move from `ci-headless.yml` to `ci-wordpress.yml`. Both define a single job named `guardrails`. The differences:

| Check | Headless (today) | WordPress plugin (after) |
|---|---|---|
| Lint / Build | Unconditional | **Only if `package.json` exists** |
| Version source | `package.json` `version` | **Plugin PHP header `Version:`** (`VERSION_SOURCE: plugin-header`) |
| Header ↔ `package.json` sync | n/a | **New required check** |
| PHP syntax lint | No | **Yes, always** (`php -l` across the tree) |
| PHPCS | No | Conditional — runs only if `phpcs.xml*` present |
| Single-zip check | No | **Yes** — at most one `<slug>*.zip` at depth ≤ 4 |
| Playwright target | Local build via `webServer` | **`preview_url` (required input)** — a staging URL |
| Netlify preview | Intended required check (still a code comment) | n/a |
| Changelog + approved-deps | Yes | Yes (deps skipped if no `package.json`) |

**`preview_url` is a required input.** You need a real staging WordPress before CI is meaningful.

### 5.2 ⚠️ The WordPress test gate is currently non-functional

This is the most important foundation finding and it affects the decision.

> *"Every Playwright test in `blueworx_labs_wordpress` has been silently skipping — in CI and locally — since the suite was written. CI reports green while running zero tests."*
> — `blueworx_labs_wordpress/docs/superpowers/specs/2026-07-15-ci-runs-no-tests-design.md` (Status: **Proposed — needs Luke's decision on Option A vs B**)

Two stacked causes:
1. `ci-wordpress.yml` has **no `secrets:` block on `workflow_call`** and never passes `WP_ADMIN_USER`/`WP_ADMIN_PASS`, so every admin test's `test.skip()` guard fires. **This affects every project consuming the workflow.**
2. Projects pass placeholder preview URLs (`blueworx_labs_wordpress/.github/workflows/ci.yml` still uses `https://staging.placeholder.blueworx.io`), and specs skip on `/placeholder/i`.

`npx playwright test` exits 0 when everything skips, and **no workflow has a "fail on zero tests" gate**.

**Implication: migrating today would move this project from a working test gate to a broken one.** The hard guardrail *"New functionality or a real bug fix has a Playwright test"* would be unenforced. This is a blocker to fix in the foundation first, not a detail to discover later.

The interim pattern that works is per-project, not a foundation feature: `bluegroup_project_afristream/playwright.config.js` defines a `STAGING_PLACEHOLDER` and falls back to a local hermetic `webServer` (`scripts/preview-server.mjs`, port 4180) when the incoming base URL matches it.

### 5.3 Versioning, changelog, deps

- Version must be **strictly greater** than base (`compareSemver(current, base) === 1`), read from the plugin header. Patch for fixes, minor for features.
- `CHANGELOG.md` must appear in the PR diff. Keep a Changelog format.
- `approved-deps.json` is **by name only**, `dependencies` + `devDependencies` only. Missing file = hard fail. **PHP/Composer deps are not gated by anything** — so vendoring PHP libraries passes CI unchallenged. Worth self-policing.

### 5.4 Deployment artifact rules

These are strict and non-obvious:

- Zip built **one level up from the repo**, at `<plugin-parent-dir>/<plugin-slug>.zip` — never inside the working tree. Delete the older zip first. CI fails on more than one `<slug>*.zip`.
- **Forward slashes, nested one level** (`<slug>/<slug>.php`). Backslash entries mis-extract on Linux hosts and WordPress reports *"Plugin file does not exist."* on activate.
- **`Compress-Archive` is banned.** Use System32 bsdtar: `/c/Windows/System32/tar.exe -a -c -f ../<slug>.zip -C dist <slug>`.
- If the repo ships a build script, run it — never hand-build. `blueworx_labs_wordpress` already has `scripts/build-zip.mjs`; the allowlist reference implementation is `bluegroup_project_afristream/scripts/build-plugin.mjs`.
- Verify with `unzip -l` before handing off.

Net effect: **deployment goes from fully automatic (Netlify on merge) to a manual zip-and-upload step.** Real ongoing friction to weigh.

### 5.5 Project-type and structural requirements

A WordPress plugin project must have: `ci.yml` calling `ci-wordpress.yml@main` with `preview_url` + `plugin_slug`; a main `*.php` at depth ≤ 2 with `Plugin Name:` and `Version:` headers; `package.json` version identical to the header (if present); `CHANGELOG.md`; populated `approved-deps.json`; at most one slug zip; optional `composer.json` + `phpcs.xml.dist` (adding it opts into PHPCS); `playwright.config.*` reading `PLAYWRIGHT_BASE_URL`; copied-in `CLAUDE.md`, PR/issue templates, `.claude/settings.json`; branch protection on `main`.

Also binding: **"No page builders (Elementor etc.) — WordPress sites are built as a plugin, in code, never straight into WordPress core or a loose theme."** The migration target is therefore templates/blocks *inside the plugin*, not a theme.

### 5.6 Gaps in the foundation this exposes

1. **No WordPress plugin Starter Prompt exists in the repo** — only headless has one (`docs/starter-prompt-headless-framework.md`). The Starter Prompts live in the ClickUp Team Guidelines doc. There is nothing on disk to scaffold against.
2. **No Recipe Book file exists**, despite `CLAUDE.md` claiming it lives in the foundation repo — and *"WordPress shortcodes on a headless site"* is explicitly named as one of its topics. The one thing that should have answered this question is missing.
3. **No `secrets:` block on `ci-wordpress.yml`** — see §5.2.
4. **No "fail on zero tests" check** in any workflow.
5. The headless **Netlify preview required check is still a comment**, not code.
6. `README.md` recommends pinning `foundation_ref: v1`, but **no tags exist** — every consumer uses `@main`.

---

## 6. Options

### Option A — Stay headless, add a shortcode-render endpoint
**~1–2 days.** Solves the immediate pain, keeps Netlify/ISR/TypeScript, no governance change. Leaves the leaky-abstraction tax in place permanently and does not fix archive/term routing.

### Option B — Staged migration ✅ *recommended*
1. **Now:** ship the shortcode-render endpoint (Option A) so the pain stops immediately and you learn how bad the asset problem really is with your actual third-party plugins.
2. **Fix the foundation first:** add `secrets:` to `ci-wordpress.yml`, add a fail-on-zero-tests gate, decide Option A vs B on the wp-env spec, stand up a real staging URL. Do not migrate onto a broken gate.
3. **Write the missing WordPress Starter Prompt and the shortcode Recipe Book entry** while the context is fresh.
4. **Then migrate** the marketing site (the easy 90% — mostly static content plus CSS work) into the plugin as templates.
5. **Portal last, as its own project** — it needs `/portal/me` built regardless of which architecture wins.

### Option C — Full migration now
**Not recommended.** You would land on an unenforced test gate (§5.2), with no starter prompt and no recipe to follow, while `blueworx_labs_wordpress` has an unwired `client-roles` branch mid-flight.

---

## 7. Risks and blockers

| Risk | Severity | Note |
|---|---|---|
| WordPress Playwright gate runs zero tests | **High** | §5.2 — must be fixed before migrating; affects all WP projects |
| No staging WordPress URL | **High** | `preview_url` is a required CI input; placeholder = skipped tests |
| Portal has no backend (~80% demo data) | **High** | `/portal/me` deferred twice; blocks a like-for-like port |
| 962 lines of bespoke CSS + inline styles | Medium | Largest hands-on cost; no token system to carry across |
| Deployment becomes manual zip | Medium | Loses Netlify auto-deploy; strict zip rules (§5.4) |
| No WP starter prompt / no Recipe Book | Medium | Nothing to scaffold or standardise against |
| `client-roles` branch unwired mid-flight | Low | `includes/client-roles.php` committed but never `require_once`'d; its `client_roles` feature key is missing from `features.php`. Land or park before restructuring. |
| JWT authenticates all of `wp/v2`, not just `blueworx/v1` | Low | `bootstrap.php:39-65` — scope this down if any JWT survives a transition |
| PHP version inconsistency | Low | Plugin header says PHP 8.0+; `composer.json` says `>=7.4` |

---

## 8. Recommended next steps

1. **Decide Option A / B / C.**
2. Raise foundation issues for §5.6 items 1–4 — the CI secrets gap and fail-on-zero-tests block any WP migration.
3. Stand up a real staging WordPress and replace the placeholder `preview_url` in `blueworx_labs_wordpress`.
4. Spike the shortcode-render endpoint against your actual third-party plugins; report back on how many render correctly.
5. Land or park the `client-roles` branch.
6. Propose the shortcode Recipe Book entry for approval.

---

## Appendix — key file references

**Headless:** `app/[...slug]/page.tsx` (only WP-backed route) · `lib/wp-client.ts` (JWT client) · `lib/config.ts` (base derivation, mock switch) · `components/Portal.tsx` (736 lines) · `app/globals.css` (962 lines) · `docs/API_CONTRACT.md` (superseded endpoint names; field shapes still valid) · `docs/plugin-endpoints-cycle2.md` (the deferred `/portal/me`)

**Plugin:** `blueworx-labs-wordpress.php` (v1.15.0) · `includes/rest/` (~2,000 lines) · `includes/features.php` (11 feature flags incl. `admin_theme`) · `HEADLESS_INTEGRATION.md` (583 lines — the formal contract) · `docs/superpowers/specs/2026-07-15-ci-runs-no-tests-design.md`

**Foundation:** `.github/workflows/ci-wordpress.yml` · `scripts/check-version-bump.mjs`, `check-plugin-version-sync.mjs`, `check-plugin-zip.mjs`, `check-approved-deps.mjs` · `CLAUDE.md.template` · `docs/starter-prompt-headless-framework.md`
