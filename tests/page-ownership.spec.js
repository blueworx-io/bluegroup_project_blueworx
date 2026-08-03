/**
 * Page ownership — the plugin must only claim pages it created.
 *
 * Seen in production (v1.5.3): a site whose own Elementor page happened to be
 * slugged "home" had that page adopted into blueworx_public_page_ids on
 * activation and reported as owned, so the asset sweep stripped every
 * non-blueworx style and script from it — its Elementor CSS and the theme's
 * global CSS included. The page rendered with no layout at all. Nothing about
 * the page belonged to this plugin except a coincidental slug.
 *
 * Ownership is now a post meta stamp written at creation. These specs exercise
 * the two ways a foreign page used to get claimed (adoption at activation, and
 * the post_name fallback at request time), plus the upgrade path that keeps
 * existing installs working once the stamp became a requirement.
 *
 * SAME FIXTURE STRATEGY AS asset-isolation.spec.js, for the same reason: proving
 * a page is NOT swept needs something sweepable on it, and proving a slug
 * collision is refused needs a colliding slug that is genuinely somebody else's.
 * The fixture adds two throwaway registry entries and a foreign stylesheet, and
 * drives activation through the plugin's real functions rather than a
 * re-implementation of them. It never touches the plugin's own eight pages, and
 * it restores the page map afterwards.
 */

import { test, expect } from '@playwright/test';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';
const isPlaceholder = /placeholder/i.test(baseURL);

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-page-ownership.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

// The collided slug is the site's own page; the created slug is the plugin's.
// Deliberately not "home"/"about" — the harness already has the plugin's real
// pages at those paths, and a spec that had to delete them to run would be
// testing a site that no longer resembles the one in production.
const COLLIDED = 'bw-collide';
const CREATED = 'bw-created';
const LEGACY = 'bw-legacy';

const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture for tests/page-ownership.spec.js. Adds throwaway registry
 * entries, a foreign stylesheet, and a small JSON control endpoint that drives
 * the plugin's real activation/upgrade functions. Written by the spec and
 * removed again afterwards. Never shipped.
 */

// The extra registry entries exist only while a spec has switched them on, so
// no other spec ever sees a registry that differs from the shipped one.
add_filter( 'blueworx_public_pages', function ( $pages ) {
	if ( '1' !== (string) get_option( 'bw_test_ownership_on', '' ) ) {
		return $pages;
	}

	$pages['${COLLIDED}'] = array( 'title' => 'Collide', 'template' => 'pages/about.php' );
	$pages['${CREATED}']  = array( 'title' => 'Created', 'template' => 'pages/about.php' );

	return $pages;
} );

// Stands in for the theme and page-builder CSS the live bug destroyed. Its
// presence on a page means the sweep did not run there.
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'bw-ownership-foreign', home_url( '/bw-ownership-foreign.css' ), array(), '1.0' );
} );

/**
 * Creates a page nobody stamped — i.e. the site's own content.
 */
function bw_test_make_foreign_page( $slug, $title ) {
	$existing = get_page_by_path( $slug );

	if ( $existing instanceof WP_Post ) {
		return (int) $existing->ID;
	}

	return (int) wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => '<p id="bw-foreign-content">The site owner wrote this.</p>',
	) );
}

function bw_test_pages_named( $slug ) {
	global $wpdb;

	return array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status != 'trash' AND ( post_name = %s OR post_name LIKE %s )",
		$slug,
		$wpdb->esc_like( $slug ) . '-%'
	) ) );
}

