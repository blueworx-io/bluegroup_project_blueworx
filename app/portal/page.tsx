import { redirect } from "next/navigation";
import Portal from "@/components/Portal";
import { getSession } from "@/lib/auth";
import { getPortalData } from "@/lib/api/portal";
import { getTools } from "@/lib/api/content";

export const metadata = { title: "Client Portal — BlueWorx" };

// Per-customer, authenticated data — never statically cached.
export const dynamic = "force-dynamic";

export default async function PortalPage() {
  const session = await getSession();
  if (!session) {
    // TODO(plugin): send to the real sign-in flow once it exists. Only reached
    // when PORTAL_REQUIRE_AUTH=true and no session is present.
    redirect("/");
  }

  const [data, tools] = await Promise.all([getPortalData(), getTools()]);
  return <Portal data={data} tools={tools} />;
}
