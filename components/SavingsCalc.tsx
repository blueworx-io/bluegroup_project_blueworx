"use client";

import { useState } from "react";
import Link from "next/link";
import Icon from "@/components/Icon";
import { TOOLBOX_TOOLS, SOLO_PRICES, faviconUrl } from "@/lib/data";

const HOSTING = 30;
const TOOLBOX = 30;

// "See what you save" calculator on the toolbox page.
export default function SavingsCalc() {
  const [off, setOff] = useState<Record<string, boolean>>({});

  const solo = TOOLBOX_TOOLS.filter((t) => !off[t.slug]).reduce((a, t) => a + (SOLO_PRICES[t.slug] || 0), 0) + HOSTING;
  const save = Math.max(0, solo - TOOLBOX);

  return (
    <div className="calc">
      <div className="calc-panel">
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "0 36px" }}>
          {TOOLBOX_TOOLS.map((t) => (
            <div key={t.slug} style={{ display: "flex", alignItems: "center", gap: 12, padding: "10px 0", borderBottom: "1px solid #F0F0F5" }}>
              <div style={{ width: 32, height: 32, borderRadius: 9, background: "#F5F6FB", border: "1px solid #EEEEF5", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={faviconUrl(t.domain)} alt={t.name} style={{ width: 17, height: 17, objectFit: "contain" }} />
              </div>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ fontSize: 14, fontWeight: 600, color: "#0A0C29" }}>{t.name}</div>
                <div style={{ fontSize: 12, color: "#8A8DA6" }}>${SOLO_PRICES[t.slug] || 0}/mo individually</div>
              </div>
              <button
                className={off[t.slug] ? "toggle-pill" : "toggle-pill on"}
                aria-label={`Include ${t.name}`}
                aria-pressed={!off[t.slug]}
                onClick={() => setOff((o) => ({ ...o, [t.slug]: !o[t.slug] }))}
                style={{ transform: "scale(.78)", transformOrigin: "right center" }}
              ></button>
            </div>
          ))}
        </div>
        <div style={{ display: "flex", alignItems: "center", gap: 12, padding: "14px 0 2px" }}>
          <div style={{ width: 32, height: 32, borderRadius: 9, background: "#E8E7F7", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
            <Icon name="server" style={{ width: 16, height: 16, display: "block", color: "#4338CA" }} />
          </div>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontSize: 14, fontWeight: 600, color: "#0A0C29" }}>Managed website hosting</div>
            <div style={{ fontSize: 12, color: "#8A8DA6" }}>${HOSTING}/mo bought separately</div>
          </div>
          <span style={{ fontSize: 12, fontWeight: 600, color: "#178048", background: "#E6F6EC", padding: "5px 12px", borderRadius: 20 }}>Included</span>
        </div>
      </div>
      <div className="calc-out">
        <div className="cl">Buying everything individually</div>
        <div style={{ position: "relative", zIndex: 1, fontFamily: "'Helvetica Neue',var(--font-sora),sans-serif", fontWeight: 700, fontSize: 34, letterSpacing: "-.5px", color: "rgba(255,255,255,.55)", textDecoration: "line-through", textDecorationColor: "rgba(255,107,107,.75)", textDecorationThickness: 3 }}>
          ${solo}<span style={{ fontSize: 15, fontWeight: 500 }}>/mo</span>
        </div>
        <div className="cl" style={{ marginTop: 20 }}>With the BlueWorx Toolbox</div>
        <div className="cv">${TOOLBOX}<span style={{ fontSize: 20, fontWeight: 500, color: "rgba(255,255,255,.6)" }}>/mo</span></div>
        <div className="cp" style={{ marginTop: 14 }}>
          <span style={{ display: "inline-flex", alignItems: "center", gap: 7, fontSize: 14, fontWeight: 600, color: "#01D084", background: "rgba(1,208,132,.12)", border: "1px solid rgba(1,208,132,.3)", padding: "8px 16px", borderRadius: 100 }}>
            You save ${save}/mo · ${(save * 12).toLocaleString("en-US")}/yr
          </span>
        </div>
        <Link href="/contact" className="btn btn-brand btn-md" style={{ width: "100%" }}>Get the Toolbox</Link>
      </div>
    </div>
  );
}
