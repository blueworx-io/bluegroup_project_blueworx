<?php
/**
 * Public front-end layer — the Contact page's enquiry form.
 *
 * The contact page used to hand its form column to a third-party shortcode
 * (still honoured when one is configured — see templates/pages/contact.php).
 * This is the form the page renders itself when none is: plain HTML that posts
 * back to the page, handled here on `template_redirect` before a byte of the
 * page is rendered, and mailed to blueworx_contact_email().
 *
 * No nonce, on purpose. The contact page is served to signed-out visitors
 * from the edge cache (includes/public/cache.php), so any nonce in it would go
 * stale with the cached copy and the form would start "expiring" at random.
 * In its place: a honeypot field no person fills in, a per-address rate limit,
 * and the same field validation whether or not JavaScript ran.
 *
 * The browser script (assets/js/public-widgets.js) submits the same form over
 * fetch and asks for JSON back, so a visitor with JavaScript sees the success
 * panel without a reload; without it, the POST lands here and the page renders
 * the outcome. One handler, two ways of reporting it.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Where enquiries are sent, and the address shown on the contact cards.
 *
 * @return string Email address.
 */
function blueworx_contact_email() {
	/**
	 * Filters the sales inbox address.
	 *
	 * @param string $email Email address.
	 */
	return (string) apply_filters( 'blueworx_contact_email', 'sales@blueworx.io' );
}

/**
 * The "What can we help with?" options.
 *
 * @return string[] Key => label.
 */
function blueworx_contact_topics() {
	return array(
		'website'   => __( 'A new website or platform', 'bluegroup-project-blueworx' ),
		'clubhouse' => __( 'ClubHouse for our club', 'bluegroup-project-blueworx' ),
		'hosting'   => __( 'Managed hosting or a migration', 'bluegroup-project-blueworx' ),
		'support'   => __( 'Integrated Support package', 'bluegroup-project-blueworx' ),
		'other'     => __( 'Something else', 'bluegroup-project-blueworx' ),
	);
}

/**
 * The budget bands offered as chips. Picking one writes its label into the
 * budget text field, so the field is the single value the form submits.
 *
 * @return string[] Labels.
 */
function blueworx_contact_budgets() {
	return array(
		__( 'Under £2k', 'bluegroup-project-blueworx' ),
		__( '£2k–£5k', 'bluegroup-project-blueworx' ),
		__( '£5k–£15k', 'bluegroup-project-blueworx' ),
		__( '£15k+', 'bluegroup-project-blueworx' ),
		__( 'Monthly retainer', 'bluegroup-project-blueworx' ),
	);
}

/**
 * The outcome of this request's submission, for the template.
 *
 * Set by blueworx_contact_handle() when a POST was rejected; read by
 * templates/parts/contact-form.php to show the errors beside the fields and
 * keep what the visitor typed. Empty on a plain GET.
 *
 * @param array|null $state When given, stores this state.
 * @return array { errors: string[] keyed by field, values: string[] keyed by field, failed: bool }
 */
function blueworx_contact_state( $state = null ) {
	static $current = array(
		'errors' => array(),
		'values' => array(),
		'failed' => false,
	);

	if ( is_array( $state ) ) {
		$current = array_merge( $current, $state );
	}

	return $current;
}

/**
 * Whether the page being rendered is the contact page.
 *
 * By template, not slug, for the reason blueworx_public_page_needs_foreign_assets()
 * gives: renaming the page must not switch its form off.
 *
 * @return bool
 */
function blueworx_contact_is_contact_page() {
	$page = blueworx_public_current_page();

	return null !== $page && 'pages/contact.php' === $page['template'];
}

/**
 * Whether the visitor's browser asked for a JSON answer (the fetch submit).
 *
 * @return bool
 */
function blueworx_contact_wants_json() {
	$with = isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) : '';

	return 'XMLHttpRequest' === $with;
}

/**
 * Reads and validates the submitted fields.
 *
 * @return array { values: string[], errors: string[] }
 */
function blueworx_contact_read_submission() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- a public, cacheable form: see the file header for why there is no nonce and what stands in for it.
	$text = function ( $key ) {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	};

	$values = array(
		'name'    => $text( 'full_name' ),
		'company' => $text( 'company' ),
		'email'   => $text( 'email' ),
		'phone'   => $text( 'phone' ),
		'topic'   => sanitize_key( $text( 'topic' ) ),
		'budget'  => $text( 'budget' ),
		'message' => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
		'agree'   => isset( $_POST['agree'] ) ? '1' : '',
	);
	// phpcs:enable

	$topics = blueworx_contact_topics();

	if ( ! isset( $topics[ $values['topic'] ] ) ) {
		$values['topic'] = (string) key( $topics );
	}

	$errors = array();

	if ( '' === trim( $values['name'] ) ) {
		$errors['name'] = __( 'Please tell us your name.', 'bluegroup-project-blueworx' );
	}

	if ( '' === trim( $values['email'] ) ) {
		$errors['email'] = __( 'Please add your email address.', 'bluegroup-project-blueworx' );
	} elseif ( ! is_email( $values['email'] ) ) {
		$errors['email'] = __( 'That email address does not look right.', 'bluegroup-project-blueworx' );
	}

	if ( '' === trim( $values['message'] ) ) {
		$errors['message'] = __( 'Please tell us a little about the project.', 'bluegroup-project-blueworx' );
	}

	return array(
		'values' => $values,
		'errors' => $errors,
	);
}

