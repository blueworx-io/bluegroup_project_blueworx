<?php
/**
 * Sign in (#43).
 *
 * Posts to itself; includes/public/auth.php handles the submission before
 * anything is rendered, so a failed sign-in is a redirect back here with a
 * notice rather than a half-drawn page.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Carried through the form so that a visitor who was sent here from a
// dashboard page lands back on it. Validated in blueworx_auth_redirect_target()
// before it is ever used as a destination.
$blueworx_login_redirect = isset( $_GET['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only.

blueworx_public_part(
	'parts/auth-shell.php',
	array(
		'heading' => __( 'Sign in', 'bluegroup-project-blueworx' ),
		'blurb'   => __( 'Your plans, invoices and orders, all in one place.', 'bluegroup-project-blueworx' ),
	)
);
?>
<form class="auth-form" method="post" action="<?php echo esc_url( blueworx_auth_url( 'login' ) ); ?>">
	<?php wp_nonce_field( 'blueworx_auth_login', 'blueworx_auth_nonce' ); ?>
	<input type="hidden" name="blueworx_auth_action" value="login" />
	<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $blueworx_login_redirect ); ?>" />

	<div class="auth-field">
		<label for="blueworx_email"><?php esc_html_e( 'Email address', 'bluegroup-project-blueworx' ); ?></label>
		<input type="email" id="blueworx_email" name="blueworx_email" autocomplete="username" required />
	</div>

	<div class="auth-field">
		<label for="blueworx_password"><?php esc_html_e( 'Password', 'bluegroup-project-blueworx' ); ?></label>
		<input type="password" id="blueworx_password" name="blueworx_password" autocomplete="current-password" required />
	</div>

	<div class="auth-row">
		<label class="auth-check" for="blueworx_remember">
			<input type="checkbox" id="blueworx_remember" name="blueworx_remember" value="1" />
			<?php esc_html_e( 'Keep me signed in', 'bluegroup-project-blueworx' ); ?>
		</label>
		<a href="<?php echo esc_url( blueworx_auth_url( 'reset-password' ) ); ?>"><?php esc_html_e( 'Forgotten your password?', 'bluegroup-project-blueworx' ); ?></a>
	</div>

	<button type="submit" class="btn btn-brand btn-md auth-submit"><?php esc_html_e( 'Sign in', 'bluegroup-project-blueworx' ); ?></button>
</form>

<p class="auth-alt">
	<?php if ( blueworx_auth_registration_open() ) : ?>
		<?php esc_html_e( 'New here?', 'bluegroup-project-blueworx' ); ?>
		<a href="<?php echo esc_url( blueworx_auth_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'bluegroup-project-blueworx' ); ?></a>
	<?php else : ?>
		<?php esc_html_e( 'Do not have an account yet?', 'bluegroup-project-blueworx' ); ?>
		<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php esc_html_e( 'Get in touch', 'bluegroup-project-blueworx' ); ?></a>
	<?php endif; ?>
</p>
<?php
blueworx_public_part( 'parts/auth-end.php' );
