/**
 * The client dashboard (#37).
 *
 * This is the first signed-in surface the plugin has, and the first place where
 * getting it wrong exposes somebody's billing information rather than making a
 * page look odd. So the first thing tested here is not that the dashboard
 * renders — it is that a logged-out request cannot see any part of it, on every
 * page of it, including ones added later.
 */

import { test, expect, login, cacheBust, isPlaceholder, baseURL } from './helpers.js';

const PATHS = [
  '/dashboard/',
  '/dashboard/subscriptions/',
  '/dashboard/invoices/',
  '/dashboard/orders/',
];

test.describe('Client dashboard — logged out', () => {
  test.beforeEach(() => {
    test.skip(isPlaceholder, 'No real WordPress target configured.');
  });

  // Deliberately every path, not a sample: the gate is the security control and
  // a section that slipped past it would look exactly like a passing suite.
  for (const path of PATHS) {
    // No status assertion on the destination: the Client Login setting still
    // points at SureDash's /portal (#28), which does not exist on a bare test
    // install. What matters is that the request left the dashboard.
    test(`${path} sends a logged-out visitor to log in`, async ({ page }) => {
      await page.goto(cacheBust(path));

      expect(new URL(page.url()).pathname.replace(/\/$/, '')).not.toBe(path.replace(/\/$/, ''));
      await expect(page.locator('.dash-panel')).toHaveCount(0);
      await expect(page.locator('.dash-tabs')).toHaveCount(0);
    });
  }

  test('the visitor is sent back to the page they asked for after logging in', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/invoices/'));

    const redirectTo = new URL(page.url()).searchParams.get('redirect_to');
    expect(redirectTo).toBeTruthy();
    expect(new URL(redirectTo, baseURL).pathname.replace(/\/$/, '')).toBe('/dashboard/invoices');
  });
});

test.describe('Client dashboard — signed in', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(isPlaceholder, 'No real WordPress target configured.');
    await login(page);
  });

  test('the overview shows the account and a way into each section', async ({ page }) => {
    await page.goto('/dashboard/');

    await expect(page.locator('.dash-panel')).toHaveCount(2);
    await expect(page.locator('.dash-facts dd').first()).not.toBeEmpty();
    await expect(page.locator('.dash-cards .dash-card')).toHaveCount(3);
    await expect(page.locator('.dash-signout')).toHaveCount(1);
  });

  test('every section is reachable and marks itself as the current tab', async ({ page }) => {
    for (const section of ['subscriptions', 'invoices', 'orders']) {
      await page.goto(`/dashboard/${section}/`);

      await expect(page.locator('.dash-tab[aria-current="page"]')).toHaveCount(1);
      await expect(page.locator('.dash-tab[aria-current="page"]')).toHaveText(
        new RegExp(section, 'i')
      );
      // Nothing is wired to SureCart yet (#38-#40), so each section says so
      // rather than showing an empty table.
      await expect(page.locator('.dash-empty')).toHaveCount(1);
    }
  });

  test('the tabs link to all four pages', async ({ page }) => {
    await page.goto('/dashboard/');

    const hrefs = await page
      .locator('.dash-tabs a')
      .evaluateAll((els) => els.map((el) => new URL(el.href).pathname.replace(/\/$/, '')));

    expect(hrefs).toEqual([
      '/dashboard',
      '/dashboard/subscriptions',
      '/dashboard/invoices',
      '/dashboard/orders',
    ]);
  });

  // A search engine should not hold a copy of a customer's dashboard. Counted
  // rather than matched exactly: a site with "discourage search engines" on
  // emits its own robots tag as well, and both saying noindex is fine.
  test('no dashboard page is indexable', async ({ page }) => {
    for (const path of PATHS) {
      await page.goto(path);

      const tags = await page
        .locator('meta[name="robots"]')
        .evaluateAll((els) => els.map((el) => el.getAttribute('content') || ''));

      expect(tags.length).toBeGreaterThan(0);
      expect(tags.every((content) => /noindex/i.test(content))).toBe(true);
    }
  });

  // The dashboard is a plugin-rendered page like any other, so it must not
  // depend on the active theme.
  test('the dashboard renders the plugin document', async ({ page }) => {
    await page.goto('/dashboard/');

    await expect(page.locator('body.bw-page.bw-dashboard')).toHaveCount(1);
    await expect(page.locator('nav .nav-links')).toHaveCount(1);
  });
});
