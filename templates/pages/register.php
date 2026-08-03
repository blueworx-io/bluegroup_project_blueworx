<?php
/**
 * Create an account (#43).
 *
 * The form is only drawn when WordPress's own membership setting allows new
 * accounts. That is checked again in the handler — the form is HTML, and HTML
 * is not a control.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

blueworx_public_part(
	'parts/auth-shell.php',
	array(
		'heading' => __( 'Create an account', 'bluegroup-project-blueworx' ),
		'blurb'   => __( 'One account for your plans, invoices and orders.', 'bluegroup-project-blueworx' ),
	)
);

if ( ! blueworx_auth_registration_open() ) :
	?>
	<p class="auth-blurb">
		<?php esc_html_e( 'New accounts are not open at the moment. Tell us what you need and we will set one up for you.', 'bluegroup-project-blueworx' ); ?>
	</p>
	<a class="btn btn-brand btn-md auth-submit" href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php esc_html_e( 'Get in touch', 'bluegroup-project-blueworx' ); ?></a>
	<?php
else :
	?>
	<form class="auth-form" method="post" action="<?php echo esc_url( blueworx_auth_url( 'register' ) ); ?>">
		<?php wp_nonce_field( 'blueworx_auth_register', 'blueworx_auth_nonce' ); ?>
		<input type="hidden" name="blueworx_auth_action" value="register" />

		<div class="auth-field">
			<label for="blueworx_name"><?php esc_html_e( 'Your name', 'bluegroup-project-blueworx' ); ?></label>
			<input type="text" id="blueworx_name" name="blueworx_name" autocomplete="name" />
		</div>

		<div class="auth-field">
			<label for="blueworx_email"><?php esc_html_e( 'Email address', 'bluegroup-project-blueworx' ); ?></label>
			<input type="email" id="blueworx_email" name="blueworx_email" autocomplete="email" required />
		</div>

		<div class="auth-field">
			<label for="blueworx_password"><?php esc_html_e( 'Password', 'bluegroup-project-blueworx' ); ?></label>
			<input type="password" id="blueworx_password" name="blueworx_password" autocomplete="new-password" minlength="12" required aria-describedby="blueworx_password_hint" />
			<p class="auth-hint" id="blueworx_password_hint"><?php esc_html_e( 'At least 12 characters.', 'bluegroup-project-blueworx' ); ?></p>
		</div>

		<button type="submit" class="btn btn-brand btn-md auth-submit"><?php esc_html_e( 'Create account', 'bluegroup-project-blueworx' ); ?></button>
	</form>
	<?php
endif;
?>
<p class="auth-alt">
	<?php esc_html_e( 'Already have an account?', 'bluegroup-project-blueworx' ); ?>
	<a href="<?php echo esc_url( blueworx_auth_url( 'login' ) ); ?>"><?php esc_html_e( 'Sign in', 'bluegroup-project-blueworx' ); ?></a>
</p>
<?php
blueworx_public_part( 'parts/auth-end.php' );
