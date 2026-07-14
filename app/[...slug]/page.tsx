import { notFound } from "next/navigation";
import { useMockData } from "@/lib/config";
import { resolve, getByRestUrl } from "@/lib/api/wp";
import { decideOutcome } from "@/lib/api/resolve-page";

// Catch-all for CMS-authored pages. Existing front-end routes (about, pricing, …)
// are more specific and take precedence. See HEADLESS_INTEGRATION.md §6.1.
export const dynamic = "force-dynamic";

export default async function WordPressPage({
  params,
}: {
  params: Promise<{ slug: string[] }>;
}) {
  const { slug } = await params;
  const uri = "/" + (slug?.join("/") ?? "");

  // No CMS configured → behave exactly like today: unmatched routes 404.
  if (useMockData) notFound();

  let resolved: Awaited<ReturnType<typeof resolve>> | null = null;
  try {
    resolved = await resolve(uri);
  } catch {
    notFound(); // live CMS outage → 404, not a 500
  }

  const outcome = decideOutcome(resolved);
  if (outcome.kind === "notFound") notFound();

  let page: Awaited<ReturnType<typeof getByRestUrl>>;
  try {
    page = await getByRestUrl(outcome.restUrl);
  } catch {
    notFound();
  }

  return (
    <main className="wp-page">
      <h1 dangerouslySetInnerHTML={{ __html: page.title.rendered }} />
      <div dangerouslySetInnerHTML={{ __html: page.content.rendered }} />
    </main>
  );
}
