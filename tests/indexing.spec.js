/**
 * What search engines are told to index (#80).
 *
 * The live sitemap invited Google to index the client's own account — sign in,
 * sign up, reset a password, the dashboard and its three sections — and every
 * one of them served `robots: index, follow`. The dashboard pages also emitted
 * a second robots tag of their own saying the opposite, so a page said
 * "noindex, nofollow" and "index, follow" at once.
 *
 * Two things are asserted, and the second is the one that was actually broken:
 * that the private pages say noindex, and that they say it ONCE. A test that
 * only looked for a noindex tag passed the whole time.
 *
 * The sitemap itself cannot be fetched from the local harness — the PHP
 * built-in server cannot serve a path ending in .xml — so the sitemap
 * assertions go through a fixture that asks WordPress's own sitemap provider
 * what it would list. That is the same list, taken one step earlier.
 */

import { test, expect, isPlaceholder, cacheBust, login, TOOL_SLUGS } from './helpers.js';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-indexing.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

const PRIVATE_PATHS = ['/login/', '/register/', '/reset-password/'];
const PUBLIC_PATHS = ['/', '/services/', '/toolbox/', '/pricing/'];

const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture for tests/indexing.spec.js. Reports what WordPress's own
 * sitemap provider would list, and which pages the plugin considers private.
 * Written by the spec and removed again afterwards. Never shipped.
 */
