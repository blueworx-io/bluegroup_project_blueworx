/*
 * BlueWorx quote builder (#113).
 *
 * Takes over the Support page's hours slider when the questions are on, and
 * runs the same calculator in the Sales section. The slider on its own is
 * still handled by public-widgets.js — this file only loads where the full
 * builder is rendered, and it owns the whole widget when it does.
 *
 * The model (what a page costs in hours, what a membership system adds) is
 * printed into the page by PHP as window.blueworxQuote, so it is stated once,
 * in includes/public/quote.php.
 */
( function () {
	'use strict';

	var data = window.blueworxQuote;

	/**
	 * Formats an amount: whole pounds carry no decimals, anything else two.
	 *
	 * @param {number} amount Amount in pounds.
	 * @return {string}
	 */
	function money( amount ) {
		var whole = Math.abs( amount - Math.round( amount ) ) < 0.005;

		return '£' + amount.toLocaleString( 'en-GB', {
			minimumFractionDigits: whole ? 0 : 2,
			maximumFractionDigits: whole ? 0 : 2,
		} );
	}

	/**
	 * A rate for display: 0.2 reads as "20".
	 *
	 * @param {number} rate Rate as a fraction.
	 * @return {string}
	 */
	function percent( rate ) {
		return String( Math.round( rate * 100 ) );
	}

	/**
	 * How a stage with a rate was arrived at.
	 *
	 * @param {number}  count How many of the thing.
	 * @param {number}  each  Hours each one takes.
	 * @param {boolean} pages Whether the thing is pages, which are worth naming.
	 * @return {string} The working, e.g. "5 pages × 4 hrs".
	 */
	function rateNote( count, each, pages ) {
		var what = pages ? ( 1 === count ? ' page' : ' pages' ) : '';

		return count + what + ' × ' + each + ' hrs';
	}

	/**
	 * The hours a build comes to, and the stages behind them.
	 *
	 * @param {number}  pages        Pages designed and built.
	 * @param {boolean} membership   Whether a membership system is included.
	 * @param {number}  integrations How many other systems it has to talk to.
	 * @param {Object}  options      share: the fraction of a from-scratch build
	 *                               this is. buildPerPage: overrides the build
	 *                               rate, for work the share does not describe.
	 * @return {Object} total and stages.
	 */
	function build( pages, membership, integrations, options ) {
		var model = data.model;
		var fixed = model.fixed;
		var opts = options || {};

		// A build on ground already broken is a fraction of the same work from
		// scratch. Every stage takes the same fraction, so the lines still add
		// up to the total underneath them.
		var rate = function ( hours ) {
			return Math.round( hours * ( opts.share || 1 ) );
		};
		var buildPerPage = opts.buildPerPage || rate( model.build_per_page );
		var stages = [
			{ key: 'discovery', label: fixed.discovery.label, hours: rate( fixed.discovery.hours ) },
			{
				key: 'design',
				label: 'Design and review',
				hours: pages * rate( model.design_per_page ),
				note: rateNote( pages, rate( model.design_per_page ), true ),
			},
			{
				key: 'build',
				label: 'Build and review',
				hours: pages * buildPerPage,
				note: rateNote( pages, buildPerPage, true ),
			},
		];

		if ( integrations > 0 ) {
			stages.push( {
				key: 'integrations',
				label: 'Custom integrations',
				hours: integrations * rate( model.integration ),
				note: rateNote( integrations, rate( model.integration ), false ),
			} );
		}

		if ( membership ) {
			stages.push( { key: 'membership', label: 'Membership system', hours: rate( model.membership ) } );
		}

		[ 'testing', 'deployment', 'monitoring' ].forEach( function ( key ) {
			stages.push( { key: key, label: fixed[ key ].label, hours: rate( fixed[ key ].hours ) } );
		} );

		return {
			stages: stages,
			total: stages.reduce( function ( sum, stage ) {
				return sum + stage.hours;
			}, 0 ),
		};
	}

	/**
	 * The smallest package whose annual allowance covers the hours.
	 *
	 * @param {Array}  packages The package table.
	 * @param {number} hours    Hours needed.
	 * @return {number} Index in the table, or -1 when none is big enough.
	 */
	function packageFor( packages, hours ) {
		var best = -1;

		packages.forEach( function ( pkg, index ) {
			if ( pkg.hours >= hours && ( best < 0 || pkg.hours < packages[ best ].hours ) ) {
				best = index;
			}
		} );

		return best;
	}

	/**
	 * Marks the pressed button in a segmented control.
	 *
	 * @param {Element} group    The control, or its wrapper.
	 * @param {string}  selected The chosen value.
	 */
	function paintSegments( group, selected ) {
		if ( ! group ) {
			return;
		}

		Array.prototype.forEach.call( group.querySelectorAll( '[data-value]' ), function ( button ) {
			var on = button.getAttribute( 'data-value' ) === selected;

			button.classList.toggle( 'on', on );
			button.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
	}

	/**
	 * Wires one quote builder.
	 *
	 * @param {Element} root The widget.
	 */
	function start( root ) {
		var packages;

		try {
			packages = JSON.parse( root.getAttribute( 'data-packages' ) || '[]' );
		} catch {
			return;
		}

		if ( ! packages.length ) {
			return;
		}

		var quote = JSON.parse( JSON.stringify( data.defaults ) );
		var range = root.querySelector( 'input[type="range"]' );
		var slider = root.querySelector( '[data-slider]' );
		var buildBox = root.querySelector( '[data-build]' );
		var hostingMode = root.querySelector( '[data-hosting-mode]' );
		var clubhouseMode = root.querySelector( '[data-clubhouse-mode]' );
		var clubhouseSupport = root.querySelector( '[data-clubhouse-support]' );
		var membershipAsk = root.querySelector( '[data-membership-ask]' );
		var pagesBox = root.querySelector( '[data-pages]' );
		var integrationsBox = root.querySelector( '[data-integrations]' );
		var pagesFixed = root.querySelector( '[data-pages-fixed]' );
		var stagesList = root.querySelector( '[data-stages]' );
		var hoursOut = root.querySelector( '[data-testid="quote-hours"]' );
		var over = root.querySelector( '[data-testid="quote-over"]' );
		var left = root.querySelector( '[data-left]' );
		var remaining = root.querySelector( '[data-testid="quote-remaining"]' );
		var lines = root.querySelector( '[data-quote-lines]' );
		var commission = root.querySelector( '[data-testid="quote-commission"]' );
		var commissionParts = root.querySelector( '[data-commission-parts]' );

		/** Whether the quote is being sized as a build. */
		function isBuild() {
			if ( quote.hosting ) {
				return 'build' === quote.hostingMode;
			}

			if ( quote.clubhouse ) {
				return 'custom' === quote.clubhouseMode;
			}

			return false;
		}

		/** How many pages this quote covers — fixed for a custom ClubHouse. */
		function pageCount() {
			return isCustomClubhouse() ? data.model.clubhouse_pages : quote.pages;
		}

		/** Whether this is a ClubHouse built on the platform rather than from scratch. */
		function isCustomClubhouse() {
			return quote.clubhouse && 'custom' === quote.clubhouseMode;
		}

		/** How this quote departs from a from-scratch build, if it does. */
		function buildOptions() {
			if ( ! isCustomClubhouse() ) {
				return {};
			}

			return {
				share: data.model.clubhouse_share,
				buildPerPage: data.model.clubhouse_build,
			};
		}

		/** Draws the package name, blurb, rate and price. */
		function paintPackage( index ) {
			var pkg = packages[ index ];
			var perHour = ( pkg.price * 12 ) / pkg.hours;
			var gbp = 'GBP' === pkg.currency;
			var hours = root.querySelector( '[data-testid="support-calc-hours"]' );
			var annual = root.querySelector( '[data-testid="support-calc-annual"]' );
			var name = root.querySelector( '[data-testid="support-calc-name"]' );
			var blurb = root.querySelector( '[data-testid="support-calc-blurb"]' );
			var rate = root.querySelector( '[data-testid="support-calc-rate"]' );
			var price = root.querySelector( '[data-testid="support-calc-price"]' );

			hours.textContent = String( Math.round( ( pkg.hours / 12 ) * 100 ) / 100 );
			annual.textContent = String( pkg.hours );
			name.textContent = pkg.name;
			blurb.textContent = pkg.blurb;

			// Pounds go through the currency painter's contract so they follow
			// the switcher; anything else is written in its own sign.
			if ( gbp ) {
				rate.setAttribute( 'data-bw-gbp', perHour.toFixed( 2 ) );
				rate.setAttribute( 'data-bw-dp', '2' );
				rate.setAttribute( 'data-bw-suffix', ' / hr' );
				price.setAttribute( 'data-bw-gbp', String( pkg.price ) );
			} else {
				rate.removeAttribute( 'data-bw-gbp' );
				price.removeAttribute( 'data-bw-gbp' );
				rate.textContent = pkg.sign + perHour.toFixed( 2 ) + ' / hr';
				price.textContent = pkg.sign + pkg.price.toLocaleString( 'en-GB' );
			}

			// The currency painter repaints every data-bw-gbp figure on this
			// event — the same one the switcher fires — so a package changed
			// here is shown in whatever currency the visitor picked.
			window.dispatchEvent( new Event( 'bw:currency' ) );
		}

		/** Draws the extras that sit under the package price. */
		function paintLines() {
			var rows = [];

			if ( quote.hosting ) {
				rows.push( [ data.products.hosting.name, money( data.products.hosting.year ) + ' a year' ] );
			}

			if ( quote.clubhouse ) {
				// The setup fee is for standing a membership system up, so it
				// follows that question rather than the ClubHouse itself. A custom
				// one never carries it: the build hours already cover that work.
				if ( 'standard' === quote.clubhouseMode && quote.membership ) {
					rows.push( [ 'Membership setup', money( data.setup ) + ' one-off' ] );
				}

				rows.push( [ data.products.clubhouse.name, money( data.products.clubhouse.year ) + ' a year' ] );
			}

			lines.innerHTML = '';
			lines.hidden = ! rows.length;

			rows.forEach( function ( row ) {
				var li = document.createElement( 'li' );
				var label = document.createElement( 'span' );
				var value = document.createElement( 'b' );

				label.textContent = row[ 0 ];
				value.textContent = row[ 1 ];
				li.appendChild( label );
				li.appendChild( value );
				lines.appendChild( li );
			} );
		}

		/**
		 * Draws what the quote pays, where that is shown at all.
		 *
		 * Part by part, because the parts do not pay the same: a site earns 20%
		 * of its subscription flat, while a support package earns 10% or 20%
		 * depending on whether it is worth £9,000 a year. A single total cannot
		 * say which rate did what, and that is the first thing anybody asks.
		 *
		 * @param {number} index The quoted package, or -1 for none.
		 */
		function paintCommission( index ) {
			if ( ! commission ) {
				return;
			}

			var rates = data.rates;
			var total = 0;
			var parts = [];

			/**
			 * Adds one part, and remembers how it was worked out.
			 *
			 * @param {string} label  What it is.
			 * @param {number} value  The sale value it is paid on.
			 * @param {number} rate   The rate paid on it.
			 */
			function add( label, value, rate ) {
				total += value * rate;
				parts.push( money( value * rate ) + ' ' + label + ' at ' + percent( rate ) + '%' );
			}

			if ( quote.hosting ) {
				add( 'hosting', data.products.hosting.year, rates.seat );
			}

			if ( quote.clubhouse ) {
				// The one-off setup fee earns nothing: commission is on the
				// subscription, which is the part that was sold.
				add( 'ClubHouse', data.products.clubhouse.year, rates.seat );
			}

			if ( index >= 0 ) {
				var annual = packages[ index ].price * 12;

				add( 'support', annual, annual >= rates.threshold ? rates.support_high : rates.support_low );
			}

			commission.textContent = money( total );

			if ( commissionParts ) {
				commissionParts.textContent = parts.length > 1 ? parts.join( ' · ' ) : '';
			}
		}

		/** Redraws everything from the current quote. */
		function paint() {
			var building = isBuild();
			var sized = building
				? build( pageCount(), quote.membership, quote.integrations, buildOptions() )
				: null;
			var index = parseInt( range.value, 10 ) || 0;

			if ( hostingMode ) {
				hostingMode.hidden = ! quote.hosting;
				paintSegments( hostingMode, quote.hostingMode );
			}

			if ( clubhouseMode ) {
				clubhouseMode.hidden = ! quote.clubhouse;
				paintSegments( clubhouseMode, quote.clubhouseMode );
			}

			paintSegments( root.querySelector( '[data-toggle="hosting"]' ), quote.hosting ? 'yes' : 'no' );
			paintSegments( root.querySelector( '[data-toggle="clubhouse"]' ), quote.clubhouse ? 'yes' : 'no' );
			paintSegments( root.querySelector( '[data-toggle="membership"]' ), quote.membership ? 'yes' : 'no' );
			paintSegments( root.querySelector( '[data-toggle="management"]' ), quote.clubhouseSupport ? 'yes' : 'no' );

			if ( buildBox ) {
				buildBox.hidden = ! building;
			}

			// A ClubHouse build is twelve pages by definition, so the stepper
			// goes and a plain statement takes its place.
			if ( pagesBox ) {
				pagesBox.hidden = ! building || ( quote.clubhouse && 'custom' === quote.clubhouseMode );
				pagesBox.querySelector( '[data-qty]' ).textContent = String( quote.pages );
			}

			if ( integrationsBox ) {
				integrationsBox.querySelector( '[data-qty]' ).textContent = String( quote.integrations );
			}

			if ( pagesFixed ) {
				pagesFixed.hidden = ! ( building && quote.clubhouse && 'custom' === quote.clubhouseMode );
			}

			// A standard ClubHouse is a platform somebody joins: no hours, and
			// so nothing to slide between — unless they also want us looking
			// after it, which is what the management question asks.
			var standardClubhouse = quote.clubhouse && 'standard' === quote.clubhouseMode;
			var noPackage = standardClubhouse && ! quote.clubhouseSupport;

			if ( clubhouseSupport ) {
				clubhouseSupport.hidden = ! standardClubhouse;
			}

			// A build is charged 30 hours for a membership system; a standard
			// ClubHouse is charged the setup fee for one. Nothing else asks.
			if ( membershipAsk ) {
				membershipAsk.hidden = ! ( building || standardClubhouse );
			}

			slider.hidden = building || noPackage;

			if ( building ) {
				index = packageFor( packages, sized.total );

				hoursOut.textContent = sized.total + ' hrs';
				over.hidden = index >= 0;

				stagesList.innerHTML = '';
				sized.stages.forEach( function ( stage ) {
					var li = document.createElement( 'li' );
					var label = document.createElement( 'span' );
					var value = document.createElement( 'b' );

					li.setAttribute( 'data-stage', stage.key );
					label.textContent = stage.label;
					value.textContent = stage.hours + ' hrs';
					li.appendChild( label );

					// Only the stages that multiply out show their working, and it sits
					// beside the total it produced rather than under the label.
					if ( stage.note ) {
						var note = document.createElement( 'i' );

						note.setAttribute( 'data-note', '' );
						note.textContent = stage.note;
						li.appendChild( note );
					}

					li.appendChild( value );
					stagesList.appendChild( li );
				} );

				if ( index >= 0 ) {
					range.value = String( index );
				}

				// What the client has left of the package's year once the build
				// has been drawn out of it.
				if ( left ) {
					left.hidden = index < 0;

					if ( index >= 0 ) {
						remaining.textContent = ( packages[ index ].hours - sized.total ) + ' hrs';
					}
				}
			} else if ( left ) {
				left.hidden = true;
			}

			if ( index >= 0 ) {
				paintPackage( index );
			}

			// The package panel says nothing useful for a standard ClubHouse.
			root.classList.toggle( 'quote-no-package', noPackage );

			paintLines();
			paintCommission( noPackage ? -1 : index );
		}

		root.addEventListener( 'click', function ( event ) {
			var choice = event.target.closest( '[data-value]' );
			var step = event.target.closest( '[data-step]' );

			if ( step ) {
				var by = 'up' === step.getAttribute( 'data-step' ) ? 1 : -1;

				if ( step.closest( '[data-integrations]' ) ) {
					// None is a real answer here, unlike pages.
					quote.integrations = Math.min( 40, Math.max( 0, quote.integrations + by ) );
				} else {
					quote.pages = Math.min( 60, Math.max( 1, quote.pages + by ) );
				}

				paint();
				return;
			}

			if ( ! choice ) {
				return;
			}

			var value = choice.getAttribute( 'data-value' );
			var toggleGroup = choice.closest( '[data-toggle]' );

			if ( toggleGroup ) {
				var name = toggleGroup.getAttribute( 'data-toggle' );

				if ( 'membership' === name ) {
					quote.membership = 'yes' === value;
				} else if ( 'management' === name ) {
					quote.clubhouseSupport = 'yes' === value;
				} else {
					quote[ name ] = 'yes' === value;

					// One or the other, never both: a quote for a hosted site
					// and a ClubHouse is a quote for two sites.
					if ( 'yes' === value ) {
						quote[ 'hosting' === name ? 'clubhouse' : 'hosting' ] = false;
					}
				}

				paint();
				return;
			}

			if ( choice.closest( '[data-hosting-mode]' ) ) {
				quote.hostingMode = value;
				paint();
				return;
			}

			if ( choice.closest( '[data-clubhouse-mode]' ) ) {
				quote.clubhouseMode = value;
				paint();
			}
		} );

		range.addEventListener( 'input', paint );
		range.addEventListener( 'change', paint );

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
		var root = document.querySelector( '[data-quote="full"]' );

		if ( root && data && data.model ) {
			start( root );
		}
	} );
}() );
