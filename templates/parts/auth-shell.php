<?php
/**
 * Shell for the sign-in, sign-up and reset pages.
 *
 * A narrow centred card on the site's own background, with the nav above it, so
 * signing in plainly happens on blueworx.io rather than on a screen that could
 * be anywhere. Close it with parts/auth-end.php.
 *
 * $vars:
 * - heading (string, required) The page's <h1>.
 * - blurb   (string, optional) A line under the heading.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_auth_heading = isset( $heading ) ? (string) $heading : '';
$blueworx_auth_blurb   = isset( $blurb ) ? (string) $blurb : '';
$blueworx_auth_notice  = blueworx_auth_notice();
$blueworx_auth_all     = blueworx_auth_messages();

blueworx_public_document_open( array( 'body_class' => 'bw-auth' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main class="auth">
	<div class="auth-card">
		<h1 class="h2 auth-title"><?php echo esc_html( $blueworx_auth_heading ); ?></h1>
		<?php if ( '' !== $blueworx_auth_blurb ) : ?>
			<p class="auth-blurb"><?php echo esc_html( $blueworx_auth_blurb ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $blueworx_auth_notice ) : ?>
			<?php $blueworx_auth_msg = $blueworx_auth_all[ $blueworx_auth_notice ]; ?>
			<p class="auth-notice auth-notice-<?php echo esc_attr( $blueworx_auth_msg['type'] ); ?>" role="<?php echo 'error' === $blueworx_auth_msg['type'] ? 'alert' : 'status'; ?>">
				<?php echo esc_html( $blueworx_auth_msg['text'] ); ?>
			</p>
		<?php endif; ?>
