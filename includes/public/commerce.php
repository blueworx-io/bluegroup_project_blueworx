<?php
/**
 * Public front-end layer — commerce.
 *
 * The Pricing page's plan names, prices and buttons were all written into the
 * plugin (see blueworx_content_retainer_plans()). That is fine until somebody
 * changes a price in SureCart, at which point the site advertises one figure
 * and charges another — and the "Get started" buttons went to the contact form,
 * so nobody could buy a plan from the pricing page at all (#41).
 *
 * This file is the seam between the two. Given a SureCart price ID per plan per
 * billing interval, it reads the real amount from SureCart and points the
 * button at a SureCart checkout for that price.
 *
 * Two rules run through all of it, and they are the reason for the shape:
 *
 * 1. **The page must never break.** SureCart may be deactivated, its API may be
 *    down, a price ID may be wrong or deleted. Every one of those falls back to
 *    the hardcoded figure and the contact-form button — the behaviour before
 *    this file existed. A pricing page that renders last week's price is a
 *    problem; a pricing page that renders a fatal error is a worse one.
 * 2. **Nothing is read from WordPress.** SureCart keeps products and prices in
 *    its own cloud, not as WordPress posts, so the `sc_product` post type is
 *    empty on this site and a WP_Query for it quietly returns nothing. Prices
 *    come through SureCart's models, which call its API.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BLUEWORX_COMMERCE_PRICE_CACHE = 'blueworx_commerce_price_amounts';

/**
 * How long a price read from SureCart is trusted, in seconds.
 *
 * Fifteen minutes: long enough that the pricing page is not making an API call
 * per visitor, short enough that a price change in SureCart shows up on the
 * site while whoever made it is still looking at it.
 */
const BLUEWORX_COMMERCE_PRICE_TTL = 900;

/**
 * Whether SureCart is active and its price model is usable.
 *
 * @return bool
 */
function blueworx_commerce_ready() {
	return class_exists( '\SureCart\Models\Price' );
}

/**
 * The configured SureCart price IDs, keyed by plan slug.
 *
 * Shape: array( 'growth-support' => array( 'm' => 'price_x', 'a' => 'price_y' ) ).
 * Any plan, or any interval within a plan, may be absent — that plan simply
 * keeps its hardcoded price and its contact-form button.
 *
 * @return array
 */
function blueworx_commerce_price_ids() {
	$stored = get_option( 'blueworx_surecart_price_ids', array() );

	return is_array( $stored ) ? $stored : array();
}

/**
 * The plan slug used to look a plan up in the price-ID map.
 *
 * Derived from the plan name rather than stored alongside it, so adding a plan
 * to the content file does not also mean editing a second list.
 *
 * @param string $name Plan name, e.g. "Growth Support".
 * @return string Slug, e.g. "growth-support".
 */
function blueworx_commerce_plan_slug( $name ) {
	return sanitize_title( (string) $name );
}

/**
 * The base URL of the SureCart checkout page.
 *
 * @return string
 */
function blueworx_commerce_checkout_url() {
	$configured = (string) get_option( 'blueworx_checkout_url', '' );

	if ( '' === $configured ) {
		$configured = '/checkout';
	}

	if ( ! wp_parse_url( $configured, PHP_URL_SCHEME ) ) {
		$configured = home_url( $configured );
	}

	/**
	 * Filters the checkout URL the plan buttons point at.
	 *
	 * @param string $configured Absolute checkout URL.
	 */
	return (string) apply_filters( 'blueworx_commerce_checkout_url', $configured );
}

/**
 * A SureCart buy link for a single price.
 *
 * SureCart's checkout reads the line items it should start with off the query
 * string, so a buy link is the checkout page plus the price ID — no cart state
 * and no session needed for the visitor to land on a checkout for the plan they
 * clicked.
 *
 * @param string $price_id SureCart price ID.
 * @return string Absolute URL.
 */
function blueworx_commerce_buy_url( $price_id ) {
	// add_query_arg() encodes both the key and the value, so the brackets
	// arrive percent-encoded and the ID must NOT be pre-encoded here — doing
	// both would send SureCart an ID with %-escapes in it.
	return add_query_arg(
		array(
			'line_items[0][price]'    => (string) $price_id,
			'line_items[0][quantity]' => 1,
		),
		blueworx_commerce_checkout_url()
	);
}

/**
 * The amount SureCart holds for a price, in whole currency units.
 *
 * Returns null whenever the figure cannot be established — SureCart inactive,
 * ID missing or unknown, API unreachable, or an amount that is not a number.
 * Every one of those means "use the hardcoded price", which is what the caller
 * does with null.
 *
 * SureCart stores amounts in the currency's minor unit (cents), while the
 * pricing page renders whole units, so the value is divided and rounded here
 * rather than in the template.
 *
 * @param string $price_id SureCart price ID.
 * @return int|null Amount in whole currency units, or null.
 */
