<?php
/**
 * Client dashboard — invoices (#39).
 *
 * The page and its route exist from #37; the live SureCart data arrives with
 * #39.
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
		'section' => 'invoices',
		'heading' => $blueworx_dash_sections['invoices']['title'],
		'blurb'   => $blueworx_dash_sections['invoices']['blurb'],
	)
);

blueworx_public_part(
	'parts/dash-empty.php',
	array(
		'message' => __( 'There are no invoices on your account yet.', 'bluegroup-project-blueworx' ),
	)
);

blueworx_public_part( 'parts/dash-end.php' );
