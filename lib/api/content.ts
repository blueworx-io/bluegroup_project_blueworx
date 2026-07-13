// Content data-access layer (server-side). Marketing content flows through here.
// Live: fetch CPTs from wp/v2 and map ACF → front-end types (lib/api/mappers.ts).
// Fallback: static data from lib/data.ts whenever the CMS is unconfigured, a CPT
// is absent/empty, or a fetch fails — a public page must never render blank.

import {
  TOOLBOX_TOOLS, TOOLBOX_PLANS, RETAINER_PLANS, FAQS, HOME_REVIEWS, SOLO_PRICES,
  type Tool, type Plan,
} from "@/lib/data";
import { useMockData } from "@/lib/config";
import { listCpt } from "@/lib/api/wp";
import {
  mapTool, mapPlan, planGroup, mapFaq, mapTestimonial,
  type Faq, type Testimonial, type WpTool, type WpPlan, type WpFaq, type WpTestimonial,
} from "@/lib/api/mappers";

export type { Faq, Testimonial };

/** Run `fetcher` live; on mock mode, empty result, or any error, return `fallback`. */
async function liveOrFallback<T>(fallback: T, label: string, fetcher: () => Promise<T>): Promise<T> {
  if (useMockData) return fallback;
  try {
    const value = await fetcher();
    if (Array.isArray(value) && value.length === 0) {
      console.warn(`[content] ${label} returned empty; using static fallback`);
      return fallback;
    }
    return value;
  } catch (err) {
    console.warn(`[content] ${label} failed; using static fallback:`, err);
    return fallback;
  }
}

export async function getTools(): Promise<Tool[]> {
  return liveOrFallback(TOOLBOX_TOOLS, "getTools", async () =>
    ((await listCpt("bw_tool")) as WpTool[]).map(mapTool));
}

export async function getToolBySlug(slug: string): Promise<Tool | undefined> {
  return (await getTools()).find((t) => t.slug === slug);
}

async function getPlans(group: "toolbox" | "retainer"): Promise<Plan[]> {
  const fallback = group === "retainer" ? RETAINER_PLANS : TOOLBOX_PLANS;
  return liveOrFallback(fallback, `getPlans:${group}`, async () => {
    const items = (await listCpt("bw_plan")) as WpPlan[];
    return items.filter((i) => planGroup(i) === group).map(mapPlan);
  });
}

export function getToolboxPlans(): Promise<Plan[]> { return getPlans("toolbox"); }
export function getRetainerPlans(): Promise<Plan[]> { return getPlans("retainer"); }

export async function getFaqs(): Promise<Faq[]> {
  return liveOrFallback(FAQS, "getFaqs", async () =>
    ((await listCpt("bw_faq")) as WpFaq[]).map(mapFaq));
}

export async function getTestimonials(): Promise<Testimonial[]> {
  return liveOrFallback(HOME_REVIEWS, "getTestimonials", async () =>
    ((await listCpt("bw_testimonial")) as WpTestimonial[]).map(mapTestimonial));
}

/** slug → per-tool solo price, for the savings calculator. Derived from tools when live. */
export async function getSoloPrices(): Promise<Record<string, number>> {
  if (useMockData) return SOLO_PRICES;
  const tools = await getTools();
  const fromTools = Object.fromEntries(
    tools.filter((t) => typeof t.soloPrice === "number").map((t) => [t.slug, t.soloPrice as number]),
  );
  return Object.keys(fromTools).length ? fromTools : SOLO_PRICES;
}
