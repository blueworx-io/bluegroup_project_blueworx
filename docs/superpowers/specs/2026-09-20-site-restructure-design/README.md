# Handoff: BlueWorx Website Restructure (8 pages + client dashboard)

## Overview

A full restructure and redesign of the BlueWorx marketing site and client area. BlueWorx is a digital agency and platform selling three things: **ClubHouse** (a ready-made website platform for sports clubs and membership organisations), **Managed Hosting**, and **Integrated Support** (retainer hours). The site also has a **Work** portfolio, an **AI Powered** capability page, a **Contact** page, and a logged-in **Client Dashboard** prototype.

Eight page designs plus two shared layout components are included.

---

## About the Design Files

**The files in `designs/` are design references created in HTML — prototypes that show intended look and behaviour. They are not production code to copy directly.**

They are authored as "Design Components" (`.dc.html`): a template section plus a small JavaScript class that supplies data and handlers, rendered by a bundled runtime (`support.js`). The runtime is a harness for the prototype only. **Do not ship `support.js`, `image-slot.js`, or the `.dc.html` files.**

The task is to **recreate these designs in the target codebase's existing environment**, using its established patterns and libraries. The original source of truth is a WordPress plugin (see `github.md` in this bundle) that renders these pages from `templates/pages/*.php` and `templates/parts/*.php` against `assets/css/public.css`. If you are implementing into that plugin, the stylesheet in this bundle is the real one and can be used as-is. If you are implementing into a fresh React/Vue/Next app, treat `public.css` as the token and component-style reference and port it to your own styling layer.

### How to read a `.dc.html` file

- Everything between `<x-dc>` and `</x-dc>` is the markup. Ordinary HTML plus three custom constructs:
  - `{{ name }}` — a value supplied by `renderVals()` in the `<script data-dc-script>` block at the bottom of the file.
  - `<sc-for list="{{ items }}" as="item">…</sc-for>` — a loop (`map`).
  - `<sc-if value="{{ flag }}">…</sc-if>` — a conditional.
  - `<dc-import name="Site Nav">` — mounts the sibling `Site Nav.dc.html`.
- `<helmet>` holds `<link>`/`<style>`/`<script>` that belong in `<head>`. Page-specific CSS overrides live there — **read these, they contain real layout fixes** (grid track definitions, section dividers, responsive breakpoints).
- The `<script data-dc-script>` block at the bottom holds the data arrays (pricing, copy, FAQ content) and the interaction logic. This is where all real content lives.

---

## Fidelity

**High fidelity.** Final colours, typography, spacing, shadows, hover states and interactions. Recreate pixel-accurately. All values below are exact.

---

## Design Tokens

Defined in `designs/assets/css/public.css` (`:root` block). Everything else in the design derives from these.

### Colours

| Token | Value | Use |
| --- | --- | --- |
| `--ink` | `#0A0C29` | Body ink, headings, dark CTA fill |
| `--brand` | `#4F46E5` | Primary indigo — links, active nav, icons, eyebrows |
| `--brand-dk` | `#4338CA` | Hover indigo, gradient end |
| `--muted` | `#5B5D74` | Lead / secondary body copy |
| `--line` | `#ECEDF3` | Dividers (general) |
| `--surface` | `#F7F8FC` | Tinted section surface |
| — | `#EFEFF0` | Section hairline dividers, card borders, FAQ rules |
| — | `#06081F` | Dark hero background (`.tech-hero`) |
| — | `#E8E7F7` | Lavender accent — button hover fill |
| — | `#F5F6FF` | Lightest lavender surface |
| — | `#8B8EFF` / `#6D6AF0` / `#B7B9FF` / `#C9CBFF` | Decorative indigo ramp (blobs, dark-section accents, gradient text) |
| — | `#01D084` | Live/status green dot |
| — | `#4C4C4C` | Card body copy |

Gradients:
- Brand button / nav button: `linear-gradient(150deg, #5B52F0, #4338CA)`
- Gradient headline text (`.tech-grad`): `linear-gradient(96deg, #C9CBFF 0%, #6D6AF0 52%, #4F46E5 100%)`, clipped to text
- Decorative blob (`.blob`): `radial-gradient(circle at 30% 30%, #E0E1FF, #8B8EFF 45%, #6D6AF0 100%)`, `border-radius:50%`, `opacity` set per instance (.11–.55)

On dark backgrounds: heading `#fff`, body `rgba(226,228,255,.66)` or `rgba(255,255,255,.66)`, card fill `rgba(255,255,255,.04)`, card border `rgba(255,255,255,.1)`.

### Shadows

```
--sh-1     0 1px 2px rgba(10,12,41,.04), 0 1px 3px rgba(10,12,41,.03)
--sh-2     0 2px 4px rgba(10,12,41,.03), 0 8px 24px rgba(10,12,41,.06)
--sh-hover 0 4px 8px rgba(10,12,41,.04), 0 24px 48px rgba(79,70,229,.13)
--sh-dark  0 24px 60px rgba(0,0,0,.34)
```

