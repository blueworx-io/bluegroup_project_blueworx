import { test, expect } from '@playwright/test';
import { mapTool, mapPlan, planGroup, mapFaq, mapTestimonial, type WpTool, type WpPlan, type WpFaq, type WpTestimonial } from '../lib/api/mappers';

test('mapTool maps a CPT+ACF item to the Tool shape', () => {
  const item = {
    id: 1, slug: 'sureforms', title: { rendered: 'SureForms' }, content: { rendered: '' },
    acf: {
      desc: 'Flexible form builder.', domain: 'sureforms.com', category: 'Build',
      popular: true, tagline: 'Build forms that convert.', solo_price: 9,
      features: [{ icon: 'workflow', title: 'Conditional logic', desc: 'Show or hide fields.' }],
    },
  };
  expect(mapTool(item as WpTool)).toEqual({
    slug: 'sureforms', name: 'SureForms', desc: 'Flexible form builder.',
    domain: 'sureforms.com', category: 'Build', popular: true,
    tagline: 'Build forms that convert.', soloPrice: 9,
    features: [{ icon: 'workflow', title: 'Conditional logic', desc: 'Show or hide fields.' }],
  });
});

test('mapTool omits popular/soloPrice when absent and decodes entities', () => {
  const item = { id: 2, slug: 'x', title: { rendered: 'A &amp; B' }, content: { rendered: '' },
    acf: { desc: 'd', domain: 'x.com', category: 'Grow', tagline: 't', features: [] } };
  const out = mapTool(item as WpTool);
  expect(out.name).toBe('A & B');
  expect('popular' in out).toBe(false);
  expect('soloPrice' in out).toBe(false);
  expect(out.features).toEqual([]);
});

test('mapPlan derives btn/pop and splits newline features; planGroup reads plan_group', () => {
  const item = { id: 3, slug: 'business', title: { rendered: 'Business' }, content: { rendered: '' },
    acf: { plan_group: 'toolbox', desc: 'For businesses.', price_monthly: 60, price_annual: 50,
      featured: true, popular: true, features: 'All 12+ tools\nUp to 5 websites' } };
  expect(planGroup(item as WpPlan)).toBe('toolbox');
  expect(mapPlan(item as WpPlan)).toEqual({
    name: 'Business', desc: 'For businesses.', priceM: 60, priceA: 50,
    feat: true, pop: true, btn: 'plan-btn dark', features: ['All 12+ tools', 'Up to 5 websites'],
  });
});

test('mapPlan non-featured plan gets the outline button and no pop key', () => {
  const item = { id: 4, slug: 'personal', title: { rendered: 'Personal' }, content: { rendered: '' },
    acf: { plan_group: 'toolbox', desc: 'd', price_monthly: 30, price_annual: 25,
      featured: false, features: ['A', 'B'] } };
  const out = mapPlan(item as WpPlan);
  expect(out.btn).toBe('plan-btn out');
  expect('pop' in out).toBe(false);
});

test('mapFaq uses the title as question and ACF answer as answer', () => {
  const item = { id: 5, slug: 'q', title: { rendered: 'How do payments work?' }, content: { rendered: '' },
    acf: { answer: 'Pay and forget!' } };
  expect(mapFaq(item as WpFaq)).toEqual({ q: 'How do payments work?', a: 'Pay and forget!' });
});

test('mapTestimonial derives a single-letter initial from the name', () => {
  const item = { id: 6, slug: 't', title: { rendered: 'Hannah Whitfield' }, content: { rendered: '' },
    acf: { quote: 'It just works.', role: 'Owner, Bloom & Co.' } };
  expect(mapTestimonial(item as WpTestimonial)).toEqual({
    text: 'It just works.', initials: 'H', name: 'Hannah Whitfield', role: 'Owner, Bloom & Co.',
  });
});