/**
 * Whether this address has sent too many enquiries lately.
 *
 * Five in ten minutes is far more than a person needs and far fewer than a
 * script sends. Counted per address in a transient, so a busy office behind
 * one IP still gets its five.
 *
 * @return bool True when the request must be refused.
 */
function blueworx_contact_rate_limited() {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'blueworx_contact_' . md5( $ip );
	$n   = (int) get_transient( $key );

	/**
	 * Filters how many enquiries one address may send in ten minutes.
	 *
	 * @param int $max Default 5.
	 */
	$max = (int) apply_filters( 'blueworx_contact_rate_limit', 5 );

	if ( $n >= $max ) {
		return true;
	}

	set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );

	return false;
}

/**
 * Sends the enquiry.
 *
 * @param array $values Validated field values.
 * @return bool Whether the mail was accepted for delivery.
 */
function blueworx_contact_send( $values ) {
	$topics = blueworx_contact_topics();

	$lines = array(
		sprintf(
			/* translators: 1: sender name, 2: sender email. */
			__( 'From: %1$s (%2$s)', 'bluegroup-project-blueworx' ),
			$values['name'],
			$values['email']
		),
	);

	if ( '' !== $values['company'] ) {
		/* translators: %s: company name. */
		$lines[] = sprintf( __( 'Company: %s', 'bluegroup-project-blueworx' ), $values['company'] );
	}

	if ( '' !== $values['phone'] ) {
		/* translators: %s: phone number. */
		$lines[] = sprintf( __( 'Phone: %s', 'bluegroup-project-blueworx' ), $values['phone'] );
	}

	/* translators: %s: the topic the visitor picked. */
	$lines[] = sprintf( __( 'Topic: %s', 'bluegroup-project-blueworx' ), $topics[ $values['topic'] ] );

	if ( '' !== $values['budget'] ) {
		/* translators: %s: budget band or figure. */
		$lines[] = sprintf( __( 'Budget: %s', 'bluegroup-project-blueworx' ), $values['budget'] );
	}

	$lines[] = sprintf(
		/* translators: %s: Yes or No. */
		__( 'Happy to be contacted: %s', 'bluegroup-project-blueworx' ),
		'1' === $values['agree'] ? __( 'Yes', 'bluegroup-project-blueworx' ) : __( 'No', 'bluegroup-project-blueworx' )
	);
	$lines[] = '';
	$lines[] = $values['message'];

	return (bool) wp_mail(
		blueworx_contact_email(),
		sprintf(
			/* translators: 1: topic, 2: sender name. */
			__( '[Enquiry] %1$s — %2$s', 'bluegroup-project-blueworx' ),
			$topics[ $values['topic'] ],
			$values['name']
		),
		implode( "\n", $lines ),
		array( 'Reply-To: ' . $values['name'] . ' <' . $values['email'] . '>' )
	);
}

/**
 * Handles a submission of the contact form.
 *
 * On success: a fetch submit gets `{ ok: true }`; a plain one is sent back to
 * the page with `?sent=1`, which the template turns into the success panel —
 * a redirect, so a refresh cannot send the enquiry twice. On failure the
 * errors go back the same two ways, and the plain-POST page keeps what was
 * typed.
 *
 * @return void
 */
function blueworx_contact_handle() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '' ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- see the file header.
	if ( ! isset( $_POST['blueworx_contact'] ) || ! blueworx_contact_is_contact_page() ) {
		return;
	}

	// The honeypot: a field the stylesheet hides and a person never sees.
	// Anything in it is a script, which is told it succeeded and ignored.
	$trapped = isset( $_POST['website'] ) && '' !== trim( sanitize_text_field( wp_unslash( $_POST['website'] ) ) );
	// phpcs:enable

	$submission = blueworx_contact_read_submission();
	$values     = $submission['values'];
	$errors     = $submission['errors'];
	$failed     = false;

	if ( empty( $errors ) && ! $trapped ) {
		if ( blueworx_contact_rate_limited() ) {
			$failed = true;
		} elseif ( ! blueworx_contact_send( $values ) ) {
			$failed = true;
		}
	}

	$ok = empty( $errors ) && ! $failed;

	if ( blueworx_contact_wants_json() ) {
		nocache_headers();
		wp_send_json(
			array(
				'ok'     => $ok,
				'errors' => $errors,
				'failed' => $failed,
			),
			$ok ? 200 : 422
		);
	}

	if ( $ok ) {
		wp_safe_redirect( add_query_arg( 'sent', '1', get_permalink() ), 303 );
		exit;
	}

	blueworx_contact_state(
		array(
			'errors' => $errors,
			'values' => $values,
			'failed' => $failed,
		)
	);
}
add_action( 'template_redirect', 'blueworx_contact_handle', 2 );