### Typography

Self-hosted WOFF2, latin subset (`designs/assets/fonts/`, declared in `designs/assets/css/blueworx-fonts.css`):
- **Sora** 400 / 600 / 700 — UI and body
- **Inter** 400 / 500 / 600 — supporting UI

Stacks:
- Body: `'Sora', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`
- Display (`.h1`, `.h2`): `'Helvetica Neue', 'Sora', sans-serif` — Helvetica Neue is licensed and not bundled; it falls back to Sora. Decide up front whether to license it.
- Mono (eyebrows, badges, status lines): `'SF Mono', 'JetBrains Mono', ui-monospace, Menlo, monospace`

Base letter-spacing on `.bw-page` is `-.01em`. `text-rendering: optimizeLegibility`, antialiased.

| Style | Size | Weight | Line-height | Letter-spacing |
| --- | --- | --- | --- | --- |
| `.h1` | 66px | 700 | 1.04 | -2.4px |
| `.h1` (ClubHouse hero override) | 56px | 700 | 1.04 | -1.9px |
| `.h1` @≤1100px | 44px | 700 | 1.04 | -1.2px |
| `.h1` @≤700px | 36px | 700 | 1.04 | -0.9px |
| `.h2` | 47px | 700 | 1.1 | -1.6px |
| `.h2` small variant (inline) | 34px | 700 | 1.1 | -1.6px |
| `.lead` | 20px | 400 | 1.66 | -.01em |
| `.center-head .lead` | 18px | 400 | 1.66 | -.01em |
| Card `h3` | 19px | 600 | 1.2 | -0.3px |
| Card body | 15px | 400 | 1.55 | — |
| Dark card body | 14.5px | 400 | 1.6 | — |
| `.eyebrow` (mono) | 12px | 500 | 1 | .16em, uppercase |
| `.tech-badge` (mono) | 12px | 500 | 1 | .16em, uppercase |
| `.nav-tag` (mono) | 8.5px | 600 | 1 | .14em, uppercase |
| `.tech-status` (mono) | 12.5px | 400 | 1 | .04em |
| Nav link | 15px | 500 (600 active) | — | — |

`.h1, .h2, h3, h4 { text-wrap: balance }` and `p, .lead { text-wrap: pretty }` — keep these, they are what stops headline widows.

### Spacing & layout

- `--gut: 140px` — horizontal page gutter at desktop. Mobile nav drops to `24px`.
- Standard light section: `.sec { padding: 116px var(--gut); }` — **uniform vertical rhythm, do not vary it per section.**
- Dark hero: `.tech-hero { padding: 104px var(--gut) 92px; }`; `.pb-tall` variant `padding-bottom: 220px`.
- Nav height: `96px`, sticky, `z-index: 200`.
- Card grid gap: `20px`. Header-row gap: `24px`.
- `.wrap { max-width: 1440px; margin: 0 auto; }`
- Section dividers: adjacent light sections get `border-top: 1px solid #EFEFF0`. Rule as written is `main > div > .sec + .sec, main > div > .split + .sec`. **Note for reimplementation:** this sibling-selector approach is brittle — prefer a `has-divider` modifier class applied deliberately.

### Radii

| Element | Radius |
| --- | --- |
| Content cards | 16px |
| Glass card / mega panel | 18–20px |
| Buttons | 13px (`.btn`), 12px (`.nav-btn`, currency button) |
| Nav links | 10px |
| Small tiles / floats | 9–14px |
| Pills / badges / eyebrows | 100px |

### Transitions

- Buttons: `background .22s ease, color .22s ease, box-shadow .28s ease, transform .28s cubic-bezier(.2,.7,.2,1)`
- Nav links: `color .18s ease, background .18s ease`
- Sticky nav show/hide: `transform .38s cubic-bezier(.2,.7,.2,1)`
- Standard hover lift: `transform: translateY(-2px)` on `.btn`, `-1px` on `.nav-btn`

Keyframes (in `public.css`): `fadeUp`, `blobFloat` (12s), `thPulse` (2.4s, eyebrow/badge dot), `thFloat` (6s, floating hero chips), `thScan` (5.5s, glass-card scan line), `cbSpin` (9s, Claude badge spark).

---

## Component Library

These classes recur on every page. Build them once.

### Buttons — `.btn` + size + variant

Base: `inline-flex`, centred, `gap: 9px`, `border-radius: 13px`, `font-weight: 600`, `letter-spacing: -.01em`, `white-space: nowrap`. Inline SVG icons are `20×20`. Hover lifts `translateY(-2px)`.

Sizes: `.btn-lg` 62px tall / `0 38px` / 17px · `.btn-md` 54px / `0 30px` / 16px · `.btn-sm` 46px / `0 22px` / 15px.

