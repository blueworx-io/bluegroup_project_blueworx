// Public marketing pages — logged out — so every navigation goes through
// cacheBust(). See tests/helpers.js. Covers Toolbox, which uses the
// plan-cards, comparison-table and billing-toggle structure. Pricing was
// retired in the 2026-09 restructure (folded into ClubHouse, Hosting and
// Support — see tests/marketing-clubhouse.spec.js, marketing-hosting.spec.js
// and marketing-support.spec.js) and /pricing now redirects to /support.
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

test.describe('Marketing toolbox page', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('renders plan cards, comparison, the savings calculator, FAQ and the tool grid', async ({
    page,
  }) => {
    await page.goto(cacheBust('/toolbox/'));

    await expect(page.locator('.plans .plan-card')).toHaveCount(3);
    await expect(page.locator('table.cmp')).toHaveCount(1);
    // The savings calculator lives in the #savings section (an anchor the
    // Services page links to).
    await expect(page.locator('#savings [data-widget="savings-calc"]')).toBeVisible();
    // The dark toolbox grid lists all 12 tools with bundled favicons.
    await expect(page.locator('.tbx .tbx-card')).toHaveCount(12);
    const srcs = await page
      .locator('.tbx-card img')
      .evaluateAll((imgs) => imgs.map((i) => i.getAttribute('src') || ''));
    expect(srcs.every((s) => /\/assets\/img\/tools\//.test(s))).toBe(true);
  });

  test('the Toolbox nav link is marked active', async ({ page }) => {
    await page.goto(cacheBust('/toolbox/'));
    await expect(page.locator('nav .nav-links a.active')).toContainText('Toolbox');
  });
});
