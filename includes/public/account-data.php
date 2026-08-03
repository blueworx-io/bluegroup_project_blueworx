<?php
/**
 * Public front-end layer — the client area's data.
 *
 * The dashboard's three sections (#38, #39, #40) each show one kind of record
 * belonging to the signed-in client, read live from SureCart. This file is the
 * only place that talks to SureCart on their behalf, so the rules that matter
 * live in one place rather than three:
 *
 * 1. **A client only ever sees their own records.** Every query is constrained
 *    to the SureCart customer resolved from the current WordPress user, and if
 *    that customer cannot be resolved the answer is an empty list — never an
 *    unconstrained query. A dashboard that falls back to "show everything" when
 *    it cannot identify you is the worst possible failure here, so the fallback
 *    is deliberately the other way.
 * 2. **"We could not load this" is not the same as "you have none."** Telling a
 *    paying client they have no subscriptions because an API call failed is
 *    worse than telling them something went wrong. Every accessor returns both
 *    the rows and whether the read actually succeeded, and the templates say
 *    different things for each.
 * 3. **Nothing here may break the page.** Every call into SureCart is wrapped;
 *    a failure becomes ok=false, never a fatal error on someone's dashboard.
 *
 * Records are normalised into plain arrays at this boundary so the templates
 * never touch a SureCart object. That keeps the guesswork about SureCart's
 * model shape — which is not verifiable without a live SureCart account — in
 * one file, behind filters, instead of spread across three templates.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * An empty result that reports the read did not succeed.
 *
 * @param bool $ok Whether the read worked.
 * @return array array( 'ok' => bool, 'rows' => array() )
 */
function blueworx_account_result( $ok, $rows = array() ) {
	return array(
		'ok'   => (bool) $ok,
		'rows' => (array) $rows,
	);
}

/**
 * The SureCart customer ID for the signed-in WordPress user.
 *
 * SureCart links a WordPress user to its own customer record, and where it
 * keeps that link has changed between versions — hence the ordered attempts
 * below rather than one lookup. They run cheapest-first and stop at the first
 * answer.
 *
 * Returns '' when there is no link, which every caller treats as "this person
 * has no records", not as "fetch everything".
 *
 * @return string Customer ID, or '' when there is none.
 */
function blueworx_account_customer_id() {
	static $resolved = null;

	if ( null !== $resolved ) {
		return $resolved;
	}

	$resolved  = '';
	$user_id   = get_current_user_id();

	if ( $user_id <= 0 ) {
		return $resolved;
	}

	/**
	 * Short-circuits customer resolution.
	 *
	 * The seam to use if SureCart moves the link again: return a customer ID
	 * and none of the attempts below run.
	 *
	 * @param string $customer_id Empty by default.
	 * @param int    $user_id     WordPress user ID.
	 */
	$filtered = (string) apply_filters( 'blueworx_account_customer_id', '', $user_id );

	if ( '' !== $filtered ) {
		$resolved = $filtered;
		return $resolved;
	}

	// SureCart keeps one customer per mode (live and test are separate
	// accounts with separate IDs), so the mode has to be part of the lookup —
	// reading the wrong one shows a client an empty dashboard on a site that
	// has their data, which looks exactly like data loss.
	$mode = 'live';

	if ( class_exists( '\SureCart\Models\ApiToken' ) && is_callable( array( '\SureCart\Models\ApiToken', 'getMode' ) ) ) {
		try {
			$mode = (string) \SureCart\Models\ApiToken::getMode();
		} catch ( \Throwable $e ) {
			$mode = 'live';
		}
	}

	$ids = get_user_meta( $user_id, 'sc_customer_ids', true );

	if ( is_array( $ids ) && ! empty( $ids[ $mode ] ) ) {
		$resolved = (string) $ids[ $mode ];
		return $resolved;
	}

	$single = (string) get_user_meta( $user_id, 'sc_customer_id', true );

	if ( '' !== $single ) {
		$resolved = $single;
	}

	return $resolved;
}