| Variant | Rest | Hover |
| --- | --- | --- |
| `.btn-dark` | bg `#0A0C29`, white text, shadow `0 8px 22px rgba(10,12,41,.22)` | bg `#191c44`, shadow `0 14px 30px rgba(10,12,41,.28)` |
| `.btn-brand` | `linear-gradient(150deg,#5B52F0,#4338CA)`, white text, `0 8px 22px rgba(79,70,229,.32)` + `inset 0 1px 0 rgba(255,255,255,.18)` | shadow `0 16px 34px rgba(79,70,229,.44)` |
| `.btn-outline` | white bg, `inset 0 0 0 1.5px rgba(10,12,41,.16)`, ink text | border `#4F46E5`, text `#4338CA` |
| `.btn-outline-w` (on dark) | `rgba(255,255,255,.04)`, `inset 0 0 0 1.5px rgba(255,255,255,.32)`, white text | bg `rgba(255,255,255,.1)`, border `rgba(255,255,255,.72)` |
| `.btn-white` (on dark) | `#fff` bg, `#06081F` text | bg `#E8E7F7` |

### Eyebrow — `.eyebrow`

Inline-flex pill above section headings. `padding: 8px 15px`, bg `rgba(79,70,229,.07)`, border `1px solid rgba(79,70,229,.22)`, radius 100px, mono 12px/.16em uppercase, colour `#4F46E5`. A 7px indigo dot before the label, pulsing via `thPulse` (`box-shadow: 0 0 0 3px rgba(79,70,229,.35)` → `0 0 0 6px rgba(79,70,229,0)`). Dark-section override: bg `rgba(139,142,255,.09)`, border `rgba(139,142,255,.28)`, colour `#B7B9FF`. Always `margin-bottom: 20px`.

### Section header — `.center-head`

`max-width: 660px; margin: 0 auto; text-align: center`. Eyebrow → `h2.h2` → `p.lead` (18px, `margin-top:16px`). Standard `margin-bottom` before content: 36–44px.

Split header variant (used when a section has a right-aligned CTA): `display:flex; align-items:flex-end; justify-content:space-between; gap:24px; flex-wrap:wrap; margin-bottom:32–40px` — left column holds eyebrow + `h2` (`max-width:560px`), right holds a `.btn-outline.btn-md`.

### Card grids — `.bw-g2` / `.bw-g3` / `.bw-g4`

Deterministic column counts, never `auto-fit` — auto-fit produced orphan rows.

```css
.bw-g2,.bw-g3,.bw-g4 { display:grid; gap:20px; }
.bw-g2 { grid-template-columns: repeat(2, minmax(0,1fr)); }
.bw-g3 { grid-template-columns: repeat(3, minmax(0,1fr)); }
.bw-g4 { grid-template-columns: repeat(4, minmax(0,1fr)); }
@media (max-width:1100px) { .bw-g4 { grid-template-columns: repeat(2, minmax(0,1fr)); } }
@media (max-width:900px)  { .bw-g3 { grid-template-columns: repeat(2, minmax(0,1fr)); } }
@media (max-width:700px)  { .bw-g2,.bw-g3,.bw-g4 { grid-template-columns: minmax(0,1fr); } }
```

### Light card

`background:#fff; border:1px solid #EFEFF0; border-radius:16px; padding:28px 26px; box-shadow:0 1px 2px rgba(10,12,41,.04)`. Icon block `.svc-ic` with `margin-bottom:18px`, then `h3` 19px, then 15px body in `#4C4C4C`.

### Dark card (inside `.features-dark`)

`background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1); border-radius:16px; padding:26px 24px`. Mono tag 11px/.14em uppercase in `#A5A7FF`, then `h3` 19px white, then 14.5px body at `rgba(255,255,255,.66)`.

### Dark feature section — `.features-dark`

Full-bleed `#0A0C29` band, `position:relative; overflow:hidden` (the `overflow:hidden` is required — decorative `.blob` elements are positioned at negative offsets and would otherwise bleed into neighbouring sections). Contains a `.fd-header` (heading + `.fd-sub`) then a card grid.

### Glass card — `.glass-card`

Hero-side product mock. `border-radius:18px`, border `1px solid rgba(139,142,255,.22)`, `background: linear-gradient(180deg, rgba(30,33,74,.72), rgba(12,14,38,.72))`, `backdrop-filter: blur(8px)`, `box-shadow: 0 30px 70px rgba(0,0,0,.45)`, `overflow:hidden`. Inside: a `.gc-scan` animated light sweep, a `.gc-head` (three 9px dots + mono tag), stacked `.gc-metric` rows (`small` label 13px / `b` value 26px 700 / green delta 12.5px, separated by `1px solid rgba(255,255,255,.07)`), and a `.gc-spark` bar chart (46px tall, 5px gap, `border-radius:3px 3px 0 0`, `.hi` bar uses the indigo gradient).

