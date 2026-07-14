"use client";

import { useState } from "react";
import Link from "next/link";
import { register } from "@/lib/api/account";
import { errorMessage, isRegistrationClosed, passwordTooShort, PASSWORD_MIN } from "@/lib/auth/errors";

type Outcome = "form" | "verify" | "ready" | "closed";

export default function RegisterForm() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [outcome, setOutcome] = useState<Outcome>("form");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    if (passwordTooShort(password)) { setError(`Use at least ${PASSWORD_MIN} characters.`); return; }
    setBusy(true);
    try {
      const r = await register(email, password);
      setOutcome(r.verificationRequired ? "verify" : "ready");
    } catch (err) {
      if (isRegistrationClosed(err)) setOutcome("closed");
      else setError(errorMessage(err));
    } finally {
      setBusy(false);
    }
  }

  if (outcome === "verify") return <p className="auth-note" role="status">Thanks! If that email can be used, a verification link is on its way. Check your inbox to finish signing up.</p>;
  if (outcome === "ready") return <p className="auth-note" role="status">Your account is ready. <Link href="/login">Sign in</Link>.</p>;
  if (outcome === "closed") return <p className="auth-note" role="status">Sign-ups are closed right now. Please check back later.</p>;

  return (
    <form className="auth-form" onSubmit={onSubmit} noValidate>
      <h1>Create your account</h1>
      {error && <p className="auth-error" role="alert">{error}</p>}
      <label htmlFor="email">Email</label>
      <input id="email" type="email" autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
      <label htmlFor="password">Password</label>
      <input id="password" type="password" autoComplete="new-password" minLength={PASSWORD_MIN} value={password} onChange={(e) => setPassword(e.target.value)} required />
      <button className="btn btn-brand btn-md" type="submit" disabled={busy}>{busy ? "Creating…" : "Create account"}</button>
      <p className="auth-links"><Link href="/login">Already have an account?</Link></p>
    </form>
  );
}
