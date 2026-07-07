"use client";

import { useState } from "react";
import Link from "next/link";

type Support = "essential" | "growth" | "advanced";

const BASE: Record<Support, number> = { essential: 200, growth: 500, advanced: 750 };

// "Pricing calculator" on the support retainers page.
export default function PricingCalc() {
  const [support, setSupport] = useState<Support>("growth");
  const [updates, setUpdates] = useState(2);
  const [sites, setSites] = useState(1);
  const [hosting, setHosting] = useState(true);

  const total = BASE[support] + (updates - 1) * 60 + (sites - 1) * 120 + (hosting ? 40 : 0);
  const step = (v: number, d: number, min: number, max: number) => Math.max(min, Math.min(max, v + d));

  return (
    <div className="calc">
      <div className="calc-panel">
        <div className="calc-field">
          <label>Support level</label>
          <div className="opt-row">
            <button className={support === "essential" ? "opt on" : "opt"} onClick={() => setSupport("essential")}>Essential</button>
            <button className={support === "growth" ? "opt on" : "opt"} onClick={() => setSupport("growth")}>Growth</button>
            <button className={support === "advanced" ? "opt on" : "opt"} onClick={() => setSupport("advanced")}>Advanced</button>
          </div>
        </div>
        <div className="calc-field">
          <label>Update packs per year</label>
          <div className="stepper">
            <button aria-label="Fewer update packs" onClick={() => setUpdates((v) => step(v, -1, 1, 6))}>−</button>
            <b>{updates}</b>
            <button aria-label="More update packs" onClick={() => setUpdates((v) => step(v, 1, 1, 6))}>+</button>
          </div>
        </div>
        <div className="calc-field">
          <label>Number of websites</label>
          <div className="stepper">
            <button aria-label="Fewer websites" onClick={() => setSites((v) => step(v, -1, 1, 5))}>−</button>
            <b>{sites}</b>
            <button aria-label="More websites" onClick={() => setSites((v) => step(v, 1, 1, 5))}>+</button>
          </div>
        </div>
        <div className="calc-field" style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <label style={{ margin: 0 }}>Managed hosting add-on</label>
          <button
            className={hosting ? "toggle-pill on" : "toggle-pill"}
            aria-label="Managed hosting add-on"
            aria-pressed={hosting}
            onClick={() => setHosting((v) => !v)}
          ></button>
        </div>
      </div>
      <div className="calc-out">
        <div className="cl">Estimated total</div>
        <div className="cv" data-testid="calc-total">${total}</div>
        <div className="cp">per month</div>
        <Link href="/contact" className="btn btn-brand btn-md" style={{ width: "100%" }}>Get this plan</Link>
      </div>
    </div>
  );
}