Floating chips `.tech-float` are absolutely positioned at the card's corners (typical offsets `±22px` / `±26px`), animate with `thFloat`, and are staggered via `animation-delay`.

### Dark hero — `.tech-hero`

`background:#06081F`, `color:#fff`, `isolation:isolate`, `overflow:hidden`.
- `::before` — a 58×58px indigo grid (`linear-gradient(rgba(139,142,255,.09) 1px, transparent 1px)` in both axes) masked by `radial-gradient(ellipse 90% 75% at 50% 0%, #000 30%, transparent 78%)`.
- `::after` — a 1000×620px indigo glow, `top:-280px`, centred: `radial-gradient(ellipse at center, rgba(79,70,229,.55), rgba(79,70,229,0) 62%)`.
- Two-column layout `.tech-2col { display:grid; grid-template-columns:1fr 0.82fr; gap:56px; align-items:center }`, copy column capped at `560px` (840px on the AI page).
- `.tech-status` — a row of mono 12.5px items, each prefixed by a 5px `#01D084` dot with `0 0 8px rgba(1,208,132,.7)` glow.

### FAQ accordion — `details.faq-item`

Native `<details>`. `border-bottom: 1px solid #EFEFF0`. Summary marker hidden (`list-style:none` + `::-webkit-details-marker{display:none}`). Chevron rotates 180° when `[open]`. Answer padding `0 8px 26px`. **Answers must not be height-clipped** — use `max-height:none; overflow:visible` (a prior transition-based version collapsed them invisibly).

### Pricing plan card — `.plan-card` / `.plan-card.feat`

Two-part card: `.plan-top` (name row with a `.pop` "Popular" badge, `.plan-desc`, `.plan-price` with `<b>` amount + `<em>` period, then a full-width `.plan-btn`) and `.plan-feats` (an `INCLUDED` label, then `.pf` rows each with a 24px filled check `.ck`).

### Billing toggle — `.bill-toggle`

Two-button segmented control, `Monthly billing` / `Annual billing`, active button gets class `on`. Centred, `margin-bottom: 34px`.

---

## Shared Layout

### Site Nav (`designs/Site Nav.dc.html`)

96px tall, full width, sticky at `top:0`, `z-index:200`. `background: rgba(255,255,255,.62)` with `backdrop-filter: blur(22px) saturate(180%)`, bottom border `1px solid rgba(10,12,41,.07)`. Padding `0 var(--gut)`; at ≤1000px it becomes `0 24px` with `justify-content: space-between`.

Layout: logo (left) · links (centre, `flex:1`, `justify-content:center`, `gap:4px`) · CTA cluster (right).

- **Logo** — `assets/img/logo.png` at `height:34px`, intrinsic `170×34`.
- **Links** (in order): Home · ClubHouse · Hosting · Support · Work · AI Powered. Each `padding:9px 15px`, `border-radius:10px`, 15px/500. Hover: `#4F46E5` on `rgba(79,70,229,.07)`. Active: `#4F46E5` 600 on `rgba(79,70,229,.1)`. AI Powered carries a `.nav-tag.tag-light` "New" pill.
- **CTA cluster** `.nav-cta` — `gap:20px`, preceded by a `1px × 26px` divider (`rgba(10,12,41,.12)`) as `::before`. Contains: "Client Login" text link (15px/600) → "Contact" gradient button `.nav-btn` (46px tall, `0 22px`, radius 12px, arrow icon) → currency dropdown.
- **Currency switcher** `.bw-cur` — 46px button showing `£ GBP` with a chevron; opens a dark (`#0A0C29`) popover listing GBP / EUR / USD. Selected row gets `background: rgba(139,142,255,.18)`, text `#C9CBFF`.
- **Mobile** (≤1000px) — hamburger (44×44, `#F5F6FF` fill, `inset 0 0 0 1px #E4E5FF`; two bars, the second 13px wide and indigo, morphing into an X with a gradient background when `.open`) and a `.mobile-menu` panel. The currency switcher goes full-width inside the panel.
- **Scroll behaviour** — `nav.nav-scrolled` raises opacity to `.78` and adds `0 10px 34px rgba(10,12,41,.09)`; `nav.nav-hidden` translates `-100%` on scroll-down. Driven by `assets/js/public-nav.js`.

> **Implementation note:** in the prototype the nav is wrapped by the component runtime, so `.sc-host:has(> nav) { display: contents }` is used to keep `position: sticky` working. In a real app this wrapper does not exist — just make sure no ancestor of the sticky nav creates a scroll container or a transform/filter containing block.

### Site Footer (`designs/Site Footer.dc.html`)

Dark footer. Nav columns include **About** (which is deliberately not in the top nav). Currency is not repeated here.

---

## Screens

### 1. Home — `designs/BlueWorx Home.dc.html`

**Purpose:** agency positioning and routing to the three product pages.

