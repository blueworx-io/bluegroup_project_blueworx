import Link from "next/link";
import Icon from "@/components/Icon";
import LogosBand from "@/components/LogosBand";
import FeatureTabs from "@/components/FeatureTabs";
import Testimonials from "@/components/Testimonials";
import ToolboxGrid from "@/components/ToolboxGrid";

const MONO = "'SF Mono',ui-monospace,Menlo,monospace" as const;

const ARROW = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <line x1="7" y1="17" x2="17" y2="7" />
    <polyline points="7 7 17 7 17 17" />
  </svg>
);

const CHECK = (
  <svg viewBox="0 0 24 24" fill="none" stroke="#01D084" strokeWidth="2.5" style={{ width: 14, height: 14 }}>
    <polyline points="20 6 9 17 4 12" />
  </svg>
);

const COLLAB_ITEMS = [
  { icon: "server", label: "Maintain" },
  { icon: "plug", label: "Integrate" },
  { icon: "chart", label: "Improve" },
  { icon: "clock", label: "Optimise" },
];

const TICKER = ["strategy & UX", "web & platform design", "e-commerce builds", "SEO & growth", "managed hosting", "brand & identity", "automation", "ongoing support"];

function TimelineRow({
  icon,
  title,
  desc,
  status,
  state,
}: {
  icon?: string;
  title: string;
  desc: string;
  status: string;
  state: "done" | "current" | "todo";
}) {
  const circle =
    state === "done"
      ? { background: "linear-gradient(rgba(1,208,132,.13),rgba(1,208,132,.13)),#10122E", border: "1px solid rgba(1,208,132,.4)", boxShadow: undefined as string | undefined }
      : state === "current"
        ? { background: "linear-gradient(rgba(139,142,255,.16),rgba(139,142,255,.16)),#10122E", border: "1px solid rgba(154,155,255,.55)", boxShadow: "0 0 16px rgba(122,124,255,.35)" }
        : { background: "linear-gradient(rgba(255,255,255,.05),rgba(255,255,255,.05)),#10122E", border: "1px solid rgba(139,142,255,.25)", boxShadow: undefined };
  const statusColor = state === "done" ? "#01D084" : state === "current" ? "#B9BAFF" : "rgba(226,228,255,.4)";
  const titleColor = state === "todo" ? "rgba(255,255,255,.75)" : "#fff";
  const descColor = state === "todo" ? "rgba(226,228,255,.45)" : "rgba(226,228,255,.5)";
  return (
    <div style={{ position: "relative", display: "flex", alignItems: "center", gap: 15, padding: "11px 0" }}>
      <div style={{ width: 35, height: 35, borderRadius: "50%", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0, ...circle }}>
        {state === "done" ? CHECK : icon ? <Icon name={icon} style={{ width: 15, height: 15, color: state === "current" ? "#B9BAFF" : "rgba(226,228,255,.45)" }} /> : null}
      </div>
      <div style={{ flex: 1 }}>
        <div style={{ fontSize: 15, fontWeight: 600, color: titleColor }}>{title}</div>
        <div style={{ fontSize: "12.5px", color: descColor, marginTop: 1 }}>{desc}</div>
      </div>
      <span style={{ fontFamily: MONO, fontSize: 10, letterSpacing: ".1em", textTransform: "uppercase", color: statusColor }}>{status}</span>
    </div>
  );
}

