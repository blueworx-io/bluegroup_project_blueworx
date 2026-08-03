<?php
/**
 * Client dashboard — subscriptions (#38).
 *
 * The page and its route exist from #37; the live SureCart data arrives with
 * #38. Until then it says so plainly rather than showing an empty table.
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
		'blurb'   => $blueworx_dash_sections['subscriptions']['blurb'],
	)
);

blueworx_public_part(
	'parts/dash-empty.php',
	array(
		'message' => __( 'You have no active plans on your account yet.', 'bluegroup-project-blueworx' ),
		'cta'     => __( 'See our support plans', 'bluegroup-project-blueworx' ),
		'href'    => home_url( '/pricing' ),
	)
);

blueworx_public_part( 'parts/dash-end.php' );
