<?php
/**
 * FAQ section (`.sec` > `.center-head` + `.faq-list`), from the 2026-09 designs.
 *
 * Used by the ClubHouse, Hosting and Integrated Support pages, which each
 * render the same accordion under their own heading and lead. Each question is
 * a native <details> with a chevron in the summary; the `.faq-item[open]`
 * rules in assets/css/public.css turn it, and no script is involved.
 *
 * $vars:
 * - faqs  (array, required) List of array( 'q' => string, 'a' => string ).
 * - lead  (string, required) The sentence under the heading.
 * - title (string) Heading. Defaults to "Frequently asked questions".
 * - class (string) Extra classes on the section, e.g. 'bw-divided'.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_faq_items = isset( $faqs ) && is_array( $faqs ) ? $faqs : array();
$blueworx_faq_title = isset( $title ) ? (string) $title : __( 'Frequently asked questions', 'bluegroup-project-blueworx' );
$blueworx_faq_lead  = isset( $lead ) ? (string) $lead : '';
$blueworx_faq_class = isset( $class ) ? trim( 'sec ' . (string) $class ) : 'sec';
?>
<section class="<?php echo esc_attr( $blueworx_faq_class ); ?>">
	<div class="center-head" style="margin-bottom:40px">
		<h2 class="h2"><?php echo esc_html( $blueworx_faq_title ); ?></h2>
		<?php if ( '' !== $blueworx_faq_lead ) : ?>
			<p class="lead"><?php echo esc_html( $blueworx_faq_lead ); ?></p>
		<?php endif; ?>
	</div>
	<div class="faq-list">
		<?php foreach ( $blueworx_faq_items as $blueworx_faq ) : ?>
			<details class="faq-item">
				<summary class="faq-q">
					<?php echo esc_html( $blueworx_faq['q'] ); ?>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
				</summary>
				<div class="faq-a"><p><?php echo esc_html( $blueworx_faq['a'] ); ?></p></div>
			</details>
		<?php endforeach; ?>
	</div>
</section>
