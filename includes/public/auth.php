<?php
/**
 * Public front-end layer — signing in.
 *
 * Login and registration were SureDash's `/portal-login` and `/portal-register`
 * (#43). They became three pages of our own — sign in, create an account, reset
 * your password — and those have now become one: the shop's `<sc-login-form>`
 * on the site's own /login page. See templates/pages/login.php.
 *
 * **Nothing here authenticates anybody, and nothing here ever did.** Signing in
 * is the shop's REST route, which is `wp_authenticate()` underneath, so core's
 * hooks still fire and a security plugin that rate-limits logins keeps working.
 * Its form also covers the two screens we have stopped shipping: a forgotten
 * password and the email code that follows it.
 *
 * Keeping our own sign-in beside it meant maintaining a second front door onto
 * the same house — a second set of nonces, a second set of deliberately vague
 * failure messages, a second reset email to keep pointing at the right screen.
 * The ClubHouse plugin made the same swap for the same reason.
 *
 * What is left is the two things the shop cannot answer:
 *
 * - **Where somebody lands once it has signed them in.** Carried on the shop's
 *   own `sc_login_redirect_url` filter, since its form is a web component and
 *   there is no field of ours to put in it. An administrator goes to wp-admin;
 *   a client goes to their dashboard.
 * - **Where somebody lands when they sign out**, because the link is ours and
 *   it is on every dashboard page.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The auth pages, keyed by slug.
 *
 * One page now. /register and /reset-password were retired with the forms they
 * carried — includes/public/upgrade.php trashes them and
 * includes/public/redirects.php sends both addresses here.
 *
 * @return array Slug => array( title, template ).
 */
function blueworx_auth_pages() {
	return array(
		'login' => array(
			'title'    => __( 'Sign in', 'bluegroup-project-blueworx' ),
			'template' => 'pages/login.php',
		),
	);
}

/**
 * Registers the auth pages.
 *
 * Note what is NOT set here: the `account` flag. This page is the way in to the
 * client area, so gating it behind being signed in would lock every client out
 * of the site permanently.
 *
 * @param array $pages Pages from blueworx_public_pages().
 * @return array
 */
function blueworx_auth_register_pages( $pages ) {
	foreach ( blueworx_auth_pages() as $slug => $page ) {
		$pages[ $slug ] = array(
			'title'    => $page['title'],
			'template' => $page['template'],
			'auth'     => true,
		);
	}

	return $pages;
}
add_filter( 'blueworx_public_pages', 'blueworx_auth_register_pages' );

/**
 * The URL of an auth page.
 *
 * @param string $slug One of the blueworx_auth_pages() keys.
 * @return string Absolute URL.
 */
function blueworx_auth_url( $slug ) {
	return home_url( '/' . trim( $slug, '/' ) );
}

/**
 * Whether the current request is for one of the auth pages.
 *
 * @return bool
 */
function blueworx_auth_is_auth_request() {
	if ( ! function_exists( 'blueworx_public_current_page' ) ) {
		return false;
	}

	$current = blueworx_public_current_page();

	return is_array( $current ) && ! empty( $current['auth'] );
}

/**
 * Whether the shop is here to bring its sign-in form to life.
 *
 * Tested by the handle its front-end bundle registers rather than by a class or
 * a constant, because the handle is the thing that actually has to exist: the
 * form is a web component, and markup without that script is a form that never
 * comes alive. A shop that renames the handle, or no shop at all, is then the
 * same answer — no, so say where to sign in instead of drawing a dead form.
 *
 * @return bool
 */
function blueworx_auth_shop_form_available() {
	return function_exists( 'wp_script_is' ) && wp_script_is( 'surecart-components', 'registered' );
}

/**
 * Where signing in should land somebody.
 *
 * A redirect target is only honoured when it stays on this site.
 * wp_validate_redirect() is what enforces that; without it, `?redirect_to=` on
 * a login page is an open redirect with a trustworthy-looking address in front
 * of it, which is the classic phishing setup. The shop validates its own
 * `redirect_to` as well — this does not lean on that, because the value can
 * also arrive on one of our own links.
 *
 * @param string $requested Optional. A target to consider before the defaults.
 * @return string Absolute URL.
 */
