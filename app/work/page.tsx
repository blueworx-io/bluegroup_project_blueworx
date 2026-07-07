import Link from "next/link";
import Icon from "@/components/Icon";

export const metadata = { title: "Work — BlueWorx" };

const STAR = (
  <svg width="26" height="26" viewBox="0 0 24 24" fill="#FFC107">
    <path d="M12 2l2.9 6.3 6.9.6-5.2 4.5 1.6 6.7L12 17l-6.2 3.6 1.6-6.7L2.2 8.9l6.9-.6z" />
  </svg>
);

const PROJECTS = [
  { img: "/assets/feature-image-1.png", alt: "Hirasté website", tags: ["Web Design", "Booking Platform"], name: "Hirasté", res: "+64%", resText: "group booking enquiries" },
  { img: "/assets/feature-image-3.png", alt: "Padel365 website", tags: ["E-commerce", "Court Booking"], name: "Padel365", res: "Sold-out", resText: "launch season" },
  { img: "/assets/feature-image-4.png", alt: "QURE website", tags: ["Brand", "Web Build"], name: "QURE", res: "+38%", resText: "conversion rate" },
  { img: "/assets/feature-image-2.png", alt: "Bloom & Co. website", tags: ["Migration", "Managed Hosting"], name: "Bloom & Co.", res: "Zero-downtime", resText: "platform migration" },
  { img: "/assets/fig-collab.png", alt: "chromaesthesia website", tags: ["Web Design", "CMS"], name: "chromaesthesia", res: "2× faster", resText: "publishing workflow" },
  { img: "/assets/hero-image.png", alt: "Reid Consulting website", tags: ["SEO", "Growth Retainer"], name: "Reid Consulting", res: "3×", resText: "organic traffic in 12 months" },
];

const ABOUT_STATS = [
  { v: "5.0", star: true, label: "Google Rating" },
  { v: "82+", label: "Projects Completed" },
  { v: "100k +", label: "Revenue Handled" },
  { v: "2K +", label: "Toolbox Value" },
];

const TESTIMONIALS = [
  { text: '"BlueWorx has completely transformed how we manage our website. The tools are powerful and the support team is incredibly responsive."', initials: "SJ", name: "Sarah Johnson", role: "Owner, Fresh Bakery Co." },
  { text: '"The live chat and booking system have increased our conversion rate significantly. Worth every penny — and then some."', initials: "MR", name: "Marcus Reid", role: "Director, Reid Consulting" },
  { text: '"Finally, one platform that does it all. We cancelled three separate subscriptions when we switched to BlueWorx."', initials: "AL", name: "Amy Leung", role: "Founder, Leung Law Group" },
];

export default function Work() {
  return (
    <div>
      <section className="tech-hero">
        <div className="tech-inner tech-2col">
          <div className="tc-copy">
            <div className="tech-badge"><span className="dot"></span>Selected Work</div>
            <h1 className="h1" style={{ margin: "22px 0 0" }}>Work That Moves <span className="tech-grad">the Needle</span></h1>
            <p className="lead" style={{ maxWidth: 520, margin: "22px 0 34px" }}>Digital solutions we&apos;ve designed, built, and grown alongside our partners, with the outcomes to show for it.</p>
            <div style={{ display: "flex", gap: 14, flexWrap: "wrap" }}>
              <Link href="/contact" className="btn btn-white btn-lg">Start a Project</Link>
              <Link href="/services" className="btn btn-outline-w btn-lg">Our Services</Link>
            </div>
            <div className="tech-status"><span>82+ projects</span><span>4.9 rating</span><span>99.9% uptime</span></div>
          </div>
          <div className="glass-wrap">
            <div className="glass-card">
              <div className="gc-scan"></div>
              <div className="gc-head"><div className="gc-dots"><i></i><i></i><i></i></div><div className="gc-tag">results.log</div></div>
              <div className="gc-metric"><small>Hirasté — booking enquiries</small><b>+64%</b><span className="up">▲</span></div>
              <div className="gc-metric"><small>QURE — conversion rate</small><b>+38%</b><span className="up">▲</span></div>
              <div className="gc-metric" style={{ borderBottom: "none" }}><small>Reid — organic traffic</small><b>3×</b><span className="up">▲</span></div>
              <div className="gc-spark">
                <i style={{ height: "30%" }}></i><i style={{ height: "44%" }}></i><i style={{ height: "58%" }}></i><i style={{ height: "52%" }}></i><i style={{ height: "70%" }}></i><i className="hi" style={{ height: "96%" }}></i><i style={{ height: "78%" }}></i><i style={{ height: "88%" }}></i>
              </div>
            </div>
            <div className="tech-float" style={{ bottom: -22, left: -26, animationDelay: ".6s" }}>
              <div className="tf-ic"><Icon name="chart" /></div>
              <div><small>Avg. lift</small><b>+41%</b></div>
            </div>
          </div>
        </div>
      </section>

      <section className="sec" style={{ paddingTop: 52 }}>
        <div className="work-grid">
          {PROJECTS.map((p) => (
            <div key={p.name} className="work-card">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <div className="work-img"><img src={p.img} alt={p.alt} /></div>
              <div className="work-meta">
                <div className="work-tags">
                  {p.tags.map((t) => <span key={t} className="work-tag">{t}</span>)}
                </div>
                <h3>{p.name}</h3>
                <div className="res"><b>{p.res}</b> {p.resText}</div>
              </div>
            </div>
          ))}
        </div>
      </section>

      <section className="stats-band">
        <div className="blob" style={{ width: 320, height: 320, top: -60, right: -100, opacity: 0.16 }}></div>
        <div className="stats-grid">
          <h2 className="h2">Outcomes, not just outputs.</h2>
          <div className="stats-copy">
            <p>Every engagement is measured against the goals we set together: traffic, conversions, and revenue. Not vanity metrics.</p>
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
          <div className="eyebrow" style={{ marginBottom: 20 }}>What Our Clients Say</div>
          <h2 className="h2">Partners Who&apos;d Recommend Us</h2>
        </div>
        <div className="tg">
          {TESTIMONIALS.map((t) => (
            <div key={t.name} className="tc">
              <div className="tstars">★★★★★</div>
              <p className="ttext">{t.text}</p>
              <div className="tauthor">
                <div className="tavatar">{t.initials}</div>
                <div>
                  <div className="tname">{t.name}</div>
                  <div className="trole">{t.role}</div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}
