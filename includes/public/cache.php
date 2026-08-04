<?php
/**
 * Public front-end layer — how long a page may be kept (#81).
 *
 * The server took between two and three and a half seconds to begin sending a
 * page, and the reason was in the response headers: the HTML carried no
 * Cache-Control at all. Static files were cached for a year; the pages
 * themselves were not cached by anything, anywhere, so every visitor waited for
 * WordPress to build the same marketing page from scratch. The site is fronted
 * by a Varnish cache that was being told nothing and therefore stored nothing.
 *
 * A marketing page is byte-identical for every logged-out visitor, so it is
 * exactly the thing a shared cache is for. That is the fix here: say so.
 *
 * The opposite matters more. A client's dashboard is not identical for anybody,
 * and a shared cache that stored one would serve one client another client's
 * invoices. Every page behind a sign-in — and every response to somebody who is
 * signed in, on any page — is marked private and no-store, explicitly, rather
 * than relying on the absence of a caching header to mean "do not cache". The
 * live site is proof that absence means whatever the cache in front decides:
 * the sign-in page, which WordPress marks no-cache, was being served from
 * Varnish four hours stale.
 *
 * What this file cannot do is make WordPress itself faster. It makes the answer
 * reusable, which is the difference between one visitor in a thousand waiting
 * for WordPress and all of them.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * How long a shared cache may keep a marketing page, in seconds.
 *
 * Ten minutes. Long enough that a burst of traffic hits the cache rather than
 * WordPress, short enough that a wording change is live before anyone thinks to
 * ask why it is not. Editing a page also purges nothing — ten minutes is the
 * longest anybody has to wait for their own edit to appear.
 *
 * @return int Seconds.
 */
function blueworx_public_cache_max_age() {
	return (int) apply_filters( 'blueworx_public_cache_max_age', 10 * MINUTE_IN_SECONDS );
}

/**
 * Whether this response may be stored by a cache shared between visitors.
 *
 * Deliberately a short list of things that must ALL be true, rather than a list
 * of exclusions. Anything unusual — a POST, a signed-in visitor, a page behind
 * the sign-in, a search, a preview, a comment — falls through to private by
 * default, which is the safe answer.
 *
 * @return bool True when the response is the same for everybody.
 */
function blueworx_public_response_is_shareable() {
	// Never in wp-admin, on a cron run, in the REST API or over AJAX.
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';

	if ( 'GET' !== $method && 'HEAD' !== $method ) {
		return false;
	}

	// A signed-in visitor sees the admin bar, and a client sees their own name.
	// Neither may ever be stored where somebody else can be handed it.
	if ( is_user_logged_in() ) {
		return false;
	}

	// Only pages this plugin renders. Anything else on the site belongs to
	// somebody else and may well be personal.
	if ( ! blueworx_public_renders_request() ) {
		return false;
	}

	// The client area and the sign-in pages, even signed out: a cached sign-in
	// page carries a stale nonce, which is how logins start failing at random.
	if ( function_exists( 'blueworx_public_is_private_page' ) && blueworx_public_is_private_page() ) {
		return false;
	}

	// A not-found page is cheap to build and its status is the point; caching
	// it at the edge mostly serves to keep a URL 404ing after it starts working.
	if ( is_404() || is_search() || is_preview() || post_password_required() ) {
		return false;
	}

	return (bool) apply_filters( 'blueworx_public_response_is_shareable', true );
}

/**
 * Sends the caching headers for the current response.
 *
 * On `template_redirect`, which is late enough that the query has run — so
 * "which page is this" and "is anybody signed in" both have real answers — and
 * early enough that nothing has been sent yet.
 *
 * @return void
 */
function blueworx_public_send_cache_headers() {
	if ( headers_sent() ) {
		return;
	}

	if ( ! blueworx_public_renders_request() && ! is_user_logged_in() ) {
		// Not ours and nobody is signed in: leave the site's own headers alone.
		return;
	}

	if ( ! blueworx_public_response_is_shareable() ) {
		// The important half. Said explicitly, because "no header" is not an
		// instruction — a cache in front is free to invent its own answer, and
		// on the live site it did.
		header( 'Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );

		return;
	}

	$max_age = blueworx_public_cache_max_age();

	// max-age=0 for the browser, s-maxage for the shared cache in front. A
	// visitor's own browser revalidates, so a change they make is theirs
	// immediately; the edge is what absorbs everybody else's traffic.
	//
	// stale-while-revalidate is what actually removes the wait: when the copy
	// expires, the next visitor is still handed it instantly and the refresh
	// happens behind them, so nobody sits through a rebuild.
	header(
		sprintf(
			'Cache-Control: public, max-age=0, s-maxage=%d, stale-while-revalidate=%d',
			$max_age,
			(int) apply_filters( 'blueworx_public_cache_stale_while_revalidate', DAY_IN_SECONDS )
		)
	);

	// Encoding only. NOT Vary: Cookie — it looks careful and is the opposite: a
	// shared cache would then key on every analytics cookie a visitor happens to
	// carry, and store a separate copy of the same page for each one. Signed-in
	// visitors never reach this branch at all, which is what makes that safe.
	header( 'Vary: Accept-Encoding' );

	// Nothing personal is in here, but say so anyway: some intermediaries treat
	// an unset Expires as a licence to guess.
	header_remove( 'Pragma' );
	header_remove( 'Expires' );
}
add_action( 'template_redirect', 'blueworx_public_send_cache_headers', 999 );

/**
 * Stops WordPress undoing the above on a page it thinks is private.
 *
 * WordPress sends nocache headers from several places, some of them after
 * template_redirect. Where this plugin has decided a response is shareable, its
 * answer is the one that stands; everywhere else core's headers are left
 * completely alone.
 *
 * @param array $headers Headers WordPress is about to send.
 * @return array Filtered headers.
 */
function blueworx_public_keep_cache_headers( $headers ) {
	if ( ! blueworx_public_response_is_shareable() ) {
		return $headers;
	}

	unset( $headers['Cache-Control'], $headers['Pragma'], $headers['Expires'] );

	return $headers;
}
add_filter( 'nocache_headers', 'blueworx_public_keep_cache_headers', 999 );
