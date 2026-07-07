import Link from "next/link";
import Icon from "@/components/Icon";

export const metadata = { title: "About Us — BlueWorx" };

const ARROW = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <line x1="7" y1="17" x2="17" y2="7" />
    <polyline points="7 7 17 7 17 17" />
  </svg>
);

const STAR = (
  <svg width="26" height="26" viewBox="0 0 24 24" fill="#FFC107">
    <path d="M12 2l2.9 6.3 6.9.6-5.2 4.5 1.6 6.7L12 17l-6.2 3.6 1.6-6.7L2.2 8.9l6.9-.6z" />
  </svg>
);

const WHY_CARDS = [
  { icon: "users", title: "One team, end to end", desc: "Strategy to support handled in-house. No hand-offs, no finger-pointing." },
  { icon: "chart", title: "Built to perform", desc: "Fast, accessible, search-ready builds measured against real goals." },
  { icon: "server", title: "Reliable by default", desc: "Managed hosting, backups, and 99.9% uptime handled for you." },
  { icon: "chat", title: "Support that answers", desc: "A real team one message away, usually within the same business day." },
];

const ABOUT_STATS = [
  { v: "5.0", star: true, label: "Google Rating" },
  { v: "82+", label: "Projects Completed" },
  { v: "100k +", label: "Revenue Handled" },
  { v: "2K +", label: "Toolbox Value" },
];

const TEAM = [
  { name: "Ross", role: "Project Manager", initial: "R" },
  { name: "Jess", role: "Digital Designer", initial: "J" },
  { name: "Jono", role: "Sales Manager", initial: "J" },
];

const STORIES = [
  { img: "/assets/feature-image-1.png", alt: "Hirasté website", tags: ["Web Design", "Booking Platform"], name: "Hirasté", desc: "A curated platform for large group accommodation, with advanced search that helps groups book the perfect getaway.", res: "+64%", resText: "group bookings" },
  { img: "/assets/feature-image-3.png", alt: "Padel365 website", tags: ["E-commerce", "Court Booking"], name: "Padel365", desc: "Powering the launch of padel clubs across Australia with seamless court discovery and booking in one experience.", res: "Sold-out", resText: "launch weekend" },
  { img: "/assets/feature-image-4.png", alt: "QURE website", tags: ["Brand", "Web Build"], name: "QURE", desc: "A clean, credible web presence for a cannabis science company, built to support growing research operations.", res: "+38%", resText: "conversion rate" },
];

export default function About() {
  return (
    <div>
      <section className="tech-hero" style={{ textAlign: "center", paddingBottom: 72 }}>
        <div className="tech-inner" style={{ maxWidth: 820, margin: "0 auto" }}>
          <div className="tech-badge" style={{ marginBottom: 22 }}><span className="dot"></span>About BlueWorx</div>
          <h1 className="h1">The Digital Agency That <span className="tech-grad">Works Like a Partner</span></h1>
          <p className="lead" style={{ maxWidth: 560, margin: "22px auto 0" }}>Helping your business grow without breaking the bank. One platform, one team, endless support.</p>
        </div>
      </section>

      <section className="sec">
        <div className="af-wrap" style={{ display: "grid", gridTemplateColumns: "0.92fr 1.08fr", gap: 56, alignItems: "center" }}>
          <div>
            <div className="eyebrow" style={{ marginBottom: 20 }}>Why BlueWorx</div>
            <h2 className="h2">We&apos;re the team behind digital solutions that grow businesses</h2>
            <p className="lead" style={{ fontSize: 18, marginTop: 18 }}>Founded to remove the complexity of running a modern digital business, BlueWorx brings strategy, design, build, hosting, and support under one roof, so you have one accountable partner instead of five vendors.</p>
            <div style={{ display: "flex", gap: 14, marginTop: 30, flexWrap: "wrap" }}>
              <Link href="/contact" className="btn btn-dark btn-md">Book a Call</Link>
              <Link href="/work" className="btn btn-outline btn-md">View Our Work</Link>
            </div>
          </div>
          <div className="svc-grid" style={{ gridTemplateColumns: "1fr 1fr" }}>
            {WHY_CARDS.map((c) => (
              <div key={c.title} className="svc">
                <div className="svc-ic"><Icon name={c.icon} /></div>
                <h3>{c.title}</h3>
                <p>{c.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="stats-band">
        <div className="blob" style={{ width: 320, height: 320, top: -60, right: -100, opacity: 0.16 }}></div>
        <div className="stats-grid">
          <h2 className="h2">One partner powering everything digital your business needs.</h2>
          <div className="stats-copy">
            <p>BlueWorx brings together premium services that let owners run and manage their business in one platform. The results show in the businesses we support, the projects we deliver, and the value our platform provides.</p>
            <div className="stat-nums">
              {ABOUT_STATS.map((st) => (
                <div key={st.label} className="stat">
                  <b>{st.v}{st.star && STAR}</b>
                  <span>{st.label}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className="sec">
        <div className="center-head" style={{ marginBottom: 40 }}>
          <h2 className="h2">Our Team</h2>
          <p className="lead">Meet the team committed to collaboration and gold-standard delivery.</p>
        </div>
        <div className="team-grid">
          {TEAM.map((m) => (
            <div key={m.name} className="team-card">
              <div className="team-photo"><span className="team-mono">{m.initial}</span></div>
              <h4>{m.name}</h4>
              <p>{m.role}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="sec" style={{ background: "#F5F6FF", paddingTop: 0 }}>
        <div style={{ paddingTop: 96 }}>
          <div className="center-head" style={{ marginBottom: 40 }}>
            <h2 className="h2">Client Success Stories</h2>
            <p className="lead">Explore a selection of projects we&apos;ve delivered in collaboration with our partners.</p>
          </div>
          <div className="work-grid">
            {STORIES.map((s) => (
              <Link key={s.name} href="/work" className="work-card" style={{ display: "block", color: "inherit", textDecoration: "none" }}>
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <div className="work-img"><img src={s.img} alt={s.alt} /></div>
                <div className="work-meta">
                  <div className="work-tags">
                    {s.tags.map((t) => <span key={t} className="work-tag">{t}</span>)}
                  </div>
                  <h3>{s.name}</h3>
                  <p style={{ fontSize: "14.5px", lineHeight: 1.6, color: "#4C4C4C", margin: "10px 0 14px" }}>{s.desc}</p>
                  <div className="res"><b>{s.res}</b> {s.resText}</div>
                </div>
              </Link>
            ))}
          </div>
          <div style={{ display: "flex", justifyContent: "center", marginTop: 40 }}>
            <Link href="/work" className="btn btn-outline btn-md">View All Work {ARROW}</Link>
          </div>
        </div>
      </section>
    </div>
  );
}
