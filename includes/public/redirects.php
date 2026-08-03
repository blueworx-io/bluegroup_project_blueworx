<?php
/**
 * Public front-end layer — legacy URL redirects.
 *
 * Four pages were removed from the live site (#22, #23, #24, #25): /shop, which
 * rendered the marketing home page for a site that sells nothing from a
 * storefront; /about-us and /features, which were built in Elementor and
 * duplicated pages this plugin already owns; and /test-page, which was never
 * meant to be published at all.
 *
 * All four were published and indexable, so deleting them without a redirect
 * turns every inbound link, bookmark and search result into a 404. The deletion
 * is a one-off action in wp-admin; the redirect has to keep working forever, on
 * every environment, which is why it belongs here rather than in the live site's
 * database or a host-level rule nobody can review.
 *
 * These fire whether or not a page still exists at the path, so the redirect can
 * ship before the pages are deleted and behave identically afterwards.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retired paths, mapped to where each one should now send a visitor.
 *
 * Keys and values are both site-root-relative paths without a leading or
 * trailing slash ('' meaning the site root). Targets were chosen to preserve
 * intent rather than to dump everything on the home page: someone who followed
 * a link to /shop wanted to buy something, so Pricing is the honest destination.
 *
 * @return array Retired path => replacement path.
 */
function blueworx_public_legacy_redirects() {
	return (array) apply_filters(
		'blueworx_public_legacy_redirects',
		array(
			'shop'      => 'pricing',
			'about-us'  => 'about',
			'features'  => 'toolbox',
			'test-page' => '',
		)
	);
}

/**
 * Sends a 301 for a retired path.
 *
 * Hooked on `template_redirect` at priority 0 for two reasons. It is the
 * earliest front-end-only hook — it never runs for wp-admin, REST, cron or
 * AJAX, so no path matching has to guess at those. And priority 0 puts it ahead
 * of core's redirect_canonical() (priority 10), which would otherwise get first
 * refusal on a path that no longer resolves to a post and could send the
 * visitor somewhere of its own choosing before this ever ran.
 *
 * The redirect is deliberately NOT conditional on the page being absent. While
 * the pages still exist on the live site this overrides them, which is what
 * makes it safe to ship the redirect before someone deletes them by hand.
 *
 * @return void
 */
function blueworx_public_redirect_legacy_paths() {
	// A redirect answers a request for a document. Anything else — a form post
	// to a retired URL, say — should fall through and 404 rather than be
	// silently turned into a GET of a different page.
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';

	if ( 'GET' !== $method && 'HEAD' !== $method ) {
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$request_uri = sanitize_text_field( $request_uri );

	$path = strtolower( (string) wp_parse_url( $request_uri, PHP_URL_PATH ) );

	// Strip the subdirectory WordPress is installed under, if any, so the
	// comparison below is always against a site-root-relative path.
	$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home_path = trim( strtolower( $home_path ), '/' );
	$path      = trim( $path, '/' );

	if ( '' !== $home_path ) {
		if ( $path !== $home_path && 0 !== strpos( $path, $home_path . '/' ) ) {
			return;
		}

		$path = trim( substr( $path, strlen( $home_path ) ), '/' );
	}

	$redirects = blueworx_public_legacy_redirects();

	// An exact match only. A prefix match would take out any child page that
	// ever lived under one of these paths, and would make /features-of-x a
	// redirect too.
	if ( ! isset( $redirects[ $path ] ) ) {
		return;
	}

	$target = home_url( '/' . ltrim( (string) $redirects[ $path ], '/' ) );

	// Campaign traffic reaches these URLs with tracking parameters attached.
	// Dropping them breaks attribution for every campaign that ever pointed at
	// the old URL, so they are carried across to the new one.
	$query_string = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';

	if ( '' !== $query_string ) {
		$target .= ( false === strpos( $target, '?' ) ? '?' : '&' ) . $query_string;
	}

	// A redirect to the path being requested is an infinite loop. This can only
	// happen if the filter above is given a map that points a path at itself,
	// but the failure mode is bad enough to be worth one comparison.
	if ( untrailingslashit( $target ) === untrailingslashit( home_url( '/' . $path ) ) ) {
		return;
	}

	// 301: these pages are gone for good, and the permanence is the point —
	// it is what moves the search ranking to the replacement page rather than
	// leaving both URLs competing.
	wp_safe_redirect( $target, 301 );
	exit;
}
add_action( 'template_redirect', 'blueworx_public_redirect_legacy_paths', 0 );
