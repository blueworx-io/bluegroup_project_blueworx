<?php
/**
 * Public front-end layer — currency exchange rates.
 *
 * Prices on the marketing pages are written in pounds. The visitor can view
 * them in euros, US dollars, rand, Australian dollars or dirhams (the
 * switcher in templates/parts/nav.php), and the conversion happens in the
 * browser (assets/js/public-widgets.js) from the rates this file hands it.
 *
 * The rates come from the European Central Bank's daily reference rates, via
 * the Frankfurter API (no key, no account). The ECB publishes no dirham rate,
 * so AED is worked out from the dollar figure at the UAE's fixed peg
 * (blueworx_currency_pegs()). They are fetched twice a day by
 * WP-Cron and kept in an option, so a visitor never waits on the rate
 * service; if the site has never managed a fetch, or the last good one is a
 * day old and a fresh attempt fails, the last known figures stay in use, and
 * failing everything the fixed rates below apply. A currency switcher that
 * shows nothing, or an error, would be worse than one that is a day stale.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The rates used when no live figure has ever been fetched.
 *
 * These are the launch figures the site went live with (2026-09) — safe
 * defaults, not a source of truth.
 *
 * @return array Rate per £1, keyed by ISO code.
 */
function blueworx_currency_fallback_rates() {
	return array(
		'EUR' => 1.17,
		'USD' => 1.27,
		'ZAR' => 21.8,
		'AUD' => 1.88,
		'AED' => 4.66,
	);
}

/**
 * Currencies the rate service does not publish, and how to derive them.
 *
 * The UAE dirham has been fixed at 3.6725 to the US dollar since 1997, so
 * the pound-to-dirham rate is simply the pound-to-dollar rate times the peg.
 * Anything listed here is left out of the fetch and computed afterwards.
 *
 * @return array Code => array( source code, multiplier ).
 */
function blueworx_currency_pegs() {
	return array(
		'AED' => array( 'USD', 3.6725 ),
	);
}

/**
 * The currency codes fetched from the rate service: every currency the site
 * offers, less the pegged ones.
 *
 * @return string[] ISO codes.
 */
function blueworx_currency_fetched_codes() {
	return array_values( array_diff( array_keys( blueworx_currency_fallback_rates() ), array_keys( blueworx_currency_pegs() ) ) );
}

/**
 * The URL the rates are fetched from.
 *
 * @return string
 */
function blueworx_currency_rates_url() {
	/**
	 * Filters where exchange rates are fetched from.
	 *
	 * The response must carry a `rates` object keyed by ISO code with the
	 * value of one pound in that currency, for every code in
	 * blueworx_currency_fetched_codes(). Return an empty string to switch
	 * live rates off and use blueworx_currency_fallback_rates() instead.
	 *
	 * @param string $url The rates endpoint.
	 */
	return (string) apply_filters( 'blueworx_currency_rates_url', 'https://api.frankfurter.dev/v1/latest?base=GBP&symbols=' . implode( ',', blueworx_currency_fetched_codes() ) );
}

/**
 * Fetches today's rates from the rate service.
 *
 * @return array|null `array( 'rates' => array( 'EUR' => 1.17, ... ), 'date' => 'YYYY-MM-DD' )`, or null when anything at all went wrong.
 */
function blueworx_currency_fetch_rates() {
	$url = blueworx_currency_rates_url();

	if ( '' === $url ) {
		return null;
	}

	$response = wp_remote_get(
		$url,
		array(
			// Short: on a site whose cron has lapsed this runs inside a page
			// load, and a stale rate beats a slow page.
			'timeout' => 5,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}

	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) || empty( $body['rates'] ) || ! is_array( $body['rates'] ) ) {
		return null;
	}

	$rates = array();

	foreach ( blueworx_currency_fetched_codes() as $code ) {
		if ( ! isset( $body['rates'][ $code ] ) || ! is_numeric( $body['rates'][ $code ] ) ) {
			return null;
		}

		$rate = (float) $body['rates'][ $code ];

		// A rate of zero, or a negative one, can only be a broken response;
		// painting every price as £0 on the strength of it would be far worse
		// than a stale figure.
		if ( $rate <= 0 ) {
			return null;
		}

		$rates[ $code ] = round( $rate, 4 );
	}

	foreach ( blueworx_currency_pegs() as $code => $peg ) {
		list( $source, $multiplier ) = $peg;

		if ( ! isset( $rates[ $source ] ) ) {
			return null;
		}

		$rates[ $code ] = round( $rates[ $source ] * $multiplier, 4 );
	}

	return array(
		'rates' => $rates,
		'date'  => isset( $body['date'] ) ? sanitize_text_field( (string) $body['date'] ) : '',
	);
}

