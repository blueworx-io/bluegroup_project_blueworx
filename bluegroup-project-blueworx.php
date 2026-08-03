<?php
/**
 * Plugin Name:       BlueWorx | Marketing Site
 * Plugin URI:        https://blueworx.io/
 * Description:       The BlueWorx public marketing site, rendered by the plugin itself so it is identical wherever it is hosted. Self-contained: no theme and no dependency on other plugins.
 * Version:           1.8.0
 * Requires at least: 5.0
 * Requires PHP:      8.0
 * Author:            BlueWorx
 * Author URI:        https://profiles.wordpress.org/blueworx/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bluegroup-project-blueworx
 * Domain Path:       /languages
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BLUEWORX_SITE_VERSION' ) ) {
	define( 'BLUEWORX_SITE_VERSION', '1.8.0' );
}

if ( ! defined( 'BLUEWORX_SITE_PATH' ) ) {
	define( 'BLUEWORX_SITE_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BLUEWORX_SITE_URL' ) ) {
	define( 'BLUEWORX_SITE_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Returns a cache-busting version string for a bundled asset.
 *
 * Vendored into this plugin so the public front-end has no dependency on the
 * BlueWorx enhancement plugin. Mirrors that plugin's helper but resolves paths
 * against this plugin's own directory and version.
 *
 * @param string $relative_path Asset path relative to the plugin root.
 * @return string Version string: "<plugin version>-<mtime>", or the plugin
 *                version alone if the file is missing.
 */
function blueworx_site_asset_version( $relative_path ) {
	$path = BLUEWORX_SITE_PATH . ltrim( $relative_path, '/' );

	if ( file_exists( $path ) ) {
		return BLUEWORX_SITE_VERSION . '-' . filemtime( $path );
	}

	return BLUEWORX_SITE_VERSION;
}

// The marketing front-end is this plugin's sole job, so it always loads —
// there is no feature gate. The enhancement plugin's `public_site` toggle,
// which used to guard this include, does not exist here.
require_once BLUEWORX_SITE_PATH . 'includes/public/bootstrap.php';

// The settings screen is the only admin surface this plugin has, and nothing
// on the front end needs it, so it is not loaded for a public request.
if ( is_admin() ) {
	require_once BLUEWORX_SITE_PATH . 'includes/admin/settings.php';
}

/**
 * Installs the front-end's pages on activation.
 *
 * @return void
 */
function blueworx_site_activate() {
	// Before install_pages(), not after. An install upgrading from a version
	// whose pages predate the ownership stamp must have them backfilled first,
	// or install_pages() sees unstamped pages, refuses to re-adopt them, and
	// the site loses the pages it already had.
	if ( function_exists( 'blueworx_public_maybe_upgrade' ) ) {
		blueworx_public_maybe_upgrade();
	}

	if ( function_exists( 'blueworx_public_install_pages' ) ) {
		blueworx_public_install_pages();
	}
}

/**
 * Hands the front page back to whatever it pointed at before this plugin took
 * it over, if it still owns it. See blueworx_public_restore_prior_front()
 * (includes/public/pages.php) for the full precondition.
 *
 * @return void
 */
function blueworx_site_deactivate() {
	if ( function_exists( 'blueworx_public_restore_prior_front' ) ) {
		blueworx_public_restore_prior_front();
	}
}

register_activation_hook( __FILE__, 'blueworx_site_activate' );
register_deactivation_hook( __FILE__, 'blueworx_site_deactivate' );
