<?php
/**
 * Client dashboard — orders (#40).
 *
 * The page and its route exist from #37; the live SureCart data arrives with
 * #40.
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
		'blurb'   => $blueworx_dash_sections['orders']['blurb'],
	)
);

blueworx_public_part(
	'parts/dash-empty.php',
	array(
		'message' => __( 'You have not placed any orders yet.', 'bluegroup-project-blueworx' ),
		'cta'     => __( 'See our support plans', 'bluegroup-project-blueworx' ),
		'href'    => home_url( '/pricing' ),
	)
);

blueworx_public_part( 'parts/dash-end.php' );
