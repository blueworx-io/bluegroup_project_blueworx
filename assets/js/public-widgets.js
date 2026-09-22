/**
 * BlueWorx public marketing widgets (Plan 3a).
 *
 * Progressive enhancement: the templates render each widget's correct default
 * state; this script only rewrites text and toggles classes on interaction.
 * Each init no-ops when its [data-widget] marker is absent, so the one file is
 * safe on every owned page.
 */
( function () {
	'use strict';

	// GBP is the base. The rates are the ECB's, handed over by the server in
	// window.blueworxCurrency (includes/public/currency.php); the figures here
	// only apply if that is missing, so a broken inline script cannot leave
	// the switcher painting nothing.
	var CURRENCIES = {
		GBP: { symbol: '£', rate: 1 },
		EUR: { symbol: '€', rate: 1.17 },
		USD: { symbol: '$', rate: 1.27 },
		ZAR: { symbol: 'R', rate: 21.8 },
		AUD: { symbol: 'A$', rate: 1.88 },
		AED: { symbol: 'AED ', rate: 4.66 }
	};

	( function () {
		var cfg = window.blueworxCurrency;
		if ( ! cfg || ! cfg.rates ) {
			return;
		}
		for ( var code in CURRENCIES ) {
			var rate = Number( cfg.rates[ code ] );
			if ( isFinite( rate ) && rate > 0 ) {
				CURRENCIES[ code ].rate = rate;
			}
		}
	} )();

	// The currency the visitor last chose on this page. The switcher
	// announces its choice as a "bw:currency" event, and that wins over
	// storage: where storage is blocked (private mode) the choice still has
	// to apply to the page it was made on.
	var chosen = '';

	function currentCurrency() {
		var code = chosen;
		if ( ! code ) {
			try {
				code = localStorage.getItem( 'bw-currency' ) || 'GBP';
			} catch {
				code = 'GBP';
			}
		}
		return CURRENCIES[ code ] ? code : 'GBP';
	}

	window.addEventListener( 'bw:currency', function ( event ) {
		if ( event.detail && CURRENCIES[ event.detail ] ) {
			chosen = event.detail;
		}
	} );

	/**
	 * Formats a GBP amount in the visitor's chosen currency.
	 *
	 * @param {number} gbp Amount in pounds.
	 * @param {number} dp  Decimal places (0 or 2).
	 * @return {string} e.g. "£20", "€58.50".
	 */
	function money( gbp, dp ) {
		var cur = CURRENCIES[ currentCurrency() ];
		var value = Number( gbp ) * cur.rate;
		if ( dp ) {
			return cur.symbol + value.toFixed( dp );
		}
		return cur.symbol + Math.round( value ).toLocaleString( 'en-GB' );
	}

	/**
	 * Repaints every element carrying a base GBP amount.
	 *
	 * Templates render the pound figure, so the page is correct with JS off
	 * and only ever changes when the visitor picks another currency.
	 */
	function paintPrices() {
		var els = document.querySelectorAll( '[data-bw-gbp]' );
		for ( var i = 0; i < els.length; i++ ) {
			var el = els[ i ];
			var dp = parseInt( el.getAttribute( 'data-bw-dp' ) || '0', 10 );
			el.textContent = ( el.getAttribute( 'data-bw-prefix' ) || '' )
				+ money( el.getAttribute( 'data-bw-gbp' ), dp )
				+ ( el.getAttribute( 'data-bw-suffix' ) || '' );
		}
	}

	function initCurrencyPrices() {
		if ( ! document.querySelector( '[data-bw-gbp]' ) ) {
			return;
		}
		paintPrices();
		window.addEventListener( 'bw:currency', paintPrices );
	}

	function initBillingToggle() {
		var toggle = document.querySelector( '[data-widget="billing-toggle"]' );
		if ( ! toggle ) {
			return;
		}
		var btns = toggle.querySelectorAll( 'button' );
		if ( btns.length < 2 ) {
			return;
		}

		function apply( annual ) {
			btns[ 0 ].className = annual ? '' : 'on';
			btns[ 1 ].className = annual ? 'on' : '';
			btns[ 0 ].setAttribute( 'aria-pressed', annual ? 'false' : 'true' );
			btns[ 1 ].setAttribute( 'aria-pressed', annual ? 'true' : 'false' );

			var prices = document.querySelectorAll( '.plan-price' );
			for ( var i = 0; i < prices.length; i++ ) {
				var b = prices[ i ].querySelector( 'b' );
				var em = prices[ i ].querySelector( 'em' );
				if ( b ) {
					var amount = annual ? prices[ i ].getAttribute( 'data-price-a' ) : prices[ i ].getAttribute( 'data-price-m' );
					if ( b.hasAttribute( 'data-bw-gbp' ) ) {
						// A pound price: store the new base and let the painter
						// render it in whatever currency is selected.
						b.setAttribute( 'data-bw-gbp', amount );
						b.textContent = money( amount, 0 );
					} else {
						b.textContent = ( prices[ i ].getAttribute( 'data-symbol' ) || '$' ) + amount;
					}
				}
				if ( em ) {
					em.textContent = annual ? em.getAttribute( 'data-sub-a' ) : em.getAttribute( 'data-sub-m' );
				}
			}

			// The buy link has to follow the toggle, or picking annual billing
			// and clicking through lands the visitor on a monthly checkout.
			// Only plans wired to SureCart carry these; the rest keep whatever
			// href the template gave them.
			var buys = document.querySelectorAll( '[data-buy-m], [data-buy-a]' );
			for ( var j = 0; j < buys.length; j++ ) {
				var target = buys[ j ].getAttribute( annual ? 'data-buy-a' : 'data-buy-m' );
				if ( target ) {
					buys[ j ].setAttribute( 'href', target );
				}
			}
		}

		toggle.setAttribute( 'role', 'group' );
		btns[ 0 ].addEventListener( 'click', function () {
			apply( false );
		} );
		btns[ 1 ].addEventListener( 'click', function () {
			apply( true );
		} );
		apply( false );
	}

	/**
	 * The Support page's hours slider (2026-09 restructure).
	 *
	 * The slider indexes the nine packages, whose numbers the template
	 * writes into data-packages so this never carries a second copy of the
	 * price list. Prices go through the painter's data-bw-gbp contract so a
	 * currency change repaints them like every other price on the page.
	 */
	function initSupportCalc() {
		var root = document.querySelector( '[data-widget="support-calc"]' );
		if ( ! root ) {
			return;
		}
		var packages;
		try {
			packages = JSON.parse( root.getAttribute( 'data-packages' ) || '[]' );
		} catch {
			return;
		}
		var range = root.querySelector( 'input[type="range"]' );
		var hours = root.querySelector( '[data-testid="support-calc-hours"]' );
		var annual = root.querySelector( '[data-testid="support-calc-annual"]' );
		var name = root.querySelector( '[data-testid="support-calc-name"]' );
		var blurb = root.querySelector( '[data-testid="support-calc-blurb"]' );
		var rate = root.querySelector( '[data-testid="support-calc-rate"]' );
		var price = root.querySelector( '[data-testid="support-calc-price"]' );
		if ( ! range || ! packages.length ) {
			return;
		}

		function apply() {
			var pkg = packages[ Math.min( packages.length - 1, Math.max( 0, parseInt( range.value, 10 ) || 0 ) ) ];
			var perHour = ( pkg.price * 12 ) / pkg.hours;
			var gbp = 'GBP' === pkg.currency;
			// Two decimals, trailing zeros dropped — the same figure
			// blueworx_content_hours_a_month() renders on the server.
			if ( hours ) {
				hours.textContent = String( Math.round( ( pkg.hours / 12 ) * 100 ) / 100 );
			}
			if ( annual ) {
				annual.textContent = String( pkg.hours );
			}
			if ( name ) {
				name.textContent = pkg.name;
			}
			if ( blurb ) {
				blurb.textContent = pkg.blurb;
			}
			// A pound figure goes through the painter so it follows the
			// switcher; a package priced in another currency is written as
			// is, in its own sign, and never converted.
			if ( rate ) {
				if ( gbp ) {
					rate.setAttribute( 'data-bw-gbp', perHour.toFixed( 2 ) );
					rate.setAttribute( 'data-bw-dp', '2' );
					rate.setAttribute( 'data-bw-suffix', ' / hr' );
				} else {
					rate.removeAttribute( 'data-bw-gbp' );
					rate.textContent = pkg.sign + perHour.toFixed( 2 ) + ' / hr';
				}
			}
			if ( price ) {
				if ( gbp ) {
					price.setAttribute( 'data-bw-gbp', String( pkg.price ) );
				} else {
					price.removeAttribute( 'data-bw-gbp' );
					price.textContent = pkg.sign + pkg.price.toLocaleString( 'en-GB' );
				}
			}
			paintPrices();
		}

		range.addEventListener( 'input', apply );
		range.addEventListener( 'change', apply );
		apply();
	}

	function initFaqAccordion() {
		var lists = document.querySelectorAll( '.faq-list' );
		for ( var i = 0; i < lists.length; i++ ) {
			( function ( list ) {
				var items = list.querySelectorAll( 'details.faq-item' );
				for ( var j = 0; j < items.length; j++ ) {
					items[ j ].addEventListener( 'toggle', function () {
						if ( ! this.open ) {
							return;
						}
						for ( var k = 0; k < items.length; k++ ) {
							if ( items[ k ] !== this && items[ k ].open ) {
								items[ k ].open = false;
							}
						}
					} );
				}
			}( lists[ i ] ) );
		}
	}

	function initAiPipeline() {
		var shell = document.querySelector( '[data-widget="ai-pipeline"]' );
		if ( ! shell ) {
			return;
		}
		var steps = shell.querySelectorAll( '.ai-pipe-step' );
		if ( ! steps.length ) {
			return;
		}
		if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			return;
		}
		var active = 0;
		setInterval( function () {
			steps[ active ].className = 'ai-pipe-step';
			active = ( active + 1 ) % steps.length;
			steps[ active ].className = 'ai-pipe-step on';
		}, 1300 );
	}

	function initFeatureTabs() {
		var root = document.querySelector( '[data-widget="feature-tabs"]' );
		if ( ! root ) {
			return;
		}
		var tabs = root.querySelectorAll( '.tab-bar .tab' );
		var legs = root.querySelectorAll( '.af-legend .af-leg' );
		var chart = root.querySelector( '.af-chart' );
		if ( ! tabs.length || ! chart ) {
			return;
		}
		var xs = [ 0, 65, 130, 195, 260, 325, 390, 455, 520 ];
		var areaEl = chart.querySelector( '.af-area' );
		var lineEl = chart.querySelector( '.af-line' );
		var dotEl = chart.querySelector( '.af-dot' );
		var stops = chart.querySelectorAll( '#afGrad stop' );
		var head = root.querySelector( '.af-text h2' );
		var desc = root.querySelector( '.af-text p' );
		var cta = root.querySelector( '.af-text a' );

		function select( idx ) {
			var tab = tabs[ idx ];
			var pts = tab.getAttribute( 'data-pts' ).split( ' ' );
			var color = tab.getAttribute( 'data-color' );
			var d = '';
			var minY = Infinity;
			var dotI = 0;
			var i;
			for ( i = 0; i < pts.length; i++ ) {
				var y = parseFloat( pts[ i ] );
				d += ( i ? 'L' : 'M' ) + xs[ i ] + ',' + y + ( i === pts.length - 1 ? '' : ' ' );
				if ( y < minY ) {
					minY = y;
					dotI = i;
				}
			}
			if ( lineEl ) {
				lineEl.setAttribute( 'd', d );
				lineEl.setAttribute( 'stroke', color );
			}
			if ( areaEl ) {
				areaEl.setAttribute( 'd', d + ' L520,210 L0,210 Z' );
			}
			if ( dotEl ) {
				dotEl.setAttribute( 'cx', xs[ dotI ] );
				dotEl.setAttribute( 'cy', minY );
				dotEl.setAttribute( 'stroke', color );
			}
			for ( i = 0; i < stops.length; i++ ) {
				stops[ i ].setAttribute( 'stop-color', color );
			}
			for ( i = 0; i < tabs.length; i++ ) {
				tabs[ i ].className = i === idx ? 'tab on' : 'tab off';
			}
			for ( i = 0; i < legs.length; i++ ) {
				legs[ i ].className = i === idx ? 'af-leg on' : 'af-leg off';
			}
			if ( head ) {
				head.textContent = tab.getAttribute( 'data-heading' );
			}
			if ( desc ) {
				desc.textContent = tab.getAttribute( 'data-desc' );
			}
			if ( cta && cta.firstChild ) {
				cta.firstChild.nodeValue = tab.getAttribute( 'data-cta' ) + ' ';
			}
			if ( cta && tab.getAttribute( 'data-href' ) ) {
				cta.setAttribute( 'href', tab.getAttribute( 'data-href' ) );
			}
		}

		var t;
		for ( t = 0; t < tabs.length; t++ ) {
			( function ( idx ) {
				tabs[ idx ].addEventListener( 'click', function () {
					select( idx );
				} );
			}( t ) );
		}
		for ( t = 0; t < legs.length; t++ ) {
			( function ( idx ) {
				legs[ idx ].addEventListener( 'click', function () {
					select( idx );
				} );
			}( t ) );
		}
	}

	function initAiDemo() {
		var root = document.querySelector( '[data-widget="ai-demo"]' );
		if ( ! root ) {
			return;
		}
		if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			return;
		}
		var typed = root.querySelector( '.ai-typed' );
		var caret = root.querySelector( '.ai-caret' );
		var codeLines = root.querySelectorAll( '.ai-code .cl' );
		var site = root.querySelector( '.ai-site' );
		var stages = root.querySelectorAll( '.ai-stage' );
		if ( ! typed ) {
			return;
		}
		var msg = typed.textContent;
		var timers = [];

		function clearTimers() {
			for ( var i = 0; i < timers.length; i++ ) {
				clearTimeout( timers[ i ] );
			}
			timers = [];
		}
		function setStage( n ) {
			for ( var i = 0; i < stages.length; i++ ) {
				stages[ i ].className = i <= n ? 'ai-stage on' : 'ai-stage';
			}
		}
		function loop() {
			clearTimers();
			var t = 0;
			var i;
			typed.textContent = '';
			if ( caret ) {
				caret.style.display = '';
			}
			for ( i = 0; i < codeLines.length; i++ ) {
				codeLines[ i ].className = 'cl';
			}
			if ( site ) {
				site.className = 'ai-site';
			}
			setStage( 0 );
			t += 450;
			for ( i = 1; i <= msg.length; i++ ) {
				( function ( n ) {
					timers.push( setTimeout( function () {
						typed.textContent = msg.slice( 0, n );
					}, t + n * 26 ) );
				}( i ) );
			}
			t += msg.length * 26 + 600;
			timers.push( setTimeout( function () {
				if ( caret ) {
					caret.style.display = 'none';
				}
				setStage( 1 );
			}, t ) );
			for ( i = 1; i <= codeLines.length; i++ ) {
				( function ( n ) {
					timers.push( setTimeout( function () {
						codeLines[ n - 1 ].className = 'cl in';
					}, t + n * 140 ) );
				}( i ) );
			}
			t += codeLines.length * 140 + 550;
			timers.push( setTimeout( function () {
				setStage( 2 );
				if ( site ) {
					site.className = 'ai-site in';
				}
			}, t ) );
			t += 3400;
			timers.push( setTimeout( loop, t ) );
		}
		loop();
	}

	/**
	 * The 404's "Go back" button (#96).
	 *
	 * Back is a browser action, so there is no URL that means it and the
	 * control has to be a real button. A visitor who landed on a dead link
	 * straight from a search result has no history to go back to, which is
	 * why the fallback destination is on the element rather than assumed.
	 */
	function initBackButtons() {
		var btns = document.querySelectorAll( '[data-bw-back]' );

		for ( var i = 0; i < btns.length; i++ ) {
			btns[ i ].addEventListener( 'click', function ( e ) {
				var fallback = e.currentTarget.getAttribute( 'data-bw-back-fallback' ) || '/';

				if ( window.history.length > 1 ) {
					window.history.back();
					return;
				}

				window.location.href = fallback;
			} );
		}
	}

	/**
	 * The journal's category pills (#94).
	 *
	 * Every card is already in the document; this only hides the ones that do
	 * not match, so the page is complete and readable with JS off — it simply
	 * shows everything, which is the correct unfiltered state rather than a
	 * degraded one.
	 *
	 * The featured card is dropped out of view while a category is selected,
	 * matching the design: it is the journal's front page, not a member of the
	 * filtered set, and leaving it up makes the count disagree with the grid.
	 */
	function initJournalFilter() {
		var root = document.querySelector( '[data-widget="journal-filter"]' );
		if ( ! root ) {
			return;
		}

		var pills = root.querySelectorAll( '[data-jr-filter]' );
		var count = root.querySelector( '[data-jr-count]' );
		var featured = document.querySelector( '[data-jr-featured]' );
		var empty = document.querySelector( '[data-jr-empty]' );
		var cards = document.querySelectorAll( '.jr-grid .jr-card' );

		function apply( cat ) {
			var shown = 0;

			for ( var i = 0; i < cards.length; i++ ) {
				var match = ! cat || cards[ i ].getAttribute( 'data-jr-cat' ) === cat;
				cards[ i ].hidden = ! match;
				if ( match ) {
					shown++;
				}
			}

			if ( featured ) {
				featured.hidden = !! cat;
				if ( ! cat ) {
					shown++;
				}
			}

			for ( var j = 0; j < pills.length; j++ ) {
				var on = pills[ j ].getAttribute( 'data-jr-filter' ) === cat;
				pills[ j ].className = on ? 'jr-pill on' : 'jr-pill';
				pills[ j ].setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			}

			if ( count ) {
				var template = 1 === shown
					? count.getAttribute( 'data-jr-one' )
					: count.getAttribute( 'data-jr-many' );

				count.textContent = ( template || '%d' ).replace( '%d', shown );
			}

			if ( empty ) {
				empty.hidden = shown > 0;
			}
		}

		for ( var k = 0; k < pills.length; k++ ) {
			pills[ k ].addEventListener( 'click', function ( e ) {
				apply( e.currentTarget.getAttribute( 'data-jr-filter' ) );
			} );
		}
	}

	/**
	 * An article's "On this page" list (#95).
	 *
	 * Built from the headings the article actually has, because the body is
	 * whatever the author published — there is no fixed set of sections to
	 * hard-code. Anchors are added here too: the editor does not give headings
	 * ids, so without this every contents link would point at nothing.
	 *
	 * Stays hidden on an article with fewer than two headings. A contents list
	 * with one entry is furniture, not navigation.
	 */
	function initArticleToc() {
		var body = document.querySelector( '[data-jp-body]' );
		var toc = document.querySelector( '[data-jp-toc]' );
		var list = document.querySelector( '[data-jp-toc-list]' );

		if ( ! body || ! toc || ! list ) {
			return;
		}

		var heads = body.querySelectorAll( 'h2' );
		if ( heads.length < 2 ) {
			return;
		}

		var used = {};

		for ( var i = 0; i < heads.length; i++ ) {
			var head = heads[ i ];
			var id = head.getAttribute( 'id' );

			if ( ! id ) {
				id = ( head.textContent || '' )
					.toLowerCase()
					.replace( /[^a-z0-9]+/g, '-' )
					.replace( /^-+|-+$/g, '' ) || 'section';

				// A duplicated id makes every later link jump to the first
				// heading that used it, which looks like the list is broken.
				if ( used[ id ] ) {
					used[ id ]++;
					id = id + '-' + used[ id ];
				} else {
					used[ id ] = 1;
				}

				head.setAttribute( 'id', id );
			}

			var item = document.createElement( 'li' );
			var link = document.createElement( 'a' );

			link.setAttribute( 'href', '#' + id );
			link.textContent = head.textContent || '';
			item.appendChild( link );
			list.appendChild( item );
		}

		toc.hidden = false;
	}

	/**
	 * The article's copy-link button (#95).
	 *
	 * Removed outright where the browser has no clipboard API — an insecure
	 * origin, or an older browser. A button that silently does nothing is the
	 * exact thing #77 was about, and the two share links beside it still work.
	 */
	function initCopyLink() {
		var btns = document.querySelectorAll( '[data-jp-copy]' );
		var note = document.querySelector( '[data-jp-copied]' );

		for ( var i = 0; i < btns.length; i++ ) {
			if ( ! navigator.clipboard || ! navigator.clipboard.writeText ) {
				btns[ i ].parentNode.removeChild( btns[ i ] );
				continue;
			}

			btns[ i ].addEventListener( 'click', function ( e ) {
				var url = e.currentTarget.getAttribute( 'data-jp-copy' ) || window.location.href;

				navigator.clipboard.writeText( url ).then( function () {
					if ( ! note ) {
						return;
					}

					note.textContent = note.getAttribute( 'data-jp-copied-label' ) || 'Link copied';
					setTimeout( function () {
						note.textContent = '';
					}, 2200 );
				} ).catch( function () {} );
			} );
		}
	}

	/**
	 * The Contact page's enquiry form (templates/parts/contact-form.php).
	 *
	 * Without this script the form is a plain POST and the server renders the
	 * outcome. With it: the budget chips fill the budget field, the required
	 * fields are checked before anything is sent, and the send goes over fetch
	 * so the success panel appears in place rather than after a reload. The
	 * server (includes/public/contact-form.php) checks everything again.
	 */
	function initContactForm() {
		var form = document.querySelector( '[data-cf-form]' );
		if ( ! form ) {
			return;
		}

		var sent = document.querySelector( '[data-cf-sent]' );
		var alert = form.querySelector( '[data-cf-alert]' );
		var submit = form.querySelector( '[data-cf-submit]' );
		var chips = form.querySelectorAll( '[data-cf-chips] [data-budget]' );
		var budget = form.querySelector( '#bw-budget' );
		var again = sent ? sent.querySelector( '[data-cf-again]' ) : null;

		function setChip( active ) {
			for ( var i = 0; i < chips.length; i++ ) {
				var on = chips[ i ] === active;
				chips[ i ].classList.toggle( 'bw-chip-on', on );
				chips[ i ].setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			}
		}

		for ( var c = 0; c < chips.length; c++ ) {
			chips[ c ].addEventListener( 'click', function ( e ) {
				var chip = e.currentTarget;
				var wasOn = chip.classList.contains( 'bw-chip-on' );
				setChip( wasOn ? null : chip );
				if ( budget ) {
					budget.value = wasOn ? '' : chip.getAttribute( 'data-budget' );
				}
			} );
		}

		// A typed figure and a picked band must never disagree: typing clears
		// the chip unless what is typed is exactly that chip's label.
		if ( budget ) {
			budget.addEventListener( 'input', function () {
				var match = null;
				for ( var i = 0; i < chips.length; i++ ) {
					if ( chips[ i ].getAttribute( 'data-budget' ) === budget.value ) {
						match = chips[ i ];
					}
				}
				setChip( match );
			} );
		}

		// The error keys (name, email, message) match the [data-cf-msg] slots;
		// the field itself is the input beside that slot.
		function fieldFor( key ) {
			var msg = form.querySelector( '[data-cf-msg="' + key + '"]' );
			return msg ? msg.parentNode.querySelector( '.bw-in' ) : null;
		}

		function setError( name, message ) {
			var field = fieldFor( name );
			var msg = form.querySelector( '[data-cf-msg="' + name + '"]' );
			var wrap = field ? field.closest( '.cf-field' ) : null;
			if ( msg ) {
				msg.textContent = message || '';
			}
			if ( wrap ) {
				wrap.classList.toggle( 'err', !! message );
			}
			if ( field ) {
				if ( message ) {
					field.setAttribute( 'aria-invalid', 'true' );
				} else {
					field.removeAttribute( 'aria-invalid' );
				}
			}
		}

		function validate() {
			var errors = {};
			var name = fieldFor( 'name' );
			var email = fieldFor( 'email' );
			var message = fieldFor( 'message' );

			if ( ! name.value.trim() ) {
				errors.name = 'Please tell us your name.';
			}
			if ( ! email.value.trim() ) {
				errors.email = 'Please add your email address.';
			} else if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email.value.trim() ) ) {
				errors.email = 'That email address does not look right.';
			}
			if ( ! message.value.trim() ) {
				errors.message = 'Please tell us a little about the project.';
			}
			return errors;
		}

		function showErrors( errors ) {
			var names = [ 'name', 'email', 'message' ];
			var first = null;
			for ( var i = 0; i < names.length; i++ ) {
				setError( names[ i ], errors[ names[ i ] ] );
				if ( errors[ names[ i ] ] && ! first ) {
					first = fieldFor( names[ i ] );
				}
			}
			if ( first ) {
				first.focus();
			}
		}

		function showSent() {
			form.hidden = true;
			if ( sent ) {
				sent.hidden = false;
				var heading = sent.querySelector( 'h2' );
				if ( heading ) {
					heading.setAttribute( 'tabindex', '-1' );
					heading.focus();
				}
			}
		}

		if ( again ) {
			again.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				form.reset();
				setChip( null );
				showErrors( {} );
				sent.hidden = true;
				form.hidden = false;
				fieldFor( 'name' ).focus();
			} );
		}

		// Clear a field's error as soon as it is corrected.
		form.addEventListener( 'input', function ( e ) {
			var wrap = e.target && e.target.closest ? e.target.closest( '.cf-field.err' ) : null;
			var msg = wrap ? wrap.querySelector( '[data-cf-msg]' ) : null;
			if ( msg ) {
				setError( msg.getAttribute( 'data-cf-msg' ), '' );
			}
		} );

		form.addEventListener( 'submit', function ( e ) {
			var errors = validate();
			if ( Object.keys( errors ).length ) {
				e.preventDefault();
				showErrors( errors );
				return;
			}

			if ( ! window.fetch || ! window.FormData ) {
				return; // A plain POST; the server renders the outcome.
			}

			e.preventDefault();
			if ( alert ) {
				alert.hidden = true;
			}
			submit.disabled = true;

			fetch( form.action, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
				body: new FormData( form )
			} ).then( function ( res ) {
				return res.json();
			} ).then( function ( data ) {
				submit.disabled = false;
				if ( data && data.ok ) {
					showSent();
					return;
				}
				if ( data && data.errors && Object.keys( data.errors ).length ) {
					showErrors( data.errors );
					return;
				}
				if ( alert ) {
					alert.hidden = false;
				}
			} ).catch( function () {
				submit.disabled = false;
				if ( alert ) {
					alert.hidden = false;
				}
			} );
		} );
	}

	function init() {
		initCurrencyPrices();
		initBackButtons();
		initJournalFilter();
		initArticleToc();
		initCopyLink();
		initBillingToggle();
		initSupportCalc();
		initFaqAccordion();
		initAiPipeline();
		initFeatureTabs();
		initAiDemo();
		initContactForm();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
