// Public marketing page — logged out — so every navigation goes through
// cacheBust(). See tests/helpers.js.
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

// The eleven live sites, in the order blueworx_content_portfolio() lists them.
const SITES = [
  'https://hiraste.com/',
  'https://padlx.com.au/',
  'https://toptiertutors.co.za/',
  'https://worldsquashofficiating.com/',
  'https://fifthmovement.co.uk/',
  'https://forumlightingsolutions.com/',
  'https://thechange.work/',
  'https://jens-pflueger.de/en/',
  'https://studiolalaland.com/',
  'https://chromaesthesia.space/',
  'https://cansakhara.com/',
];

test.describe('Marketing portfolio page', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('renders the two-column hero, eleven site cards, stats and the shared reviews', async ({ page }) => {
    await page.goto(cacheBust('/portfolio/'));

    await expect(page.locator('.bw-page')).toHaveCount(1);
    await expect(page.locator('.tech-hero .tech-2col')).toHaveCount(1);
    await expect(page.locator('.work-grid .work-card')).toHaveCount(SITES.length);
    await expect(page.locator('.stats-band')).toHaveCount(1);
    // The same reviews section every page carries — three, no page-specific set.
    await expect(page.locator('.center-head')).toContainText('Kind words from our customers');
    await expect(page.locator('.tg > .tc')).toHaveCount(3);
  });

  test('every card opens its live site in a new tab', async ({ page }) => {
    await page.goto(cacheBust('/portfolio/'));
    const cards = page.locator('.work-grid a.work-card');
    await expect(cards).toHaveCount(SITES.length);
    const hrefs = await cards.evaluateAll((els) => els.map((el) => el.getAttribute('href')));
    expect(hrefs).toEqual(SITES);
    const targets = await cards.evaluateAll((els) => els.map((el) => `${el.getAttribute('target')}|${el.getAttribute('rel')}`));
    expect(targets.every((t) => t === '_blank|noopener')).toBe(true);
  });

  test('site screenshots are bundled by the plugin, one per site, with a WebP twin', async ({ page }) => {
    await page.goto(cacheBust('/portfolio/'));
    const srcs = await page
      .locator('.work-grid img')
      .evaluateAll((imgs) => imgs.map((i) => i.getAttribute('src') || ''));
    expect(srcs).toHaveLength(SITES.length);
    expect(srcs.every((s) => /\/assets\/img\/portfolio\/[a-z0-9-]+\.jpg$/.test(s))).toBe(true);
    expect(new Set(srcs).size, 'no two sites share a screenshot').toBe(SITES.length);
    const webp = await page.locator('.work-grid picture source[type="image/webp"]').count();
    expect(webp).toBe(SITES.length);
  });

  test('the old /work address and its nav label are gone', async ({ page }) => {
    await page.goto(cacheBust('/portfolio/'));
    await expect(page.locator('nav .nav-links a', { hasText: 'Portfolio' })).toHaveClass(/active/);
    await expect(page.locator('nav .nav-links a', { hasText: /^Work$/ })).toHaveCount(0);
    const response = await page.request.get('/work/', { maxRedirects: 0 });
    expect(response.status()).toBe(301);
    expect(new URL(response.headers().location, page.url()).pathname.replace(/\/$/, '')).toBe('/portfolio');
  });
});
