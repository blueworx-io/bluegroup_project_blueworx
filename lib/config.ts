// Central runtime configuration for the headless front-end.
//
// Integration points with the BlueWorx WordPress plugin derive from a single origin
// env var. The plugin exposes two REST bases:
//   blueworx/v1 — auth, menus, site, resolve, acf-options, surecart proxy
//   wp/v2       — the content bodies (pages/posts/CPTs), ACF attached as `acf`
// See the plugin's HEADLESS_INTEGRATION.md and
// docs/superpowers/specs/2026-07-13-headless-plugin-integration-cycle1-design.md.

/**
 * Pure derivation of the origin + two REST bases from a raw env value. Exported so
 * it can be unit-tested directly (no module re-evaluation / env juggling needed).
 */
export function deriveBases(rawOrigin: string | undefined | null) {
  const wpOrigin = rawOrigin?.replace(/\/$/, "") || "";
  return {
    wpOrigin,
    blueworxApi: wpOrigin ? `${wpOrigin}/wp-json/blueworx/v1` : "",
    wpApi: wpOrigin ? `${wpOrigin}/wp-json/wp/v2` : "",
    useMockData: wpOrigin === "",
  };
}

const bases = deriveBases(process.env.NEXT_PUBLIC_WORDPRESS_URL);

/** WordPress origin, scheme + host, no trailing slash. Empty until the CMS is live. */
export const WP_ORIGIN = bases.wpOrigin;

/** BlueWorx headless namespace base (auth, menus, site, resolve). Empty in mock mode. */
export const BLUEWORX_API = bases.blueworxApi;

/** Core WordPress REST base (content bodies). Empty in mock mode. */
export const WP_API = bases.wpApi;

export const config = {
  wpOrigin: WP_ORIGIN,
  blueworxApi: BLUEWORX_API,
  wpApi: WP_API,

  /** Server-only shared secret matching BLUEWORX_LABS_REVALIDATE_SECRET on the CMS. */
  revalidateSecret: process.env.REVALIDATE_SECRET || "",

  /** Where /api/contact forwards validated submissions. Empty = accept but don't forward. */
  contactForwardUrl: process.env.CONTACT_FORWARD_URL || "",

  /** Cycle 2 (portal). Enforces a real session on /portal when true. */
  portalRequireAuth: process.env.PORTAL_REQUIRE_AUTH === "true",
} as const;

/**
 * True while there is no live CMS to talk to. The data-access layer uses this to
 * choose between static fallback data and real fetches. Single mock⇄live switch.
 */
export const useMockData = bases.useMockData;