/**
 * Fetches fresh rates and stores them if the fetch worked.
 *
 * Runs on the `blueworx_currency_refresh` cron event twice a day, and from
 * blueworx_currency_rates() when the stored figures are missing or old.
 *
 * @return bool Whether new rates were stored.
 */
function blueworx_currency_refresh() {
	$fetched = blueworx_currency_fetch_rates();

	if ( null === $fetched ) {
		return false;
	}

	update_option(
		'blueworx_currency_rates',
		array(
			'rates'   => $fetched['rates'],
			'date'    => $fetched['date'],
			'fetched' => time(),
		),
		false
	);

	return true;
}
add_action( 'blueworx_currency_refresh', 'blueworx_currency_refresh' );

/**
 * The rates in use right now, with where they came from.
 *
 * Reads the stored figures. When there are none, or they are more than a day
 * old (cron has not run — a site with no traffic overnight, or cron switched
 * off), one fetch is attempted on the spot, at most once an hour, so a
 * struggling rate service cannot slow every page load down.
 *
 * @return array {
 *     @type array  $rates  Rate per £1 keyed by ISO code, GBP included at 1.
 *     @type string $source 'live' when the figures came from the rate service, 'fallback' otherwise.
 *     @type string $date   The date the live figures are for, '' for fallback.
 * }
 */
function blueworx_currency_rates() {
	$stored = get_option( 'blueworx_currency_rates', array() );
	$stale  = empty( $stored['fetched'] ) || ( time() - (int) $stored['fetched'] ) > DAY_IN_SECONDS;

	// Figures stored by a release that offered fewer currencies are missing
	// the new ones; treat them as stale so the next request fills them in.
	if ( ! $stale && is_array( $stored ) && ! empty( $stored['rates'] ) && array_diff_key( blueworx_currency_fallback_rates(), (array) $stored['rates'] ) ) {
		$stale = true;
	}

	if ( $stale && ! get_transient( 'blueworx_currency_rates_lock' ) ) {
		set_transient( 'blueworx_currency_rates_lock', 1, HOUR_IN_SECONDS );

		if ( blueworx_currency_refresh() ) {
			$stored = get_option( 'blueworx_currency_rates', array() );
		}
	}

	$live = is_array( $stored ) && ! empty( $stored['rates'] ) && is_array( $stored['rates'] );

	// Live figures sit over the fallback ones, so a currency the stored set
	// predates still has a number until the refresh lands.
	$result = array(
		'rates'  => array_merge( array( 'GBP' => 1 ), blueworx_currency_fallback_rates(), $live ? $stored['rates'] : array() ),
		'source' => $live ? 'live' : 'fallback',
		'date'   => $live && ! empty( $stored['date'] ) ? (string) $stored['date'] : '',
	);

	/**
	 * Filters the exchange rates the site converts prices with.
	 *
	 * @param array $result Rates, source and date — see blueworx_currency_rates().
	 */
	return (array) apply_filters( 'blueworx_currency_rates', $result );
}

/**
 * Keeps the twice-daily refresh scheduled.
 *
 * Checked on every request rather than only on activation, so a site that
 * updated the plugin in place (no activation hook runs) still gets the
 * schedule. wp_next_scheduled() is a cheap option read.
 *
 * @return void
 */
function blueworx_currency_schedule_refresh() {
	if ( ! wp_next_scheduled( 'blueworx_currency_refresh' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'blueworx_currency_refresh' );
	}
}
add_action( 'init', 'blueworx_currency_schedule_refresh' );

/**
 * Drops the refresh schedule when the plugin is switched off.
 *
 * Called from blueworx_site_deactivate() in the main plugin file.
 *
 * @return void
 */
function blueworx_currency_unschedule_refresh() {
	$timestamp = wp_next_scheduled( 'blueworx_currency_refresh' );

	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'blueworx_currency_refresh' );
	}
}