/**
 * Reads a list of the current client's records from a SureCart model.
 *
 * One function for all three sections: they differ only in the model class and
 * the field SureCart matches the customer on, and having the guard, the query
 * shape and the error handling written once means a section cannot quietly get
 * a weaker version of any of them.
 *
 * @param string   $class     Fully-qualified SureCart model class.
 * @param callable $normalise Turns one model into a plain row array.
 * @return array Result array — see blueworx_account_result().
 */
function blueworx_account_fetch( $class, $normalise ) {
	if ( ! class_exists( $class ) ) {
		return blueworx_account_result( false );
	}

	$customer_id = blueworx_account_customer_id();

	// No customer means no records. Deliberately a successful empty result
	// rather than an error: a signed-in user who has never bought anything is
	// not a failure, and the empty state is the right thing to show them.
	if ( '' === $customer_id ) {
		return blueworx_account_result( true );
	}

	try {
		$records = $class::where( array( 'customer_ids' => array( $customer_id ) ) )
			->orderBy( 'created_at' )
			->order( 'desc' )
			->get();

		$rows = array();

		foreach ( (array) $records as $record ) {
			$row = call_user_func( $normalise, $record );

			if ( is_array( $row ) ) {
				$rows[] = $row;
			}
		}

		return blueworx_account_result( true, $rows );
	} catch ( \Throwable $e ) {
		return blueworx_account_result( false );
	}
}

/**
 * Reads a property off a SureCart model without assuming it is there.
 *
 * @param mixed  $record  SureCart model.
 * @param string $name    Property name.
 * @param mixed  $default Returned when absent.
 * @return mixed
 */
function blueworx_account_prop( $record, $name, $default = '' ) {
	if ( is_object( $record ) && isset( $record->$name ) ) {
		return $record->$name;
	}

	if ( is_array( $record ) && isset( $record[ $name ] ) ) {
		return $record[ $name ];
	}

	return $default;
}

/**
 * Formats an amount held in a currency's minor unit.
 *
 * @param mixed  $amount   Amount in minor units (cents).
 * @param string $currency ISO currency code.
 * @return string Formatted amount, or '' when there is no figure.
 */
function blueworx_account_money( $amount, $currency = 'USD' ) {
	if ( ! is_numeric( $amount ) ) {
		return '';
	}

	$symbols = array(
		'USD' => '$',
		'GBP' => '£',
		'EUR' => '€',
	);

	$currency = strtoupper( (string) $currency );
	$symbol   = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';
	$value    = number_format_i18n( ( (float) $amount ) / 100, 2 );

	return '' === $symbol ? $value . ' ' . $currency : $symbol . $value;
}

/**
 * Formats a SureCart timestamp in the site's date format.
 *
 * @param mixed $timestamp Unix timestamp.
 * @return string Formatted date, or '' when there is none.
 */
function blueworx_account_date( $timestamp ) {
	if ( ! is_numeric( $timestamp ) || (int) $timestamp <= 0 ) {
		return '';
	}

	return date_i18n( (string) get_option( 'date_format', 'j F Y' ), (int) $timestamp );
}

/**
 * A status turned into something a customer would recognise.
 *
 * SureCart's own values are machine words ("past_due", "trialing"). Shown raw
 * they read as jargon at exactly the moment a client is trying to work out
 * whether something is wrong.
 *
 * @param string $status Raw status.
 * @return string
 */
