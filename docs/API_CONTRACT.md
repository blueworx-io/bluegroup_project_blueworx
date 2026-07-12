# BlueWorx Headless API Contract

This document is the **coordination spec between two repos**:

- **This repo** (`bluegroup_project_blueworx`) — the headless Next.js front‑end.
- **The BlueWorx WordPress plugin** (separate repo, in progress) — exposes the REST API this
  front‑end consumes, and wraps **SureCart** for all subscription/billing data.

The front‑end has been built so that every dynamic value flows through the data‑access layer in
[`lib/api/`](../lib/api). Today those functions return the mock data captured below. When the plugin
is live, only the bodies of those functions change — the shapes in this document are the guarantee
the plugin must satisfy so **nothing in the UI has to change**.

> **Rule of thumb:** if a field appears in a TypeScript type in `lib/api/`, the plugin must return it
> with that exact key and a compatible type. Additive fields are fine; renaming or dropping a field
> is a breaking change and must be coordinated.

---

## 1. Conventions

| Concern | Decision |
| --- | --- |
| Base URL | `NEXT_PUBLIC_WP_API_URL` (e.g. `https://cms.blueworx.io/wp-json/blueworx/v1`) |
| Auth (content) | None — content endpoints are public, cached at build time |
| Auth (portal) | Bearer token / session cookie tied to a SureCart customer — see §5 |
| Format | JSON, UTF‑8. Money as **strings already formatted for display** where the mock does so (e.g. `"$490"`, `"$938.00"`), because the design renders them verbatim. See §6 for the money note. |
| Dates | Strings as they should display (e.g. `"Apr 1, 2026"`). The plugin owns formatting/localisation. |
| Errors | Non‑2xx with `{ "error": { "code": string, "message": string } }`. The front‑end degrades gracefully (see §7). |
| Versioning | Namespaced route (`/blueworx/v1`). Breaking changes bump to `/v2`. |

---

## 2. Source map — where each piece of UI data comes from

| UI area | Data | Source | Contract section |
| --- | --- | --- | --- |
| Toolbox grid, tool detail, nav mega‑menu, savings calc | Tools catalogue | **WP content** (CPT) | §3.1 |
| Toolbox plans / retainer plans | Pricing plans | **WP content** (or SureCart products) | §3.2 |
| FAQs, testimonials | Marketing content | **WP content** | §3.3 |
| Contact page | Form submission | **WP / SureForms** endpoint | §4 |
| Portal → Subscriptions, Invoices, "Next invoice" | Billing | **SureCart** (via plugin) | §5.2, §5.3 |
| Portal → Websites, Hours, Onboarding, Activity, Time log, Support tickets, Team | Account/project data | **Custom plugin** (CPTs / meta) | §5.4–§5.9 |
| Portal → Partner portal | Partner/commission data | **Custom plugin** | §5.10 |
| Portal identity | Logged‑in client profile | **Plugin auth + SureCart customer** | §5.1 |

---

## 3. Public content endpoints

### 3.1 `GET /tools` → `Tool[]`

Drives the toolbox grid, the tool detail pages (`/toolbox/[slug]`), the nav mega‑menu, and the
savings calculator. Matches `Tool` in [`lib/data.ts`](../lib/data.ts).

```ts
type ToolFeature = { icon: string; title: string; desc: string };

type Tool = {
  slug: string;        // URL slug, also drives generateStaticParams — must be stable
  name: string;
  desc: string;        // one-line summary (grid + nav)
  domain: string;      // used to fetch a favicon (see faviconUrl note below)
  category: string;    // "Build" | "Grow" | "Sell" | "Automate" | "Support" (free-form, drives filters)
  popular?: boolean;   // flags the "Popular" tag in the mega-menu
  tagline: string;     // hero line on the detail page
  features: ToolFeature[]; // icon keys must exist in lib/icons.ts (see §8)
  soloPrice?: number;  // NEW: fold SOLO_PRICES into the tool (see note)
};
```

**Notes**

- `icon` values must be one of the keys the front‑end knows (`lib/icons.ts`). If the plugin needs a
  new icon, it must be added to `lib/icons.ts` in this repo first (see §8).
- `SOLO_PRICES` in the current mock is a separate `slug → number` map used by the savings calculator.
  Preferred contract: **return `soloPrice` on each tool** so there is one source of truth. If that is
  impractical plugin‑side, expose `GET /tools/solo-prices` → `Record<slug, number>` instead and note
  it here.
- `faviconUrl(domain)` is computed **client‑side** today via Google's favicon service. The plugin does
  not need to return icon images; returning `domain` is enough. If brand icons should be
  self‑hosted later, add `iconUrl?: string` to `Tool` and the front‑end will prefer it.

