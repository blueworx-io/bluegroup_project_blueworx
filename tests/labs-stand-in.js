/**
 * A stand-in for the BlueWorx Labs customer dashboard.
 *
 * The Sales section lives on Labs' dashboard, not on a page of ours, and the
 * test WordPress has no Labs in it. This mu-plugin does the three things of
 * Labs' that the section depends on, and nothing else:
 *
 * - blueworx_store_page_id() / blueworx_store_page_url(), so the plugin can
 *   find the dashboard page;
 * - a /customer-dashboard/ page that draws every view the blueworx_store_views
 *   filter hands back, running each view's shortcode as its panel;
 * - a nav link per view, so a spec can check what a user is offered.
 *
 * Every panel is drawn at once, visible, rather than one at a time: the real
 * dashboard draws them all too and only hides the others, and a spec reads
 * whichever it needs.
 *
 * Never install this next to the real Labs: both would declare the same
 * functions. Neither the local harness nor CI has Labs in it.
 */

import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FILE = join(MU_DIR, 'bw-test-labs-stand-in.php');

export const LABS_DASHBOARD = '/customer-dashboard/';

/** The dashboard address for one view. */
export const labsView = (key) => `${LABS_DASHBOARD}?view=${key}`;

const PLUGIN = `<?php
/**
 * Test stand-in for the BlueWorx Labs customer dashboard. Written and removed
 * by tests/labs-stand-in.js; only ever exists inside the disposable local
 * WordPress the tests create.
 */

function blueworx_store_page_id( $key ) {
	return 'dashboard' === $key ? (int) get_option( 'bw_test_labs_dashboard', 0 ) : 0;
}

function blueworx_store_page_url( $key ) {
	$id = blueworx_store_page_id( $key );

	return $id && 'publish' === get_post_status( $id ) ? get_permalink( $id ) : '';
}

add_action( 'init', function () {
	if ( isset( $_GET['bw_labs'] ) && 'cleanup' === $_GET['bw_labs'] ) {
		$id = (int) get_option( 'bw_test_labs_dashboard', 0 );
		if ( $id ) {
			wp_delete_post( $id, true );
		}
		delete_option( 'bw_test_labs_dashboard' );
		wp_send_json( array( 'ok' => true ) );
	}

	$id = (int) get_option( 'bw_test_labs_dashboard', 0 );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return;
	}

	$id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => 'Customer Dashboard',
			'post_name'   => 'customer-dashboard',
		)
	);
	update_option( 'bw_test_labs_dashboard', (int) $id );
	flush_rewrite_rules( false );
}, 1 );

add_filter( 'the_content', function ( $content ) {
	if ( ! is_page( blueworx_store_page_id( 'dashboard' ) ) ) {
		return $content;
	}

	$views = apply_filters(
		'blueworx_store_views',
		array( array( 'key' => 'dashboard', 'label' => 'Dashboard', 'shortcode' => '' ) )
	);

	$nav    = '';
	$panels = '';
	foreach ( $views as $view ) {
		$nav .= '<a data-view-link="' . esc_attr( $view['key'] ) . '" href="?view=' . esc_attr( $view['key'] ) . '">' . esc_html( $view['label'] ) . '</a>';

		if ( ! empty( $view['shortcode'] ) ) {
			$panels .= '<section data-panel="' . esc_attr( $view['key'] ) . '">' . do_shortcode( '[' . $view['shortcode'] . ']' ) . '</section>';
		}
	}

	return '<nav data-labs-nav>' . $nav . '</nav>' . $panels;
}, 20 );
`;

/** Whether the stand-in can be installed here. */
export const canInstallLabsStandIn = () => existsSync(join(WP_ROOT, 'wp-settings.php'));

/** Writes the stand-in into mu-plugins. */
export function installLabsStandIn() {
  mkdirSync(MU_DIR, { recursive: true });
  writeFileSync(FILE, PLUGIN);
}

/**
 * Deletes the stand-in's page, then the stand-in itself.
 *
 * @param {import('@playwright/test').APIRequestContext} request A request context on the site.
 */
export async function removeLabsStandIn(request) {
  if (existsSync(FILE)) {
    await request.get('/?bw_labs=cleanup').catch(() => {});
    rmSync(FILE);
  }
}
