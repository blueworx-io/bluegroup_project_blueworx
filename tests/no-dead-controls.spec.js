/**
 * Nothing on the site looks clickable and isn't (#77).
 *
 * The footer carried six links with no href and a newsletter box with no form
 * behind it, on every page of the site. They were styled as controls, hovered
 * as controls, and did nothing — which is worse than not being there, because
 * the visitor concludes the site is broken rather than that the page does not
 * exist yet.
 *
 * This is written as a rule about the whole document rather than a check on
 * the footer: the same mistake is easy to repeat anywhere, and a design ported
 * from a mockup is exactly where it comes from.
 */

import { test, expect, isPlaceholder, cacheBust } from './helpers.js';

const PAGES = ['/', '/services/', '/toolbox/', '/toolbox/surecart/', '/pricing/', '/contact/', '/about/'];

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

test.describe('#77 Nothing pretends to be a control', () => {
  for (const path of PAGES) {
    test(`no anchor without an href on ${path}`, async ({ page }) => {
      skipPlaceholder();

      await page.goto(cacheBust(path));

      const dead = await page.evaluate(() =>
        [...document.querySelectorAll('a:not([href])')].map((a) =>
          `${a.className || '(no class)'}: ${(a.textContent || a.getAttribute('aria-label') || '').trim().slice(0, 40)}`
        )
      );

      expect(dead, `${path}: anchors that go nowhere:\n${dead.join('\n')}`).toEqual([]);
    });
  }

  test('the footer has no form that submits nowhere', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/'));

    const inert = await page.evaluate(() =>
      [...document.querySelectorAll('footer input, footer button')]
        .filter((el) => !el.closest('form'))
        .map((el) => `${el.tagName.toLowerCase()}.${el.className || '(no class)'}`)
    );

    expect(inert, `Footer controls with no form behind them:\n${inert.join('\n')}`).toEqual([]);
  });

  test('every footer link goes to a page that answers', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/'));

    const hrefs = await page
      .locator('footer a[href]')
      .evaluateAll((links) => [...new Set(links.map((a) => a.href))]);

    expect(hrefs.length).toBeGreaterThan(0);

    for (const href of hrefs) {
      if (!href.startsWith(new URL(page.url()).origin)) {
        continue;
      }

      const response = await page.request.get(href);
      expect(response.status(), `Footer link ${href}`).toBeLessThan(400);
    }
  });
});
