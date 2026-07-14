"use client";

import { useState } from "react";
import { changePassword } from "@/lib/api/account";
import { errorMessage, passwordTooShort, PASSWORD_MIN } from "@/lib/auth/errors";

export default function ChangePassword() {
  const [current, setCurrent] = useState("");
  const [next, setNext] = useState("");
  const [done, setDone] = useState(false);
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setDone(false);
    if (passwordTooShort(next)) { setError(`Use at least ${PASSWORD_MIN} characters.`); return; }
    setBusy(true);
    try {
      await changePassword(current, next);
      setDone(true);
      setCurrent("");
      setNext("");
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setBusy(false);
    }
  }

  return (
    <form className="auth-form" onSubmit={onSubmit} noValidate style={{ border: "none", padding: 0, maxWidth: 380 }}>
      {error && <p className="auth-error" role="alert">{error}</p>}
      {done && <p className="auth-note" role="status">Password updated. You may need to sign in again on other devices.</p>}
      <label htmlFor="cur">Current password</label>
      <input id="cur" type="password" autoComplete="current-password" value={current} onChange={(e) => setCurrent(e.target.value)} required />
      <label htmlFor="new">New password</label>
      <input id="new" type="password" autoComplete="new-password" minLength={PASSWORD_MIN} value={next} onChange={(e) => setNext(e.target.value)} required />
      <button className="btn btn-brand btn-md" type="submit" disabled={busy}>{busy ? "Updating…" : "Change password"}</button>
    </form>
  );
}
