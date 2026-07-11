import { NextResponse } from "next/server";
import { config } from "@/lib/config";

// Server-side handler for the contact form. Validates the submission, then
// forwards it to the plugin / SureForms endpoint once CONTACT_FORWARD_URL is set.
// Until then it validates and acknowledges (dev/demo). See docs/API_CONTRACT.md §4.

type ContactSubmission = {
  firstName?: string;
  lastName?: string;
  email?: string;
  phone?: string;
  countryCode?: string;
  message?: string;
};

// Kept in sync with the client-side checks in components/ContactForm.tsx.
const EMAIL_RE = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
const PHONE_RE = /^[0-9+()\-\s]{7,}$/;

function validate(body: ContactSubmission): Record<string, string> {
  const errors: Record<string, string> = {};
  if (!body.firstName?.trim()) errors.firstName = "Please enter your first name";
  if (!body.lastName?.trim()) errors.lastName = "Please enter your last name";
  if (!body.email || !EMAIL_RE.test(body.email)) errors.email = "Enter a valid email address";
  if (!body.phone || !PHONE_RE.test(body.phone)) errors.phone = "Enter a valid phone number";
  if (!body.message?.trim()) errors.message = "Tell us a little about your project";
  return errors;
}

export async function POST(req: Request) {
  let body: ContactSubmission;
  try {
    body = await req.json();
  } catch {
    return NextResponse.json({ ok: false, errors: { form: "Invalid request body" } }, { status: 400 });
  }

  const errors = validate(body);
  if (Object.keys(errors).length > 0) {
    return NextResponse.json({ ok: false, errors }, { status: 400 });
  }

  if (config.contactForwardUrl) {
    try {
      const res = await fetch(config.contactForwardUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
      if (!res.ok) throw new Error(`contact forward failed: ${res.status}`);
    } catch {
      return NextResponse.json(
        { ok: false, errors: { form: "Could not send your message. Please try again." } },
        { status: 502 },
      );
    }
  }

  return NextResponse.json({ ok: true });
}
