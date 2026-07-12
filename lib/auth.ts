// Authentication seam for the client portal.
//
// getSession() is the single place the portal decides "who is signed in". Today,
// while the plugin's auth isn't wired, it returns a demo session so the portal
// renders for previewing. Once PORTAL_REQUIRE_AUTH=true, unauthenticated visitors
// get null and the portal page redirects them. The real implementation will read
// the SureCart/WordPress session (cookie or bearer token) here — see
// docs/API_CONTRACT.md §5.1 and open question #2.

import { config } from "@/lib/config";
import type { PortalClient } from "@/lib/api/portal";

export type Session = { client: PortalClient };

// Demo identity used only while auth is not enforced.
const DEMO_SESSION: Session = {
  client: { name: "Hannah Whitfield", first: "Hannah", company: "Bloom & Co.", initials: "HW", tier: "Growth Partner" },
};

export async function getSession(): Promise<Session | null> {
  if (config.portalRequireAuth) {
    // TODO(plugin): validate the real SureCart/WP session and return it, or null
    // when the visitor isn't signed in. No auth backend is wired yet, so enforcing
    // auth means "no one is signed in" until the plugin provides it.
    return null;
  }
  return DEMO_SESSION;
}
