/**
 * The 404 page (#78, redesigned in #96).
 *
 * The plugin renders every page it owns and, before #78, nothing else — so a
 * bad URL fell through to the site's fallback theme: a bare "Not Found"
 * heading on a white page with no way back into the site.
 *
 * #96 replaced the first version's nav/hero/link-list with the Claude Design's
 * single panel: the code ghosted behind the copy, and two things to do. So the
 * checks here changed shape too — what has to stay true is that it is the
 * BlueWorx document, that it says which address missed, and that both ways out
 * work. page-standards.spec.js covers the two universal rules (answer 404, and
 * offer at least one way out).
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

  test('shows the address that missed', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust(MISSING));

    await expect(page.locator('.nf-path-value')).toContainText('this-page-does-not-exist-7c1b04');
  });

  test('does not let a long or hostile path stretch the page', async ({ page }) => {
    skipPlaceholder();

    // The requested path is attacker-controlled text echoed onto a page that
    // renders for any URL at all. It must be escaped, truncated, and unable to
    // push the document wider than the viewport.
    const nasty = `/${'a'.repeat(400)}-<script>alert(1)</script>/`;

    await page.goto(cacheBust(nasty));

    const injected = await page.evaluate(() => document.querySelectorAll('.nf-path-value script').length);
    expect(injected, 'the requested path was rendered as markup').toBe(0);

    const overflow = await page.evaluate(() => ({
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: document.documentElement.clientWidth,
    }));

    expect(overflow.scrollWidth, 'a long 404 path widened the document').toBeLessThanOrEqual(
      overflow.clientWidth + 1
    );
  });

  test('offers a way home and a way back, and home answers', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust(MISSING));

    const home = page.locator('.nf-actions a');
    await expect(home).toHaveCount(1);

    const href = await home.getAttribute('href');
    const response = await page.request.get(href);
    expect(response.status(), `404 page home link ${href}`).toBe(200);

    // Back is a browser action, so it has to be a real button — and one that
    // actually does something, which is the #77 rule.
    const back = page.locator('.nf-actions button[data-bw-back]');
    await expect(back).toHaveCount(1);
    await expect(back).toBeVisible();
  });

  test('the back button returns to the previous page', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/'));
    await page.goto(cacheBust(MISSING));

    await page.locator('.nf-actions button[data-bw-back]').click();
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('body.bw-404')).toHaveCount(0);
  });

  test('nothing on it looks like a control and isn’t', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust(MISSING));

    const dead = await page.evaluate(() =>
      [...document.querySelectorAll('a:not([href])')].map((a) => a.className || '(no class)')
    );

    expect(dead, `anchors that go nowhere:\n${dead.join('\n')}`).toEqual([]);
  });

  test('has one h1 and a working skip link, like every other page', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust(MISSING));

    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('#content')).toHaveCount(1);
    await expect(page.locator('a.screen-reader-text[href="#content"]')).toHaveCount(1);
  });
});
