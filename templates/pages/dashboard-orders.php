<?php
/**
 * Client dashboard — orders (#40).
 *
 * Everything the client has ordered, read live from SureCart.
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
		'section' => 'orders',
		'heading' => $blueworx_dash_sections['orders']['title'],
		'kicker'  => $blueworx_dash_sections['orders']['kicker'],
	)
);

blueworx_public_part(
	'parts/dash-table.php',
	array(
		'result'  => blueworx_account_orders(),
		'columns' => array(
			'number' => __( 'Order', 'bluegroup-project-blueworx' ),
			'date'   => __( 'Date', 'bluegroup-project-blueworx' ),
			'status' => __( 'Status', 'bluegroup-project-blueworx' ),
			'amount' => __( 'Total', 'bluegroup-project-blueworx' ),
		),
		'empty'   => __( 'You have not placed any orders yet.', 'bluegroup-project-blueworx' ),
		'cta'     => __( 'See our support plans', 'bluegroup-project-blueworx' ),
		'href'    => home_url( '/pricing' ),
	)
);

blueworx_public_part( 'parts/dash-end.php' );
