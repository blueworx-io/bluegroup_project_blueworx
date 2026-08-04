/**
 * The Toolbox admin menu.
 *
 * The twelve tools are defined in the plugin's code, and the plugin creates a
 * page for each of them so /toolbox/<slug> resolves. Those pages cannot
 * usefully be edited — the body is empty and every word comes from the
 * registry — so they were twelve rows of noise in the Pages list.
 *
 * This spec covers the screen that replaces them, and, more importantly, the
 * blast radius of hiding pages: that the rule reaches the tool pages, that it
 * reaches nothing else, and that it never leaves the admin list it applies to.
 * A hiding rule that is too broad takes the site's own pages out of the one
 * place somebody goes to find them; one that leaks past the list screen breaks
 * navigation and permalinks.
 */

import { test, expect, login, cacheBust, isPlaceholder } from './helpers.js';

const TOOLBOX_SCREEN = '/wp-admin/admin.php?page=blueworx-toolbox';
const PAGES_SCREEN = '/wp-admin/edit.php?post_type=page';

/**
 * The Pages list, searched for a term.
 *
 * Searched rather than browsed, deliberately. The list paginates at 20 and this
 * site has more pages than that, so asserting a tool is absent from the default
 * view passes whether or not the tool is hidden — it may simply be on page two.
 * `posts_per_page` in the URL does not fix it; the screen takes its page size
 * from a user option, not the query string. A search is bounded and exact.
 */
const pagesSearch = (term) => `${PAGES_SCREEN}&s=${encodeURIComponent(term)}`;

/**
 * Matches a page title in the list, dashes and all.
 *
 * WordPress marks a child page's depth with em dashes *inside* the row-title
 * link, so a tool row reads "— SureForms", not "SureForms". Anchoring on the
 * bare name therefore matches nothing — and an absence assertion written that
 * way passes whether or not the row is there.
 */
const rowTitle = (name) => new RegExp(`^(—\\s*)*${name}$`);

/** Every tool in the registry, and the page each one is expected to render. */
const TOOLS = [
  ['sureforms', 'SureForms'],
  ['surerank', 'SureRank'],
  ['suremail', 'SureMail'],
  ['surewriter', 'SureWriter'],
  ['surecart', 'SureCart'],
  ['zipwp', 'ZipWP'],
  ['ottokit', 'OttoKit'],
  ['ally', 'Ally'],
  ['sweet-ai', 'Sweet AI'],
  ['elementor-ai-planner', 'Elementor AI Planner'],
  ['elementor', 'Elementor'],
  ['equalize-a11y-checker', 'Equalize A11y Checker'],
];

test.describe('Toolbox admin menu', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
    await login(page);
  });

  test('the sidebar has a Toolbox item and its screen loads', async ({ page }) => {
    await page.goto('/wp-admin/index.php');

    await expect(page.locator('#adminmenu a[href*="page=blueworx-toolbox"]')).toHaveCount(1);

    await page.goto(TOOLBOX_SCREEN);
    await expect(page.locator('h1')).toContainText('Toolbox');
  });

  test('the screen lists every tool in the registry', async ({ page }) => {
    await page.goto(TOOLBOX_SCREEN);

    const rows = page.locator('table.blueworx-toolbox-list tbody tr');
    await expect(rows).toHaveCount(TOOLS.length);

    for (const [, name] of TOOLS) {
      await expect(page.locator('table.blueworx-toolbox-list')).toContainText(name);
    }
  });

  // A listing whose links 404 is worse than no listing, because it reads as
  // proof the pages are fine.
  test('every view link resolves to that tool’s own page', async ({ page }) => {
    await page.goto(TOOLBOX_SCREEN);

    const hrefs = await page
      .locator('table.blueworx-toolbox-list a.blueworx-toolbox-view')
      .evaluateAll((els) => els.map((el) => el.getAttribute('href')));

    expect(hrefs).toHaveLength(TOOLS.length);

    for (const [slug, name] of TOOLS) {
      const href = hrefs.find((h) => new RegExp(`/toolbox/${slug}/?$`).test(h || ''));
      expect(href, `no view link for ${slug}`).toBeTruthy();

      const response = await page.request.get(href);
      expect(response.status(), `${slug} did not render`).toBe(200);
      expect(await response.text()).toContain(name);
    }
  });

  test('the tool pages are gone from the Pages list', async ({ page }) => {
    for (const [, name] of TOOLS) {
      await page.goto(pagesSearch(name));

      await expect(
        page.locator('table.wp-list-table .row-title').filter({ hasText: rowTitle(name) }),
        `${name} is still listed`
      ).toHaveCount(0);
    }
  });

  // The guard against a rule that hides too much. These are the pages somebody
  // opens the Pages list to find.
  test('the site’s own pages are still in the Pages list', async ({ page }) => {
    for (const name of ['Home', 'Pricing', 'Contact', 'Toolbox']) {
      await page.goto(pagesSearch(name));

      await expect(
        page.locator('table.wp-list-table .row-title').filter({ hasText: rowTitle(name) }),
        `${name} went missing`
      ).toHaveCount(1);
    }
  });

  // The guard against the rule leaking out of the admin list. Hiding a page
  // from one screen must not unpublish it.
  test('hiding them changes nothing on the site itself', async ({ page }) => {
    await page.goto(cacheBust('/toolbox/'));

    for (const [slug] of TOOLS) {
      await expect(page.locator(`a[href*="/toolbox/${slug}"]`).first()).toHaveCount(1);
    }

    const response = await page.request.get('/toolbox/sureforms/');
    expect(response.status()).toBe(200);
    expect(await response.text()).toContain('SureForms');
  });
});
