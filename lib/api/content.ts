// Content data-access layer (server-side).
//
// This is the seam between the front-end and the BlueWorx WordPress plugin.
// Every page reads its content through these functions. Today they return the
// captured mock data from lib/data.ts; once NEXT_PUBLIC_WP_API_URL is set, they
// fetch the real content instead — with no changes required in any page or
// component. See docs/API_CONTRACT.md §3 for the endpoint shapes.

import {
  TOOLBOX_TOOLS,
  TOOLBOX_PLANS,
  RETAINER_PLANS,
  FAQS,
  HOME_REVIEWS,
  SOLO_PRICES,
  type Tool,
  type Plan,
} from "@/lib/data";
import { config, useMockData } from "@/lib/config";

type Faq = { q: string; a: string };
type Testimonial = { text: string; initials: string; name: string; role: string };

/** Fetch JSON from the plugin REST API. Content is cacheable; revalidate periodically. */
async function fetchFromApi<T>(path: string): Promise<T> {
  const res = await fetch(`${config.wpApiUrl}${path}`, {
    headers: config.wpApiToken ? { Authorization: `Bearer ${config.wpApiToken}` } : undefined,
    next: { revalidate: 300 },
  });
  if (!res.ok) {
    throw new Error(`BlueWorx content API ${path} failed: ${res.status} ${res.statusText}`);
  }
  return res.json() as Promise<T>;
}

export async function getTools(): Promise<Tool[]> {
  if (useMockData) return TOOLBOX_TOOLS;
  return fetchFromApi<Tool[]>("/tools");
}

export async function getToolBySlug(slug: string): Promise<Tool | undefined> {
  const tools = await getTools();
  return tools.find((t) => t.slug === slug);
}

export async function getToolboxPlans(): Promise<Plan[]> {
  if (useMockData) return TOOLBOX_PLANS;
  const { toolbox } = await fetchFromApi<{ toolbox: Plan[]; retainers: Plan[] }>("/plans");
  return toolbox;
}

export async function getRetainerPlans(): Promise<Plan[]> {
  if (useMockData) return RETAINER_PLANS;
  const { retainers } = await fetchFromApi<{ toolbox: Plan[]; retainers: Plan[] }>("/plans");
  return retainers;
}

export async function getFaqs(): Promise<Faq[]> {
  if (useMockData) return FAQS;
  return fetchFromApi<Faq[]>("/faqs");
}

/** Marketing testimonials (rendered by Testimonials.tsx) — see docs/API_CONTRACT.md §3.3. */
export async function getTestimonials(): Promise<Testimonial[]> {
  if (useMockData) return HOME_REVIEWS;
  return fetchFromApi<Testimonial[]>("/testimonials");
}

/** slug → per-tool solo price, for the savings calculator. */
export async function getSoloPrices(): Promise<Record<string, number>> {
  if (useMockData) return SOLO_PRICES;
  return fetchFromApi<Record<string, number>>("/tools/solo-prices");
}