function blueworx_commerce_price_amount( $price_id ) {
	$price_id = (string) $price_id;

	if ( '' === $price_id || ! blueworx_commerce_ready() ) {
		return null;
	}

	$cache = get_transient( BLUEWORX_COMMERCE_PRICE_CACHE );
	$cache = is_array( $cache ) ? $cache : array();

	// A cached null is a real answer — it means the last lookup failed, and
	// repeating a failing API call once per visitor is how a slow API becomes a
	// slow site. array_key_exists(), not isset(), so null is seen as cached.
	if ( array_key_exists( $price_id, $cache ) ) {
		return $cache[ $price_id ];
	}

	$amount = null;

	try {
		$price = \SureCart\Models\Price::find( $price_id );

		// SureCart reports a failed lookup by RETURNING a WP_Error, not by
		// throwing one, so the try/catch alone would let it through as data.
		if ( is_wp_error( $price ) ) {
			$price = null;
		}

		// converted_amount, not amount / 100: a zero-decimal currency has no
		// minor unit, and dividing there would show a price a hundred times
		// too small. SureCart's own accessor knows which is which.
		if ( $price && is_numeric( $price->converted_amount ) ) {
			$amount = (int) round( (float) $price->converted_amount );
		}
	} catch ( \Throwable $e ) {
		// Deliberately swallowed. A pricing page is not the place to surface a
		// billing API's error, and $amount stays null, which falls back.
		$amount = null;
	}

	$cache[ $price_id ] = $amount;
	set_transient( BLUEWORX_COMMERCE_PRICE_CACHE, $cache, BLUEWORX_COMMERCE_PRICE_TTL );

	return $amount;
}

/**
 * Drops the cached price amounts.
 *
 * Hooked to the price-ID option so that correcting a wrong ID takes effect on
 * the next page view rather than up to fifteen minutes later, which would look
 * exactly like the correction not having worked.
 *
 * @return void
 */
function blueworx_commerce_flush_price_cache() {
	delete_transient( BLUEWORX_COMMERCE_PRICE_CACHE );
}
add_action( 'update_option_blueworx_surecart_price_ids', 'blueworx_commerce_flush_price_cache' );
add_action( 'add_option_blueworx_surecart_price_ids', 'blueworx_commerce_flush_price_cache' );

/**
 * Overlays live SureCart prices and buy links onto the retainer plans.
 *
 * Runs on the same filter the content file exposes, so the Pricing and Toolbox
 * pages both get it without either template changing how it asks for plans.
 *
 * A plan is only ever partly overlaid: a monthly price that resolves and an
 * annual one that does not leaves the annual figure hardcoded. That is
 * deliberate — half-live pricing is still closer to the truth than none, and
 * the alternative is one bad ID silently reverting the whole page.
 *
 * @param array $plans Plans from blueworx_content_retainer_plans().
 * @return array
 */
function blueworx_commerce_apply_live_plans( $plans ) {
	if ( ! is_array( $plans ) || ! blueworx_commerce_ready() ) {
		return $plans;
	}

	$ids = blueworx_commerce_price_ids();

	if ( empty( $ids ) ) {
		return $plans;
	}

	foreach ( $plans as $index => $plan ) {
		if ( empty( $plan['name'] ) ) {
			continue;
		}

		$slug = blueworx_commerce_plan_slug( $plan['name'] );

		if ( empty( $ids[ $slug ] ) || ! is_array( $ids[ $slug ] ) ) {
			continue;
		}

		foreach ( array(
			'm' => 'priceM',
			'a' => 'priceA',
		) as $interval => $key ) {
			$price_id = isset( $ids[ $slug ][ $interval ] ) ? (string) $ids[ $slug ][ $interval ] : '';

			if ( '' === $price_id ) {
				continue;
			}

			$amount = blueworx_commerce_price_amount( $price_id );

			if ( null !== $amount ) {
				$plans[ $index ][ $key ] = $amount;
			}

			// The buy link is set from a configured ID whether or not the
			// amount could be read. A price that exists in SureCart but whose
			// amount this request failed to fetch is still buyable, and
			// checkout shows the authoritative figure anyway.
			$plans[ $index ][ 'm' === $interval ? 'buyM' : 'buyA' ] = blueworx_commerce_buy_url( $price_id );
		}
	}

	return $plans;
}
add_filter( 'blueworx_content_retainer_plans', 'blueworx_commerce_apply_live_plans' );
