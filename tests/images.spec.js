/**
 * Images, and the page not jumping about while they arrive (#82).
 *
 * Not one image on the site declared its size, so the browser could not
 * reserve space for any of them and everything below each one moved as it
 * loaded. Photographic assets were JPEG and PNG only, the menu's tool icons
 * were lazy-loaded even though they are needed the moment the menu opens, and
 * 15KB of WordPress block CSS was inlined into every page to style blocks this
 * site never renders.
 */

import { test, expect, isPlaceholder, cacheBust } from './helpers.js';

const PAGES = ['/', '/services/', '/toolbox/', '/work/', '/about/', '/toolbox/surecart/'];

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

/** Every image on the page, with what it declares and where it sits. */
const imagesOn = (page) =>
  page.evaluate(() => {
    const viewport = window.innerHeight;

    return [...document.querySelectorAll('img')].map((img) => {
      const rect = img.getBoundingClientRect();

      return {
        src: (img.getAttribute('src') || '').split('/').pop(),
        width: img.getAttribute('width'),
        height: img.getAttribute('height'),
        loading: img.getAttribute('loading'),
        // Relative to the top of the document, so scroll position cannot
        // change the answer.
        top: rect.top + window.scrollY,
        rendered: rect.width > 0 && rect.height > 0,
        aboveFold: rect.top + window.scrollY < viewport,
        webp: !!(img.parentElement && img.parentElement.querySelector('source[type="image/webp"]')),
      };
    });
  });

test.describe('#82 Images', () => {
  for (const path of PAGES) {
    test(`every image on ${path} declares its size`, async ({ page }) => {
      skipPlaceholder();

      await page.goto(cacheBust(path));

      const images = await imagesOn(page);

      expect(images.length, `${path}: no images found at all`).toBeGreaterThan(0);

      const undeclared = images
        .filter((img) => !img.width || !img.height)
        .map((img) => img.src);

      expect(
        undeclared,
        `${path}: images with no width/height, so the page shifts as they load:\n${undeclared.join('\n')}`
      ).toEqual([]);
    });
  }

  test('nothing above the fold is lazy-loaded, and things below it are', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/'));

    const images = await imagesOn(page);
    const visible = images.filter((img) => img.rendered);

    const lazyAtTop = visible.filter((img) => img.aboveFold && img.loading === 'lazy');

    expect(
      lazyAtTop.map((img) => img.src),
      'lazy-loading an image the visitor is already looking at only delays it'
    ).toEqual([]);

    expect(
      visible.some((img) => !img.aboveFold && img.loading === 'lazy'),
      'nothing below the fold is lazy-loaded, so the whole page is fetched up front'
    ).toBe(true);
  });

  test('the menu’s tool icons are ready before the menu opens', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/'));

    const lazy = await page
      .locator('.mega-panel img')
      .evaluateAll((imgs) => imgs.filter((img) => img.getAttribute('loading') === 'lazy').length);

    // They live in a panel that is in the document but hidden, so a lazy icon
    // only starts loading when the panel opens — the one moment somebody is
    // looking straight at it.
    expect(lazy, 'the mega panel icons are lazy-loaded').toBe(0);
  });

  test('photographs are offered as WebP, with the original as a fallback', async ({ page }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/work/'));

    const photos = (await imagesOn(page)).filter((img) => /\.(jpe?g)$/i.test(img.src));

    expect(photos.length, 'no photographs on the Work page?').toBeGreaterThan(0);

    const noWebp = photos.filter((img) => !img.webp).map((img) => img.src);

    expect(noWebp, `photographs served without a WebP alternative:\n${noWebp.join('\n')}`).toEqual([]);
  });

  test('an image the design hides on a phone is not downloaded on a phone', async ({ page }) => {
    skipPlaceholder();

    await page.setViewportSize({ width: 390, height: 844 });

    const requested = [];
    page.on('request', (request) => requested.push(request.url()));

    await page.goto(cacheBust('/contact/'), { waitUntil: 'networkidle' });

    expect(
      requested.filter((url) => url.includes('contact-illustration')),
      'the contact illustration is hidden below 900px but still downloaded'
    ).toEqual([]);
  });

  test('WordPress block styles are not inlined into a page that renders no blocks', async ({
    page,
  }) => {
    skipPlaceholder();

    await page.goto(cacheBust('/'));

    const blockCss = await page.evaluate(() =>
      [...document.querySelectorAll('style[id], link[rel="stylesheet"][id]')]
        .map((el) => el.id)
        .filter((id) => /global-styles|wp-block-library|classic-theme-styles/.test(id))
    );

    expect(blockCss, `block CSS still on the page: ${blockCss.join(', ')}`).toEqual([]);
  });

  for (const path of ['/', '/services/', '/toolbox/']) {
    test(`${path} does not shift about as it loads`, async ({ page }) => {
      skipPlaceholder();

      await page.goto(cacheBust(path), { waitUntil: 'load' });

      const cls = await page.evaluate(
        () =>
          new Promise((resolve) => {
            let total = 0;

            const observer = new PerformanceObserver((list) => {
              for (const entry of list.getEntries()) {
                if (!entry.hadRecentInput) {
                  total += entry.value;
                }
              }
            });

            observer.observe({ type: 'layout-shift', buffered: true });

            // Long enough for anything lazy near the top and any late font
            // swap to have landed.
            setTimeout(() => {
              observer.disconnect();
              resolve(total);
            }, 2500);
          })
      );

      expect(cls, `${path}: cumulative layout shift is ${cls.toFixed(3)}`).toBeLessThan(0.1);
    });
  }
});
