"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import Icon from "@/components/Icon";
import { faviconUrl, type Tool } from "@/lib/data";
import type { PortalData } from "@/lib/api/portal";

type Tab =
  | "overview"
  | "onboarding"
  | "sites"
  | "toolbox"
  | "learning"
  | "subs"
  | "hours"
  | "invoices"
  | "support"
  | "partner";

const siteChip = (status: string) => (status === "Live" ? "pt-chip ok" : status === "Staging" ? "pt-chip stage" : "pt-chip warn");

const NAV_ITEMS: { key: Tab; label: string; icon: string; badge?: string }[] = [
  { key: "overview", label: "Overview", icon: "chart" },
  { key: "onboarding", label: "Onboarding", icon: "workflow", badge: "2" },
  { key: "sites", label: "My Websites", icon: "server" },
  { key: "toolbox", label: "Toolbox", icon: "zap" },
  { key: "learning", label: "Learning Center", icon: "book" },
  { key: "subs", label: "Subscriptions", icon: "plug" },
  { key: "hours", label: "Hours & Packages", icon: "clock" },
  { key: "invoices", label: "Invoices", icon: "doc" },
  { key: "support", label: "Support", icon: "chat", badge: "1" },
  { key: "partner", label: "Partner Portal", icon: "users" },
];

const TITLES: Record<Tab, [string, string]> = {
  overview: ["Client Portal", "Overview"],
  onboarding: ["Client Portal", "Onboarding"],
  learning: ["Client Portal", "Learning Center"],
  sites: ["Client Portal", "My Websites"],
  toolbox: ["Client Portal", "Toolbox"],
  subs: ["Client Portal", "Subscriptions"],
  hours: ["Client Portal", "Hours & Packages"],
  invoices: ["Client Portal", "Invoices"],
  support: ["Client Portal", "Support"],
  partner: ["Client Portal", "Partner Portal"],
};

type StepState = "done" | "current" | "waiting" | "todo";

const OB_DOT_BASE: React.CSSProperties = { width: 30, height: 30, borderRadius: "50%", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "12.5px", fontWeight: 700, flexShrink: 0 };

const OB_STATES: Record<StepState, { dot: React.CSSProperties; mark: string | null; chipCls: string; chipStyle?: React.CSSProperties; status: string }> = {
  done: { dot: { background: "#E6F6EC", color: "#178048" }, mark: "✓", chipCls: "pt-chip ok", status: "Complete" },
  current: { dot: { background: "#EEF0FF", color: "#4338CA", boxShadow: "0 0 0 4px rgba(79,70,229,.12)" }, mark: null, chipCls: "pt-chip stage", status: "In progress" },
  waiting: { dot: { background: "#FFF1E4", color: "#C4610C" }, mark: null, chipCls: "pt-chip warn", status: "Waiting on you" },
  todo: { dot: { background: "#F3F4FB", color: "#A0A2B8" }, mark: null, chipCls: "pt-chip", chipStyle: { background: "#F3F4FB", color: "#8A8DA6" }, status: "Queued" },
};

const EXIT_SVG = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
    <polyline points="16 17 21 12 16 7" />
    <line x1="21" y1="12" x2="9" y2="12" />
  </svg>
);

