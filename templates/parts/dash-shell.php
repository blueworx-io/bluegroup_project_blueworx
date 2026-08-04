<?php
/**
 * Client-area page shell — the portal app frame (#30).
 *
 * The Claude Design for the client area is an application, not a marketing
 * page: a fixed dark sidebar carrying the navigation and the signed-in client,
 * and a white header strip carrying the page title, its kicker, and the one
 * primary action. This part is that frame; each page renders its own content
 * into the scrolling column and closes with parts/dash-end.php.
 *
 * Two deliberate departures from the design, both because the data does not
 * exist rather than because the design is wrong:
 *
 * - **The site switcher is not here.** It picks between a client's websites,
 *   and nothing on this site records a client's websites.
 * - **Several sidebar sections are not here** — Websites, Toolbox, Partner,
 *   Support, Contacts. Each would need a data source we do not have, and a
 *   portal that shows a client invented figures about their own account is
 *   worse than one that shows fewer, true things.
 *
 * The sidebar is a real <nav> with an aria-current link rather than the
 * design's buttons: these are page navigations, and a button that navigates is
 * a link a keyboard user cannot open in a new tab.
 *
 * $vars:
 * - section (string, optional) Child slug of the current section. Empty on the
 *   overview.
 * - heading (string, required) The page's <h1>.
 * - kicker  (string, optional) The small uppercase line above the heading.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_dash_section  = isset( $section ) ? (string) $section : '';
$blueworx_dash_heading  = isset( $heading ) ? (string) $heading : '';
$blueworx_dash_kicker   = isset( $kicker ) ? (string) $kicker : '';
$blueworx_dash_user     = wp_get_current_user();
$blueworx_dash_name     = blueworx_account_display_name();
$blueworx_dash_sections = blueworx_account_sections();

blueworx_public_document_open( array( 'body_class' => 'bw-dashboard' ) );
?>
<div class="dash">
	<aside class="dash-side">
		<div class="dash-brand">
			<span class="dash-mark" aria-hidden="true">B</span>
			<span class="dash-wordmark"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<span class="dash-badge"><?php esc_html_e( 'Portal', 'bluegroup-project-blueworx' ); ?></span>
		</div>

		<nav class="dash-nav" aria-label="<?php esc_attr_e( 'Client area', 'bluegroup-project-blueworx' ); ?>">
			<a class="dash-navlink<?php echo '' === $blueworx_dash_section ? ' on' : ''; ?>"
				<?php
				if ( '' === $blueworx_dash_section ) {
					echo ' aria-current="page"';
				}
				?>
				href="<?php echo esc_url( blueworx_account_url() ); ?>">
				<?php blueworx_icon( 'gauge', 'dash-navicon' ); ?>
				<span><?php esc_html_e( 'Overview', 'bluegroup-project-blueworx' ); ?></span>
			</a>

			<div class="dash-navlabel"><?php esc_html_e( 'Billing', 'bluegroup-project-blueworx' ); ?></div>

			<?php foreach ( $blueworx_dash_sections as $blueworx_dash_slug => $blueworx_dash_item ) : ?>
				<?php $blueworx_dash_on = ( $blueworx_dash_slug === $blueworx_dash_section ); ?>
				<a class="dash-navlink<?php echo $blueworx_dash_on ? ' on' : ''; ?>"
					<?php
					if ( $blueworx_dash_on ) {
						echo ' aria-current="page"';
					}
					?>
					href="<?php echo esc_url( blueworx_account_url( $blueworx_dash_slug ) ); ?>">
					<?php blueworx_icon( isset( $blueworx_dash_item['icon'] ) ? $blueworx_dash_item['icon'] : 'doc', 'dash-navicon' ); ?>
					<span><?php echo esc_html( $blueworx_dash_item['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="dash-side-foot">
			<div class="dash-who">
				<span class="dash-avatar" aria-hidden="true"><?php echo esc_html( blueworx_account_initials() ); ?></span>
				<div class="dash-who-text">
					<div class="dash-who-name"><?php echo esc_html( '' === $blueworx_dash_name ? $blueworx_dash_user->display_name : $blueworx_dash_name ); ?></div>
					<div class="dash-who-sub"><?php echo esc_html( $blueworx_dash_user->user_email ); ?></div>
				</div>
			</div>
			<a class="dash-exit" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Exit to website', 'bluegroup-project-blueworx' ); ?>
			</a>
			<a class="dash-exit dash-signout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
				<?php esc_html_e( 'Sign out', 'bluegroup-project-blueworx' ); ?>
			</a>
		</div>
	</aside>

	<main class="dash-main">
		<header class="dash-top">
			<div>
				<h1 class="dash-title"><?php echo esc_html( $blueworx_dash_heading ); ?></h1>
				<?php if ( '' !== $blueworx_dash_kicker ) : ?>
					<div class="dash-kicker"><?php echo esc_html( $blueworx_dash_kicker ); ?></div>
				<?php endif; ?>
			</div>
			<a class="dash-cta" href="<?php echo esc_url( home_url( '/contact' ) ); ?>">
				<?php blueworx_icon( 'chat', 'dash-navicon' ); ?>
				<?php esc_html_e( 'New request', 'bluegroup-project-blueworx' ); ?>
			</a>
		</header>

		<div class="dash-scroll">
			<div class="dash-inner">
