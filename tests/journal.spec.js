/**
 * The journal and its articles (#94, #95).
 *
 * These are the first pages in the public layer whose content the plugin does
 * not write: everything on them comes from the site's own posts. So the checks
 * are deliberately shaped around "whatever is published", not around a fixture
 * — a spec that assumed six articles with four categories would pass only on
 * the machine somebody happened to seed, and a fresh WordPress (which is what
 * CI provisions) has exactly one post in no real category.
 *
 * What must hold either way: the journal renders as a BlueWorx document, it
 * shows articles or an honest empty state and never both, the filter agrees
 * with the grid, and an article page carries a byline, a working share row and
 * a way back to the journal.
 */

import { test, expect, isPlaceholder, cacheBust } from './helpers.js';

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

/**
 * The first article's URL, or null when the site has published nothing.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<string|null>} Absolute URL.
 */
async function firstArticleUrl(page) {
  await page.goto(cacheBust('/blog/'));

  const cards = page.locator('.jr-card');

  if ((await cards.count()) === 0) {
    return null;
  }

  return cards.first().getAttribute('href');
}

test.describe('#94 The journal', () => {
  test('answers, and is the BlueWorx document', async ({ page }) => {
    skipPlaceholder();

    const response = await page.goto(cacheBust('/blog/'));

    expect(response.status()).toBe(200);
    await expect(page.locator('body.bw-blog')).toHaveCount(1);
    await expect(page.locator('nav .nav-logo')).toHaveCount(1);
    await expect(page.locator('footer')).toHaveCount(1);
    await expect(page.locator('h1')).toHaveCount(1);
  });

  test('shows articles or an empty state, never both and never neither', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/blog/'));

    const cards = await page.locator('.jr-card').count();
    // The filter's own empty state is in the document but hidden, so only a
    // visible one counts as the page saying "there is nothing here".
    const empty = await page.locator('.jr-empty:visible').count();

    expect(cards > 0 || empty > 0, 'the journal rendered neither articles nor an empty state').toBe(
      true
    );
    expect(cards > 0 && empty > 0, 'the journal rendered articles AND an empty state').toBe(false);
  });

  test('every card links to an article that answers', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/blog/'));

    const hrefs = await page
      .locator('.jr-card')
      .evaluateAll((links) => links.map((a) => a.getAttribute('href')));

    test.skip(hrefs.length === 0, 'Nothing published on this target.');

    for (const href of hrefs) {
      const response = await page.request.get(href);
      expect(response.status(), `journal card ${href}`).toBe(200);
    }
  });

  test('the category filter agrees with the grid', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/blog/'));

    // Pills only exist once posts carry real categories. A fresh WordPress has
    // one post in "Uncategorized", which is a WordPress default rather than a
    // topic, so this legitimately has nothing to test there.
    const pills = page.locator('.jr-pill');
    const pillCount = await pills.count();

    test.skip(pillCount < 2, 'No categorised posts on this target.');

    const topic = pills.nth(1);
    const slug = await topic.getAttribute('data-jr-filter');

    await topic.click();

    await expect(topic).toHaveAttribute('aria-pressed', 'true');
    await expect(page.locator('.jr-pill[data-jr-filter=""]')).toHaveAttribute(
      'aria-pressed',
      'false'
    );

    // Every card still on screen belongs to the chosen category, and the
    // featured card — which is the journal's front page rather than a member
    // of the filtered set — steps out of the way.
    const shownCats = await page
      .locator('.jr-grid .jr-card:visible')
      .evaluateAll((cards) => cards.map((c) => c.getAttribute('data-jr-cat')));

    for (const cat of shownCats) {
      expect(cat, 'a card from another category survived the filter').toBe(slug);
    }

    await expect(page.locator('[data-jr-featured]')).toBeHidden();

    // The count is what a screen reader is told; it must not disagree with
    // what is on screen.
    const label = (await page.locator('[data-jr-count]').textContent()).trim();
    expect(label, `count "${label}" does not match ${shownCats.length} visible cards`).toContain(
      String(shownCats.length)
    );

    await page.locator('.jr-pill[data-jr-filter=""]').click();
    await expect(page.locator('[data-jr-featured]')).toBeVisible();
  });

  test('a filter with nothing in it says so', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/blog/'));

    const hasFilter = (await page.locator('[data-widget="journal-filter"]').count()) > 0;
    test.skip(!hasFilter, 'No categorised posts on this target.');

    // Drive the widget to a category that cannot match, which is the state a
    // real filter reaches only when its last post is deleted — worth covering
    // because it is the one path that leaves the grid blank.
    await page.evaluate(() => {
      const pill = document.querySelector('.jr-pill:not([data-jr-filter=""])');
      pill.setAttribute('data-jr-filter', 'no-such-category-4b1e');
      pill.click();
    });

    await expect(page.locator('[data-jr-empty]')).toBeVisible();
  });
});

