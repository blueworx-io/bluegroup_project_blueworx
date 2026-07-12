"use client";

import { useState } from "react";

type Form = {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  countryCode: string;
  message: string;
  agree: boolean;
};

type Errors = Partial<Record<keyof Form, string>>;

const EMPTY: Form = { firstName: "", lastName: "", email: "", phone: "", countryCode: "US", message: "", agree: false };

export default function ContactForm() {
  const [form, setForm] = useState<Form>(EMPTY);
  const [errors, setErrors] = useState<Errors>({});
  const [sent, setSent] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const setField = <K extends keyof Form>(k: K, v: Form[K]) => {
    setForm((f) => ({ ...f, [k]: v }));
    setErrors((e) => ({ ...e, [k]: undefined }));
  };

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    const next: Errors = {};
    if (!form.firstName.trim()) next.firstName = "Please enter your first name";
    if (!form.lastName.trim()) next.lastName = "Please enter your last name";
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(form.email)) next.email = "Enter a valid email address";
    if (!/^[0-9+()\-\s]{7,}$/.test(form.phone)) next.phone = "Enter a valid phone number";
    if (!form.message.trim()) next.message = "Tell us a little about your project";
    if (!form.agree) next.agree = "Please agree to the privacy policy";
    setErrors(next);
    if (Object.keys(next).length > 0) return;

    setSubmitting(true);
    try {
      const res = await fetch("/api/contact", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          firstName: form.firstName,
          lastName: form.lastName,
          email: form.email,
          phone: form.phone,
          countryCode: form.countryCode,
          message: form.message,
        }),
      });
      const data = (await res.json().catch(() => ({}))) as { ok?: boolean; errors?: Errors };
      if (res.ok && data.ok) {
        setSent(true);
      } else {
        setErrors(data.errors ?? { message: "Something went wrong. Please try again." });
      }
    } catch {
      setErrors({ message: "Couldn't reach the server. Please try again." });
    } finally {
      setSubmitting(false);
    }
  };

  if (sent) {
    return (
      <div className="form-success">
        <div className="ok">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
            <polyline points="20 6 9 17 4 12" />
          </svg>
        </div>
        <h3>Message sent!</h3>
        <p>Thanks for reaching out. Our friendly team will get back to you within one business day.</p>
      </div>
    );
  }

  const fieldCls = (k: keyof Form) => (errors[k] ? "field err" : "field");

  return (
    <form onSubmit={submit} noValidate>
      <h3>Contact us</h3>
      <p>Our friendly team would love to hear from you.</p>
      <div className="frow">
        <div className={fieldCls("firstName")}>
          <label htmlFor="cf-first">First name</label>
          <input id="cf-first" value={form.firstName} onChange={(e) => setField("firstName", e.target.value)} placeholder="First name" />
          {errors.firstName && <div className="msg">Please enter your first name</div>}
        </div>
        <div className={fieldCls("lastName")}>
          <label htmlFor="cf-last">Last name</label>
          <input id="cf-last" value={form.lastName} onChange={(e) => setField("lastName", e.target.value)} placeholder="Last name" />
          {errors.lastName && <div className="msg">Please enter your last name</div>}
        </div>
      </div>
      <div className={fieldCls("email")}>
        <label htmlFor="cf-email">Email</label>
        <input id="cf-email" type="email" value={form.email} onChange={(e) => setField("email", e.target.value)} placeholder="you@company.com" />
        {errors.email && <div className="msg">Enter a valid email address</div>}
      </div>
      <div className={fieldCls("phone")}>
        <label htmlFor="cf-phone">Phone number</label>
        <div className="phone-row">
          <select aria-label="Country code" value={form.countryCode} onChange={(e) => setField("countryCode", e.target.value)}>
            <option>US</option>
            <option>UK</option>
            <option>AU</option>
          </select>
          <input id="cf-phone" value={form.phone} onChange={(e) => setField("phone", e.target.value)} placeholder="+1 (555) 000-0000" style={{ flex: 1 }} />
        </div>
        {errors.phone && <div className="msg">Enter a valid phone number</div>}
      </div>
      <div className={fieldCls("message")}>
        <label htmlFor="cf-message">Message</label>
        <textarea id="cf-message" value={form.message} onChange={(e) => setField("message", e.target.value)} placeholder="Leave us a message..." />
        {errors.message && <div className="msg">Tell us a little about your project</div>}
      </div>
      <div className="agree">
        <input type="checkbox" checked={form.agree} onChange={(e) => setField("agree", e.target.checked)} id="agree" />
        <label htmlFor="agree">You agree to our friendly privacy policy.</label>
      </div>
      <button type="submit" className="btn btn-brand btn-md" style={{ width: "100%" }} disabled={submitting}>{submitting ? "Sending…" : "Send Message"}</button>
    </form>
  );
}
