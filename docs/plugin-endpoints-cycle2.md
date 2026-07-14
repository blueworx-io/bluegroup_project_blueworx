# Cycle 2 — plugin-side deliverables (for `blueworx_labs_wordpress`)

The front-end portal (Cycle 2) is built against the plugin's existing auth,
account, and SureCart-proxy endpoints (see `HEADLESS_INTEGRATION.md`). Two items
are **plugin-side work**, tracked here so the front-end isn't blocked.

## 1. `POST /blueworx/v1/contact` (new, public)

The contact form currently forwards server-side via `CONTACT_FORWARD_URL`. To
give the CMS a single-origin contact endpoint, add:

- **Route:** `POST /blueworx/v1/contact`, auth `none`, rate-limited (reuse the
  account rate-limiter, e.g. 10/hour/IP).
- **Body:** `{ firstName, lastName, email, phone, countryCode, message }`
  (matches `docs/API_CONTRACT.md` §4).
- **Success:** `200 { ok: true, id? }`. **Validation error:** `400 { ok: false,
  errors: Record<string,string> }`. Upstream failure → the standard error envelope.
- **Action:** persist and/or email (SureForms entry, `wp_mail`, or a webhook).
- **Front-end wiring:** none — once live, set `CONTACT_FORWARD_URL` in Netlify to
  `https://<cms>/wp-json/blueworx/v1/contact`. `app/api/contact/route.ts` already
  forwards to it.

## 2. (Deferred) SureCart normalization

The front-end maps SureCart's raw `/surecart/me/*` shapes to its
`Subscription`/`Invoice` types in `lib/api/surecart.ts`. If those raw field names
prove unstable, the clean fix is to normalize plugin-side into the
`API_CONTRACT.md` §5.2/§5.3 shapes (guide §9 / §10 default) and retire the
front-end mappers. Not required for Cycle 2.

## 3. (Deferred) `/portal/me` for bespoke project data

Sites, hours, onboarding, tickets, team, partner, activity, and time-log data has
no endpoint and is not in SureCart. The front-end shows labelled demo data for
these this cycle. A future cycle adds a normalized per-user endpoint (modelled in
WP via user-meta/options, **not** CPTs) — design TBD with the CMS team.
