# Site Restructure (ClubHouse, Hosting, Support + new nav) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the three new product pages (ClubHouse, Hosting, Integrated Support), restructure the site nav and footer to the new menu, add a GBP/EUR/USD currency switcher, and retire the Pricing and Services pages behind redirects — without touching pages the design left alone.

**Architecture:** Everything is a WordPress plugin: pages are PHP templates under `templates/pages/`, reusable blocks under `templates/parts/`, one stylesheet `assets/css/public.css` (scoped to `.bw-page`), two scripts (`assets/js/public-nav.js` for the header, `assets/js/public-widgets.js` for page widgets). Pages are registered in `blueworx_public_pages()` (`includes/public/pages.php`) and created on the first request after a version bump. Content data lives in PHP arrays (`includes/public/content.php`). Playwright specs in `tests/` run against a local WordPress.

**Tech Stack:** PHP 8 / WordPress plugin, vanilla JS (ES5 style, IIFE), plain CSS, Playwright.

**Spec:** `docs/superpowers/specs/2026-09-20-site-restructure-design/README.md` (design handoff) and the `.dc.html` page designs beside it. The `.dc.html` files are *references*: the markup between `<x-dc>` and `</x-dc>` is what to build; the `<helmet><style>` block holds page-level CSS that must be ported; the `<script data-dc-script>` block holds the copy/data. `{{ x }}` are values, `<sc-for list="{{ items }}" as="item">` is a loop, `<sc-if>` a conditional, `<dc-import name="Site Nav">` mounts the shared nav. Do not ship anything from the bundle other than what these tasks name.

## Global Constraints

- **Out of scope, do not build:** the Client Dashboard design (`BlueWorx Dashboard.dc.html` was deliberately not copied into the spec folder). The existing portal templates (`templates/pages/dashboard*.php`, `templates/parts/dash-*.php`) must not be edited.
- **Pages that do not change** (Home, Work, AI Powered, Contact, About, Toolbox, Journal, 404, auth pages): only their **links** change (retired URLs → new ones). No layout or copy edits.
- **Contact form stays the SureForms shortcode.** The designed custom form with budget chips is not built.
- **Currency:** GBP is the base. Rates are fixed: `EUR ×1.17`, `USD ×1.27`. Symbols `£ € $`. Stored in `localStorage['bw-currency']`, broadcast as a `bw:currency` window event. Only elements carrying `data-bw-gbp` convert; every other price on the site (Toolbox plans in `$`, SureCart-fed amounts) is left alone.
- **New page slugs:** `/clubhouse`, `/hosting`, `/support`. Retired: `/pricing` → `/support`, `/services` → `/support` (301, via the existing legacy-redirect mechanism). `/toolbox` stays live.
- **Nav order:** Home · ClubHouse · Hosting · Support · Work · AI Powered (with "New" tag). Right cluster: "Client Login" link · "Contact" gradient button · currency switcher. About and Journal live only in the footer.
- **Section rhythm:** new pages use `.sec` (116px) with a hairline top border between adjacent light sections. Use the modifier class `bw-divided` on the section, never a sibling selector.
- **Card grids:** `.bw-g2 / .bw-g3 / .bw-g4` with fixed column counts (see Task 2). Never `auto-fit`.
- **Prices:** ClubHouse £20/month or £200/year. Hosting £20/month or £200/year. Support packages (annual hours / £ per month): Starter 24/100, Launch 48/200, Scale 72/300, Enhance 96/400, Growth 120/500 (featured), Enterprise 240/750, Enterprise + 360/1000, Advantage 480/1250, Advantage + 600/1500. Effective rate = `(gbp × 12) / hours`, 2dp.
- **Version bump:** 1.15.0 → **1.16.0** (minor: new features) in `bluegroup-project-blueworx.php` (header + `BLUEWORX_SITE_VERSION`), `package.json`, `readme.txt` Stable tag, and a `CHANGELOG.md` entry. Done in the last task.
- **Coding conventions:** every PHP file starts with the `ABSPATH` guard; all output escaped (`esc_html__`, `esc_url`, `esc_attr`); text domain `bluegroup-project-blueworx`; template-local variables are prefixed `$blueworx_<part>_`; tabs for PHP indentation; internal links via `home_url( '/x' )`. JS: ES5 in the existing IIFE, `'use strict'`, each `init*()` no-ops when its marker is absent.
- **Lint once at the end** (`npm run lint`), present findings, do not auto-fix in a loop.
- **Branch:** `site-restructure`, PR to `main`. Commit after every task.

## Local test environment

The repo's WordPress lives in `.wp-test/wp` (SQLite). Port 8881 on this machine belongs to a *different* repo's server, so serve this one on **8882** with the router that overrides `WP_HOME`:

```bash
# router (kept at ~/.claude/tools/blueworx-design-snapshot/router.php; recreate if missing)
cat > /tmp/bw-router.php <<'EOF'
<?php
define('WP_HOME', 'http://127.0.0.1:8882');
define('WP_SITEURL', 'http://127.0.0.1:8882');
$root = 'C:/Users/LukeMcfarland/Documents/GitHub/bluegroup_project_blueworx/.wp-test/wp';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($root . $path)) { return false; }
$_SERVER['SCRIPT_NAME'] = '/index.php'; $_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
chdir($root); require $root . '/index.php';
EOF
cd .wp-test/wp && (php -S 127.0.0.1:8882 -t . /tmp/bw-router.php > /tmp/bw8882.log 2>&1 &)
```

Run specs with `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8882 WP_ADMIN_USER=admin WP_ADMIN_PASS='dsSyncPass123!' npx playwright test tests/<spec>.js`. New pages are created automatically on the first request after the version constant changes (Task 10 bumps it; until then, temporarily bump `BLUEWORX_SITE_VERSION` locally or call `blueworx_public_install_pages()` via `php -r` from `.wp-test/wp`: `php -r '$_SERVER["HTTP_HOST"]="127.0.0.1:8882"; require "wp-load.php"; do_action("init"); blueworx_public_install_pages();'`).

Watch out: `.wp-test/wp/wp-content/plugins/bluegroup-project-blueworx` is a junction into this repo — never `rm -rf .wp-test`.

---

## File map

| File | Responsibility |
| --- | --- |
| `assets/css/public.css` | Add: card grids, section divider modifier, FAQ open-state fix, `.pop` colour fix, range slider, ClubHouse demo band + tight hero, currency switcher, nav logo 34px, footer mobile fix. |
| `assets/js/public-nav.js` | Add `initCurrencySwitcher()` — dropdown, persistence, `bw:currency` event. |
| `assets/js/public-widgets.js` | Add `initCurrencyPrices()` — paints `[data-bw-gbp]`; make the billing toggle currency-aware; add `initSupportCalc()` (hours slider). |
| `includes/public/content.php` | Replace the 3 retainer plans with the 9 Support packages (same accessor, SureCart wiring keeps working); add `blueworx_content_support_faqs()`, `blueworx_content_hosting()`, `blueworx_content_clubhouse()`. |
| `includes/public/pages.php` | Register `clubhouse`, `hosting`, `support`; remove `pricing`, `services`. |
| `includes/public/upgrade.php` | Trash the retired Pricing/Services pages on the version-change install pass. |
| `includes/public/redirects.php` | `pricing` and `services` → `support`; `shop` → `support`. |
| `includes/public/seo-copy.php` | Title/description for the three new pages; drop the two retired. |
| `includes/admin/settings.php` | Price-ID field iterates the sellable plans (packages + hosting + clubhouse). |
| `templates/parts/nav.php` | New menu, Contact button, currency switcher; mega panel and About dropdown removed. |
| `templates/parts/footer.php` | New columns; CTA band takes per-page copy via `$vars`. |
| `templates/parts/plan-card.php` | **New.** One plan card (extracted from `plan-cards.php`), with `£`/`data-bw-gbp` support and sub-label overrides. |
| `templates/parts/plan-cards.php` | Loops `plan-card.php`. |
| `templates/pages/clubhouse.php`, `hosting.php`, `support.php` | **New** pages. |
| `templates/pages/pricing.php`, `services.php` | **Deleted.** |
| `templates/pages/home.php`, `work.php`, `ai.php`, `dashboard-*.php` | Link retargets only. |
| `assets/img/clubhouse-demo-{home,teams,membership}.jpg/.webp` | **New** screenshots of demo.305media.co.uk. |
| `tests/marketing-clubhouse.spec.js`, `marketing-hosting.spec.js`, `marketing-support.spec.js`, `currency.spec.js`, `nav-structure.spec.js` | **New** specs. |
| `tests/*` (≈15 existing specs) | Path lists updated for the retired/new pages. |

---

### Task 1: Branch, demo screenshots, and the spec folder

**Files:**
- Create: `assets/img/clubhouse-demo-home.jpg`, `assets/img/clubhouse-demo-teams.jpg`, `assets/img/clubhouse-demo-membership.jpg` (+ `.webp` twins)
- Already present: `docs/superpowers/specs/2026-09-20-site-restructure-design/*`

- [ ] **Step 1: Create the branch**

```bash
git checkout -b site-restructure
git add docs/superpowers/specs/2026-09-20-site-restructure-design docs/superpowers/plans/2026-09-20-site-restructure.md
git commit -m "Add the site restructure design handoff and plan"
```

- [ ] **Step 2: Capture the three ClubHouse demo screenshots**

The design's demo band slots are ~700×392 (large) and ~330×186 (stacked), all 16:9-ish. Capture at 1400×788 so they stay sharp at 2× and crop with `object-fit: cover` in CSS. Run from the repo root so `@playwright/test` resolves:

```bash
cat > scripts/.shot-demo.mjs <<'EOF'
import { chromium } from '@playwright/test';
const shots = [
  ['https://demo.305media.co.uk/', 'assets/img/clubhouse-demo-home.jpg'],
  ['https://demo.305media.co.uk/teams/', 'assets/img/clubhouse-demo-teams.jpg'],
  ['https://demo.305media.co.uk/membership/', 'assets/img/clubhouse-demo-membership.jpg'],
];
const b = await chromium.launch();
const p = await b.newPage({ viewport: { width: 1400, height: 788 }, deviceScaleFactor: 1 });
for (const [url, path] of shots) {
  await p.goto(url, { waitUntil: 'networkidle' });
  await p.waitForTimeout(1500);
  await p.screenshot({ path, type: 'jpeg', quality: 82 });
  console.log('saved', path);
}
await b.close();
EOF
node scripts/.shot-demo.mjs && rm scripts/.shot-demo.mjs
php scripts/build-webp.php
ls -la assets/img/clubhouse-demo-*
```

Expected: six files (3 jpg + 3 webp), each jpg under ~250KB. Open each jpg (Read tool) and confirm it shows a club site page, not a cookie banner or a blank screen; if a cookie overlay covers it, dismiss it with `page.click('text=Accept')` before the screenshot and re-run.

- [ ] **Step 3: Commit**

```bash
git add assets/img/clubhouse-demo-*
git commit -m "Add ClubHouse demo screenshots"
```

---

### Task 2: Stylesheet additions

**Files:**
- Modify: `assets/css/public.css`
- Test: `tests/currency.spec.js` (created in Task 4 — this task is CSS only and is verified visually in later tasks)

**Interfaces:**
- Produces classes used by later tasks: `.bw-g2/.bw-g3/.bw-g4`, `.sec.bw-divided`, `.bw-range`, `.bw-demo`, `.tech-hero.bw-hero-tight`, `.bw-cur`, `.bw-cur-btn`, `.bw-cur-menu`, `.bw-feat-note`.

- [ ] **Step 1: Fix the two live bugs the design pass found**

In `assets/css/public.css` replace the FAQ block (currently lines ~410–419, the `/* faq */` section) so the native `<details>` open state — which is what `public-widgets.js` actually toggles — shows the answer. The old `.faq-item.open` rules matched nothing, so every FAQ answer on the live site is collapsed to `max-height:0`:

```css
/* faq — native <details>; the answer must never be height-clipped */
.faq-list { max-width: 940px; margin: 0 auto; }
.faq-item { border-bottom: 1px solid #EFEFF0; }
.faq-q { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 26px 8px; cursor: pointer; font-size: 18px; font-weight: 600; color: #0A0C29; list-style: none; }
.faq-q::-webkit-details-marker { display: none; }
.faq-q svg { width: 22px; height: 22px; flex-shrink: 0; color: #4F46E5; transition: transform .25s ease; }
.faq-item[open] .faq-q svg { transform: rotate(180deg); }
.faq-a { max-height: none; overflow: visible; padding: 0 8px 26px; }
.faq-a p { font-size: 15px; line-height: 1.65; color: #667085; max-width: 820px; }
```

Then fix the "Popular" badge on the featured plan card, which `.feat .plan-name span { color:#fff }` was painting white-on-white. Directly after the existing `.pop { ... }` rule (line ~351) add:

```css
.feat .plan-name .pop { color: #4338CA; }
```

- [ ] **Step 2: Add the shared layout classes**

Append to the end of `assets/css/public.css`:

