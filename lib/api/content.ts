// Content data-access layer (server-side) for marketing collections.
//
// Marketing content (tools, plans, FAQs, testimonials) is intentionally STATIC —
// it lives in lib/data.ts and is edited via a code change / PR. There are no
// WordPress custom post types behind it. Real WordPress *pages* are still served
// separately through the catch-all resolve→wp/v2 flow (see lib/api/wp.ts); only
// these marketing collections are static.
//
// These stay async so the calling Server Components (which `await` them) don't
// change if a data set later moves to a live source.

import {
  TOOLBOX_TOOLS, TOOLBOX_PLANS, RETAINER_PLANS, FAQS, HOME_REVIEWS, SOLO_PRICES,
  type Tool, type Plan,
} from "@/lib/data";

export type Faq = { q: string; a: string };
export type Testimonial = { text: string; initials: string; name: string; role: string };

export async function getTools(): Promise<Tool[]> {
  return TOOLBOX_TOOLS;
}

export async function getToolBySlug(slug: string): Promise<Tool | undefined> {
  return TOOLBOX_TOOLS.find((t) => t.slug === slug);
}

export async function getToolboxPlans(): Promise<Plan[]> {
  return TOOLBOX_PLANS;
}

export async function getRetainerPlans(): Promise<Plan[]> {
  return RETAINER_PLANS;
}

export async function getFaqs(): Promise<Faq[]> {
  return FAQS;
}

export async function getTestimonials(): Promise<Testimonial[]> {
  return HOME_REVIEWS;
}

/** slug → per-tool solo price, for the savings calculator. */
export async function getSoloPrices(): Promise<Record<string, number>> {
  return SOLO_PRICES;
}
