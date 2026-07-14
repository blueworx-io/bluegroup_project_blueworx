"use client";

import { useState } from "react";
import Link from "next/link";
import { useAuth } from "@/lib/auth/AuthProvider";
import { errorMessage } from "@/lib/auth/errors";

export default function SignInForm({ onSuccess }: { onSuccess?: () => void }) {
  const { login } = useAuth();
  const [loginId, setLoginId] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError("");
    setBusy(true);
    try {
      await login(loginId, password);
      onSuccess?.();
    } catch (err) {
      setError(errorMessage(err));
    } finally {
      setBusy(false);
    }
  }

  return (
    <form className="auth-form" onSubmit={onSubmit} noValidate>
      <h1>Sign in</h1>
      {error && <p className="auth-error" role="alert">{error}</p>}
      <label htmlFor="login">Email or username</label>
      <input id="login" name="login" type="text" autoComplete="username" value={loginId} onChange={(e) => setLoginId(e.target.value)} required />
      <label htmlFor="password">Password</label>
      <input id="password" name="password" type="password" autoComplete="current-password" value={password} onChange={(e) => setPassword(e.target.value)} required />
      <button className="btn btn-brand btn-md" type="submit" disabled={busy}>{busy ? "Signing in…" : "Sign in"}</button>
      <p className="auth-links">
        <Link href="/forgot-password">Forgot password?</Link>
        <Link href="/register">Create an account</Link>
      </p>
    </form>
  );
}
