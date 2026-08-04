/**
 * The install states a fresh WordPress never reaches (#83).
 *
 * All twelve Toolbox tool pages were broken in production while two specs
 * walked those same URLs and passed. Both ran against a WordPress provisioned
 * from scratch seconds earlier, and on a brand-new install activation creates
 * the pages correctly — so the tests only ever exercised the one state that was
 * never broken. The live site was an install that already had pages, already
 * had a stored ID map, and had been updated in place rather than activated.
 *
 * This spec covers that: it drives a real WordPress into each of the states an
 * existing site can be in, then runs the plugin's real install path and asserts
 * every tool page resolves. Written against main before #75 it fails on all
 * three.
 *
 * Same fixture strategy as page-ownership.spec.js — a throwaway mu-plugin with
 * a JSON control endpoint that calls the plugin's own functions, rather than a
 * re-implementation of them in the test. It snapshots the page map and the
 * tool pages before it starts and restores them afterwards.
 */

import { test, expect } from '@playwright/test';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';
import { toolRegistry } from './helpers.js';

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';
const isPlaceholder = /placeholder/i.test(baseURL);

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-toolbox-upgrade.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

const TOOLS = toolRegistry();

const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture for tests/toolbox-upgrade.spec.js. Puts the site into each of
 * the install states an existing site can be in, and reports where every tool
 * page actually lives. Written by the spec and removed again afterwards.
 * Never shipped.
 */

/** Registry keys of the nested tool pages, from the plugin's own registry. */
function bw_test_tool_keys() {
	$keys = array();

	foreach ( blueworx_content_tools() as $tool ) {
		$keys[] = 'toolbox/' . $tool['slug'];
	}

	return $keys;
}

/** Where each tool page currently resolves, keyed by registry key. */
function bw_test_tool_state() {
	$map   = (array) get_option( 'blueworx_public_page_ids', array() );
	$state = array();

	foreach ( bw_test_tool_keys() as $key ) {
		$id = isset( $map[ $key ] ) ? (int) $map[ $key ] : 0;

		$state[ $key ] = array(
			'mapped' => $id,
			'uri'    => $id ? (string) get_page_uri( $id ) : '',
		);
	}

	return $state;
}

