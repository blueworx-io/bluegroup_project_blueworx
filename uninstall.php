<?php
/**
 * Uninstall: remove the options this plugin created.
 *
 * Only the front-end's own options are removed. The pages the plugin installed
 * are deliberately left in place — deleting user-facing content on uninstall is
 * surprising — and the prior-front pointer is consumed by deactivation, so by
 * the time uninstall runs it is normally already gone. Slugs are inlined
 * because the plugin's code is not loaded during uninstall.
 *
 * @package BlueWorxSite
 */

// Only run from WordPress's uninstall flow.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'blueworx_public_prior_front' );
delete_option( 'blueworx_public_page_ids' );