Sections in order:
1. `.home-hero` — badge "Digital Agency & Platform", `h1` "We Design, Build & Grow **Digital Solutions**" (second line gradient), lead, CTA pair, hero imagery.
2. `.sec` (padding-bottom 0) — "What We Do" / "Two Services. Everything Your Business Needs Online." → `.svc2` two-up service cards.
3. `.sec` (padding-top 0) — split header "Selected Work" / "Recent Projects, Real Results" + `View All Work` outline button → work grid.
4. `.features-dark[data-widget="feature-tabs"]` — "One Platform. Every Tool. Real Results." Tabbed feature panel (JS in `assets/js/public-widgets.js`). Includes a "Support Guides" `.af-text` block (34px `h2`, 17px lead at `rgba(255,255,255,.66)`, brand button).
5. `.sec` (padding-bottom 80) — "How We Work" / "From First Conversation to Long-Term Partner" → `.proc-grid` numbered process steps.
6. `.split` (padding-top 20) — "Ongoing Partnership" / "Integrate smarter, collaborate better, and scale with BlueWorx" + `.collab-list` + image.
7. `.tbx` — "Do more with the BlueWorx Toolbox" tool logo grid (assets in `designs/assets/img/tools/`).
8. `.sec` — "What our clients say" / "Kind words from our customers" → `.tg` testimonial grid.
9. Full-bleed CTA band — "Ready to Build a Digital Solution That Wins?" with two decorative blobs.

### 2. ClubHouse — `designs/BlueWorx ClubHouse.dc.html`

**Purpose:** sell the club website platform. Product reference: `demo.305media.co.uk`.

1. `.tech-hero.tech-2col` — badge "ClubHouse · Club Website Platform"; `h1` **56px / -1.9px** "Every Sport. Every Member. **One Club Website.**" (the gradient phrase is `white-space:nowrap` and the size is tuned so it holds one line in the 560px column — keep both); lead (max 520px); `View Live Demo` (btn-white, opens demo in a new tab) + `Talk to Us` (btn-outline-w); status row "live in 2 weeks · hosting included · no setup fee". Right side: glass card "clubhouse · season 26/27" with metrics Members renewed **412**, Fixtures published **96**, Subs collected online **94%**, plus a sparkline; floating chips "Active members 1,240" (top-right) and "Next fixture Sat · 14:00" (bottom-left).
2. `.sec` — "What's Included" / "Every Part of Club Life, Already Built" / "Nine modules that cover how a club actually runs. Switch on what you need, leave the rest." → `.bw-g3` of nine module cards (Memberships, Teams & squads, Fixtures & results, plus six more — see `MODULES` in the file). Each card: stroke icon, 19px name, 15px description.
3. `.sec` — split header "See It Live" / "A Full Club Site, Running Today" + `Open the Demo` outline button. Below, the **demo band** `.bw-demo`:
   ```css
   .bw-demo { display:grid; grid-template-columns:2fr 1fr; gap:20px;
              align-items:stretch; grid-auto-rows:392px; }
   @media (max-width:900px){ .bw-demo{ grid-template-columns:minmax(0,1fr); grid-auto-rows:auto; } }
   ```
   Left: one large screenshot (club home page). Right: a stacked pair (Teams page, Membership page) in a nested grid — **its rows must be `minmax(0,1fr)`, not `1fr`**, or the images' intrinsic 3:2 aspect ratio inflates the rows past the 392px band and the content overlaps the next section.
4. `.features-dark` — "Members Do It Themselves. Committees Get Their Evenings Back." / "Joining, renewing, paying subs, booking a court, buying kit — all self-service, all online, all reconciled in one place." → `.bw-g4` of four dark cards.
5. `.sec` — "Pricing" / "One Price. The Whole Platform." / "Hosting, updates, and every ClubHouse module included. Cancel any time." Billing toggle, then a `.bw-g2` (gap 22px, max-width 960px, centred): the featured ClubHouse plan card + a supporting panel. **Price: £20/month or £200/year** ("per year, billed annually"). Included list in `INCLUDED` (all nine modules, managed hosting/SSL/daily backups, …).
6. Audience section (`AUDIENCES`) and FAQ (`FAQS`).

### 3. Integrated Support — `designs/BlueWorx Support.dc.html`

**Purpose:** sell retainer hour packages.

1. `.tech-hero.pb-tall`, centred, max 820px — badge "Integrated Support"; `h1` "Your Design & Development Team, **On Retainer**"; lead "Buy a block of hours each month and use them however your business needs: design, development, fixes, content, SEO, or a new landing page. All of the team, none of the admin."; centred status row.
2. `.sec` — "Find Your Level" / "How Many Hours Do You Need?" / "Slide to the support you use each month. We'll show the package that covers it." **Hours calculator:** a range input over the nine package indices (default index 4 = Growth). Moving it updates the recommended package name, blurb, monthly hours, annual allowance, effective hourly rate and monthly price live.
3. `.sec` — "All Packages" / "Nine Levels of Integrated Support" / "Hours are an annual allowance, drawn down whenever you need them. Move up or down a level as your workload changes." Comparison table: Package · Annual hours · Per month · Effective rate · Price.
4. `.features-dark` — what the hours can be spent on (`USES`: Design work, Development, Content, SEO & growth, Maintenance, Advice).
5. `.sec` — "How It Works" / "Support Without the Ticket Queue".
6. `.sec` — FAQ.

