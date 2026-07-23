# Changelog — BlueWorx | Site

All notable changes to the BlueWorx | Site plugin are documented here.

## 1.0.0

Initial release.

The BlueWorx public marketing site, extracted from the BlueWorx enhancement
plugin (`blueworx-labs-wordpress`) into its own self-contained, always-on
WordPress plugin.

- Renders all marketing pages itself (home, services, work, pricing, about,
  contact, AI, toolbox, single-tool) via `includes/public/` and `templates/`.
- Always-on: no `public_site` feature gate — the marketing front-end is this
  plugin's sole job.
- Decoupled from the enhancement plugin:
  - Vendored `blueworx_site_asset_version()` in place of the enhancement
    plugin's `blueworx_get_admin_asset_version()`.
  - Own constants (`BLUEWORX_SITE_PATH` / `_URL` / `_VERSION`) and text domain
    (`blueworx-site`).
  - The enhancement plugin's `blueworx_site_protection_applies` filter is used
    only as an optional integration — harmless when that plugin is absent.
- Own main file, slug, `uninstall.php`, `readme.txt`, and a dependency-free
  `build-zip.mjs` (bsdtar, forward-slash entries).
