# Changelog — BlueWorx | Marketing Site

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/); this project uses semantic
versioning.

Before 1.0.0 this repository was the BlueWorx marketing site as a **Next.js
headless app**. That history is preserved in git. Since 1.1.0 the repository
**is** the `bluegroup-project-blueworx` WordPress plugin (slug `blueworx-site`
before 1.1.2) — the same marketing site, rendered by WordPress instead of served
headlessly from Netlify.

## [1.2.0] - 2026-08-03

### Added

- **Four retired pages now redirect instead of disappearing.** `/shop`,
  `/about-us`, `/features` and `/test-page` were all published and turning up in
  search results, and all four are being removed: `/shop` showed the home page
  for a site with nothing to sell from a storefront, `/about-us` and `/features`
  duplicated pages the plugin already renders, and `/test-page` was never meant
  to be public. Anyone following an old link or search result now lands on the
  page that replaced it — Pricing, About, Toolbox and the home page respectively
  — instead of a 404. Tracking parameters survive the redirect, so campaign
  reporting is unaffected.

## [1.1.4] - 2026-08-01

### Changed

- **CI now pins the shared foundation workflow to `@v1` instead of tracking its
  `main` branch.** Any change to the shared workflow used to land in this
  project's CI the moment it merged upstream, with no way to stage it. `v1` is a
  moving major tag that follows backward-compatible releases, so fixes still
  arrive on their own; a breaking change goes to `v2` and waits for a deliberate
  move here. `foundation_ref` is set to match — it defaults to `main`, so pinning
  only the `uses:` ref would run the v1 workflow against today's scripts.
  Nothing about the plugin itself changes.

## [1.1.3] - 2026-07-23

### Changed

- Renamed the plugin's display name from **BlueWorx | Site** to **BlueWorx |
  Marketing Site** (the `Plugin Name` header shown on the WordPress Plugins
  page), and matched it in `readme.txt`, the `package.json` description, and this
  changelog's title. Slug, files, and internal identifiers are unchanged.

## [1.1.2] - 2026-07-23

### Changed

- **Renamed the plugin slug from `blueworx-site` to `bluegroup-project-blueworx`**
  so the plugin folder, main file, and zip all match the repository name. Updated
  the main file (`bluegroup-project-blueworx.php`), text domain, `package.json`
  name, the build script's slug, the `plugin_slug` CI input, `readme.txt`, and
  the test asset-path assertions. Done before first upload, so no installed
  plugin directory is affected. Internal PHP identifiers (the `BLUEWORX_SITE_*`
  constants and `blueworx_*` function prefix) are unchanged — they are the
  plugin's namespace token, not its slug.

## [1.1.1] - 2026-07-23

### Added

- **Ported the marketing Playwright suite** from `blueworx_labs_wordpress`
  history into `tests/`: the public-rendering and widget specs — `marketing-*`
  (home, about, services, work, ai, contact, plans), `public-content`, and
  `widgets-commerce` / `widgets-showcase` — plus the shared `helpers.js` fixture
  (reduced-motion, base URL, cache-busting). These assert the real rendered
  content and interactive widgets on every marketing page, alongside the
  existing smoke/render gate.
- Fixed the `readme.txt` stable tag, which was left at `1.0.0` in 1.1.0.

### Notes

- The Site-Protection integration specs (`public-site.spec.js`, and the
  Site-Protection parts of `marketing-single-tool.spec.js`) were deliberately
  **not** ported: they test the interaction between the marketing pages and the
  BlueWorx **enhancement** plugin's Site Protection, which `blueworx-site` does
  not ship. That coverage belongs with the enhancement plugin.

## [1.1.0] - 2026-07-23

### Changed

- **The repository is now the `blueworx-site` WordPress plugin** (Stage C of the
  headless→plugin migration). The plugin, previously staged under `plugin/`, is
  promoted to the repository root — its main file, `includes/`, `templates/`,
  `assets/`, `scripts/`, `uninstall.php`, and `readme.txt` now live at the top
  level, and this is the deployable plugin.
- **CI switched from the headless (Next.js) guardrails to the WordPress
  guardrails** (`.github/workflows/ci.yml` now calls
  `ci-wordpress.yml` with `plugin_slug: blueworx-site` and
  `use_local_wordpress: true`): PHP syntax check, plugin version-sync,
  single-zip check, and Playwright run against a WordPress provisioned on the
  runner.

### Added

- **A Playwright gate for the plugin** (`playwright.config.js`, `tests/`): a
  smoke spec and a render spec that install-and-visit every plugin-owned page
  (`/`, `/about`, `/services`, `/contact`, `/work`, `/ai`, `/pricing`,
  `/toolbox`) and assert the plugin's `body.bw-page` document renders. The
  fuller marketing suite (recoverable from `blueworx_labs_wordpress` history) is
  a tracked follow-up.

### Removed

- **The Next.js app** — `app/`, `components/`, `lib/`, `public/`, the Next
  `tests/`, and the Next/TypeScript/ESLint config and lockfile. Preserved in git
  history; the live site is served by the WordPress plugin from 1.1.0 onward.
  (The actual hosting cutover — Netlify → WordPress — is a separate, manual
  operational step, not performed by this change.)

## [1.0.0] - 2026-07-23

Initial release of the plugin (added under `plugin/` in the site repo; see the
`0.6.0` entry in git history).

The BlueWorx public marketing site, extracted from the BlueWorx enhancement
plugin (`blueworx-labs-wordpress`) into its own self-contained, always-on
WordPress plugin.

- Renders all marketing pages itself (home, services, work, pricing, about,
  contact, AI, toolbox, single-tool) via `includes/public/` and `templates/`.
- Always-on: no `public_site` feature gate — the marketing front-end is this
  plugin's sole job.
- Decoupled from the enhancement plugin: vendored `blueworx_site_asset_version()`,
  own constants (`BLUEWORX_SITE_PATH` / `_URL` / `_VERSION`) and text domain
  (`blueworx-site`); the enhancement plugin's `blueworx_site_protection_applies`
  filter is used only as an optional integration, harmless when that plugin is
  absent.
- Own main file, slug, `uninstall.php`, `readme.txt`, and a dependency-free
  `build-zip.mjs` (bsdtar, forward-slash entries).