add_action( 'wp_loaded', function () {
	if ( ! isset( $_GET['bw_toolbox'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_GET['bw_toolbox'] ) );
	$map    = (array) get_option( 'blueworx_public_page_ids', array() );

	switch ( $action ) {
		case 'begin':
			// Snapshot once, so a spec that fails part way through still
			// leaves something to restore from.
			add_option( 'bw_test_toolbox_map_backup', $map );
			add_option( 'bw_test_toolbox_version_backup', (string) get_option( 'blueworx_public_installed_version', '' ) );
			break;

		case 'missing_pages':
			// A release that ADDS a tool, on a site that updates in place.
			// The registry gains an entry; nothing on the site has the page.
			// This is also the state a site is in the first time tools are
			// introduced at all.
			foreach ( bw_test_tool_keys() as $key ) {
				if ( isset( $map[ $key ] ) ) {
					wp_delete_post( (int) $map[ $key ], true );
					unset( $map[ $key ] );
				}
			}

			update_option( 'blueworx_public_page_ids', $map );
			break;

		case 'orphaned_pages':
			// The live site's state: the twelve pages exist and are mapped,
			// but sit at the top level of the site rather than under Toolbox,
			// so every /toolbox/<slug> link points at nothing.
			foreach ( bw_test_tool_keys() as $key ) {
				if ( isset( $map[ $key ] ) ) {
					wp_update_post( array( 'ID' => (int) $map[ $key ], 'post_parent' => 0 ) );
				}
			}
			break;

		case 'reparent_toolbox':
			// The Toolbox parent page is trashed and made again, so it has a
			// different ID and every child still points at the old one.
			if ( isset( $map['toolbox'] ) ) {
				wp_delete_post( (int) $map['toolbox'], true );
			}

			$new_id = wp_insert_post( array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Toolbox',
				'post_name'    => 'toolbox',
				'post_content' => '',
				'meta_input'   => array( '_blueworx_public_page' => 1 ),
			) );

			$map['toolbox'] = (int) $new_id;
			update_option( 'blueworx_public_page_ids', $map );
			break;

		case 'update_in_place':
			// Exactly what WordPress does for a plugin update: the files
			// change, and no activation hook runs. The stored version no
			// longer matches, which is the only signal the plugin gets.
			delete_option( 'blueworx_public_installed_version' );
			blueworx_public_maybe_install_pages();
			break;

		case 'cleanup':
			// Put every tool page back where it belongs, then restore the
			// stored version. Running the installer first means a page the
			// spec deleted is recreated rather than left missing.
			blueworx_public_install_pages();

			$version_backup = (string) get_option( 'bw_test_toolbox_version_backup', '' );

			if ( '' === $version_backup ) {
				delete_option( 'blueworx_public_installed_version' );
			} else {
				update_option( 'blueworx_public_installed_version', $version_backup );
			}

			delete_option( 'bw_test_toolbox_map_backup' );
			delete_option( 'bw_test_toolbox_version_backup' );
			break;
	}

	wp_send_json( array(
		'action'  => $action,
		'version' => (string) get_option( 'blueworx_public_installed_version', '' ),
		'tools'   => bw_test_tool_state(),
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

  const request = await playwright.request.newContext({ baseURL });
  await request.get('/?bw_toolbox=cleanup').catch(() => {});
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
  const response = await page.request.get(`/?bw_toolbox=${action}`);
  expect(response.ok(), `control action "${action}" failed`).toBe(true);
  return response.json();
}

/**
 * Asserts every tool in the registry is nested under Toolbox and its page
 * answers — both halves, because either one alone was true while the live site
 * was broken.
 */
async function expectEveryToolResolves(page, tools) {
  const wrong = [];

  for (const { slug } of TOOLS) {
    const key = `toolbox/${slug}`;
    const entry = tools[key];

    if (!entry || !entry.mapped) {
      wrong.push(`${key}: no page`);
      continue;
    }

    if (entry.uri !== key) {
      wrong.push(`${key}: page lives at /${entry.uri}`);
    }
  }

  expect(wrong, `Tool pages in the wrong place:\n${wrong.join('\n')}`).toEqual([]);

  const broken = [];

  for (const { slug } of TOOLS) {
    const response = await page.request.get(`/toolbox/${slug}/`);

    if (response.status() !== 200) {
      broken.push(`${slug} -> ${response.status()}`);
    }
  }

  expect(broken, `Tool pages that do not answer:\n${broken.join('\n')}`).toEqual([]);
}

test('a tool page that does not exist yet is created by an in-place update', async ({ page }) => {
  skipUnlessLocal();

  await control(page, 'begin');

  const missing = await control(page, 'missing_pages');

  expect(
    Object.values(missing.tools).filter((tool) => tool.mapped).length,
    'the fixture should have removed every tool page'
  ).toBe(0);

  const after = await control(page, 'update_in_place');

  await expectEveryToolResolves(page, after.tools);
});

test('tool pages orphaned at the top level are put back under Toolbox', async ({ page }) => {
  skipUnlessLocal();

  await control(page, 'begin');

  const orphaned = await control(page, 'orphaned_pages');

  expect(
    orphaned.tools[`toolbox/${TOOLS[0].slug}`].uri,
    'the fixture should have moved the tool pages to the top level'
  ).toBe(TOOLS[0].slug);

  const after = await control(page, 'update_in_place');

  await expectEveryToolResolves(page, after.tools);
});

test('trashing and remaking the Toolbox page does not break its children', async ({ page }) => {
  skipUnlessLocal();

  await control(page, 'begin');
  await control(page, 'reparent_toolbox');

  const after = await control(page, 'update_in_place');

  await expectEveryToolResolves(page, after.tools);
});

test('a site already in the right state is left alone by an update', async ({ page }) => {
  skipUnlessLocal();

  await control(page, 'begin');

  const before = await control(page, 'update_in_place');
  const after = await control(page, 'update_in_place');

  expect(after.tools, 'an idempotent installer must not move or recreate anything').toEqual(
    before.tools
  );

  await expectEveryToolResolves(page, after.tools);
});