### 3.2 `GET /plans` → `{ toolbox: Plan[]; retainers: Plan[] }`

Matches `Plan` in [`lib/data.ts`](../lib/data.ts). Drives `/toolbox` (toolbox plans) and `/pricing`
(retainer plans).

```ts
type Plan = {
  name: string;
  desc: string;
  priceM: number;      // monthly price
  priceA: number;      // annual (per-month equivalent) price
  feat: boolean;       // "featured" styling
  pop?: boolean;       // "Most popular" ribbon
  btn: string;         // CSS class string for the CTA button ("plan-btn out" | "plan-btn dark")
  features: string[];  // bullet list
};
```

> `btn` is presentation, not data. Acceptable for the plugin to omit it and let the front‑end derive
> it from `feat`/`pop`; if omitted, add that defaulting in `lib/api/content.ts`. Flag during
> integration.

### 3.3 `GET /faqs` → `Faq[]` and `GET /testimonials` → `Testimonial[]`

```ts
type Faq = { q: string; a: string };

type Testimonial = { text: string; initials: string; name: string; role: string };
```

---

## 4. Contact form — `POST /contact`

Backs [`components/ContactForm.tsx`](../components/ContactForm.tsx). The front‑end validates
client‑side, then POSTs to its own route handler (`/api/contact`), which forwards to this endpoint
(keeps any API key server‑side). See [`app/api/contact/route.ts`](../app/api/contact/route.ts).

**Request body**

```ts
type ContactSubmission = {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  countryCode: string; // "US" | "UK" | "AU" (from the select)
  message: string;
};
```

**Response**

- `200` → `{ "ok": true, "id"?: string }`
- `400` → `{ "ok": false, "errors": Record<string, string> }` (server‑side field errors)
- `5xx` → generic failure; the form shows a retry message.

Until the plugin exists, `/api/contact` validates and returns `{ ok: true }` without forwarding
(controlled by `CONTACT_FORWARD_URL` being unset — see `.env.example`).

---

## 5. Portal endpoints (authenticated)

All of §5 requires an authenticated session (see §5.1). Backs
[`components/Portal.tsx`](../components/Portal.tsx). A single aggregate call is preferred to avoid a
waterfall:

### `GET /portal/me` → `PortalData`

Returns everything the portal renders for the current client. Sub‑shapes below map 1:1 to the mock
consts currently in `Portal.tsx`.

### 5.1 `client` — profile / identity

```ts
type PortalClient = {
  name: string; first: string; company: string; initials: string; tier: string;
};
```

### 5.2 `subscriptions` — **SureCart**

```ts
type Subscription = {
  name: string;      // product/plan name
  site: string;      // which site it applies to, or "All sites"
  price: string;     // display string, e.g. "$490"
  cycle: string;     // "/mo" | "/yr"
  status: string;    // "Active" | "Trial ends soon" | "Past due" | "Cancelled"
  renews: string;    // display date, e.g. "Apr 1, 2026"
  icon: string;      // icon key (§8)
};
```
Maps to SureCart subscriptions for the customer. `status` strings drive the status chip; keep them
stable or extend the mapping in `Portal.tsx`.

### 5.3 `invoices` — **SureCart**

```ts
type Invoice = {
  id: string;       // "INV-2026-014"
  date: string;     // "Mar 1, 2026"
  amount: string;   // "$938.00"
  status: string;   // "Paid" | "Due" | "Overdue"
  url?: string;     // NEW: hosted invoice PDF/download link (front-end will link it if present)
};
```

### 5.4 `sites` — custom plugin

```ts
type PortalSite = {
  label: string; url: string; platform: string;
  status: string;   // "Live" | "Staging" | ...
  uptime: string; ssl: string; plan: string; visits: string;
  shot: string;     // screenshot URL/path (currently /assets/*)
  dot: string;      // status colour hex
};
```

### 5.5 `packages` — retained hours

```ts
type HoursPackage = { name: string; used: number; total: number; period: string; color: string };
```
Front‑end derives `used/total hrs`, `remaining`, and `%` from these — return raw numbers.

### 5.6 `onboarding` — project pipeline

```ts
type OnboardingDoc = { name: string; hint: string; state: "received" | "pending" };
type OnboardingStep = { title: string; desc: string; state: "done" | "current" | "waiting" | "todo" };
type OnboardingProject = {
  name: string; type: string; stage: string;
  steps: OnboardingStep[];
  docs: OnboardingDoc[];
  milestone: { label: string; date: string; who: string };
};
```

### 5.7 `activity` + `timeLog`

