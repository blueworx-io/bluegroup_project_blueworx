import FaqList from "@/components/FaqList";
import LogosBand from "@/components/LogosBand";
import { BillingProvider, BillingToggle, PlanCards } from "@/components/Plans";
import PricingCalc from "@/components/PricingCalc";
import { RETAINER_PLANS } from "@/lib/data";

export const metadata = { title: "Pricing — BlueWorx" };

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
  { label: <>Free toolbox {QMARK}</>, cells: ["Basic", "Advanced", "Advanced"] },
  { label: "Template sites", cells: [CHECK, CHECK, CHECK] },
  { label: "Large updates", cells: [CHECK, CHECK, CHECK] },
  { label: "Small updates", cells: ["—", CHECK, CHECK] },
  { label: "Support allowance", cells: ["—", CHECK, CHECK] },
  { label: "Minor updates", cells: ["—", CHECK, CHECK] },
  { label: "Major updates", cells: ["—", "—", CHECK] },
];

export default function Pricing() {
  return (
    <div>
      <BillingProvider>
        <section className="tech-hero pb-tall" style={{ textAlign: "center" }}>
          <div className="tech-inner" style={{ maxWidth: 780, margin: "0 auto" }}>
            <div className="tech-badge" style={{ marginBottom: 22 }}><span className="dot"></span>Digital Support Packages</div>
            <h1 className="h1">Choose Your <span className="tech-grad">Support Plan</span></h1>
            <p className="lead">Choose the support plan that reflects the level of growth and support your business needs.</p>
            <div style={{ display: "flex", justifyContent: "center", marginTop: 34 }}><BillingToggle /></div>
          </div>
        </section>
        <PlanCards plans={RETAINER_PLANS} />
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
              <tr><th>Feature</th><th>Essential Support</th><th>Growth Support</th><th>Advanced Support</th></tr>
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

      <section className="sec" style={{ paddingTop: 0 }}>
        <div className="center-head" style={{ marginBottom: 40 }}>
          <h2 className="h2">Pricing calculator</h2>
          <p className="lead">Estimate your monthly investment. Adjust the options to match your needs.</p>
        </div>
        <PricingCalc />
      </section>

      <section className="sec" style={{ paddingTop: 0 }}>
        <div className="center-head" style={{ marginBottom: 40 }}>
          <h2 className="h2">Frequently asked questions</h2>
          <p className="lead">Everything you need to know about the product and billing.</p>
        </div>
        <FaqList />
      </section>
    </div>
  );
}
