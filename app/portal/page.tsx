import PortalClient from "@/components/portal/PortalClient";
import { getTools } from "@/lib/api/content";
import { config } from "@/lib/config";

export const metadata = { title: "Client Portal — BlueWorx" };

// Per-customer data is client-side (browser-only JWT). This shell only fetches
// public `tools` and passes the auth-enforcement flag read server-side.
export const dynamic = "force-dynamic";

export default async function PortalPage() {
  const tools = await getTools();
  return <PortalClient tools={tools} requireAuth={config.portalRequireAuth} />;
}
