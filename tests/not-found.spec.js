/**
 * The 404 page (#78).
 *
 * The plugin renders every page it owns and, until now, nothing else — so a
 * bad URL fell through to the site's fallback theme: a bare "Not Found"
 * heading on a white page, with no nav, no footer, and no way back into the
 * site. That is what a visitor got for any rotted link, including the twelve
 * broken Toolbox addresses in #75.
 *
 * page-standards.spec.js already covers the two things a 404 must do at all
 * (say 404, and offer a way out). This covers the rest: that it is the
 * BlueWorx page and not the theme's, and that it names the places worth going.
 */

import { test, expect, isPlaceholder, cacheBust } from './helpers.js';

const MISSING = '/this-page-does-not-exist-7c1b04/';

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

test.describe('#78 The 404 page', () => {
  test('answers 404 and renders the BlueWorx document', async ({ page }) => {
    skipPlaceholder();

    const response = await page.goto(cacheBust(MISSING));

    // A "soft 404" served as 200 leaves search engines indexing it as content.
    expect(response.status()).toBe(404);

    await expect(page.locator('body.bw-page')).toHaveCount(1);
    await expect(page.locator('body.bw-404')).toHaveCount(1);
  });

  test('has the site nav and the site footer', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust(MISSING));

    await expect(page.locator('nav .nav-logo')).toHaveCount(1);
    await expect(page.locator('nav .nav-links a', { hasText: 'Services' }).first()).toBeVisible();
    await expect(page.locator('footer')).toHaveCount(1);
  });

  test('is styled — the plugin stylesheet loads here too', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust(MISSING));

    const sheets = await page.evaluate(() =>
      [...document.querySelectorAll('link[rel="stylesheet"]')].map((el) => el.getAttribute('href'))
    );

    expect(
      sheets.some((href) => (href || '').includes('public.css')),
      'the 404 rendered without the site stylesheet'
    ).toBe(true);
  });

  test('offers the main pages, and every one of them answers', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust(MISSING));

    for (const label of ['Home', 'Services', 'Toolbox', 'Pricing', 'Contact']) {
      await expect(
        page.locator('.nf-link', { hasText: label }),
        `the 404 does not offer ${label}`
      ).toHaveCount(1);
    }

    const hrefs = await page
      .locator('.nf-link')
      .evaluateAll((links) => links.map((a) => a.getAttribute('href')));

    for (const href of hrefs) {
      const response = await page.request.get(href);
      expect(response.status(), `404 page link ${href}`).toBe(200);
    }
  });

  test('has one h1 and a working skip link, like every other page', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust(MISSING));

    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('#content')).toHaveCount(1);
    await expect(page.locator('a.screen-reader-text[href="#content"]')).toHaveCount(1);
  });
});
