/*
 * BlueWorx commission calculator (#112).
 *
 * The page arrives with the default sale already worked out in PHP
 * (includes/public/commission.php), so this never has to draw the first
 * screen — it takes over from it. Every control recalculates here rather than
 * asking the server, because a salesperson on the phone is moving quantities
 * around while they talk.
 *
 * The sums are the same seven lines as blueworx_commission_summary(). They are
 * written twice on purpose: once so the page is right before this file loads,
 * once so it stays right without a round trip. tests/commission.spec.js checks
 * the numbers both must agree on.
 *
 * Prices, packages and rates are printed into the page by PHP as
 * window.blueworxCommission — nothing here knows what anything costs.
 */
( function () {
	'use strict';

	var data = window.blueworxCommission;

	if ( ! data || ! data.products ) {
		return;
	}

	/**
	 * Formats an amount the way the server does: whole pounds carry no
	 * decimals, anything else carries two.
	 *
	 * @param {number} amount Amount in pounds.
	 * @return {string} e.g. "£680" or "£56.67".
	 */
	function money( amount ) {
		var whole = Math.abs( amount - Math.round( amount ) ) < 0.005;

		return '£' + amount.toLocaleString( 'en-GB', {
			minimumFractionDigits: whole ? 0 : 2,
			maximumFractionDigits: whole ? 0 : 2,
		} );
	}

	/**
	 * A percentage for display: 0.2 reads as "20".
	 *
	 * @param {number} rate Rate as a fraction.
	 * @return {string}
	 */
	function percent( rate ) {
		return String( Math.round( rate * 100 ) );
	}

	/**
	 * Works out what a sale pays.
	 *
	 * First-year value only, and the support threshold is tested against one
	 * package's annual value rather than the combined total — two Growth
	 * packages are two £6,000 sales at 10%, not one £12,000 sale at 20%.
	 *
	 * @param {Object} sale The current sale.
	 * @return {Object} lines, commission, value.
	 */
	function summarise( sale ) {
		var lines = {};
		var commission = 0;
		var value = 0;

		Object.keys( data.products ).forEach( function ( key ) {
			var product = data.products[ key ];
			var qty = sale[ key ].qty;
			var monthly = 'month' === sale[ key ].billing;
			var unit = monthly ? product.month : product.year;
			var line = ( monthly ? product.month * 12 : product.year ) * qty;

			if ( ! qty ) {
				return;
			}

			lines[ key ] = {
				name: product.name,
				qty: qty,
				unit: unit,
				monthly: monthly,
				value: line,
				rate: data.rates.seat,
				commission: line * data.rates.seat,
			};

			value += line;
			commission += line * data.rates.seat;
		} );

		var pkg = data.packages[ sale.support ];

		if ( pkg && sale.supportQty > 0 ) {
			var supportValue = pkg.annual * sale.supportQty;

			lines.support = {
				name: 'Integrated Support',
				pkg: pkg,
				qty: sale.supportQty,
				unit: pkg.month,
				annual: pkg.annual,
				value: supportValue,
				rate: pkg.rate,
				commission: supportValue * pkg.rate,
			};

			value += supportValue;
			commission += supportValue * pkg.rate;
		}

		return { lines: lines, commission: commission, value: value };
	}

	/**
	 * Draws one product row's commission and the rate line under it.
	 *
	 * @param {Element} root The calculator.
	 * @param {string}  key  Row key.
	 * @param {Object}  line The line, or undefined when the product is out.
	 */
	function paintRow( root, key, line ) {
		var row = root.querySelector( '[data-row="' + key + '"]' );

		if ( ! row ) {
			return;
		}

		var fee = row.querySelector( '.comm-row-fee' );
		var rate = row.querySelector( '.comm-row-rate' );

		fee.textContent = money( line ? line.commission : 0 );

		if ( ! line ) {
			rate.textContent = 'Not included';
			return;
		}

		var annual = 'support' === key ? line.annual : line.value;
		var text = percent( line.rate ) + '% of ' + money( annual ) + ' a year';

		// The support row prices one package and then multiplies, so its
		// quantity has to be said rather than folded into the figure.
		if ( 'support' === key && line.qty > 1 ) {
			text += ' × ' + line.qty;
		}

		rate.textContent = text;
	}

	/**
	 * Draws the support row's meta line and its tier hint.
	 *
	 * @param {Element} root The calculator.
	 * @param {Object}  sale The current sale.
	 */
	function paintSupport( root, sale ) {
		var pkg = data.packages[ sale.support ];
		var meta = root.querySelector( '[data-support-meta]' );
		var hint = root.querySelector( '.comm-hint' );
		var hintText = root.querySelector( '[data-hint]' );

		if ( ! pkg ) {
			meta.textContent = 'Pick a package — priced monthly, billed over 12 months';
			hint.hidden = true;
			return;
		}

		var perHour = pkg.annual / ( pkg.hours || 1 );
		var monthlyHours = String( Math.round( ( pkg.hours / 12 ) * 100 ) / 100 );

		meta.textContent = pkg.hours + ' hrs a year · ' + monthlyHours + ' hrs a month · ' + money( perHour ) + ' / hr';

		hint.hidden = false;
		hintText.textContent = pkg.annual < data.rates.threshold
			? money( data.rates.threshold - pkg.annual ) + ' more in annual value moves this to the 20% rate.'
			: 'At ' + money( pkg.annual ) + ' a year this package earns the higher 20% rate.';
	}

	/**
	 * Draws the totals card: the headline figure, the line under it, and the
	 * per-product breakdown.
	 *
	 * @param {Element} root    The calculator.
	 * @param {Object}  summary The sums.
	 * @param {string}  view    'year' or 'month'.
	 */
	function paintTotals( root, summary, view ) {
		var monthly = summary.commission / 12;
		var total = root.querySelector( '.comm-total' );
		var sub = root.querySelector( '.comm-total-sub' );
		var list = root.querySelector( '[data-breakdown]' );

		total.innerHTML = '';
		total.appendChild( document.createTextNode(
			'month' === view ? money( monthly ) + ' ' : money( summary.commission ) + ' '
		) );

		var when = document.createElement( 'span' );
		when.className = 'comm-total-when';
		when.textContent = 'month' === view ? 'a month, year one' : 'in year one';
		total.appendChild( when );

		sub.textContent = 'month' === view
			? money( summary.commission ) + ' a year, on ' + money( summary.value ) + ' of annual sale value'
			: money( monthly ) + ' a month, on ' + money( summary.value ) + ' of annual sale value';

		list.innerHTML = '';

		var keys = Object.keys( summary.lines );

		if ( ! keys.length ) {
			var empty = document.createElement( 'p' );
			empty.className = 'comm-break-empty';
			empty.textContent = 'Add a product to see what you earn.';
			list.appendChild( empty );
			return;
		}

		keys.forEach( function ( key ) {
			var line = summary.lines[ key ];
			var row = document.createElement( 'div' );
			row.className = 'comm-break-row';

			var left = document.createElement( 'div' );
			var name = document.createElement( 'div' );
			name.className = 'comm-break-name';
			name.textContent = line.name;

			var detail = document.createElement( 'div' );
			detail.className = 'comm-break-detail';
			detail.textContent = line.pkg
				? line.qty + ' × ' + line.pkg.name + ' · ' + money( line.unit ) + ' a month · ' + percent( line.rate ) + '%'
				: line.qty + ' × ' + money( line.unit ) + ' ' + ( line.monthly ? 'monthly' : 'yearly' ) + ' · ' + percent( line.rate ) + '%';

			left.appendChild( name );
			left.appendChild( detail );

			var fee = document.createElement( 'div' );
			fee.className = 'comm-break-fee';
			fee.textContent = 'month' === view
				? money( line.commission / 12 ) + ' / mo'
				: money( line.commission );

			row.appendChild( left );
			row.appendChild( fee );
			list.appendChild( row );
		} );
	}

	/**
	 * Marks the pressed button in a segmented control.
	 *
	 * @param {Element} group    The control.
	 * @param {string}  attr     Attribute the segments carry.
	 * @param {string}  selected The chosen value.
	 */
	function paintSegments( group, attr, selected ) {
		var buttons = group.querySelectorAll( '[' + attr + ']' );

		Array.prototype.forEach.call( buttons, function ( button ) {
			var on = button.getAttribute( attr ) === selected;

			button.classList.toggle( 'on', on );
			button.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
	}

	/**
	 * Wires one calculator.
	 *
	 * @param {Element} root The element carrying data-commission.
	 */
	function start( root ) {
		var sale = JSON.parse( JSON.stringify( data.defaults ) );
		var view = 'year';

		/** Redraws everything from the current sale. */
		function paint() {
			var summary = summarise( sale );

			Object.keys( data.products ).forEach( function ( key ) {
				var row = root.querySelector( '[data-row="' + key + '"]' );

				row.querySelector( '[data-qty]' ).textContent = String( sale[ key ].qty );
				paintSegments( row, 'data-billing', sale[ key ].billing );
				paintRow( root, key, summary.lines[ key ] );
			} );

			var support = root.querySelector( '[data-row="support"]' );
			support.querySelector( '[data-qty]' ).textContent = String( sale.supportQty );
			support.querySelector( '[data-package]' ).value = sale.support;

			paintSupport( root, sale );
			paintRow( root, 'support', summary.lines.support );
			paintTotals( root, summary, view );
			paintSegments( root.querySelector( '.comm-out' ), 'data-view', view );
		}

		root.addEventListener( 'click', function ( event ) {
			var step = event.target.closest( '[data-step]' );
			var billing = event.target.closest( '[data-billing]' );
			var viewButton = event.target.closest( '[data-view]' );
			var reset = event.target.closest( '[data-reset]' );

			if ( step ) {
				var row = step.closest( '[data-row]' );
				var key = row.getAttribute( 'data-row' );
				var by = 'up' === step.getAttribute( 'data-step' ) ? 1 : -1;
				var current = 'support' === key ? sale.supportQty : sale[ key ].qty;
				// The design's range. 0 takes the product out of the sale
				// rather than removing the row, so it can be put back.
				var next = Math.min( 99, Math.max( 0, current + by ) );

				if ( 'support' === key ) {
					sale.supportQty = next;
				} else {
					sale[ key ].qty = next;
				}

				paint();
				return;
			}

			if ( billing ) {
				sale[ billing.closest( '[data-row]' ).getAttribute( 'data-row' ) ].billing = billing.getAttribute( 'data-billing' );
				paint();
				return;
			}

			if ( viewButton ) {
				// Display only. The tier a package earns never depends on how
				// the salesperson is looking at the total.
				view = viewButton.getAttribute( 'data-view' );
				paint();
				return;
			}

			if ( reset ) {
				sale = JSON.parse( JSON.stringify( data.defaults ) );
				paint();
			}
		} );

		root.addEventListener( 'change', function ( event ) {
			if ( event.target.hasAttribute( 'data-package' ) ) {
				sale.support = event.target.value;
				paint();
			}
		} );

		paint();
	}

	/**
	 * Runs fn once the DOM is ready, immediately if it already is.
	 *
	 * @param {Function} fn Callback.
	 */
	function ready( fn ) {
		if ( 'loading' !== document.readyState ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	ready( function () {
		var root = document.querySelector( '[data-commission]' );

		if ( root ) {
			start( root );
		}
	} );
}() );
