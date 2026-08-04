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
 * BLUEWORX_PUBLIC_PAGE_META. 2 = the client-area pages exist. 3 = the sign-in,
 * sign-up and password-reset pages exist.
 *
 * 2 and 3 no longer have a branch here. Both existed only to create pages a
 * release had added, and that is now blueworx_public_maybe_install_pages()'s
 * job on every version change — which is where it belonged, since a page being
 * missing is not a migration and forgetting the bump silently shipped a dead
 * link. This constant is back to meaning what it says: stored data that needs
 * rewriting.
 */
if ( ! defined( 'BLUEWORX_PUBLIC_DATA_VERSION' ) ) {
	define( 'BLUEWORX_PUBLIC_DATA_VERSION', 3 );
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

	update_option( 'blueworx_public_data_version', BLUEWORX_PUBLIC_DATA_VERSION );
}
add_action( 'plugins_loaded', 'blueworx_public_maybe_upgrade' );

/**
 * Re-runs page installation whenever the plugin's own version changes.
 *
 * Every page the site has is created from the registry in
 * blueworx_public_pages(), and until now the only two things that ran that
 * installer were activation and a data-version bump. Neither fires on an
 * ordinary in-place update, so a release that added a page — a new Toolbox
 * tool, say — shipped the link, the template and the nav entry, and no page.
 * The result is a dead link on a live site that no amount of front-end work
 * explains, and a data-version bump every single release to work around it.
 *
 * Keying off the plugin version instead makes it automatic and self-healing:
 * one autoloaded option read on a normal request, and on the first request
 * after an update the installer runs, creating what is missing and repairing
 * any nested page whose parent has moved. install_pages() is idempotent, so a
 * site that is already correct is left exactly as it was.
 *
 * @return void
 */
function blueworx_public_maybe_install_pages() {
	if ( get_option( 'blueworx_public_installed_version' ) === BLUEWORX_SITE_VERSION ) {
		return;
	}

	blueworx_public_install_pages();

	update_option( 'blueworx_public_installed_version', BLUEWORX_SITE_VERSION );
}
add_action( 'plugins_loaded', 'blueworx_public_maybe_install_pages', 11 );

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
