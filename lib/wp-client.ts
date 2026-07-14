"use client";

// Browser auth client for the plugin: in-memory JWT access token + single-flight
// refresh on 401. All calls use credentials:'include' so the HttpOnly refresh
// cookie is sent. See HEADLESS_INTEGRATION.md §10. Cycle 2 consumes this; Cycle 1
// ships it as reviewed infrastructure.

import { config } from "@/lib/config";

let accessToken: string | null = null;
let refreshing: Promise<boolean> | null = null;

export function setAccessToken(t: string | null) { accessToken = t; }
export function getAccessToken() { return accessToken; }

async function refresh(): Promise<boolean> {
  refreshing ??= (async () => {
    try {
      const res = await fetch(`${config.blueworxApi}/auth/refresh`, {
        method: "POST",
        credentials: "include",
      });
      if (!res.ok) return false;
      const data = await res.json();
      accessToken = data.access_token;
      return true;
    } catch {
      return false;
    } finally {
      refreshing = null;
    }
  })();
  return refreshing;
}

/** Authenticated fetch against blueworx/v1 with one automatic refresh+retry. */
export async function api(path: string, init: RequestInit = {}): Promise<Response> {
  const call = () =>
    fetch(`${config.blueworxApi}${path}`, {
      ...init,
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
        ...(accessToken ? { Authorization: `Bearer ${accessToken}` } : {}),
        ...init.headers,
      },
    });

  let res = await call();
  if (res.status === 401 && (await refresh())) res = await call();
  return res;
}

export async function login(loginId: string, password: string) {
  const res = await fetch(`${config.blueworxApi}/auth/login`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ login: loginId, password }),
  });
  if (!res.ok) throw await res.json();
  const data = await res.json();
  accessToken = data.access_token;
  return data.user;
}

export async function logout() {
  await fetch(`${config.blueworxApi}/auth/logout`, { method: "POST", credentials: "include" });
  accessToken = null;
}

/** Restore a session on app load (a hard reload loses the in-memory token). */
export async function restoreSession(): Promise<boolean> {
  return refresh();
}