function blueworx_account_status_label( $status ) {
	$labels = array(
		'active'    => __( 'Active', 'bluegroup-project-blueworx' ),
		'trialing'  => __( 'Trial', 'bluegroup-project-blueworx' ),
		'past_due'  => __( 'Payment overdue', 'bluegroup-project-blueworx' ),
		'canceled'  => __( 'Cancelled', 'bluegroup-project-blueworx' ),
		'cancelled' => __( 'Cancelled', 'bluegroup-project-blueworx' ),
		'paused'    => __( 'Paused', 'bluegroup-project-blueworx' ),
		'unpaid'    => __( 'Unpaid', 'bluegroup-project-blueworx' ),
		'paid'      => __( 'Paid', 'bluegroup-project-blueworx' ),
		'open'      => __( 'Awaiting payment', 'bluegroup-project-blueworx' ),
		'draft'     => __( 'Draft', 'bluegroup-project-blueworx' ),
		'void'      => __( 'Void', 'bluegroup-project-blueworx' ),
		'completed' => __( 'Completed', 'bluegroup-project-blueworx' ),
		'processing' => __( 'Processing', 'bluegroup-project-blueworx' ),
	);

	$status = (string) $status;

	if ( isset( $labels[ $status ] ) ) {
		return $labels[ $status ];
	}

	return '' === $status ? __( 'Unknown', 'bluegroup-project-blueworx' ) : ucfirst( str_replace( '_', ' ', $status ) );
}

/**
 * The current client's subscriptions (#38).
 *
 * @return array Result array; each row has name, status, amount, renews.
 */
function blueworx_account_subscriptions() {
	return blueworx_account_fetch(
		'\SureCart\Models\Subscription',
		function ( $record ) {
			$price   = blueworx_account_prop( $record, 'price', null );
			$product = $price ? blueworx_account_prop( $price, 'product', null ) : null;

			return array(
				'id'     => (string) blueworx_account_prop( $record, 'id' ),
				'name'   => (string) blueworx_account_prop( $product, 'name', __( 'Support plan', 'bluegroup-project-blueworx' ) ),
				'status' => (string) blueworx_account_prop( $record, 'status' ),
				'amount' => blueworx_account_money(
					blueworx_account_prop( $price, 'amount', null ),
					(string) blueworx_account_prop( $price, 'currency', 'USD' )
				),
				'renews' => blueworx_account_date( blueworx_account_prop( $record, 'current_period_end_at', 0 ) ),
			);
		}
	);
}

/**
 * The current client's invoices (#39).
 *
 * @return array Result array; each row has number, status, amount, date, url.
 */
function blueworx_account_invoices() {
	return blueworx_account_fetch(
		'\SureCart\Models\Invoice',
		function ( $record ) {
			return array(
				'id'     => (string) blueworx_account_prop( $record, 'id' ),
				'number' => (string) blueworx_account_prop( $record, 'number', blueworx_account_prop( $record, 'id' ) ),
				'status' => (string) blueworx_account_prop( $record, 'status' ),
				'amount' => blueworx_account_money(
					blueworx_account_prop( $record, 'total_amount', blueworx_account_prop( $record, 'amount_due', null ) ),
					(string) blueworx_account_prop( $record, 'currency', 'USD' )
				),
				'date'   => blueworx_account_date( blueworx_account_prop( $record, 'created_at', 0 ) ),
				// SureCart hosts the PDF itself, so downloading is a link to
				// SureCart rather than anything this plugin generates.
				'url'    => (string) blueworx_account_prop( $record, 'pdf_url', blueworx_account_prop( $record, 'url', '' ) ),
			);
		}
	);
}

/**
 * The current client's orders (#40).
 *
 * @return array Result array; each row has number, status, amount, date.
 */
function blueworx_account_orders() {
	return blueworx_account_fetch(
		'\SureCart\Models\Order',
		function ( $record ) {
			$checkout = blueworx_account_prop( $record, 'checkout', null );

			return array(
				'id'     => (string) blueworx_account_prop( $record, 'id' ),
				'number' => (string) blueworx_account_prop( $record, 'number', blueworx_account_prop( $record, 'id' ) ),
				'status' => (string) blueworx_account_prop( $record, 'status' ),
				'amount' => blueworx_account_money(
					blueworx_account_prop( $checkout, 'total_amount', null ),
					(string) blueworx_account_prop( $checkout, 'currency', 'USD' )
				),
				'date'   => blueworx_account_date( blueworx_account_prop( $record, 'created_at', 0 ) ),
			);
		}
	);
}