**Package data** (hours are the **annual** allowance; price is **per month** in GBP):

| Package | Annual hrs | Per month | £/mo |
| --- | --- | --- | --- |
| Starter | 24 | 2 | 100 |
| Launch | 48 | 4 | 200 |
| Scale | 72 | 6 | 300 |
| Enhance | 96 | 8 | 400 |
| Growth *(featured)* | 120 | 10 | 500 |
| Enterprise | 240 | 20 | 750 |
| Enterprise + | 360 | 30 | 1000 |
| Advantage | 480 | 40 | 1250 |
| Advantage + | 600 | 50 | 1500 |

Effective hourly rate is derived: `(gbp × 12) / hours`, formatted to 2dp. Featured cards on this page: Starter, Growth, Enterprise +.

### 4. Hosting — `designs/BlueWorx Hosting.dc.html`

1. `.tech-hero.tech-2col` — "Move My Site" (btn-white) + "See Pricing" (btn-outline-w, anchors to `#hosting-plans`).
2. `.sec` — "Performance" → `PERF` cards: 99.9% Uptime target · <200ms Server response · Global CDN included · Staging Safe changes · 24/7 Monitoring.
3. `.features-dark` — security (`SECURITY`): daily backups with 30-day history, free auto-renewing SSL, and more.
4. `.split` — "Migration" (free migration from current host).
5. `.sec#hosting-plans` — "Pricing" / "One Site. One Price. Everything In." / "No traffic tiers, no per-feature upsells, no renewal jump in year two." Billing toggle → **£20/month or £200/year**. Left: the Managed Hosting plan card (brand-filled, "Per site" badge). Right: a "Hosting is better with support" panel cross-selling Integrated Support from £100/month. **This section needs a top divider** — the preceding sibling is a `.split`, so the `.sec + .sec` rule does not reach it; the rule is extended to `.split + .sec`.
6. `.sec` — "Compare" table (`COMPARE`: BlueWorx vs. budget host vs. self-managed across managed updates, backups, support, server maintenance, total cost of ownership).
7. `.sec` — FAQ.

### 5. Work — `designs/BlueWorx Work.dc.html`

1. `.tech-hero.tech-2col` — badge "Selected Work"; `h1` "Work That Moves **the Needle**"; lead "Digital solutions we've designed, built, and grown alongside our partners, with the outcomes to show for it."
2. `.sec` — `.work-grid` of case-study cards (heading is visually hidden: `h2.bw-sr-only` "Selected work").
3. `.stats-band` — dark results band with a decorative blob.
4. `.sec` — "What Our Clients Say" / "Partners Who'd Recommend Us".

### 6. AI Powered — `designs/BlueWorx AI Powered.dc.html`

1. `.tech-hero.ai-hero` — a `.claude-badge` ("Powered by Claude", Anthropic) rendered in warm terracotta (`#E7936B` / `#F3A582`, border `rgba(217,119,87,.38)`, gradient fill `rgba(217,119,87,.14)` → `.06`) with a slowly rotating spark. `h1` "From Prompt to Production — **Built by AI**, Shipped by Experts". Copy column widened to 840px (lead stays 560px).
2. `.sec` — "The Full Flow" / "From Brief to Deploy, One Continuous Flow" → `.ai-pipe` pipeline.
3. `.features-dark` — "Model Guidance" / "The Right Claude Model for Every Task" → `.ai-models`.
4. `.sec` — "Approved Stack" / "Built on Tools We Trust" → `.ai-stack` logo grid (tool logos in project root: `elementor.png`, `surecart.png`, `sureforms.png`, `surerank.png`, `suremail.png`, `surewriter.png`, `zipwp.png`, `ottokit.png`, `sweet-ai.png`, `ally.png`, `equalize-a11y-checker.png`, `elementor-ai-planner.png`).
5. `.features-dark` — "What We Build" / "AI Offerings, Built for Real Businesses" → `.ai-off-grid`.

The terracotta Claude badge is the **only** place warm colour appears. Keep it isolated.

### 7. Contact — `designs/BlueWorx Contact.dc.html`

1. `.tech-hero`, centred, max 780px, `padding-bottom:72px` — badge "Contact Us"; `h1` "Start Your Conversation **With BlueWorx**"; lead "Tell us where your digital presence is holding you back and we'll show you exactly how to fix it."
2. `section` (`padding:56px 0 0`) — `.contact-grid`: the `.contact-form` beside contact detail. The form includes **selectable budget chips** (single-select; the selected chip takes brand fill).
3. `.contact-cards` — three ways-to-reach-us cards on a tinted band with a blob (`h2.bw-sr-only` "Ways to reach us").
4. `.sec` — FAQ.
5. `.sec` — "What our clients say" / "Kind words from our customers".

