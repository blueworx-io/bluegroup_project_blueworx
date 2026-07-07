import Link from "next/link";

export default function CtaBand() {
  return (
    <div className="cta-soft">
      <div className="cta-inner">
        <div className="blob" style={{ width: 220, height: 220, bottom: -80, left: -40, opacity: 0.4 }}></div>
        <div className="blob" style={{ width: 180, height: 180, top: -60, right: -20, opacity: 0.35 }}></div>
        <h2 className="h2">Ready to Build a Digital Solution That Wins?</h2>
        <p>Book a free strategy call. We&apos;ll review your current setup and show you exactly where the opportunities are.</p>
        <div className="cta-actions">
          <Link href="/pricing" className="btn btn-brand btn-md">Get a Quote</Link>
          <Link href="/contact" className="btn btn-outline-w btn-md">Book a Call</Link>
        </div>
      </div>
    </div>
  );
}
