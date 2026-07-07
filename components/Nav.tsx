"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { TOOLBOX_TOOLS, faviconUrl } from "@/lib/data";

const CHEVRON = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ width: 13, height: 13 }}>
    <polyline points="6 9 12 15 18 9" />
  </svg>
);

export default function Nav() {
  const pathname = usePathname();
  const router = useRouter();
  const [megaOpen, setMegaOpen] = useState(false);
  const [aboutOpen, setAboutOpen] = useState(false);
  const [mobileNav, setMobileNav] = useState(false);
  const megaT = useRef<ReturnType<typeof setTimeout> | null>(null);
  const aboutT = useRef<ReturnType<typeof setTimeout> | null>(null);
  const navRef = useRef<HTMLElement>(null);
  const mobileNavRef = useRef(mobileNav);
  mobileNavRef.current = mobileNav;

  // Hide-on-scroll-down / reveal-on-scroll-up behavior from the design export.
  useEffect(() => {
    let lastY = window.scrollY || 0;
    let tick = false;
    const onScroll = () => {
      if (tick) return;
      tick = true;
      requestAnimationFrame(() => {
        const nav = navRef.current;
        const y = window.scrollY || 0;
        if (nav) {
          nav.classList.toggle("nav-scrolled", y > 8);
          if (mobileNavRef.current) {
            nav.classList.remove("nav-hidden");
          } else {
            const goingDown = y > lastY + 4;
            const goingUp = y < lastY - 4;
            if (y > 160 && goingDown) nav.classList.add("nav-hidden");
            else if (goingUp || y <= 160) nav.classList.remove("nav-hidden");
          }
        }
        lastY = y;
        tick = false;
      });
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  // Lock body scroll while the mobile menu is open.
  useEffect(() => {
    document.body.style.overflow = mobileNav ? "hidden" : "";
    if (mobileNav) navRef.current?.classList.remove("nav-hidden");
    return () => {
      document.body.style.overflow = "";
    };
  }, [mobileNav]);

  // Close overlays on navigation.
  useEffect(() => {
    setMobileNav(false);
    setMegaOpen(false);
    setAboutOpen(false);
  }, [pathname]);

  const cls = (href: string) =>
    (href === "/" ? pathname === "/" : pathname.startsWith(href)) ? "active" : "";

  const openMega = () => {
    if (megaT.current) clearTimeout(megaT.current);
    setMegaOpen(true);
  };
  const closeMega = () => {
    if (megaT.current) clearTimeout(megaT.current);
    megaT.current = setTimeout(() => setMegaOpen(false), 300);
  };
  const openAbout = () => {
    if (aboutT.current) clearTimeout(aboutT.current);
    setAboutOpen(true);
  };
  const closeAbout = () => {
    if (aboutT.current) clearTimeout(aboutT.current);
    aboutT.current = setTimeout(() => setAboutOpen(false), 300);
  };

  const aiBadge = <span className="nav-tag tag-light">New</span>;

  return (
    <>
      <nav ref={navRef}>
        <div className="nav-logo" onClick={() => router.push("/")}>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src="/assets/logo.png" alt="BlueWorx" />
        </div>
        <div className="nav-links">
          <Link className={cls("/")} href="/">Home</Link>
          <Link className={cls("/services")} href="/services">Services</Link>
          <div style={{ position: "relative" }} onMouseEnter={openMega} onMouseLeave={closeMega}>
            <Link className={cls("/toolbox")} href="/toolbox">Toolbox {CHEVRON}</Link>
            {megaOpen && (
              <div
                style={{
                  position: "absolute",
                  top: "calc(100% + 14px)",
                  left: "50%",
                  transform: "translateX(-50%)",
                  width: 760,
                  background: "#0A0C29",
                  borderRadius: 20,
                  boxShadow: "0 30px 70px rgba(10,12,41,.35)",
                  padding: 28,
                  display: "grid",
                  gridTemplateColumns: "1fr 1fr",
                  gap: "6px 18px",
                  zIndex: 60,
                }}
              >
                {TOOLBOX_TOOLS.map((t) => (
                  <Link
                    key={t.slug}
                    href={`/toolbox/${t.slug}`}
                    className="mega-item"
                    style={{
                      display: "flex",
                      gap: 12,
                      alignItems: "flex-start",
                      padding: 12,
                      borderRadius: 12,
                      cursor: "pointer",
                      textDecoration: "none",
                    }}
                  >
                    <div style={{ width: 38, height: 38, borderRadius: 10, background: "#fff", flexShrink: 0, display: "flex", alignItems: "center", justifyContent: "center", overflow: "hidden" }}>
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img src={faviconUrl(t.domain)} alt={t.name} style={{ width: 22, height: 22, objectFit: "contain" }} />
                    </div>
                    <div>
                      <div style={{ fontSize: "14.5px", fontWeight: 600, color: "#fff", display: "flex", alignItems: "center", gap: 7 }}>
                        {t.name}
                        {t.popular && <span className="nav-tag tag-dark">Popular</span>}
                      </div>
                      <div style={{ fontSize: "12.5px", color: "rgba(255,255,255,.5)", lineHeight: 1.4, marginTop: 2 }}>{t.desc}</div>
                    </div>
                  </Link>
                ))}
                <div style={{ gridColumn: "1 / -1", borderTop: "1px solid rgba(255,255,255,.1)", marginTop: 8, paddingTop: 16, display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                  <span style={{ fontSize: 13, color: "rgba(255,255,255,.5)" }}>12 tools, one subscription.</span>
                  <Link href="/toolbox" style={{ fontSize: 14, fontWeight: 600, color: "#A5A7FF", cursor: "pointer", display: "flex", alignItems: "center", gap: 6, textDecoration: "none" }}>
                    Browse the full Toolbox
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ width: 14, height: 14 }}>
                      <line x1="7" y1="17" x2="17" y2="7" />
                      <polyline points="7 7 17 7 17 17" />
                    </svg>
                  </Link>
                </div>
              </div>
            )}
          </div>
          <Link className={cls("/pricing")} href="/pricing">Pricing</Link>
          <div style={{ position: "relative" }} onMouseEnter={openAbout} onMouseLeave={closeAbout}>
            <Link className={cls("/about")} href="/about">About Us {CHEVRON}</Link>
            {aboutOpen && (
              <div style={{ position: "absolute", top: "calc(100% + 10px)", left: 0, minWidth: 160, background: "#0A0C29", borderRadius: 14, boxShadow: "0 20px 50px rgba(10,12,41,.3)", padding: 8, zIndex: 60 }}>
                <Link className={cls("/work")} href="/work" style={{ display: "block", padding: "10px 14px", color: "#fff", fontSize: "14.5px", fontWeight: 500, borderRadius: 8, textDecoration: "none" }}>
                  Work
                </Link>
              </div>
            )}
          </div>
          <Link className={cls("/ai")} href="/ai">AI Powered{aiBadge}</Link>
        </div>
        <div className="nav-cta">
          <span className="nav-sign-in" onClick={() => router.push("/portal")}>Client Login</span>
          <button className="nav-btn" onClick={() => router.push("/pricing")}>
            Get a Quote
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2">
              <line x1="7" y1="17" x2="17" y2="7" />
              <polyline points="7 7 17 7 17 17" />
            </svg>
          </button>
        </div>
        <span className="nav-sign-in-mobile" onClick={() => router.push("/portal")}>Client Login</span>
        <button
          className={"hamburger" + (mobileNav ? " open" : "")}
          aria-label="Toggle menu"
          aria-expanded={mobileNav}
          onClick={() => setMobileNav((v) => !v)}
        >
          <span></span>
          <span></span>
        </button>
      </nav>

      {mobileNav && (
        <div className="mobile-menu">
          <Link className={cls("/")} href="/">Home</Link>
          <Link className={cls("/services")} href="/services">Services</Link>
          <Link className={cls("/toolbox")} href="/toolbox">Toolbox</Link>
          <div style={{ display: "flex", flexDirection: "column", gap: 0, paddingLeft: 12, borderLeft: "2px solid rgba(79,70,229,.15)", margin: "0 0 4px" }}>
            {TOOLBOX_TOOLS.map((t) => (
              <Link key={t.slug} href={`/toolbox/${t.slug}`} style={{ fontSize: "13.5px", padding: "8px 8px" }}>
                {t.name}
              </Link>
            ))}
          </div>
          <Link className={cls("/pricing")} href="/pricing">Pricing</Link>
          <Link className={cls("/about")} href="/about">About Us</Link>
          <Link className={cls("/work")} href="/work" style={{ fontSize: "13.5px", paddingLeft: 24 }}>Work</Link>
          <Link className={cls("/ai")} href="/ai">AI Powered{aiBadge}</Link>
          <Link href="/portal">Client Login</Link>
          <button className="btn btn-brand btn-md" onClick={() => router.push("/pricing")}>Get a Quote</button>
        </div>
      )}
    </>
  );
}
