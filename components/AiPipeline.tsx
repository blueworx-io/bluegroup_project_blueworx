"use client";

import { useEffect, useState } from "react";
import Icon from "@/components/Icon";

const STEPS = [
  { n: "01", icon: "doc", title: "Brief", desc: "Your goal becomes a scoped brief with in-scope, out-of-scope and acceptance criteria.", model: "Claude Opus" },
  { n: "02", icon: "workflow", title: "Plan", desc: "The brief becomes GitHub Milestones and scoped Issues, one per screen or feature.", model: "Claude Sonnet" },
  { n: "03", icon: "palette", title: "Design", desc: "Screens designed on your design system, then handed off to build.", model: "Claude Opus" },
  { n: "04", icon: "code", title: "Build", desc: "Each Issue built on its own branch, following our recipe book and standards.", model: "Claude Sonnet" },
  { n: "05", icon: "shield", title: "Review", desc: "Automated checks, Playwright tests and code review gate every pull request.", model: "Claude Sonnet" },
  { n: "06", icon: "zap", title: "Deploy", desc: "Merged and shipped to Netlify, WordPress or standalone, versioned and logged.", model: "Automated" },
];

// Pipeline console with the active step cycling every 1.3s.
export default function AiPipeline() {
  const [active, setActive] = useState(0);

  useEffect(() => {
    const id = setInterval(() => setActive((a) => (a + 1) % STEPS.length), 1300);
    return () => clearInterval(id);
  }, []);

  return (
    <div className="ai-pipe-shell">
      <div className="ai-pipe-glow"></div>
      <div className="ai-pipe-status">
        <span><span className="dotp"></span>Live Pipeline</span>
        <span>Brief → Deploy</span>
      </div>
      <div className="ai-pipe">
        {STEPS.map((s, i) => (
          <div key={s.n} className={i === active ? "ai-pipe-step on" : "ai-pipe-step"}>
            <span className="pn">{s.n}</span>
            <div className="pic"><Icon name={s.icon} /></div>
            <h4>{s.title}</h4>
            <p>{s.desc}</p>
            <span className="model">{s.model}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
