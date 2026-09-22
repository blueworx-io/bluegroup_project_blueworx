<?php
/**
 * The Contact page's enquiry form (`.cf-form`), or the panel that replaces
 * it once an enquiry has been sent.
 *
 * Rendered by templates/pages/contact.php when no third-party shortcode is
 * configured. Posts back to the page; includes/public/contact-form.php handles
 * the submission and, on a rejected plain POST, hands the errors and typed
 * values back through blueworx_contact_state() so the fields keep them.
 *
 * `?sent=1` on the page URL is the redirect after a successful plain POST and
 * shows the success panel instead of the form. The browser script swaps the
 * two in place without a reload, so both are always in the markup.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_cf_state  = blueworx_contact_state();
$blueworx_cf_errors = $blueworx_cf_state['errors'];
$blueworx_cf_values = array_merge(
	array(
		'name'    => '',
		'company' => '',
		'email'   => '',
		'phone'   => '',
		'topic'   => '',
		'budget'  => '',
		'message' => '',
		'agree'   => '',
	),
	$blueworx_cf_state['values']
);
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a flag set by our own redirect, read only to pick which panel to show.
$blueworx_cf_sent   = isset( $_GET['sent'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['sent'] ) );
$blueworx_cf_failed = ! empty( $blueworx_cf_state['failed'] );
?>
<div class="cf-sent" data-cf-sent<?php echo $blueworx_cf_sent ? '' : ' hidden'; ?> role="status">
	<h2><?php esc_html_e( 'Thanks — that&rsquo;s with us.', 'bluegroup-project-blueworx' ); ?></h2>
	<p><?php esc_html_e( 'We read every enquiry ourselves. Expect a reply from a real person within one business day, usually sooner.', 'bluegroup-project-blueworx' ); ?></p>
	<a class="btn btn-outline btn-sm" href="<?php echo esc_url( get_permalink() ); ?>" data-cf-again><?php esc_html_e( 'Send another message', 'bluegroup-project-blueworx' ); ?></a>
</div>

<form class="cf-form" method="post" action="<?php echo esc_url( get_permalink() ); ?>" novalidate data-widget="contact-form" data-cf-form<?php echo $blueworx_cf_sent ? ' hidden' : ''; ?>>
	<input type="hidden" name="blueworx_contact" value="1">
	<?php // The name field is full_name, not name: WordPress reads its public query vars from POST as well as GET, so a field called name would make it look for a post by that slug and 404 the submission. ?>
	<?php // The honeypot. Hidden by the stylesheet, not `hidden`, so a script that skips hidden inputs still fills it. ?>
	<div class="cf-trap" aria-hidden="true">
		<label for="bw-website"><?php esc_html_e( 'Website', 'bluegroup-project-blueworx' ); ?></label>
		<input id="bw-website" name="website" type="text" tabindex="-1" autocomplete="off">
	</div>

	<p class="cf-alert" data-cf-alert role="alert"<?php echo $blueworx_cf_failed ? '' : ' hidden'; ?>>
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s: sales email address. */
				__( 'We could not send that just now. Please email us at %s and we will pick it up.', 'bluegroup-project-blueworx' ),
				blueworx_contact_email()
			)
		);
		?>
	</p>

	<div class="cf-row">
		<div class="cf-field<?php echo isset( $blueworx_cf_errors['name'] ) ? ' err' : ''; ?>">
			<label class="bw-lb" for="bw-name"><?php esc_html_e( 'Your name', 'bluegroup-project-blueworx' ); ?></label>
			<input class="bw-in" id="bw-name" name="full_name" type="text" placeholder="Jane Whitfield" required autocomplete="name" aria-describedby="bw-name-msg" value="<?php echo esc_attr( $blueworx_cf_values['name'] ); ?>">
			<span class="msg" id="bw-name-msg" data-cf-msg="name"><?php echo isset( $blueworx_cf_errors['name'] ) ? esc_html( $blueworx_cf_errors['name'] ) : ''; ?></span>
		</div>
		<div class="cf-field">
			<label class="bw-lb" for="bw-company"><?php esc_html_e( 'Company', 'bluegroup-project-blueworx' ); ?></label>
			<input class="bw-in" id="bw-company" name="company" type="text" placeholder="Whitfield &amp; Co." autocomplete="organization" value="<?php echo esc_attr( $blueworx_cf_values['company'] ); ?>">
		</div>
	</div>

	<div class="cf-row">
		<div class="cf-field<?php echo isset( $blueworx_cf_errors['email'] ) ? ' err' : ''; ?>">
			<label class="bw-lb" for="bw-email"><?php esc_html_e( 'Email', 'bluegroup-project-blueworx' ); ?></label>
			<input class="bw-in" id="bw-email" name="email" type="email" placeholder="jane@company.co.uk" required autocomplete="email" aria-describedby="bw-email-msg" value="<?php echo esc_attr( $blueworx_cf_values['email'] ); ?>">
			<span class="msg" id="bw-email-msg" data-cf-msg="email"><?php echo isset( $blueworx_cf_errors['email'] ) ? esc_html( $blueworx_cf_errors['email'] ) : ''; ?></span>
		</div>
		<div class="cf-field">
			<label class="bw-lb" for="bw-phone"><?php esc_html_e( 'Phone (optional)', 'bluegroup-project-blueworx' ); ?></label>
			<input class="bw-in" id="bw-phone" name="phone" type="tel" placeholder="01628 000 000" autocomplete="tel" value="<?php echo esc_attr( $blueworx_cf_values['phone'] ); ?>">
		</div>
	</div>

	<div class="cf-field">
		<label class="bw-lb" for="bw-topic"><?php esc_html_e( 'What can we help with?', 'bluegroup-project-blueworx' ); ?></label>
		<select class="bw-in" id="bw-topic" name="topic">
			<?php foreach ( blueworx_contact_topics() as $blueworx_cf_key => $blueworx_cf_label ) : ?>
				<option value="<?php echo esc_attr( $blueworx_cf_key ); ?>"<?php selected( $blueworx_cf_values['topic'], $blueworx_cf_key ); ?>><?php echo esc_html( $blueworx_cf_label ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="cf-field">
		<label class="bw-lb" for="bw-budget"><?php esc_html_e( 'Rough budget', 'bluegroup-project-blueworx' ); ?></label>
		<div class="cf-chips" data-cf-chips>
			<?php foreach ( blueworx_contact_budgets() as $blueworx_cf_band ) : ?>
				<button type="button" class="svc-chip bw-chipbtn<?php echo $blueworx_cf_band === $blueworx_cf_values['budget'] ? ' bw-chip-on' : ''; ?>" data-budget="<?php echo esc_attr( $blueworx_cf_band ); ?>" aria-pressed="<?php echo $blueworx_cf_band === $blueworx_cf_values['budget'] ? 'true' : 'false'; ?>"><?php echo esc_html( $blueworx_cf_band ); ?></button>
			<?php endforeach; ?>
		</div>
		<input class="bw-in" id="bw-budget" name="budget" type="text" placeholder="<?php esc_attr_e( 'Or tell us a figure', 'bluegroup-project-blueworx' ); ?>" style="margin-top:10px" value="<?php echo esc_attr( $blueworx_cf_values['budget'] ); ?>">
	</div>

	<div class="cf-field<?php echo isset( $blueworx_cf_errors['message'] ) ? ' err' : ''; ?>">
		<label class="bw-lb" for="bw-message"><?php esc_html_e( 'Tell us about the project', 'bluegroup-project-blueworx' ); ?></label>
		<textarea class="bw-in" id="bw-message" name="message" rows="5" placeholder="<?php esc_attr_e( 'What are you trying to achieve, and what is getting in the way?', 'bluegroup-project-blueworx' ); ?>" required aria-describedby="bw-message-msg"><?php echo esc_textarea( $blueworx_cf_values['message'] ); ?></textarea>
		<span class="msg" id="bw-message-msg" data-cf-msg="message"><?php echo isset( $blueworx_cf_errors['message'] ) ? esc_html( $blueworx_cf_errors['message'] ) : ''; ?></span>
	</div>

	<div class="cf-agree">
		<input id="bw-agree" type="checkbox" name="agree" value="1"<?php checked( '1', $blueworx_cf_values['agree'] ); ?>>
		<label for="bw-agree"><?php esc_html_e( 'I&rsquo;m happy for BlueWorx to contact me about this enquiry. We never pass your details on.', 'bluegroup-project-blueworx' ); ?></label>
	</div>

	<button type="submit" class="btn btn-brand btn-md" style="align-self:flex-start" data-cf-submit>
		<?php esc_html_e( 'Send Enquiry', 'bluegroup-project-blueworx' ); ?>
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
	</button>
</form>
