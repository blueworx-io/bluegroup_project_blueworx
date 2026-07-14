"use client";

import { useState } from "react";
import Link from "next/link";
import { forgotPassword } from "@/lib/api/account";

export default function ForgotPasswordForm() {
  const [email, setEmail] = useState("");
  const [done, setDone] = useState(false);
  const [busy, setBusy] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    try {
      await forgotPassword(email);
    } catch {
      // Non-enumerating: never reveal whether the email exists — show generic success regardless.
    } finally {
      setBusy(false);
      setDone(true);
    }
  }

  if (done) return <p className="auth-note" role="status">If that email can be used, a reset link is on its way. Check your inbox.</p>;

  return (
    <form className="auth-form" onSubmit={onSubmit} noValidate>
      <h1>Reset your password</h1>
      <label htmlFor="email">Email</label>
      <input id="email" type="email" autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
      <button className="btn btn-brand btn-md" type="submit" disabled={busy}>{busy ? "Sending…" : "Send reset link"}</button>
      <p className="auth-links"><Link href="/login">Back to sign in</Link></p>
    </form>
  );
}
