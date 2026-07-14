// Pure decision for the catch-all WordPress page: given a /resolve result (or
// null when the fetch failed), decide whether to render a wp/v2 body or 404.
// Extracted so it is unit-testable without a running server; the component
// wraps resolve()/getByRestUrl() in try/catch and defers the decision here.
import type { ResolveResult } from "@/lib/api/wp";

export type PageOutcome =
  | { kind: "notFound" }
  | { kind: "render"; restUrl: string };

export function decideOutcome(r: ResolveResult | null): PageOutcome {
  if (!r || r.type === "404" || !r.rest_url) return { kind: "notFound" };
  return { kind: "render", restUrl: r.rest_url };
}
