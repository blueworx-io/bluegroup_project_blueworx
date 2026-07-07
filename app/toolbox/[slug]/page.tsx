import Link from "next/link";
import { notFound } from "next/navigation";
import Icon from "@/components/Icon";
import ToolboxGrid from "@/components/ToolboxGrid";
import { TOOLBOX_TOOLS, faviconUrl } from "@/lib/data";

const MONO = "'SF Mono','JetBrains Mono',ui-monospace,Menlo,monospace" as const;

export function generateStaticParams() {
  return TOOLBOX_TOOLS.map((t) => ({ slug: t.slug }));
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const tool = TOOLBOX_TOOLS.find((t) => t.slug === slug);
  return { title: tool ? `${tool.name} — BlueWorx Toolbox` : "BlueWorx Toolbox" };
}

export default async function ToolDetail({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const tool = TOOLBOX_TOOLS.find((t) => t.slug === slug);
  if (!tool) notFound();
  const related = TOOLBOX_TOOLS.filter((t) => t.slug !== slug).slice(0, 4);

  return (
    <div>
      <section className="tech-hero" style={{ paddingBottom: 88 }}>
        <div className="tech-inner tech-2col">
          <div className="tc-copy">
            <div style={{ display: "flex", alignItems: "center", gap: 8, fontSize: "13.5px", color: "rgba(226,228,255,.5)", marginBottom: 28 }}>
              <Link href="/toolbox" style={{ cursor: "pointer", color: "inherit", textDecoration: "none" }}>Toolbox</Link>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ width: 13, height: 13 }}><polyline points="9 18 15 12 9 6" /></svg>
              <span style={{ color: "rgba(255,255,255,.85)" }}>{tool.name}</span>
            </div>
            <div className="tech-badge" style={{ marginBottom: 20 }}><span className="dot"></span>{tool.category}</div>
            <h1 className="h1">{tool.name}</h1>
            <p className="lead" style={{ maxWidth: 520, margin: "20px 0 34px" }}>{tool.tagline}</p>
            <div style={{ display: "flex", gap: 14, flexWrap: "wrap" }}>
              <a className="btn btn-white btn-lg" href="#tool-why" style={{ textDecoration: "none" }}>Add to Your Toolbox</a>
              <Link href="/toolbox" className="btn btn-outline-w btn-lg">View All Tools</Link>
            </div>
            <div className="tech-status"><span>included in every Toolbox plan</span><span>set up &amp; managed by BlueWorx</span></div>
          </div>
          <div className="glass-wrap">
            <div className="glass-card" style={{ padding: 28 }}>
              <div className="gc-scan"></div>
              <div style={{ position: "relative", display: "flex", alignItems: "center", gap: 16 }}>
                <div style={{ width: 58, height: 58, borderRadius: 15, background: "#fff", display: "flex", alignItems: "center", justifyContent: "center", boxShadow: "0 10px 26px rgba(0,0,0,.28)", flexShrink: 0 }}>
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={faviconUrl(tool.domain, 128)} alt={tool.name} style={{ width: 32, height: 32, objectFit: "contain" }} />
                </div>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div style={{ display: "flex", alignItems: "center", gap: 9, fontSize: 17, fontWeight: 600, color: "#fff" }}>
                    {tool.name}
                    {tool.popular && (
                      <span style={{ fontSize: 10, fontWeight: 700, letterSpacing: ".04em", textTransform: "uppercase", color: "#0A0C29", background: "#A5A7FF", padding: "2px 7px", borderRadius: 100 }}>Popular</span>
                    )}
                  </div>
                  <div style={{ fontFamily: MONO, fontSize: 12, letterSpacing: ".06em", color: "rgba(226,228,255,.45)", marginTop: 3 }}>{tool.domain}</div>
                </div>
              </div>
              <div style={{ position: "relative", height: 1, background: "rgba(139,142,255,.18)", margin: "22px 0" }}></div>
              <div style={{ position: "relative", display: "flex", flexDirection: "column", gap: 14 }}>
                {tool.features.map((f) => (
                  <div key={f.title} style={{ display: "flex", alignItems: "flex-start", gap: 11 }}>
                    <div style={{ width: 20, height: 20, borderRadius: "50%", background: "rgba(1,208,132,.14)", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0, marginTop: 1 }}>
                      <svg viewBox="0 0 24 24" fill="none" stroke="#01D084" strokeWidth="3" style={{ width: 11, height: 11 }}><polyline points="20 6 9 17 4 12" /></svg>
                    </div>
                    <div style={{ fontSize: "14.5px", color: "rgba(226,228,255,.82)", lineHeight: 1.5 }}>{f.title}</div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="sec" id="tool-why">
        <div className="center-head" style={{ marginBottom: 40 }}>
          <div className="eyebrow" style={{ marginBottom: 20 }}>Why it&apos;s in the Toolbox</div>
          <h2 className="h2">What {tool.name} gives you</h2>
        </div>
        <div className="svc-grid">
          {tool.features.map((f) => (
            <div key={f.title} className="svc">
              <div className="svc-ic"><Icon name={f.icon} /></div>
              <h3>{f.title}</h3>
              <p>{f.desc}</p>
            </div>
          ))}
        </div>
      </section>

      <ToolboxGrid
        title="More from the Toolbox"
        sub="Every tool below is included in the same subscription. No extra logins, no extra bills."
        tools={related}
      />
    </div>
  );
}
