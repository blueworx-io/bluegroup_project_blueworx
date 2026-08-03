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
 * 3. **Nothing here may break the page.** Every call is both wrapped and
 *    error-checked — see below — so a failure becomes ok=false, never a fatal
 *    error on someone's dashboard.
 *
 * **SureCart reports failure by RETURN VALUE, not by exception.** Its models
 * hand back a WP_Error from find() and get() when the API call fails. A
 * try/catch alone therefore catches nothing, and the WP_Error falls through as
 * if it were data — which is how a failed request ends up rendering as an empty
 * account. Every call here is is_wp_error()-checked for that reason; the
 * try/catch is kept as well, for the fatal a WP_Error check cannot help with.
 *
 * Two more things that are not guessable from the outside, and cost a working
 * dashboard if got wrong:
 *
 * - **Related records must be asked for.** Without `with( [ 'price',
 *   'price.product' ] )` a subscription's price is an ID string, not an object,
 *   so every plan renders as the fallback name.
 * - **Amounts are not simply cents.** Zero-decimal currencies have no minor
 *   unit, so SureCart's own `display_amount` and `converted_amount` are used
 *   rather than dividing by 100 here.
 *
 * Records are normalised into plain arrays at this boundary so the templates
 * never touch a SureCart object.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A result, reporting both what was found and whether the read succeeded.
 *
 * @param bool  $ok   Whether the read worked.
 * @param array $rows The records.
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
 * Asks SureCart rather than reading its user meta directly: it keeps one
 * customer per mode (live and test are separate accounts with separate IDs),
 * and its own resolver already handles that and the older single-value shape.
 * Reading the wrong one shows a client an empty dashboard on a site that has
 * their data, which looks exactly like data loss.
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

	$resolved = '';
	$user_id  = get_current_user_id();

	if ( $user_id <= 0 ) {
		return $resolved;
	}

	/**
	 * Short-circuits customer resolution.
	 *
	 * @param string $customer_id Empty by default.
	 * @param int    $user_id     WordPress user ID.
	 */
	$filtered = (string) apply_filters( 'blueworx_account_customer_id', '', $user_id );

	if ( '' !== $filtered ) {
		$resolved = $filtered;
		return $resolved;
	}

	if ( class_exists( '\SureCart\Models\User' ) ) {
		try {
			$customer_id = \SureCart\Models\User::current()->customerId();

			if ( is_string( $customer_id ) && '' !== $customer_id ) {
				$resolved = $customer_id;
				return $resolved;
			}
		} catch ( \Throwable $e ) {
			$resolved = '';
		}
	}

	// Fallback for a SureCart that is absent or has moved its resolver: the
	// meta key it has stored the link under for several major versions.
	$ids = get_user_meta( $user_id, 'sc_customer_ids', true );

	if ( is_array( $ids ) ) {
		foreach ( array( 'live', 0 ) as $key ) {
			if ( ! empty( $ids[ $key ] ) ) {
				$resolved = (string) $ids[ $key ];
				break;
			}
		}
	}

	return $resolved;
}

/**
 * Reads a list of the current client's records from a SureCart model.
 *
 * One function for all three sections: they differ only in the model, what
 * they need expanded, and how a row is shaped. Having the guard, the query and
 * the error handling written once means a section cannot quietly get a weaker
 * version of any of them.
 *
 * @param string   $class     Fully-qualified SureCart model class.
 * @param array    $expand    Related records to ask SureCart to include.
 * @param callable $normalise Turns one model into a plain row array.
 * @return array Result array — see blueworx_account_result().
 */