export default function Portal({ data, tools }: { data: PortalData; tools: Tool[] }) {
  const router = useRouter();

  // Portal data comes from the server (SureCart + plugin), destructured into the
  // names the view below already uses. Presentation-derived fields (chip classes,
  // "x / y hrs", percentages) are computed here from the raw API values, matching
  // the contract that keeps the plugin's payload presentation-free.
  const {
    client: CLIENT,
    sites: SITES,
    onboarding: OB_PROJECTS,
    learning: LC_STARTERS,
    stats: PT_STATS,
    platform: PT_PLATFORM,
    activity: ACTIVITY,
    invoices: INVOICES,
    timeLog: TIME_LOG,
    team: TEAM,
    partner,
  } = data;
  const SUBS = data.subscriptions.map((x) => ({ ...x, chipCls: x.status === "Active" ? "pt-chip ok" : "pt-chip warn" }));
  const PACKAGES = data.packages.map((p) => ({
    ...p,
    numText: `${p.used} / ${p.total} hrs`,
    leftText: `${p.total - p.used} hrs remaining`,
    pct: Math.round((p.used / p.total) * 100),
  }));
  const TICKETS = data.tickets.map((t) => ({
    ...t,
    chipCls: t.status === "In progress" ? "pt-chip stage" : t.status === "Resolved" ? "pt-chip ok" : "pt-chip warn",
  }));
  const PN_TIERS = partner.tiers;
  const PN_BRANDS = partner.brands;
  const PN_EARNERS = partner.earners;

  const [tab, setTab] = useState<Tab>("overview");
  const [siteIdx, setSiteIdx] = useState(0);
  const [siteMenuOpen, setSiteMenuOpen] = useState(false);
  const [obProject, setObProject] = useState(0);

  // Partner calculator state
  const [pnTier, setPnTier] = useState("certified");
  const [pnBrand, setPnBrand] = useState(0);
  const [pnSupport, setPnSupport] = useState<"essential" | "growth" | "advanced">("growth");
  const [pnUpdates, setPnUpdates] = useState(2);
  const [pnSites, setPnSites] = useState(1);
  const [pnHosting, setPnHosting] = useState(true);
  const [pnToolbox, setPnToolbox] = useState(true);

  const goTab = (t: Tab) => {
    setTab(t);
    setSiteMenuOpen(false);
  };

  const cur = SITES[siteIdx];
  const [crumb, title] = TITLES[tab];
  const obCur = OB_PROJECTS[obProject];
  const obDocsDue = obCur.docs.filter((d) => d.state === "pending").length;

  const tier = PN_TIERS.find((t) => t.key === pnTier) ?? PN_TIERS[1];
  const pnBase = { essential: 200, growth: 500, advanced: 750 }[pnSupport];
  const pnTotal = Math.round((pnBase + (pnUpdates - 1) * 60 + (pnSites - 1) * 120 + (pnHosting ? 40 : 0) + (pnToolbox ? 49 : 0)) * PN_BRANDS[pnBrand].mult);
  const pnComm = Math.round(pnTotal * tier.rate);
  const pnRateText = Math.round(tier.rate * 100) + "%";
  const step = (v: number, d: number, min: number, max: number) => Math.max(min, Math.min(max, v + d));

  const pnStats = [
    { icon: "users", value: tier.name, label: `Your tier · ${pnRateText} commission` },
    { icon: "chart", value: "$3,840", label: "Earned year to date" },
    { icon: "cart", value: "$433", label: "Pending payout · Apr 1" },
    { icon: "plug", value: "6", label: "Active referrals" },
  ];

  return (
    <div className="portal">
      <aside className="pt-side">
        <div className="pt-brand">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/logo.png" alt="BlueWorx" />
          <span>Portal</span>
        </div>
        <nav className="pt-nav">
          <div className="pt-nav-label">Account</div>
          {NAV_ITEMS.map((it) => (
            <div key={it.key} className={tab === it.key ? "pt-nav-item on" : "pt-nav-item"} onClick={() => goTab(it.key)}>
              <Icon name={it.icon} />
              {it.label}
              {it.badge && <span className="pt-nav-badge">{it.badge}</span>}
            </div>
          ))}
        </nav>
        <div className="pt-user">
          <div className="pt-avatar">{CLIENT.initials}</div>
          <div className="pt-user-meta"><b>{CLIENT.name}</b><span>{CLIENT.company}</span></div>
        </div>
        <button className="pt-exit" onClick={() => router.push("/")}>
          {EXIT_SVG}
          Back to website
        </button>
      </aside>

      <div className="pt-main">
        <header className="pt-top">
          <div>
            <div className="pt-crumb">{crumb}</div>
            <h1>{title}</h1>
          </div>
          <div className="pt-top-right">
            <button className="pt-exit-top" aria-label="Back to website" onClick={() => router.push("/")}>{EXIT_SVG}</button>
            <div className="pt-switch">
              <button className="pt-switch-btn" onClick={() => setSiteMenuOpen((v) => !v)}>
                <span className="pt-switch-dot"></span>
                <span className="pt-switch-lbl"><b>{cur.label}</b><span>{cur.url}</span></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9" /></svg>
              </button>
              {siteMenuOpen && (
                <div className="pt-switch-menu">
                  {SITES.map((s, i) => (
                    <div key={s.url} className={i === siteIdx ? "pt-switch-item on" : "pt-switch-item"} onClick={() => { setSiteIdx(i); setSiteMenuOpen(false); }}>
                      <span className="pt-si-dot" style={{ background: s.dot }}></span>
                      <span className="pt-si-meta"><b>{s.label}</b><span>{s.url}</span></span>
                      {i === siteIdx && (
                        <svg className="pt-si-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
            <button className="pt-iconbtn" aria-label="Messages">
              <Icon name="chat" />
              <span className="pt-ping"></span>
            </button>
            <div className="pt-avatar" style={{ width: 44, height: 44 }}>{CLIENT.initials}</div>
          </div>
        </header>

        <div className="pt-body">
          {tab === "overview" && (
            <div>
              <div className="pt-welcome">
                <h2>Welcome back, {CLIENT.first}</h2>
                <p>Here&apos;s what&apos;s happening across your {CLIENT.company} account today.</p>
              </div>
              <div className="pt-stats">
                {PT_STATS.map((st) => (
                  <div key={st.label} className="pt-stat">
                    <div className="pt-stat-ic"><Icon name={st.icon} /></div>
                    <b>{st.value}</b>
                    <span>{st.label}</span>
                  </div>
                ))}
              </div>
              <div className="pt-cols">
                <div>
                  <div className="pt-card">
                    <div className="pt-card-head"><h3>Active subscriptions</h3><a onClick={() => goTab("subs")}>Manage</a></div>
                    {SUBS.map((s) => (
                      <div key={s.name} className="pt-sub">
                        <div className="pt-sub-ic"><Icon name={s.icon} /></div>
                        <div className="pt-sub-meta"><b>{s.name}</b><span>{s.site}</span></div>
                        <div className="pt-sub-price"><b>{s.price}<i>{s.cycle}</i></b><div className={s.chipCls}>{s.status}</div></div>
                      </div>
                    ))}
                  </div>
                  <div className="pt-card">
                    <div className="pt-card-head"><h3>Your websites</h3><a onClick={() => goTab("sites")}>View all</a></div>
                    <div className="pt-sites">
                      {SITES.map((site) => (
                        <div key={site.url} className="pt-site">
                          <div className="pt-site-shot">
                            {/* eslint-disable-next-line @next/next/no-img-element */}
                            <img src={site.shot} alt={site.label} />
                            <div className={siteChip(site.status)}>{site.status}</div>
                          </div>
                          <div className="pt-site-body">
                            <h4>{site.label}</h4>
                            <div className="pt-site-url">{site.url}</div>
                            <div className="pt-site-rows">
                              <div className="pt-site-row"><span>Platform</span><b>{site.platform}</b></div>
                              <div className="pt-site-row"><span>Uptime</span><b>{site.uptime}</b></div>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
                <div>
                  <div className="pt-card">
                    <div className="pt-card-head"><h3>Hours &amp; packages</h3><a onClick={() => goTab("hours")}>Details</a></div>
                    {PACKAGES.map((p) => (
                      <div key={p.name} className="pt-hours">
                        <div className="pt-hours-top"><b>{p.name}</b><span className="pt-hours-num">{p.numText}</span></div>
                        <div className="pt-bar"><i style={{ width: `${p.pct}%`, background: p.color }}></i></div>
                        <div className="pt-hours-top" style={{ margin: "7px 0 0" }}><span>{p.period}</span><span>{p.leftText}</span></div>
                      </div>
                    ))}
                  </div>
                  <div className="pt-card">
                    <div className="pt-card-head"><h3>Recent activity</h3></div>
                    {ACTIVITY.map((a) => (
                      <div key={a.text} className="pt-act">
                        <div className="pt-act-ic"><Icon name={a.icon} /></div>
                        <div className="pt-act-meta"><p>{a.text}</p><span>{a.time}</span></div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          )}

          {tab === "sites" && (
            <div>
              <div className="pt-sites" style={{ marginBottom: 20 }}>
                {SITES.map((site) => (
                  <div key={site.url} className="pt-site">
                    <div className="pt-site-shot">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img src={site.shot} alt={site.label} />
                      <div className={siteChip(site.status)}>{site.status}</div>
                    </div>
                    <div className="pt-site-body">
                      <h4>{site.label}</h4>
                      <div className="pt-site-url">{site.url}</div>
                      <div className="pt-site-rows">
                        <div className="pt-site-row"><span>Platform</span><b>{site.platform}</b></div>
                        <div className="pt-site-row"><span>Plan</span><b>{site.plan}</b></div>
                        <div className="pt-site-row"><span>SSL</span><b>{site.ssl}</b></div>
                        <div className="pt-site-row"><span>30-day visits</span><b>{site.visits}</b></div>
                      </div>
                      <div className="pt-site-foot">
                        <button className="pt-btn primary">Visit site</button>
                        <button className="pt-btn">Details</button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
              <div className="pt-card">
                <div className="pt-card-head"><h3>Platform information</h3></div>
                <div className="pt-platform">
                  {PT_PLATFORM.map((p) => (
                    <div key={p.label} className="pt-plat">
                      <div className="pt-plat-ic"><Icon name={p.icon} /></div>
                      <div className="pt-plat-meta"><span>{p.label}</span><b>{p.value}</b></div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {tab === "toolbox" && (
            <div>
              <div className="pt-welcome">
                <h2>Your Toolbox</h2>
                <p>Every tool included in your plan, set up, managed, and maintained by BlueWorx.</p>
              </div>
              <div className="pt-tool-grid">
                {tools.map((t) => (
                  <a
                    key={t.slug}
                    href={`https://${t.domain}`}
                    target="_blank"
                    rel="noopener"
                    className="pt-tool-link"
                    style={{ display: "flex", flexDirection: "column", gap: 12, padding: 20, background: "#fff", border: "1px solid #E9E9F2", borderRadius: 14, textDecoration: "none", transition: "border-color .2s, box-shadow .2s" }}
                  >
                    <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
                      <div style={{ width: 42, height: 42, borderRadius: 11, background: "#F5F6FB", border: "1px solid #EEEEF5", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                        {/* eslint-disable-next-line @next/next/no-img-element */}
                        <img src={faviconUrl(t.domain)} alt={t.name} style={{ width: 24, height: 24, objectFit: "contain" }} />
                      </div>
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{ fontSize: "14.5px", fontWeight: 600, color: "#0A0C29" }}>{t.name}</div>
                        <div style={{ fontSize: 11, fontWeight: 600, letterSpacing: ".06em", textTransform: "uppercase", color: "#8A8F98", marginTop: 2 }}>{t.category}</div>
                      </div>
                      <svg viewBox="0 0 24 24" fill="none" stroke="#8A8F98" strokeWidth="2" style={{ width: 14, height: 14, flexShrink: 0 }}>
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                        <polyline points="15 3 21 3 21 9" />
                        <line x1="10" y1="14" x2="21" y2="3" />
                      </svg>
                    </div>
                    <p style={{ margin: 0, fontSize: 13, lineHeight: 1.55, color: "#6B6E86" }}>{t.desc}</p>
                  </a>
                ))}
              </div>
            </div>
          )}

          {tab === "onboarding" && (
            <div>
              <div className="pt-welcome">
                <h2>Project onboarding</h2>
                <p>Track every step of your projects and share what we need, right when we need it.</p>
              </div>
              <div style={{ display: "flex", gap: 10, flexWrap: "wrap", marginBottom: 20 }}>
                {OB_PROJECTS.map((p, i) => (
                  <button key={p.name} className={i === obProject ? "opt on" : "opt"} onClick={() => setObProject(i)} style={{ display: "flex", flexDirection: "column", alignItems: "flex-start", gap: 1, padding: "10px 18px", borderRadius: 12 }}>
                    <b style={{ fontSize: "13.5px" }}>{p.name}</b>
                    <span style={{ fontSize: 11, opacity: 0.72, fontWeight: 500 }}>{p.type}</span>
                  </button>
                ))}
              </div>
              <div className="pt-cols">
                <div className="pt-card" style={{ margin: 0 }}>
                  <div className="pt-card-head"><h3>{obCur.name} — progress</h3><span className="pt-chip stage">{obCur.stage}</span></div>
                  {obCur.steps.map((st, i) => {
                    const m = OB_STATES[st.state];
                    return (
                      <div key={st.title} style={{ display: "flex", alignItems: "flex-start", gap: 14, padding: "13px 0", borderTop: "1px solid #F0F0F6" }}>
                        <div style={{ ...OB_DOT_BASE, ...m.dot }}>{m.mark ?? String(i + 1)}</div>
                        <div style={{ flex: 1, minWidth: 0 }}>
                          <b style={{ display: "block", fontSize: 14, fontWeight: 600 }}>{st.title}</b>
                          <span style={{ display: "block", fontSize: "12.5px", color: "#8A8DA6", marginTop: 1 }}>{st.desc}</span>
                        </div>
                        <span className={m.chipCls} style={m.chipStyle}>{m.status}</span>
                      </div>
                    );
                  })}
                </div>
                <div>
                  <div className="pt-card">
                    <div className="pt-card-head">
                      <h3>Documents needed</h3>
                      {obDocsDue > 0 && <span className="pt-chip warn">{obDocsDue} outstanding</span>}
                    </div>
                    {obCur.docs.map((d) => (
                      <div key={d.name} className="pt-sub">
                        <div className="pt-sub-ic"><Icon name="doc" /></div>
                        <div className="pt-sub-meta"><b>{d.name}</b><span>{d.hint}</span></div>
                        {d.state === "received" ? (
                          <span className="pt-chip ok">Received</span>
                        ) : (
                          <button className="pt-btn primary" style={{ flex: "none", height: 34, padding: "0 14px", fontSize: "12.5px" }}>Upload</button>
                        )}
                      </div>
                    ))}
                  </div>
                  <div className="pt-card">
                    <div className="pt-card-head"><h3>Next milestone</h3></div>
                    <div style={{ display: "flex", alignItems: "center", gap: 13 }}>
                      <div className="pt-sub-ic"><Icon name="calendar" /></div>
                      <div className="pt-sub-meta"><b>{obCur.milestone.label}</b><span>{obCur.milestone.date} · with {obCur.milestone.who}</span></div>
                    </div>
                    <Link href="/contact" className="pt-btn" style={{ marginTop: 18, width: "100%", height: 42, textDecoration: "none" }}>
                      <Icon name="chat" style={{ width: 16, height: 16 }} />
                      Ask about this project
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          )}

          {tab === "learning" && (
            <div>
              <div className="pt-welcome">
                <h2>Learning Center</h2>
                <p>Step-by-step guides for every tool and system in your Toolbox. Learn it once, own it forever.</p>
              </div>
              <div className="pt-card">
                <div className="pt-card-head"><h3>Start here</h3><span className="pt-link">All getting-started guides</span></div>
                {LC_STARTERS.map((g) => (
                  <div key={g.title} className="pt-sub" style={{ cursor: "pointer" }}>
                    <div className="pt-sub-ic"><Icon name={g.icon} /></div>
                    <div className="pt-sub-meta"><b>{g.title}</b><span>{g.meta}</span></div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="#8A8DA6" strokeWidth="2" style={{ width: 16, height: 16, flexShrink: 0 }}><polyline points="9 18 15 12 9 6" /></svg>
                  </div>
                ))}
              </div>
              <div className="pt-tool-grid">
                {tools.map((t, i) => (
                  <div key={t.slug} className="pt-tool-link" style={{ display: "flex", flexDirection: "column", gap: 12, padding: 20, background: "#fff", border: "1px solid #E9E9F2", borderRadius: 14, cursor: "pointer", transition: "border-color .2s, box-shadow .2s" }}>
                    <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
                      <div style={{ width: 42, height: 42, borderRadius: 11, background: "#F5F6FB", border: "1px solid #EEEEF5", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                        {/* eslint-disable-next-line @next/next/no-img-element */}
                        <img src={faviconUrl(t.domain)} alt={t.name} style={{ width: 24, height: 24, objectFit: "contain" }} />
                      </div>
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{ fontSize: "14.5px", fontWeight: 600, color: "#0A0C29" }}>{t.name}</div>
                        <div style={{ fontSize: 12, color: "#8A8DA6", marginTop: 2 }}>{4 + (i % 4)} guides · {t.category}</div>
                      </div>
                    </div>
                    <span style={{ fontSize: 13, fontWeight: 600, color: "#4F46E5", display: "inline-flex", alignItems: "center", gap: 6 }}>
                      View guides
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ width: 14, height: 14 }}><line x1="7" y1="17" x2="17" y2="7" /><polyline points="7 7 17 7 17 17" /></svg>
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {tab === "subs" && (
            <div>
              <div className="pt-stats pt-stats-3">
                <div className="pt-stat"><div className="pt-stat-ic"><Icon name="cart" /></div><b>$938/mo</b><span>Total monthly spend</span></div>
                <div className="pt-stat"><div className="pt-stat-ic"><Icon name="plug" /></div><b>{SUBS.length}</b><span>Active subscriptions</span></div>
                <div className="pt-stat"><div className="pt-stat-ic"><Icon name="calendar" /></div><b>Mar 20</b><span>Next renewal</span></div>
              </div>
              <div className="pt-card">
                <div className="pt-card-head"><h3>All subscriptions</h3><Link href="/contact" style={{ fontSize: 13, fontWeight: 600, color: "#4F46E5", textDecoration: "none" }}>Need a change?</Link></div>
                <table className="pt-table">
                  <thead>
                    <tr><th>Plan</th><th>Applies to</th><th>Billing</th><th>Renews</th><th className="pt-td-r">Status</th></tr>
                  </thead>
                  <tbody>
                    {SUBS.map((s) => (
                      <tr key={s.name}>
                        <td><b>{s.name}</b></td>
                        <td>{s.site}</td>
                        <td>{s.price}{s.cycle}</td>
                        <td>{s.renews}</td>
                        <td className="pt-td-r"><span className={s.chipCls}>{s.status}</span></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {tab === "hours" && (
            <div>
              <div className="pt-card">
                <div className="pt-card-head"><h3>Retained hours &amp; credits</h3><span className="pt-link">Resets 1st of each period</span></div>
                {PACKAGES.map((p) => (
                  <div key={p.name} className="pt-hours">
                    <div className="pt-hours-top"><b>{p.name}</b><span className="pt-hours-num">{p.numText}</span></div>
                    <div className="pt-bar"><i style={{ width: `${p.pct}%`, background: p.color }}></i></div>
                    <div className="pt-hours-top" style={{ margin: "7px 0 0" }}><span>{p.period}</span><span>{p.leftText}</span></div>
                  </div>
                ))}
              </div>
              <div className="pt-card">
                <div className="pt-card-head"><h3>Recent time logged</h3></div>
                <table className="pt-table">
                  <thead>
                    <tr><th>Date</th><th>Task</th><th>Team member</th><th className="pt-td-r">Hours</th></tr>
                  </thead>
                  <tbody>
                    {TIME_LOG.map((t) => (
                      <tr key={t.task}>
                        <td>{t.date}</td>
                        <td><b>{t.task}</b></td>
                        <td>{t.who}</td>
                        <td className="pt-td-r">{t.hrs}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {tab === "invoices" && (
            <div>
              <div className="pt-card">
                <div className="pt-card-head"><h3>Billing history</h3><a>Download all</a></div>
                <table className="pt-table">
                  <thead>
                    <tr><th>Invoice</th><th>Date</th><th>Amount</th><th>Status</th><th className="pt-td-r">Action</th></tr>
                  </thead>
                  <tbody>
                    {INVOICES.map((v) => (
                      <tr key={v.id}>
                        <td><b>{v.id}</b></td>
                        <td>{v.date}</td>
                        <td>{v.amount}</td>
                        <td><span className="pt-chip paid">{v.status}</span></td>
                        <td className="pt-td-r"><span className="pt-link">Download</span></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {tab === "support" && (
            <div className="pt-support-grid">
              <div className="pt-card" style={{ margin: 0 }}>
                <div className="pt-card-head">
                  <h3>Your support tickets</h3>
                  <Link href="/contact" className="pt-btn primary" style={{ flex: "none", padding: "0 16px", textDecoration: "none" }}>New request</Link>
                </div>
                {TICKETS.map((t) => (
                  <div key={t.ref} className="pt-ticket">
                    <div className="pt-sub-ic"><Icon name="doc" /></div>
                    <div className="pt-ticket-meta"><b>{t.title}</b><span>{t.ref} · {t.time}</span></div>
                    <div className={t.chipCls}>{t.status}</div>
                  </div>
                ))}
              </div>
              <div className="pt-card" style={{ margin: 0 }}>
                <div className="pt-card-head"><h3>Your BlueWorx team</h3></div>
                {TEAM.map((m) => (
                  <div key={m.name} className="pt-contact-row">
                    <div className="pt-avatar">{m.initial}</div>
                    <div className="pt-sub-meta"><b>{m.name}</b><span>{m.role}</span></div>
                  </div>
                ))}
                <Link href="/contact" className="pt-btn" style={{ marginTop: 18, width: "100%", height: 42, textDecoration: "none" }}>
                  <Icon name="chat" style={{ width: 16, height: 16 }} />
                  Message your team
                </Link>
              </div>
            </div>
          )}

          {tab === "partner" && (
            <div>
              <div className="pt-welcome">
                <h2>Partner Portal</h2>
                <p>Price up projects for your clients and see exactly what you&apos;ll earn, instantly.</p>
              </div>

              <div className="pt-stats">
                {pnStats.map((st) => (
                  <div key={st.label} className="pt-stat">
                    <div className="pt-stat-ic"><Icon name={st.icon} /></div>
                    <b>{st.value}</b>
                    <span>{st.label}</span>
                  </div>
                ))}
              </div>

              <div className="pt-card">
                <div className="pt-card-head"><h3>Your commission tier</h3><span className="pt-link">How tiers work</span></div>
                <div className="pt-tier-grid">
                  {PN_TIERS.map((t) => {
                    const on = t.key === pnTier;
                    return (
                      <div
                        key={t.key}
                        onClick={() => setPnTier(t.key)}
                        style={{
                          padding: "16px 18px",
                          borderRadius: 14,
                          cursor: "pointer",
                          transition: "border-color .2s, box-shadow .2s",
                          border: on ? "1.5px solid #4F46E5" : "1px solid #E9E9F2",
                          background: on ? "#F7F7FF" : "#fff",
                          boxShadow: on ? "0 8px 22px rgba(79,70,229,.1)" : undefined,
                        }}
                      >
                        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: 10 }}>
                          <b style={{ fontSize: "14.5px", fontWeight: 600 }}>{t.name}</b>
                          <span style={{ flexShrink: 0, fontSize: "12.5px", fontWeight: 700, borderRadius: 99, padding: "3px 10px", background: on ? "#4F46E5" : "#F3F4FB", color: on ? "#fff" : "#6B6E86" }}>
                            {Math.round(t.rate * 100)}%
                          </span>
                        </div>
                        <p style={{ margin: "8px 0 0", fontSize: "12.5px", lineHeight: 1.5, color: "#6B6E86" }}>{t.desc}</p>
                        <span style={{ display: "block", marginTop: 10, fontSize: 11, fontWeight: 600, letterSpacing: ".05em", textTransform: "uppercase", color: "#A0A2B8" }}>{t.req}</span>
                      </div>
                    );
                  })}
                </div>
              </div>

              <div className="pt-card">
                <div className="pt-card-head"><h3>Pricing &amp; commission calculator</h3><span className="pt-link">Your rate: {pnRateText}</span></div>
                <div className="pt-cols pt-cols-nrw">
                  <div>
                    <div className="calc-field">
                      <label>Partner brand</label>
                      <div className="opt-row">
                        {PN_BRANDS.map((b, i) => (
                          <button key={b.name} className={i === pnBrand ? "opt on" : "opt"} onClick={() => setPnBrand(i)}>{b.name}</button>
                        ))}
                      </div>
                    </div>
                    <div className="calc-field">
                      <label>Support level</label>
                      <div className="opt-row">
                        <button className={pnSupport === "essential" ? "opt on" : "opt"} onClick={() => setPnSupport("essential")}>Essential</button>
                        <button className={pnSupport === "growth" ? "opt on" : "opt"} onClick={() => setPnSupport("growth")}>Growth</button>
                        <button className={pnSupport === "advanced" ? "opt on" : "opt"} onClick={() => setPnSupport("advanced")}>Advanced</button>
                      </div>
                    </div>
                    <div className="calc-field">
                      <label>Update packs per year</label>
                      <div className="stepper">
                        <button aria-label="Fewer update packs" onClick={() => setPnUpdates((v) => step(v, -1, 1, 6))}>−</button>
                        <b>{pnUpdates}</b>
                        <button aria-label="More update packs" onClick={() => setPnUpdates((v) => step(v, 1, 1, 6))}>+</button>
                      </div>
                    </div>
                    <div className="calc-field">
                      <label>Number of websites</label>
                      <div className="stepper">
                        <button aria-label="Fewer websites" onClick={() => setPnSites((v) => step(v, -1, 1, 5))}>−</button>
                        <b>{pnSites}</b>
                        <button aria-label="More websites" onClick={() => setPnSites((v) => step(v, 1, 1, 5))}>+</button>
                      </div>
                    </div>
                    <div className="calc-field" style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
                      <label style={{ margin: 0 }}>Managed hosting add-on</label>
                      <button className={pnHosting ? "toggle-pill on" : "toggle-pill"} aria-label="Managed hosting add-on" aria-pressed={pnHosting} onClick={() => setPnHosting((v) => !v)}></button>
                    </div>
                    <div className="calc-field" style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
                      <label style={{ margin: 0 }}>Toolbox subscription</label>
                      <button className={pnToolbox ? "toggle-pill on" : "toggle-pill"} aria-label="Toolbox subscription" aria-pressed={pnToolbox} onClick={() => setPnToolbox((v) => !v)}></button>
                    </div>
                  </div>
                  <div className="calc-out" style={{ padding: "34px 30px" }}>
                    <div className="cl">Client pays</div>
                    <div style={{ position: "relative", zIndex: 1, fontFamily: "'Helvetica Neue',var(--font-sora),sans-serif", fontWeight: 700, fontSize: 34, letterSpacing: "-.5px" }}>
                      ${pnTotal.toLocaleString()}<span style={{ fontSize: 17, fontWeight: 500, color: "rgba(255,255,255,.6)" }}>/mo</span>
                    </div>
                    <div className="cl" style={{ marginTop: 22 }}>Your commission at {pnRateText}</div>
                    <div className="cv">${pnComm.toLocaleString()}<span style={{ fontSize: 20, fontWeight: 500, color: "rgba(255,255,255,.6)" }}>/mo</span></div>
                    <div className="cp" style={{ marginTop: 12 }}>
                      <span style={{ display: "inline-flex", alignItems: "center", gap: 7, fontSize: 14, fontWeight: 600, color: "#01D084", background: "rgba(1,208,132,.12)", border: "1px solid rgba(1,208,132,.3)", borderRadius: 99, padding: "6px 14px" }}>
                        ${(pnComm * 12).toLocaleString()} in year one
                      </span>
                    </div>
                    <button className="btn btn-brand btn-md" style={{ width: "100%" }}>Generate referral quote</button>
                  </div>
                </div>
              </div>

              <div className="pt-card">
                <div className="pt-card-head"><h3>Commission earners</h3><a>Download statement</a></div>
                <table className="pt-table">
                  <thead>
                    <tr><th>Partner</th><th>Tier</th><th>Active referrals</th><th>Referred MRR</th><th>This month</th><th className="pt-td-r">Payout</th></tr>
                  </thead>
                  <tbody>
                    {PN_EARNERS.map((e) => (
                      <tr key={e.name}>
                        <td><b>{e.name}</b></td>
                        <td>{e.type}</td>
                        <td>{e.refs}</td>
                        <td>{e.mrr}</td>
                        <td><b>{e.month}</b></td>
                        <td className="pt-td-r"><span className={e.status === "Paid" ? "pt-chip ok" : "pt-chip warn"}>{e.status}</span></td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
