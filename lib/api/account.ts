// Account / auth operations against blueworx/v1. Public operations use a
// credentials:'include' fetch (register may set the refresh cookie); authed
// operations go through api() from wp-client. Every non-2xx becomes a typed
// WpAuthError. See HEADLESS_INTEGRATION.md §5.1–§5.2.
import { config } from "@/lib/config";
import { api, errorFromResponse } from "@/lib/wp-client";

export type Me = {
  id: number;
  email: string;
  username: string;
  display_name: string;
  first_name: string;
  last_name: string;
  roles: string[];
  capabilities?: string[];
};

export type RegisterResult = { verificationRequired: boolean; user?: Me };

function publicPost(path: string, body: unknown): Promise<Response> {
  return fetch(`${config.blueworxApi}${path}`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
}

export async function register(email: string, password: string, inviteToken?: string): Promise<RegisterResult> {
  const res = await publicPost("/account/register", {
    email, password, ...(inviteToken ? { invite_token: inviteToken } : {}),
  });
  if (!res.ok) throw await errorFromResponse(res);
  const data = (await res.json().catch(() => ({}))) as { access_token?: string; user?: Me };
  // Verification off → a full login session; verification on → generic success.
  if (data.access_token) return { verificationRequired: false, user: data.user };
  return { verificationRequired: true };
}

export async function verifyEmail(token: string): Promise<void> {
  const res = await publicPost("/account/verify", { token });
  if (!res.ok) throw await errorFromResponse(res);
}

export async function resendVerification(email: string): Promise<void> {
  const res = await publicPost("/account/resend-verification", { email });
  if (!res.ok) throw await errorFromResponse(res);
}

export async function forgotPassword(email: string): Promise<void> {
  const res = await publicPost("/account/password/forgot", { email });
  if (!res.ok) throw await errorFromResponse(res);
}

export async function resetPassword(token: string, password: string): Promise<void> {
  const res = await publicPost("/account/password/reset", { token, password });
  if (!res.ok) throw await errorFromResponse(res);
}

export async function changePassword(currentPassword: string, newPassword: string): Promise<void> {
  const res = await api("/account/password/change", {
    method: "POST",
    body: JSON.stringify({ current_password: currentPassword, new_password: newPassword }),
  });
  if (!res.ok) throw await errorFromResponse(res);
}

export async function getMe(): Promise<Me> {
  const res = await api("/auth/me");
  if (!res.ok) throw await errorFromResponse(res);
  return res.json() as Promise<Me>;
}
