// Pure mapping of the plugin's error codes to UI copy, plus password rules.
// Non-enumeration (register/forgot/resend always generic) is a UI rule enforced
// in those forms, not here. See HEADLESS_INTEGRATION.md §7.
import { WpAuthError } from "@/lib/wp-client";

export const PASSWORD_MIN = 8;
export function passwordTooShort(pw: string): boolean {
  return pw.length < PASSWORD_MIN;
}

const MESSAGES: Record<string, string> = {
  blueworx_invalid_login: "Incorrect email or password.",
  blueworx_email_unverified: "Please confirm your email address to sign in.",
  blueworx_registration_closed: "Sign-ups are closed right now.",
  blueworx_weak_password: "Use at least 8 characters.",
  blueworx_invalid_token: "That link has expired or already been used.",
  blueworx_refresh_reuse: "Your session expired for security. Please sign in again.",
  blueworx_rate_limited: "Too many attempts. Please wait a moment and try again.",
  blueworx_auth_unconfigured: "Sign-in is temporarily unavailable. Please try again later.",
};

const GENERIC = "Something went wrong. Please try again.";

export function errorMessage(err: unknown): string {
  const code = err instanceof WpAuthError ? err.code : "";
  return MESSAGES[code] ?? GENERIC;
}

export function isUnverified(err: unknown): boolean {
  return err instanceof WpAuthError && err.code === "blueworx_email_unverified";
}
export function isRegistrationClosed(err: unknown): boolean {
  return err instanceof WpAuthError && err.code === "blueworx_registration_closed";
}
export function retryAfterSeconds(err: unknown): number | undefined {
  return err instanceof WpAuthError ? err.retryAfter : undefined;
}
