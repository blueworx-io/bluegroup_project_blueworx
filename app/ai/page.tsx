import Link from "next/link";
import Icon from "@/components/Icon";
import AiDemo from "@/components/AiDemo";
import AiPipeline from "@/components/AiPipeline";

export const metadata = { title: "AI Powered — BlueWorx" };

const ARROW = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <line x1="7" y1="17" x2="17" y2="7" />
    <polyline points="7 7 17 7 17 17" />
  </svg>
);

const MODELS = [
  { icon: "gauge", tier: "Haiku", title: "Fast & light", role: "For quick, mechanical, high-volume work and simple tweaks.", items: ["High-volume edits", "Simple design tweaks", "Routine changes"] },
  { icon: "code", tier: "Sonnet", title: "The default", role: "Our everyday workhorse for building, planning and most design.", items: ["Day-to-day coding", "Issues & milestones", "Everyday screens"] },
  { icon: "sparkles", tier: "Opus", title: "Deep reasoning", role: "For new or unclear briefs, hard bugs and demanding design.", items: ["New project briefs", "Hard bugs & architecture", "Demanding design"] },
  { icon: "zap", tier: "Fable", title: "The big jobs", role: "Reserved for the largest builds and toughest design challenges.", items: ["Major migrations", "Multi-day builds", "Toughest design"] },
];

const STACK = [
  { name: "Radix Themes", sub: "components" },
  { name: "lucide-react", sub: "icons" },
  { name: "Tailwind CSS", sub: "styling" },
  { name: "GSAP", sub: "motion" },
  { name: "Refero", sub: "tokens" },
  { name: "Playwright", sub: "testing" },
  { name: "GitHub", sub: "source" },
  { name: "Netlify", sub: "deploy" },
  { name: "WordPress", sub: "backend" },
  { name: "Claude Code", sub: "build" },
];

const OFFERINGS = [
  { icon: "doc", title: "AI-Built Websites", desc: "Conversion-focused sites generated from your brief and refined by hand. Fast, on-brand and production-ready." },
  { icon: "plug", title: "AI Plugins & Integrations", desc: "Custom WordPress plugins and app integrations, built code-first so every change is reviewable through a pull request." },
  { icon: "workflow", title: "AI Automations & Workflows", desc: "Connect the tools you already use and let AI-designed workflows handle the busywork end to end." },
  { icon: "server", title: "Custom AI Tooling", desc: "Bespoke internal tools and AI features tailored to how your business actually runs day to day." },
  { icon: "palette", title: "AI-Assisted Design", desc: "Screens designed in Claude Design on your own design system (Radix, lucide and tokens), ready for a clean handoff." },
];

export default function AiPowered() {
  return (
    <div>
      <section className="tech-hero ai-hero">
        <div className="tech-inner">
          <div className="tech-2col">
            <div className="tc-copy">
              <div className="claude-badge">
                <span className="cb-spark">
                  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1.5c.62 5.4 3.6 8.38 9 9-5.4.62-8.38 3.6-9 9-.62-5.4-3.6-8.38-9-9 5.4-.62 8.38-3.6 9-9z" /></svg>
                </span>
                <span className="cb-lbl">Powered by</span>
                <b>Claude</b>
                <span className="cb-div"></span>
                <span className="cb-by">Anthropic</span>
              </div>
              <h1 className="h1" style={{ marginTop: 22 }}>From Prompt to Production — <span className="tech-grad">Built by AI</span>, Shipped by Experts</h1>
              <p className="lead" style={{ marginTop: 22 }}>We build websites, plugins, automations and custom tools with an AI-first process. Claude models turn your brief into production-ready code on a vetted stack, reviewed, tested and shipped by our team.</p>
              <div className="hh-cta" style={{ marginTop: 34 }}>
                <Link href="/pricing" className="btn btn-white btn-lg">Get a Quote</Link>
                <Link href="/work" className="btn btn-outline-w btn-lg">{ARROW} View Our Work</Link>
              </div>
              <div className="ai-lead-tags">
                <span>Opus · Sonnet · Haiku</span><span>Radix + Tailwind</span><span>GSAP motion</span>
              </div>
            </div>
            <div className="glass-wrap">
              <AiDemo />
            </div>
          </div>
        </div>
      </section>

      <section className="sec">
        <div className="center-head" style={{ marginBottom: 40 }}>
          <div className="eyebrow" style={{ marginBottom: 20 }}>The Full Flow</div>
          <h2 className="h2">From Brief to Deploy, One Continuous Flow</h2>
          <p className="lead">A repeatable pipeline where the right Claude model handles each stage, and every change ships through review, tests and version control.</p>
        </div>
        <AiPipeline />
      </section>

      <section className="features-dark">
        <div className="blob" style={{ width: 360, height: 360, bottom: -140, right: -120, opacity: 0.13 }}></div>
        <div className="center-head" style={{ marginBottom: 40 }}>
          <div className="eyebrow" style={{ marginBottom: 20, background: "rgba(139,142,255,.09)", borderColor: "rgba(139,142,255,.28)", color: "#B7B9FF" }}>Model Guidance</div>
          <h2 className="h2" style={{ color: "#fff" }}>The Right Claude Model for Every Task</h2>
          <p className="fd-sub" style={{ margin: "16px auto 0", maxWidth: 600, textAlign: "center" }}>We match each job to the model that fits it best, so you get the right balance of speed, cost and depth on every piece of work.</p>
        </div>
        <div className="ai-models">
          {MODELS.map((m) => (
            <div key={m.tier} className="ai-model">
              <div className="tier"><span className="glyph"><Icon name={m.icon} /></span>{m.tier}</div>
              <h4>{m.title}</h4>
              <p className="role">{m.role}</p>
              <ul>
                {m.items.map((it) => <li key={it}>{it}</li>)}
              </ul>
            </div>
          ))}
        </div>
      </section>

      <section className="sec">
        <div className="center-head" style={{ marginBottom: 40 }}>
          <div className="eyebrow" style={{ marginBottom: 20 }}>Approved Stack</div>
          <h2 className="h2">Built on Tools We Trust</h2>
          <p className="lead">No guesswork. A vetted, approved toolset used consistently across every project.</p>
        </div>
        <div className="ai-stack">
          {STACK.map((s) => (
            <div key={s.name} className="ai-chip">{s.name} <small>{s.sub}</small></div>
          ))}
        </div>
      </section>

      <section className="features-dark">
        <div className="blob" style={{ width: 340, height: 340, top: -120, left: -120, opacity: 0.14 }}></div>
        <div className="blob" style={{ width: 300, height: 300, bottom: -120, right: -100, opacity: 0.11 }}></div>
        <div className="center-head" style={{ marginBottom: 52 }}>
          <div className="eyebrow" style={{ marginBottom: 20, background: "rgba(139,142,255,.09)", borderColor: "rgba(139,142,255,.28)", color: "#B7B9FF" }}>What We Build</div>
          <h2 className="h2" style={{ color: "#fff" }}>AI Offerings, Built for Real Businesses</h2>
          <p className="fd-sub" style={{ margin: "16px auto 0", maxWidth: 620, textAlign: "center" }}>Every build runs through our AI-first process: production-ready code on a vetted stack, reviewed and shipped by our team.</p>
        </div>
        <div className="ai-off-grid">
          {OFFERINGS.map((o) => (
            <div key={o.title} className="fc">
              <div className="fi"><Icon name={o.icon} /></div>
              <h3>{o.title}</h3>
              <p>{o.desc}</p>
              <span className="svc-link">Learn more {ARROW}</span>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}
