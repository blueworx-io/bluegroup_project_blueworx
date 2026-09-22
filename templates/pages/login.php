<?php
/**
 * Sign in (#43).
 *
 * The form is the shop's — `<sc-login-form>`, a custom element its own script
 * brings to life, posting to its own route. That route is wp_authenticate()
 * underneath, so every login guard the site has still applies, and a shop that
 * is installed but not yet connected still signs people in.
 *
 * What stays ours is the card around it: the heading, the line under it, and
 * the way through for somebody who has no account yet. The form's own title is
 * left empty and hidden in CSS — the card already says "Sign in", and the form
 * repeating it two lines below reads like two forms.
 *
 * Where a member lands afterwards is set in includes/public/auth.php, on the
 * shop's sc_login_redirect_url filter.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_login_heading = __( 'Sign in', 'bluegroup-project-blueworx' );

blueworx_public_part(
	'parts/auth-shell.php',
	array(
		'heading' => $blueworx_login_heading,
		'blurb'   => __( 'Your plans, invoices and orders, all in one place.', 'bluegroup-project-blueworx' ),
	)
);
?>
<?php if ( blueworx_auth_shop_form_available() ) : ?>
	<sc-login-form></sc-login-form>

	<p class="auth-alt">
		<?php esc_html_e( 'New here?', 'bluegroup-project-blueworx' ); ?>
		<a href="<?php echo esc_url( home_url( '/support' ) ); ?>"><?php esc_html_e( 'See the support packages', 'bluegroup-project-blueworx' ); ?></a>
	</p>
<?php else : ?>
	<?php
	// No shop, so no form to draw. Saying so and pointing at the WordPress
	// sign-in is honest; an empty card, or a custom element with nothing to
	// bring it to life, is a page that looks broken and leaves no way in.
	?>
	<p class="auth-notice auth-notice-error" role="alert">
		<?php esc_html_e( 'Client sign-in is unavailable at the moment.', 'bluegroup-project-blueworx' ); ?>
	</p>

	<p class="auth-alt">
		<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Sign in to WordPress instead', 'bluegroup-project-blueworx' ); ?></a>
	</p>
<?php endif; ?>
<?php
blueworx_public_part( 'parts/auth-end.php' );
