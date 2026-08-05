<?php
/**
 * Public front-end layer — the client area's forms (#97, #98).
 *
 * The portal was read-only until now: four pages that show a client what we
 * already know about them. These are the first two places it accepts input, so
 * the handling is deliberately all in one file rather than spread through the
 * templates.
 *
 * Every handler follows the same shape, and none of it is optional:
 *
 * 1. Run on `template_redirect`, before a byte of the page is rendered, so a
 *    successful save can redirect rather than render a page whose content
 *    contradicts the form that was just submitted (and so a refresh does not
 *    re-submit it).
 * 2. Check the nonce AND the capability. The nonce proves the request came
 *    from our form; being logged in proves who is asking. Neither alone is
 *    enough, and a client area is exactly where a missing check is worst.
 * 3. Act only on the CURRENT user's own record. Nothing here takes a user ID
 *    from the request — the one shape of bug that turns "edit my details" into
 *    "edit anyone's details".
 * 4. Report back through a short-lived transient keyed to the user, so the
 *    outcome survives the redirect without putting anything in the URL.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores the outcome of a form submission for the page that follows it.
 *
 * A transient rather than a query parameter: a query parameter would survive
 * being shared or bookmarked, and would show a stale "Saved" to whoever opened
 * the link. Keyed by user so two people signed in at once cannot see each
 * other's notice, and expires in a minute because it is only ever meant to
 * survive one redirect.
 *
 * @param string $type    'success' or 'error'.
 * @param string $message Human-readable message.
 * @return void
 */
function blueworx_account_set_notice( $type, $message ) {
	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return;
	}

	set_transient(
		'blueworx_account_notice_' . $user_id,
		array(
			'type'    => 'error' === $type ? 'error' : 'success',
			'message' => (string) $message,
		),
		MINUTE_IN_SECONDS
	);
}

/**
 * Reads and clears the pending notice for the current user.
 *
 * Cleared on read so a notice is shown exactly once — a "Saved" that reappears
 * on every later visit to the page is worse than none, because it stops meaning
 * anything.
 *
 * @return array|null array( type, message ), or null.
 */
function blueworx_account_take_notice() {
	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return null;
	}

	$key    = 'blueworx_account_notice_' . $user_id;
	$notice = get_transient( $key );

	if ( ! is_array( $notice ) || ! isset( $notice['message'] ) ) {
		return null;
	}

	delete_transient( $key );

	return $notice;
}

/**
 * Whether a client-area form submission is genuine.
 *
 * @param string $action Nonce action.
 * @param string $field  Nonce field name.
 * @return bool True when this request may be acted on.
 */
function blueworx_account_verify_request( $action, $field ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// A client edits their own record, so `read` is the right capability — the
	// bar is "is a real, logged-in user of this site", not "can administer it".
	// The ownership guarantee comes from only ever writing to
	// get_current_user_id(), never to an ID from the request.
	if ( ! current_user_can( 'read' ) ) {
		return false;
	}

	$nonce = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';

	return (bool) wp_verify_nonce( $nonce, $action );
}

/**
 * Saves the client's own name, company and email (#97).
 *
 * @return void
 */
