<?php
/**
 * One plan card (`.plan-card`).
 *
 * Extracted from plan-cards.php so a page that shows a single plan beside a
 * supporting panel (ClubHouse, Hosting) renders the same card as the
 * three-up `.plans` grid, rather than a hand-copied lookalike.
 *
 * $vars:
 * - plan (array, required) array(
 *     name, desc, priceM (int), priceA (int), feat (bool), pop (bool|string),
 *     features (string[]),
 *     buyM, buyA   (string, optional) SureCart checkout links per interval.
 *     currency     (string, optional) ISO code. 'GBP' renders "£" and marks
 *                  the amount with data-bw-gbp so public-widgets.js can
 *                  convert it; any other code renders that currency's sign
 *                  and is never converted. Absent: "$" and no conversion
 *                  (the Toolbox plans). A live SureCart amount brings its
 *                  own currency with it (includes/public/commerce.php).
 *     subM, subA   (string, optional) Period labels. Defaults: "per month" /
 *                  "per month, billed yearly".
 *     lbl          (string, optional) Feature-list label. Default "FEATURES".
 *   ).
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_pc_plan = isset( $plan ) && is_array( $plan ) ? $plan : array();

if ( empty( $blueworx_pc_plan['name'] ) ) {
	return;
}

$blueworx_pc_feat   = ! empty( $blueworx_pc_plan['feat'] );
$blueworx_pc_pop    = isset( $blueworx_pc_plan['pop'] ) ? $blueworx_pc_plan['pop'] : false;
$blueworx_pc_cur    = isset( $blueworx_pc_plan['currency'] ) ? strtoupper( (string) $blueworx_pc_plan['currency'] ) : 'USD';
$blueworx_pc_gbp    = 'GBP' === $blueworx_pc_cur;
$blueworx_pc_signs  = array(
	'GBP' => '£',
	'EUR' => '€',
	'USD' => '$',
);
$blueworx_pc_symbol = isset( $blueworx_pc_signs[ $blueworx_pc_cur ] ) ? $blueworx_pc_signs[ $blueworx_pc_cur ] : $blueworx_pc_cur . ' ';
$blueworx_pc_btn    = $blueworx_pc_feat ? 'plan-btn dark' : 'plan-btn out';
$blueworx_pc_sub_m  = isset( $blueworx_pc_plan['subM'] ) ? (string) $blueworx_pc_plan['subM'] : __( 'per month', 'bluegroup-project-blueworx' );
$blueworx_pc_sub_a  = isset( $blueworx_pc_plan['subA'] ) ? (string) $blueworx_pc_plan['subA'] : __( 'per month, billed yearly', 'bluegroup-project-blueworx' );
$blueworx_pc_lbl    = isset( $blueworx_pc_plan['lbl'] ) ? (string) $blueworx_pc_plan['lbl'] : __( 'FEATURES', 'bluegroup-project-blueworx' );
$blueworx_pc_buy_m  = isset( $blueworx_pc_plan['buyM'] ) ? (string) $blueworx_pc_plan['buyM'] : '';
$blueworx_pc_buy_a  = isset( $blueworx_pc_plan['buyA'] ) ? (string) $blueworx_pc_plan['buyA'] : '';
$blueworx_pc_href   = '' !== $blueworx_pc_buy_m ? $blueworx_pc_buy_m : home_url( '/contact' );

// The feature check glyph, ported verbatim from components/Plans.tsx.
$blueworx_pc_check = '<svg class="ck" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm-1 14.4l-4.2-4.2 1.5-1.5 2.7 2.7 5-5 1.5 1.5z"/></svg>';
?>
<div class="<?php echo $blueworx_pc_feat ? 'plan-card feat' : 'plan-card'; ?>">
	<div class="plan-top">
		<div class="plan-name">
			<span><?php echo esc_html( $blueworx_pc_plan['name'] ); ?></span>
			<?php if ( $blueworx_pc_pop ) : ?>
				<span class="pop"><?php echo esc_html( is_string( $blueworx_pc_pop ) ? $blueworx_pc_pop : __( 'Popular', 'bluegroup-project-blueworx' ) ); ?></span>
			<?php endif; ?>
		</div>
		<div class="plan-desc"><?php echo esc_html( $blueworx_pc_plan['desc'] ); ?></div>
		<div class="plan-price"<?php echo $blueworx_pc_gbp ? ' data-cur="GBP"' : ''; ?> data-symbol="<?php echo esc_attr( $blueworx_pc_symbol ); ?>" data-price-m="<?php echo esc_attr( (string) $blueworx_pc_plan['priceM'] ); ?>" data-price-a="<?php echo esc_attr( (string) $blueworx_pc_plan['priceA'] ); ?>">
			<b<?php echo $blueworx_pc_gbp ? ' data-bw-gbp="' . esc_attr( (string) $blueworx_pc_plan['priceM'] ) . '"' : ''; ?>><?php echo esc_html( $blueworx_pc_symbol . number_format( (float) $blueworx_pc_plan['priceM'], 0, '.', ',' ) ); ?></b>
			<em data-sub-m="<?php echo esc_attr( $blueworx_pc_sub_m ); ?>" data-sub-a="<?php echo esc_attr( $blueworx_pc_sub_a ); ?>"><?php echo esc_html( $blueworx_pc_sub_m ); ?></em>
		</div>
		<a href="<?php echo esc_url( $blueworx_pc_href ); ?>"
			<?php if ( '' !== $blueworx_pc_buy_m ) : ?>
				data-buy-m="<?php echo esc_url( $blueworx_pc_buy_m ); ?>"
			<?php endif; ?>
			<?php if ( '' !== $blueworx_pc_buy_a ) : ?>
				data-buy-a="<?php echo esc_url( $blueworx_pc_buy_a ); ?>"
			<?php endif; ?>
			class="<?php echo esc_attr( $blueworx_pc_btn ); ?>" style="display:flex;align-items:center;justify-content:center;text-decoration:none"><?php esc_html_e( 'Get started', 'bluegroup-project-blueworx' ); ?></a>
	</div>
	<div class="plan-feats">
		<div class="lbl"><?php echo esc_html( $blueworx_pc_lbl ); ?></div>
		<?php foreach ( (array) $blueworx_pc_plan['features'] as $blueworx_pc_feature ) : ?>
			<div class="pf">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted check glyph.
				echo $blueworx_pc_check;
				?>
				<?php echo esc_html( $blueworx_pc_feature ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