add_action( 'wp_loaded', function () {
	if ( ! isset( $_GET['bw_index'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_GET['bw_index'] ) );

	// A test WordPress is installed with "discourage search engines" on. That
	// noindexes every page of the site and switches the sitemap off entirely,
	// so nothing this spec is about can be observed — a public page and a
	// sign-in page look identical, and there is no sitemap to inspect. The
	// site is made public for the duration and put back afterwards.
	if ( 'begin' === $action ) {
		add_option( 'bw_test_blog_public_backup', (string) get_option( 'blog_public' ) );
		update_option( 'blog_public', 1 );
	}

	if ( 'cleanup' === $action ) {
		$backup = get_option( 'bw_test_blog_public_backup', null );

		if ( null !== $backup ) {
			update_option( 'blog_public', (int) $backup );
			delete_option( 'bw_test_blog_public_backup' );
		}
	}

	$provider = wp_sitemaps_get_server()->registry->get_provider( 'posts' );
	$urls     = $provider ? (array) $provider->get_url_list( 1, 'page' ) : array();

	$private = array();

	foreach ( blueworx_public_private_page_ids() as $id ) {
		$private[] = array(
			'uri'  => (string) get_page_uri( $id ),
			'meta' => (string) get_post_meta( $id, BLUEWORX_SURERANK_NOINDEX_META, true ),
		);
	}

	wp_send_json( array(
		'action'      => $action,
		'blog_public' => (int) get_option( 'blog_public' ),
		'sitemap'     => wp_list_pluck( $urls, 'loc' ),
		'private'     => $private,
	) );
} );
`;

test.beforeAll(async ({ playwright }) => {
  if (isPlaceholder || !canInstallFixture) {
    return;
  }

  mkdirSync(MU_DIR, { recursive: true });
  writeFileSync(FIXTURE, FIXTURE_PLUGIN);

  const request = await playwright.request.newContext({ baseURL });
  await request.get('/?bw_index=begin').catch(() => {});
  await request.dispose();
});

test.afterAll(async ({ playwright }) => {
  if (isPlaceholder || !canInstallFixture) {
    return;
  }

  // Put the site's own "discourage search engines" setting back BEFORE the
  // fixture goes, since the cleanup endpoint lives in it.
  const request = await playwright.request.newContext({ baseURL });
  await request.get('/?bw_index=cleanup').catch(() => {});
  await request.dispose();

  if (existsSync(FIXTURE)) {
    rmSync(FIXTURE);
  }
});

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

const skipUnlessLocal = () => {
  skipPlaceholder();
  test.skip(
    !canInstallFixture,
    'Needs the local WordPress harness — the fixture plugin has to be installed on disk.'
  );
};

/** Every robots tag on the page, in order. */
const robotsTags = (page) =>
  page.evaluate(() =>
    [...document.querySelectorAll('meta[name="robots" i]')].map((el) => el.getAttribute('content') || '')
  );

test.describe('#80 What may be indexed', () => {
  for (const path of PRIVATE_PATHS) {
    test(`${path} says noindex, exactly once`, async ({ page }) => {
      skipPlaceholder();

      await page.goto(cacheBust(path));

      const tags = await robotsTags(page);

      // The live bug: two tags, saying opposite things. One is the whole point.
      expect(tags, `${path}: expected exactly one robots tag, got ${tags.length}`).toHaveLength(1);
      expect(tags[0]).toContain('noindex');
    });
  }

  test('the dashboard says noindex, exactly once, to a signed-in client', async ({ page }) => {
    skipPlaceholder();

    await login(page);
    await page.goto(cacheBust('/dashboard/'));

    const tags = await robotsTags(page);

    expect(tags, `expected exactly one robots tag, got ${tags.length}`).toHaveLength(1);
    expect(tags[0]).toContain('noindex');
  });

  for (const path of PUBLIC_PATHS) {
    test(`${path} is left alone`, async ({ page }) => {
      skipPlaceholder();

      // Compared against a private page rather than asserted outright: a test
      // WordPress is installed with "discourage search engines" on, which
      // noindexes every page of the site whatever the plugin does. What has to
      // be true on any site is that the plugin treats these two differently —
      // and that it never adds a second tag.
      await page.goto(cacheBust('/login/'));
      const privateTags = await robotsTags(page);

      await page.goto(cacheBust(path));
      const tags = await robotsTags(page);

      expect(tags.length, `${path}: expected at most one robots tag`).toBeLessThanOrEqual(1);
      expect(tags.join(), `${path} is treated the same as a sign-in page`).not.toBe(
        privateTags.join()
      );
    });
  }

  test('every private page carries the setting the SEO plugin reads', async ({ page }) => {
    skipUnlessLocal();

    const state = await (await page.request.get('/?bw_index=1')).json();

    expect(state.private.length, 'no private pages were found at all').toBeGreaterThanOrEqual(7);

    for (const entry of state.private) {
      expect(entry.meta, `/${entry.uri} is not marked noindex for the SEO plugin`).toBe('yes');
    }
  });

  test('the sitemap has no private page in it', async ({ page }) => {
    skipUnlessLocal();

    const state = await (await page.request.get('/?bw_index=1')).json();

    for (const entry of state.private) {
      const listed = state.sitemap.filter((loc) => new URL(loc).pathname.replace(/\/$/, '') === `/${entry.uri}`);

      expect(listed, `/${entry.uri} is in the sitemap`).toEqual([]);
    }
  });

  test('the sitemap has every public page in it, and every one answers', async ({ page }) => {
    skipUnlessLocal();

    const state = await (await page.request.get('/?bw_index=1')).json();
    const paths = state.sitemap.map((loc) => new URL(loc).pathname);

    for (const expected of ['/about/', '/services/', '/contact/', '/work/', '/ai/', '/pricing/', '/toolbox/']) {
      expect(paths, `${expected} is missing from the sitemap`).toContain(expected);
    }

    for (const slug of TOOL_SLUGS) {
      expect(paths, `/toolbox/${slug}/ is missing from the sitemap`).toContain(`/toolbox/${slug}/`);
    }

    // The live sitemap listed addresses that 404. Anything offered to a search
    // engine has to be a page that exists.
    const broken = [];

    for (const loc of state.sitemap) {
      const response = await page.request.get(loc);

      if (response.status() !== 200) {
        broken.push(`${loc} -> ${response.status()}`);
      }
    }

    expect(broken, `Sitemap entries that do not answer:\n${broken.join('\n')}`).toEqual([]);
  });

  test('the retired addresses redirect instead of 404ing', async ({ page }) => {
    skipPlaceholder();

    for (const [from, to] of [
      ['/feature/', '/toolbox'],
      ['/portal/', '/login'],
      ['/form/', '/contact'],
    ]) {
      const response = await page.request.get(from, { maxRedirects: 0 });

      expect(response.status(), `${from} should redirect`).toBe(301);
      expect(new URL(response.headers().location, baseURL).pathname.replace(/\/$/, '')).toBe(to);
    }
  });
});
