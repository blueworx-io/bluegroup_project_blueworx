// Central runtime configuration for the headless front-end.
//
// Every integration point with the (in-progress) BlueWorx WordPress plugin and
// SureCart is read from the environment here, so there is exactly one place to
// look when wiring the real API. See docs/API_CONTRACT.md and .env.example.
//
// Until the plugin is live, `WP_API_URL` is unset and the data-access layer in
// lib/api/* serves the captured mock data. The moment the env var is populated,
// those functions switch to real fetches with no UI changes.

/** Base URL of the plugin's REST API, e.g. https://cms.blueworx.io/wp-json/blueworx/v1 */
const WP_API_URL = process.env.NEXT_PUBLIC_WP_API_URL?.replace(/\/$/, "") || "";

export const config = {
  /** Public base URL for the plugin REST API. Empty string means "not configured yet". */
  wpApiUrl: WP_API_URL,

  /** Server-only token for authenticated plugin/content requests (never exposed to the client). */
  wpApiToken: process.env.WP_API_TOKEN || "",

  /** Server-only SureCart API token, used by the plugin proxy or (later) direct calls. */
  surecartApiToken: process.env.SURECART_API_TOKEN || "",

  /** Where /api/contact forwards validated submissions. Empty means "accept but don't forward yet". */
  contactForwardUrl: process.env.CONTACT_FORWARD_URL || "",

  /**
   * When true, the portal enforces a real authenticated session and redirects
   * unauthenticated visitors to sign in. Defaults to false so the demo portal
   * keeps rendering the mock client until the plugin's auth is wired.
   */
  portalRequireAuth: process.env.PORTAL_REQUIRE_AUTH === "true",
} as const;

/**
 * True while there is no live API to talk to. The data-access layer uses this to
 * decide between mock data and real fetches. This is the single kill-switch that
 * flips the whole site from mock to live once the plugin ships.
 */
export const useMockData = config.wpApiUrl === "";
