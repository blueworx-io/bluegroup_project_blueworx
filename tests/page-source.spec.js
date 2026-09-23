/**
 * The Pages list's Source column names this plugin's pages "BlueWorx page".
 *
 * The column, and the read-only row actions that come with it, are the BlueWorx
 * Labs plugin's; Labs is not in the test WordPress. What is ours is the answer
 * to its blueworx_page_source filter, so a fixture asks for that answer the way
 * Labs does.
 */

import { test, expect, isPlaceholder, baseURL } from './helpers.js';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-page-source.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture for tests/page-source.spec.js: reports what the
 * blueworx_page_source filter says about a few pages. Removed after the run.
 */
add_action( 'init', function () {
	if ( ! isset( $_GET['bw_source'] ) ) {
		return;
	}

	$map   = (array) get_option( 'blueworx_public_page_ids', array() );
	$ours  = (int) ( $map['support'] ?? 0 );
	$other = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Not ours' ) );

	$result = array(
		'ours'    => apply_filters( 'blueworx_page_source', '', $ours ),
		'other'   => apply_filters( 'blueworx_page_source', '', $other ),
		'claimed' => apply_filters( 'blueworx_page_source', 'Commerce page', $ours ),
	);

	wp_delete_post( $other, true );
	wp_send_json( $result );
}, 20 );
`;

test.beforeAll(() => {
  if (isPlaceholder || !canInstallFixture) {
    return;
  }
  mkdirSync(MU_DIR, { recursive: true });
  writeFileSync(FIXTURE, FIXTURE_PLUGIN);
});

test.afterAll(() => {
  if (existsSync(FIXTURE)) {
    rmSync(FIXTURE);
  }
});

test('names our pages "BlueWorx page", leaves others alone, and keeps a label given first', async ({
  request,
}) => {
  test.skip(isPlaceholder || !canInstallFixture, 'Needs the local WordPress harness.');

  const response = await request.get(new URL('/?bw_source=1', baseURL).toString());
  const result = await response.json();

  expect(result.ours).toBe('BlueWorx page');
  expect(result.other).toBe('');
  expect(result.claimed).toBe('Commerce page');
});
