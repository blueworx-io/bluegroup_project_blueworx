<?php
/**
 * Client dashboard — subscriptions (#38).
 *
 * The client's active plans, what they cost and when they renew, read live from
 * SureCart.
 *
 * Changing or cancelling a plan is deliberately not a button here. SureCart
 * owns the billing state, and a cancel button that half-works on a live
 * commercial site is worse than a clear route to a person — so the page says
 * plainly how to change a plan and links to it.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_dash_sections = blueworx_account_sections();

blueworx_public_part(
	'parts/dash-shell.php',
	array(
		'section' => 'subscriptions',
		'heading' => $blueworx_dash_sections['subscriptions']['title'],
		'kicker'  => $blueworx_dash_sections['subscriptions']['kicker'],
	)
);

blueworx_public_part(
	'parts/dash-table.php',
	array(
		'result'  => blueworx_account_subscriptions(),
		'columns' => array(
			'name'   => __( 'Plan', 'bluegroup-project-blueworx' ),
			'status' => __( 'Status', 'bluegroup-project-blueworx' ),
			'amount' => __( 'Price', 'bluegroup-project-blueworx' ),
			'renews' => __( 'Renews', 'bluegroup-project-blueworx' ),
		),
		'empty'   => __( 'You have no active plans on your account yet.', 'bluegroup-project-blueworx' ),
		'cta'     => __( 'See our support plans', 'bluegroup-project-blueworx' ),
		'href'    => home_url( '/pricing' ),
	)
);
?>
<p class="dash-note">
	<?php esc_html_e( 'Want to change or cancel a plan? Tell us and we will take care of it.', 'bluegroup-project-blueworx' ); ?>
	<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php esc_html_e( 'Get in touch', 'bluegroup-project-blueworx' ); ?></a>
</p>
<?php
blueworx_public_part( 'parts/dash-end.php' );
