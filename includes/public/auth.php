<?php
/**
 * Public front-end layer — signing in, signing up, and password resets.
 *
 * Login and registration were SureDash's `/portal-login` and `/portal-register`
 * (#43). This file replaces them with three pages the plugin renders itself, so
 * a client can join, sign in and recover an account without ever landing on
 * wp-login.php or on a page belonging to a plugin we are removing.
 *
 * **Nothing here reimplements authentication.** Every actual security decision
 * is made by WordPress core: wp_signon() checks the password, wp_create_user()
 * hashes the new one, retrieve_password() issues the reset key, and
 * check_password_reset_key() validates it. Core's own hooks still fire, so a
 * security plugin that rate-limits logins keeps working. What this file adds is
 * the pages, the nonces, the redirects and the wording — the parts that are
 * genuinely site-specific.
 *
 * Two behaviours are deliberate and worth not "fixing" later:
 *
 * - **Failures are vague on purpose.** A wrong password and an unknown address
 *   produce the same message, and a reset request says the same thing whether
 *   or not the address is on file. Anything more helpful is an endpoint for
 *   working out who has an account here.
 * - **Registration follows WordPress's own membership setting.** Turning it on
 *   is a deliberate act by an administrator in Settings → General, not
 *   something this plugin quietly decides on a live commercial site.
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
 * @return array Slug => array( title, template ).
 */
function blueworx_auth_pages() {
	return array(
		'login'          => array(
			'title'    => __( 'Sign in', 'bluegroup-project-blueworx' ),
			'template' => 'pages/login.php',
		),
		'register'       => array(
			'title'    => __( 'Create an account', 'bluegroup-project-blueworx' ),
			'template' => 'pages/register.php',
		),
		'reset-password' => array(
			'title'    => __( 'Reset your password', 'bluegroup-project-blueworx' ),
			'template' => 'pages/reset-password.php',
		),
	);
}

/**
 * Registers the auth pages.
 *
 * Note what is NOT set here: the `account` flag. These pages are the way in to
 * the client area, so gating them behind being signed in would lock every
 * client out of the site permanently.
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
 * Where signing in should land somebody.
 *
 * A redirect target is only honoured when it stays on this site.
 * wp_validate_redirect() is what enforces that; without it, `?redirect_to=` on
 * a login page is an open redirect with a trustworthy-looking address in front
 * of it, which is the classic phishing setup.
 *
 * @return string Absolute URL.
 */
function blueworx_auth_redirect_target() {
	$requested = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only; the value is validated below and every state change carries its own nonce.

	if ( '' !== $requested ) {
		$safe = wp_validate_redirect( $requested, '' );

		if ( '' !== $safe ) {
			return $safe;
		}
	}

	return blueworx_account_url();
}

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
 * One list so the wording is reviewable in one place — including the two
 * deliberately unhelpful ones.
 *
 * @return array Code => array( type, text ).
 */
function blueworx_auth_messages() {
	return array(
		// Deliberately identical for a wrong password and an unknown address.
		'bad-credentials'  => array(
			'type' => 'error',
			'text' => __( 'That email address and password do not match. Please try again.', 'bluegroup-project-blueworx' ),
		),
		'empty-fields'     => array(
			'type' => 'error',
			'text' => __( 'Please fill in both fields.', 'bluegroup-project-blueworx' ),
		),
		'expired'          => array(
			'type' => 'error',
			'text' => __( 'That did not go through — please try again.', 'bluegroup-project-blueworx' ),
		),
		'signed-out'       => array(
			'type' => 'ok',
			'text' => __( 'You are signed out.', 'bluegroup-project-blueworx' ),
		),
		'registered'       => array(
			'type' => 'ok',
			'text' => __( 'Your account is ready.', 'bluegroup-project-blueworx' ),
		),
		'email-taken'      => array(
			'type' => 'error',
			'text' => __( 'There is already an account with that email address. Try signing in, or reset your password.', 'bluegroup-project-blueworx' ),
		),
		'email-invalid'    => array(
			'type' => 'error',
			'text' => __( 'That does not look like an email address.', 'bluegroup-project-blueworx' ),
		),
		'password-short'   => array(
			'type' => 'error',
			'text' => __( 'Please choose a password of at least 12 characters.', 'bluegroup-project-blueworx' ),
		),
		'registration-off' => array(
			'type' => 'error',
			'text' => __( 'New accounts are not open at the moment. Get in touch and we will set one up for you.', 'bluegroup-project-blueworx' ),
		),
		// Says the same thing whether or not the address is on file.
		'reset-sent'       => array(
			'type' => 'ok',
			'text' => __( 'If that address has an account, a link to set a new password is on its way.', 'bluegroup-project-blueworx' ),
		),
		'reset-bad-key'    => array(
			'type' => 'error',
			'text' => __( 'That password link has expired or has already been used. Please request a new one.', 'bluegroup-project-blueworx' ),
		),
		'reset-done'       => array(
			'type' => 'ok',
			'text' => __( 'Your password is changed. You can sign in with it now.', 'bluegroup-project-blueworx' ),
		),
	);
}

