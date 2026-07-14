import { notFound } from "next/navigation";
import { useMockData } from "@/lib/config";
import { resolve, getByRestUrl } from "@/lib/api/wp";

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

  const r = await resolve(uri);
  if (r.type === "404" || !r.rest_url) notFound();

  const page = await getByRestUrl(r.rest_url);
  return (
    <main className="wp-page">
      <h1 dangerouslySetInnerHTML={{ __html: page.title.rendered }} />
      <div dangerouslySetInnerHTML={{ __html: page.content.rendered }} />
    </main>
  );
}
