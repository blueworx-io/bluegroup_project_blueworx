"use client";

import { createContext, useContext, useState } from "react";
import Link from "next/link";
import type { Plan } from "@/lib/data";

type Billing = "monthly" | "annual";

const BillingContext = createContext<{ billing: Billing; setBilling: (b: Billing) => void }>({
  billing: "monthly",
  setBilling: () => {},
});

// The billing toggle lives inside the page hero while the plan cards overlap it
// further down, so the two share state via context. Server-rendered hero JSX is
// passed through as children.
export function BillingProvider({ children }: { children: React.ReactNode }) {
  const [billing, setBilling] = useState<Billing>("monthly");
  return <BillingContext.Provider value={{ billing, setBilling }}>{children}</BillingContext.Provider>;
}

export function BillingToggle() {
  const { billing, setBilling } = useContext(BillingContext);
  return (
    <div className="bill-toggle">
      <button className={billing === "monthly" ? "on" : ""} onClick={() => setBilling("monthly")}>Monthly billing</button>
      <button className={billing === "annual" ? "on" : ""} onClick={() => setBilling("annual")}>Annual billing</button>
    </div>
  );
}

const CHECK = (
  <svg className="ck" viewBox="0 0 24 24" fill="currentColor">
    <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm-1 14.4l-4.2-4.2 1.5-1.5 2.7 2.7 5-5 1.5 1.5z" />
  </svg>
);

export function PlanCards({ plans }: { plans: Plan[] }) {
  const { billing } = useContext(BillingContext);
  return (
    <div style={{ margin: "-190px var(--gut) 0", position: "relative", zIndex: 3 }}>
      <div className="plans">
        {plans.map((p) => (
          <div key={p.name} className={p.feat ? "plan-card feat" : "plan-card"}>
            <div className="plan-top">
              <div className="plan-name">
                <span>{p.name}</span>
                {p.pop && <span className="pop">Popular</span>}
              </div>
              <div className="plan-desc">{p.desc}</div>
              <div className="plan-price">
                <b>${billing === "monthly" ? p.priceM : p.priceA}</b>
                <em>{billing === "monthly" ? "per month" : "per month, billed yearly"}</em>
              </div>
              <Link href="/contact" className={p.btn} style={{ display: "flex", alignItems: "center", justifyContent: "center", textDecoration: "none" }}>
                Get started
              </Link>
            </div>
            <div className="plan-feats">
              <div className="lbl">FEATURES</div>
              {p.features.map((ft) => (
                <div key={ft} className="pf">{CHECK}{ft}</div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
