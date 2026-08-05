<?php
/**
 * Public front-end layer — asset enqueueing.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues public styles on plugin-owned pages only.
 *
 * Scoped deliberately: these styles carry a reset, so loading them on a page
 * the plugin does not own would restyle someone else's content.
 *
 * @return void
 */
function blueworx_enqueue_public_assets() {
	if ( ! blueworx_public_renders_request() ) {
		return;
	}

	wp_enqueue_style(
		'blueworx-fonts',
		BLUEWORX_SITE_URL . 'assets/css/blueworx-fonts.css',
		array(),
		blueworx_site_asset_version( 'assets/css/blueworx-fonts.css' )
	);

	wp_enqueue_style(
		'blueworx-public',
		BLUEWORX_SITE_URL . 'assets/css/public.css',
		array( 'blueworx-fonts' ),
		blueworx_site_asset_version( 'assets/css/public.css' )
	);

	wp_enqueue_script(
		'blueworx-public-nav',
		BLUEWORX_SITE_URL . 'assets/js/public-nav.js',
		array(),
		blueworx_site_asset_version( 'assets/js/public-nav.js' ),
		true
	);

	wp_enqueue_script(
		'blueworx-public-widgets',
		BLUEWORX_SITE_URL . 'assets/js/public-widgets.js',
		array(),
		blueworx_site_asset_version( 'assets/js/public-widgets.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'blueworx_enqueue_public_assets' );

/**
 * Dequeues the active theme's own front-end stylesheet on plugin-owned
 * pages, so a theme with bare-element or `!important` rules cannot visibly
 * change a page this plugin renders even though the `.bw-page` DOM never
 * changes.
 *
 * Deliberately narrow: only the theme's own stylesheet handle(s) are
 * removed — get_stylesheet() . '-style' (and, for a child theme,
 * get_template() . '-style' for the parent) is the convention every default
 * WordPress theme since Twenty Ten uses for its main style.css, and it is
 * what a from-scratch block theme like Twenty Twenty-Five/Twenty
 * Twenty-Four registers too. Hooked late (priority 100) so it runs after the
 * theme itself (and other plugins) have had a chance to enqueue on the normal
 * wp_enqueue_scripts priority.
 *
 * Kept separate from blueworx_public_dequeue_foreign_assets() below, which
 * sweeps other plugins' assets too. The two answer different questions and can
 * be switched off independently: this is a narrow, named removal that must keep
 * working even on a page where the sweep has been filtered off because it
 * genuinely needs a third party's CSS.
 *
 * @return void
 */
function blueworx_public_dequeue_theme_styles() {
	if ( ! blueworx_public_renders_request() ) {
		return;
	}

	$handles = array_unique(
		array(
			get_stylesheet() . '-style',
			get_template() . '-style',
		)
	);

	foreach ( $handles as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'blueworx_public_dequeue_theme_styles', 100 );

/**
 * Whether the current owned page will run a shortcode belonging to another
 * plugin, and therefore needs that plugin's styles and scripts.
 *
 * The sweep below is an allowlist, so anything it does not recognise goes —
 * including the assets a legitimately embedded third-party form needs. The
 * contact page is the one place that happens: it renders whatever shortcode
 * `blueworx_contact_form_shortcode` names (see templates/pages/contact.php).
 * A form stripped of its CSS and JS is worse than a heavy page, so that page
 * opts out of the sweep entirely whenever a shortcode is actually configured.
 *
 * Deliberately checked by TEMPLATE rather than by slug: an admin who renames
 * the Contact page must not silently turn its form's assets off.
 *
 * @return bool True when the sweep must be skipped.
 */
function blueworx_public_page_needs_foreign_assets() {
	$page = blueworx_public_current_page();

	if ( null === $page || 'pages/contact.php' !== $page['template'] ) {
		return false;
	}

	$shortcode = (string) apply_filters(
		'blueworx_contact_form_shortcode',
		(string) get_option( 'blueworx_contact_form_shortcode', '' )
	);

	return '' !== trim( $shortcode );
}

/**
 * The handles that may load on a page this plugin renders.
 *
 * An ALLOWLIST, for the same reason blueworx_public_is_owned_request_path()
 * uses one: a denylist has to name every offender up front and falls behind the
 * moment somebody installs another plugin. The live site currently carries
 * SureCart, UiCore, SureDash, SureRank and Elementor assets on marketing pages
 * that use none of them — but naming those five would leave the sixth to be
 * discovered in production. Anything not recognised here is refused by default.
 *
 * Prefix-matched, because a plugin registers many handles from one namespace
 * (`blueworx-public`, `blueworx-fonts`, `blueworx-labs-translate`) and listing
 * each one would need editing every time a stylesheet is added.
 *
 * @return array Allowed handle prefixes.
 */
function blueworx_public_allowed_asset_prefixes() {
	$prefixes = array(
		// This plugin, and its sister enhancement plugin — the translate widget
		// is a deliberate part of the marketing pages, not incidental weight.
		'blueworx',
	);

	// A journal article is editor content, not a designed template (#95): its
	// paragraphs, images, galleries and embeds are core blocks, and core ships
	// their CSS under these handles. Sweeping them leaves a post rendered with
	// the plugin's typography but none of the block layout the author saw in
	// the editor — columns stacked, galleries as a vertical list of images.
	// Marketing pages contain no blocks, so this is added only where it applies.
	if ( function_exists( 'blueworx_public_renders_post' ) && blueworx_public_renders_post() ) {
		$prefixes[] = 'wp-block';
		$prefixes[] = 'global-styles';
		$prefixes[] = 'core-block-supports';
		$prefixes[] = 'classic-theme-styles';
	}

	// The admin bar is only ever shown to a logged-in user, and stripping it
	// would break wp-admin navigation for the people editing the site. Its
	// script depends on hoverintent-js, and it draws its icons from dashicons.
	if ( is_admin_bar_showing() ) {
		$prefixes[] = 'admin-bar';
		$prefixes[] = 'dashicons';
		$prefixes[] = 'hoverintent';
	}

	return (array) apply_filters( 'blueworx_public_allowed_asset_prefixes', $prefixes );
}

/**
 * Removes every style and script on an owned page that this plugin did not put
 * there.
 *
 * Marketing pages were shipping roughly 450KB of HTML because plugins that take
 * no part in rendering them enqueue globally: SureCart alone inlines around a
 * hundred block stylesheets into pages that contain no blocks, and UiCore adds a
 * global script from the uploads directory. The plugin renders these pages in
 * its own markup, so none of it has anything to style or wire up.
 *
 * Hooked at PHP_INT_MAX so it runs after every other plugin has enqueued —
 * anything sweeping earlier only catches whoever registered before it, which
 * looks like it works and quietly misses half the queue.
 *
 * Dequeue, not deregister: a handle removed from the queue can still be pulled
 * back in as a dependency of something that legitimately needs it, which is the
 * behaviour we want. Deregistering would make that a fatal-looking missing
 * dependency instead.
 *
 * @return void
 */
function blueworx_public_dequeue_foreign_assets() {
	if ( ! blueworx_public_renders_request() ) {
		return;
	}

	/**
	 * Filters whether foreign assets are swept from this page at all.
	 *
	 * @param bool $sweep Whether to sweep.
	 */
	if ( ! apply_filters( 'blueworx_public_sweep_foreign_assets', ! blueworx_public_page_needs_foreign_assets() ) ) {
		return;
	}

	$prefixes = blueworx_public_allowed_asset_prefixes();

	foreach ( array( wp_styles(), wp_scripts() ) as $collection ) {
		$is_styles = ( $collection instanceof WP_Styles );

		// Copied, because dequeueing rewrites the queue being walked.
		foreach ( (array) $collection->queue as $handle ) {
			$allowed = false;

			foreach ( $prefixes as $prefix ) {
				if ( 0 === strpos( (string) $handle, (string) $prefix ) ) {
					$allowed = true;
					break;
				}
			}

			if ( $allowed ) {
				continue;
			}

			if ( $is_styles ) {
				wp_dequeue_style( $handle );
			} else {
				wp_dequeue_script( $handle );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'blueworx_public_dequeue_foreign_assets', PHP_INT_MAX );