```ts
type ActivityItem = { icon: string; text: string; time: string };
type TimeLogEntry = { date: string; task: string; who: string; hrs: string };
```

### 5.8 `tickets` — support

```ts
type Ticket = { title: string; ref: string; time: string; status: string };
```
(The mock also carries `chipCls`; that is presentation and is derived front‑end from `status`.)

### 5.9 `team` + `platform` + `stats`

```ts
type TeamMember = { initial: string; name: string; role: string };
type PlatformFact = { icon: string; label: string; value: string };
type PortalStat = { icon: string; value: string; label: string };
```

### 5.10 `partner` — partner portal

```ts
type PartnerTier = { key: string; name: string; rate: number; desc: string; req: string };
type PartnerBrand = { name: string; mult: number };
type PartnerEarner = { name: string; type: string; refs: string; mrr: string; month: string; status: string };

type PartnerData = {
  tiers: PartnerTier[];
  brands: PartnerBrand[];
  earners: PartnerEarner[];
};
```

### PortalData (aggregate)

```ts
type PortalData = {
  client: PortalClient;
  stats: PortalStat[];
  platform: PlatformFact[];
  sites: PortalSite[];
  subscriptions: Subscription[];
  invoices: Invoice[];
  packages: HoursPackage[];
  onboarding: OnboardingProject[];
  activity: ActivityItem[];
  timeLog: TimeLogEntry[];
  tickets: Ticket[];
  team: TeamMember[];
  learning: { icon: string; title: string; meta: string }[];
  partner: PartnerData;
};
```

---

## 6. Money & formatting note

The design renders money as pre‑formatted display strings. The **plugin owns formatting** for now,
so the front‑end stays presentation‑free. If we later need currency switching or arithmetic on these
values (e.g. summing invoices), we should migrate to `{ amountCents: number, currency: string }` and
format front‑end. Track that as a follow‑up before it becomes load‑bearing.

## 7. Failure behaviour

- **Content endpoints** (§3): on failure the front‑end falls back to the last successful build's data
  (content is fetched at build time). A failed build should fail loudly in CI, not ship empty pages.
- **Portal** (§5): on failure show the portal shell with an inline "couldn't load your account" state;
  never render another client's data. A `401` redirects to sign‑in (§5.1).

## 8. Shared enums the plugin must respect

- **Icon keys**: any `icon` field must be a key in [`lib/icons.ts`](../lib/icons.ts). Current keys:
  see that file. Adding an icon is a front‑end change in this repo, coordinated via PR.
- **Status strings** map to chip styles in `Portal.tsx`. Reuse existing strings where possible; new
  ones need a matching branch added front‑end.

---

## 9. Open questions for the plugin team

1. Will subscriptions/invoices come straight from SureCart's REST API proxied by the plugin, or be
   normalised into the plugin's own endpoints? (Front‑end doesn't care as long as §5.2/§5.3 shapes hold.)
2. Auth mechanism: SureCart customer portal session cookie, or a bearer token minted by the plugin?
   This decides the `getSession()` implementation in [`lib/auth.ts`](../lib/auth.ts).
3. Are toolbox/retainer **plans** SureCart products (so pricing is one source of truth), or WP content?
4. Should `soloPrice` live on the tool object (preferred) or a separate endpoint?

## 10. Recommended defaults (front-end team)

These are the front-end's recommended answers to §9. They are **defaults, not decisions** — the
plugin team can override any of them, but building to these keeps the current front-end unchanged.

1. **Normalise, don't proxy.** Have the plugin map SureCart into the §5.2 / §5.3 shapes rather than
   forwarding raw SureCart JSON. This insulates the front-end from SureCart API changes and matches
   the "plugin owns formatting" stance in §6. (Answers Q1.)
2. **Session cookie for portal auth.** An `httpOnly`, `SameSite` session cookie tied to the SureCart
   customer, read server-side by `getSession()` in [`lib/auth.ts`](../lib/auth.ts). It's the simplest
   secure option for a same-origin headless portal and needs no token handling in the client. Use a
   bearer token only if the portal ends up on a different origin from the API. (Answers Q2.)
3. **SureCart products are the source of truth for price.** Surface toolbox/retainer plans through
   `GET /plans` but drive their prices from SureCart products, so marketing pricing and checkout can
   never drift apart. WP content owns only the copy (name, desc, feature bullets). (Answers Q3.)
4. **`soloPrice` on each tool.** Return it on the `Tool` object (the field is already reserved in
   §3.1) so there is one source of truth and the separate `SOLO_PRICES` map can retire. Until that
   lands, `tests/fixtures-parity.spec.ts` guards that the tools list and `SOLO_PRICES` stay in
   lockstep. (Answers Q4.)
