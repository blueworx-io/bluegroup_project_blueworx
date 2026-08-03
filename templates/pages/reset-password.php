<?php
/**
 * Reset your password (#43).
 *
 * Two screens in one page, chosen by whether the request carries a valid reset
 * key: ask for a link, or set a new password with one. Keeping both here means
 * the email can link straight to this page and a client never sees
 * wp-login.php.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- these come
// from the emailed link, not from a form submission; the key itself is the
// credential and is verified by check_password_reset_key() below.
$blueworx_reset_key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
$blueworx_reset_login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// Checked before the form is drawn as well as on submit, so an expired link
// says so straight away rather than after the client has typed a new password
// twice.
$blueworx_reset_valid = false;

if ( '' !== $blueworx_reset_key && '' !== $blueworx_reset_login ) {
	$blueworx_reset_valid = ! is_wp_error( check_password_reset_key( $blueworx_reset_key, $blueworx_reset_login ) );
}

blueworx_public_part(
	'parts/auth-shell.php',
	array(
		'heading' => $blueworx_reset_valid
			? __( 'Choose a new password', 'bluegroup-project-blueworx' )
			: __( 'Reset your password', 'bluegroup-project-blueworx' ),
		'blurb'   => $blueworx_reset_valid
			? ''
			: __( 'We will email you a link to set a new one.', 'bluegroup-project-blueworx' ),
	)
);

if ( $blueworx_reset_valid ) :
	?>
	<form class="auth-form" method="post" action="<?php echo esc_url( blueworx_auth_url( 'reset-password' ) ); ?>">
		<?php wp_nonce_field( 'blueworx_auth_reset-set', 'blueworx_auth_nonce' ); ?>
		<input type="hidden" name="blueworx_auth_action" value="reset-set" />
		<input type="hidden" name="blueworx_key" value="<?php echo esc_attr( $blueworx_reset_key ); ?>" />
		<input type="hidden" name="blueworx_login" value="<?php echo esc_attr( $blueworx_reset_login ); ?>" />

		<div class="auth-field">
			<label for="blueworx_password"><?php esc_html_e( 'New password', 'bluegroup-project-blueworx' ); ?></label>
			<input type="password" id="blueworx_password" name="blueworx_password" autocomplete="new-password" minlength="12" required aria-describedby="blueworx_password_hint" />
			<p class="auth-hint" id="blueworx_password_hint"><?php esc_html_e( 'At least 12 characters.', 'bluegroup-project-blueworx' ); ?></p>
		</div>

		<button type="submit" class="btn btn-brand btn-md auth-submit"><?php esc_html_e( 'Save new password', 'bluegroup-project-blueworx' ); ?></button>
	</form>
	<?php
else :
	?>
	<form class="auth-form" method="post" action="<?php echo esc_url( blueworx_auth_url( 'reset-password' ) ); ?>">
		<?php wp_nonce_field( 'blueworx_auth_reset-request', 'blueworx_auth_nonce' ); ?>
		<input type="hidden" name="blueworx_auth_action" value="reset-request" />

		<div class="auth-field">
			<label for="blueworx_email"><?php esc_html_e( 'Email address', 'bluegroup-project-blueworx' ); ?></label>
			<input type="email" id="blueworx_email" name="blueworx_email" autocomplete="username" required />
		</div>

		<button type="submit" class="btn btn-brand btn-md auth-submit"><?php esc_html_e( 'Email me a link', 'bluegroup-project-blueworx' ); ?></button>
	</form>
	<?php
endif;
?>
<p class="auth-alt">
	<a href="<?php echo esc_url( blueworx_auth_url( 'login' ) ); ?>"><?php esc_html_e( 'Back to sign in', 'bluegroup-project-blueworx' ); ?></a>
</p>
<?php
blueworx_public_part( 'parts/auth-end.php' );