**Form validation is not implemented in the prototype.** Implement: required name, email (format-checked), message; optional company and phone; budget chips optional single-select; inline error text below each field using `#FF302F`; disable submit while in flight; success state replacing the form body.

### 8. Client Dashboard — `designs/BlueWorx Dashboard.dc.html`

A single-page logged-in client area. Root carries `.bw-page.bw-dashboard` (which disables the marketing sticky-nav rule). Layout: fixed left sidebar + scrolling main.

- **Sidebar** `.dash-side` (252px) — `.dash-brand` (a square `B` mark + "BlueWorx" wordmark), then `.dash-nav` with grouped sections (each optional group renders a `.dash-navlabel` heading). Items are buttons, not links — navigation is client-side view state.
- **Top bar** `.dash-top` — page `h1.dash-title` + `.dash-kicker` subtitle on the left; "New request" `.dash-cta` button; and a **collapsible user pill** `.bw-user` on the right (40px tall, white, `1px solid var(--dash-line)`, radius 11px; avatar initials `DO`, name "Daniel Okafor" 13.5px/600, sub-line 11.5px muted truncated at 150px, chevron rotating 180° when open). Its menu is a 220px white popover (radius 14px, `0 20px 44px` shadow) holding the account links that used to live in the sidebar foot.
- **Views** (mutually exclusive, driven by `isOverview` / `isPackages` / etc.):
  - **Overview** — `.dash-welcome` ("Welcome back, Daniel" + "Everything BlueWorx is running for you, in one place." + a `3 active plans` tier pill); a `.dash-split` pairing a **Support hours** card with a dark **Next payment** card (£540.00, "Invoice BW-2098 is due on 1 October 2026.", `Pay now` filled button + `See all invoices` text button); then a `.dash-tiles` row of five quick-link tiles.
  - **Packages** — one `.dash-card` per active package with a status chip.
  - **Websites** — `.dash-sites` grid, **3 columns fixed** (`repeat(3, minmax(0,1fr))`, single column ≤640px), each card ~195px tall. Domain text uses `overflow-wrap: anywhere` so long URLs wrap rather than overflow.
  - **Support / requests** — ticket rows.
  - **Invoices** — table with a pay action.
  - **Documents**.
- **Grid behaviour** — `.dash-split` is `repeat(auto-fit, minmax(320px,1fr))` with `align-items:stretch`; `.dash-tiles` is `repeat(auto-fit, minmax(230px,1fr))`.
- **Card padding** — `.dash-card` has no padding of its own; padding lives on its known children. Custom content is wrapped in `.bw-cb` (`padding:18px 22px 22px; display:flex; flex-direction:column; gap:14px; align-items:flex-start`) so it matches. Row lists use `.bw-rows` with `align-items:stretch`, and the last row drops its bottom border and padding.
- **Data is realistic placeholder data.** Replace with real API responses.

---

## Interactions & Behaviour

| Interaction | Behaviour |
| --- | --- |
| Sticky nav | Adds `.nav-scrolled` past a scroll threshold (higher opacity + shadow); adds `.nav-hidden` (`translateY(-100%)`) when scrolling down, removes when scrolling up. `assets/js/public-nav.js`. |
| Mobile menu | Hamburger toggles `.open` on itself and on `.mobile-menu`. Bars morph into an X. |
| Dropdowns (mega panel, About, currency, user pill) | Toggled by an `.open` class, and also revealed on `:focus-within` so keyboard users can reach them without JS. Panels are always in the DOM and hidden with CSS. |
| Currency switcher | Writes `bw-currency` (`GBP`/`EUR`/`USD`) to `localStorage` and dispatches a `bw:currency` window event. Every page listens and re-renders prices. Rates are hardcoded: GBP ×1, EUR ×1.17, USD ×1.27. **Replace with live rates or server-side pricing before shipping.** |
| Billing toggle (ClubHouse, Hosting) | Local boolean; swaps price and period label. Not persisted. |
| Support hours calculator | Range input over indices 0–8, default 4. Updates recommendation live on `input`. |
| Contact budget chips | Single-select. |
| FAQ | Native `<details>`/`<summary>`; chevron rotates when `[open]`. |
| Dashboard nav | Client-side view state; no routing in the prototype — **add real routes.** |
| Hover states | Documented per component above. There are no opacity fades; colour swap plus a small `translateY` lift is the house style. |

## State Management

Marketing pages need almost none. What exists:

