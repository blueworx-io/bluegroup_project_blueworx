# BlueWorx CMS Content Model (Cycle 1)

The headless front-end maps **from** these WordPress CPTs + ACF fields into its
marketing types. The CMS must register each CPT with `show_in_rest: true` and list
its key under **BlueWorx → Headless → CPTs in REST** (plugin HEADLESS_INTEGRATION.md
§2/§6.4). Until these exist, the front-end serves static fallback data — so CMS and
front-end work can land independently.

Post **title** = display name; post **slug** = URL slug, unless noted.
`icon` values must be one of: chat, mail, chart, clock, sms, doc, server, users,
plug, book, cart, calendar, phone, sparkles, code, zap, git, palette, workflow,
gauge, shield (front-end `lib/icons.ts`; adding one is a front-end PR first).

## `bw_tool` → Tool
- title = `name`, slug = `slug`
- ACF: `desc` (text), `domain` (text), `category` (select: Build|Grow|Sell|Automate|Support),
  `popular` (true/false), `tagline` (textarea), `solo_price` (number),
  `features` (repeater of `{ icon (select from icon keys), title (text), desc (textarea) }`)

## `bw_plan` → Plan
- title = `name`
- ACF: `plan_group` (select: toolbox|retainer), `desc` (textarea),
  `price_monthly` (number), `price_annual` (number),
  `featured` (true/false), `popular` (true/false),
  `features` (repeater OR textarea — one bullet per line)
- `btn` is NOT stored (derived front-end from `featured`)

## `bw_faq` → Faq
- title = `q` (question)
- ACF: `answer` (wysiwyg/textarea) — falls back to post content if empty

## `bw_testimonial` → Testimonial
- title = author `name`
- ACF: `quote` (textarea → `text`), `role` (text)
- `initials` is NOT stored (derived front-end from `name`)

Mapping is implemented in `lib/api/mappers.ts`; the fetch/fallback orchestration in
`lib/api/content.ts`. If a field is renamed or dropped CMS-side, update both.