export default function Home() {
  return (
    <div>
      <section className="home-hero">
        <div className="hh-inner">
          <div className="hh-copy">
            <div className="tech-badge"><span className="dot"></span>Digital Agency &amp; Platform</div>
            <h1 className="h1">We Design, Build &amp; Grow <span className="tech-grad">Digital Solutions</span> That Win Business</h1>
            <p className="lead">BlueWorx is the agency behind high-performing digital solutions: websites, platforms, and automations. Strategy, design, build, hosting, and ongoing support from one dedicated team.</p>
            <div className="hh-cta">
              <Link href="/pricing" className="btn btn-white btn-lg">Get a Quote</Link>
              <Link href="/work" className="btn btn-outline-w btn-lg">{ARROW}View Our Work</Link>
            </div>
            <div className="hh-stats">
              <div><b>82+</b><span>Projects delivered</span></div>
              <div>
                <b>
                  4.9
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="#FFB300"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.5 1.6 6.7L12 17l-6.2 3.6 1.6-6.7L2.2 8.9l6.9-.6z" /></svg>
                </b>
                <span>Google rating</span>
              </div>
              <div><b>99.9%</b><span>Uptime maintained</span></div>
            </div>
          </div>
          <div className="hh-visual">
            <div className="hh-ring"></div>
            <div className="glass-wrap">
              <div className="glass-card" style={{ padding: 28 }}>
                <div className="gc-scan"></div>
                <div className="gc-head" style={{ marginBottom: 24 }}>
                  <div className="gc-dots"><i></i><i></i><i></i></div>
                  <div className="gc-tag">yourproject · status</div>
                  <span style={{ display: "inline-flex", alignItems: "center", gap: 6, fontFamily: MONO, fontSize: "10.5px", letterSpacing: ".12em", textTransform: "uppercase", color: "#01D084" }}>
                    <i style={{ width: 6, height: 6, borderRadius: "50%", background: "#01D084", boxShadow: "0 0 8px rgba(1,208,132,.7)", display: "block" }}></i>
                    On track
                  </span>
                </div>
                <div style={{ position: "relative", display: "flex", flexDirection: "column" }}>
                  <div style={{ position: "absolute", left: 17.5, top: 29, bottom: 29, width: 1, marginLeft: -0.5, background: "linear-gradient(180deg,rgba(1,208,132,.45) 0%,rgba(1,208,132,.45) 30%,rgba(139,142,255,.22) 50%,rgba(139,142,255,.22) 100%)" }}></div>
                  <TimelineRow state="done" title="Discovery call" desc="Goals, scope & strategy agreed" status="Done" />
                  <TimelineRow state="done" title="Design" desc="On-brand, conversion-first layouts" status="Done" />
                  <TimelineRow state="current" icon="code" title="Development" desc="Fast, responsive build in progress" status="In progress" />
                  <TimelineRow state="todo" icon="server" title="Deploy" desc="Launch on managed hosting" status="Queued" />
                  <TimelineRow state="todo" icon="chat" title="Support & growth" desc="Updates, SEO & a team on call" status="Always on" />
                </div>
              </div>
              <div className="tech-float" style={{ top: -22, right: -26, animationDelay: ".4s" }}>
                <div className="tf-ic"><Icon name="clock" /></div>
                <div><small>Avg. launch</small><b>3–6 weeks</b></div>
              </div>
              <div className="tech-float" style={{ bottom: -22, left: -26, animationDelay: "1.1s" }}>
                <div className="tf-ic"><Icon name="server" /></div>
                <div><small>Uptime</small><b>Live · 99.9%</b></div>
              </div>
            </div>
          </div>
        </div>
        <div className="hh-ticker">
          <div className="hh-ticker-mask">
            <div className="hh-ticker-track">
              {[...TICKER, ...TICKER].map((t, i) => (
                <span key={i}>{t}</span>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className="sec" style={{ paddingBottom: 0 }}>
        <div className="center-head" style={{ marginBottom: 40 }}>
          <div className="eyebrow" style={{ marginBottom: 20 }}>What We Do</div>
          <h2 className="h2">Two Services. Everything Your Business Needs Online.</h2>
        </div>
        <div className="svc2">
          <Link href="/services" className="svc" style={{ padding: "42px 40px", display: "block", color: "inherit", textDecoration: "none" }}>
            <div style={{ display: "flex", alignItems: "flex-start", justifyContent: "space-between", marginBottom: 24 }}>
              <div className="svc-ic" style={{ marginBottom: 0 }}><Icon name="users" /></div>
              <span style={{ fontFamily: MONO, fontSize: 11, letterSpacing: ".14em", textTransform: "uppercase", color: "#A0AFC0" }}>Service 01</span>
            </div>
            <h3 style={{ fontSize: 26, letterSpacing: "-.6px" }}>Integrated Support</h3>
            <p style={{ fontSize: "15.5px", maxWidth: 460 }}>A full design and development team, integrated into your business. We scope, design, and build your digital solution, then stay on as your support and growth partner.</p>
            <div style={{ display: "flex", flexWrap: "wrap", gap: 8, margin: "2px 0 20px" }}>
              <span className="svc-chip">Design</span><span className="svc-chip">Development</span><span className="svc-chip">Support</span><span className="svc-chip">Reporting</span>
            </div>
            <span className="svc-link">Explore Integrated Support {ARROW}</span>
          </Link>
          <Link href="/toolbox" className="svc" style={{ padding: "42px 40px", display: "block", color: "inherit", textDecoration: "none" }}>
            <div style={{ display: "flex", alignItems: "flex-start", justifyContent: "space-between", marginBottom: 24 }}>
              <div className="svc-ic" style={{ marginBottom: 0 }}><Icon name="plug" /></div>
              <span style={{ fontFamily: MONO, fontSize: 11, letterSpacing: ".14em", textTransform: "uppercase", color: "#A0AFC0" }}>Service 02</span>
            </div>
            <h3 style={{ fontSize: 26, letterSpacing: "-.6px" }}>Digital Toolbox</h3>
            <p style={{ fontSize: "15.5px", maxWidth: 460 }}>Every premium tool your business needs, from forms and SEO to e-commerce and automation, in one subscription with hosting included. No individual licences to manage.</p>
            <div style={{ display: "flex", flexWrap: "wrap", gap: 8, margin: "2px 0 20px" }}>
              <span className="svc-chip">12+ premium tools</span><span className="svc-chip">Hosting included</span><span className="svc-chip">Learning Center</span>
            </div>
            <span className="svc-link">Explore the Toolbox {ARROW}</span>
          </Link>
        </div>
      </section>

      <LogosBand />

      <section className="sec" style={{ paddingTop: 0 }}>
        <div style={{ display: "flex", alignItems: "flex-end", justifyContent: "space-between", gap: 24, flexWrap: "wrap", marginBottom: 40 }}>
          <div>
            <div className="eyebrow" style={{ marginBottom: 20 }}>Selected Work</div>
            <h2 className="h2" style={{ maxWidth: 560 }}>Recent Projects, Real Results</h2>
          </div>
          <Link href="/work" className="btn btn-outline btn-md">View All Work {ARROW}</Link>
        </div>
        <div className="work-grid">
          <Link href="/work" className="work-card" style={{ display: "block", color: "inherit", textDecoration: "none" }}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <div className="work-img"><img src="/assets/feature-image-1.png" alt="Hirasté website" /></div>
            <div className="work-meta">
              <div className="work-tags"><span className="work-tag">Web Design</span><span className="work-tag">Booking Platform</span></div>
              <h3>Hirasté</h3>
              <div className="res"><b>+64%</b> group booking enquiries</div>
            </div>
          </Link>
          <Link href="/work" className="work-card" style={{ display: "block", color: "inherit", textDecoration: "none" }}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <div className="work-img"><img src="/assets/feature-image-3.png" alt="Padel365 website" /></div>
            <div className="work-meta">
              <div className="work-tags"><span className="work-tag">E-commerce</span><span className="work-tag">Court Booking</span></div>
              <h3>Padel365</h3>
              <div className="res"><b>Sold-out</b> launch season</div>
            </div>
          </Link>
          <Link href="/work" className="work-card" style={{ display: "block", color: "inherit", textDecoration: "none" }}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <div className="work-img"><img src="/assets/feature-image-4.png" alt="QURE website" /></div>
            <div className="work-meta">
              <div className="work-tags"><span className="work-tag">Brand</span><span className="work-tag">Web Build</span></div>
              <h3>QURE</h3>
              <div className="res"><b>+38%</b> conversion rate</div>
            </div>
          </Link>
        </div>
      </section>

      <FeatureTabs />

      <section className="sec" style={{ paddingBottom: 80 }}>
        <div className="center-head" style={{ marginBottom: 40 }}>
          <div className="eyebrow" style={{ marginBottom: 20 }}>How We Work</div>
          <h2 className="h2">From First Conversation to Long-Term Partner</h2>
        </div>
        <div className="proc-grid">
          <div className="proc"><b className="num">01</b><h4>Talk It Through</h4><p>Bring us the problem. We talk it through together and identify exactly what your business needs.</p></div>
          <div className="proc"><b className="num">02</b><h4>Configure Your Package</h4><p>We shape a package around your needs and budget. You sign up when it fits, not before.</p></div>
          <div className="proc"><b className="num">03</b><h4>Scope, Design &amp; Build</h4><p>We scope, design, and build your digital solution, powered by the BlueWorx Toolbox.</p></div>
          <div className="proc"><b className="num">04</b><h4>Support &amp; Grow</h4><p>After launch we move into a support and growth role: your dev team, on call.</p></div>
        </div>
      </section>

      <section className="split" style={{ paddingTop: 20 }}>
        <div>
          <div className="eyebrow" style={{ marginBottom: 20 }}>Ongoing Partnership</div>
          <h2 className="h2">Integrate smarter, collaborate better, and scale with BlueWorx</h2>
          <p className="lead" style={{ fontSize: 18, margin: "18px 0 30px" }}>Through ongoing support and retainer services, BlueWorx works alongside your team to support your digital solutions as your business grows.</p>
          <div className="collab-list">
            {COLLAB_ITEMS.map((tool) => (
              <div key={tool.label} className="fli" style={{ borderBottom: "none", padding: "10px 0" }}>
                <div className="fli-icon"><Icon name={tool.icon} /></div>
                <span style={{ fontSize: 17 }}>{tool.label}</span>
              </div>
            ))}
          </div>
          <div style={{ marginTop: 30 }}>
            <Link href="/services" className="btn btn-outline btn-md">Find Out More {ARROW}</Link>
          </div>
        </div>
        <div className="collab-visual">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/fig-collab.png" alt="BlueWorx collaboration tools" />
          <div className="collab-chip" style={{ top: 26, left: -14 }}>
            <div className="ci" style={{ background: "#E8E7F7" }}><Icon name="chart" style={{ width: 20, height: 20, color: "#4F46E5" }} /></div>
            <div><small>Conversion</small><b>+38.6%</b></div>
          </div>
          <div className="collab-chip" style={{ bottom: 30, right: -14 }}>
            <div className="ci" style={{ background: "#E7F6EE" }}><Icon name="server" style={{ width: 20, height: 20, color: "#01824C" }} /></div>
            <div><small>Uptime</small><b>99.9%</b></div>
          </div>
        </div>
      </section>

      <ToolboxGrid />

      <Testimonials />
    </div>
  );
}
