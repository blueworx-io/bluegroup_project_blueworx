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

// Labs refuses to trash a labelled page by stopping the request. Imitated here
// so retiring a page on upgrade is tested against it.
add_action( 'wp_trash_post', function ( $post_id ) {
	if ( '' !== apply_filters( 'blueworx_page_source', '', $post_id ) ) {
		wp_die( 'This is a ' . esc_html( strtolower( apply_filters( 'blueworx_page_source', '', $post_id ) ) ) . '. It cannot be deleted.', '', array( 'response' => 403 ) );
	}
} );

// Stages an upgrade that retires one of our pages: a stamped page in the map
// under a retired slug, and the installed version wound back.
add_action( 'init', function () {
	if ( ! isset( $_GET['bw_retire'] ) ) {
		return;
	}

	if ( 'stage' === $_GET['bw_retire'] ) {
		$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Old dashboard', 'post_name' => 'bw-old-dashboard' ) );
		update_post_meta( $id, BLUEWORX_PUBLIC_PAGE_META, '1' );
		$map              = (array) get_option( 'blueworx_public_page_ids', array() );
		$map['dashboard'] = $id;
		update_option( 'blueworx_public_page_ids', $map );
		update_option( 'blueworx_public_installed_version', '0.0.0' );
		wp_send_json( array( 'id' => $id ) );
	}

	$id = (int) $_GET['bw_retire'];
	$map = (array) get_option( 'blueworx_public_page_ids', array() );
	$status = get_post_status( $id );
	wp_delete_post( $id, true );
	wp_send_json( array( 'status' => $status, 'mapped' => in_array( $id, array_map( 'intval', $map ), true ) ) );
}, 1 );
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

// 1.23.0 took the live site down: the upgrade trashed a retired page while it
// was still labelled, Labs stopped the request, and it happened again on every
// request after.
test('an upgrade that retires a page does not trip the refusal to delete', async ({ request }) => {
  test.skip(isPlaceholder || !canInstallFixture, 'Needs the local WordPress harness.');

  const url = (path) => new URL(path, baseURL).toString();
  const { id } = await (await request.get(url('/?bw_retire=stage'))).json();

  // The first request after the upgrade is the one that retires the page.
  const home = await request.get(url(`/?after_upgrade=${Date.now()}`));
  expect(home.status()).toBe(200);

  const after = await (await request.get(url(`/?bw_retire=${id}`))).json();
  expect(after.status).toBe('trash');
  expect(after.mapped).toBe(false);
});
