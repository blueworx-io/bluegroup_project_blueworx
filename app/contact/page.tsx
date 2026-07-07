import Icon from "@/components/Icon";
import ContactForm from "@/components/ContactForm";
import FaqList from "@/components/FaqList";
import Testimonials from "@/components/Testimonials";

export const metadata = { title: "Contact Us — BlueWorx" };

const CONTACT_CARDS = [
  { icon: "phone", title: "Give us a call", sub: "Mon–Fri from 8am to 5pm.", link: "+00 (704) 555-0127" },
  { icon: "chat", title: "Send us a WhatsApp", sub: "Speak to our friendly team.", link: "+00 (704) 555-0127" },
  { icon: "mail", title: "Email us here", sub: "Let us know how we can help.", link: "info@blueworx.com" },
];

export default function Contact() {
  return (
    <div>
      <section className="tech-hero" style={{ textAlign: "center", paddingBottom: 72 }}>
        <div className="tech-inner" style={{ maxWidth: 780, margin: "0 auto" }}>
          <div className="tech-badge" style={{ marginBottom: 22 }}><span className="dot"></span>Contact Us</div>
          <h1 className="h1">Start Your Conversation <span className="tech-grad">With BlueWorx</span></h1>
          <p className="lead" style={{ maxWidth: 540, margin: "22px auto 0" }}>Tell us where your digital presence is holding you back and we&apos;ll show you exactly how to fix it.</p>
          <div className="tech-status" style={{ justifyContent: "center" }}><span>reply within 1 business day</span><span>no obligation</span></div>
        </div>
      </section>

      <section style={{ padding: "56px 0 0" }}>
        <div className="contact-grid">
          <div className="contact-form">
            <ContactForm />
          </div>
          <div className="contact-illus">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src="/assets/contact-illustration.jpg" alt="Get in touch" />
          </div>
        </div>
      </section>

      <section className="contact-cards">
        <div className="blob" style={{ width: 280, height: 280, bottom: -100, right: -80, opacity: 0.14 }}></div>
        <div className="cc-grid">
          {CONTACT_CARDS.map((c) => (
            <div key={c.title} className="cc">
              <div className="cc-ic"><Icon name={c.icon} /></div>
              <h4>{c.title}</h4>
              <p>{c.sub}</p>
              <a>{c.link}</a>
            </div>
          ))}
        </div>
      </section>

      <section className="sec">
        <div className="center-head" style={{ marginBottom: 40 }}>
          <h2 className="h2">Frequently asked questions</h2>
          <p className="lead">Everything you need to know about the product and billing.</p>
        </div>
        <FaqList />
      </section>

      <Testimonials style={{ paddingTop: 0 }} />
    </div>
  );
}
