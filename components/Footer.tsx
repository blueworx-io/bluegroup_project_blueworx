import Link from "next/link";

export default function Footer() {
  return (
    <footer>
      <div className="ft">
        <div className="fb">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/logo.png" alt="BlueWorx" style={{ filter: "brightness(0) invert(1)" }} />
          <p>BlueWorx supports growing businesses worldwide with premium tools, hosting, and expert support.</p>
          <div className="fsocial">
            <a aria-label="Facebook">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 10h3l.5-3H13V5.5c0-.8.3-1.5 1.5-1.5H16.5V1.4C16.2 1.3 15 1.2 13.8 1.2c-2.5 0-4.3 1.5-4.3 4.3V7H6.7v3H9.5v8h3.5v-8z" /></svg>
            </a>
            <a aria-label="LinkedIn">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5a2.5 2.5 0 11-.02 5 2.5 2.5 0 01.02-5zM3 8.9h4v12H3v-12zM9.5 8.9h3.8v1.6h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1v6.35h-4v-5.63c0-1.34-.02-3.07-1.87-3.07-1.87 0-2.16 1.46-2.16 2.97v5.73h-4v-12z" /></svg>
            </a>
            <a aria-label="Twitter">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 5.9c-.7.3-1.5.5-2.3.6.8-.5 1.5-1.3 1.8-2.3-.8.5-1.7.8-2.6 1a4.1 4.1 0 00-7 3.7A11.6 11.6 0 013.2 4.5a4.1 4.1 0 001.3 5.5c-.7 0-1.3-.2-1.8-.5v.05a4.1 4.1 0 003.3 4 4.1 4.1 0 01-1.8.07 4.1 4.1 0 003.8 2.85A8.2 8.2 0 012 18.1a11.6 11.6 0 006.3 1.85c7.5 0 11.7-6.3 11.7-11.7v-.5c.8-.6 1.5-1.3 2-2.15z" /></svg>
            </a>
          </div>
        </div>
        <div className="fcol">
          <h4>Pages</h4>
          <Link href="/">Home</Link>
          <Link href="/services">Services</Link>
          <Link href="/ai">AI Powered</Link>
          <Link href="/work">Work</Link>
          <Link href="/toolbox">Toolbox</Link>
          <Link href="/about">About Us</Link>
          <Link href="/pricing">Pricing</Link>
        </div>
        <div className="fcol">
          <h4>About</h4>
          <a>Blog</a>
          <Link href="/contact">Contact</Link>
          <a>Resources</a>
          <a>Careers</a>
        </div>
        <div className="fnews">
          <h4 style={{ fontSize: 14, fontWeight: 600, color: "#fff", marginBottom: 16 }}>Newsletters</h4>
          <p>Curious about new developments &amp; updates? Sign up for our newsletter!</p>
          <div className="fnews-in">
            <input placeholder="email@.blueworx.com" aria-label="Email address" />
            <button aria-label="Subscribe">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <line x1="5" y1="12" x2="19" y2="12" />
                <polyline points="12 5 19 12 12 19" />
              </svg>
            </button>
          </div>
        </div>
      </div>
      <div className="fbot">
        <p>© 2026 BlueWorx. All rights reserved.</p>
        <p>Powered by BabyBlue Digital.</p>
      </div>
    </footer>
  );
}
