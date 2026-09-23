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
 * ordinary in-place update, so a release that added a page — a new product
 * page, say — shipped the link, the template and the nav entry, and no page.
 * The result is a dead link on a live site that no amount of front-end work
 * explains, and a data-version bump every single release to work around it.
 *
 * Keying off the plugin version instead makes it automatic and self-healing:
 * one autoloaded option read on a normal request, and on the first request
 * after an update the installer runs, creating what is missing and repairing
 * any nested page whose parent has moved. install_pages() is idempotent, so a
 * site that is already correct is left exactly as it was.
 *
 * ON `init`, NOT `plugins_loaded`. This took the live site down. Creating a
 * page means wp_insert_post(), which builds the page's permalink through
 * $wp_rewrite — and WordPress does not create $wp_rewrite until after
 * plugins_loaded has finished. Reading it there is reading a property on null.
 *
 * It never showed up in testing because it only fires when a page is actually
 * MISSING: a site that already has all its pages never reaches wp_insert_post()
 * at all, and every test WordPress is provisioned with them already there. The
 * one install where a page was missing was the live one.
 *
 * Priority 5, so the pages exist before anything at the default priority goes
 * looking for them.
 *
 * @return void
 */
function blueworx_public_maybe_install_pages() {
	if ( get_option( 'blueworx_public_installed_version' ) === BLUEWORX_SITE_VERSION ) {
		return;
	}

	blueworx_public_retire_removed_pages();
	blueworx_public_install_pages();

	update_option( 'blueworx_public_installed_version', BLUEWORX_SITE_VERSION );
}
add_action( 'init', 'blueworx_public_maybe_install_pages', 5 );

/**
 * The twelve Toolbox tool slugs, kept only so their pages can be retired and
 * their addresses redirected now the Toolbox itself is gone (1.17.0).
 *
 * @return string[]
 */
function blueworx_public_retired_tool_slugs() {
	return array( 'sureforms', 'surerank', 'suremail', 'surewriter', 'surecart', 'zipwp', 'ottokit', 'ally', 'sweet-ai', 'elementor-ai-planner', 'elementor', 'equalize-a11y-checker' );
}

/**
 * Trashes the pages a release removed from the registry.
 *
 * A registry entry that disappears leaves its page behind: published, in the
 * ID map, and — because the template is gone — rendered by the theme as an
 * empty page. Trashed (not deleted) so it can be restored from wp-admin, and
 * only when the page carries the plugin's own stamp: a page the site created
 * under the same slug is not ours to touch. The legacy redirect for each path
 * (includes/public/redirects.php) is what visitors actually meet.
 *
 * Idempotent: a slug no longer in the map is skipped.
 *
 * @return void
 */
function blueworx_public_retire_removed_pages() {
	$map     = (array) get_option( 'blueworx_public_page_ids', array() );
	$retired = array_merge(
		// 'register' and 'reset-password' went with the forms they carried when
		// the shop's sign-in form took over /login (1.20.0).
		array( 'pricing', 'services', 'work', 'toolbox', 'dashboard/toolbox', 'register', 'reset-password' ),
		// The plugin's own client dashboard, now the Labs customer dashboard's
		// job (1.23.0). Its two Sales sections moved onto that dashboard; the
		// rest went. Children before the parent, so none is left orphaned.
		array(
			'dashboard/subscriptions',
			'dashboard/invoices',
			'dashboard/orders',
			'dashboard/websites',
			'dashboard/partner',
			'dashboard/details',
			'dashboard/support',
			'dashboard/commission',
			'dashboard/quote-builder',
			'dashboard',
		),
		array_map(
			function ( $slug ) {
				return 'toolbox/' . $slug;
			},
			blueworx_public_retired_tool_slugs()
		)
	);
	$to_trash = array();

	foreach ( $retired as $slug ) {
		if ( empty( $map[ $slug ] ) ) {
			continue;
		}

		$to_trash[] = (int) $map[ $slug ];
		unset( $map[ $slug ] );
	}

	if ( array() === $to_trash ) {
		return;
	}

	// Out of the map BEFORE anything is trashed. A page in the map is labelled
	// "BlueWorx page" (blueworx_public_page_source()), and BlueWorx Labs refuses
	// to trash a labelled page by stopping the request outright — which took the
	// whole site down in 1.23.0, on every request, because the map was never
	// saved and so this ran again each time.
	update_option( 'blueworx_public_page_ids', $map );

	foreach ( $to_trash as $page_id ) {
		if ( 'page' === get_post_type( $page_id ) && blueworx_public_page_is_ours( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
			wp_trash_post( $page_id );
		}
	}
}

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
