<?php
/**
 * Client dashboard — overview (#37).
 *
 * The screen a client lands on after logging in: who they are signed in as, and
 * a way into each section. The account details come from WordPress; the
 * per-section figures arrive with #38, #39 and #40, which is why each card
 * links to its section rather than pretending to summarise it.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_dash_user = wp_get_current_user();

blueworx_public_part(
	'parts/dash-shell.php',
	array(
		'section' => '',
		'heading' => __( 'Your dashboard', 'bluegroup-project-blueworx' ),
		'blurb'   => __( 'Your plans, invoices and orders, all in one place.', 'bluegroup-project-blueworx' ),
	)
);
?>
<section class="dash-panel">
	<h2 class="dash-h3"><?php esc_html_e( 'Account', 'bluegroup-project-blueworx' ); ?></h2>
	<dl class="dash-facts">
		<div>
			<dt><?php esc_html_e( 'Name', 'bluegroup-project-blueworx' ); ?></dt>
			<dd><?php echo esc_html( $blueworx_dash_user->display_name ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Email', 'bluegroup-project-blueworx' ); ?></dt>
			<dd><?php echo esc_html( $blueworx_dash_user->user_email ); ?></dd>
		</div>
	</dl>
	<p class="dash-note">
		<?php esc_html_e( 'Need something changed on your account? Get in touch and we will sort it.', 'bluegroup-project-blueworx' ); ?>
		<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php esc_html_e( 'Contact us', 'bluegroup-project-blueworx' ); ?></a>
	</p>
</section>

<section class="dash-panel">
	<h2 class="dash-h3"><?php esc_html_e( 'Your sections', 'bluegroup-project-blueworx' ); ?></h2>
	<div class="dash-cards">
		<?php foreach ( blueworx_account_sections() as $blueworx_dash_slug => $blueworx_dash_section ) : ?>
			<a class="dash-card" href="<?php echo esc_url( blueworx_account_url( $blueworx_dash_slug ) ); ?>">
				<span class="dash-card-name"><?php echo esc_html( $blueworx_dash_section['label'] ); ?></span>
				<span class="dash-card-desc"><?php echo esc_html( $blueworx_dash_section['blurb'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<?php
blueworx_public_part( 'parts/dash-end.php' );
