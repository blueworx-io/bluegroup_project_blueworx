/**
 * What each page tells a search engine it is (#79).
 *
 * Five of the nine main pages had no description at all, and the ones that did
 * had a fragment scraped from the middle of a sentence on the page. Titles were
 * the nav label and the site name, which says what a page is called rather than
 * what it offers.
 *
 * The lengths are asserted as well as the presence, because "has a description"
 * was never the problem — an auto-generated one is present too. A description
 * that is 90 characters wastes the space a result gives you and one that is 200
 * is cut off mid-word.
 */

import { test, expect, isPlaceholder, cacheBust, TOOL_SLUGS } from './helpers.js';

const PAGES = ['/', '/services/', '/toolbox/', '/pricing/', '/about/', '/work/', '/ai/', '/contact/'];
const ALL = [...PAGES, ...TOOL_SLUGS.map((slug) => `/toolbox/${slug}/`)];

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

/** The title and the description tags the page actually renders. */
const seoOf = (page) =>
  page.evaluate(() => ({
    title: document.title,
    descriptions: [...document.querySelectorAll('meta[name="description" i]')].map(
      (el) => el.getAttribute('content') || ''
    ),
    ogTitle: (document.querySelector('meta[property="og:title"]') || {}).content || '',
    ogDescription:
      (document.querySelector('meta[property="og:description"]') || {}).content || '',
  }));

test.describe('#79 Titles and descriptions', () => {
  for (const path of ALL) {
    test(`${path} has a written title and description`, async ({ page }) => {
      skipPlaceholder();

      await page.goto(cacheBust(path));

      const seo = await seoOf(page);

      expect(seo.descriptions, `${path}: expected exactly one description`).toHaveLength(1);

      const description = seo.descriptions[0];

      expect(description.length, `${path}: description is ${description.length} characters`)
        .toBeGreaterThanOrEqual(135);
      expect(description.length, `${path}: description is ${description.length} characters`)
        .toBeLessThanOrEqual(165);

      // A scraped description is a fragment: it stops mid-sentence, usually on
      // an ellipsis, and it repeats copy from the page verbatim.
      expect(description, `${path}: description looks truncated`).not.toMatch(/(…|\.\.\.)\s*$/);
      expect(description.trim().endsWith('.'), `${path}: description is not a full sentence`).toBe(
        true
      );

      expect(seo.title.length, `${path}: title is too short to say anything`).toBeGreaterThan(20);
    });
  }

  test('no two pages share a title or a description', async ({ page }) => {
    skipPlaceholder();

    const titles = new Map();
    const descriptions = new Map();

    for (const path of ALL) {
      await page.goto(cacheBust(path));

      const seo = await seoOf(page);

      const clashTitle = titles.get(seo.title);
      expect(clashTitle, `${path} has the same title as ${clashTitle}`).toBeUndefined();
      titles.set(seo.title, path);

      const clashDescription = descriptions.get(seo.descriptions[0]);
      expect(
        clashDescription,
        `${path} has the same description as ${clashDescription}`
      ).toBeUndefined();
      descriptions.set(seo.descriptions[0], path);
    }
  });

  test('Open Graph and Twitter repeat the same text', async ({ page }) => {
    skipPlaceholder();

    for (const path of ['/', '/pricing/', '/toolbox/surecart/']) {
      await page.goto(cacheBust(path));

      const seo = await seoOf(page);

      expect(seo.ogDescription, `${path}: og:description differs from the description`).toBe(
        seo.descriptions[0]
      );
      expect(seo.title, `${path}: og:title is not in the title`).toContain(seo.ogTitle);
    }
  });
});