test.describe('#95 A journal article', () => {
  test('renders as the BlueWorx document, with one h1', async ({ page }) => {
    skipPlaceholder();

    const url = await firstArticleUrl(page);
    test.skip(!url, 'Nothing published on this target.');

    const response = await page.goto(cacheBust(url));

    expect(response.status()).toBe(200);
    await expect(page.locator('body.bw-post')).toHaveCount(1);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('#content')).toHaveCount(1);
  });

  test('is styled by the plugin, not the theme', async ({ page }) => {
    skipPlaceholder();

    const url = await firstArticleUrl(page);
    test.skip(!url, 'Nothing published on this target.');

    await page.goto(cacheBust(url));

    const sheets = await page.evaluate(() =>
      [...document.querySelectorAll('link[rel="stylesheet"]')].map((el) => el.getAttribute('href'))
    );

    expect(
      sheets.some((href) => (href || '').includes('public.css')),
      'the article rendered without the site stylesheet'
    ).toBe(true);
  });

  test('carries a byline, a read time and a way back to the journal', async ({ page }) => {
    skipPlaceholder();

    const url = await firstArticleUrl(page);
    test.skip(!url, 'Nothing published on this target.');

    await page.goto(cacheBust(url));

    await expect(page.locator('.jp-byline-who')).not.toBeEmpty();
    await expect(page.locator('.jp-byline-when')).toContainText('min read');

    const back = page.locator('.jp-crumbs a').first();
    const href = await back.getAttribute('href');
    expect(new URL(href, page.url()).pathname).toContain('/blog');
  });

  test('the share row works and nothing in it is decorative', async ({ page }) => {
    skipPlaceholder();

    const url = await firstArticleUrl(page);
    test.skip(!url, 'Nothing published on this target.');

    await page.goto(cacheBust(url));

    // Both share links must carry this article's own URL, not the journal's —
    // the bug that makes every share on the site point at the same page.
    const hrefs = await page
      .locator('.jp-share a')
      .evaluateAll((links) => links.map((a) => a.getAttribute('href')));

    expect(hrefs.length).toBe(2);

    for (const href of hrefs) {
      expect(href, `share link does not carry the article URL: ${href}`).toContain(
        encodeURIComponent(new URL(page.url()).pathname.replace(/\?.*$/, ''))
      );
    }

    // Every share control is a real control: named, and either a link with an
    // href or a button with a handler (#77).
    const nameless = await page.evaluate(() =>
      [...document.querySelectorAll('.jp-share a, .jp-share button')].filter(
        (el) => !(el.getAttribute('aria-label') || '').trim()
      ).length
    );

    expect(nameless, 'a share control has no accessible name').toBe(0);
  });

  test('the contents list matches the article’s own headings', async ({ page }) => {
    skipPlaceholder();

    const url = await firstArticleUrl(page);
    test.skip(!url, 'Nothing published on this target.');

    await page.goto(cacheBust(url));

    const headings = await page.locator('.bw-post-body h2').count();
    const toc = page.locator('[data-jp-toc]');

    if (headings < 2) {
      // A contents box listing one item, or none, is furniture.
      await expect(toc).toBeHidden();
      return;
    }

    await expect(toc).toBeVisible();
    await expect(page.locator('[data-jp-toc-list] a')).toHaveCount(headings);

    // Every entry must point at a heading that exists, or the list looks
    // broken the first time somebody uses it.
    const targets = await page
      .locator('[data-jp-toc-list] a')
      .evaluateAll((links) => links.map((a) => document.querySelectorAll(a.getAttribute('href')).length));

    for (const found of targets) {
      expect(found, 'a contents link points at no heading').toBe(1);
    }
  });
});
