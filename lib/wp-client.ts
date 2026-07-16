"use client";

// Browser auth client for the plugin: in-memory JWT access token + single-flight
// refresh on 401. All calls use credentials:'include' so the HttpOnly refresh
// cookie is sent. See HEADLESS_INTEGRATION.md §10. Cycle 2 consumes this; Cycle 1
// ships it as reviewed infrastructure.

import { config } from "@/lib/config";

export class WpAuthError extends Error {
  code: string;
  status: number;
  retryAfter?: number;
  constructor(code: string, message: string, status: number, retryAfter?: number) {
    super(message);
    this.name = "WpAuthError";
    this.code = code;
    this.status = status;
    this.retryAfter = retryAfter;
  }
}

/**
 * Parse the plugin's WP_Error envelope from a non-2xx response into a typed
 * error. Falls back to a generic code/message when the body isn't the expected
 * shape (HTML error pages, empty bodies, network layers).
 */
export async function errorFromResponse(res: Response): Promise<WpAuthError> {
  let code = "blueworx_request_failed";
  let message = "Something went wrong. Please try again.";
  let retryAfter: number | undefined;
  try {
    const body = (await res.json()) as {
      code?: unknown; message?: unknown; data?: { retry_after?: unknown };
    };
    if (typeof body.code === "string") code = body.code;
    if (typeof body.message === "string") message = body.message;
    if (typeof body.data?.retry_after === "number") retryAfter = body.data.retry_after;
  } catch {
    // non-JSON body — keep the generic defaults
  }
  return new WpAuthError(code, message, res.status, retryAfter);
}

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
  if (!res.ok) throw await errorFromResponse(res);
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
