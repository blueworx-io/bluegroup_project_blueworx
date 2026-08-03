<?php
/**
 * Public front-end layer — stored-data upgrades.
 *
 * Separate from the activation hook on purpose: WordPress does NOT fire
 * register_activation_hook() when a plugin is updated in place, only when it is
 * activated. An install that upgrades from a version whose pages predate
 * BLUEWORX_PUBLIC_PAGE_META would therefore never get stamped, and every one of
 * its pages would silently stop being owned — no template, no sweep, no Site
 * Protection exemption. This runs on plugins_loaded instead, so a plain file
 * update is enough.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Version of the plugin's stored data (options and post meta), NOT the plugin
 * version.
 *
 * Bumped only when stored data needs migrating, so a routine release does not
 * make every site re-run a migration it has already done. 1 = pages carry
 * BLUEWORX_PUBLIC_PAGE_META. 2 = the client-area pages exist.
 */
if ( ! defined( 'BLUEWORX_PUBLIC_DATA_VERSION' ) ) {
	define( 'BLUEWORX_PUBLIC_DATA_VERSION', 2 );
}

/**
 * Runs any stored-data migration this install has not had yet.
 *
 * Version-gated and idempotent: after the first pass the stored version matches
 * and this returns on its first line, so the cost on every later request is one
 * autoloaded option read. Safe to call directly (activation does) as well as via
 * the hook.
 *
 * @return void
 */
function blueworx_public_maybe_upgrade() {
	$stored = (int) get_option( 'blueworx_public_data_version', 0 );

	if ( $stored >= BLUEWORX_PUBLIC_DATA_VERSION ) {
		return;
	}

	if ( $stored < 1 ) {
		blueworx_public_backfill_page_meta();
	}

	if ( $stored < 2 ) {
		// The client area (#37) adds pages to the registry. Activation creates
		// them on a fresh install, but WordPress does not re-run activation for
		// an in-place update — so without this, an existing site updates and
		// the dashboard simply has no pages. install_pages() only ever creates
		// what is missing, so this is safe on a site that already has them.
		blueworx_public_install_pages();
	}

	update_option( 'blueworx_public_data_version', BLUEWORX_PUBLIC_DATA_VERSION );
}
add_action( 'plugins_loaded', 'blueworx_public_maybe_upgrade' );

/**
 * Stamps BLUEWORX_PUBLIC_PAGE_META onto every page already in
 * blueworx_public_page_ids.
 *
 * Ownership became a stamp rather than a slug match. Pages created before that
 * have no stamp, and without this an existing install would lose its own pages
 * the moment the stamp became a requirement: blueworx_public_install_pages()
 * would refuse to re-adopt them and the plugin would stop rendering the site it
 * had been rendering yesterday.
 *
 * The map is the correct source: an ID only got in there by being created by
 * this plugin, or — on a version with the slug-adoption bug — by colliding with
 * a registry slug. That second case is why this does not go looking for pages by
 * slug: it stamps what is already claimed and nothing more, so upgrading cannot
 * newly claim anything.
 *
 * Idempotent — a page that already carries the stamp is skipped, and a mapped ID
 * that is no longer a page (trashed and purged) is ignored rather than having
 * orphan meta written against it.
 *
 * @return void
 */
function blueworx_public_backfill_page_meta() {
	$map = (array) get_option( 'blueworx_public_page_ids', array() );

	foreach ( $map as $page_id ) {
		$page_id = (int) $page_id;

		if ( $page_id <= 0 || 'page' !== get_post_type( $page_id ) ) {
			continue;
		}

		if ( blueworx_public_page_is_ours( $page_id ) ) {
			continue;
		}

		update_post_meta( $page_id, BLUEWORX_PUBLIC_PAGE_META, 1 );
	}
}