```css
/* ===== site restructure (2026-09) — shared layout ===== */
/* Deterministic card grids. auto-fit left orphan rows (3 + 1), so column
   counts are fixed per breakpoint. */
.bw-g2, .bw-g3, .bw-g4 { display: grid; gap: 20px; }
.bw-g2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.bw-g3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.bw-g4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
@media (max-width: 1100px) { .bw-g4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 900px) { .bw-g3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 700px) { .bw-g2, .bw-g3, .bw-g4 { grid-template-columns: minmax(0, 1fr); } }

/* A hairline between adjacent light sections. A modifier, not a sibling
   selector, so a page states which sections divide rather than inheriting it
   from whatever happens to precede them. */
.bw-page .sec.bw-divided { border-top: 1px solid #EFEFF0; }

/* Light and dark cards used by the product pages. */
.bw-card { background: #fff; border: 1px solid #EFEFF0; border-radius: 16px; padding: 28px 26px; box-shadow: 0 1px 2px rgba(10,12,41,.04); }
.bw-card h3 { font-size: 19px; letter-spacing: -.3px; margin: 0 0 8px; }
.bw-card p { font-size: 15px; margin: 0; color: #4C4C4C; line-height: 1.55; }
.bw-card .bw-stat { font-family: 'Sora', sans-serif; font-size: 30px; font-weight: 600; letter-spacing: -1px; color: #4F46E5; margin-bottom: 10px; }
.bw-card-dark { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1); border-radius: 16px; padding: 26px 24px; }
.bw-card-dark .bw-tag { font-family: 'SF Mono', ui-monospace, Menlo, monospace; font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: #A5A7FF; margin-bottom: 14px; }
.bw-card-dark h3 { font-size: 19px; color: #fff; margin: 0 0 8px; letter-spacing: -.3px; }
.bw-card-dark p { font-size: 14.5px; color: rgba(255,255,255,.66); margin: 0; line-height: 1.6; }

/* The "supporting panel" beside a single plan card (ClubHouse, Hosting). */
.bw-plan-aside { background: #F5F6FF; border-radius: 16px; padding: 34px 32px; display: flex; flex-direction: column; gap: 18px; }
.bw-plan-aside h3 { font-size: 22px; letter-spacing: -.4px; margin: 0; }
.bw-plan-aside p { font-size: 15.5px; margin: 0; color: #4C4C4C; line-height: 1.6; }
.bw-plan-aside .collab-list { margin: 0; }
.bw-plan-aside .fli { border-bottom: none; padding: 8px 0; }
.bw-plan-aside .fli span { font-size: 16px; }
.bw-plan-aside .pf { font-size: 15px; }
.bw-plan-aside .btn { align-self: flex-start; }
.bw-plan-grid { gap: 22px; max-width: 960px; margin: 0 auto; }

/* Split section header: eyebrow + h2 on the left, a button on the right. */
.bw-split-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; flex-wrap: wrap; margin-bottom: 32px; }
.bw-split-head .h2 { max-width: 560px; }

/* Support page: hours slider. */
input[type="range"].bw-range { -webkit-appearance: none; appearance: none; width: 100%; height: 6px; border-radius: 99px; background: #E8E7F7; outline: none; }
input[type="range"].bw-range::-webkit-slider-thumb { -webkit-appearance: none; width: 24px; height: 24px; border-radius: 50%; background: #0A0C29; border: 4px solid #fff; box-shadow: 0 2px 8px rgba(10,12,41,.25); cursor: pointer; }
input[type="range"].bw-range::-moz-range-thumb { width: 20px; height: 20px; border-radius: 50%; background: #0A0C29; border: 4px solid #fff; cursor: pointer; }
.bw-range-ends { display: flex; justify-content: space-between; font-size: 12.5px; color: #A0AFC0; margin-top: 8px; }
.bw-calc-big { display: flex; align-items: baseline; gap: 10px; margin-bottom: 14px; }
.bw-calc-big b { font-family: 'Sora', sans-serif; font-size: 40px; letter-spacing: -1px; color: #0A0C29; }
.bw-calc-big span { font-size: 15px; color: #667085; }
.bw-calc-name { font-family: 'Sora', sans-serif; font-size: 22px; font-weight: 600; color: #0A0C29; }
.bw-calc-blurb { font-size: 14.5px; color: #667085; margin: 6px 0 0; line-height: 1.55; }
.bw-calc-rate { font-family: 'Sora', sans-serif; font-size: 18px; color: #0A0C29; }
.tech-status .bw-plain::before { display: none; }

/* ClubHouse: the hero headline is set so the accent phrase holds one line in
   the 560px column; the demo band is a wide slot beside a stacked pair. */
.tech-hero.bw-hero-tight .h1 { font-size: 56px; letter-spacing: -1.9px; }
.tech-hero.bw-hero-tight .h1 .tech-grad { white-space: nowrap; }
@media (max-width: 1100px) { .tech-hero.bw-hero-tight .h1 { font-size: 44px; letter-spacing: -1.2px; } }
@media (max-width: 700px) { .tech-hero.bw-hero-tight .h1 { font-size: 36px; letter-spacing: -.9px; } }
.bw-demo { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: stretch; grid-auto-rows: 392px; }
.bw-demo-stack { display: grid; grid-template-rows: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; min-width: 0; min-height: 0; height: 100%; }
.bw-demo-shot { display: block; width: 100%; height: 100%; min-width: 0; border-radius: 16px; overflow: hidden; border: 1px solid #EFEFF0; box-shadow: 0 1px 2px rgba(10,12,41,.04); }
.bw-demo-shot img { width: 100%; height: 100%; object-fit: cover; object-position: top; }
@media (max-width: 900px) { .bw-demo { grid-template-columns: minmax(0, 1fr); grid-auto-rows: auto; } .bw-demo-shot { aspect-ratio: 16 / 9; } }

/* Nav: larger logo, tighter link gap, the currency switcher. */
.nav-logo img { height: 34px; }
.nav-links { gap: 4px; }
.bw-cur { position: relative; flex-shrink: 0; }
.bw-cur-btn { display: inline-flex; align-items: center; gap: 7px; height: 46px; padding: 0 14px; border-radius: 12px; border: 1px solid rgba(10,12,41,.14); background: #fff; font-family: 'Sora', sans-serif; font-size: 14.5px; font-weight: 600; color: #0A0C29; cursor: pointer; transition: background .2s ease, border-color .2s ease, color .2s ease; }
.bw-cur-btn:hover { background: #E8E7F7; border-color: #4338CA; color: #4338CA; }
.bw-cur-btn svg { width: 14px; height: 14px; transition: transform .2s ease; }
.bw-cur.open .bw-cur-btn svg { transform: rotate(180deg); }
.bw-cur-menu { display: none; position: absolute; top: calc(100% + 10px); right: 0; min-width: 150px; background: #0A0C29; border-radius: 14px; box-shadow: 0 30px 70px rgba(10,12,41,.34); padding: 8px; z-index: 260; }
.bw-cur.open .bw-cur-menu, .bw-cur:focus-within .bw-cur-menu { display: block; }
.bw-cur-menu button { display: flex; align-items: center; justify-content: space-between; gap: 14px; width: 100%; padding: 10px 12px; border: 0; border-radius: 9px; background: transparent; font-family: 'Sora', sans-serif; font-size: 14.5px; font-weight: 500; color: #fff; cursor: pointer; transition: background .18s ease; }
.bw-cur-menu button:hover { background: rgba(255,255,255,.08); }
.bw-cur-menu button.on { background: rgba(139,142,255,.18); color: #C9CBFF; }
.bw-cur-menu button i { font-style: normal; opacity: .55; font-size: 13px; }
.mobile-menu .bw-cur { width: 100%; margin-top: 6px; }
.mobile-menu .bw-cur-btn { width: 100%; justify-content: space-between; }
.mobile-menu .bw-cur-menu { left: 0; right: 0; }

/* Footer: below 1000px .ft has two tracks for three children; let the brand
   block span the row so the two link columns pair up. */
@media (max-width: 1000px) { .ft > .fb { grid-column: 1 / -1; } }
```

- [ ] **Step 3: Check the stylesheet still parses and the FAQ opens**

Start the local server (see "Local test environment"), open `http://127.0.0.1:8882/contact/` in Playwright and click the first FAQ question:

```bash
cat > scripts/.faq-check.mjs <<'EOF'
import { chromium } from '@playwright/test';
const b = await chromium.launch(); const p = await b.newPage();
await p.goto('http://127.0.0.1:8882/contact/?nocache=' + Date.now());
await p.locator('.faq-list details.faq-item').first().locator('summary').click();
const h = await p.locator('.faq-list details.faq-item').first().locator('.faq-a').evaluate((el) => el.getBoundingClientRect().height);
console.log('answer height', h);
await b.close();
EOF
node scripts/.faq-check.mjs; rm scripts/.faq-check.mjs
```

Expected: `answer height` greater than 40 (before this task it was 0).

- [ ] **Step 4: Commit**

```bash
git add assets/css/public.css
git commit -m "Add the restructure's shared layout styles and fix the collapsed FAQ answers and hidden Popular badge"
```

---

### Task 3: Plan card part with GBP support

**Files:**
- Create: `templates/parts/plan-card.php`
- Modify: `templates/parts/plan-cards.php`
- Test: `tests/marketing-plans.spec.js` (existing Toolbox assertions must still pass — the `$` path is unchanged)

**Interfaces:**
- Produces: `blueworx_public_part( 'parts/plan-card.php', array( 'plan' => $plan ) )` where `$plan` is `array( name, desc, priceM, priceA, feat (bool), pop (bool|string), features (string[]), buyM?, buyA?, currency? ('GBP'), subM?, subA?, lbl? )`.
  - `currency => 'GBP'` renders `£` and puts `data-bw-gbp="<priceM>"` on the `<b>` so Task 4's painter converts it.
  - `pop` may be a string label (Hosting shows "Per site"); `true` means "Popular".
  - `subM`/`subA` override the two period labels; `lbl` overrides the "FEATURES" label (ClubHouse/Hosting use "INCLUDED").

- [ ] **Step 1: Create `templates/parts/plan-card.php`**

```php
<?php
/**
 * One plan card (`.plan-card`).
 *
 * Extracted from plan-cards.php so a page that shows a single plan beside a
 * supporting panel (ClubHouse, Hosting) renders the same card as the
 * three-up `.plans` grid, rather than a hand-copied lookalike.
 *
 * $vars:
 * - plan (array, required) array(
 *     name, desc, priceM (int), priceA (int), feat (bool), pop (bool|string),
 *     features (string[]),
 *     buyM, buyA   (string, optional) SureCart checkout links per interval.
 *     currency     (string, optional) 'GBP' renders "£" and marks the amount
 *                  with data-bw-gbp so public-widgets.js can convert it.
 *                  Absent: "$" and no conversion (the Toolbox plans).
 *     subM, subA   (string, optional) Period labels. Defaults: "per month" /
 *                  "per month, billed yearly".
 *     lbl          (string, optional) Feature-list label. Default "FEATURES".
 *   ).
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_pc_plan = isset( $plan ) && is_array( $plan ) ? $plan : array();

if ( empty( $blueworx_pc_plan['name'] ) ) {
	return;
}

$blueworx_pc_feat   = ! empty( $blueworx_pc_plan['feat'] );
$blueworx_pc_pop    = isset( $blueworx_pc_plan['pop'] ) ? $blueworx_pc_plan['pop'] : false;
$blueworx_pc_gbp    = isset( $blueworx_pc_plan['currency'] ) && 'GBP' === $blueworx_pc_plan['currency'];
$blueworx_pc_symbol = $blueworx_pc_gbp ? '£' : '$';
$blueworx_pc_btn    = $blueworx_pc_feat ? 'plan-btn dark' : 'plan-btn out';
$blueworx_pc_sub_m  = isset( $blueworx_pc_plan['subM'] ) ? (string) $blueworx_pc_plan['subM'] : __( 'per month', 'bluegroup-project-blueworx' );
$blueworx_pc_sub_a  = isset( $blueworx_pc_plan['subA'] ) ? (string) $blueworx_pc_plan['subA'] : __( 'per month, billed yearly', 'bluegroup-project-blueworx' );
$blueworx_pc_lbl    = isset( $blueworx_pc_plan['lbl'] ) ? (string) $blueworx_pc_plan['lbl'] : __( 'FEATURES', 'bluegroup-project-blueworx' );
$blueworx_pc_buy_m  = isset( $blueworx_pc_plan['buyM'] ) ? (string) $blueworx_pc_plan['buyM'] : '';
$blueworx_pc_buy_a  = isset( $blueworx_pc_plan['buyA'] ) ? (string) $blueworx_pc_plan['buyA'] : '';
$blueworx_pc_href   = '' !== $blueworx_pc_buy_m ? $blueworx_pc_buy_m : home_url( '/contact' );

// The feature check glyph, ported verbatim from components/Plans.tsx.
$blueworx_pc_check = '<svg class="ck" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm-1 14.4l-4.2-4.2 1.5-1.5 2.7 2.7 5-5 1.5 1.5z"/></svg>';
?>
<div class="<?php echo $blueworx_pc_feat ? 'plan-card feat' : 'plan-card'; ?>">
	<div class="plan-top">
		<div class="plan-name">
			<span><?php echo esc_html( $blueworx_pc_plan['name'] ); ?></span>
			<?php if ( $blueworx_pc_pop ) : ?>
				<span class="pop"><?php echo esc_html( is_string( $blueworx_pc_pop ) ? $blueworx_pc_pop : __( 'Popular', 'bluegroup-project-blueworx' ) ); ?></span>
			<?php endif; ?>
		</div>
		<div class="plan-desc"><?php echo esc_html( $blueworx_pc_plan['desc'] ); ?></div>
		<div class="plan-price"<?php echo $blueworx_pc_gbp ? ' data-cur="GBP"' : ''; ?> data-price-m="<?php echo esc_attr( (string) $blueworx_pc_plan['priceM'] ); ?>" data-price-a="<?php echo esc_attr( (string) $blueworx_pc_plan['priceA'] ); ?>">
			<b<?php echo $blueworx_pc_gbp ? ' data-bw-gbp="' . esc_attr( (string) $blueworx_pc_plan['priceM'] ) . '"' : ''; ?>><?php echo esc_html( $blueworx_pc_symbol . number_format( (float) $blueworx_pc_plan['priceM'], 0, '.', ',' ) ); ?></b>
			<em data-sub-m="<?php echo esc_attr( $blueworx_pc_sub_m ); ?>" data-sub-a="<?php echo esc_attr( $blueworx_pc_sub_a ); ?>"><?php echo esc_html( $blueworx_pc_sub_m ); ?></em>
		</div>
		<a href="<?php echo esc_url( $blueworx_pc_href ); ?>"
			<?php if ( '' !== $blueworx_pc_buy_m ) : ?>
				data-buy-m="<?php echo esc_url( $blueworx_pc_buy_m ); ?>"
			<?php endif; ?>
			<?php if ( '' !== $blueworx_pc_buy_a ) : ?>
				data-buy-a="<?php echo esc_url( $blueworx_pc_buy_a ); ?>"
			<?php endif; ?>
			class="<?php echo esc_attr( $blueworx_pc_btn ); ?>" style="display:flex;align-items:center;justify-content:center;text-decoration:none"><?php esc_html_e( 'Get started', 'bluegroup-project-blueworx' ); ?></a>
	</div>
	<div class="plan-feats">
		<div class="lbl"><?php echo esc_html( $blueworx_pc_lbl ); ?></div>
		<?php foreach ( (array) $blueworx_pc_plan['features'] as $blueworx_pc_feature ) : ?>
			<div class="pf">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted check glyph.
				echo $blueworx_pc_check;
				?>
				<?php echo esc_html( $blueworx_pc_feature ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
```

- [ ] **Step 2: Make `plan-cards.php` loop the new part**

Replace everything from `$blueworx_pc_plans = ...` to the end of `templates/parts/plan-cards.php` with:

```php
$blueworx_pcs_plans = isset( $plans ) && is_array( $plans ) ? $plans : array();
?>
<div class="plan-cards-wrap" style="margin:-190px var(--gut) 0;position:relative;z-index:3">
	<div class="plans">
		<?php foreach ( $blueworx_pcs_plans as $blueworx_pcs_plan ) : ?>
			<?php blueworx_public_part( 'parts/plan-card.php', array( 'plan' => $blueworx_pcs_plan ) ); ?>
		<?php endforeach; ?>
	</div>
</div>
```

and update the file's doc comment: the card markup now lives in `plan-card.php`; this part only positions the grid.

- [ ] **Step 3: Verify the Toolbox page is unchanged**

Run: `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8882 npx playwright test tests/marketing-plans.spec.js -g "toolbox"`
Expected: PASS (three `$` cards, one featured, `data-price-m/a` present).

- [ ] **Step 4: Commit**

```bash
git add templates/parts/plan-card.php templates/parts/plan-cards.php
git commit -m "Extract the single plan card so one-plan pages can reuse it, with GBP support"
```

---

### Task 4: Currency switcher (nav JS) and price painter (widgets JS)

