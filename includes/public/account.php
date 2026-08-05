<?php
/**
 * Public front-end layer — the client area.
 *
 * The client dashboard was SureDash's (`/portal`, `/customer-dashboard`), which
 * is being removed (#34). This file is the plugin's replacement: four pages —
 * an overview and a section each for subscriptions, invoices and orders — that
 * the plugin creates, owns and renders like any other page here.
 *
 * What is different from the marketing pages, and why:
 *
 * - **They are gated.** Everything in the client area is somebody's billing
 *   information, so a logged-out request is sent to log in rather than shown an
 *   empty page. The gate is one check applied to every page in the registry, so
 *   adding a section cannot accidentally add an ungated one.
 * - **They are never indexed.** A search engine should not hold a copy of a
 *   customer's dashboard, however empty it looks when crawled logged out.
 * - **They do not read SureCart yet.** The overview shows the account details
 *   WordPress already holds; the three sections show an empty state until #38,
 *   #39 and #40 wire each one to SureCart.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The dashboard's root slug.
 *
 * One constant rather than a literal in nine places: it is the page slug, the
 * parent of every section, the prefix the gate matches on and the base of every
 * link between sections.
 */
const BLUEWORX_ACCOUNT_ROOT = 'dashboard';

/**
 * The sections of the client area, in the order they appear.
 *
 * Keyed by the child slug under the dashboard root. The overview is the root
 * itself and so is not listed here.
 *
 * `group` is the sidebar heading a section sits under. It used to be a single
 * hard-coded "Billing" label in dash-shell.php, which only worked while every
 * section was a billing one (#97, #98, #99).
 *
 * @return array Slug => array( label, title, kicker, group, icon, template, blurb ).
 */
function blueworx_account_sections() {
	$sections = array(
		'subscriptions' => array(
			'label'    => __( 'Subscriptions', 'bluegroup-project-blueworx' ),
			'title'    => __( 'Subscriptions', 'bluegroup-project-blueworx' ),
			'kicker'   => __( 'Billing', 'bluegroup-project-blueworx' ),
			'group'    => __( 'Billing', 'bluegroup-project-blueworx' ),
			'icon'     => 'clock',
			'template' => 'pages/dashboard-subscriptions.php',
			'blurb'    => __( 'Your active plans, what they cost, and when they renew.', 'bluegroup-project-blueworx' ),
		),
		'invoices'      => array(
			'label'    => __( 'Invoices', 'bluegroup-project-blueworx' ),
			'title'    => __( 'Invoices', 'bluegroup-project-blueworx' ),
			'kicker'   => __( 'Billing', 'bluegroup-project-blueworx' ),
			'group'    => __( 'Billing', 'bluegroup-project-blueworx' ),
			'icon'     => 'doc',
			'template' => 'pages/dashboard-invoices.php',
			'blurb'    => __( 'Every invoice on your account.', 'bluegroup-project-blueworx' ),
		),
		'orders'        => array(
			'label'    => __( 'Orders', 'bluegroup-project-blueworx' ),
			'title'    => __( 'Orders', 'bluegroup-project-blueworx' ),
			'kicker'   => __( 'Billing', 'bluegroup-project-blueworx' ),
			'group'    => __( 'Billing', 'bluegroup-project-blueworx' ),
			'icon'     => 'cart',
			'template' => 'pages/dashboard-orders.php',
			'blurb'    => __( 'Everything you have ordered from us.', 'bluegroup-project-blueworx' ),
		),
		'websites'      => array(
			'label'    => __( 'Websites', 'bluegroup-project-blueworx' ),
			'title'    => __( 'Your websites', 'bluegroup-project-blueworx' ),
			'kicker'   => __( 'Your plan', 'bluegroup-project-blueworx' ),
			'group'    => __( 'Your plan', 'bluegroup-project-blueworx' ),
			'icon'     => 'server',
			'template' => 'pages/dashboard-websites.php',
			'blurb'    => __( 'The sites we look after for you, and where each one is.', 'bluegroup-project-blueworx' ),
		),
		'toolbox'       => array(
			'label'    => __( 'Toolbox', 'bluegroup-project-blueworx' ),
			'title'    => __( 'Your Toolbox', 'bluegroup-project-blueworx' ),
			'kicker'   => __( 'Your plan', 'bluegroup-project-blueworx' ),
			'group'    => __( 'Your plan', 'bluegroup-project-blueworx' ),
			'icon'     => 'plug',
			'template' => 'pages/dashboard-toolbox.php',
			'blurb'    => __( 'The premium tools included with your plan.', 'bluegroup-project-blueworx' ),
		),
		// Grouped headings are emitted when the group CHANGES, so every section
		// in a group must sit together in this array. Partner between details
		// and support would print "Account" twice.
		'partner'       => array(
			'label'    => __( 'Partner', 'bluegroup-project-blueworx' ),
			'title'    => __( 'Your referrals', 'bluegroup-project-blueworx' ),
			'kicker'   => __( 'Partner', 'bluegroup-project-blueworx' ),
			'group'    => __( 'Partner', 'bluegroup-project-blueworx' ),
			'icon'     => 'users',
			'template' => 'pages/dashboard-partner.php',
			'blurb'    => __( 'Businesses you have sent us, and what has been paid.', 'bluegroup-project-blueworx' ),
			// Only shown to somebody who actually has referrals. Every client
			// would otherwise carry a Partner tab into an empty page about a
			// scheme they are not on.
			'only_if'  => 'blueworx_is_partner',
		),
		'details'       => array(
			'label'    => __( 'Your details', 'bluegroup-project-blueworx' ),
			'title'    => __( 'Your details', 'bluegroup-project-blueworx' ),
			'kicker'   => __( 'Account', 'bluegroup-project-blueworx' ),
			'group'    => __( 'Account', 'bluegroup-project-blueworx' ),
			'icon'     => 'users',
			'template' => 'pages/dashboard-details.php',
			'blurb'    => __( 'Your name, company and email, and your password.', 'bluegroup-project-blueworx' ),
		),
		'support'       => array(
			'label'    => __( 'Support', 'bluegroup-project-blueworx' ),
			'title'    => __( 'Support', 'bluegroup-project-blueworx' ),
			'kicker'   => __( 'Account', 'bluegroup-project-blueworx' ),
			'group'    => __( 'Account', 'bluegroup-project-blueworx' ),
			'icon'     => 'chat',
			'template' => 'pages/dashboard-support.php',
			'blurb'    => __( 'Ask us for help, and see who to contact.', 'bluegroup-project-blueworx' ),
		),
	);

	/**
	 * Filters the client-area sections.
	 *
	 * @param array $sections Section definitions keyed by child slug.
	 */
	return (array) apply_filters( 'blueworx_account_sections', $sections );
}

