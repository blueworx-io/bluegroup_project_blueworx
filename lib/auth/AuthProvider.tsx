"use client";

import { createContext, useContext, useEffect, useState, useCallback, type ReactNode } from "react";
import { login as apiLogin, logout as apiLogout, restoreSession } from "@/lib/wp-client";
import { getMe, type Me } from "@/lib/api/account";

export type AuthStatus = "loading" | "authed" | "anon";

type AuthValue = {
  user: Me | null;
  status: AuthStatus;
  login: (loginId: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshMe: () => Promise<void>;
};

// Demo identity for CMS-less preview (requireAuth=false) — matches the portal's
// long-standing sample client so the design still previews without a backend.
const DEMO_USER: Me = {
  id: 0, email: "hannah@bloomandco.com", username: "hannah",
  display_name: "Hannah Whitfield", first_name: "Hannah", last_name: "Whitfield",
  roles: ["subscriber"], capabilities: ["read"],
};

const Ctx = createContext<AuthValue | null>(null);

export function AuthProvider({ requireAuth, children }: { requireAuth: boolean; children: ReactNode }) {
  const [user, setUser] = useState<Me | null>(requireAuth ? null : DEMO_USER);
  const [status, setStatus] = useState<AuthStatus>(requireAuth ? "loading" : "authed");

  useEffect(() => {
    if (!requireAuth) return; // demo mode: already authed with DEMO_USER
    let active = true;
    (async () => {
      const ok = await restoreSession();
      if (!active) return;
      if (!ok) { setStatus("anon"); return; }
      try {
        const me = await getMe();
        if (!active) return;
        setUser(me);
        setStatus("authed");
      } catch {
        if (active) setStatus("anon");
      }
    })();
    return () => { active = false; };
  }, [requireAuth]);

  const login = useCallback(async (loginId: string, password: string) => {
    await apiLogin(loginId, password);
    const me = await getMe();
    setUser(me);
    setStatus("authed");
  }, []);

  const logout = useCallback(async () => {
    await apiLogout();
    setUser(null);
    setStatus("anon");
  }, []);

  const refreshMe = useCallback(async () => {
    setUser(await getMe());
  }, []);

  return <Ctx.Provider value={{ user, status, login, logout, refreshMe }}>{children}</Ctx.Provider>;
}

export function useAuth(): AuthValue {
  const v = useContext(Ctx);
  if (!v) throw new Error("useAuth must be used within AuthProvider");
  return v;
}
