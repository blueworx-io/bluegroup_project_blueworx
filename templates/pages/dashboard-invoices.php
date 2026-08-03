<?php
/**
 * Client dashboard — invoices (#39).
 *
 * Every invoice on the client's account, read live from SureCart, each linking
 * to SureCart's own PDF rather than to anything this plugin generates.
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
	'parts/dash-table.php',
	array(
		'result'  => blueworx_account_invoices(),
		'columns' => array(
			'number' => __( 'Invoice', 'bluegroup-project-blueworx' ),
			'date'   => __( 'Date', 'bluegroup-project-blueworx' ),
			'status' => __( 'Status', 'bluegroup-project-blueworx' ),
			'amount' => __( 'Amount', 'bluegroup-project-blueworx' ),
			'url'    => __( 'PDF', 'bluegroup-project-blueworx' ),
		),
		'empty'   => __( 'There are no invoices on your account yet.', 'bluegroup-project-blueworx' ),
	)
);

blueworx_public_part( 'parts/dash-end.php' );