function blueworx_account_fetch( $class, $expand, $normalise ) {
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
			->with( $expand )
			->get();

		// The check that matters. Without it a failed request is iterated as
		// though it were a list of records.
		if ( is_wp_error( $records ) || ! is_array( $records ) ) {
			return blueworx_account_result( false );
		}

		$rows = array();

		foreach ( $records as $record ) {
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
 * SureCart resolves many attributes through __get, so a missing one is null
 * rather than a notice — but a missing RELATION is null too, and reading
 * through it would be fatal. Callers chain through this rather than with `->`.
 *
 * @param mixed  $record  SureCart model, object or array.
 * @param string $name    Property name.
 * @param mixed  $default Returned when absent or empty.
 * @return mixed
 */
function blueworx_account_prop( $record, $name, $default = '' ) {
	if ( is_object( $record ) ) {
		try {
			$value = $record->$name;
		} catch ( \Throwable $e ) {
			return $default;
		}

		return ( null === $value || '' === $value ) ? $default : $value;
	}

	if ( is_array( $record ) && isset( $record[ $name ] ) ) {
		return $record[ $name ];
	}

	return $default;
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
		'active'     => __( 'Active', 'bluegroup-project-blueworx' ),
		'trialing'   => __( 'Trial', 'bluegroup-project-blueworx' ),
		'past_due'   => __( 'Payment overdue', 'bluegroup-project-blueworx' ),
		'canceled'   => __( 'Cancelled', 'bluegroup-project-blueworx' ),
		'cancelled'  => __( 'Cancelled', 'bluegroup-project-blueworx' ),
		'paused'     => __( 'Paused', 'bluegroup-project-blueworx' ),
		'unpaid'     => __( 'Unpaid', 'bluegroup-project-blueworx' ),
		'paid'       => __( 'Paid', 'bluegroup-project-blueworx' ),
		'open'       => __( 'Awaiting payment', 'bluegroup-project-blueworx' ),
		'draft'      => __( 'Draft', 'bluegroup-project-blueworx' ),
		'void'       => __( 'Cancelled', 'bluegroup-project-blueworx' ),
		'completed'  => __( 'Completed', 'bluegroup-project-blueworx' ),
		'processing' => __( 'Processing', 'bluegroup-project-blueworx' ),
		'payment_failed' => __( 'Payment failed', 'bluegroup-project-blueworx' ),
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
		// Without these the price is an ID string and every plan renders as the
		// fallback name.
		array( 'price', 'price.product' ),
		function ( $record ) {
			$price   = blueworx_account_prop( $record, 'price', null );
			$product = $price ? blueworx_account_prop( $price, 'product', null ) : null;

			// SureCart formats the amount with its currency and interval
			// itself, which is also what makes this correct for currencies
			// with no minor unit.
			$amount   = (string) blueworx_account_prop( $record, 'ad_hoc_display_amount', '' );
			$interval = '';

			if ( '' === $amount && $price ) {
				$amount   = (string) blueworx_account_prop( $price, 'display_amount', '' );
				$interval = trim( (string) blueworx_account_prop( $price, 'interval_text', '' ) );
			}

			return array(
				'id'     => (string) blueworx_account_prop( $record, 'id' ),
				'name'   => (string) blueworx_account_prop( $product, 'name', __( 'Support plan', 'bluegroup-project-blueworx' ) ),
				'status' => (string) blueworx_account_prop( $record, 'status' ),
				'amount' => trim( $amount . ( '' === $interval ? '' : ' ' . $interval ) ),
				'renews' => (string) blueworx_account_prop( $record, 'current_period_end_at_date', '' ),
			);
		}
	);
}

/**
 * The current client's invoices (#39).
 *
 * There is deliberately no PDF column. SureCart does not expose a PDF on the
 * invoice — what it has is a payment page for an unpaid one — so a Download
 * link here would have been a column of dead links. An open invoice gets a
 * "Pay now" link instead, which is the thing a client actually wants from this
 * page.
 *
 * @return array Result array; each row has number, date, status, amount, pay.
 */
function blueworx_account_invoices() {
	return blueworx_account_fetch(
		'\SureCart\Models\Invoice',
		array( 'checkout', 'checkout.order' ),
		function ( $record ) {
			$checkout = blueworx_account_prop( $record, 'checkout', null );
			$order    = $checkout ? blueworx_account_prop( $checkout, 'order', null ) : null;
			$status   = (string) blueworx_account_prop( $record, 'status' );

			return array(
				'id'     => (string) blueworx_account_prop( $record, 'id' ),
				'number' => (string) blueworx_account_prop( $order, 'number', __( 'Draft', 'bluegroup-project-blueworx' ) ),
				'date'   => (string) blueworx_account_prop( $record, 'issue_date_date', '' ),
				'status' => $status,
				'amount' => (string) blueworx_account_prop( $checkout, 'total_display_amount', '' ),
				'pay'    => 'open' === $status ? (string) blueworx_account_prop( $record, 'checkout_url', '' ) : '',
			);
		}
	);
}

/**
 * The current client's orders (#40).
 *
 * @return array Result array; each row has number, date, status, amount.
 */
function blueworx_account_orders() {
	return blueworx_account_fetch(
		'\SureCart\Models\Order',
		array( 'checkout' ),
		function ( $record ) {
			$checkout = blueworx_account_prop( $record, 'checkout', null );

			return array(
				'id'     => (string) blueworx_account_prop( $record, 'id' ),
				'number' => (string) blueworx_account_prop( $record, 'number', blueworx_account_prop( $record, 'id' ) ),
				'date'   => blueworx_account_date( blueworx_account_prop( $record, 'created_at', 0 ) ),
				'status' => (string) blueworx_account_prop( $record, 'status' ),
				'amount' => (string) blueworx_account_prop( $checkout, 'total_display_amount', '' ),
			);
		}
	);
}
