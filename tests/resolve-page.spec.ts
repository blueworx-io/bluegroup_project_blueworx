import { test, expect } from '@playwright/test';
import { decideOutcome } from '../lib/api/resolve-page';

test('null (fetch failed) → notFound', () => {
  expect(decideOutcome(null)).toEqual({ kind: 'notFound' });
});

test('a 404 resolve result → notFound', () => {
  expect(decideOutcome({ type: '404', id: 0, slug: '', rest_url: '', template: '404' }))
    .toEqual({ kind: 'notFound' });
});

test('a resolvable page with an empty rest_url → notFound', () => {
  expect(decideOutcome({ type: 'page', id: 12, slug: 'about', rest_url: '', template: 'single' }))
    .toEqual({ kind: 'notFound' });
});

test('a resolvable page → render with its rest_url', () => {
  expect(decideOutcome({ type: 'page', id: 12, slug: 'about', rest_url: 'https://cms/wp-json/wp/v2/pages/12', template: 'single' }))
    .toEqual({ kind: 'render', restUrl: 'https://cms/wp-json/wp/v2/pages/12' });
});
