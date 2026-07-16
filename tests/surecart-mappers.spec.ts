import { test, expect } from '@playwright/test';
import { mapSubscription, mapInvoice } from '../lib/api/surecart';

// SureCart's raw field names are a documented guess (no live API in this repo);
// these tests pin the mapping we build against and are the correction point.
test('mapSubscription maps a representative SureCart subscription', () => {
  const raw = {
    status: 'active',
    current_period_end_at: 1775001600, // 2026-04-01 (unix seconds)
    price: { amount: 49000, currency: 'usd', recurring_interval: 'month', name: 'Growth' },
    product: { name: 'Growth Retainer' },
    metadata: { site: 'bloomandco.com' },
  };
  expect(mapSubscription(raw)).toEqual({
    name: 'Growth Retainer',
    site: 'bloomandco.com',
    price: '$490',
    cycle: '/mo',
    status: 'Active',
    renews: 'Apr 1, 2026',
    icon: 'plug',
  });
});

test('mapSubscription degrades gracefully on a sparse object', () => {
  const s = mapSubscription({});
  expect(s.name).toBe('Subscription');
  expect(s.site).toBe('All sites');
  expect(s.cycle).toBe('/mo');
  expect(s.status).toBe('Active');
  expect(s.icon).toBe('plug');
});

test('mapInvoice maps number, formatted total, status, and url', () => {
  const raw = { number: 'INV-9001', created_at: 1772323200, total: 93800, currency: 'usd', status: 'paid', url: 'https://pay/x' };
  expect(mapInvoice(raw)).toEqual({ id: 'INV-9001', date: 'Mar 1, 2026', amount: '$938.00', status: 'Paid', url: 'https://pay/x' });
});

test('mapInvoice maps non-paid statuses', () => {
  expect(mapInvoice({ id: 'x', total: 100, currency: 'usd', status: 'open' }).status).toBe('Due');
  expect(mapInvoice({ id: 'x', total: 100, currency: 'usd', status: 'uncollectible' }).status).toBe('Overdue');
});
