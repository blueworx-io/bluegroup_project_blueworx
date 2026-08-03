/**
 * Legacy URL redirects.
 *
 * Four pages were removed from the live site because they duplicated pages the
 * plugin already owns, or never should have been published at all:
 *
 *   /shop      → /pricing   (#22 — rendered the home page; the site has no shop)
 *   /about-us  → /about     (#23 — duplicate of the plugin's own About page)
 *   /features  → /toolbox   (#24 — overlapped Toolbox and Services)
 *   /test-page → /          (#25 — a test page that was publicly reachable)
 *
 * Deleting a published, indexable page without a redirect turns every inbound
 * link and every search result for it into a 404, so the redirect is the part
 * that has to live in the repo — the deletion happens once in wp-admin, but the
 * redirect has to keep working forever, on every environment, including a fresh
 * install where those pages never existed at all.
 *
 * That is why these assert against a clean local WordPress that has never had
 * the pages: the redirect must come from the plugin, not from a row in the live
 * site's database.
 */

import { test, expect } from '@playwright/test';

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';
const isPlaceholder = /placeholder/i.test(baseURL);

const REDIRECTS = [
  { from: '/shop', to: '/pricing' },
  { from: '/about-us', to: '/about' },
  { from: '/features', to: '/toolbox' },
  { from: '/test-page', to: '/' },
];

for (const { from, to } of REDIRECTS) {
  test(`${from} permanently redirects to ${to}`, async ({ request }) => {
    test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

    const response = await request.get(from, { maxRedirects: 0 });

    expect(response.status(), `expected ${from} to be a permanent redirect`).toBe(301);
    expect(new URL(response.headers().location, baseURL).pathname.replace(/\/$/, '')).toBe(
      to.replace(/\/$/, '')
    );
  });

  // A trailing slash is what WordPress's own pretty permalinks produce, so it
  // is the form real inbound links actually take.
  test(`${from}/ permanently redirects to ${to}`, async ({ request }) => {
    test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

    const response = await request.get(`${from}/`, { maxRedirects: 0 });

    expect(response.status()).toBe(301);
    expect(new URL(response.headers().location, baseURL).pathname.replace(/\/$/, '')).toBe(
      to.replace(/\/$/, '')
    );
  });
}

// Campaign traffic lands on these URLs with tracking params attached. Dropping
// them on redirect silently breaks attribution for every campaign that ever
// pointed at the old URL.
test('a redirect preserves the query string', async ({ request }) => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

  const response = await request.get('/about-us/?utm_source=newsletter&utm_campaign=spring', {
    maxRedirects: 0,
  });

  expect(response.status()).toBe(301);

  const location = new URL(response.headers().location, baseURL);
  expect(location.pathname.replace(/\/$/, '')).toBe('/about');
  expect(location.searchParams.get('utm_source')).toBe('newsletter');
  expect(location.searchParams.get('utm_campaign')).toBe('spring');
});

// The guard that matters most: a path-matching redirect that is too greedy
// takes out a real page. /about is one character away from /about-us, and
// /toolbox is the parent of twelve live tool pages.
test('pages the plugin owns are not redirected', async ({ request }) => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

  for (const path of ['/about/', '/toolbox/', '/pricing/', '/toolbox/surecart/', '/']) {
    const response = await request.get(path, { maxRedirects: 0 });
    expect(response.status(), `expected ${path} to render, not redirect`).toBe(200);
  }
});
