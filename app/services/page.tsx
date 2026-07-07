import Link from "next/link";
import Icon from "@/components/Icon";
import Testimonials from "@/components/Testimonials";
import { faviconUrl } from "@/lib/data";

export const metadata = { title: "Services — BlueWorx" };

const MONO = "'SF Mono',ui-monospace,Menlo,monospace" as const;

const ARROW = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <line x1="7" y1="17" x2="17" y2="7" />
    <polyline points="7 7 17 7 17 17" />
  </svg>
);

const FEAT_HIGHLIGHTS = [
  { icon: "palette", title: "Design", desc: "Branding, UX/UI, and custom graphics. Every touchpoint designed to convert." },
  { icon: "code", title: "Development", desc: "Page builds, integrations, forms, booking systems, and e-commerce." },
  { icon: "chat", title: "Support", desc: "Reactive assistance and guidance from a team that already knows your site." },
  { icon: "chart", title: "Reporting", desc: "Monthly traffic and key-metric reports, in plain English." },
];

const TOOLBOX_CHECKS = [
  "12+ premium tools, set up and managed for you",
  "Managed website hosting included in the fee",
  "Learning Center guides for every tool and system",
  "Available to individuals — and in bulk for agencies",
];

const GLASS_TOOLS = [
  { domain: "surecart.com", name: "SureCart", sub: "E-commerce & checkout" },
  { domain: "sureforms.com", name: "SureForms", sub: "Forms & lead capture" },
  { domain: "surerank.com", name: "SureRank", sub: "SEO & search visibility" },
  { domain: "ottokit.com", name: "OttoKit", sub: "Workflow automation" },
  { domain: "elementor.com", name: "Elementor", sub: "Visual page building" },
];

