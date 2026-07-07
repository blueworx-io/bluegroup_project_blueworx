"use client";

import { useState } from "react";
import Link from "next/link";

// "One Platform. Every Tool. Real Results." tabbed analytics section (home page).
const TABS = ["Support", "Toolbox", "Hosting"];

const AF = [
  { heading: "Support Guides", desc: "Get ahead by accessing our dedicated support guides, designed to give you an edge.", cta: "View Guides", color: "#4F46E5", pts: [150, 118, 138, 82, 110, 64, 96, 74, 88] },
  { heading: "Digital Toolbox", desc: "Access a curated set of tools that power your website, automations, and integrations, all set up, managed, and maintained for you.", cta: "View Toolbox", color: "#A5A7FF", pts: [120, 96, 112, 60, 84, 46, 72, 54, 62] },
  { heading: "Website Hosting", desc: "Remove the headache of WordPress hosting with our high-performance hosting supported by integrated growth & security functionality.", cta: "View Hosting", color: "#3686F7", pts: [168, 150, 158, 128, 146, 120, 136, 126, 142] },
];

const XS = [0, 65, 130, 195, 260, 325, 390, 455, 520];

const LEGEND = [
  { label: "Support", value: "120,456", color: "#4F46E5" },
  { label: "Toolbox", value: "245,877", color: "#A5A7FF" },
  { label: "Hosting", value: "78,987", color: "#3686F7" },
];

export default function FeatureTabs() {
  const [tab, setTab] = useState(0);
  const active = AF[tab];
  const path = active.pts.map((y, i) => (i ? "L" : "M") + XS[i] + "," + y).join(" ");
  const area = path + " L520,210 L0,210 Z";
  const minY = Math.min(...active.pts);
  const dotI = active.pts.indexOf(minY);

  return (
    <section className="features-dark">
      <div className="blob" style={{ width: 360, height: 360, top: -120, right: -120, opacity: 0.14 }}></div>
      <div className="fd-header">
        <h2 className="h2">One Platform. Every Tool. Real Results.</h2>
        <p className="fd-sub">Every BlueWorx build ships on a managed platform with tools, hosting, and support included, so your site keeps performing long after launch.</p>
      </div>
      <div className="tab-bar">
        {TABS.map((label, i) => (
          <button key={label} className={tab === i ? "tab on" : "tab off"} onClick={() => setTab(i)}>
            {label}
          </button>
        ))}
      </div>
      <div className="af-wrap">
        <div className="af-panel">
          <div className="af-panel-head">
            <h4>Quick Analytics</h4>
            <span className="af-range">
              All time
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="6 9 12 15 18 9" /></svg>
            </span>
          </div>
          <div className="af-legend">
            {LEGEND.map((l, i) => (
              <div key={l.label} className={tab === i ? "af-leg on" : "af-leg off"} onClick={() => setTab(i)}>
                <small><i style={{ background: l.color }}></i>{l.label}</small>
                <b>{l.value}</b>
              </div>
            ))}
          </div>
          <svg className="af-chart" viewBox="0 0 520 210" preserveAspectRatio="none">
            <defs>
              <linearGradient id="afGrad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stopColor={active.color} stopOpacity="0.16" />
                <stop offset="100%" stopColor={active.color} stopOpacity="0" />
              </linearGradient>
            </defs>
            <line x1="0" y1="52" x2="520" y2="52" stroke="#EFEFF0" strokeWidth="1" />
            <line x1="0" y1="104" x2="520" y2="104" stroke="#EFEFF0" strokeWidth="1" />
            <line x1="0" y1="156" x2="520" y2="156" stroke="#EFEFF0" strokeWidth="1" />
            <path d={area} fill="url(#afGrad)" />
            <path d={path} fill="none" stroke={active.color} strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx={XS[dotI]} cy={minY} r="5" fill="#fff" stroke={active.color} strokeWidth="3" />
          </svg>
        </div>
        <div className="af-text">
          <h2 className="h2" style={{ fontSize: 34, marginBottom: 14, color: "#fff" }}>{active.heading}</h2>
          <p className="lead" style={{ fontSize: 17, marginBottom: 28, color: "rgba(255,255,255,.66)" }}>{active.desc}</p>
          <Link href="/toolbox" className="btn btn-brand btn-md">
            {active.cta}
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="7" y1="17" x2="17" y2="7" /><polyline points="7 7 17 7 17 17" /></svg>
          </Link>
        </div>
      </div>
    </section>
  );
}
