"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { verifyEmail } from "@/lib/api/account";

export default function VerifyEmail() {
  const token = useSearchParams().get("token") || "";
  const [state, setState] = useState<"working" | "ok" | "bad">("working");

  useEffect(() => {
    if (!token) { setState("bad"); return; }
    let active = true;
    verifyEmail(token).then(() => active && setState("ok")).catch(() => active && setState("bad"));
    return () => { active = false; };
  }, [token]);

  if (state === "working") return <p className="auth-note" role="status">Confirming your email…</p>;
  if (state === "ok") return <p className="auth-note" role="status">Your email is confirmed. <Link href="/login">Sign in</Link>.</p>;
  return <p className="auth-error" role="alert">That link has expired or has already been used. <Link href="/register">Start again</Link>.</p>;
}
