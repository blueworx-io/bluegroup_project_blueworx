<?php
/**
 * Plan cards grid (`.plans`), used by the Support and Toolbox pages.
 *
 * Ported from the PlanCards component in components/Plans.tsx. The source swaps
 * the displayed price between monthly and annual via React state shared with a
 * billing toggle; that toggle is a Plan 3 interactive widget, so here each
 * card's price shows the monthly figure and carries `data-price-m` /
 * `data-price-a` (plus matching sub-labels) for Plan 3 to swap client-side.
 *
 * The card markup itself lives in `plan-card.php` (one card per plan, reused
 * by pages that show a single plan beside a supporting panel); this part only
 * positions the grid and loops the plans into it.
 *
 * The wrapper's negative top margin pulls the cards up to overlap the preceding
 * `.pb-tall` hero, matching the source.
 *
 * $vars:
 * - plans (array, required) List of plans, each array(
 *     name, desc, priceM (int), priceA (int), feat (bool), pop (bool),
 *     features (string[]) ).
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_pcs_plans = isset( $plans ) && is_array( $plans ) ? $plans : array();
?>
<div class="plan-cards-wrap" style="margin:-190px var(--gut) 0;position:relative;z-index:3">
	<div class="plans">
		<?php foreach ( $blueworx_pcs_plans as $blueworx_pcs_plan ) : ?>
			<?php blueworx_public_part( 'parts/plan-card.php', array( 'plan' => $blueworx_pcs_plan ) ); ?>
		<?php endforeach; ?>
	</div>
</div>
