<?php
/**
 * Client-area page shell.
 *
 * Everything the four dashboard pages share: the document, the site nav, the
 * greeting, and the section tabs. Each page passes its own heading and then
 * renders its content into the open <main>, closing with parts/dash-end.php.
 *
 * Split into an opening and a closing part rather than taking the content as a
 * callback so the pages read like the marketing templates next to them.
 *
 * $vars:
 * - section (string, optional) Child slug of the current section. Empty on the
 *   overview.
 * - heading (string, required) The page's <h1>.
 * - blurb   (string, optional) A line under the heading.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_dash_section = isset( $section ) ? (string) $section : '';
$blueworx_dash_heading = isset( $heading ) ? (string) $heading : '';
$blueworx_dash_blurb   = isset( $blurb ) ? (string) $blurb : '';
$blueworx_dash_name    = blueworx_account_display_name();

blueworx_public_document_open( array( 'body_class' => 'bw-dashboard' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main class="dash">
	<div class="dash-wrap">
		<header class="dash-head">
			<div>
				<p class="dash-hello">
					<?php
					if ( '' === $blueworx_dash_name ) {
						esc_html_e( 'Your account', 'bluegroup-project-blueworx' );
					} else {
						echo esc_html(
							sprintf(
								/* translators: %s: the client's first name. */
								__( 'Welcome back, %s', 'bluegroup-project-blueworx' ),
								$blueworx_dash_name
							)
						);
					}
					?>
				</p>
				<h1 class="h2"><?php echo esc_html( $blueworx_dash_heading ); ?></h1>
				<?php if ( '' !== $blueworx_dash_blurb ) : ?>
					<p class="dash-blurb"><?php echo esc_html( $blueworx_dash_blurb ); ?></p>
				<?php endif; ?>
			</div>
			<a class="dash-signout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Sign out', 'bluegroup-project-blueworx' ); ?></a>
		</header>

		<nav class="dash-tabs" aria-label="<?php esc_attr_e( 'Account sections', 'bluegroup-project-blueworx' ); ?>">
			<a class="dash-tab<?php echo '' === $blueworx_dash_section ? ' on' : ''; ?>"
				<?php
				if ( '' === $blueworx_dash_section ) {
					echo ' aria-current="page"';
				}
				?>
				href="<?php echo esc_url( blueworx_account_url() ); ?>"><?php esc_html_e( 'Overview', 'bluegroup-project-blueworx' ); ?></a>
			<?php foreach ( blueworx_account_sections() as $blueworx_dash_slug => $blueworx_dash_item ) : ?>
				<?php $blueworx_dash_on = ( $blueworx_dash_slug === $blueworx_dash_section ); ?>
				<a class="dash-tab<?php echo $blueworx_dash_on ? ' on' : ''; ?>"
					<?php
					if ( $blueworx_dash_on ) {
						echo ' aria-current="page"';
					}
					?>
					href="<?php echo esc_url( blueworx_account_url( $blueworx_dash_slug ) ); ?>"><?php echo esc_html( $blueworx_dash_item['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>
