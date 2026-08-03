/**
 * The same page-quality standard, applied to the signed-in pages (#53).
 *
 * page-standards.spec.js covers everything a logged-out visitor can reach. The
 * dashboard cannot be in that file: it needs a session, which needs the `test`
 * from helpers.js rather than Playwright's own, and a spec file can only have
 * one. The checks themselves are shared (tests/standards.js) rather than
 * copied, so the signed-in pages cannot quietly end up held to a weaker
 * standard than the public ones.
 */

import { test, expect, login, isPlaceholder } from './helpers.js';
import { audit, firstFocusable, horizontalOverflow, PHONE } from './standards.js';

const PAGES = [
  { path: '/dashboard/', name: 'Overview' },
  { path: '/dashboard/subscriptions/', name: 'Subscriptions' },
  { path: '/dashboard/invoices/', name: 'Invoices' },
  { path: '/dashboard/orders/', name: 'Orders' },
];

for (const { path, name } of PAGES) {
  test.describe(`#53 Client dashboard — ${name}`, () => {
    test.beforeEach(async ({ page }) => {
      test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
      await login(page);
    });

    test('has one h1, in a heading order that does not skip a level', async ({ page }) => {
      await page.goto(path);

      const { headings } = await audit(page);
      const h1s = headings.filter((h) => h.level === 1);

      expect(h1s.map((h) => h.text), `${path} must have exactly one h1`).toHaveLength(1);

      let previous = 1;
      for (const heading of headings) {
        expect(
          heading.level,
          `${path}: "${heading.text}" is an h${heading.level} directly after an h${previous}`
        ).toBeLessThanOrEqual(previous + 1);
        previous = heading.level;
      }
    });

    test('every image, link and button can be announced', async ({ page }) => {
      await page.goto(path);

      const { imagesWithoutAlt, linksWithoutName, buttonsWithoutName } = await audit(page);

      expect(imagesWithoutAlt, `${path}: images with no alt attribute at all`).toEqual([]);
      expect(linksWithoutName, `${path}: links a screen reader would announce as blank`).toEqual([]);
      expect(buttonsWithoutName, `${path}: buttons a screen reader would announce as blank`).toEqual([]);
    });

    test('declares a language and a page title', async ({ page }) => {
      await page.goto(path);

      const { lang, title } = await audit(page);

      expect(lang, `${path} must declare a lang for screen readers`).toBeTruthy();
      expect(title, `${path} must have a non-empty <title>`).not.toBe('');
    });

    // The record tables are the thing most likely to break this, which is why
    // they scroll inside their own container rather than pushing the page wide.
    test('does not scroll sideways on a phone', async ({ page }) => {
      await page.setViewportSize(PHONE);
      await page.goto(path);

      const overflow = await horizontalOverflow(page);

      expect(
        overflow.scrollWidth,
        `${path} scrolls sideways at 375px. Widest elements: ${overflow.widest.join(', ') || 'none identified'}`
      ).toBeLessThanOrEqual(overflow.clientWidth + 1);
    });

    test('the first thing keyboard focus reaches is a real, visible control', async ({ page }) => {
      await page.goto(path);

      const focused = await firstFocusable(page);

      expect(focused, `${path}: pressing Tab from the top moves focus nowhere`).not.toBeNull();
      expect(['a', 'button', 'input', 'select', 'textarea']).toContain(focused.tag);
      expect(focused.hasSize, `${path}: focus lands on a zero-size element`).toBe(true);
      expect(focused.name, `${path}: the first focusable control has no name`).not.toBe('');
    });

    // Every section is reachable from every other one without going back to
    // the overview first.
    test('the section tabs are reachable from here', async ({ page }) => {
      await page.goto(path);

      await expect(page.locator('.dash-tabs a')).toHaveCount(4);
      await expect(page.locator('.dash-tab[aria-current="page"]')).toHaveCount(1);
    });
  });
}
