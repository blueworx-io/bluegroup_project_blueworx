# Changelog — BlueWorx | Marketing Site

All notable changes to this project are documented here. Format follows
[Keep a Changelog](https://keepachangelog.com/); this project uses semantic
versioning.

Before 1.0.0 this repository was the BlueWorx marketing site as a **Next.js
headless app**. That history is preserved in git. Since 1.1.0 the repository
**is** the `bluegroup-project-blueworx` WordPress plugin (slug `blueworx-site`
before 1.1.2) — the same marketing site, rendered by WordPress instead of served
headlessly from Netlify.

## [1.12.6] - 2026-08-04

### Fixed

- **The sitemap was inviting Google to index the client area.** Sign in, sign
  up, reset a password, the dashboard and its three sections were all listed,
  and all told search engines to index them. They are now marked private, in
  the way the site's SEO plugin actually reads, which both keeps them out of
  search results and drops them from the sitemap.
- **Dashboard pages carried two contradictory instructions at once** — one
  saying do not index, one saying index — because the plugin printed its own
  tag next to the SEO plugin's rather than instead of it. There is now exactly
  one, on every page.
- `/feature`, `/portal` and `/form` were listed in the sitemap and returned an
  error. They now send visitors to the Toolbox, the sign-in page and the
  contact page respectively.

## [1.12.5] - 2026-08-04

### Added

- **A BlueWorx page for a bad address.** Anyone who followed a rotted link got
  a bare "Not Found" heading on a white page from the fallback theme — no
  branding, no menu, no footer and no way back into the site. It now looks like
  the rest of the site, says plainly what happened, and offers Home, Services,
  Toolbox, Pricing and Contact.

## [1.12.4] - 2026-08-04

### Removed

- **The footer's dead links and its newsletter box.** Blog, Resources and
  Careers had nowhere to go, the three social icons had no profiles behind
  them, and the newsletter field and button were markup with no form attached —
  typing an address and pressing the button did nothing at all. All of it
  looked like working controls on every page of the site. They come back when
  there is something real to point them at.
- The About column now lists Contact and Client Login, both of which work.

## [1.12.3] - 2026-08-04

### Fixed

- **"Skip to the content" was printed on every page of the site** as ordinary
  blue underlined text in the top-left corner, and following it did nothing.
  The link is meant to be invisible until somebody reaches it with the
  keyboard, but the rule that hides it comes from the theme's stylesheet, which
  the plugin removes from the pages it renders — and it pointed at a part of
  the page that did not exist.
- The link is now the plugin's own, so it works whatever theme the site has, it
  stays hidden until it is focused, and following it puts the keyboard at the
  start of the content.

## [1.12.2] - 2026-08-04

### Changed

- **The tests now cover the way the site actually gets updated.** Every check
  ran against a WordPress built from scratch seconds earlier, which is the one
  state the Toolbox tool pages were never broken in — so all twelve could be
  dead on the live site with every check green. There are now tests for a site
  that updates in place, a site whose tool pages have drifted out of place, and
  a Toolbox page that has been deleted and made again.
- Tests read the list of tools from the plugin itself instead of a copy written
  into each test file, so a tool cannot be added without being tested.
- After a release goes out, the public pages of blueworx.io are checked
  automatically. Nothing in the pipeline had ever looked at the real site.

## [1.12.1] - 2026-08-04

### Fixed

- **Every Toolbox tool page was a dead link.** All twelve are linked from the
  desktop menu, the mobile menu and the Toolbox page, and none of them worked.
  The pages were only ever created when the plugin was activated, so a site that
  updated in place — which is every update — never got them, and a site that
  already had a page called Toolbox got the twelve tool pages dumped at the top
  level of the site instead, at addresses nothing links to.
- Pages are now created and repaired on the first visit after any update, so a
  tool added in a future release brings its page with it, and a site that is
  already in the broken state fixes itself rather than needing the database
  edited by hand.
- The menu now reads its tool list from the plugin's own registry instead of a
  second copy written out by hand, so the menu and the pages cannot disagree.

## [1.12.0] - 2026-08-04

### Added

- **The Toolbox tools have a screen of their own in the admin menu.** It lists
  every tool with its category, address and a link to view it on the site, and
  says plainly that tools are part of the plugin — adding one or rewording it is
  a change that ships with a release.

### Changed

- The twelve tool pages no longer clutter the Pages list. They were pages that
  could not usefully be edited, sitting in the one screen somebody opens to find
  a page they can change. Only those twelve are hidden, and only from that list:
  Home, Pricing, Contact and the client area stay where they are, and the pages
  themselves are untouched everywhere else — navigation, permalinks and the site
  itself are unaffected.

## [1.11.1] - 2026-08-04

### Fixed

- **Pricing can now actually be connected to SureCart.** The settings screen
  threw away every price ID it was given: it insisted an ID begin with `price_`,
  and no SureCart ID does — they are UUIDs. The field emptied itself on save
  without saying anything, so the pricing page kept showing its built-in figures
  and kept sending "Get started" to the contact form, exactly as if nothing had
  been configured.
- **The plan buttons sent SureCart a checkout with nothing in it.** The link
  named the price under the wrong key, and SureCart drops a line item it does
  not recognise rather than objecting, so the checkout opened normally and
  empty.
- A price ID that is refused now says which plan it was refused for, instead of
  the box quietly going blank.

The tests agreed with the code on both counts, which is why neither showed up:
they wrote invented `price_`-style IDs straight into the database and never put
a real one through the settings form. They now use real UUIDs and cover the save.

## [1.11.0] - 2026-08-04

### Changed

- **The client area now looks like the approved portal design.** It reads as an
  application rather than a web page: a dark sidebar carrying the navigation
  and the signed-in client, a header strip with the page name and a "New
  request" button, and cards and record tables to match.
- On a phone the sidebar becomes a row of links above the content rather than
  a drawer, and record tables scroll inside themselves rather than dragging the
  page sideways.

### Fixed

- **The marketing site's header styling was leaking into the portal sidebar** —
  it applied to any navigation on a plugin-rendered page, which gave the
  sidebar a translucent white background and centred its links.
- The WordPress admin bar is now accounted for, so the bottom of the sidebar is
  no longer pushed below the fold for anyone signed in.

Parts of the design are deliberately not built: websites, support, partner and
contacts, and the figures on the overview for uptime, visits and tools in use.
Every one of them needs a source of truth this site does not have, and a
client's own account is the worst possible place to show invented numbers.

## [1.10.0] - 2026-08-03

### Changed

- **The plan buttons now ask SureCart where its checkout page is** instead of
  assuming `/checkout`, so they keep working on a site whose checkout lives
  somewhere else or has been renamed. Setting the checkout page by hand still
  overrides it.

### Added

- `docs/client-area-setup.md` — everything that has to be switched on for the
  client area to work, in order, with what to try afterwards. None of it is a
  release; it is all settings.

## [1.9.2] - 2026-08-03

### Fixed

Checked the SureCart integration against the real SureCart plugin rather than
against assumptions. Four things were wrong, and none of them would have looked
wrong:

- **Every plan would have shown as "Support plan".** SureCart returns a related
  record as a bare reference unless you ask for it, so no plan name or price
  came back at all.
- **A failed request showed as an empty account.** SureCart reports failure by
  returning an error rather than raising one, so a client whose data could not
  be loaded was told they had none.
- **Prices in a currency with no decimal part would have shown a hundred times
  too small.** Amounts are now formatted by SureCart instead of assumed to be
  in cents.
- **The invoice PDF column would have been dead links.** SureCart has no PDF to
  link to. Unpaid invoices now offer a payment link instead, which is what a
  client wants from that page anyway.

## [1.9.1] - 2026-08-03

### Changed

- The accessibility and mobile checks that already covered every marketing page
  now cover the dashboard and the sign-in pages too — heading order, alt text,
  form labels, keyboard access, and no sideways scrolling on a phone.

Tests only. Nothing about the site changes.

## [1.9.0] - 2026-08-03

### Added

- **Signing in, creating an account and resetting a password now happen on the
  site.** Three pages the plugin renders, on brand, replacing the portal pages
  that belonged to a plugin we are removing. A client never lands on
  wp-login.php, including from the password-reset email.
- Signing in returns the client to whatever they were trying to reach.

### Changed

- **The Client Login link now points at that sign-in page** rather than at the
  old portal. A site that has set the link explicitly keeps what it set.

New accounts follow WordPress's own "Anyone can register" setting — with it off,
the page invites the visitor to get in touch instead. Passwords must be at least
12 characters. Sign-in failures and reset requests deliberately give the same
answer whether or not an account exists, so neither can be used to find out who
has one.

## [1.8.0] - 2026-08-03

### Added

- **The dashboard now shows real records.** Subscriptions with what they cost
  and when they renew, invoices with a link to the PDF, and order history — all
  read live from SureCart rather than stored here.
- A client only ever sees their own records. If the account cannot be
  identified, nothing is fetched at all rather than falling back to a wider
  query.
- If SureCart cannot be reached, the page says so. It never shows a paying
  client an empty account because a request failed.

Changing or cancelling a plan is still done by getting in touch rather than by
a button, and each section says so.

## [1.7.0] - 2026-08-03

### Added

- **A client dashboard, in the plugin.** `/dashboard`, with a section each for
  subscriptions, invoices and orders. It replaces the SureDash portal, so
  SureDash can eventually come off the site.
- Only a signed-in client can reach any of it — a logged-out visitor is sent to
  log in and then returned to the page they asked for. No dashboard page is
  indexed by search engines.

The three sections say plainly that there is nothing to show yet. They are
connected to real SureCart data by the work that follows this. Existing sites
get the new pages the first time this version loads; nothing else changes.

## [1.6.0] - 2026-08-03

### Added

- **Plan prices can now come from SureCart instead of being written into the
  plugin.** Enter a SureCart price ID per plan, per billing period, under
  Settings → BlueWorx Site, and the Pricing page shows what SureCart actually
  charges — so changing a price in one place changes it everywhere.
- **"Get started" can now go to checkout.** A plan with a price ID sends the
  visitor straight to a checkout for that plan, and switching between monthly
  and annual billing moves the button as well as the figure, so nobody picks
  annual and is charged monthly.

A plan left blank behaves exactly as before: the price built into the plugin,
and a button that goes to the contact form. The same applies if SureCart is
switched off, a price ID is wrong, or SureCart cannot be reached — the page
falls back rather than breaking.

## [1.5.4] - 2026-08-03

### Fixed

- **The plugin claimed pages it did not create, and stripped their styling.**
  Ownership was decided by slug, so a site with its own page named "home",
  "about" or "pricing" had that page treated as the plugin's — and the asset
  sweep then removed its theme and page-builder CSS, leaving the page with no
  layout at all. Seen on a live client site. A page now belongs to this plugin
  only if this plugin created it, which is recorded when the page is made.
  Nothing changes for the plugin's own pages.
- **The front page could be repointed by the same slug collision.** Taking over
  the homepage now requires a home page the plugin actually created.
- **Site Protection no longer exempts a page the plugin does not own** at a path
  matching one of its own — the two ownership checks agree again.

Existing sites are upgraded automatically the first time this version loads;
their pages stay exactly as they are. Uninstall leaves the ownership marker in
place so a reinstall recognises its own pages.

## [1.5.3] - 2026-08-03

### Fixed

- **Two things this repo's own documentation said that were not true.** The
  plugin replacement plan claimed SureCart had no products — it does; the audit
  counted the `sc_product` post type in WordPress, which is empty because
  SureCart keeps products in its own cloud. It also listed ACF Pro as an unknown
  blocking the whole plan; ACF is confirmed unused and can simply be removed.
  The plan's order and estimate are corrected to match.
- **The plugin zip naming rule in `CLAUDE.md`,** which contradicted the shared
  foundation and produced an unversioned artifact. Updates ship as GitHub
  Releases; a hand-built zip is `<slug>-<version>.zip`, with the version in the
  filename only and never in the folder inside the archive — a versioned folder
  installs a second copy of the plugin on every update instead of replacing it.

Documentation only; the plugin itself is unchanged.

## [1.5.2] - 2026-08-03

### Added

- `docs/plugin-replacement-plan.md` — a written proposal for the remaining
  plugin replacements and the client area (#30–#43, #53–#55), based on a
  read-only audit of the live site rather than an estimate. Documentation only;
  the plugin itself is unchanged.

## [1.5.1] - 2026-08-03

### Fixed

- **Heading order on every marketing page.** Card headings across the footer,
  team, contact, services, toolbox, process and AI sections were `h4`s sitting
  directly under an `h2`, which tells a screen reader a whole level of the page
  is missing. They are now `h3`s, and the stylesheet follows them, so every
  heading renders at exactly the size it did before — verified by comparing
  computed styles on all nine page types before and after.
- The Work and Contact pages each had a section with no heading at all, so their
  cards followed the page title with a level missing in between. Both sections
  are now named for screen readers only; sighted layout is unchanged.

### Added

- **A standards check that runs over every marketing page** — one h1, no skipped
  heading levels, an alt attribute on every image, a name on every link and
  button, a declared language and title, no sideways scrolling at 375px, and a
  real focusable control as the first Tab stop. Covers the twenty pages the
  plugin renders plus the 404, which is also checked for returning a genuine 404
  status rather than a 200 with "not found" written on it.

## [1.5.0] - 2026-08-03

### Added

- **A settings screen at Settings → BlueWorx Site.** Two things were already
  configurable in principle — the shortcode the Contact page renders, and where
  the nav's Client Login link points — but only through options with no screen
  behind them, which meant "configurable" for anyone willing to run WP-CLI or
  write a filter, and nobody else. That is why the Contact page has been showing
  a grey placeholder on a site that already has a published contact form ready
  to use: the only missing piece was somewhere to paste its shortcode.
- Anything pasted into the shortcode field has its markup stripped before it is
  stored, so the field cannot be used to put a script tag on every visitor's
  Contact page. Only administrators can reach the screen, but an admin session
  is exactly what a compromised account gives an attacker.

## [1.4.0] - 2026-08-03

### Changed

- **The nav's Client Login link is now set in one place instead of three.** It
  still points at `/portal`, so nothing changes for visitors today. But `/portal`
  belongs to SureDash, which is on its way out — and the link was written by hand
  into the desktop nav, the mobile bar and the mobile menu, so removing SureDash
  would have turned all three into dead links, with three chances to miss one.
  Pointing it at the new client dashboard is now a setting
  (`blueworx_client_login_url`, or the filter of the same name) rather than a
  code change.

## [1.3.0] - 2026-08-03

### Changed

- **Marketing pages no longer load other plugins' styles and scripts.** Every
  page the plugin renders was carrying assets belonging to plugins that take no
  part in rendering it — SureCart alone was inlining 110 block stylesheets into
  pages that contain no blocks, worth 71KB on the About page, and UiCore was
  adding a global script from the uploads folder. The About page drops from
  126KB to roughly 55KB and makes six fewer script requests, one of which was to
  an external domain on every page view. Nothing about how the pages look or
  behave changes.
- Pages the plugin does **not** render are untouched, so other plugins keep
  working normally everywhere else on the site. The Contact page keeps its form
  plugin's assets whenever a form shortcode is configured, so an embedded form
  still works.

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