const GREEN_CHECK = (
  <div style={{ width: 20, height: 20, borderRadius: "50%", background: "rgba(1,208,132,.14)", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
    <svg viewBox="0 0 24 24" fill="none" stroke="#01D084" strokeWidth="3" style={{ width: 11, height: 11 }}><polyline points="20 6 9 17 4 12" /></svg>
  </div>
);

export default function Services() {
  return (
    <div>
      <section className="tech-hero">
        <div className="tech-inner tech-2col">
          <div className="tc-copy">
            <div className="tech-badge"><span className="dot"></span>Our Services</div>
            <h1 className="h1" style={{ margin: "22px 0 0" }}>Two Services. <span className="tech-grad">One Accountable Team.</span></h1>
            <p className="lead" style={{ maxWidth: 520, margin: "22px 0 34px" }}>An integrated dev team that works inside your business, and a Digital Toolbox that replaces a stack of individual subscriptions, hosting included.</p>
            <div style={{ display: "flex", gap: 14, flexWrap: "wrap" }}>
              <Link href="/pricing" className="btn btn-white btn-lg">Get a Quote</Link>
              <Link href="/work" className="btn btn-outline-w btn-lg">View Our Work</Link>
            </div>
            <div className="tech-status"><span>integrated support</span><span>digital toolbox</span><span>hosting included</span><span>learning center</span></div>
          </div>
          <div className="glass-wrap">
            <div className="glass-card">
              <div className="gc-scan"></div>
              <div className="gc-head"><div className="gc-dots"><i></i><i></i><i></i></div><div className="gc-tag">performance.live</div></div>
              <div className="gc-metric"><small>Web pageviews</small><b>98,745</b><span className="up">↑ 24%</span></div>
              <div className="gc-metric"><small>Conversion rate</small><b>4.9%</b><span className="up">↑ 0.8%</span></div>
              <div className="gc-metric" style={{ borderBottom: "none", paddingBottom: 4 }}><small>Core Web Vitals</small><b>Pass</b><span className="up">98 / 100</span></div>
              <div className="gc-spark">
                <i style={{ height: "38%" }}></i><i style={{ height: "60%" }}></i><i style={{ height: "46%" }}></i><i style={{ height: "72%" }}></i><i className="hi" style={{ height: "92%" }}></i><i style={{ height: "64%" }}></i><i style={{ height: "80%" }}></i><i style={{ height: "56%" }}></i>
              </div>
            </div>
            <div className="tech-float" style={{ bottom: -22, left: -26, animationDelay: ".8s" }}>
              <div className="tf-ic"><Icon name="server" /></div>
              <div><small>Uptime</small><b>99.9%</b></div>
            </div>
          </div>
        </div>
      </section>

      <section className="sec" style={{ paddingTop: 70, paddingBottom: 76 }}>
        <div className="svc01-head" style={{ display: "grid", gridTemplateColumns: "1.05fr 0.95fr", gap: 56, alignItems: "end", marginBottom: 40 }}>
          <div>
            <div className="eyebrow" style={{ marginBottom: 18 }}>Service 01 · Integrated Support</div>
            <h2 className="h2">A full dev team, integrated into your business</h2>
          </div>
          <div>
            <p className="lead" style={{ fontSize: 17 }}>Designers, developers, and strategists who work as an extension of your own team, without the overhead of hiring one. Request what you need; we deliver it, then keep improving it.</p>
            <div style={{ marginTop: 18 }}>
              <Link href="/pricing" className="btn btn-dark btn-md">View Support Plans {ARROW}</Link>
            </div>
          </div>
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit,minmax(210px,1fr))", gap: 14 }}>
          {FEAT_HIGHLIGHTS.map((h) => (
            <div key={h.title} style={{ background: "#FBFBFE", border: "1px solid #ECECF3", borderRadius: 16, padding: "24px 22px" }}>
              <div className="fli-num" style={{ marginBottom: 16 }}><Icon name={h.icon} /></div>
              <h4 style={{ fontSize: 17, fontWeight: 600, marginBottom: 6 }}>{h.title}</h4>
              <p style={{ fontSize: 14, lineHeight: 1.6, color: "#667085" }}>{h.desc}</p>
            </div>
          ))}
        </div>
        <div style={{ marginTop: 14, background: "linear-gradient(160deg,#EEF0FF,#E4E2FA)", border: "1px solid #E7E7F2", borderRadius: 20, padding: 20 }}>
          <div style={{ background: "#fff", borderRadius: 14, border: "1px solid #ECECF3", boxShadow: "0 18px 40px rgba(10,12,41,.10)", overflow: "hidden" }}>
            <div style={{ display: "flex", alignItems: "center", gap: 8, padding: "12px 16px", borderBottom: "1px solid #F0F0F5" }}>
              <div style={{ display: "flex", gap: 6 }}>
                <i style={{ width: 9, height: 9, borderRadius: "50%", background: "#FF5F57", display: "block" }}></i>
                <i style={{ width: 9, height: 9, borderRadius: "50%", background: "#FEBC2E", display: "block" }}></i>
                <i style={{ width: 9, height: 9, borderRadius: "50%", background: "#28C840", display: "block" }}></i>
              </div>
              <div style={{ marginLeft: 6, fontFamily: MONO, fontSize: 11, color: "#A0AFC0" }}>app.blueworx.com</div>
            </div>
            <div style={{ display: "flex", alignItems: "center", gap: 36, padding: "22px 28px" }}>
              <div style={{ flexShrink: 0 }}>
                <div style={{ fontSize: 12, color: "#667085", marginBottom: 4 }}>Revenue this month</div>
                <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
                  <div style={{ fontFamily: "'Helvetica Neue',var(--font-sora),sans-serif", fontSize: 30, fontWeight: 700, letterSpacing: -1, color: "#0A0C29" }}>$48,270</div>
                  <span style={{ fontSize: 12, fontWeight: 600, color: "#01824C", background: "#E7F6EE", padding: "5px 11px", borderRadius: 20 }}>↑ 18.4%</span>
                </div>
              </div>
              <svg viewBox="0 0 320 118" preserveAspectRatio="none" style={{ flex: 1, minWidth: 0, height: 88, display: "block" }}>
                <defs>
                  <linearGradient id="fsg" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stopColor="#4F46E5" stopOpacity=".26" />
                    <stop offset="1" stopColor="#4F46E5" stopOpacity="0" />
                  </linearGradient>
                </defs>
                <path d="M0,92 L40,74 L80,84 L120,52 L160,62 L200,34 L240,46 L280,20 L320,30 L320,118 L0,118 Z" fill="url(#fsg)" />
                <path d="M0,92 L40,74 L80,84 L120,52 L160,62 L200,34 L240,46 L280,20 L320,30" fill="none" stroke="#4F46E5" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
              <div style={{ flexShrink: 0, borderLeft: "1px solid #F0F0F5", paddingLeft: 36 }}>
                <div style={{ fontSize: 12, color: "#667085" }}>Web pageviews</div>
                <div style={{ fontFamily: "'Helvetica Neue',var(--font-sora),sans-serif", fontSize: 26, fontWeight: 700, letterSpacing: "-.5px", color: "#0A0C29", margin: "2px 0" }}>98,745</div>
                <div style={{ fontSize: 12, color: "#01824C" }}>↑ this month</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="sec" style={{ paddingTop: 24 }}>
        <div className="center-head" style={{ marginBottom: 40 }}>
          <div className="eyebrow" style={{ marginBottom: 20 }}>How It Works</div>
          <h2 className="h2">From first conversation to live solution</h2>
        </div>
        <div className="proc-grid">
          <div className="proc"><b className="num">01</b><h4>Talk It Through</h4><p>Come to us with a problem. We talk it through and identify exactly what your business needs.</p></div>
          <div className="proc"><b className="num">02</b><h4>Configure Your Package</h4><p>We configure a package around your needs and budget, then you sign up.</p></div>
          <div className="proc"><b className="num">03</b><h4>Scope, Design &amp; Build</h4><p>We scope, design, and build your digital solution, powered by the Toolbox for expanded value.</p></div>
          <div className="proc"><b className="num">04</b><h4>Support &amp; Grow</h4><p>Once live, we move into a support and growth role: updates, reporting, and a team on call.</p></div>
        </div>
      </section>

      <section className="sec" style={{ background: "#0A0C29", position: "relative", overflow: "hidden" }}>
        <div className="blob" style={{ width: 340, height: 340, top: -120, right: -120, opacity: 0.14 }}></div>
        <div className="af-wrap" style={{ position: "relative" }}>
          <div className="af-text">
            <div className="eyebrow" style={{ marginBottom: 20, borderColor: "rgba(139,142,255,.28)", background: "rgba(139,142,255,.08)", color: "#B7B9FF" }}>Service 02 · Digital Toolbox</div>
            <h2 className="h2" style={{ color: "#fff" }}>Every premium tool. One subscription.</h2>
            <p className="lead" style={{ marginTop: 16 }}>All BlueWorx clients get the full Digital Toolbox (forms, SEO, e-commerce, automation, and more) without paying for individual licences. Website hosting is included in the fee.</p>
            <div style={{ display: "flex", flexDirection: "column", gap: 13, margin: "26px 0 32px" }}>
              {TOOLBOX_CHECKS.map((c) => (
                <div key={c} style={{ display: "flex", alignItems: "center", gap: 11, fontSize: 15, color: "rgba(226,228,255,.85)" }}>
                  {GREEN_CHECK}
                  {c}
                </div>
              ))}
            </div>
            <div style={{ display: "flex", gap: 14, flexWrap: "wrap" }}>
              <Link href="/toolbox" className="btn btn-brand btn-md">View Toolbox Plans {ARROW}</Link>
              <Link href="/toolbox#savings" className="btn btn-outline-w btn-md">Calculate Your Savings</Link>
            </div>
          </div>
          <div className="glass-wrap">
            <div className="glass-card">
              <div className="gc-scan"></div>
              <div className="gc-head"><div className="gc-dots"><i></i><i></i><i></i></div><div className="gc-tag">toolbox · included</div></div>
              {GLASS_TOOLS.map((t) => (
                <div key={t.name} className="gc-metric" style={{ padding: "12px 0" }}>
                  <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
                    <div style={{ width: 34, height: 34, borderRadius: 9, background: "#fff", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img src={faviconUrl(t.domain)} alt={t.name} style={{ width: 20, height: 20, objectFit: "contain" }} />
                    </div>
                    <div>
                      <div style={{ fontSize: "14.5px", fontWeight: 600, color: "#fff" }}>{t.name}</div>
                      <div style={{ fontSize: "11.5px", color: "rgba(226,228,255,.5)" }}>{t.sub}</div>
                    </div>
                  </div>
                  <svg viewBox="0 0 24 24" fill="none" stroke="#01D084" strokeWidth="3" style={{ width: 15, height: 15 }}><polyline points="20 6 9 17 4 12" /></svg>
                </div>
              ))}
              <Link href="/toolbox" style={{ display: "block", marginTop: 16, paddingTop: 16, borderTop: "1px solid rgba(255,255,255,.09)", fontSize: 13, color: "rgba(226,228,255,.55)", textAlign: "center", cursor: "pointer", textDecoration: "none" }}>
                + 7 more tools, hosting &amp; Learning Center in every plan
              </Link>
            </div>
            <div className="tech-float" style={{ bottom: -22, left: -26, animationDelay: ".7s" }}>
              <div className="tf-ic"><Icon name="server" /></div>
              <div><small>Hosting</small><b>Included</b></div>
            </div>
          </div>
        </div>
      </section>

      <Testimonials />
    </div>
  );
}
