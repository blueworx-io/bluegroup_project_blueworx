/**
 * What may be cached, and for whom (#81).
 *
 * The site took two to three and a half seconds to begin sending a page, and
 * the reason was in the headers: the HTML carried no Cache-Control at all, so
 * the Varnish cache in front of it stored nothing and every visitor waited for
 * WordPress to rebuild the same marketing page.
 *
 * The half of this that matters most is the other half. A cache that is told
 * nothing decides for itself — the live sign-in page was served four hours
 * stale — so anything that is not identical for every visitor has to say so
 * out loud. These specs check both directions, and the "never cache" direction
 * is the one to keep if the other ever has to go.
 */

import { test, expect, isPlaceholder, cacheBust, login } from './helpers.js';

const PUBLIC_PATHS = ['/', '/services/', '/toolbox/', '/pricing/', '/about/', '/toolbox/surecart/'];
const PRIVATE_PATHS = ['/login/', '/register/', '/reset-password/'];

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

/** The caching headers on a response, lower-cased. */
async function cacheHeaders(page, path) {
  const response = await page.request.get(cacheBust(path));
  const headers = response.headers();

  return {
    status: response.status(),
    cacheControl: (headers['cache-control'] || '').toLowerCase(),
    vary: (headers.vary || '').toLowerCase(),
  };
}

test.describe('#81 Caching', () => {
  for (const path of PUBLIC_PATHS) {
    test(`${path} may be cached for everyone`, async ({ page }) => {
      skipPlaceholder();

      const { cacheControl, vary } = await cacheHeaders(page, path);

      expect(cacheControl, `${path} has no Cache-Control at all`).not.toBe('');
      expect(cacheControl, `${path}: ${cacheControl}`).toContain('public');
      expect(cacheControl, `${path} is not cacheable by a shared cache`).toMatch(/s-maxage=[1-9]/);
      expect(cacheControl, `${path}: ${cacheControl}`).not.toContain('no-store');

      // Vary: Cookie would make a shared cache store a separate copy of the
      // page per analytics cookie, which is the same as not caching at all.
      expect(vary, `${path}: Vary: ${vary}`).not.toContain('cookie');
    });
  }

  for (const path of PRIVATE_PATHS) {
    test(`${path} is never stored by a shared cache`, async ({ page }) => {
      skipPlaceholder();

      const { cacheControl } = await cacheHeaders(page, path);

      expect(cacheControl, `${path}: ${cacheControl}`).toContain('private');
      expect(cacheControl, `${path}: ${cacheControl}`).toContain('no-store');
      expect(cacheControl, `${path}: ${cacheControl}`).not.toContain('public');
    });
  }

  test('a signed-in visitor is never served from, or stored in, a shared cache', async ({
    page,
  }) => {
    skipPlaceholder();

    await login(page);

    // The marketing pages too, not just the dashboard: signed in, they carry
    // the admin bar and the visitor's own name.
    for (const path of ['/', '/pricing/', '/dashboard/']) {
      const response = await page.goto(cacheBust(path));
      const cacheControl = (response.headers()['cache-control'] || '').toLowerCase();

      expect(cacheControl, `signed in, ${path}: ${cacheControl}`).toContain('private');
      expect(cacheControl, `signed in, ${path}: ${cacheControl}`).toContain('no-store');
    }
  });

  test('the 404 is not cached at the edge', async ({ page }) => {
    skipPlaceholder();

    const { status, cacheControl } = await cacheHeaders(page, '/this-page-does-not-exist-0b2a/');

    expect(status).toBe(404);
    expect(cacheControl, `404: ${cacheControl}`).toContain('no-store');
  });

  test('a form post is never treated as cacheable', async ({ page }) => {
    skipPlaceholder();

    const response = await page.request.post('/pricing/', { form: { anything: '1' } });
    const cacheControl = (response.headers()['cache-control'] || '').toLowerCase();

    expect(cacheControl, `POST /pricing/: ${cacheControl}`).not.toContain('public');
  });
});
