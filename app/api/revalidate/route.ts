import { revalidatePath } from "next/cache";
import { NextRequest, NextResponse } from "next/server";
import { timingSafeEqual } from "node:crypto";
import { config } from "@/lib/config";

// CMS → frontend on-demand ISR. The plugin POSTs { paths: [...] } with the shared
// secret in X-Blueworx-Revalidate. See HEADLESS_INTEGRATION.md §8.
export async function POST(req: NextRequest) {
  const provided = req.headers.get("x-blueworx-revalidate") ?? "";
  const expected = config.revalidateSecret;
  const a = Buffer.from(provided);
  const b = Buffer.from(expected);
  if (!expected || a.length !== b.length || !timingSafeEqual(a, b)) {
    return NextResponse.json({ ok: false }, { status: 401 });
  }
  const body = (await req.json().catch(() => ({}))) as { paths?: string[] };
  const paths = Array.isArray(body.paths) ? body.paths : [];
  for (const p of paths) revalidatePath(p);
  return NextResponse.json({ ok: true, revalidated: paths });
}
