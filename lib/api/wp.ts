// Low-level fetchers for the BlueWorx WordPress plugin's generic endpoints.
// Single responsibility: HTTP to the CMS, typed JSON out. Used for site config,
// nav menus, path resolution, and real WordPress page bodies. Shapes mirror the
// plugin's HEADLESS_INTEGRATION.md §5–§6.

import { config } from "@/lib/config";

export type WpSite = {
  name: string; description: string; url: string; admin_url: string;
  language: string; timezone: string; date_format: string; time_format: string;
  posts_per_page: number; show_on_front: string;
  page_on_front: number; page_for_posts: number; site_logo: string | null;
};

export type MenuItem = {
  id: number; title: string; url: string; target: string;
  object: string; object_id: number; children: MenuItem[];
};

export type ResolveResult = {
  type: string; id: number; slug: string; rest_url: string; template: string;
};

export type WpRendered = { rendered: string };
export type WpContent<A = Record<string, unknown>> = {
  id: number; slug: string; title: WpRendered; content: WpRendered;
  excerpt?: WpRendered; acf?: A;
};

const REVALIDATE_DEFAULT = 300;

async function getJson<T>(url: string, revalidate = REVALIDATE_DEFAULT): Promise<T> {
  const res = await fetch(url, { next: { revalidate } });
  if (!res.ok) throw new Error(`WordPress GET ${url} failed: ${res.status} ${res.statusText}`);
  return res.json() as Promise<T>;
}

export function getSite(): Promise<WpSite> {
  return getJson<WpSite>(`${config.blueworxApi}/site`, 3600);
}

export async function getMenu(location: string): Promise<MenuItem[]> {
  const data = await getJson<{ location: string; items: MenuItem[] }>(
    `${config.blueworxApi}/menus/${encodeURIComponent(location)}`, 3600,
  );
  return data.items;
}

export function resolve(uri: string): Promise<ResolveResult> {
  return getJson<ResolveResult>(`${config.blueworxApi}/resolve?uri=${encodeURIComponent(uri)}`);
}

export function getAcfOptions(): Promise<Record<string, unknown>> {
  return getJson<Record<string, unknown>>(`${config.blueworxApi}/acf-options`, 3600);
}

export function getByRestUrl<A = Record<string, unknown>>(restUrl: string): Promise<WpContent<A>> {
  return getJson<WpContent<A>>(restUrl);
}

/** Menu URLs point at the WP origin; strip it so <Link>s stay on the front-end (§5.3). */
export function rewriteMenuUrl(url: string): string {
  if (config.wpOrigin && url.startsWith(config.wpOrigin)) {
    return url.slice(config.wpOrigin.length) || "/";
  }
  try {
    return new URL(url).pathname;
  } catch {
    return url; // already a relative path
  }
}
