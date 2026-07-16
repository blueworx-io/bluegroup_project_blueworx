"use client";

import { useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { resetPassword } from "@/lib/api/account";
import { errorMessage, passwordTooShort, PASSWORD_MIN } from "@/lib/auth/errors";

export default function ResetPasswordForm() {
  const token = useSearchParams().get("token") || "";
  const [password, setPassword] = useState("");
  const [done, setDone] = useState(false);
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    if (passwordTooShort(password)) { setError(`Use at least ${PASSWORD_MIN} characters.`); return; }
    setBusy(true);
    try {
      await resetPassword(token, password);
      setDone(true);
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setBusy(false);
    }
  }

  if (!token) return <p className="auth-error" role="alert">This reset link is missing its token. <Link href="/forgot-password">Request a new one</Link>.</p>;
  if (done) return <p className="auth-note" role="status">Your password has been reset. <Link href="/login">Sign in</Link>.</p>;

  return (
    <form className="auth-form" onSubmit={onSubmit} noValidate>
      <h1>Choose a new password</h1>
      {error && <p className="auth-error" role="alert">{error}</p>}
      <label htmlFor="password">New password</label>
      <input id="password" type="password" autoComplete="new-password" minLength={PASSWORD_MIN} value={password} onChange={(e) => setPassword(e.target.value)} required />
      <button className="btn btn-brand btn-md" type="submit" disabled={busy}>{busy ? "Saving…" : "Reset password"}</button>
    </form>
  );
}
