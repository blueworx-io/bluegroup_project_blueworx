<?php
/**
 * Client dashboard — invoices (#39).
 *
 * Every invoice on the client's account, read live from SureCart.
 *
 * There is no PDF column: SureCart does not expose a PDF on an invoice, so one
 * would have been a column of dead links. What it does have is a payment page
 * for an unpaid invoice, which is the thing a client actually wants from this
 * page, so that is what an open invoice offers.
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
		'kicker'  => $blueworx_dash_sections['invoices']['kicker'],
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
			'pay'    => '',
		),
		'empty'   => __( 'There are no invoices on your account yet.', 'bluegroup-project-blueworx' ),
	)
);

blueworx_public_part( 'parts/dash-end.php' );