| State | Scope | Persistence |
| --- | --- | --- |
| `currency` (`'GBP' \| 'EUR' \| 'USD'`) | Global / app-level | `localStorage['bw-currency']`, broadcast via a `bw:currency` event |
| `annual` (boolean) | Per pricing page | none |
| `calculatorIndex` (0–8) | Support page | none |
| `budget` (string \| null) | Contact form | none |
| `navScrolled` / `navHidden` | Nav | none |
| `mobileMenuOpen`, `currencyMenuOpen`, `userMenuOpen` | Nav / dashboard | none |
| `dashboardView` | Dashboard | should become a route |

Data fetching needed for a real build: package and hosting pricing, work case studies, testimonials, FAQ content, and every dashboard collection (packages, sites, hours ledger, requests, invoices, documents). Contact form needs a POST endpoint.

## Responsive Behaviour

| Breakpoint | Change |
| --- | --- |
| ≤1100px | `.bw-g4` → 2 columns; `.h1` → 44px / -1.2px |
| ≤1000px | Nav gutter → 24px, links collapse into the hamburger menu, currency moves into the mobile panel |
| ≤900px | `.bw-g3` → 2 columns; `.bw-demo` → single column with auto rows |
| ≤700px | All card grids → 1 column; `.h1` → 36px / -0.9px |
| ≤640px | `.dash-sites` → 1 column |

Gutters remain `--gut: 140px` until the nav breakpoint — check this at 1024–1280px in your implementation and consider a fluid `clamp()` gutter instead.

## Accessibility

Already handled in the designs; preserve it:
- `.bw-sr-only` / `.screen-reader-text` for visually hidden headings, so heading order has no gaps. Uses `clip-path: inset(50%)` (not `display:none`) so it stays in the a11y tree.
- A visible-on-focus skip link (`.screen-reader-text:focus`) landing on `main#content`, which carries `tabindex="-1"` and `outline:none` on `:focus`.
- Dropdowns open on `:focus-within`, not only on click.
- `aria-haspopup` / `aria-expanded` on the currency and user menu triggers; `aria-label="Client area"` on the dashboard nav.

Still to do: focus-visible rings on buttons and cards, `prefers-reduced-motion` handling for the six looping keyframe animations, and form error announcement on Contact.

## Assets

All under `designs/assets/` unless noted.

| Asset | Notes |
| --- | --- |
| `img/logo.png` | BlueWorx wordmark, rendered at 34px tall (170×34) |
| `img/hero-image.webp` | Home hero photography |
| `img/feature-image-1..4.jpg` | Feature and work-card imagery |
| `img/fig-collab.jpg` | "Ongoing Partnership" split image |
| `img/tools/*` | Toolbox logo grid |
| `fonts/sora-400|600|700.woff2`, `fonts/inter-400|500|600.woff2` | Self-hosted, latin subset |
| `css/blueworx-fonts.css` | `@font-face` declarations |
| `css/public.css` | **The real stylesheet (1298 lines).** Scoped to `.bw-page`. |
| `js/public-nav.js` | Sticky nav, mobile menu, dropdown toggles |
| `js/public-widgets.js` | Home feature tabs, AI page widgets |
| Tool logos (project root) | `elementor.png`, `elementor-ai-planner.png`, `surecart.png`, `sureforms.png`, `suremail.png`, `surerank.png`, `surewriter.png`, `zipwp.png`, `ottokit.png`, `sweet-ai.png/.webp`, `ally.png/.webp`, `equalize-a11y-checker.png` |

**Image placeholders:** the ClubHouse demo band uses `<image-slot>` elements (`ch-demo-home`, `ch-demo-teams`, `ch-demo-membership`) — these are prototype-only drop targets. Replace with three real screenshots of the club demo site at `demo.305media.co.uk`. Target render sizes at a 1440px canvas: large slot ~700×392, each stacked slot ~330×186.

**Helvetica Neue is not bundled** and is not free. Either license it for the display face or ship Sora 700 for `.h1`/`.h2` (the current fallback) and re-check the tracking values, which were tuned for Helvetica Neue.

## Files

```
design_handoff_blueworx_site/
├── README.md                      this document
├── github.md                      source repo, branch, and screen → repo-file map
└── designs/
    ├── BlueWorx Home.dc.html
    ├── BlueWorx ClubHouse.dc.html
    ├── BlueWorx Support.dc.html
    ├── BlueWorx Hosting.dc.html
    ├── BlueWorx Work.dc.html
    ├── BlueWorx AI Powered.dc.html
    ├── BlueWorx Contact.dc.html
    ├── BlueWorx Dashboard.dc.html
    ├── Site Nav.dc.html           shared header, imported by every page
    ├── Site Footer.dc.html        shared footer, imported by every page
    ├── support.js                 prototype runtime — DO NOT SHIP
    ├── image-slot.js              prototype image placeholder — DO NOT SHIP
    └── assets/                    css, fonts, img, js (ship these)
```

Open any `.dc.html` directly in a browser to see the design running. `github.md` maps each screen back to the `templates/pages/*.php` and `templates/parts/*.php` files it was built from, which is the fastest route if you are implementing back into the original WordPress plugin.