**Files:**
- Modify: `assets/js/public-nav.js` (add `initCurrencySwitcher`, call it from `ready`)
- Modify: `assets/js/public-widgets.js` (add `initCurrencyPrices`, make `initBillingToggle` currency-aware)
- Test: `tests/currency.spec.js` (new; passes fully after Task 5 renders the switcher and Task 8's pages carry prices — write it now, run it at the end of Task 8)

**Interfaces:**
- Consumes: markup from Task 5 (`.bw-cur`, `.bw-cur-btn`, `[data-cur-label]`, `.bw-cur-menu button[data-cur]`).
- Produces:
  - `window.dispatchEvent(new CustomEvent('bw:currency', { detail: 'GBP'|'EUR'|'USD' }))` from the nav.
  - Global painter contract: any element with `data-bw-gbp="<number>"` gets its text replaced by `symbol + amount` in the chosen currency. Optional `data-bw-dp="2"` (decimals, default 0), `data-bw-suffix=" / hr"` (appended verbatim), `data-bw-prefix="from "`.
  - `window.blueworxMoney(gbp, dp)` → string, exposed for the Support calculator (Task 8).

- [ ] **Step 1: Write the failing spec `tests/currency.spec.js`**

```js
// The site-wide currency switcher (design pass, 2026-09). GBP is the base;
// EUR and USD are fixed rates. Only elements marked data-bw-gbp convert, so
// the Toolbox's dollar prices are untouched.
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

test.describe('Currency switcher', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('defaults to GBP and lists three currencies', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    await expect(page.locator('nav .bw-cur-btn [data-cur-label]')).toHaveText('£ GBP');
    await page.locator('nav .bw-cur-btn').click();
    await expect(page.locator('nav .bw-cur-menu button')).toHaveCount(3);
    await expect(page.locator('nav .bw-cur-menu button.on')).toHaveAttribute('data-cur', 'GBP');
  });

  test('switching to USD converts marked prices at ×1.27 and persists across pages', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('£20');
    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="USD"]').click();
    await expect(page.locator('nav .bw-cur-btn [data-cur-label]')).toHaveText('$ USD');
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('$25');
    await page.locator('#hosting-plans .bill-toggle button').nth(1).click();
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('$254');

    await page.goto(cacheBust('/support/'));
    await expect(page.locator('nav .bw-cur-btn [data-cur-label]')).toHaveText('$ USD');
    await expect(page.locator('.plans .plan-card.feat .plan-price b')).toHaveText('$635');
  });

  test('EUR uses ×1.17 and the effective rate keeps two decimals', async ({ page }) => {
    await page.goto(cacheBust('/support/'));
    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="EUR"]').click();
    // Growth: £500 × 1.17 = €585; rate (500×12)/120 = £50.00 → €58.50
    await expect(page.locator('.plans .plan-card.feat .plan-price b')).toHaveText('€585');
    await expect(page.locator('[data-testid="support-calc-rate"]')).toHaveText('€58.50 / hr');
  });

  test('the Toolbox dollar prices are not converted', async ({ page }) => {
    await page.goto(cacheBust('/toolbox/'));
    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="EUR"]').click();
    const first = await page.locator('.plans .plan-price b').first().textContent();
    expect(first.startsWith('$')).toBe(true);
  });
});
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8882 npx playwright test tests/currency.spec.js`
Expected: FAIL (no `.bw-cur-btn`, no `/hosting/` page yet).

- [ ] **Step 3: Add the switcher to `assets/js/public-nav.js`**

Add this function after `initMobileMenu()` and call `initCurrencySwitcher();` inside the file's `ready( function () { ... } )` bootstrap (find the existing `ready(` call at the bottom; add the call alongside the other `init*` calls, before `initScroll`):

```js
	/**
	 * The site-wide currency switcher.
	 *
	 * Currency is a visitor preference, not page state: it is stored once in
	 * localStorage and announced as a "bw:currency" event so any page holding
	 * prices (public-widgets.js) can repaint. The nav and the mobile menu each
	 * carry a copy of the control; both are kept in step.
	 */
	function initCurrencySwitcher() {
		var drops = document.querySelectorAll( '.bw-cur' );
		if ( ! drops.length ) {
			return;
		}

		var LABEL = { GBP: '£ GBP', EUR: '€ EUR', USD: '$ USD' };
		var current = 'GBP';

		try {
			current = localStorage.getItem( 'bw-currency' ) || 'GBP';
		} catch ( e ) {
			current = 'GBP';
		}
		if ( ! LABEL[ current ] ) {
			current = 'GBP';
		}

		function paint( code ) {
			var labels = document.querySelectorAll( '[data-cur-label]' );
			for ( var i = 0; i < labels.length; i++ ) {
				labels[ i ].textContent = LABEL[ code ];
			}
			var options = document.querySelectorAll( '.bw-cur-menu button' );
			for ( var j = 0; j < options.length; j++ ) {
				var on = options[ j ].getAttribute( 'data-cur' ) === code;
				options[ j ].classList.toggle( 'on', on );
				options[ j ].setAttribute( 'aria-selected', on ? 'true' : 'false' );
			}
		}

		function closeAll() {
			for ( var i = 0; i < drops.length; i++ ) {
				drops[ i ].classList.remove( 'open' );
				var btn = drops[ i ].querySelector( '.bw-cur-btn' );
				if ( btn ) {
					btn.setAttribute( 'aria-expanded', 'false' );
				}
			}
		}

		paint( current );

		Array.prototype.forEach.call( drops, function ( drop ) {
			var toggle = drop.querySelector( '.bw-cur-btn' );
			if ( ! toggle ) {
				return;
			}

			toggle.addEventListener( 'click', function ( event ) {
				event.stopPropagation();
				var willOpen = ! drop.classList.contains( 'open' );
				closeAll();
				if ( willOpen ) {
					drop.classList.add( 'open' );
					toggle.setAttribute( 'aria-expanded', 'true' );
				}
			} );

			var options = drop.querySelectorAll( '.bw-cur-menu button' );
			Array.prototype.forEach.call( options, function ( option ) {
				option.addEventListener( 'click', function ( event ) {
					event.stopPropagation();
					var code = option.getAttribute( 'data-cur' );
					if ( ! LABEL[ code ] ) {
						return;
					}
					try {
						localStorage.setItem( 'bw-currency', code );
					} catch ( e ) {
						// Private mode: the choice still applies to this page.
					}
					paint( code );
					closeAll();
					window.dispatchEvent( new CustomEvent( 'bw:currency', { detail: code } ) );
				} );
			} );
		} );

		document.addEventListener( 'click', closeAll );
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				closeAll();
			}
		} );
	}
```

- [ ] **Step 4: Add the price painter to `assets/js/public-widgets.js`**

Add before `initBillingToggle()`:

```js
	// GBP is the base; the other two are fixed rates agreed for the site.
	var CURRENCIES = {
		GBP: { symbol: '£', rate: 1 },
		EUR: { symbol: '€', rate: 1.17 },
		USD: { symbol: '$', rate: 1.27 }
	};

	function currentCurrency() {
		var code = 'GBP';
		try {
			code = localStorage.getItem( 'bw-currency' ) || 'GBP';
		} catch ( e ) {
			code = 'GBP';
		}
		return CURRENCIES[ code ] ? code : 'GBP';
	}

	/**
	 * Formats a GBP amount in the visitor's chosen currency.
	 *
	 * @param {number} gbp Amount in pounds.
	 * @param {number} dp  Decimal places (0 or 2).
	 * @return {string} e.g. "£20", "€58.50".
	 */
	function money( gbp, dp ) {
		var cur = CURRENCIES[ currentCurrency() ];
		var value = Number( gbp ) * cur.rate;
		if ( dp ) {
			return cur.symbol + value.toFixed( dp );
		}
		return cur.symbol + Math.round( value ).toLocaleString( 'en-GB' );
	}
	window.blueworxMoney = money;

	/**
	 * Repaints every element carrying a base GBP amount.
	 *
	 * Templates render the pound figure, so the page is correct with JS off
	 * and only ever changes when the visitor picks another currency.
	 */
	function paintPrices() {
		var els = document.querySelectorAll( '[data-bw-gbp]' );
		for ( var i = 0; i < els.length; i++ ) {
			var el = els[ i ];
			var dp = parseInt( el.getAttribute( 'data-bw-dp' ) || '0', 10 );
			el.textContent = ( el.getAttribute( 'data-bw-prefix' ) || '' )
				+ money( el.getAttribute( 'data-bw-gbp' ), dp )
				+ ( el.getAttribute( 'data-bw-suffix' ) || '' );
		}
	}

	function initCurrencyPrices() {
		if ( ! document.querySelector( '[data-bw-gbp]' ) ) {
			return;
		}
		paintPrices();
		window.addEventListener( 'bw:currency', paintPrices );
	}
```

Then in `initBillingToggle()`'s `apply()` replace the `if ( b ) { b.textContent = '$' + ... }` block with:

```js
				if ( b ) {
					var amount = annual ? prices[ i ].getAttribute( 'data-price-a' ) : prices[ i ].getAttribute( 'data-price-m' );
					if ( b.hasAttribute( 'data-bw-gbp' ) ) {
						// A pound price: store the new base and let the painter
						// render it in whatever currency is selected.
						b.setAttribute( 'data-bw-gbp', amount );
						b.textContent = money( amount, 0 );
					} else {
						b.textContent = '$' + amount;
					}
				}
```

And in `init()` add `initCurrencyPrices();` as the first call.

- [ ] **Step 5: Lint the two scripts**

Run: `npx eslint assets/js/public-nav.js assets/js/public-widgets.js`
Expected: no errors (warnings are reported to the user at the end, not fixed now).

- [ ] **Step 6: Commit**

```bash
git add assets/js/public-nav.js assets/js/public-widgets.js tests/currency.spec.js
git commit -m "Add the currency switcher and the GBP price painter"
```

---

### Task 5: Nav and footer restructure

**Files:**
- Modify: `templates/parts/nav.php` (rewrite the markup section; keep the PHP preamble and `blueworx_public_nav_active_class()`)
- Modify: `templates/parts/footer.php`
- Create: `tests/nav-structure.spec.js`
- Modify: `tests/images.spec.js:85-100` (mega-panel assertion), `tests/toolbox-tool-pages.spec.js:54-86` (mega-panel + mobile tool links tests), `tests/nav-client-login.spec.js` (check it still passes)

**Interfaces:**
- Produces: `blueworx_public_part( 'parts/footer.php', array( 'cta_title' => ..., 'cta_copy' => ..., 'cta_primary' => array( 'label', 'href' ), 'cta_secondary' => array( 'label', 'href' ) ) )` — all optional; defaults are the current copy with the primary button going to `/contact`.

- [ ] **Step 1: Write the failing spec `tests/nav-structure.spec.js`**

```js
// The nav after the 2026-09 restructure: six links, Contact as the button,
// About and Journal only in the footer, no Toolbox mega panel.
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

const LINKS = ['Home', 'ClubHouse', 'Hosting', 'Support', 'Work', 'AI Powered'];

test.describe('Site nav structure', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('lists the six pages in order and nothing else', async ({ page }) => {
    await page.goto(cacheBust('/'));
    const links = page.locator('nav .nav-links > a');
    await expect(links).toHaveCount(6);
    for (let i = 0; i < LINKS.length; i++) {
      await expect(links.nth(i)).toContainText(LINKS[i]);
    }
    await expect(page.locator('nav .mega-panel')).toHaveCount(0);
    await expect(page.locator('nav .about-panel')).toHaveCount(0);
    await expect(page.locator('nav .nav-links a', { hasText: 'Toolbox' })).toHaveCount(0);
  });

  test('the right cluster is Client Login, a Contact button and the currency switcher', async ({ page }) => {
    await page.goto(cacheBust('/'));
    await expect(page.locator('nav .nav-cta .nav-sign-in')).toHaveText('Client Login');
    const btn = page.locator('nav .nav-cta .nav-btn');
    await expect(btn).toContainText('Contact');
    await expect(btn).toHaveAttribute('href', /\/contact\/?$/);
    await expect(page.locator('nav .nav-cta .bw-cur')).toHaveCount(1);
  });

  test('each product page marks its own link active', async ({ page }) => {
    for (const [path, label] of [['/clubhouse/', 'ClubHouse'], ['/hosting/', 'Hosting'], ['/support/', 'Support']]) {
      await page.goto(cacheBust(path));
      await expect(page.locator('nav .nav-links a.active')).toHaveText(new RegExp(label));
    }
  });

  test('the mobile menu mirrors the nav and carries the switcher', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(cacheBust('/'));
    await page.locator('nav .hamburger').click();
    const menu = page.locator('.mobile-menu');
    await expect(menu).toHaveClass(/open/);
    for (const label of LINKS) {
      await expect(menu.locator('a', { hasText: label })).toHaveCount(1);
    }
    await expect(menu.locator('a.btn', { hasText: 'Contact' })).toHaveCount(1);
    await expect(menu.locator('.bw-cur')).toHaveCount(1);
    await expect(menu.locator('a[href*="/toolbox/"]')).toHaveCount(0);
  });

  test('the footer holds About, Journal, Contact and Client Login', async ({ page }) => {
    await page.goto(cacheBust('/'));
    const cols = page.locator('footer .fcol');
    await expect(cols).toHaveCount(2);
    for (const label of LINKS) {
      await expect(cols.nth(0).locator('a', { hasText: label.replace('Support', 'Integrated Support') })).toHaveCount(1);
    }
    await expect(cols.nth(0).locator('a', { hasText: 'Toolbox' })).toHaveCount(0);
    for (const label of ['About Us', 'Journal', 'Contact', 'Client Login']) {
      await expect(cols.nth(1).locator('a', { hasText: label })).toHaveCount(1);
    }
  });
});
```

- [ ] **Step 2: Run it to confirm it fails**

Run: `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8882 npx playwright test tests/nav-structure.spec.js`
Expected: FAIL on link count / mega panel present.

- [ ] **Step 3: Rewrite the markup half of `templates/parts/nav.php`**

Delete the `$blueworx_nav_tools = blueworx_content_tools();` line and its comment block (the tools registry is no longer read by the nav). Replace everything from `<nav>` to the end of the file with:

```php
<?php
// One list, rendered twice (desktop row and mobile panel), so the two can
// never disagree about what the site's pages are.
$blueworx_nav_items = array(
	array( '/', __( 'Home', 'bluegroup-project-blueworx' ), home_url( '/' ) ),
	array( '/clubhouse', __( 'ClubHouse', 'bluegroup-project-blueworx' ), home_url( '/clubhouse' ) ),
	array( '/hosting', __( 'Hosting', 'bluegroup-project-blueworx' ), home_url( '/hosting' ) ),
	array( '/support', __( 'Support', 'bluegroup-project-blueworx' ), home_url( '/support' ) ),
	array( '/work', __( 'Work', 'bluegroup-project-blueworx' ), home_url( '/work' ) ),
);

// The currency switcher, rendered in the desktop cluster and again in the
// mobile panel. Both copies are wired by public-nav.js.
$blueworx_nav_currency = '
	<div class="bw-cur">
		<button type="button" class="bw-cur-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="' . esc_attr__( 'Currency', 'bluegroup-project-blueworx' ) . '">
			<span data-cur-label>£ GBP</span>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
		</button>
		<div class="bw-cur-menu" role="listbox">
			<button type="button" role="option" data-cur="GBP">£ GBP<i>' . esc_html__( 'Pound', 'bluegroup-project-blueworx' ) . '</i></button>
			<button type="button" role="option" data-cur="EUR">€ EUR<i>' . esc_html__( 'Euro', 'bluegroup-project-blueworx' ) . '</i></button>
			<button type="button" role="option" data-cur="USD">$ USD<i>' . esc_html__( 'Dollar', 'bluegroup-project-blueworx' ) . '</i></button>
		</div>
	</div>';
?>
<nav>
	<a class="nav-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php if ( file_exists( $blueworx_nav_logo_path ) ) : ?>
			<?php blueworx_public_image( 'img/logo.png', __( 'BlueWorx', 'bluegroup-project-blueworx' ), array( 'above_fold' => true ) ); ?>
		<?php else : ?>
			<span class="bw-nav-logo-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		<?php endif; ?>
	</a>
	<div class="nav-links">
		<?php foreach ( $blueworx_nav_items as $blueworx_nav_item ) : ?>
			<a class="<?php echo esc_attr( blueworx_public_nav_active_class( $blueworx_nav_item[0], $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( $blueworx_nav_item[2] ); ?>"><?php echo esc_html( $blueworx_nav_item[1] ); ?></a>
		<?php endforeach; ?>
		<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/ai', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/ai' ) ); ?>" style="gap:7px"><?php echo esc_html__( 'AI Powered', 'bluegroup-project-blueworx' ); ?><span class="nav-tag tag-light"><?php echo esc_html__( 'New', 'bluegroup-project-blueworx' ); ?></span></a>
	</div>
	<div class="nav-cta">
		<a class="nav-sign-in" href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
		<a class="nav-btn" href="<?php echo esc_url( home_url( '/contact' ) ); ?>">
			<?php echo esc_html__( 'Contact', 'bluegroup-project-blueworx' ); ?>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="7" y1="17" x2="17" y2="7" /><polyline points="7 7 17 7 17 17" /></svg>
		</a>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr__()/esc_html__() calls and static markup.
		echo $blueworx_nav_currency;
		?>
	</div>
	<a class="nav-sign-in-mobile" href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
	<button class="hamburger" aria-label="<?php echo esc_attr__( 'Toggle menu', 'bluegroup-project-blueworx' ); ?>" aria-expanded="false">
		<span></span>
		<span></span>
	</button>
</nav>
<div class="mobile-menu">
	<?php foreach ( $blueworx_nav_items as $blueworx_nav_item ) : ?>
		<a class="<?php echo esc_attr( blueworx_public_nav_active_class( $blueworx_nav_item[0], $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( $blueworx_nav_item[2] ); ?>"><?php echo esc_html( $blueworx_nav_item[1] ); ?></a>
	<?php endforeach; ?>
	<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/ai', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/ai' ) ); ?>"><?php echo esc_html__( 'AI Powered', 'bluegroup-project-blueworx' ); ?><span class="nav-tag tag-light"><?php echo esc_html__( 'New', 'bluegroup-project-blueworx' ); ?></span></a>
	<a href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
	<a class="btn btn-brand btn-md" href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php echo esc_html__( 'Contact', 'bluegroup-project-blueworx' ); ?></a>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- see above.
	echo $blueworx_nav_currency;
	?>
</div>
```

Update the file's doc comment: the mega panel and About dropdown are gone (2026-09 restructure); About and Journal live in the footer; the nav no longer reads the tools registry.

- [ ] **Step 4: Rewrite `templates/parts/footer.php`**

Add the `$vars` to the doc comment, then replace from `$blueworx_footer_logo_path = ...` to the end with:

```php
$blueworx_footer_logo_path = BLUEWORX_SITE_PATH . 'assets/img/logo.png';

// The CTA band's copy is the page's, not the footer's: each product page ends
// on its own ask. Defaults are the agency-wide ones.
$blueworx_footer_cta_title     = isset( $cta_title ) ? (string) $cta_title : __( 'Ready to Build a Digital Solution That Wins?', 'bluegroup-project-blueworx' );
$blueworx_footer_cta_copy      = isset( $cta_copy ) ? (string) $cta_copy : __( "Book a free strategy call. We'll review your current setup and show you exactly where the opportunities are.", 'bluegroup-project-blueworx' );
$blueworx_footer_cta_primary   = isset( $cta_primary ) && is_array( $cta_primary ) ? $cta_primary : array(
	'label' => __( 'Get a Quote', 'bluegroup-project-blueworx' ),
	'href'  => home_url( '/contact' ),
);
$blueworx_footer_cta_secondary = isset( $cta_secondary ) && is_array( $cta_secondary ) ? $cta_secondary : array(
	'label' => __( 'Book a Call', 'bluegroup-project-blueworx' ),
	'href'  => home_url( '/contact' ),
);
$blueworx_footer_cta_primary_attrs   = ! empty( $blueworx_footer_cta_primary['external'] ) ? ' target="_blank" rel="noopener"' : '';
$blueworx_footer_cta_secondary_attrs = ! empty( $blueworx_footer_cta_secondary['external'] ) ? ' target="_blank" rel="noopener"' : '';
?>
<div class="cta-soft">
	<div class="cta-inner">
		<?php
		blueworx_blob( 'width:220px;height:220px;bottom:-80px;left:-40px;opacity:.4' );
		blueworx_blob( 'width:180px;height:180px;top:-60px;right:-20px;opacity:.35' );
		?>
		<h2 class="h2"><?php echo esc_html( $blueworx_footer_cta_title ); ?></h2>
		<p><?php echo esc_html( $blueworx_footer_cta_copy ); ?></p>
		<div class="cta-actions">
			<a href="<?php echo esc_url( $blueworx_footer_cta_primary['href'] ); ?>" class="btn btn-brand btn-md"<?php echo $blueworx_footer_cta_primary_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute string. ?>><?php echo esc_html( $blueworx_footer_cta_primary['label'] ); ?></a>
			<a href="<?php echo esc_url( $blueworx_footer_cta_secondary['href'] ); ?>" class="btn btn-outline-w btn-md"<?php echo $blueworx_footer_cta_secondary_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute string. ?>><?php echo esc_html( $blueworx_footer_cta_secondary['label'] ); ?></a>
		</div>
	</div>
</div>
<footer>
	<div class="ft">
		<div class="fb">
			<?php if ( file_exists( $blueworx_footer_logo_path ) ) : ?>
				<?php
				blueworx_public_image(
					'img/logo.png',
					__( 'BlueWorx', 'bluegroup-project-blueworx' ),
					array( 'style' => 'filter:brightness(0) invert(1)' )
				);
				?>
			<?php else : ?>
				<span class="bw-footer-logo-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<?php endif; ?>
			<p><?php echo esc_html__( 'BlueWorx supports growing businesses worldwide with premium tools, hosting, and expert support.', 'bluegroup-project-blueworx' ); ?></p>
		</div>
		<div class="fcol">
			<h3><?php echo esc_html__( 'Pages', 'bluegroup-project-blueworx' ); ?></h3>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( 'Home', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/clubhouse' ) ); ?>"><?php echo esc_html__( 'ClubHouse', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/hosting' ) ); ?>"><?php echo esc_html__( 'Hosting', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/support' ) ); ?>"><?php echo esc_html__( 'Integrated Support', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/work' ) ); ?>"><?php echo esc_html__( 'Work', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/ai' ) ); ?>"><?php echo esc_html__( 'AI Powered', 'bluegroup-project-blueworx' ); ?></a>
		</div>
		<div class="fcol">
			<h3><?php echo esc_html__( 'About', 'bluegroup-project-blueworx' ); ?></h3>
			<a href="<?php echo esc_url( home_url( '/about' ) ); ?>"><?php echo esc_html__( 'About Us', 'bluegroup-project-blueworx' ); ?></a>
			<?php // Resolved through the page map rather than home_url('/blog'), so the link survives a rename (#94). ?>
			<a href="<?php echo esc_url( blueworx_public_journal_url() ); ?>"><?php echo esc_html__( 'Journal', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php echo esc_html__( 'Contact', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
		</div>
	</div>
	<div class="fbot">
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: current year. */
					__( '© %s BlueWorx. All rights reserved.', 'bluegroup-project-blueworx' ),
					gmdate( 'Y' )
				)
			);
			?>
		</p>
		<p><?php echo esc_html__( 'Powered by BabyBlue Digital.', 'bluegroup-project-blueworx' ); ?></p>
	</div>
</footer>
```

(The old `$blueworx_footer_logo_url` variable was unused; drop it. The `external => true` flag on a CTA button is used by ClubHouse for the demo link.)

- [ ] **Step 5: Update the tests that asserted on the mega panel**

- `tests/images.spec.js` ~lines 85–100: delete the test that checks `.mega-panel img` lazy-loading (there is no mega panel).
- `tests/toolbox-tool-pages.spec.js`: delete the two tests "every tool link in the desktop mega panel resolves" and the mobile-menu `/toolbox/` one (lines ~54–86). Keep "every tool link on the Toolbox page resolves".
- Run `tests/nav-client-login.spec.js`; it should pass unchanged (the `.nav-sign-in` link is still there).

- [ ] **Step 6: Run the nav spec (the product-page cases still fail until Task 8)**

Run: `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8882 npx playwright test tests/nav-structure.spec.js tests/nav-client-login.spec.js tests/images.spec.js tests/toolbox-tool-pages.spec.js`
Expected: everything passes except `nav-structure` "each product page marks its own link active" (404s until Task 8).

- [ ] **Step 7: Commit**

```bash
git add templates/parts/nav.php templates/parts/footer.php tests/nav-structure.spec.js tests/images.spec.js tests/toolbox-tool-pages.spec.js
git commit -m "Restructure the nav and footer: ClubHouse, Hosting, Support; Contact as the button; currency switcher"
```

---

### Task 6: Content data for the three products

**Files:**
- Modify: `includes/public/content.php` — replace `blueworx_content_retainer_plans()` and `blueworx_content_faqs()`; add `blueworx_content_support_packages()`, `blueworx_content_hosting()`, `blueworx_content_clubhouse()`
- Modify: `includes/admin/settings.php:218-260` and `:285-310` — iterate `blueworx_commerce_sellable_plans()`
- Modify: `includes/public/commerce.php` — add `blueworx_commerce_sellable_plans()`; apply live prices to hosting and clubhouse too
- Test: `tests/public-content.spec.js` (hermetic PHP harness — no browser needed)

**Interfaces:**
- Produces:
  - `blueworx_content_support_packages()` → list of 9 `array( name, hours (int, annual), priceM (int GBP), priceA (int, = priceM), blurb, feat (bool), pop (bool), featured (bool), features (string[5]), currency => 'GBP', desc (= blurb) )`. Filter `blueworx_content_retainer_plans` is applied (so SureCart wiring keeps working).
  - `blueworx_content_retainer_plans()` → alias of the above (kept because commerce/admin/tests reference it).
  - `blueworx_content_support_faqs()` → 5 `array( q, a )`.
  - `blueworx_content_faqs()` → now returns the Hosting FAQ (5) — the pricing FAQ it held is retired with the page. Rename callers: none remain after Task 9 deletes pricing.php.
  - `blueworx_content_hosting()` → `array( plan => plan array (name 'Managed Hosting', priceM 20, priceA 200, currency 'GBP', pop 'Per site', feat true, subA 'per year, billed annually', lbl 'INCLUDED', features[8]), perf[6] => (stat, name, desc), security[6] => (name, desc), compare[8] => (label, a, b, c), faqs[5] )`.
  - `blueworx_content_clubhouse()` → `array( plan => (name 'ClubHouse', priceM 20, priceA 200, currency 'GBP', pop true, feat true, subA, lbl 'INCLUDED', features[7]), modules[9] => (name, desc, paths (string[] of SVG path d)), self_serve[4] => (tag, name, desc), audiences[4] => (name, desc), faqs[5] )`.
  - `blueworx_commerce_sellable_plans()` → packages + hosting plan + clubhouse plan, for the admin price-ID field.

- [ ] **Step 1: Write the failing tests in `tests/public-content.spec.js`**

Replace the test named `'blueworx_content_toolbox_plans() and blueworx_content_retainer_plans() each return 3 plans, with no btn field'` with:

```js
  test('blueworx_content_toolbox_plans() returns 3 plans with no btn field', () => {
    const plans = runContentPhp('echo json_encode( blueworx_content_toolbox_plans() );');
    expect(plans).toHaveLength(3);
    for (const plan of plans) {
      expect(plan).not.toHaveProperty('btn');
      expect(plan.features.length).toBeGreaterThan(0);
    }
  });

  test('blueworx_content_support_packages() returns the nine GBP packages in order', () => {
    const packages = runContentPhp('echo json_encode( blueworx_content_support_packages() );');
    expect(packages.map((p) => [p.name, p.hours, p.priceM])).toEqual([
      ['Starter', 24, 100],
      ['Launch', 48, 200],
      ['Scale', 72, 300],
      ['Enhance', 96, 400],
      ['Growth', 120, 500],
      ['Enterprise', 240, 750],
      ['Enterprise +', 360, 1000],
      ['Advantage', 480, 1250],
      ['Advantage +', 600, 1500],
    ]);
    for (const p of packages) {
      expect(p.currency).toBe('GBP');
      expect(p.priceA).toBe(p.priceM);
      expect(p.features).toHaveLength(5);
    }
    expect(packages.filter((p) => p.featured).map((p) => p.name)).toEqual(['Starter', 'Growth', 'Enterprise +']);
    expect(packages.filter((p) => p.feat).map((p) => p.name)).toEqual(['Growth']);
    // retainer_plans is the same list under its historical name.
    const retainer = runContentPhp('echo json_encode( blueworx_content_retainer_plans() );');
    expect(retainer).toEqual(packages);
  });

  test('hosting and clubhouse each carry one £20/£200 plan and their section data', () => {
    const hosting = runContentPhp('echo json_encode( blueworx_content_hosting() );');
    expect(hosting.plan).toMatchObject({ name: 'Managed Hosting', priceM: 20, priceA: 200, currency: 'GBP', feat: true, pop: 'Per site' });
    expect(hosting.plan.features).toHaveLength(8);
    expect(hosting.perf).toHaveLength(6);
    expect(hosting.security).toHaveLength(6);
    expect(hosting.compare).toHaveLength(8);
    expect(hosting.faqs).toHaveLength(5);

    const clubhouse = runContentPhp('echo json_encode( blueworx_content_clubhouse() );');
    expect(clubhouse.plan).toMatchObject({ name: 'ClubHouse', priceM: 20, priceA: 200, currency: 'GBP', feat: true, pop: true });
    expect(clubhouse.plan.features).toHaveLength(7);
    expect(clubhouse.modules).toHaveLength(9);
    expect(clubhouse.modules.every((m) => Array.isArray(m.paths) && m.paths.length > 0)).toBe(true);
    expect(clubhouse.self_serve).toHaveLength(4);
    expect(clubhouse.audiences).toHaveLength(4);
    expect(clubhouse.faqs).toHaveLength(5);
  });

  test('the support FAQ has five entries', () => {
    expect(runContentPhp('echo json_encode( blueworx_content_support_faqs() );')).toHaveLength(5);
  });
```

Also update the `'every accessor is filterable'`-style list near line 150 (the array of function names) to include `blueworx_content_support_packages`, `blueworx_content_support_faqs`, `blueworx_content_hosting`, `blueworx_content_clubhouse`. Note: the harness stubs `apply_filters`; `sprintf` and `number_format` are plain PHP and fine.

- [ ] **Step 2: Run to confirm failure**

Run: `npx playwright test tests/public-content.spec.js`
Expected: FAIL — undefined functions.

- [ ] **Step 3: Replace the retainer plans in `includes/public/content.php`**

Replace the whole `blueworx_content_retainer_plans()` function (from its doc comment to the closing `}`) with:

```php
/**
 * The nine Integrated Support packages.
 *
 * Hours are the ANNUAL allowance; the price is what the client pays every
 * month, in GBP. priceA equals priceM — packages have no annual-billing
 * discount — and is kept so the plan-card part and the SureCart wiring (which
 * key on priceM/priceA) work unchanged. `featured` marks the three shown as
 * cards on the Support page; `feat` (the highlighted card) is Growth.
 *
 * Runs through the `blueworx_content_retainer_plans` filter so SureCart price
 * IDs configured per package (includes/public/commerce.php) still apply.
 *
 * @return array List of plan arrays (see blueworx_content_support_package()).
 */
function blueworx_content_support_packages() {
	$rows = array(
		// name, annual hours, £/month, blurb, featured.
		array( 'Starter', 24, 100, 'Keeping a simple site current — small edits, updates and the odd fix.', true ),
		array( 'Launch', 48, 200, 'A day a month of design and development time for a growing site.', false ),
		array( 'Scale', 72, 300, 'Steady improvement work alongside the everyday maintenance.', false ),
		array( 'Enhance', 96, 400, 'Regular new pages, campaigns and feature work on top of upkeep.', false ),
		array( 'Growth', 120, 500, 'A consistent programme of design, build and optimisation each month.', true ),
		array( 'Enterprise', 240, 750, 'A standing slice of the team for multi-site or multi-brand operations.', false ),
		array( 'Enterprise +', 360, 1000, 'Larger roadmaps with parallel workstreams and priority turnaround.', true ),
		array( 'Advantage', 480, 1250, 'A near-full-time partner embedded in your product and marketing.', false ),
		array( 'Advantage +', 600, 1500, 'Our deepest engagement — the whole team, on call, all year.', false ),
	);

	// The three featured packages carry hand-written feature lists; the rest
	// derive theirs from the numbers so every card has five lines.
	$written = array(
		'Starter'      => array( 'Content and small design edits', 'Core, plugin and theme updates', 'Email support, 2 business days', 'Monthly summary of hours used' ),
		'Growth'       => array( 'Design and development time', 'New pages, campaigns and fixes', 'Priority response, 1 business day', 'Quarterly review and roadmap' ),
		'Enterprise +' => array( 'Parallel design and build workstreams', 'Multi-site and multi-brand cover', 'Same-day response on urgent work', 'Named lead and monthly reporting' ),
	);
	$generic = array( 'Design and development time', 'Fixes, content and small changes', 'Core, plugin and theme updates', 'Hours logged and reported monthly' );

	$plans = array();

	foreach ( $rows as $row ) {
		list( $name, $hours, $gbp, $blurb, $featured ) = $row;

		$per_month = $hours / 12;
		$first     = sprintf(
			/* translators: 1: hours a year, 2: hours a month. */
			__( '%1$d hours a year (%2$s a month)', 'bluegroup-project-blueworx' ),
			$hours,
			rtrim( rtrim( number_format( $per_month, 1, '.', '' ), '0' ), '.' )
		);
		$rest = isset( $written[ $name ] ) ? $written[ $name ] : $generic;

		$plans[] = array(
			'name'     => $name,
			'desc'     => $blurb,
			'blurb'    => $blurb,
			'hours'    => $hours,
			'priceM'   => $gbp,
			'priceA'   => $gbp,
			'currency' => 'GBP',
			'feat'     => 'Growth' === $name,
			'pop'      => 'Growth' === $name,
			'featured' => $featured,
			'features' => array_merge( array( $first ), $rest ),
		);
	}

	/**
	 * Filters the support packages. Historically named for the three retainer
	 * plans these replaced; the name is kept so configured SureCart hooks
	 * still fire.
	 *
	 * @param array $plans The 9 package arrays.
	 */
	return apply_filters( 'blueworx_content_retainer_plans', $plans );
}

/**
 * The support packages under the name the commerce and admin code use.
 *
 * @return array See blueworx_content_support_packages().
 */
function blueworx_content_retainer_plans() {
	return blueworx_content_support_packages();
}

/**
 * The Integrated Support FAQ.
 *
 * @return array List of array( q, a ).
 */
function blueworx_content_support_faqs() {
	$faqs = array(
		array(
			'q' => 'Do unused hours roll over?',
			'a' => 'Your allowance is annual, so a quiet month simply leaves more in the pot for a busy one. Hours do not carry past the end of your support year.',
		),
		array(
			'q' => 'What if we go over our hours?',
			'a' => 'We tell you before you get there, not after. You can either move up a package or approve extra hours at your package rate for that piece of work.',
		),
		array(
			'q' => 'How quickly do you respond?',
			'a' => 'Two business days on the smaller packages, one business day from Growth upwards, and same-day on Enterprise + and above. Anything that takes a site offline is treated as urgent on every package.',
		),
		array(
			'q' => 'Can we change package mid-year?',
			'a' => 'Yes. Move up at any time and the new allowance applies from that month. Moving down takes effect at your next renewal.',
		),
		array(
			'q' => 'Is hosting included?',
			'a' => 'Hosting is separate at £20 a month or £200 a year per site, so you only pay for it on the sites we host. Support packages work with sites hosted anywhere.',
		),
	);

	/**
	 * Filters the support FAQ list.
	 *
	 * @param array $faqs List of array( q, a ).
	 */
	return apply_filters( 'blueworx_content_support_faqs', $faqs );
}
```

Then **delete** `blueworx_content_faqs()` entirely (its only caller was pricing.php, which Task 9 removes). Update the `tests/public-content.spec.js` test `'blueworx_content_faqs() returns 5 question/answer pairs'` to call `blueworx_content_support_faqs` instead, and remove `blueworx_content_faqs` from the filterable-accessor list.

- [ ] **Step 4: Add the hosting and clubhouse accessors**

Append to `includes/public/content.php` before the reviews accessor:

```php
/**
 * Everything the Managed Hosting page says: the plan, the performance and
 * security cards, the comparison table and the FAQ.
 *
 * @return array array( plan, perf, security, compare, faqs ).
 */
function blueworx_content_hosting() {
	$data = array(
		'plan'     => array(
			'name'     => 'Managed Hosting',
			'desc'     => 'Everything one WordPress site needs to stay fast, safe and online.',
			'priceM'   => 20,
			'priceA'   => 200,
			'currency' => 'GBP',
			'feat'     => true,
			'pop'      => __( 'Per site', 'bluegroup-project-blueworx' ),
			'subA'     => __( 'per year, billed annually', 'bluegroup-project-blueworx' ),
			'lbl'      => __( 'INCLUDED', 'bluegroup-project-blueworx' ),
			'features' => array(
				'Managed WordPress hosting for one site',
				'Free migration from your current host',
				'Daily off-site backups, 30-day history',
				'Free SSL and global CDN',
				'Firewall, malware scanning and clean-up',
				'Core, plugin and theme updates',
				'One-click staging environment',
				'24/7 monitoring and email support',
			),
		),
		'perf'     => array(
			array( 'stat' => '99.9%', 'name' => 'Uptime target', 'desc' => 'Monitored every minute from multiple regions, with alerts that reach a human, not a dashboard.' ),
			array( 'stat' => '<200ms', 'name' => 'Server response', 'desc' => 'Object and page caching tuned per site, so the first byte arrives before a visitor notices the wait.' ),
			array( 'stat' => 'NVMe', 'name' => 'Storage', 'desc' => 'All-flash NVMe disks and PHP 8 workers, not oversold spinning platters shared with 400 neighbours.' ),
			array( 'stat' => 'Global', 'name' => 'CDN included', 'desc' => 'Static assets served from the edge, so a visitor in Sydney gets the same site speed as one in Slough.' ),
			array( 'stat' => 'Staging', 'name' => 'Safe changes', 'desc' => 'A one-click staging copy for every site, so nothing risky is ever tried on the live version.' ),
			array( 'stat' => '24/7', 'name' => 'Monitoring', 'desc' => 'Uptime, certificates, disk and error rates watched around the clock, with fixes started before you call.' ),
		),
		'security' => array(
			array( 'name' => 'Daily backups, 30-day history', 'desc' => 'Off-site, restorable to any point in the last month, and tested — a backup nobody has restored is a rumour.' ),
			array( 'name' => 'Free SSL, renewed automatically', 'desc' => 'Certificates issued and renewed for you, with HTTPS enforced site-wide.' ),
			array( 'name' => 'Web application firewall', 'desc' => 'Malicious traffic, brute-force attempts and known exploits blocked at the edge before they reach WordPress.' ),
			array( 'name' => 'Managed updates', 'desc' => 'Core, plugin and theme updates applied on a schedule and checked afterwards, not fired blind at 3am.' ),
			array( 'name' => 'Isolated environments', 'desc' => 'Every site runs in its own container, so a neighbour being compromised is not your problem.' ),
			array( 'name' => 'Malware clean-up included', 'desc' => 'If something does get through on a site we host, we clean it and restore it at no extra cost.' ),
		),
		'compare'  => array(
			array( 'label' => 'Managed updates', 'a' => 'Included', 'b' => 'Your job', 'c' => 'Your job' ),
			array( 'label' => 'Daily off-site backups', 'a' => '30-day history', 'b' => 'Often paid extra', 'c' => 'You configure it' ),
			array( 'label' => 'Free migration', 'a' => 'Included', 'b' => 'Sometimes', 'c' => 'You do it' ),
			array( 'label' => 'Staging environment', 'a' => 'One click', 'b' => 'Rarely', 'c' => 'You build it' ),
			array( 'label' => 'WAF & malware clean-up', 'a' => 'Included', 'b' => 'Paid add-on', 'c' => 'You configure it' ),
			array( 'label' => 'Support that knows your site', 'a' => 'The team who built it', 'b' => 'Generic ticket queue', 'c' => 'Nobody' ),
			array( 'label' => 'Server maintenance', 'a' => 'Ours', 'b' => 'Theirs', 'c' => 'Yours' ),
			array( 'label' => 'Typical cost of ownership', 'a' => 'One monthly fee', 'b' => 'Cheap, plus add-ons', 'c' => 'Server + your time' ),
		),
		'faqs'     => array(
			array( 'q' => 'Is there a contract?', 'a' => 'No. Monthly hosting is rolling and you can leave whenever you like — we will hand over a full copy of the site if you do. Annual is paid up front and works out at ten months for twelve.' ),
			array( 'q' => 'What counts as one site?', 'a' => 'One WordPress installation on one primary domain, including its staging copy. Multi-site networks and second brands are quoted separately.' ),
			array( 'q' => 'Do you have traffic limits?', 'a' => 'There is no hard cap. If a site consistently uses far more resource than a typical business site we will talk to you about it rather than throttle it or bill you by surprise.' ),
			array( 'q' => 'Can you host a site you did not build?', 'a' => 'Yes, as long as it is a WordPress site in reasonable health. We audit it during migration and tell you anything that needs attention first.' ),
			array( 'q' => 'Where are the servers?', 'a' => 'UK and EU data centres, with the CDN serving assets globally. Tell us if you have a data residency requirement and we will confirm the region before you sign up.' ),
		),
	);

	/**
	 * Filters the hosting page content.
	 *
	 * @param array $data See above.
	 */
	return apply_filters( 'blueworx_content_hosting', $data );
}

/**
 * Everything the ClubHouse page says: the plan, the nine modules (with their
 * stroke-icon SVG paths), the self-service cards, the audiences and the FAQ.
 *
 * @return array array( plan, modules, self_serve, audiences, faqs ).
 */
function blueworx_content_clubhouse() {
	$data = array(
		'plan'       => array(
			'name'     => 'ClubHouse',
			'desc'     => 'The complete club website platform, hosted and maintained by us.',
			'priceM'   => 20,
			'priceA'   => 200,
			'currency' => 'GBP',
			'feat'     => true,
			'pop'      => true,
			'subA'     => __( 'per year, billed annually', 'bluegroup-project-blueworx' ),
			'lbl'      => __( 'INCLUDED', 'bluegroup-project-blueworx' ),
			'features' => array(
				'All nine ClubHouse modules',
				'Managed hosting, SSL and daily backups',
				'Online payments for subs, tickets and kit',
				'Unlimited teams, fixtures and members',
				'Mobile-first club site, branded to you',
				'Platform updates and security patches',
				'Email support from the BlueWorx team',
			),
		),
		'modules'    => array(
			array( 'name' => 'Memberships', 'desc' => 'Tiers, joining flows, renewals and subs collected by direct debit or card.', 'paths' => array( 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8', 'M22 21v-2a4 4 0 0 0-3-3.87' ) ),
			array( 'name' => 'Teams & squads', 'desc' => 'A page per team with squad lists, coaches, league tables and results.', 'paths' => array( 'M12 2 4 6v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V6Z' ) ),
			array( 'name' => 'Fixtures & results', 'desc' => 'Season fixtures, scores and reports, published once and shown everywhere.', 'paths' => array( 'M8 2v4', 'M16 2v4', 'M3 10h18', 'M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z' ) ),
			array( 'name' => 'Bookings', 'desc' => 'Courts, pitches, lanes and function rooms, bookable by members online.', 'paths' => array( 'M12 6v6l4 2', 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20' ) ),
			array( 'name' => 'Events & socials', 'desc' => 'Open days, camps, awards nights — with tickets and capacity limits.', 'paths' => array( 'M21 11.5a8.4 8.4 0 0 1-.9 3.8A8.5 8.5 0 0 1 12.5 20 8.4 8.4 0 0 1 8.7 19L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 4Z' ) ),
			array( 'name' => 'Club shop', 'desc' => 'Kit, merchandise and match-day extras, with member-only pricing.', 'paths' => array( 'M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z', 'M3 6h18', 'M16 10a4 4 0 0 1-8 0' ) ),
			array( 'name' => 'News & notices', 'desc' => 'Match reports, committee notices and a ticker for anything urgent.', 'paths' => array( 'M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Z', 'M8 7h8', 'M8 12h4' ) ),
			array( 'name' => 'Sponsors', 'desc' => 'Partner tiers, logo boards and a sponsorship enquiry route that converts.', 'paths' => array( 'M12 2 15 8.3l6.9.6-5.2 4.5 1.6 6.7L12 17l-6.3 3.1 1.6-6.7L2.1 8.9l6.9-.6Z' ) ),
			array( 'name' => 'Calendar', 'desc' => 'Everything the club is doing this month, in one subscribable feed.', 'paths' => array( 'M8 2v4', 'M16 2v4', 'M3 10h18', 'M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z', 'M9 16h6' ) ),
		),
		'self_serve' => array(
			array( 'tag' => 'Join', 'name' => 'Sign-up in minutes', 'desc' => 'A member joins, picks a tier, pays, and lands in the club directory without a committee member touching a spreadsheet.' ),
			array( 'tag' => 'Renew', 'name' => 'Renewals that chase themselves', 'desc' => 'Automatic reminders, card retries and a renewal page every member can reach from their phone.' ),
			array( 'tag' => 'Book', 'name' => 'Courts and pitches online', 'desc' => 'Availability, rules and member-only slots, with the bar and grounds team seeing the same diary.' ),
			array( 'tag' => 'Buy', 'name' => 'Kit and tickets in one basket', 'desc' => 'Shop and event ticketing share a checkout, so one order covers the kit bag and the awards night.' ),
		),
		'audiences'  => array(
			array( 'name' => 'Multi-sport clubs', 'desc' => 'Rugby, cricket, hockey, netball and tennis under one roof, each section with its own space.' ),
			array( 'name' => 'Single-sport clubs', 'desc' => 'Juniors through to first team, with squads, fixtures and subs handled in one place.' ),
			array( 'name' => 'Societies & associations', 'desc' => 'Membership tiers, an events calendar and a members-only area, without the sport.' ),
			array( 'name' => 'Gyms & studios', 'desc' => 'Class booking, recurring memberships and a shop, on hosting that stays up at 6am.' ),
		),
		'faqs'       => array(
			array( 'q' => 'Can we keep our existing domain?', 'a' => 'Yes. We point your current domain at the new site and handle the DNS and SSL for you, with no downtime on the day of the switch.' ),
			array( 'q' => 'What happens to our current member list?', 'a' => 'We import it. Send us whatever you have — a spreadsheet, an export from your old system — and we map it into membership tiers before you go live.' ),
			array( 'q' => 'Is hosting really included?', 'a' => 'It is. Managed hosting, backups, SSL, updates and monitoring are part of the monthly fee, so there is no separate hosting bill.' ),
			array( 'q' => 'Who updates the site day to day?', 'a' => 'Your committee does, through a simple editor. Fixtures, news and events take a couple of minutes each, and we are on the end of an email when something bigger is needed.' ),
			array( 'q' => 'Can we take payments through the site?', 'a' => 'Yes — memberships, subs, event tickets and shop orders all run through the same checkout, with money landing in the club account.' ),
		),
	);

	/**
	 * Filters the ClubHouse page content.
	 *
	 * @param array $data See above.
	 */
	return apply_filters( 'blueworx_content_clubhouse', $data );
}
```

- [ ] **Step 5: Run the content tests**

Run: `npx playwright test tests/public-content.spec.js`
Expected: PASS.

- [ ] **Step 6: Let SureCart price IDs cover hosting and clubhouse too**

In `includes/public/commerce.php`, after `blueworx_commerce_apply_live_plans()` and its `add_filter`, add:

```php
/**
 * Every plan a SureCart price can be configured for: the nine support
 * packages plus the single Hosting and ClubHouse plans.
 *
 * Read by the admin field and its sanitizer, so a plan added here gets a row
 * in wp-admin without a second list to edit.
 *
 * @return array List of plan arrays (name is what matters here).
 */
function blueworx_commerce_sellable_plans() {
	$hosting   = blueworx_content_hosting();
	$clubhouse = blueworx_content_clubhouse();

	return array_merge(
		blueworx_content_support_packages(),
		array( $hosting['plan'], $clubhouse['plan'] )
	);
}

/**
 * Applies a configured SureCart price to the single plan inside the hosting
 * or ClubHouse content, the way apply_live_plans() does for the packages.
 *
 * @param array $data Page content carrying a 'plan' key.
 * @return array
 */
function blueworx_commerce_apply_live_single_plan( $data ) {
	if ( ! is_array( $data ) || empty( $data['plan'] ) ) {
		return $data;
	}

	$applied      = blueworx_commerce_apply_live_plans( array( $data['plan'] ) );
	$data['plan'] = $applied[0];

	return $data;
}
add_filter( 'blueworx_content_hosting', 'blueworx_commerce_apply_live_single_plan' );
add_filter( 'blueworx_content_clubhouse', 'blueworx_commerce_apply_live_single_plan' );
```

In `includes/admin/settings.php` change both `foreach ( blueworx_content_retainer_plans() as $plan )` loops (sanitizer at ~line 223 and renderer at ~line 297) to `foreach ( blueworx_commerce_sellable_plans() as $plan )`.

- [ ] **Step 7: Check the admin settings spec still passes**

Run: `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8882 WP_ADMIN_USER=admin WP_ADMIN_PASS='dsSyncPass123!' npx playwright test tests/admin-settings.spec.js`
Expected: PASS. If it asserts on a plan name such as "Growth Support", change the expectation to "Growth" (the field now lists eleven plans).

- [ ] **Step 8: Commit**

```bash
git add includes/public/content.php includes/public/commerce.php includes/admin/settings.php tests/public-content.spec.js tests/admin-settings.spec.js
git commit -m "Add the support packages, hosting and ClubHouse content; SureCart price IDs cover all sellable plans"
```

---

### Task 7: Register the pages, retire Pricing and Services, SEO copy, redirects

**Files:**
- Modify: `includes/public/pages.php:56-95` (registry)
- Modify: `includes/public/upgrade.php` (retire step in `blueworx_public_maybe_install_pages()`)
- Modify: `includes/public/redirects.php:38-57`
- Modify: `includes/public/seo-copy.php:37-75`
- Modify: `tests/legacy-redirects.spec.js`, `tests/seo-copy.spec.js`, `tests/indexing.spec.js`, `tests/render.spec.js`, `tests/cache-headers.spec.js`, `tests/no-dead-controls.spec.js`, `tests/page-standards.spec.js`, `tests/skip-link.spec.js`, `tests/images.spec.js`
- Delete: `tests/marketing-services.spec.js`; the Pricing half of `tests/marketing-plans.spec.js`

**Interfaces:**
- Produces: registry keys `clubhouse`, `hosting`, `support` → `templates/pages/clubhouse.php`, `hosting.php`, `support.php` (created in Task 8; until then the pages 500 — do Task 8 before running browser specs).

- [ ] **Step 1: Update the redirect spec first**

In `tests/legacy-redirects.spec.js` change the `REDIRECTS` array to:

```js
const REDIRECTS = [
  { from: '/shop', to: '/support' },
  { from: '/about-us', to: '/about' },
  { from: '/features', to: '/toolbox' },
  { from: '/test-page', to: '/' },
  // 2026-09 restructure: Pricing became Integrated Support; Services was
  // folded into the three product pages.
  { from: '/pricing', to: '/support' },
  { from: '/services', to: '/support' },
];
```

and in the "does not redirect owned pages" loop (~line 86) replace `'/pricing/'` with `'/support/'`. Update the file's header comment to mention the two new entries.

- [ ] **Step 2: Registry, redirects, SEO copy**

`includes/public/pages.php` — in `blueworx_public_pages()` remove the `'services'` and `'pricing'` entries and add, after `'home'`:

```php
		'clubhouse' => array(
			'title'    => __( 'ClubHouse', 'bluegroup-project-blueworx' ),
			'template' => 'pages/clubhouse.php',
		),
		'hosting'   => array(
			'title'    => __( 'Hosting', 'bluegroup-project-blueworx' ),
			'template' => 'pages/hosting.php',
		),
		'support'   => array(
			'title'    => __( 'Integrated Support', 'bluegroup-project-blueworx' ),
			'template' => 'pages/support.php',
		),
```

`includes/public/redirects.php` — in `blueworx_public_legacy_redirects()` change `'shop' => 'pricing'` to `'shop' => 'support'` and add:

```php
			// 2026-09 restructure. Pricing became Integrated Support and
			// Services was folded into the three product pages. Both were
			// indexed for years, so they redirect rather than 404.
			'pricing'   => 'support',
			'services'  => 'support',
```

`includes/public/seo-copy.php` — remove the `'services'` and `'pricing'` entries and add:

```php
		'clubhouse' => array(
			'title'       => __( 'ClubHouse — The Website Platform for Sports Clubs', 'bluegroup-project-blueworx' ),
			'description' => __( 'A ready-made club website with memberships, teams, fixtures, bookings, events and a shop, on managed hosting from day one. £20 a month, live in two weeks.', 'bluegroup-project-blueworx' ),
		),
		'hosting'   => array(
			'title'       => __( 'Managed WordPress Hosting, One Price Per Site', 'bluegroup-project-blueworx' ),
			'description' => __( 'Fast managed WordPress hosting with daily backups, free SSL, a firewall, staging and monitoring handled for you. £20 a month per site, and we move you across for free.', 'bluegroup-project-blueworx' ),
		),
		'support'   => array(
			'title'       => __( 'Integrated Support — Design and Development on Retainer', 'bluegroup-project-blueworx' ),
			'description' => __( 'Nine support packages from £100 a month. Buy a block of hours and spend them on design, development, content, SEO or fixes, with the whole BlueWorx team behind them.', 'bluegroup-project-blueworx' ),
		),
```

- [ ] **Step 3: Retire the two pages on the version-change pass**

In `includes/public/upgrade.php`, add above `blueworx_public_maybe_install_pages()`:

```php
/**
 * Trashes the pages a release removed from the registry.
 *
 * A registry entry that disappears leaves its page behind: published, in the
 * ID map, and — because the template is gone — rendered by the theme as an
 * empty page. Trashed (not deleted) so it can be restored from wp-admin, and
 * only when the page carries the plugin's own stamp: a page the site created
 * under the same slug is not ours to touch. The legacy redirect for each path
 * (includes/public/redirects.php) is what visitors actually meet.
 *
 * Idempotent: a slug no longer in the map is skipped.
 *
 * @return void
 */
function blueworx_public_retire_removed_pages() {
	$map     = (array) get_option( 'blueworx_public_page_ids', array() );
	$retired = array( 'pricing', 'services' );
	$changed = false;

	foreach ( $retired as $slug ) {
		if ( empty( $map[ $slug ] ) ) {
			continue;
		}

		$page_id = (int) $map[ $slug ];

		if ( 'page' === get_post_type( $page_id ) && blueworx_public_page_is_ours( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
			wp_trash_post( $page_id );
		}

		unset( $map[ $slug ] );
		$changed = true;
	}

	if ( $changed ) {
		update_option( 'blueworx_public_page_ids', $map );
	}
}
```

and call it as the first line inside `blueworx_public_maybe_install_pages()` after the version check (i.e. before `blueworx_public_install_pages();`).

- [ ] **Step 4: Update the path lists in the existing specs**

Make these mechanical edits (each file has a `PAGES`/`PUBLIC_PATHS`/`OWNED_PAGES` constant near the top):

- `tests/cache-headers.spec.js:18` → `['/', '/clubhouse/', '/hosting/', '/support/', '/toolbox/', '/about/', '/toolbox/surecart/']`; line 75 and 96 → replace `/pricing/` with `/support/`.
- `tests/images.spec.js:14` → `['/', '/clubhouse/', '/hosting/', '/toolbox/', '/work/', '/about/', '/toolbox/surecart/']`; line 146 → `['/', '/hosting/', '/toolbox/']`.
- `tests/indexing.spec.js:33` → `['/', '/clubhouse/', '/hosting/', '/support/', '/toolbox/']`; line 213 expected sitemap list → `['/about/', '/clubhouse/', '/hosting/', '/support/', '/contact/', '/work/', '/ai/', '/toolbox/', '/blog/']`.
- `tests/no-dead-controls.spec.js:17` → `['/', '/clubhouse/', '/hosting/', '/support/', '/toolbox/', '/toolbox/surecart/', '/contact/', '/about/']`.
- `tests/page-standards.spec.js:35-38` → replace the Services and Pricing rows with `{ path: '/clubhouse/', issue: 'ClubHouse' }, { path: '/hosting/', issue: 'Hosting' }, { path: '/support/', issue: 'Integrated Support' }`.
- `tests/render.spec.js:10` → `['/about', '/clubhouse', '/hosting', '/support', '/contact', '/work', '/ai', '/toolbox']`.
- `tests/seo-copy.spec.js:17` → `['/', '/clubhouse/', '/hosting/', '/support/', '/toolbox/', '/about/', '/work/', '/ai/', '/contact/']`; line 91 → replace `/pricing/` with `/support/`.
- `tests/skip-link.spec.js:17` → `['/', '/hosting/', '/toolbox/', '/toolbox/surecart/', '/login/']`; line 71 → `/hosting/`.
- `tests/toolbox-tool-pages.spec.js:131` → `robotsFor('/support/')`.
- `tests/marketing-home.spec.js:32` → the first service card now links to Support: `toHaveAttribute('href', /\/support\/?$/)`.
- Delete `tests/marketing-services.spec.js`. In `tests/marketing-plans.spec.js` delete the whole `'Marketing pricing page'` describe block and update the header comment; keep the Toolbox block.
- `tests/commerce-pricing.spec.js` and `tests/widgets-commerce.spec.js`: replace every `/pricing` with `/support`, `'Growth Support'` with `'Growth'`, `'$500'`→`'£500'`, `'$400'`→`'£500'` (no annual discount), and any `'growth-support'` slug with `'growth'`. Read each assertion after replacing: a test that expected the *annual* figure to differ from monthly must now expect them equal, and one that clicks the billing toggle on `/support` must be dropped (the Support page has no toggle — see Task 8).

- [ ] **Step 5: Commit (browser specs run after Task 8)**

```bash
git add includes/public/pages.php includes/public/upgrade.php includes/public/redirects.php includes/public/seo-copy.php tests/
git rm tests/marketing-services.spec.js
git commit -m "Register ClubHouse, Hosting and Support; retire Pricing and Services behind redirects"
```

---

### Task 8: The three page templates

**Files:**
- Create: `templates/pages/clubhouse.php`, `templates/pages/hosting.php`, `templates/pages/support.php`
- Modify: `assets/js/public-widgets.js` (add `initSupportCalc`)
- Create: `tests/marketing-clubhouse.spec.js`, `tests/marketing-hosting.spec.js`, `tests/marketing-support.spec.js`
- Reference: the three `.dc.html` designs in the spec folder — match their markup section by section. Shared markup helpers already exist: `blueworx_public_document_open( array( 'body_class' => ... ) )`, `blueworx_public_part( 'parts/nav.php' )`, `'parts/tech-hero.php'` (see its `$vars` doc), `'parts/glass-card.php'`, `'parts/plan-card.php'`, `'parts/proc-grid.php'`, `'parts/footer.php'`, `blueworx_blob( $style )`, `blueworx_public_image( 'img/x.jpg', $alt, $args )`, `blueworx_public_document_close()` (check `includes/public/render.php` for the exact close helper name and use it the way `templates/pages/work.php` does).

**Interfaces:**
- Consumes: Task 6 accessors, Task 3 `plan-card.php`, Task 5 footer `$vars`, Task 2 classes, Task 4 `data-bw-gbp` contract and `window.blueworxMoney`.
- Produces: `[data-widget="support-calc"]` with `input.bw-range[name=hours]`, `[data-testid="support-calc-hours"]`, `-annual`, `-name`, `-blurb`, `-rate`, `-price`; the calculator reads its package table from a `data-packages` JSON attribute on the widget root.

- [ ] **Step 1: Write the three failing specs**

`tests/marketing-clubhouse.spec.js`:

```js
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

test.describe('Marketing ClubHouse page', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('renders the hero, nine modules, demo band, four dark cards, plan, audiences and FAQ', async ({ page }) => {
    await page.goto(cacheBust('/clubhouse/'));
    await expect(page.locator('.tech-hero.bw-hero-tight .tech-2col')).toHaveCount(1);
    await expect(page.locator('.tech-hero .glass-card')).toHaveCount(1);
    await expect(page.locator('.tech-hero a[href="https://demo.305media.co.uk/"][target="_blank"]')).toHaveCount(1);
    await expect(page.locator('main .bw-g3 .bw-card')).toHaveCount(9);
    await expect(page.locator('.bw-demo .bw-demo-shot img')).toHaveCount(3);
    const srcs = await page.locator('.bw-demo img').evaluateAll((imgs) => imgs.map((i) => i.getAttribute('src') || ''));
    expect(srcs.every((s) => /\/assets\/img\/clubhouse-demo-/.test(s))).toBe(true);
    await expect(page.locator('.features-dark .bw-card-dark')).toHaveCount(4);
    await expect(page.locator('.plan-card.feat')).toHaveCount(1);
    await expect(page.locator('.plan-card .plan-name .pop')).toHaveText('Popular');
    await expect(page.locator('.bw-plan-aside')).toHaveCount(1);
    await expect(page.locator('.bw-g4 .bw-card').filter({ hasText: 'Multi-sport clubs' })).toHaveCount(1);
    await expect(page.locator('.faq-list details.faq-item')).toHaveCount(5);
  });

  test('billing toggle swaps £20 per month for £200 per year', async ({ page }) => {
    await page.goto(cacheBust('/clubhouse/'));
    await expect(page.locator('.plan-price b')).toHaveText('£20');
    await expect(page.locator('.plan-price em')).toHaveText('per month');
    await page.locator('.bill-toggle button').nth(1).click();
    await expect(page.locator('.plan-price b')).toHaveText('£200');
    await expect(page.locator('.plan-price em')).toHaveText('per year, billed annually');
  });

  test('the CTA band is the ClubHouse one and the Popular badge is legible', async ({ page }) => {
    await page.goto(cacheBust('/clubhouse/'));
    await expect(page.locator('.cta-soft h2')).toHaveText('Ready to Move Your Club Online?');
    const color = await page.locator('.plan-card.feat .pop').evaluate((el) => getComputedStyle(el).color);
    expect(color).not.toBe('rgb(255, 255, 255)');
  });
});
```

`tests/marketing-hosting.spec.js`:

```js
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

test.describe('Marketing Hosting page', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('renders hero, six performance cards, six security cards, migration split, plan, comparison and FAQ', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    await expect(page.locator('.tech-hero .tech-2col .glass-card')).toHaveCount(1);
    await expect(page.locator('.tech-hero a[href="#hosting-plans"]')).toHaveCount(1);
    await expect(page.locator('main .sec .bw-g3 .bw-card')).toHaveCount(6);
    await expect(page.locator('.features-dark .bw-card-dark')).toHaveCount(6);
    await expect(page.locator('section.split .collab-list .fli')).toHaveCount(4);
    await expect(page.locator('#hosting-plans.sec.bw-divided')).toHaveCount(1);
    await expect(page.locator('#hosting-plans .plan-card.feat .pop')).toHaveText('Per site');
    await expect(page.locator('#hosting-plans .bw-plan-aside a[href$="/support/"]')).toHaveCount(1);
    await expect(page.locator('table.cmp tbody tr')).toHaveCount(8);
    await expect(page.locator('.faq-list details.faq-item')).toHaveCount(5);
  });

  test('billing toggle swaps £20 per month for £200 per year', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('£20');
    await page.locator('#hosting-plans .bill-toggle button').nth(1).click();
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('£200');
    await expect(page.locator('#hosting-plans .plan-price em')).toHaveText('per year, billed annually');
  });
});
```

`tests/marketing-support.spec.js`:

```js
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

test.describe('Marketing Integrated Support page', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('renders the tall hero, three featured cards, calculator, nine-row table, six uses, process and FAQ', async ({ page }) => {
    await page.goto(cacheBust('/support/'));
    await expect(page.locator('.tech-hero.pb-tall')).toHaveCount(1);
    await expect(page.locator('.tech-hero .tech-status [data-bw-gbp="100"]')).toHaveText('£100');
    const cards = page.locator('.plans .plan-card');
    await expect(cards).toHaveCount(3);
    await expect(cards.nth(0).locator('.plan-name span').first()).toHaveText('Starter');
    await expect(cards.nth(1)).toHaveClass(/feat/);
    await expect(cards.nth(1).locator('.plan-name span').first()).toHaveText('Growth');
    await expect(cards.nth(2).locator('.plan-name span').first()).toHaveText('Enterprise +');
    await expect(cards.nth(1).locator('.plan-price b')).toHaveText('£500');
    await expect(cards.nth(1).locator('.plan-price em')).toHaveText('per month · 10 hrs a month');
    await expect(page.locator('.bill-toggle')).toHaveCount(0);
    await expect(page.locator('[data-widget="support-calc"]')).toBeVisible();
    await expect(page.locator('table.cmp tbody tr')).toHaveCount(9);
    await expect(page.locator('table.cmp tbody tr').nth(4)).toContainText('Growth');
    await expect(page.locator('table.cmp tbody tr').nth(4)).toContainText('£50.00 / hr');
    await expect(page.locator('.features-dark .bw-card-dark')).toHaveCount(6);
    await expect(page.locator('.proc-grid .proc')).toHaveCount(4);
    await expect(page.locator('.faq-list details.faq-item')).toHaveCount(5);
  });

  test('the hours slider defaults to Growth and updates the recommendation live', async ({ page }) => {
    await page.goto(cacheBust('/support/'));
    const calc = page.locator('[data-widget="support-calc"]');
    await expect(calc.locator('[data-testid="support-calc-name"]')).toHaveText('Growth');
    await expect(calc.locator('[data-testid="support-calc-hours"]')).toHaveText('10');
    await expect(calc.locator('[data-testid="support-calc-price"]')).toHaveText('£500');
    await expect(calc.locator('[data-testid="support-calc-rate"]')).toHaveText('£50.00 / hr');
    await calc.locator('input.bw-range').fill('8');
    await expect(calc.locator('[data-testid="support-calc-name"]')).toHaveText('Advantage +');
    await expect(calc.locator('[data-testid="support-calc-hours"]')).toHaveText('50');
    await expect(calc.locator('[data-testid="support-calc-annual"]')).toHaveText('600');
    await expect(calc.locator('[data-testid="support-calc-price"]')).toHaveText('£1,500');
    await expect(calc.locator('[data-testid="support-calc-rate"]')).toHaveText('£30.00 / hr');
  });
});
```

- [ ] **Step 2: Run them to confirm they fail**

Run: `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8882 npx playwright test tests/marketing-clubhouse.spec.js tests/marketing-hosting.spec.js tests/marketing-support.spec.js`
Expected: FAIL (templates missing → the pages 500 or 404).

- [ ] **Step 3: Write `templates/pages/clubhouse.php`**

Follow the `.dc.html` section order exactly. Skeleton (fill every section from the design; the shapes below are complete for the parts that differ from existing templates):

```php
<?php
/**
 * ClubHouse page template — the club website platform.
 *
 * Built from the 2026-09 design (docs/superpowers/specs/2026-09-20-site-
 * restructure-design/BlueWorx ClubHouse.dc.html). Content comes from
 * blueworx_content_clubhouse(); the three demo screenshots are bundled
 * captures of demo.305media.co.uk.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_ch      = blueworx_content_clubhouse();
$blueworx_ch_demo = 'https://demo.305media.co.uk/';

blueworx_public_document_open( array( 'body_class' => 'bw-clubhouse' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main id="content" tabindex="-1">
	<div>
		<section class="tech-hero bw-hero-tight">
			<div class="tech-inner tech-2col">
				<div class="tc-copy">
					<?php
					blueworx_public_part(
						'parts/tech-hero.php',
						array(
							'centered'        => false,
							'badge'           => __( 'ClubHouse · Club Website Platform', 'bluegroup-project-blueworx' ),
							'title'           => __( 'Every Sport. Every Member. One Club Website.', 'bluegroup-project-blueworx' ),
							'title_highlight' => __( 'One Club Website.', 'bluegroup-project-blueworx' ),
							'lead'            => __( 'ClubHouse is a ready-made website platform for sports clubs and membership organisations. Teams, fixtures, memberships, bookings, events and a club shop, all running on managed hosting from day one.', 'bluegroup-project-blueworx' ),
							'cta'             => array(
								array( 'label' => __( 'View Live Demo', 'bluegroup-project-blueworx' ), 'href' => $blueworx_ch_demo, 'class' => 'btn btn-white btn-lg', 'external' => true ),
								array( 'label' => __( 'Talk to Us', 'bluegroup-project-blueworx' ), 'href' => home_url( '/contact' ), 'class' => 'btn btn-outline-w btn-lg' ),
							),
							'meta'            => array(
								__( 'live in 2 weeks', 'bluegroup-project-blueworx' ),
								__( 'hosting included', 'bluegroup-project-blueworx' ),
								__( 'no setup fee', 'bluegroup-project-blueworx' ),
							),
						)
					);
					?>
				</div>
				<?php
				ob_start();
				?>
				<div class="gc-metric"><small><?php esc_html_e( 'Members renewed', 'bluegroup-project-blueworx' ); ?></small><b>412</b><span class="up">▲</span></div>
				<div class="gc-metric"><small><?php esc_html_e( 'Fixtures published', 'bluegroup-project-blueworx' ); ?></small><b>96</b><span class="up">▲</span></div>
				<div class="gc-metric" style="border-bottom:none"><small><?php esc_html_e( 'Subs collected online', 'bluegroup-project-blueworx' ); ?></small><b>94%</b><span class="up">▲</span></div>
				<div class="gc-spark">
					<i style="height:34%"></i><i style="height:48%"></i><i style="height:42%"></i><i style="height:62%"></i><i style="height:74%"></i><i class="hi" style="height:96%"></i><i style="height:82%"></i><i style="height:90%"></i>
				</div>
				<?php
				$blueworx_ch_gc_body = ob_get_clean();

				blueworx_public_part(
					'parts/glass-card.php',
					array(
						'tag'    => __( 'clubhouse · season 26/27', 'bluegroup-project-blueworx' ),
						'body'   => $blueworx_ch_gc_body,
						'style'  => 'padding:28px',
						'floats' => array(
							array( 'icon' => 'users', 'label' => __( 'Active members', 'bluegroup-project-blueworx' ), 'value' => '1,240', 'style' => 'top:-22px;right:-26px;animation-delay:.4s' ),
							array( 'icon' => 'calendar', 'label' => __( 'Next fixture', 'bluegroup-project-blueworx' ), 'value' => 'Sat · 14:00', 'style' => 'bottom:-22px;left:-26px;animation-delay:1.1s' ),
						),
					)
				);
				?>
			</div>
		</section>
		<?php /* …sections 2–7 follow the design; see the notes below… */ ?>
	</div>
</main>
<?php
blueworx_public_part(
	'parts/footer.php',
	array(
		'cta_title'     => __( 'Ready to Move Your Club Online?', 'bluegroup-project-blueworx' ),
		'cta_copy'      => __( "Tour the demo, then tell us about your club. We'll have you live inside a fortnight.", 'bluegroup-project-blueworx' ),
		'cta_primary'   => array( 'label' => __( 'View Live Demo', 'bluegroup-project-blueworx' ), 'href' => $blueworx_ch_demo, 'external' => true ),
		'cta_secondary' => array( 'label' => __( 'Talk to Us', 'bluegroup-project-blueworx' ), 'href' => home_url( '/contact' ) ),
	)
);
```

Notes for the remaining sections (write them out in full in the file):

- **tech-hero.php CTA `external`:** the part currently renders `<a href class>` only. Add support for an optional `'external' => true` entry key that appends ` target="_blank" rel="noopener"` (edit `templates/parts/tech-hero.php` in the CTA loop). **glass-card.php float icons:** check the part's icon map; if `users`/`calendar` are not among the supported names, add them using the SVG paths from the design (`M16 21v-2a4 4 0 0 0-4-4H6…` and the calendar rect/lines).
- **Section 2 (modules):** `<section class="sec">` → `.center-head` (eyebrow "What's Included", h2 "Every Part of Club Life, Already Built", lead) → `<div class="bw-g3">` with one `.bw-card` per module: `<div class="svc-ic" style="margin-bottom:18px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:100%;height:100%">` + one `<path d="…">` per entry in `paths` (escape each with `esc_attr`) + `</svg></div><h3>name</h3><p>desc</p>`.
- **Section 3 (demo band):** `<section class="sec bw-divided">` → `.bw-split-head` (eyebrow "See It Live", h2 "A Full Club Site, Running Today", right: `<a class="btn btn-outline btn-md" href=demo target=_blank rel=noopener>Open the Demo` + arrow svg) → `<div class="bw-demo"><div class="bw-demo-shot">` `blueworx_public_image( 'img/clubhouse-demo-home.jpg', __( 'The ClubHouse demo home page' ) )` `</div><div class="bw-demo-stack"><div class="bw-demo-shot">…teams…</div><div class="bw-demo-shot">…membership…</div></div></div>`.
- **Section 4:** `<section class="features-dark">` → `blueworx_blob( 'width:360px;height:360px;top:-120px;right:-120px;opacity:.14' )` → `.fd-header` (h2 "Members Do It Themselves. Committees Get Their Evenings Back.", `.fd-sub`) → `.bw-g4` of `.bw-card-dark` (`.bw-tag` tag, h3 name, p desc) from `self_serve`.
- **Section 5 (pricing):** `<section class="sec">` (no divider — it follows a dark band) → `.center-head` (eyebrow "Pricing", h2 "One Price. The Whole Platform.", lead) → `<div style="display:flex;justify-content:center;margin-bottom:34px"><div class="bill-toggle" data-widget="billing-toggle"><button type="button" class="on">Monthly billing</button><button type="button">Annual billing</button></div></div>` → `<div class="bw-g2 bw-plan-grid">` → `blueworx_public_part( 'parts/plan-card.php', array( 'plan' => $blueworx_ch['plan'] ) )` + `<div class="bw-plan-aside">` with h3 "Need it tailored?", the paragraph, the three `.collab-list .fli` rows (icons + "Member data migration" / "Club branding & kit colours" / "Custom modules & integrations") and `<a class="btn btn-outline btn-md" href=/contact>Get a Quote` + arrow.
- **Section 6 (audiences):** `<section class="sec bw-divided">` → `.center-head` (eyebrow "Who It's For", h2 "Built for Clubs. Works for Any Membership Organisation.") → `.bw-g4` of `.bw-card` (h3 18px, p `#667085`) from `audiences`.
- **Section 7 (FAQ):** `<section class="sec bw-divided">` → `.center-head` (h2 "Frequently asked questions", lead "Everything you need to know about running your club on ClubHouse.") → `<div class="faq-list">` → for each faq: `<details class="faq-item"><summary class="faq-q">q<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9" /></svg></summary><div class="faq-a"><p>a</p></div></details>`.

- [ ] **Step 4: Write `templates/pages/hosting.php`**

Same skeleton, `body_class => 'bw-hosting'`, `$blueworx_h = blueworx_content_hosting();`. Sections in order:

1. `<section class="tech-hero">` two-column: tech-hero part with badge "Managed Hosting", title "Hosting That Stays Up, Stays Fast, and Stays Patched", highlight "Stays Fast", lead, CTAs "Move My Site" (`btn btn-white btn-lg`, `/contact`) and "See Pricing" (`btn btn-outline-w btn-lg`, `#hosting-plans`), meta "99.9% uptime" / "free migration" / "daily backups". Glass card: `status_label => __( 'All systems live' )`, `status_color => '#01D084'`, tag "status · last 30 days", metrics Uptime 99.98% / Median server response 184ms / Blocked malicious requests 41,208, the spark bars from the design, floats `server` "Backups" "Daily · 30 days" (top-right) and `shield` "WAF & SSL" "Always on" (bottom-left). Add `server`/`shield` icons to glass-card.php if absent (paths in the design).
2. `<section class="sec">` "Performance" / "Built for Speed, Measured Constantly" / lead → `.bw-g3` of `.bw-card` with `<div class="bw-stat">stat</div><h3>name</h3><p>desc</p>`.
3. `<section class="features-dark">` blob + `.fd-header` ("Security and Backups You Never Have to Think About", sub) → `.bw-g3` of `.bw-card-dark` (h3, p) from `security`.
4. `<section class="split">` — copy the structure from `templates/pages/home.php`'s `.split` section: left column eyebrow "Migration", h2 "We Move You. You Do Nothing.", `.lead` (18px, `margin:18px 0 30px`), `.collab-list` of four `.fli` rows each `<div class="fli-num">1</div><span style="font-size:17px">Copy and test on staging</span>` (…"You review and sign off", "Out-of-hours DNS switch", "We watch it for 48 hours"), then `<a class="btn btn-outline btn-md" href=/contact>Start a Migration` + arrow. Right: `<div class="collab-visual">` `blueworx_public_image( 'img/feature-image-3.jpg', __( 'BlueWorx managed hosting' ) )` + two `.collab-chip`s ("Downtime" "0 minutes" green check at `top:26px;left:-14px`; "Typical move" "2–3 days" clock at `bottom:30px;right:-14px`) — copy the chip markup from the design verbatim.
5. `<section class="sec bw-divided" id="hosting-plans">` "Pricing" / "One Site. One Price. Everything In." / lead → billing toggle (`data-widget="billing-toggle"`) → `.bw-g2.bw-plan-grid` → plan-card part with `$blueworx_h['plan']` + `.bw-plan-aside`: h3 "Hosting is better with support", `<p>… from <span data-bw-gbp="100">£100</span> a month.</p>`, three `.pf` lines ("Hours pooled across the year", "Priority response on hosted sites", "One invoice for the lot"), `<a class="btn btn-outline btn-md" href=/support>View Integrated Support` + arrow.
6. `<section class="sec bw-divided">` "Compare" / "How We Stack Up" / lead → `<div class="cmp-scroll"><table class="cmp"><thead><tr><th>Feature</th><th>BlueWorx</th><th>Shared hosting</th><th>Self-managed VPS</th></tr></thead><tbody>` rows from `compare` (`<td>label</td><td>a</td><td>b</td><td>c</td>`).
7. `<section class="sec bw-divided">` FAQ (h2 "Frequently asked questions", lead "Everything you need to know about hosting with BlueWorx.") from `faqs`, same markup as ClubHouse.

Footer: `cta_title` "Tired of Chasing Your Host?", `cta_copy` "Send us your current setup. We'll tell you what it is costing you in speed, and move you across for nothing.", primary "Move My Site" → `/contact`, secondary "Add Support" → `/support`.

- [ ] **Step 5: Write `templates/pages/support.php` and its calculator**

`body_class => 'bw-support'`, `$blueworx_s_packages = blueworx_content_support_packages();`, `$blueworx_s_featured = array_values( array_filter( $blueworx_s_packages, function ( $p ) { return ! empty( $p['featured'] ); } ) );`. Featured cards need the design's period label: before rendering, map each featured plan to set `'subM' => sprintf( __( 'per month · %s hrs a month' ), $p['hours'] / 12 )` and `'subA'` to the same string.

Sections:

1. Centered tall hero — compose inline like `templates/pages/pricing.php` did (not via the part, because the status row holds a converted price): `<section class="tech-hero pb-tall" style="text-align:center"><div class="tech-inner" style="max-width:820px;margin:0 auto"><div class="tech-badge" style="margin-bottom:22px"><span class="dot"></span>Integrated Support</div><h1 class="h1">Your Design &amp; Development Team, <span class="tech-grad">On Retainer</span></h1><p class="lead">Buy a block of hours each month and use them however your business needs: design, development, fixes, content, SEO, or a new landing page. All of the team, none of the admin.</p><div class="tech-status" style="justify-content:center"><span>from <b style="font:inherit" data-bw-gbp="100">£100</b> per month</span><span>hours pooled annually</span><span>cancel any time</span></div></div></section>`.
2. `blueworx_public_part( 'parts/plan-cards.php', array( 'plans' => $blueworx_s_featured ) )` — the wrap's `-190px` margin overlaps the tall hero exactly as Pricing did.
3. `<section class="sec">` "Find Your Level" / "How Many Hours Do You Need?" / lead → the calculator:

```php
<div class="calc" data-widget="support-calc" data-packages="<?php echo esc_attr( wp_json_encode( array_map( function ( $p ) { return array( 'name' => $p['name'], 'hours' => (int) $p['hours'], 'gbp' => (int) $p['priceM'], 'blurb' => $p['blurb'] ); }, $blueworx_s_packages ) ) ); ?>">
	<div class="calc-panel">
		<div class="calc-field">
			<label for="bw-support-hours"><?php esc_html_e( 'Support hours per month', 'bluegroup-project-blueworx' ); ?></label>
			<div class="bw-calc-big"><b data-testid="support-calc-hours">10</b><span><?php esc_html_e( 'hours', 'bluegroup-project-blueworx' ); ?> · <span data-testid="support-calc-annual">120</span> <?php esc_html_e( 'hours a year', 'bluegroup-project-blueworx' ); ?></span></div>
			<input class="bw-range" id="bw-support-hours" name="hours" type="range" min="0" max="8" step="1" value="4" />
			<div class="bw-range-ends"><span>2 hrs</span><span>50 hrs</span></div>
		</div>
		<div class="calc-field">
			<label><?php esc_html_e( 'Package', 'bluegroup-project-blueworx' ); ?></label>
			<div class="bw-calc-name" data-testid="support-calc-name">Growth</div>
			<p class="bw-calc-blurb" data-testid="support-calc-blurb"><?php echo esc_html( $blueworx_s_packages[4]['blurb'] ); ?></p>
		</div>
		<div class="calc-field" style="display:flex;align-items:center;justify-content:space-between;gap:16px">
			<label style="margin:0"><?php esc_html_e( 'Effective hourly rate', 'bluegroup-project-blueworx' ); ?></label>
			<b class="bw-calc-rate" data-testid="support-calc-rate" data-bw-gbp="50" data-bw-dp="2" data-bw-suffix=" / hr">£50.00 / hr</b>
		</div>
	</div>
	<div class="calc-out">
		<div class="cl"><?php esc_html_e( 'Your package', 'bluegroup-project-blueworx' ); ?></div>
		<div class="cv" data-testid="support-calc-price" data-bw-gbp="500">£500</div>
		<div class="cp"><?php esc_html_e( 'per month', 'bluegroup-project-blueworx' ); ?></div>
		<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn btn-brand btn-md" style="width:100%;text-decoration:none"><?php esc_html_e( 'Get this plan', 'bluegroup-project-blueworx' ); ?></a>
	</div>
</div>
```

4. `<section class="sec bw-divided">` "All Packages" / "Nine Levels of Integrated Support" / lead → `.cmp-scroll table.cmp` with head Package · Hours a year · Hours a month · Effective rate · Monthly price and one row per package: name (bold), `N hrs`, `N hrs`, `<span data-bw-gbp="<?php echo esc_attr( number_format( $p['priceM'] * 12 / $p['hours'], 2, '.', '' ) ); ?>" data-bw-dp="2" data-bw-suffix=" / hr">£50.00 / hr</span>`, `<span data-bw-gbp="500">£500</span>` (bold). Format the pound figures server-side with `number_format( $x, 0, '.', ',' )` / `number_format( $x, 2, '.', '' )` so the JS-off page matches the painted one.
5. `<section class="features-dark">` blob + `.fd-header` ("What You Can Spend Your Hours On", sub "Anything we do, you can draw from the same allowance. No separate quotes for small jobs, no surprise invoices.") → `.bw-g3` of `.bw-card-dark` from a local `$blueworx_s_uses` array of the six (name, desc) pairs in the design's `USES`.
6. `<section class="sec">` "How It Works" / "Support Without the Ticket Queue" → `blueworx_public_part( 'parts/proc-grid.php', array( 'steps' => …4 steps… ) )` — check `proc-grid.php`'s `$vars` for the exact key names and pass "Pick a level", "Ask us anything", "We draw down hours", "Review and adjust" with the design's copy.
7. `<section class="sec bw-divided">` FAQ from `blueworx_content_support_faqs()` (lead "Everything you need to know about Integrated Support and billing.").

Footer: `cta_title` "Not Sure Which Level Fits?", `cta_copy` "Tell us what you have been asking your last developer for. We'll tell you honestly which package covers it.", primary "Talk to Us" → `/contact`, secondary "See Hosting" → `/hosting`.

Add the calculator to `assets/js/public-widgets.js` (after `initPricingCalc`, and call `initSupportCalc();` from `init()`):

```js
	/**
	 * The Support page's hours slider (2026-09 restructure).
	 *
	 * The slider indexes the nine packages, whose numbers the template
	 * writes into data-packages so this never carries a second copy of the
	 * price list. Prices go through the painter's data-bw-gbp contract so a
	 * currency change repaints them like every other price on the page.
	 */
	function initSupportCalc() {
		var root = document.querySelector( '[data-widget="support-calc"]' );
		if ( ! root ) {
			return;
		}
		var packages;
		try {
			packages = JSON.parse( root.getAttribute( 'data-packages' ) || '[]' );
		} catch ( e ) {
			return;
		}
		var range = root.querySelector( 'input[type="range"]' );
		var hours = root.querySelector( '[data-testid="support-calc-hours"]' );
		var annual = root.querySelector( '[data-testid="support-calc-annual"]' );
		var name = root.querySelector( '[data-testid="support-calc-name"]' );
		var blurb = root.querySelector( '[data-testid="support-calc-blurb"]' );
		var rate = root.querySelector( '[data-testid="support-calc-rate"]' );
		var price = root.querySelector( '[data-testid="support-calc-price"]' );
		if ( ! range || ! packages.length ) {
			return;
		}

		function apply() {
			var pkg = packages[ Math.min( packages.length - 1, Math.max( 0, parseInt( range.value, 10 ) || 0 ) ) ];
			var perHour = ( pkg.gbp * 12 ) / pkg.hours;
			if ( hours ) {
				hours.textContent = String( pkg.hours / 12 );
			}
			if ( annual ) {
				annual.textContent = String( pkg.hours );
			}
			if ( name ) {
				name.textContent = pkg.name;
			}
			if ( blurb ) {
				blurb.textContent = pkg.blurb;
			}
			if ( rate ) {
				rate.setAttribute( 'data-bw-gbp', perHour.toFixed( 2 ) );
			}
			if ( price ) {
				price.setAttribute( 'data-bw-gbp', String( pkg.gbp ) );
			}
			paintPrices();
		}

		range.addEventListener( 'input', apply );
		range.addEventListener( 'change', apply );
		apply();
	}
```

- [ ] **Step 6: Create the pages locally and run the three specs plus nav and currency**

From `.wp-test/wp`: `php -r '$_SERVER["HTTP_HOST"]="127.0.0.1:8882"; require "wp-load.php"; do_action("init"); blueworx_public_install_pages();'` (harmless if init already ran; it creates the three pages and trashes the two retired ones on the next request because `blueworx_public_maybe_install_pages()` keys off the version — if the pages do not appear, temporarily set `BLUEWORX_SITE_VERSION` to `1.16.0` now rather than in Task 10).

Run: `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8882 npx playwright test tests/marketing-clubhouse.spec.js tests/marketing-hosting.spec.js tests/marketing-support.spec.js tests/nav-structure.spec.js tests/currency.spec.js`
Expected: all PASS.

- [ ] **Step 7: Look at the pages**

Screenshot each new page at 1440 wide (full page) and read the images. Compare against the design's section order and check: the ClubHouse hero headline's gradient phrase sits on one line; the demo band is 392px tall with no overflow into the next section; the featured plan's badge is legible; the FAQ answers open; the support calculator shows "10 hours · 120 hours a year" and £500 by default.

- [ ] **Step 8: Commit**

```bash
git add templates/pages/clubhouse.php templates/pages/hosting.php templates/pages/support.php templates/parts/tech-hero.php templates/parts/glass-card.php assets/js/public-widgets.js tests/marketing-clubhouse.spec.js tests/marketing-hosting.spec.js tests/marketing-support.spec.js
git commit -m "Add the ClubHouse, Hosting and Integrated Support pages"
```

---

### Task 9: Retarget links on the unchanged pages; delete the retired templates

**Files:**
- Modify: `templates/pages/home.php:179,261,440,500`, `templates/pages/ai.php:172`, `templates/pages/work.php:146`, `templates/pages/dashboard-orders.php:38`, `templates/pages/dashboard-subscriptions.php:44`, `templates/pages/dashboard-toolbox.php:56`
- Delete: `templates/pages/pricing.php`, `templates/pages/services.php`
- Test: `tests/marketing-home.spec.js`, `tests/marketing-ai.spec.js`, `tests/marketing-work.spec.js`, `tests/no-dead-controls.spec.js`, `tests/dashboard.spec.js`

Only hrefs change. No copy, no markup. (The dashboard link edits are the one exception to "don't touch the portal": a button pointing at a URL that now redirects is still a working button, but pointing it straight at `/support` saves the hop. Change the href string only.)

- [ ] **Step 1: Retarget**

- `home.php:179` "Get a Quote" → `home_url( '/contact' )`.
- `home.php:261` first service card (`Integrated Support`) → `home_url( '/support' )`. The second card (Digital Toolbox, ~line 277) stays `/toolbox`.
- `home.php:440` "View Guides" (feature tabs CTA) → `home_url( '/support' )`.
- `home.php:500` "Find Out More" → `home_url( '/support' )`.
- `ai.php:172` "Get a Quote" → `home_url( '/contact' )`.
- `work.php:146` "Our Services" → label stays as is? No: the design's Work hero has the same two buttons but the second now reads **"Our Services" → keep the label, point it at** `home_url( '/support' )`. (Design copy for the Work page is otherwise unchanged.)
- `dashboard-orders.php:38`, `dashboard-subscriptions.php:44`, `dashboard-toolbox.php:56` → `home_url( '/support' )`.

Then `git rm templates/pages/pricing.php templates/pages/services.php`. Grep once more: `grep -rn "'/pricing'\|'/services'\|blueworx_content_faqs\b" templates includes tests` must return nothing except the redirect map and the redirect spec.

- [ ] **Step 2: Run the affected specs**

Run: `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8882 WP_ADMIN_USER=admin WP_ADMIN_PASS='dsSyncPass123!' npx playwright test tests/marketing-home.spec.js tests/marketing-ai.spec.js tests/marketing-work.spec.js tests/no-dead-controls.spec.js tests/legacy-redirects.spec.js tests/dashboard.spec.js`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add -A templates/pages
git commit -m "Point the remaining Pricing and Services links at Support and Contact; remove the retired templates"
```

---

### Task 10: Full suite, version bump, changelog, lint, PR

**Files:**
- Modify: `bluegroup-project-blueworx.php` (header `Version:` and `BLUEWORX_SITE_VERSION` → `1.16.0`), `package.json` (`"version": "1.16.0"`), `readme.txt` (`Stable tag: 1.16.0`), `CHANGELOG.md`

- [ ] **Step 1: Bump the version in all four places**

- [ ] **Step 2: Add the changelog entry** (below the intro, above `## [1.15.0]`):

```markdown
## [1.16.0] - 2026-09-20

### Added

- **Three product pages: ClubHouse, Hosting and Integrated Support.** ClubHouse
  is the club website platform (nine modules, live demo, £20 a month). Hosting is
  managed WordPress hosting at £20 a month per site with free migration.
  Integrated Support replaces the old three retainer plans with nine packages
  from £100 a month, a slider that recommends one from the hours you use, and
  a full comparison table.
- **A currency switcher in the header.** Prices show in GBP, EUR or USD; the
  choice is remembered across pages. Rates are fixed for now.

### Changed

- **The menu.** Home · ClubHouse · Hosting · Support · Work · AI Powered, with
  Contact as the header button. About and the Journal moved to the footer;
  Toolbox left the menu (its page is still live).
- Pricing and Services now redirect to Support. The old pages are moved to the
  bin, not deleted.
- The "Get a Quote" buttons go to the contact form.

### Fixed

- FAQ answers on every page were collapsed to nothing when opened.
- The "Popular" badge on the highlighted plan was white on white.
```

- [ ] **Step 3: Run the whole suite**

Restart the local server (the version change triggers the page install on the first request), then:

Run: `PLAYWRIGHT_BASE_URL=http://127.0.0.1:8882 WP_ADMIN_USER=admin WP_ADMIN_PASS='dsSyncPass123!' npx playwright test`
Expected: 0 failed. Known flake: an occasional login timeout in the signed-in specs recovers on retry (see memory); judge on the final "failed" count. Any other failure is a regression from this branch — fix it, do not skip it.

- [ ] **Step 4: Lint once**

Run: `npm run lint`
Record the output verbatim for the hand-off message. Do not fix findings without the user's say-so.

- [ ] **Step 5: Commit and open the PR**

```bash
git add bluegroup-project-blueworx.php package.json readme.txt CHANGELOG.md
git commit -m "Bump to 1.16.0 and note the site restructure"
git push -u origin site-restructure
gh pr create --title "Site restructure: ClubHouse, Hosting, Support pages and the new menu" --body "$(cat <<'EOF'
Adds the ClubHouse, Hosting and Integrated Support pages, the new six-item menu with Contact as the header button, and a GBP/EUR/USD switcher. Pricing and Services redirect to Support. Home, Work, AI and Contact only had their links changed. The client dashboard is untouched.

Two live bugs fixed on the way: FAQ answers were collapsed when opened, and the "Popular" plan badge was invisible.

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

Hand-off message to the user: PR link, the lint findings (if any), and the one thing to decide — the currency rates are fixed (×1.17 / ×1.27) and need a real source before EUR/USD pricing is relied on.

---

## Self-review notes

- **Spec coverage.** Nav ✔ (T5), footer ✔ (T5), currency ✔ (T4/T5), ClubHouse ✔ (T6/T8), Hosting ✔ (T6/T8), Support ✔ (T6/T8), section rhythm/dividers ✔ (T2, `bw-divided`), deterministic grids ✔ (T2), FAQ fix ✔ (T2), Popular badge ✔ (T2), demo screenshots ✔ (T1), redirects ✔ (T7), link retargets ✔ (T9), version/changelog ✔ (T10). Dashboard and the custom contact form: intentionally excluded (Global Constraints). Home's "Support Guides" block keeps its current heading/copy (unchanged page; only the button target moves).
- **Names used consistently:** `blueworx_content_support_packages()`, `blueworx_content_support_faqs()`, `blueworx_content_hosting()`, `blueworx_content_clubhouse()`, `blueworx_commerce_sellable_plans()`, `blueworx_public_retire_removed_pages()`, `initCurrencySwitcher`, `initCurrencyPrices`, `paintPrices`, `money`, `initSupportCalc`, `window.blueworxMoney`, part vars `plan`, `cta_title/cta_copy/cta_primary/cta_secondary`, CSS `bw-g2/3/4`, `bw-divided`, `bw-card`, `bw-card-dark`, `bw-plan-aside`, `bw-plan-grid`, `bw-split-head`, `bw-range`, `bw-demo`, `bw-demo-stack`, `bw-demo-shot`, `bw-hero-tight`, `bw-cur*`.
- **Order of execution matters:** Tasks 7 and 8 must both be done before running the browser specs touched in Task 7 (the registry points at templates Task 8 creates).
