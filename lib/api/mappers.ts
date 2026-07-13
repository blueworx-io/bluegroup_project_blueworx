// Pure mappers: WordPress CPT + ACF payloads → front-end marketing types.
// No I/O — unit-testable in isolation. The CMS content model these expect is
// documented in docs/cms-content-model.md.

import type { Tool, Plan } from "@/lib/data";
import type { WpContent } from "@/lib/api/wp";

export type Faq = { q: string; a: string };
export type Testimonial = { text: string; initials: string; name: string; role: string };

type ToolAcf = {
  desc: string; domain: string; category: string; popular?: boolean;
  tagline: string; solo_price?: number;
  features: { icon: string; title: string; desc: string }[];
};
type PlanAcf = {
  plan_group: string; desc: string;
  price_monthly: number; price_annual: number;
  featured?: boolean; popular?: boolean;
  features: string[] | string;
};
type FaqAcf = { answer?: string };
type TestimonialAcf = { quote: string; role: string };

export type WpTool = WpContent<ToolAcf> & { acf: ToolAcf };
export type WpPlan = WpContent<PlanAcf> & { acf: PlanAcf };
export type WpFaq = WpContent<FaqAcf> & { acf: FaqAcf };
export type WpTestimonial = WpContent<TestimonialAcf> & { acf: TestimonialAcf };

/** Minimal HTML-entity decode for WP `rendered` strings used as plain text. */
function decode(s: string): string {
  return s
    .replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">")
    .replace(/&#8217;/g, "’").replace(/&#8211;/g, "–").replace(/&quot;/g, '"');
}

export function mapTool(item: WpTool): Tool {
  const a = item.acf;
  return {
    slug: item.slug,
    name: decode(item.title.rendered),
    desc: a.desc,
    domain: a.domain,
    category: a.category,
    ...(a.popular ? { popular: true } : {}),
    tagline: a.tagline,
    features: (a.features ?? []).map((f) => ({ icon: f.icon, title: f.title, desc: f.desc })),
    ...(typeof a.solo_price === "number" ? { soloPrice: a.solo_price } : {}),
  };
}

export function planGroup(item: WpPlan): "toolbox" | "retainer" {
  return item.acf.plan_group === "retainer" ? "retainer" : "toolbox";
}

export function mapPlan(item: WpPlan): Plan {
  const a = item.acf;
  const featured = !!a.featured;
  const features = Array.isArray(a.features)
    ? a.features
    : String(a.features ?? "").split("\n").map((s) => s.trim()).filter(Boolean);
  return {
    name: decode(item.title.rendered),
    desc: a.desc,
    priceM: a.price_monthly,
    priceA: a.price_annual,
    feat: featured,
    ...(a.popular ? { pop: true } : {}),
    btn: featured ? "plan-btn dark" : "plan-btn out", // presentation, derived front-end
    features,
  };
}

export function mapFaq(item: WpFaq): Faq {
  return { q: decode(item.title.rendered), a: decode(item.acf.answer ?? item.content?.rendered ?? "") };
}

export function mapTestimonial(item: WpTestimonial): Testimonial {
  const name = decode(item.title.rendered);
  return {
    text: decode(item.acf.quote),
    initials: name.trim().charAt(0).toUpperCase(), // matches the single-letter mock convention
    name,
    role: decode(item.acf.role),
  };
}
