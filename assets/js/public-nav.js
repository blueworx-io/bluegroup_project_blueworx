/*
 * BlueWorx public navigation.
 *
 * Three behaviours ported from the front-end Nav component (Nav.tsx):
 *  - dropdown open/close with a 300ms close grace period, so moving the
 *    cursor from the trigger into the panel does not snap it shut. The
 *    Toolbox mega panel and the About Us dropdown each get their own timer,
 *    matching the source's two independent refs (megaT/aboutT).
 *  - hide-on-scroll-down / reveal-on-scroll-up below 160px, rAF-throttled.
 *  - mobile menu with a body scroll lock.
 *
 * Unlike the React source, templates/parts/nav.php renders the mega panel,
 * the about dropdown and the mobile menu unconditionally — React mounts them
 * only while open, but a plain document has nothing to mount. This file is
 * what turns "always in the DOM" into "hidden until asked for", by toggling
 * the same ".open" class the ported CSS (assets/css/public.css) keys off.
 */
( function () {
	'use strict';

	var CLOSE_DELAY = 300;
	var SCROLL_SHOW_AT = 8;
	var HIDE_AFTER_Y = 160;
	var MOVE_THRESHOLD = 4;

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

	/**
	 * Wires the Toolbox mega panel and the About Us dropdown.
	 *
	 * Each `[data-nav-drop]` wrapper opens its panel immediately on
	 * mouseenter and closes it after CLOSE_DELAY on mouseleave, re-entering
	 * before the timer fires cancels the pending close — the delay exists so
	 * the cursor can travel from the trigger link down into the panel
	 * without the panel disappearing under it.
	 *
	 * Keyboard users get the same behaviour via focusin/focusout on the
	 * whole wrapper: Tabbing onto the trigger link opens the panel exactly
	 * like a hover does (reusing the same open()/scheduleClose() and the
	 * same CLOSE_DELAY timer), and it only schedules closing once focus has
	 * actually left the wrapper entirely (checked via focusout's
	 * relatedTarget) — Tabbing from the trigger into one of the panel's own
	 * links must not close the panel out from under that link.
	 *
	 * @param {Element} nav The <nav> element.
	 */
	function initDropdowns( nav ) {
		var triggers = nav.querySelectorAll( '[data-nav-drop]' );

		Array.prototype.forEach.call( triggers, function ( trigger ) {
			var panel = trigger.querySelector( '.mega-panel, .about-panel' );

			if ( ! panel ) {
				return;
			}

			var closeTimer = null;

			function open() {
				if ( closeTimer ) {
					clearTimeout( closeTimer );
					closeTimer = null;
				}
				panel.classList.add( 'open' );
			}

			function scheduleClose() {
				if ( closeTimer ) {
					clearTimeout( closeTimer );
				}
				closeTimer = setTimeout( function () {
					panel.classList.remove( 'open' );
					closeTimer = null;
				}, CLOSE_DELAY );
			}

			trigger.addEventListener( 'mouseenter', open );
			trigger.addEventListener( 'mouseleave', scheduleClose );

			trigger.addEventListener( 'focusin', open );
			trigger.addEventListener( 'focusout', function ( event ) {
				if ( trigger.contains( event.relatedTarget ) ) {
					return;
				}
				scheduleClose();
			} );
		} );
	}

	/**
	 * Wires the hamburger and the mobile menu, including the body scroll
	 * lock, and keeps aria-expanded in sync with the real open state.
	 *
	 * @param {Element} nav   The <nav> element.
	 * @param {Object}  state Shared state; state.mobileOpen is read by
	 *                        initScroll() to suppress hide-on-scroll while
	 *                        the mobile menu is open.
	 */
	function initMobileMenu( nav, state ) {
		var hamburger = nav.querySelector( '.hamburger' );
		var menu = document.querySelector( '.mobile-menu' );

		if ( ! hamburger || ! menu ) {
			return;
		}

		hamburger.addEventListener( 'click', function () {
			state.mobileOpen = ! state.mobileOpen;

			hamburger.classList.toggle( 'open', state.mobileOpen );
			hamburger.setAttribute( 'aria-expanded', state.mobileOpen ? 'true' : 'false' );
			menu.classList.toggle( 'open', state.mobileOpen );
			document.body.style.overflow = state.mobileOpen ? 'hidden' : '';

			if ( state.mobileOpen ) {
				nav.classList.remove( 'nav-hidden' );
			}
		} );
	}

	/**
	 * Hide-on-scroll-down / reveal-on-scroll-up, rAF-throttled.
	 *
	 * Adds "nav-scrolled" past SCROLL_SHOW_AT regardless of direction. Adds
	 * "nav-hidden" once past HIDE_AFTER_Y while moving down by more than
	 * MOVE_THRESHOLD; removes it on any upward movement past that threshold,
	 * or whenever y is at or below HIDE_AFTER_Y. Suppressed entirely while
	 * the mobile menu is open, so opening it never leaves the nav hidden
	 * behind it.
	 *
	 * @param {Element} nav   The <nav> element.
	 * @param {Object}  state Shared state; see initMobileMenu().
	 */
	function initScroll( nav, state ) {
		var lastY = window.scrollY || 0;
		var ticking = false;

		window.addEventListener(
			'scroll',
			function () {
				if ( ticking ) {
					return;
				}
				ticking = true;

				window.requestAnimationFrame( function () {
					var y = window.scrollY || 0;

					nav.classList.toggle( 'nav-scrolled', y > SCROLL_SHOW_AT );

					if ( state.mobileOpen ) {
						nav.classList.remove( 'nav-hidden' );
					} else {
						var goingDown = y > lastY + MOVE_THRESHOLD;
						var goingUp = y < lastY - MOVE_THRESHOLD;

						if ( y > HIDE_AFTER_Y && goingDown ) {
							nav.classList.add( 'nav-hidden' );
						} else if ( goingUp || y <= HIDE_AFTER_Y ) {
							nav.classList.remove( 'nav-hidden' );
						}
					}

					lastY = y;
					ticking = false;
				} );
			},
			{ passive: true }
		);
	}

	/**
	 * The site-wide currency switcher.
	 *
	 * Currency is a visitor preference, not page state: it is stored once in
	 * localStorage and announced as a "bw:currency" event so any page holding
	 * prices (public-widgets.js) can repaint. The nav and the mobile menu each
	 * carry a copy of the control; both are kept in step.
	 */
	function initCurrencySwitcher() {
		var drops = document.querySelectorAll( '.bw-cur' );
		if ( ! drops.length ) {
			return;
		}

		var LABEL = { GBP: '£ GBP', EUR: '€ EUR', USD: '$ USD' };
		var current = 'GBP';

		try {
			current = localStorage.getItem( 'bw-currency' ) || 'GBP';
		} catch {
			current = 'GBP';
		}
		if ( ! LABEL[ current ] ) {
			current = 'GBP';
		}

		function paint( code ) {
			var labels = document.querySelectorAll( '[data-cur-label]' );
			for ( var i = 0; i < labels.length; i++ ) {
				labels[ i ].textContent = LABEL[ code ];
			}
			var options = document.querySelectorAll( '.bw-cur-menu button' );
			for ( var j = 0; j < options.length; j++ ) {
				var on = options[ j ].getAttribute( 'data-cur' ) === code;
				options[ j ].classList.toggle( 'on', on );
				options[ j ].setAttribute( 'aria-selected', on ? 'true' : 'false' );
			}
		}

		function closeAll() {
			for ( var i = 0; i < drops.length; i++ ) {
				drops[ i ].classList.remove( 'open' );
				var btn = drops[ i ].querySelector( '.bw-cur-btn' );
				if ( btn ) {
					btn.setAttribute( 'aria-expanded', 'false' );
				}
			}
		}

		paint( current );

		Array.prototype.forEach.call( drops, function ( drop ) {
			var toggle = drop.querySelector( '.bw-cur-btn' );
			if ( ! toggle ) {
				return;
			}

			toggle.addEventListener( 'click', function ( event ) {
				event.stopPropagation();
				var willOpen = ! drop.classList.contains( 'open' );
				closeAll();
				if ( willOpen ) {
					drop.classList.add( 'open' );
					toggle.setAttribute( 'aria-expanded', 'true' );
				}
			} );

			var options = drop.querySelectorAll( '.bw-cur-menu button' );
			Array.prototype.forEach.call( options, function ( option ) {
				option.addEventListener( 'click', function ( event ) {
					event.stopPropagation();
					var code = option.getAttribute( 'data-cur' );
					if ( ! LABEL[ code ] ) {
						return;
					}
					try {
						localStorage.setItem( 'bw-currency', code );
					} catch {
						// Private mode: the choice still applies to this page.
					}
					paint( code );
					closeAll();
					window.dispatchEvent( new CustomEvent( 'bw:currency', { detail: code } ) );
				} );
			} );
		} );

		document.addEventListener( 'click', closeAll );
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				closeAll();
			}
		} );
	}

	ready( function () {
		var nav = document.querySelector( 'nav' );

		if ( ! nav ) {
			return;
		}

		var state = { mobileOpen: false };

		initDropdowns( nav );
		initMobileMenu( nav, state );
		initCurrencySwitcher();
		initScroll( nav, state );
	} );
}() );