/**
 * The sections to show the current client in the sidebar and on the overview.
 *
 * Deliberately NOT the same list blueworx_account_register_pages() uses. Every
 * section keeps its page — created, owned, gated and never indexed like the
 * rest — because hiding a tab must not mean the address stops existing: a
 * partner whose referrals are entered next week would otherwise be linked to a
 * page that 404s. This only decides what is offered.
 *
 * @return array Slug => section, filtered to the ones this client should see.
 */
function blueworx_account_visible_sections() {
	$visible = array();

	foreach ( blueworx_account_sections() as $slug => $section ) {
		if ( isset( $section['only_if'] ) && is_callable( $section['only_if'] ) && ! call_user_func( $section['only_if'] ) ) {
			continue;
		}

		$visible[ $slug ] = $section;
	}

	return $visible;
}

/**
 * Registers the client-area pages alongside the marketing ones.
 *
 * Hooked onto the same registry the rest of the front end uses, so these pages
 * are installed, owned, stamped, rendered and protected by exactly the same
 * code paths — there is no second, parallel notion of "a page this plugin
 * owns" to keep in step.
 *
 * @param array $pages Pages from blueworx_public_pages().
 * @return array
 */
function blueworx_account_register_pages( $pages ) {
	// The `account` flag is what the gate below tests. It travels with the
	// registry entry, so a client-area page stays gated however it is renamed
	// or moved, and a section added later is gated by existing — not by
	// somebody remembering to add its path to a second list.
	$pages[ BLUEWORX_ACCOUNT_ROOT ] = array(
		'title'    => __( 'Dashboard', 'bluegroup-project-blueworx' ),
		'template' => 'pages/dashboard.php',
		'account'  => true,
	);

	foreach ( blueworx_account_sections() as $slug => $section ) {
		$pages[ BLUEWORX_ACCOUNT_ROOT . '/' . $slug ] = array(
			'title'    => $section['title'],
			'template' => $section['template'],
			'slug'     => $slug,
			'parent'   => BLUEWORX_ACCOUNT_ROOT,
			'account'  => true,
		);
	}

	return $pages;
}
add_filter( 'blueworx_public_pages', 'blueworx_account_register_pages' );

