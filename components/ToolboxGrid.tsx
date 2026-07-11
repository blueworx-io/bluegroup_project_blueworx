import Link from "next/link";
import { faviconUrl, type Tool } from "@/lib/data";
import { getTools } from "@/lib/api/content";

const ARROW = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <line x1="7" y1="17" x2="17" y2="7" />
    <polyline points="7 7 17 7 17 17" />
  </svg>
);

// Dark "tbx" band listing toolbox tools — shared by home, toolbox, and tool detail pages.
// Server component: fetches the tool list itself when a caller doesn't pass one.
export default async function ToolboxGrid({
  title = "Do more with the BlueWorx Toolbox",
  sub = "Access premium tools, reduce unnecessary costs, and run your digital operations more efficiently.",
  tools,
}: {
  title?: string;
  sub?: string;
  tools?: Tool[];
}) {
  const list = tools ?? (await getTools());
  return (
    <section className="tbx">
      <div className="blob" style={{ width: 320, height: 320, top: -100, left: -120, opacity: 0.14 }}></div>
      <div className="tbx-head">
        <h2 className="h2">{title}</h2>
        <p>{sub}</p>
      </div>
      <div className="tbx-grid">
        {list.map((t) => (
          <Link key={t.slug} href={`/toolbox/${t.slug}`} className="tbx-card" style={{ textDecoration: "none" }}>
            <div className="tbx-top">
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <div className="tbx-logo"><img src={faviconUrl(t.domain)} alt={t.name} loading="lazy" /></div>
              <span className="tbx-arrow">{ARROW}</span>
            </div>
            <h4>{t.name}</h4>
            <p>{t.desc}</p>
          </Link>
        ))}
      </div>
    </section>
  );
}
