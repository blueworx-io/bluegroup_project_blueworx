<?php
/**
 * Client dashboard — your details (#97).
 *
 * The portal design's "Your details" panel: name, company and email in one
 * card, a password change in another. Both are real — they write to the
 * WordPress user this client signs in as, which is the same record SureCart
 * reads for their invoices.
 *
 * Submitting posts back to this same page and is handled in
 * includes/public/account-forms.php, before anything renders. Two separate
 * forms rather than one: changing an email address and changing a password are
 * different decisions with different risks, and a single Save that quietly did
 * both is how somebody changes a password by accident.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_dash_sections = blueworx_account_sections();
$blueworx_details_user  = wp_get_current_user();
$blueworx_details_co    = (string) get_user_meta( $blueworx_details_user->ID, 'billing_company', true );

blueworx_public_part(
	'parts/dash-shell.php',
	array(
		'section' => 'details',
		'heading' => $blueworx_dash_sections['details']['title'],
		'kicker'  => $blueworx_dash_sections['details']['kicker'],
	)
);

blueworx_public_part( 'parts/dash-notice.php' );
?>
<div class="dash-split">
	<section class="dash-card" aria-labelledby="bw-details-heading">
		<h2 class="dash-card-title" id="bw-details-heading"><?php esc_html_e( 'Your details', 'bluegroup-project-blueworx' ); ?></h2>
		<p class="dash-card-sub"><?php esc_html_e( 'What we call you, and where we send anything about your account.', 'bluegroup-project-blueworx' ); ?></p>

		<form class="dash-form" method="post" action="<?php echo esc_url( blueworx_account_url( 'details' ) ); ?>">
			<input type="hidden" name="blueworx_account_form" value="details" />
			<?php wp_nonce_field( 'blueworx_account_details', 'blueworx_account_details_nonce' ); ?>

			<div class="dash-field-row">
				<div class="dash-field">
					<label for="bw-first-name"><?php esc_html_e( 'First name', 'bluegroup-project-blueworx' ); ?></label>
					<input type="text" id="bw-first-name" name="first_name" autocomplete="given-name"
						value="<?php echo esc_attr( $blueworx_details_user->first_name ); ?>" />
				</div>
				<div class="dash-field">
					<label for="bw-last-name"><?php esc_html_e( 'Last name', 'bluegroup-project-blueworx' ); ?></label>
					<input type="text" id="bw-last-name" name="last_name" autocomplete="family-name"
						value="<?php echo esc_attr( $blueworx_details_user->last_name ); ?>" />
				</div>
			</div>

			<div class="dash-field">
				<label for="bw-company"><?php esc_html_e( 'Company', 'bluegroup-project-blueworx' ); ?></label>
				<input type="text" id="bw-company" name="company" autocomplete="organization"
					value="<?php echo esc_attr( $blueworx_details_co ); ?>" />
				<p class="dash-hint"><?php esc_html_e( 'This is the name that appears on your invoices.', 'bluegroup-project-blueworx' ); ?></p>
			</div>

			<div class="dash-field">
				<label for="bw-email"><?php esc_html_e( 'Email', 'bluegroup-project-blueworx' ); ?></label>
				<input type="email" id="bw-email" name="email" autocomplete="email" required
					value="<?php echo esc_attr( $blueworx_details_user->user_email ); ?>" />
				<p class="dash-hint"><?php esc_html_e( 'You sign in with this address, and it is where a password reset would go.', 'bluegroup-project-blueworx' ); ?></p>
			</div>

			<button type="submit" class="btn btn-brand btn-sm"><?php esc_html_e( 'Save changes', 'bluegroup-project-blueworx' ); ?></button>
		</form>
	</section>

	<section class="dash-card" aria-labelledby="bw-password-heading">
		<h2 class="dash-card-title" id="bw-password-heading"><?php esc_html_e( 'Change password', 'bluegroup-project-blueworx' ); ?></h2>
		<p class="dash-card-sub"><?php esc_html_e( 'Changing your password signs you out everywhere else, but keeps you signed in here.', 'bluegroup-project-blueworx' ); ?></p>

		<form class="dash-form" method="post" action="<?php echo esc_url( blueworx_account_url( 'details' ) ); ?>">
			<input type="hidden" name="blueworx_account_form" value="password" />
			<?php wp_nonce_field( 'blueworx_account_password', 'blueworx_account_password_nonce' ); ?>

			<div class="dash-field">
				<label for="bw-current-password"><?php esc_html_e( 'Current password', 'bluegroup-project-blueworx' ); ?></label>
				<input type="password" id="bw-current-password" name="current_password" autocomplete="current-password" required />
				<?php // Asked for even though they are signed in — see account-forms.php. ?>
				<p class="dash-hint"><?php esc_html_e( 'We ask for this so nobody who finds your screen unlocked can change it.', 'bluegroup-project-blueworx' ); ?></p>
			</div>

			<div class="dash-field">
				<label for="bw-new-password"><?php esc_html_e( 'New password', 'bluegroup-project-blueworx' ); ?></label>
				<input type="password" id="bw-new-password" name="new_password" autocomplete="new-password" minlength="8" required />
				<p class="dash-hint"><?php esc_html_e( 'At least 8 characters.', 'bluegroup-project-blueworx' ); ?></p>
			</div>

			<button type="submit" class="btn btn-outline btn-sm"><?php esc_html_e( 'Update password', 'bluegroup-project-blueworx' ); ?></button>
		</form>
	</section>
</div>
<?php
blueworx_public_part( 'parts/dash-end.php' );