/**
 * The URL of a client-area page.
 *
 * @param string $section Optional child slug. Empty for the overview.
 * @return string Absolute URL.
 */
function blueworx_account_url( $section = '' ) {
	$path = BLUEWORX_ACCOUNT_ROOT . ( '' === $section ? '' : '/' . trim( $section, '/' ) );

	return home_url( '/' . $path );
}

/**
 * Whether the current request is for a client-area page.
 *
 * Asks the page layer which of the plugin's pages is being rendered rather than
 * matching on the request path: that resolution goes through the plugin's own
 * page-ID map, so a dashboard page that has been renamed or moved is still
 * recognised as one, and a page the plugin does not own can never be mistaken
 * for one by having a matching slug.
 *
 * blueworx_public_current_page() returns the registry ENTRY, not its key, so
 * this reads the flag set in blueworx_account_register_pages() rather than
 * comparing paths.
 *
 * @return bool
 */
function blueworx_account_is_account_request() {
	if ( ! function_exists( 'blueworx_public_current_page' ) ) {
		return false;
	}

	$current = blueworx_public_current_page();

	return is_array( $current ) && ! empty( $current['account'] );
}

/**
 * Where a logged-out visitor is sent from a client-area page.
 *
 * Uses the same setting the nav's Client Login link uses, so there is one
 * answer to "where do clients log in" rather than two that can disagree. The
 * page they were heading for is passed along, so logging in lands them where
 * they meant to be instead of on a generic screen.
 *
 * @param string $requested Absolute URL the visitor asked for.
 * @return string Absolute URL to send them to.
 */
function blueworx_account_login_redirect_url( $requested ) {
	$login = blueworx_public_client_login_url();

	return add_query_arg( 'redirect_to', rawurlencode( $requested ), $login );
}

/**
 * Sends logged-out visitors away from the client area.
 *
 * The gate is deliberately here rather than inside each template: a template
 * that forgets to call it renders somebody's billing page to the public, and
 * "remember to add the check" is not a control. Running on template_redirect
 * means nothing has been sent yet, so this is a clean redirect rather than a
 * page that starts rendering and then stops.
 *
 * @return void
 */
function blueworx_account_require_login() {
	if ( is_user_logged_in() || ! blueworx_account_is_account_request() ) {
		return;
	}

	$requested = home_url( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/' );

	wp_safe_redirect( blueworx_account_login_redirect_url( $requested ), 302 );
	exit;
}
add_action( 'template_redirect', 'blueworx_account_require_login', 1 );

/*
 * Keeping the client area out of search results used to be an echo straight
 * into wp_head from here. It is now in includes/public/indexing.php, and it
 * covers the sign-in pages as well as the dashboard.
 *
 * The echo was the reason a dashboard page carried two robots tags saying
 * opposite things: SureRank prints its own tag from a per-post setting and
 * removes WordPress's, but it cannot remove markup somebody else echoed. The
 * page said "noindex, nofollow" and "index, follow" at once, and the crawler
 * picked one. Writing the setting SureRank reads says it once, and drops the
 * page from the sitemap in the same move.
 */

/**
 * The name to greet the logged-in client by.
 *
 * Prefers what they chose to be called, and never falls back to something that
 * looks like a system identifier.
 *
 * @return string
 */
function blueworx_account_display_name() {
	$user = wp_get_current_user();

	if ( ! $user || ! $user->exists() ) {
		return '';
	}

	foreach ( array( $user->first_name, $user->display_name, $user->user_nicename ) as $candidate ) {
		$candidate = trim( (string) $candidate );

		if ( '' !== $candidate ) {
			return $candidate;
		}
	}

	return '';
}

/**
 * The client's initials, for the sidebar avatar.
 *
 * Falls back to a single letter rather than to empty: an avatar circle with
 * nothing in it reads as a rendering fault.
 *
 * @return string One or two uppercase letters.
 */
function blueworx_account_initials() {
	$user = wp_get_current_user();

	if ( ! $user || ! $user->exists() ) {
		return '?';
	}

	$source = trim( (string) $user->display_name );

	if ( '' === $source ) {
		$source = (string) $user->user_email;
	}

	$parts    = preg_split( '/[\s@._-]+/', $source, -1, PREG_SPLIT_NO_EMPTY );
	$initials = '';

	foreach ( array_slice( (array) $parts, 0, 2 ) as $part ) {
		$initials .= mb_strtoupper( mb_substr( $part, 0, 1 ) );
	}

	return '' === $initials ? '?' : $initials;
}
