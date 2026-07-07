import FaqList from "@/components/FaqList";
import LogosBand from "@/components/LogosBand";
import { BillingProvider, BillingToggle, PlanCards } from "@/components/Plans";
import SavingsCalc from "@/components/SavingsCalc";
import ToolboxGrid from "@/components/ToolboxGrid";
import { TOOLBOX_PLANS } from "@/lib/data";

export const metadata = { title: "Toolbox Plans — BlueWorx" };

const CHECK = (
  <svg className="ck" width="20" height="20" viewBox="0 0 24 24" fill="#0A0C29">
    <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm-1 14.4l-4.2-4.2 1.5-1.5 2.7 2.7 5-5 1.5 1.5z" />
  </svg>
);

const QMARK = (
  <svg className="qmark" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
    <circle cx="12" cy="12" r="10" />
    <path d="M9.1 9a3 3 0 015.8 1c0 2-3 3-3 3" />
    <line x1="12" y1="17" x2="12" y2="17" />
  </svg>
);

const CMP_ROWS: { label: React.ReactNode; cells: React.ReactNode[] }[] = [
  { label: "All 12+ premium tools", cells: [CHECK, CHECK, CHECK] },
  { label: <>Managed website hosting {QMARK}</>, cells: ["Included", "Included", "Included"] },
  { label: "Websites covered", cells: ["1", "Up to 5", "Up to 25"] },
  { label: "Learning Center access", cells: [CHECK, CHECK, CHECK] },
  { label: "Site stability support", cells: [CHECK, CHECK, CHECK] },
  { label: "Priority support", cells: ["—", CHECK, CHECK] },
  { label: "Bulk licensing for client sites", cells: ["—", "—", CHECK] },
  { label: "Dedicated account manager", cells: ["—", "—", CHECK] },
];

export default function Toolbox() {
  return (
    <div>
      <BillingProvider>
        <section className="tech-hero pb-tall" style={{ textAlign: "center" }}>
          <div className="tech-inner" style={{ maxWidth: 780, margin: "0 auto" }}>
            <div className="tech-badge" style={{ marginBottom: 22 }}><span className="dot"></span>BlueWorx Toolbox</div>
            <h1 className="h1">Choose Your <span className="tech-grad">Toolbox Plan</span></h1>
            <p className="lead" style={{ maxWidth: 580, margin: "22px auto 0" }}>One subscription replaces a stack of individual licences. Every tool is set up and managed for you, with website hosting included. For BlueWorx clients, individuals, and agencies buying in bulk.</p>
            <div style={{ display: "flex", justifyContent: "center", marginTop: 34 }}><BillingToggle /></div>
          </div>
        </section>
        <PlanCards plans={TOOLBOX_PLANS} />
      </BillingProvider>

      <LogosBand />

      <section className="sec" style={{ paddingTop: 0 }}>
        <div className="center-head" style={{ marginBottom: 40 }}>
          <h2 className="h2">All the features you need</h2>
          <p className="lead">Compare what&apos;s included across every level of support.</p>
        </div>
        <div className="cmp-scroll">
          <table className="cmp">
            <thead>
              <tr><th>Feature</th><th>Personal</th><th>Business</th><th>Agency</th></tr>
            </thead>
            <tbody>
              {CMP_ROWS.map((row, i) => (
                <tr key={i}>
                  <td>{row.label}</td>
                  {row.cells.map((c, j) => <td key={j}>{c}</td>)}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      <section className="sec" id="savings" style={{ paddingTop: 0 }}>
        <div className="center-head" style={{ marginBottom: 40 }}>
          <h2 className="h2">See what you save</h2>
          <p className="lead">Compare the Toolbox to paying for each tool individually. Toggle off anything you wouldn&apos;t buy on its own.</p>
        </div>
        <SavingsCalc />
      </section>

      <section className="sec" style={{ paddingTop: 0 }}>
        <div className="center-head" style={{ marginBottom: 40 }}>
          <h2 className="h2">Frequently asked questions</h2>
          <p className="lead">Everything you need to know about the product and billing.</p>
        </div>
        <FaqList />
      </section>

      <ToolboxGrid />
    </div>
  );
}