function blueworx_auth_redirect_target( $requested = null ) {
	if ( null === $requested ) {
		$requested = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, and validated below.
	}

	$requested = is_string( $requested ) ? $requested : '';

	if ( '' !== $requested ) {
		$safe = wp_validate_redirect( $requested, '' );

		if ( '' !== $safe ) {
			return $safe;
		}
	}

	return blueworx_auth_home_for_current_user();
}

/**
 * Where somebody with no destination of their own belongs.
 *
 * The dashboard, whoever they are. Signing in at /login is signing in as a
 * member, and an administrator who wants wp-admin goes there directly — being
 * dropped into it because of a role is a surprise, not a shortcut.
 *
 * @return string Absolute URL.
 */
function blueworx_auth_home_for_current_user() {
	return blueworx_account_url();
}

/**
 * Where the shop should send somebody it has just signed in.
 *
 * The shop offers whatever `redirect_to` was on the address, already validated,
 * or null when it has no opinion. Either way the answer is ours: validated
 * again, and otherwise the right home for who they turned out to be.
 *
 * This is the only hook that carries it. The form is a web component posting to
 * a REST route, so there is no field of ours to add to it.
 *
 * @param string|null $theirs The shop's own answer.
 * @return string Absolute URL.
 */
function blueworx_auth_login_redirect( $theirs ) {
	return blueworx_auth_redirect_target( is_string( $theirs ) ? $theirs : '' );
}
add_filter( 'sc_login_redirect_url', 'blueworx_auth_login_redirect', 10, 1 );

/**
 * Adds a notice code to an auth page URL.
 *
 * Codes travel, never messages: a page that prints an arbitrary string from the
 * query string is a way to put convincing text on a real login page.
 *
 * @param string $slug   Auth page slug.
 * @param string $code   Notice code.
 * @param array  $extra  Optional extra query args to preserve.
 * @return string Absolute URL.
 */
function blueworx_auth_notice_url( $slug, $code, $extra = array() ) {
	return add_query_arg(
		array_merge( array( 'notice' => $code ), $extra ),
		blueworx_auth_url( $slug )
	);
}

/**
 * The notice code on the current request, if it is one this file knows.
 *
 * @return string Code, or ''.
 */
function blueworx_auth_notice() {
	$code = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only.

	return array_key_exists( $code, blueworx_auth_messages() ) ? $code : '';
}

/**
 * Every notice this file can show, and what it says.
 *
 * A short list now: the shop's form reports its own failures inside itself, so
 * the only thing left to say on arrival is what happened just before it.
 *
 * @return array Code => array( type, text ).
 */
function blueworx_auth_messages() {
	return array(
		'signed-out' => array(
			'type' => 'ok',
			'text' => __( 'You are signed out.', 'bluegroup-project-blueworx' ),
		),
	);
}

/**
 * Sends an already-signed-in visitor away from the sign-in page.
 *
 * @return void
 */
function blueworx_auth_redirect_signed_in() {
	if ( ! is_user_logged_in() || ! blueworx_auth_is_auth_request() ) {
		return;
	}

	wp_safe_redirect( blueworx_auth_redirect_target(), 302 );
	exit;
}
add_action( 'template_redirect', 'blueworx_auth_redirect_signed_in', 2 );

/**
 * Sends a client signing out back to the site rather than to wp-login.php.
 *
 * @return void
 */
function blueworx_auth_logout_redirect() {
	// Only for clients. An administrator signing out of wp-admin should keep
	// landing where WordPress puts them.
	if ( current_user_can( 'edit_posts' ) ) {
		return;
	}

	wp_safe_redirect( blueworx_auth_notice_url( 'login', 'signed-out' ), 302 );
	exit;
}
add_action( 'wp_logout', 'blueworx_auth_logout_redirect' );
