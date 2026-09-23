<?php
/**
 * Public front-end layer — the Sales section, on the Labs customer dashboard.
 *
 * Clients have one dashboard: the one the BlueWorx Labs plugin draws on the
 * shop's customer dashboard page (/customer-dashboard/). This plugin used to
 * build a second one of its own at /dashboard. Now it only adds the two panels
 * that are ours to add — Commission and Quote Builder — through the hooks Labs
 * offers for exactly this (docs/store-pages-api.md in that repo).
 *
 * Both panels are about what a sale pays us, so both are for somebody allowed
 * to sell and nobody else. That is checked twice, on purpose: once when the
 * nav is built, and again when a panel is drawn. Labs draws every panel on
 * every visit and only hides the ones not being read, so a nav check alone
 * would still print our commission rates into a client's page.
 *
 * Without Labs nothing here does anything: the views filter is never applied
 * and the shortcodes are never asked for.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Sales panels, in the order the dashboard nav shows them.
 *
 * `icon` is a Lucide name from the set Labs' design system ships.
 *
 * @return array Key => array( label, title, lede, icon, shortcode, template ).
 */
function blueworx_sales_panels() {
	return array(
		'commission'    => array(
			'label'     => __( 'Commission', 'bluegroup-project-blueworx' ),
			'title'     => __( 'Commission', 'bluegroup-project-blueworx' ),
			'lede'      => __( 'Work out what you earn on a sale before you send the quote.', 'bluegroup-project-blueworx' ),
			'icon'      => 'trending-up',
			'shortcode' => 'blueworx_commission',
			'template'  => 'parts/sales-commission.php',
		),
		'quote-builder' => array(
			'label'     => __( 'Quote Builder', 'bluegroup-project-blueworx' ),
			'title'     => __( 'Quote Builder', 'bluegroup-project-blueworx' ),
			'lede'      => __( 'Size a build or a support package, and see what it costs and what it pays.', 'bluegroup-project-blueworx' ),
			'icon'      => 'file-text',
			'shortcode' => 'blueworx_quote_builder',
			'template'  => 'parts/sales-quote-builder.php',
		),
	);
}

/**
 * Adds the Sales panels to the Labs dashboard, for somebody allowed to sell.
 *
 * Sidebar only. The phone's bottom bar is a short, curated list; the panels
 * are still one tap away from the dashboard's overview, which links every view.
 *
 * @param array $views Views from Labs, in nav order.
 * @return array
 */
function blueworx_sales_views( $views ) {
	if ( ! blueworx_user_can_sell() ) {
		return $views;
	}

	foreach ( blueworx_sales_panels() as $key => $panel ) {
		$views[] = array(
			'key'       => $key,
			'label'     => $panel['label'],
			'title'     => $panel['title'],
			'lede'      => $panel['lede'],
			'icon'      => $panel['icon'],
			'where'     => 'side',
			'blocks'    => array(),
			'shortcode' => $panel['shortcode'],
		);
	}

	return $views;
}
add_filter( 'blueworx_store_views', 'blueworx_sales_views' );

/**
 * One Sales panel's markup, or '' for anybody not allowed to sell.
 *
 * Wrapped in .blueworx-sales, which is what assets/css/sales.css is scoped to,
 * so none of it can reach the rest of the dashboard.
 *
 * @param string $key A key from blueworx_sales_panels().
 * @return string
 */
function blueworx_sales_render( $key ) {
	$panels = blueworx_sales_panels();

	if ( ! isset( $panels[ $key ] ) || ! blueworx_user_can_sell() ) {
		return '';
	}

	ob_start();
	echo '<div class="blueworx-sales">';
	blueworx_public_part( $panels[ $key ]['template'] );
	echo '</div>';

	return (string) ob_get_clean();
}

/**
 * The [blueworx_commission] shortcode.
 *
 * @return string
 */
function blueworx_sales_commission_shortcode() {
	return blueworx_sales_render( 'commission' );
}
add_shortcode( 'blueworx_commission', 'blueworx_sales_commission_shortcode' );

/**
 * The [blueworx_quote_builder] shortcode.
 *
 * @return string
 */
function blueworx_sales_quote_builder_shortcode() {
	return blueworx_sales_render( 'quote-builder' );
}
add_shortcode( 'blueworx_quote_builder', 'blueworx_sales_quote_builder_shortcode' );

/**
 * Where a signed-in client's dashboard is.
 *
 * The Labs dashboard when it is there, otherwise the home page — never an
 * address that would 404.
 *
 * @return string Absolute URL.
 */
function blueworx_dashboard_url() {
	$url = function_exists( 'blueworx_store_page_url' ) ? (string) blueworx_store_page_url( 'dashboard' ) : '';

	return '' !== $url ? $url : home_url( '/' );
}

/**
 * Whether this request is for the Labs dashboard page.
 *
 * @return bool
 */
function blueworx_sales_is_dashboard_request() {
	if ( ! function_exists( 'blueworx_store_page_id' ) ) {
		return false;
	}

	$id = (int) blueworx_store_page_id( 'dashboard' );

	return $id > 0 && is_page( $id );
}

/**
 * Loads the Sales panels' styles and scripts on the Labs dashboard.
 *
 * In the head rather than from the shortcode, so a salesperson who opens the
 * dashboard straight onto a panel does not see it unstyled first. Nothing for
 * anybody else: the scripts carry our commission rates.
 *
 * @return void
 */
function blueworx_sales_enqueue_assets() {
	if ( ! blueworx_sales_is_dashboard_request() || ! blueworx_user_can_sell() ) {
		return;
	}

	blueworx_public_enqueue_sales_style();
	blueworx_public_enqueue_widgets();
	blueworx_public_enqueue_quote_script();
	blueworx_public_enqueue_commission_script();
}
add_action( 'wp_enqueue_scripts', 'blueworx_sales_enqueue_assets' );