/**
 * Whether new clients may create an account.
 *
 * Follows WordPress's own membership setting rather than introducing a second
 * switch: an administrator turning on registration should have to mean it, in
 * the place they would look for it.
 *
 * @return bool
 */
function blueworx_auth_registration_open() {
	return (bool) get_option( 'users_can_register', false );
}

/**
 * Sends an already-signed-in visitor away from the sign-in and sign-up pages.
 *
 * @return void
 */
function blueworx_auth_redirect_signed_in() {
	if ( ! is_user_logged_in() || ! blueworx_auth_is_auth_request() ) {
		return;
	}

	// The reset page is exempt: somebody signed in on one device who followed a
	// reset link from their email still needs to be able to set the password.
	$current = blueworx_public_current_page();

	if ( isset( $current['template'] ) && 'pages/reset-password.php' === $current['template'] ) {
		return;
	}

	wp_safe_redirect( blueworx_auth_redirect_target(), 302 );
	exit;
}
add_action( 'template_redirect', 'blueworx_auth_redirect_signed_in', 2 );

/**
 * Handles a sign-in submission.
 *
 * @return void
 */
function blueworx_auth_handle_login() {
	if ( ! isset( $_POST['blueworx_auth_action'] ) || 'login' !== $_POST['blueworx_auth_action'] ) {
		return;
	}

	if ( ! isset( $_POST['blueworx_auth_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['blueworx_auth_nonce'] ) ), 'blueworx_auth_login' ) ) {
		wp_safe_redirect( blueworx_auth_notice_url( 'login', 'expired' ), 302 );
		exit;
	}

	$email    = isset( $_POST['blueworx_email'] ) ? sanitize_text_field( wp_unslash( $_POST['blueworx_email'] ) ) : '';
	$password = isset( $_POST['blueworx_password'] ) ? (string) wp_unslash( $_POST['blueworx_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- a password is checked, never stored or printed; sanitising it would silently change what the user typed.
	$target   = blueworx_auth_redirect_target();

	if ( '' === $email || '' === $password ) {
		wp_safe_redirect( blueworx_auth_notice_url( 'login', 'empty-fields', array( 'redirect_to' => $target ) ), 302 );
		exit;
	}

	$user = wp_signon(
		array(
			'user_login'    => $email,
			'user_password' => $password,
			'remember'      => ! empty( $_POST['blueworx_remember'] ),
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {
		// Core's error tells you which half was wrong. That is useful in
		// wp-admin and a disclosure on a public page, so it is discarded.
		wp_safe_redirect( blueworx_auth_notice_url( 'login', 'bad-credentials', array( 'redirect_to' => $target ) ), 302 );
		exit;
	}

	wp_set_current_user( $user->ID );
	wp_safe_redirect( $target, 302 );
	exit;
}
add_action( 'template_redirect', 'blueworx_auth_handle_login', 0 );

/**
 * Handles a registration submission.
 *
 * @return void
 */
function blueworx_auth_handle_register() {
	if ( ! isset( $_POST['blueworx_auth_action'] ) || 'register' !== $_POST['blueworx_auth_action'] ) {
		return;
	}

	if ( ! isset( $_POST['blueworx_auth_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['blueworx_auth_nonce'] ) ), 'blueworx_auth_register' ) ) {
		wp_safe_redirect( blueworx_auth_notice_url( 'register', 'expired' ), 302 );
		exit;
	}

	// Checked again here, not only when drawing the form: the form is HTML and
	// a form is not a control.
	if ( ! blueworx_auth_registration_open() ) {
		wp_safe_redirect( blueworx_auth_notice_url( 'register', 'registration-off' ), 302 );
		exit;
	}

	$email    = isset( $_POST['blueworx_email'] ) ? sanitize_email( wp_unslash( $_POST['blueworx_email'] ) ) : '';
	$name     = isset( $_POST['blueworx_name'] ) ? sanitize_text_field( wp_unslash( $_POST['blueworx_name'] ) ) : '';
	$password = isset( $_POST['blueworx_password'] ) ? (string) wp_unslash( $_POST['blueworx_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- hashed by wp_create_user(), never stored or printed as given.

	if ( '' === $email || ! is_email( $email ) ) {
		wp_safe_redirect( blueworx_auth_notice_url( 'register', 'email-invalid' ), 302 );
		exit;
	}

	// Long enough that a guessing attack is not the weak point. WordPress has
	// no server-side minimum of its own, so this is the site's.
	if ( strlen( $password ) < 12 ) {
		wp_safe_redirect( blueworx_auth_notice_url( 'register', 'password-short' ), 302 );
		exit;
	}

	if ( email_exists( $email ) ) {
		// This does tell an attacker the address is registered. It is accepted
		// here because the alternative — silently not creating the account and
		// claiming success — leaves a real client stuck with no way forward.
		wp_safe_redirect( blueworx_auth_notice_url( 'register', 'email-taken' ), 302 );
		exit;
	}

	$user_id = wp_create_user( $email, $password, $email );

	if ( is_wp_error( $user_id ) ) {
		wp_safe_redirect( blueworx_auth_notice_url( 'register', 'expired' ), 302 );
		exit;
	}

	if ( '' !== $name ) {
		wp_update_user(
			array(
				'ID'           => $user_id,
				'first_name'   => $name,
				'display_name' => $name,
			)
		);
	}

	// Core's own welcome email, so a site that customises it keeps its wording.
	wp_new_user_notification( $user_id, null, 'user' );

	wp_signon(
		array(
			'user_login'    => $email,
			'user_password' => $password,
			'remember'      => true,
		),
		is_ssl()
	);

	wp_safe_redirect( add_query_arg( 'notice', 'registered', blueworx_auth_redirect_target() ), 302 );
	exit;
}
add_action( 'template_redirect', 'blueworx_auth_handle_register', 0 );

/**
 * Handles both halves of a password reset: asking for a link, and using one.
 *
 * @return void
 */
function blueworx_auth_handle_reset() {
	if ( ! isset( $_POST['blueworx_auth_action'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_POST['blueworx_auth_action'] ) );

	if ( 'reset-request' !== $action && 'reset-set' !== $action ) {
		return;
	}

	if ( ! isset( $_POST['blueworx_auth_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['blueworx_auth_nonce'] ) ), 'blueworx_auth_' . $action ) ) {
		wp_safe_redirect( blueworx_auth_notice_url( 'reset-password', 'expired' ), 302 );
		exit;
	}

	if ( 'reset-request' === $action ) {
		$email = isset( $_POST['blueworx_email'] ) ? sanitize_text_field( wp_unslash( $_POST['blueworx_email'] ) ) : '';

		if ( '' !== $email ) {
			// The return value is deliberately ignored. retrieve_password()
			// reports "no such user", and passing that on turns this page into
			// a way to test whether an address has an account here.
			retrieve_password( $email );
		}

		wp_safe_redirect( blueworx_auth_notice_url( 'reset-password', 'reset-sent' ), 302 );
		exit;
	}

	$login    = isset( $_POST['blueworx_login'] ) ? sanitize_text_field( wp_unslash( $_POST['blueworx_login'] ) ) : '';
	$key      = isset( $_POST['blueworx_key'] ) ? sanitize_text_field( wp_unslash( $_POST['blueworx_key'] ) ) : '';
	$password = isset( $_POST['blueworx_password'] ) ? (string) wp_unslash( $_POST['blueworx_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- hashed by reset_password().

	$user = check_password_reset_key( $key, $login );

	if ( is_wp_error( $user ) ) {
		wp_safe_redirect( blueworx_auth_notice_url( 'reset-password', 'reset-bad-key' ), 302 );
		exit;
	}

	if ( strlen( $password ) < 12 ) {
		wp_safe_redirect(
			blueworx_auth_notice_url(
				'reset-password',
				'password-short',
				array(
					'login' => $login,
					'key'   => $key,
				)
			),
			302
		);
		exit;
	}

	reset_password( $user, $password );

	wp_safe_redirect( blueworx_auth_notice_url( 'login', 'reset-done' ), 302 );
	exit;
}
add_action( 'template_redirect', 'blueworx_auth_handle_reset', 0 );

/**
 * Points the password-reset email at this site's own page.
 *
 * Without this, core's email sends a client to wp-login.php — the screen this
 * whole file exists to keep them off, and the one that looks least like the
 * site they signed up to.
 *
 * @param string $message The email body.
 * @param string $key     The reset key.
 * @param string $login   The user login.
 * @return string
 */
function blueworx_auth_reset_email( $message, $key, $login ) {
	$ours = add_query_arg(
		array(
			'key'   => rawurlencode( $key ),
			'login' => rawurlencode( $login ),
		),
		blueworx_auth_url( 'reset-password' )
	);

	// Replace core's link rather than rewriting the whole email, so a site that
	// customises the wording keeps it.
	return preg_replace( '#<?' . preg_quote( network_site_url( 'wp-login.php' ), '#' ) . '[^\s>]*>?#', $ours, $message );
}
add_filter( 'retrieve_password_message', 'blueworx_auth_reset_email', 10, 3 );

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
