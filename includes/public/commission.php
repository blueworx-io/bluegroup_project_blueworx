<?php
/**
 * Public front-end layer — what a sale pays a salesperson.
 *
 * The rates and the threshold live here, in one place, because they are the
 * part somebody will change: a rate written into the page's JavaScript is a
 * rate that has to be found again a year later, and one copied into both is a
 * rate that will disagree with itself.
 *
 * The prices are NOT here. They are read from the same table the Support,
 * Hosting and ClubHouse pages are drawn from (includes/public/content.php), so
 * a price change moves the public site and the calculator together. Deliberately
 * the plugin's list prices rather than the live SureCart ones: a quote is made
 * against the price list, and a missing price ID or a slow API should not
 * quietly change the number a salesperson promises somebody.
 *
 * The calculation is done twice — once here and once in assets/js/commission.js
 * — and that is on purpose. The page has to show a correct sale before its
 * JavaScript runs, and every control then recalculates in the browser with no
 * round trip. blueworx_commission_summary() and the `calc()` in that file are
 * the same seven lines; tests/commission.spec.js checks the numbers the two
 * agree on.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The commission rates, and the annual value a support package earns the
 * higher one at.
 *
 * `threshold` is inclusive: a package worth exactly £9,000 a year earns 20%.
 *
 * @return array
 */
function blueworx_commission_rates() {
	/**
	 * Filters the commission rates.
	 *
	 * @param array $rates seat, support_low, support_high, threshold.
	 */
	return (array) apply_filters(
		'blueworx_commission_rates',
		array(
			'seat'         => 0.20,
			'support_low'  => 0.10,
			'support_high' => 0.20,
			'threshold'    => 9000,
		)
	);
}

/**
 * The two per-site products, priced yearly and monthly.
 *
 * Monthly billing is counted as twelve months of first-year value, which is
 * what makes a monthly ClubHouse worth £240 rather than £200.
 *
 * @return array Key => array( name, year, month ).
 */
function blueworx_commission_products() {
	$hosting   = blueworx_content_hosting();
	$clubhouse = blueworx_content_clubhouse();

	return array(
		'hosting'   => array(
			'name'  => __( 'Website Hosting', 'bluegroup-project-blueworx' ),
			'year'  => (float) $hosting['plan']['priceA'],
			'month' => (float) $hosting['plan']['priceM'],
		),
		'clubhouse' => array(
			'name'  => __( 'ClubHouse', 'bluegroup-project-blueworx' ),
			// The £499 ClubHouse setup fee is deliberately not here. Commission
			// is paid on the subscription; a one-off build fee is not a sale
			// that renews and is not part of the rate.
			'year'  => (float) $clubhouse['plan']['priceA'],
			'month' => (float) $clubhouse['plan']['priceM'],
		),
	);
}

/**
 * The support packages, in the shape the calculator needs.
 *
 * Keyed by the same slug the SureCart price settings use, so a package is
 * called the same thing everywhere in this plugin.
 *
 * @return array Slug => array( name, hours, month, annual, rate ).
 */
function blueworx_commission_packages() {
	$rates    = blueworx_commission_rates();
	$packages = array();

	foreach ( blueworx_content_support_packages() as $package ) {
		$monthly = (float) $package['priceM'];
		$annual  = $monthly * 12;

		$packages[ blueworx_commerce_plan_slug( $package['name'] ) ] = array(
			'name'   => $package['name'],
			'hours'  => (int) $package['hours'],
			'month'  => $monthly,
			'annual' => $annual,
			'rate'   => $annual >= $rates['threshold'] ? $rates['support_high'] : $rates['support_low'],
		);
	}

	return $packages;
}

/**
 * The sale the page opens on.
 *
 * Growth is the default package for the same reason it is the highlighted card
 * on the Support page: it is the one most sales start from.
 *
 * @return array
 */
function blueworx_commission_default_sale() {
	return array(
		'hosting'    => array(
			'qty'     => 1,
			'billing' => 'year',
		),
		'clubhouse'  => array(
			'qty'     => 1,
			'billing' => 'year',
		),
		'support'    => 'growth',
		'supportQty' => 1,
	);
}

/**
 * Works out what a sale pays.
 *
 * First-year value only: a renewal earns nothing, so nothing here is multiplied
 * out over a subscription's life.
 *
 * The threshold is tested against ONE package's annual value, not against the
 * combined value of several — two Growth packages are two £6,000 sales at 10%,
 * not one £12,000 sale at 20%.
 *
 * @param array $sale A sale in blueworx_commission_default_sale()'s shape.
 * @return array lines (per product), commission, value.
 */
function blueworx_commission_summary( $sale ) {
	$rates    = blueworx_commission_rates();
	$products = blueworx_commission_products();
	$packages = blueworx_commission_packages();

	$lines      = array();
	$commission = 0.0;
	$value      = 0.0;

	foreach ( $products as $key => $product ) {
		$qty     = isset( $sale[ $key ]['qty'] ) ? max( 0, (int) $sale[ $key ]['qty'] ) : 0;
		$billing = isset( $sale[ $key ]['billing'] ) && 'month' === $sale[ $key ]['billing'] ? 'month' : 'year';

		if ( 0 === $qty ) {
			continue;
		}

		// Twelve months of the monthly price, or one year of the yearly one.
		$each = 'month' === $billing ? $product['month'] * 12 : $product['year'];
		$line = $each * $qty;

		$lines[ $key ] = array(
			'name'       => $product['name'],
			'qty'        => $qty,
			'billing'    => $billing,
			'unit'       => 'month' === $billing ? $product['month'] : $product['year'],
			'value'      => $line,
			'rate'       => $rates['seat'],
			'commission' => $line * $rates['seat'],
		);

		$value      += $line;
		$commission += $line * $rates['seat'];
	}

	$package_id  = isset( $sale['support'] ) ? (string) $sale['support'] : '';
	$support_qty = isset( $sale['supportQty'] ) ? max( 0, (int) $sale['supportQty'] ) : 0;

	if ( isset( $packages[ $package_id ] ) && $support_qty > 0 ) {
		$package = $packages[ $package_id ];
		$line    = $package['annual'] * $support_qty;

		$lines['support'] = array(
			'name'       => __( 'Integrated Support', 'bluegroup-project-blueworx' ),
			'package'    => $package['name'],
			'qty'        => $support_qty,
			'unit'       => $package['month'],
			'annual'     => $package['annual'],
			'value'      => $line,
			'rate'       => $package['rate'],
			'commission' => $line * $package['rate'],
		);

		$value      += $line;
		$commission += $line * $package['rate'];
	}

	return array(
		'lines'      => $lines,
		'commission' => $commission,
		'value'      => $value,
	);
}

/**
 * Formats an amount the way the rest of the calculator does.
 *
 * Whole pounds carry no decimals; anything else carries two. A commission of
 * £680 is £680, and a month of it is £56.67.
 *
 * @param float $amount Amount in pounds.
 * @return string
 */
function blueworx_commission_money( $amount ) {
	$amount   = (float) $amount;
	$decimals = ( abs( $amount - round( $amount ) ) < 0.005 ) ? 0 : 2;

	return '£' . number_format( $amount, $decimals );
}

/**
 * Everything the browser needs to recalculate without asking the server.
 *
 * @return array
 */
function blueworx_commission_payload() {
	return array(
		'products' => blueworx_commission_products(),
		'packages' => blueworx_commission_packages(),
		'rates'    => blueworx_commission_rates(),
		'defaults' => blueworx_commission_default_sale(),
	);
}