add_action( 'wp_loaded', function () {
	if ( ! isset( $_GET['bw_own'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_GET['bw_own'] ) );
	$map    = (array) get_option( 'blueworx_public_page_ids', array() );

	switch ( $action ) {
		case 'begin':
			// Snapshot once, so a spec that fails part way still leaves
			// something to restore from.
			add_option( 'bw_test_map_backup', $map );
			add_option( 'bw_test_data_version_backup', (string) get_option( 'blueworx_public_data_version', '' ) );
			// The deactivate path can repoint the front page, which every
			// other spec depends on. Snapshot it too.
			add_option( 'bw_test_front_backup', array(
				'show_on_front' => get_option( 'show_on_front' ),
				'page_on_front' => (int) get_option( 'page_on_front' ),
			) );
			update_option( 'bw_test_ownership_on', '1' );
			break;

		case 'make_collision':
			bw_test_make_foreign_page( '${COLLIDED}', 'Collide' );
			break;

		case 'make_legacy':
			// A page from before the stamp existed: in the map, no meta, and
			// the data version reset so the upgrade has not run yet.
			$legacy_id = bw_test_make_foreign_page( '${LEGACY}', 'Legacy' );
			delete_post_meta( $legacy_id, '_blueworx_public_page' );
			$map['${LEGACY}'] = $legacy_id;
			update_option( 'blueworx_public_page_ids', $map );
			delete_option( 'blueworx_public_data_version' );
			break;

		case 'activate':
			blueworx_site_activate();
			break;

		case 'deactivate':
			blueworx_site_deactivate();
			break;

		case 'cleanup':
			foreach ( array( '${COLLIDED}', '${CREATED}', '${LEGACY}' ) as $slug ) {
				foreach ( bw_test_pages_named( $slug ) as $page_id ) {
					wp_delete_post( $page_id, true );
				}
			}

			$backup = get_option( 'bw_test_map_backup', null );

			if ( is_array( $backup ) ) {
				update_option( 'blueworx_public_page_ids', $backup );
			}

			$version_backup = (string) get_option( 'bw_test_data_version_backup', '' );

			if ( '' === $version_backup ) {
				delete_option( 'blueworx_public_data_version' );
			} else {
				update_option( 'blueworx_public_data_version', $version_backup );
			}

			$front_backup = get_option( 'bw_test_front_backup', null );

			if ( is_array( $front_backup ) ) {
				update_option( 'show_on_front', $front_backup['show_on_front'] );
				update_option( 'page_on_front', (int) $front_backup['page_on_front'] );
			}

			delete_option( 'bw_test_map_backup' );
			delete_option( 'bw_test_data_version_backup' );
			delete_option( 'bw_test_front_backup' );
			delete_option( 'bw_test_ownership_on' );
			break;
	}

	$map   = (array) get_option( 'blueworx_public_page_ids', array() );
	$slugs = array( '${COLLIDED}', '${CREATED}', '${LEGACY}' );
	$state = array();

	foreach ( $slugs as $slug ) {
		$mapped = isset( $map[ $slug ] ) ? (int) $map[ $slug ] : 0;
		$page   = get_page_by_path( $slug );

		$state[ $slug ] = array(
			'mapped'    => $mapped,
			'page_id'   => $page instanceof WP_Post ? (int) $page->ID : 0,
			'stamped'   => $page instanceof WP_Post ? blueworx_public_page_is_ours( $page->ID ) : false,
			'pages'     => count( bw_test_pages_named( $slug ) ),
		);
	}

	wp_send_json( array(
		'action'       => $action,
		'data_version' => (string) get_option( 'blueworx_public_data_version', '' ),
		'state'        => $state,
	) );
} );
`;

test.beforeAll(() => {
  if (isPlaceholder || !canInstallFixture) {
    return;
  }
  mkdirSync(MU_DIR, { recursive: true });
  writeFileSync(FIXTURE, FIXTURE_PLUGIN);
});

test.afterAll(async ({ playwright }) => {
  if (isPlaceholder || !canInstallFixture) {
    return;
  }

  // Restore the page map and remove the throwaway pages BEFORE the fixture
  // goes, since the cleanup endpoint lives in it.
  const request = await playwright.request.newContext({ baseURL });
  await request.get('/?bw_own=cleanup').catch(() => {});
  await request.dispose();

  if (existsSync(FIXTURE)) {
    rmSync(FIXTURE);
  }
});

const skipUnlessLocal = () => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
  test.skip(
    !canInstallFixture,
    'Needs the local WordPress harness — the fixture plugin has to be installed on disk.'
  );
};

/** Drives the fixture endpoint and returns the reported state. */
async function control(page, action) {
  const response = await page.request.get(`/?bw_own=${action}`);
  expect(response.ok(), `control action "${action}" failed`).toBe(true);
  return response.json();
}

/** Every stylesheet href the page actually asked the browser for. */
async function styleUrls(page) {
  return page.evaluate(() =>
    [...document.querySelectorAll('link[rel="stylesheet"]')]
      .map((el) => el.getAttribute('href'))
      .filter(Boolean)
  );
}

test('a foreign page whose slug collides is never claimed, across activate → deactivate → reactivate', async ({
  page,
}) => {
  skipUnlessLocal();

  await control(page, 'begin');
  await control(page, 'make_collision');

  for (const cycle of ['activate', 'deactivate', 'activate']) {
    const result = await control(page, cycle);
    const collided = result.state[COLLIDED];

    expect(collided.page_id, 'the site\'s own page must still exist').toBeGreaterThan(0);
    expect(collided.mapped, `claimed into the page map after ${cycle}`).toBe(0);
    expect(collided.stamped, `stamped as plugin-created after ${cycle}`).toBe(false);
    expect(
      collided.pages,
      'the plugin must not create a suffixed duplicate ("bw-collide-2") beside the real page'
    ).toBe(1);
  }

  // The half that actually broke the live site: not the map, but what the
  // sweep does once a page is considered owned.
  await page.goto(`/${COLLIDED}/`);

  await expect(page.locator('#bw-foreign-content')).toBeVisible();
  await expect(page.locator('body.bw-page'), 'the plugin must not render its template here').toHaveCount(0);

  const urls = await styleUrls(page);

  expect(
    urls.filter((url) => /bw-ownership-foreign/.test(url)).length,
    'the site\'s own CSS was stripped from a page the plugin did not create — this is the production bug'
  ).toBeGreaterThan(0);
});

test('a page the plugin created is still mapped, rendered and swept', async ({ page }) => {
  skipUnlessLocal();

  await control(page, 'begin');
  const result = await control(page, 'activate');
  const created = result.state[CREATED];

  expect(created.page_id, 'the plugin should have created this page').toBeGreaterThan(0);
  expect(created.mapped).toBe(created.page_id);
  expect(created.stamped, 'a page the plugin creates must carry the ownership stamp').toBe(true);

  await page.goto(`/${CREATED}/`);

  await expect(page.locator('body.bw-page'), 'the plugin renders its own template here').toHaveCount(1);

  const urls = await styleUrls(page);

  expect(
    urls.filter((url) => /bw-ownership-foreign/.test(url)),
    'the sweep must keep working exactly as before on a genuinely owned page'
  ).toEqual([]);
  expect(urls.some((url) => url.includes('public.css'))).toBe(true);
});

test('upgrading backfills the stamp so an existing install keeps its pages', async ({ page }) => {
  skipUnlessLocal();

  await control(page, 'begin');

  const before = await control(page, 'make_legacy');

  expect(before.state[LEGACY].mapped, 'set up as an already-mapped page').toBeGreaterThan(0);
  expect(before.state[LEGACY].stamped, 'set up as pre-dating the stamp').toBe(false);
  expect(before.data_version, 'set up as not yet upgraded').toBe('');

  const after = await control(page, 'activate');

  expect(after.state[LEGACY].stamped, 'the backfill must stamp every already-mapped page').toBe(true);
  expect(after.state[LEGACY].mapped, 'and it must stay mapped').toBe(before.state[LEGACY].mapped);
  expect(after.data_version, 'the routine is version-gated').toBe('1');

  // Idempotent: running it again changes nothing and cannot claim anything new.
  const again = await control(page, 'activate');

  expect(again.state[LEGACY].mapped).toBe(before.state[LEGACY].mapped);
  expect(again.state[COLLIDED].mapped, 'the upgrade must never claim a page by slug').toBe(0);
});
