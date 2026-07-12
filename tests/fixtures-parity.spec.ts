import { test, expect } from '@playwright/test';
import {
  TOOLBOX_TOOLS,
  SOLO_PRICES,
  TOOLBOX_PLANS,
  RETAINER_PLANS,
  FAQS,
  HOME_REVIEWS,
} from '../lib/data';

// Fixture-parity guard. In mock mode the content data-access layer (lib/api/content.ts)
// returns these constants verbatim, so pinning them here pins exactly what every page
// renders today — and what the WordPress plugin's endpoints must reproduce field-for-field
// once it goes live. If someone changes a shape or key, this fails loudly rather than
// silently drifting away from docs/API_CONTRACT.md §3. Server-free, so it needs no build.

test.describe('content fixtures match the API contract shapes', () => {
  test('GET /tools — every tool has the contracted Tool shape', () => {
    expect(TOOLBOX_TOOLS.length).toBe(12);
    const categories = new Set(['Build', 'Grow', 'Sell', 'Automate', 'Support']);
    for (const tool of TOOLBOX_TOOLS) {
      expect(typeof tool.slug).toBe('string');
      expect(tool.slug.length).toBeGreaterThan(0);
      expect(typeof tool.name).toBe('string');
      expect(typeof tool.desc).toBe('string');
      expect(typeof tool.domain).toBe('string');
      expect(categories.has(tool.category)).toBe(true);
      expect(typeof tool.tagline).toBe('string');
      expect(Array.isArray(tool.features)).toBe(true);
      expect(tool.features.length).toBe(6);
      for (const f of tool.features) {
        expect(typeof f.icon).toBe('string');
        expect(typeof f.title).toBe('string');
        expect(typeof f.desc).toBe('string');
      }
    }
  });

  test('slugs are unique and every slug has a solo price (savings-calc parity)', () => {
    const slugs = TOOLBOX_TOOLS.map((t) => t.slug);
    expect(new Set(slugs).size).toBe(slugs.length);

    // SOLO_PRICES and the tools list are two sources today; keep them in lockstep
    // so the savings calculator never shows $0 for a real tool (and vice versa).
    for (const slug of slugs) {
      expect(typeof SOLO_PRICES[slug], `missing solo price for "${slug}"`).toBe('number');
    }
    expect(Object.keys(SOLO_PRICES).sort()).toEqual([...slugs].sort());
  });

  test('GET /plans — toolbox and retainer plans have the contracted Plan shape', () => {
    expect(TOOLBOX_PLANS.length).toBe(3);
    expect(RETAINER_PLANS.length).toBe(3);
    for (const plan of [...TOOLBOX_PLANS, ...RETAINER_PLANS]) {
      expect(typeof plan.name).toBe('string');
      expect(typeof plan.desc).toBe('string');
      expect(typeof plan.priceM).toBe('number');
      expect(typeof plan.priceA).toBe('number');
      expect(typeof plan.feat).toBe('boolean');
      expect(typeof plan.btn).toBe('string');
      expect(Array.isArray(plan.features)).toBe(true);
      expect(plan.features.length).toBeGreaterThan(0);
    }
  });

  test('GET /faqs and /testimonials — contracted shapes', () => {
    expect(FAQS.length).toBe(5);
    for (const faq of FAQS) {
      expect(typeof faq.q).toBe('string');
      expect(typeof faq.a).toBe('string');
    }
    expect(HOME_REVIEWS.length).toBeGreaterThan(0);
    for (const r of HOME_REVIEWS) {
      expect(typeof r.text).toBe('string');
      expect(typeof r.initials).toBe('string');
      expect(typeof r.name).toBe('string');
      expect(typeof r.role).toBe('string');
    }
  });
});
