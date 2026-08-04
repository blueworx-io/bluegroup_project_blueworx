/**
 * The skip link (#76).
 *
 * On the live site the words "Skip to the content" sat in the top-left corner
 * of every page as ordinary blue underlined text, and following it did nothing.
 * Two separate faults: the rule that hides it until it is focused lives in the
 * active theme's stylesheet, which this plugin removes from every page it
 * renders, and its target did not exist in the markup.
 *
 * Both are asserted here on a page of each kind the plugin renders — marketing,
 * a tool page and the signed-out client area — because the document wrapper is
 * shared and a regression in it would hit all of them at once.
 */

import { test, expect, isPlaceholder, cacheBust } from './helpers.js';

const PAGES = ['/', '/services/', '/toolbox/', '/toolbox/surecart/', '/login/'];

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

const skipLink = (page) => page.locator('a.screen-reader-text[href="#content"]').first();

test.describe('#76 Skip link', () => {
  for (const path of PAGES) {
    test(`is invisible until focused on ${path}`, async ({ page }) => {
      skipPlaceholder();

      await page.goto(cacheBust(path));

      const link = skipLink(page);
      await expect(link).toHaveCount(1);

      // Not toBeVisible(): a 1px clipped element is "visible" to Playwright and
      // to a screen reader, which is exactly what this needs to be. What went
      // wrong on the live site is that it took up real space on the page, so
      // that is what gets measured.
      const hidden = await link.boundingBox();

      expect(hidden.width, `${path}: the skip link is drawn on the page`).toBeLessThanOrEqual(2);
      expect(hidden.height, `${path}: the skip link is drawn on the page`).toBeLessThanOrEqual(2);

      await link.focus();

      const shown = await link.boundingBox();

      expect(shown.width, `${path}: focusing the skip link does not reveal it`).toBeGreaterThan(40);
      expect(shown.height, `${path}: focusing the skip link does not reveal it`).toBeGreaterThan(20);
    });
  }

  test('is the first thing a keyboard reaches', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/'));
    await page.keyboard.press('Tab');

    const focused = await page.evaluate(() => {
      const el = document.activeElement;
      return { tag: el.tagName, cls: el.className, href: el.getAttribute('href') };
    });

    expect(focused.tag).toBe('A');
    expect(focused.cls).toContain('screen-reader-text');
    expect(focused.href).toBe('#content');
  });

  test('following it moves focus to the start of the content', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/services/'));

    await expect(page.locator('#content')).toHaveCount(1);

    await skipLink(page).focus();
    await page.keyboard.press('Enter');

    const focused = await page.evaluate(() => ({
      id: document.activeElement.id,
      tag: document.activeElement.tagName,
    }));

    expect(focused.id, 'Enter on the skip link did not move focus into the content').toBe('content');
    expect(focused.tag).toBe('MAIN');
  });
});