function blueworx_account_handle_details() {
	if ( ! isset( $_POST['blueworx_account_form'] ) || 'details' !== $_POST['blueworx_account_form'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is verified immediately below, on the same request.
		return;
	}

	if ( ! blueworx_account_verify_request( 'blueworx_account_details', 'blueworx_account_details_nonce' ) ) {
		blueworx_account_set_notice( 'error', __( 'That form had expired. Please try again.', 'bluegroup-project-blueworx' ) );
		blueworx_account_redirect( 'details' );
	}

	$user_id = get_current_user_id();

	$first   = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last    = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	$company = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( '' === $email || ! is_email( $email ) ) {
		blueworx_account_set_notice( 'error', __( 'That email address does not look right. Nothing was changed.', 'bluegroup-project-blueworx' ) );
		blueworx_account_redirect( 'details' );
	}

	// An email already on another account cannot be taken: WordPress uses it to
	// identify a person at sign-in and for password resets, so two accounts
	// sharing one address is an account somebody can be locked out of.
	$existing = email_exists( $email );

	if ( $existing && (int) $existing !== $user_id ) {
		blueworx_account_set_notice( 'error', __( 'That email address is already used by another account.', 'bluegroup-project-blueworx' ) );
		blueworx_account_redirect( 'details' );
	}

	$display = trim( $first . ' ' . $last );

	$result = wp_update_user(
		array(
			'ID'         => $user_id,
			'first_name' => $first,
			'last_name'  => $last,
			'user_email' => $email,
			// Only when they have actually given a name. Writing an empty
			// display_name would leave the portal greeting nobody.
			'display_name' => '' === $display ? wp_get_current_user()->display_name : $display,
		)
	);

	if ( is_wp_error( $result ) ) {
		blueworx_account_set_notice( 'error', __( 'We could not save that. Please try again, or tell us and we will do it for you.', 'bluegroup-project-blueworx' ) );
		blueworx_account_redirect( 'details' );
	}

	// billing_company is the key SureCart and WooCommerce both read, so a
	// company saved here is the one that appears on an invoice rather than a
	// second, private copy that agrees with nothing.
	update_user_meta( $user_id, 'billing_company', $company );

	blueworx_account_set_notice( 'success', __( 'Your details have been saved.', 'bluegroup-project-blueworx' ) );
	blueworx_account_redirect( 'details' );
}

/**
 * Changes the client's own password (#97).
 *
 * @return void
 */
function blueworx_account_handle_password() {
	if ( ! isset( $_POST['blueworx_account_form'] ) || 'password' !== $_POST['blueworx_account_form'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is verified immediately below, on the same request.
		return;
	}

	if ( ! blueworx_account_verify_request( 'blueworx_account_password', 'blueworx_account_password_nonce' ) ) {
		blueworx_account_set_notice( 'error', __( 'That form had expired. Please try again.', 'bluegroup-project-blueworx' ) );
		blueworx_account_redirect( 'details' );
	}

	$user = wp_get_current_user();

	// Raw, deliberately: a password is not text to be sanitized. Stripping
	// characters from it before checking would reject the correct password of
	// anyone whose password contains them.
	$current = isset( $_POST['current_password'] ) ? (string) wp_unslash( $_POST['current_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- a password is verified against the stored hash, never sanitized.
	$new     = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- a password is hashed, never sanitized.

	// The current password is required even though the client is already
	// signed in. Without it, an unattended logged-in browser is a full account
	// takeover rather than a nuisance.
	if ( '' === $current || ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
		blueworx_account_set_notice( 'error', __( 'Your current password was not right. Your password has not been changed.', 'bluegroup-project-blueworx' ) );
		blueworx_account_redirect( 'details' );
	}

	if ( strlen( $new ) < 8 ) {
		blueworx_account_set_notice( 'error', __( 'Your new password needs to be at least 8 characters. Your password has not been changed.', 'bluegroup-project-blueworx' ) );
		blueworx_account_redirect( 'details' );
	}

	wp_set_password( $new, $user->ID );

	// wp_set_password() destroys every session, including this one, so without
	// this the client is silently signed out by succeeding. Signing the other
	// sessions out is the point; signing this one out is not.
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, false );

	blueworx_account_set_notice( 'success', __( 'Your password has been changed. Anywhere else you were signed in has been signed out.', 'bluegroup-project-blueworx' ) );
	blueworx_account_redirect( 'details' );
}

/**
 * Sends a support request (#98).
 *
 * There is no ticket system behind this, and this does not pretend there is:
 * the request is emailed to the site's support address and the client is told
 * plainly that a person will reply by email. Inventing a reference number for a
 * queue that does not exist would be the worst possible version of this page.
 *
 * @return void
 */
function blueworx_account_handle_support() {
	if ( ! isset( $_POST['blueworx_account_form'] ) || 'support' !== $_POST['blueworx_account_form'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is verified immediately below, on the same request.
		return;
	}

	if ( ! blueworx_account_verify_request( 'blueworx_account_support', 'blueworx_account_support_nonce' ) ) {
		blueworx_account_set_notice( 'error', __( 'That form had expired. Please try again.', 'bluegroup-project-blueworx' ) );
		blueworx_account_redirect( 'support' );
	}

	$user    = wp_get_current_user();
	$types   = blueworx_account_support_types();
	$type    = isset( $_POST['request_type'] ) ? sanitize_key( wp_unslash( $_POST['request_type'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	// An unknown type is not trusted into the subject line, so it falls back to
	// the general one rather than being echoed.
	if ( ! isset( $types[ $type ] ) ) {
		$type = 'general';
	}

	if ( '' === trim( $message ) ) {
		blueworx_account_set_notice( 'error', __( 'Please tell us what is happening before sending.', 'bluegroup-project-blueworx' ) );
		blueworx_account_redirect( 'support' );
	}

	$sent = wp_mail(
		blueworx_account_support_email(),
		sprintf(
			/* translators: 1: request type, 2: client name. */
			__( '[Portal] %1$s — %2$s', 'bluegroup-project-blueworx' ),
			$types[ $type ]['label'],
			'' === $user->display_name ? $user->user_email : $user->display_name
		),
		implode(
			"\n",
			array(
				sprintf(
					/* translators: 1: client name, 2: client email. */
					__( 'From: %1$s (%2$s)', 'bluegroup-project-blueworx' ),
					$user->display_name,
					$user->user_email
				),
				sprintf(
					/* translators: %s: request type. */
					__( 'Type: %s', 'bluegroup-project-blueworx' ),
					$types[ $type ]['label']
				),
				'',
				$message,
			)
		),
		array( 'Reply-To: ' . $user->user_email )
	);

	if ( ! $sent ) {
		// Said plainly, with the address, so the client can simply email us
		// instead. A silent failure here is somebody sitting waiting for a
		// reply to a message nobody received.
		blueworx_account_set_notice(
			'error',
			sprintf(
				/* translators: %s: support email address. */
				__( 'We could not send that just now. Please email us directly at %s and we will pick it up.', 'bluegroup-project-blueworx' ),
				blueworx_account_support_email()
			)
		);
		blueworx_account_redirect( 'support' );
	}

	blueworx_account_set_notice( 'success', __( 'Thanks — we have your request and will reply by email.', 'bluegroup-project-blueworx' ) );
	blueworx_account_redirect( 'support' );
}

/**
 * Redirects back to a client-area section and stops.
 *
 * Always a redirect after a successful POST, so a refresh cannot repeat the
 * action — and after a failed one too, so the notice is shown by the same code
 * path in both cases rather than two that can drift apart.
 *
 * @param string $section Section slug.
 * @return void
 */
function blueworx_account_redirect( $section ) {
	wp_safe_redirect( blueworx_account_url( $section ), 303 );
	exit;
}

/**
 * Where support requests are sent.
 *
 * Defaults to the site's own admin address, which is the one address a
 * WordPress install always has. Filterable and overridable by option so it can
 * be pointed at a shared inbox without editing the plugin.
 *
 * @return string Email address.
 */
function blueworx_account_support_email() {
	$configured = trim( (string) get_option( 'blueworx_support_email', '' ) );
	$email      = is_email( $configured ) ? $configured : (string) get_option( 'admin_email' );

	/**
	 * Filters the support inbox address.
	 *
	 * @param string $email Email address.
	 */
	return (string) apply_filters( 'blueworx_support_email', $email );
}

/**
 * The kinds of request the form offers (#98).
 *
 * The two urgent ones are named separately from "everything else" because they
 * are the two that should not wait in an inbox — the copy beside each says so
 * and gives the faster route.
 *
 * @return array Key => array( label, hint ).
 */
function blueworx_account_support_types() {
	return (array) apply_filters(
		'blueworx_account_support_types',
		array(
			'down'     => array(
				'label' => __( 'Site down or unreachable', 'bluegroup-project-blueworx' ),
				'hint'  => __( 'Tell us the address and what you see. If it is urgent, call rather than wait for email.', 'bluegroup-project-blueworx' ),
			),
			'payments' => array(
				'label' => __( 'Payments or checkout failing', 'bluegroup-project-blueworx' ),
				'hint'  => __( 'Include the order or invoice number if you have one, and what the customer saw.', 'bluegroup-project-blueworx' ),
			),
			'general'  => array(
				'label' => __( 'Everything else', 'bluegroup-project-blueworx' ),
				'hint'  => __( 'Changes, questions, or something that looks wrong but is not stopping business.', 'bluegroup-project-blueworx' ),
			),
		)
	);
}

/**
 * Runs the client-area form handlers before anything is rendered.
 *
 * Priority 2, after blueworx_account_require_login() at 1 — so a logged-out
 * POST is bounced to the sign-in page by the gate rather than reaching a
 * handler that would refuse it anyway.
 *
 * @return void
 */
function blueworx_account_handle_forms() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '' ) ) {
		return;
	}

	if ( ! blueworx_account_is_account_request() ) {
		return;
	}

	blueworx_account_handle_details();
	blueworx_account_handle_password();
	blueworx_account_handle_support();
}
add_action( 'template_redirect', 'blueworx_account_handle_forms', 2 );
