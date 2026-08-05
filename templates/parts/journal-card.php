<?php
/**
 * Journal article card template part (`.jr-card`) (#94, #95).
 *
 * One card, used in three places: the journal's featured slot (wide, two
 * columns), the journal grid, and the "keep reading" row at the foot of an
 * article. The featured variant is the same card with a different layout, not
 * a second card — the design draws them alike on purpose, and two templates
 * would drift.
 *
 * The whole card is the link. A separate "read more" inside a clickable card
 * gives a keyboard user two tab stops to the same place, and a screen reader
 * two announcements of it.
 *
 * $vars:
 * - post     (WP_Post, required) The article.
 * - featured (bool, optional)    Wide featured layout. Default false.
 * - compact  (bool, optional)    Drop the excerpt and author, keep date and
 *                                read time — the "keep reading" row. Default
 *                                false.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_jc_post = isset( $post ) ? get_post( $post ) : null;

if ( ! $blueworx_jc_post instanceof WP_Post ) {
	return;
}

$blueworx_jc_featured = isset( $featured ) && $featured;
$blueworx_jc_compact  = isset( $compact ) && $compact;
$blueworx_jc_term     = blueworx_public_post_category( $blueworx_jc_post );
$blueworx_jc_author   = (string) get_the_author_meta( 'display_name', (int) $blueworx_jc_post->post_author );
$blueworx_jc_initials = blueworx_public_initials( $blueworx_jc_author );
$blueworx_jc_excerpt  = (string) get_the_excerpt( $blueworx_jc_post );
$blueworx_jc_classes  = 'jr-card';

if ( $blueworx_jc_featured ) {
	$blueworx_jc_classes .= ' jr-card-feat';
}

if ( $blueworx_jc_compact ) {
	$blueworx_jc_classes .= ' jr-card-compact';
}
?>
<a class="<?php echo esc_attr( $blueworx_jc_classes ); ?>"
	href="<?php echo esc_url( (string) get_permalink( $blueworx_jc_post ) ); ?>"
	data-jr-cat="<?php echo esc_attr( $blueworx_jc_term ? $blueworx_jc_term->slug : '' ); ?>">
	<div class="jr-card-img">
		<?php if ( has_post_thumbnail( $blueworx_jc_post ) ) : ?>
			<?php
			// The card's own alt text is deliberately empty: the heading right
			// beside it already names the article, and a screen reader that
			// hears the title twice learns nothing the second time.
			echo get_the_post_thumbnail(
				$blueworx_jc_post,
				$blueworx_jc_featured ? 'large' : 'medium_large',
				array(
					'alt'     => '',
					'loading' => 'lazy',
				)
			);
			?>
		<?php else : ?>
			<?php // No image is a fair state for a written post. A tinted panel keeps the card's proportions rather than collapsing the grid row. ?>
			<span class="jr-card-noimg" aria-hidden="true"></span>
		<?php endif; ?>
	</div>
	<div class="jr-card-body">
		<div class="jr-card-top">
			<?php if ( $blueworx_jc_featured ) : ?>
				<span class="jr-feat-tag"><?php echo esc_html__( 'Featured', 'bluegroup-project-blueworx' ); ?></span>
			<?php endif; ?>
			<?php if ( $blueworx_jc_term ) : ?>
				<span class="jr-cat"><?php echo esc_html( $blueworx_jc_term->name ); ?></span>
			<?php endif; ?>
		</div>

		<h3 class="jr-card-title"><?php echo esc_html( get_the_title( $blueworx_jc_post ) ); ?></h3>

		<?php if ( ! $blueworx_jc_compact && '' !== $blueworx_jc_excerpt ) : ?>
			<p class="jr-card-excerpt"><?php echo esc_html( $blueworx_jc_excerpt ); ?></p>
		<?php endif; ?>

		<div class="jr-card-meta">
			<?php if ( ! $blueworx_jc_compact && '' !== $blueworx_jc_initials ) : ?>
				<span class="jr-avatar" aria-hidden="true"><?php echo esc_html( $blueworx_jc_initials ); ?></span>
				<span class="jr-card-author"><?php echo esc_html( $blueworx_jc_author ); ?></span>
			<?php endif; ?>
			<time datetime="<?php echo esc_attr( (string) get_the_date( 'c', $blueworx_jc_post ) ); ?>"><?php echo esc_html( (string) get_the_date( '', $blueworx_jc_post ) ); ?></time>
			<span class="jr-card-read"><?php echo esc_html( blueworx_public_read_label( $blueworx_jc_post ) ); ?></span>
		</div>
	</div>
</a>
