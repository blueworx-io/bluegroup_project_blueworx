<?php
/**
 * Client dashboard — support (#98).
 *
 * The design draws this as the front of a ticket system: a request form, a
 * queue, and a set of guides. There is no ticket system, so this page is the
 * honest version of it — the form emails the request to us and says so, and
 * the client is told a person will reply by email rather than being handed a
 * reference number for a queue that does not exist.
 *
 * The guides the design lists are not here either. Every one of them
 * ("Editing pages safely", "Reading your analytics") is an article nobody has
 * written; five links to nothing is the exact fault #77 removed from the
 * footer. The journal is linked instead, because it is real, and the guides can
 * take their place here as they are written.
 *
 * Handled in includes/public/account-forms.php, before anything renders.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_dash_sections = blueworx_account_sections();
$blueworx_sup_types     = blueworx_account_support_types();
$blueworx_sup_email     = blueworx_account_support_email();

blueworx_public_part(
	'parts/dash-shell.php',
	array(
		'section' => 'support',
		'heading' => $blueworx_dash_sections['support']['title'],
		'kicker'  => $blueworx_dash_sections['support']['kicker'],
	)
);

blueworx_public_part( 'parts/dash-notice.php' );
?>
<div class="dash-split">
	<section class="dash-card" aria-labelledby="bw-support-heading">
		<h2 class="dash-card-title" id="bw-support-heading"><?php esc_html_e( 'Ask us for help', 'bluegroup-project-blueworx' ); ?></h2>
		<p class="dash-card-sub"><?php esc_html_e( 'We already know who you are and what you are on, so just tell us what is happening.', 'bluegroup-project-blueworx' ); ?></p>

		<form class="dash-form" method="post" action="<?php echo esc_url( blueworx_account_url( 'support' ) ); ?>">
			<input type="hidden" name="blueworx_account_form" value="support" />
			<?php wp_nonce_field( 'blueworx_account_support', 'blueworx_account_support_nonce' ); ?>

			<?php
			// A real radio group in a fieldset, not a styled row of divs: these
			// are one choice from three, and a screen reader should be told
			// that before it reads the options.
			?>
			<fieldset class="dash-fieldset">
				<legend><?php esc_html_e( 'What is this about?', 'bluegroup-project-blueworx' ); ?></legend>
				<?php $blueworx_sup_first = true; ?>
				<?php foreach ( $blueworx_sup_types as $blueworx_sup_key => $blueworx_sup_type ) : ?>
					<label class="dash-radio" for="bw-type-<?php echo esc_attr( $blueworx_sup_key ); ?>">
						<input type="radio" id="bw-type-<?php echo esc_attr( $blueworx_sup_key ); ?>"
							name="request_type" value="<?php echo esc_attr( $blueworx_sup_key ); ?>"
							<?php checked( $blueworx_sup_first ); ?> />
						<span>
							<span class="dash-radio-label"><?php echo esc_html( $blueworx_sup_type['label'] ); ?></span>
							<span class="dash-radio-hint"><?php echo esc_html( $blueworx_sup_type['hint'] ); ?></span>
						</span>
					</label>
					<?php $blueworx_sup_first = false; ?>
				<?php endforeach; ?>
			</fieldset>

			<div class="dash-field">
				<label for="bw-support-message"><?php esc_html_e( 'What is happening?', 'bluegroup-project-blueworx' ); ?></label>
				<textarea id="bw-support-message" name="message" rows="6" required></textarea>
			</div>

			<button type="submit" class="btn btn-brand btn-sm"><?php esc_html_e( 'Send request', 'bluegroup-project-blueworx' ); ?></button>
			<p class="dash-hint"><?php esc_html_e( 'This goes straight to the team by email, and we reply to the address on your account.', 'bluegroup-project-blueworx' ); ?></p>
		</form>
	</section>

	<div class="dash-stack">
		<section class="dash-card" aria-labelledby="bw-urgent-heading">
			<h2 class="dash-card-title" id="bw-urgent-heading"><?php esc_html_e( 'Something urgent?', 'bluegroup-project-blueworx' ); ?></h2>
			<p class="dash-card-sub"><?php esc_html_e( 'A site that is down or a checkout that is failing should not wait in an inbox.', 'bluegroup-project-blueworx' ); ?></p>
			<p class="dash-contact">
				<a href="<?php echo esc_url( 'mailto:' . $blueworx_sup_email ); ?>"><?php echo esc_html( $blueworx_sup_email ); ?></a>
			</p>
			<a class="dash-quiet-link" href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php esc_html_e( 'Other ways to reach us', 'bluegroup-project-blueworx' ); ?></a>
		</section>

		<section class="dash-card" aria-labelledby="bw-reading-heading">
			<h2 class="dash-card-title" id="bw-reading-heading"><?php esc_html_e( 'Read up first', 'bluegroup-project-blueworx' ); ?></h2>
			<p class="dash-card-sub"><?php esc_html_e( 'How we do things, written up plainly — often faster than waiting on a reply.', 'bluegroup-project-blueworx' ); ?></p>
			<a class="btn btn-outline btn-sm" href="<?php echo esc_url( blueworx_public_journal_url() ); ?>"><?php esc_html_e( 'Open the journal', 'bluegroup-project-blueworx' ); ?></a>
		</section>
	</div>
</div>
<?php
blueworx_public_part( 'parts/dash-end.php' );
