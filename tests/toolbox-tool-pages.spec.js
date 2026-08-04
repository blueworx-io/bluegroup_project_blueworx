/**
 * Every Toolbox tool link on the site goes somewhere real (#75).
 *
 * All twelve tool pages were broken on the live site while the suite was green.
 * The reason was not that nobody tested them — two specs walked /toolbox/<slug>
 * and asserted a 200 — but that both walked a list of slugs copied into the
 * test file, on a WordPress provisioned from scratch, where the pages happen to
 * be created correctly.
 *
 * This spec fixes the first half of that: it never names a tool. Both the slugs
 * and the links come from the site itself — the registry via toolRegistry(),
 * and the rendered nav — so a tool added to the plugin is covered the moment it
 * exists, and a link the nav renders is checked whether or not anyone
 * remembered it. The second half, the install states a fresh site never
 * reaches, is toolbox-upgrade.spec.js (#83).
 */

import { test, expect, isPlaceholder, toolRegistry, cacheBust } from './helpers.js';

const TOOLS = toolRegistry();

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

test.describe('#75 Toolbox tool pages', () => {
  test('every tool in the registry has a page that returns 200', async ({ page }) => {
    skipPlaceholder();

    const broken = [];

    for (const { slug } of TOOLS) {
      const response = await page.request.get(`/toolbox/${slug}/`);

      if (response.status() !== 200) {
        broken.push(`${slug} -> ${response.status()} ${response.url()}`);
      }
    }

    expect(broken, `Tool pages that do not resolve:\n${broken.join('\n')}`).toEqual([]);
  });

  test('each tool page renders the single-tool template, not some other page', async ({ page }) => {
    skipPlaceholder();

    for (const { slug, name } of TOOLS) {
      await page.goto(cacheBust(`/toolbox/${slug}/`));
      await expect(page.locator('h1')).toContainText(name);
    }
  });

  // The failure on the live site was reported as dead links in the menu, so
  // this asserts the menu rather than a list of addresses: whatever the nav
  // renders is what a visitor can click, and every one of those has to resolve.
  test('every tool link in the desktop mega panel resolves', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/'));

    const hrefs = await page.locator('.mega-panel a.mega-item').evaluateAll((links) =>
      links.map((link) => link.getAttribute('href'))
    );

    expect(hrefs).toHaveLength(TOOLS.length);

    for (const href of hrefs) {
      const response = await page.request.get(href);
      expect(response.status(), `Mega panel link ${href}`).toBe(200);
    }
  });

  test('every tool link in the mobile menu resolves', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/'));

    const hrefs = await page
      .locator('.mobile-menu a[href*="/toolbox/"]')
      .evaluateAll((links) => links.map((link) => link.getAttribute('href')));

    expect(hrefs.length).toBeGreaterThanOrEqual(TOOLS.length);

    for (const href of hrefs) {
      const response = await page.request.get(href);
      expect(response.status(), `Mobile menu link ${href}`).toBe(200);
    }
  });

  test('every tool link on the Toolbox page resolves', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/toolbox/'));

    const hrefs = await page
      .locator('main a[href*="/toolbox/"], .toolbox-grid a[href*="/toolbox/"]')
      .evaluateAll((links) => [...new Set(links.map((link) => link.getAttribute('href')))]);

    expect(hrefs.length).toBeGreaterThanOrEqual(TOOLS.length);

    for (const href of hrefs) {
      const response = await page.request.get(href);
      expect(response.status(), `Toolbox page link ${href}`).toBe(200);
    }
  });

  // A tool page that 404s is also a tool page missing from the sitemap, which
  // is how the live failure was first spotted. WordPress puts every published,
  // indexable page in wp-sitemap.xml on its own, so the two things that decide
  // membership are the two asserted here — and unlike the sitemap itself, both
  // can be checked on the local PHP built-in server, which cannot serve a path
  // ending in .xml at all.
  test('each tool page is published and indexable, so the sitemap includes it', async ({ page }) => {
    skipPlaceholder();

    // Compared against Pricing rather than asserted outright: a test WordPress
    // is installed with "discourage search engines" on, which puts noindex on
    // every page of the site. Asking whether the tool pages agree with an
    // ordinary marketing page answers the question that matters — does the
    // plugin treat them as public — on any site, however that setting is left.
    const robotsFor = async (path) => {
      await page.goto(cacheBust(path));

      return (
        (await page
          .locator('meta[name="robots"]')
          .first()
          .getAttribute('content')
          .catch(() => null)) || ''
      );
    };

    const baseline = await robotsFor('/pricing/');

    for (const { slug } of TOOLS) {
      expect(await robotsFor(`/toolbox/${slug}/`), `/toolbox/${slug}/ robots`).toBe(baseline);
    }
  });
});
