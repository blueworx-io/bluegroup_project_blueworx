"use client";

import { useEffect, useState } from "react";
import { AuthProvider, useAuth } from "@/lib/auth/AuthProvider";
import Portal from "@/components/Portal";
import SignInForm from "@/components/auth/SignInForm";
import { initialsOf } from "@/lib/auth/identity";
import { DEMO_PORTAL } from "@/lib/api/portal";
import { getSubscriptions, getInvoices } from "@/lib/api/surecart";
import type { PortalData, Subscription, Invoice } from "@/lib/api/portal";
import type { Me } from "@/lib/api/account";
import type { Tool } from "@/lib/data";

export default function PortalClient({ tools, requireAuth }: { tools: Tool[]; requireAuth: boolean }) {
  return (
    <AuthProvider requireAuth={requireAuth}>
      <PortalGate tools={tools} demo={!requireAuth} />
    </AuthProvider>
  );
}

function PortalGate({ tools, demo }: { tools: Tool[]; demo: boolean }) {
  const { status, user } = useAuth();
  if (status === "loading") return <div className="portal-auth-shell"><p className="auth-note">Loading your portal…</p></div>;
  if (status === "anon") return <div className="portal-auth-shell"><SignInForm /></div>;
  return <PortalReady tools={tools} demo={demo} user={user} />;
}

function PortalReady({ tools, demo, user }: { tools: Tool[]; demo: boolean; user: Me | null }) {
  const [subs, setSubs] = useState<Subscription[] | null>(demo ? DEMO_PORTAL.subscriptions : null);
  const [invoices, setInvoices] = useState<Invoice[] | null>(demo ? DEMO_PORTAL.invoices : null);
  const [billingError, setBillingError] = useState(false);

  useEffect(() => {
    if (demo) return;
    let active = true;
    (async () => {
      try {
        const [s, i] = await Promise.all([getSubscriptions(), getInvoices()]);
        if (!active) return;
        setSubs(s);
        setInvoices(i);
      } catch {
        if (active) setBillingError(true);
      }
    })();
    return () => { active = false; };
  }, [demo]);

  const displayName = user?.display_name ?? DEMO_PORTAL.client.name;
  const data: PortalData = {
    ...DEMO_PORTAL,
    client: demo
      ? DEMO_PORTAL.client
      : {
          name: displayName,
          first: user?.first_name || displayName.split(" ")[0] || DEMO_PORTAL.client.first,
          company: DEMO_PORTAL.client.company, // not in the WP user payload — placeholder this cycle
          initials: initialsOf(displayName),
          tier: DEMO_PORTAL.client.tier,        // sourced from SureCart/user-meta in a later cycle
        },
    subscriptions: subs ?? [],
    invoices: invoices ?? [],
  };

  const billingLoading = !demo && subs === null && !billingError;
  return <Portal data={data} tools={tools} billingLoading={billingLoading} billingError={billingError} sample={!demo} />;
}
