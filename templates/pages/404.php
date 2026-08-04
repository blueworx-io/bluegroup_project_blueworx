<?php
/**
 * Not-found page template (#78).
 *
 * The plugin renders every page it owns and, until now, nothing else — so a
 * bad URL fell through to whatever theme the site happens to have. On the live
 * site that is blankslate: a bare "Not Found" heading on a white page, with no
 * nav, no footer and no way back in. A 404 is a page a real visitor sees, and
 * it is the one page they arrive at already unsure whether the site works.
 *
 * Composed from the same parts as the marketing pages rather than styled on
 * its own, so it cannot drift away from the rest of the site: the same
 * document wrapper, the same nav, the same hero and the same footer.
 *
 * The <main><div> wrapper is required, not stylistic: globals.css targets
 * `main > div > .sec:last-child` to zero the final section's bottom padding.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Where to send somebody instead. Deliberately the whole site rather than only
// the home page: a visitor who followed a rotted link usually wanted something
// specific, and the fastest way back is a list of what is actually here.
$blueworx_404_links = array(
	array(
		'label' => __( 'Home', 'bluegroup-project-blueworx' ),
		'desc'  => __( 'What BlueWorx does, from the start.', 'bluegroup-project-blueworx' ),
		'href'  => home_url( '/' ),
	),
	array(
		'label' => __( 'Services', 'bluegroup-project-blueworx' ),
		'desc'  => __( 'Websites, hosting and support, and how each one works.', 'bluegroup-project-blueworx' ),
		'href'  => home_url( '/services' ),
	),
	array(
		'label' => __( 'Toolbox', 'bluegroup-project-blueworx' ),
		'desc'  => __( 'The twelve premium tools included with every plan.', 'bluegroup-project-blueworx' ),
		'href'  => home_url( '/toolbox' ),
	),
	array(
		'label' => __( 'Pricing', 'bluegroup-project-blueworx' ),
		'desc'  => __( 'Plans, what each includes, and what it costs.', 'bluegroup-project-blueworx' ),
		'href'  => home_url( '/pricing' ),
	),
	array(
		'label' => __( 'Contact', 'bluegroup-project-blueworx' ),
		'desc'  => __( 'Tell us what you were looking for and we will point you at it.', 'bluegroup-project-blueworx' ),
		'href'  => home_url( '/contact' ),
	),
);

blueworx_public_document_open( array( 'body_class' => 'bw-404' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main id="content" tabindex="-1">
	<div>
		<?php
		blueworx_public_part(
			'parts/tech-hero.php',
			array(
				'badge'           => __( 'Error 404', 'bluegroup-project-blueworx' ),
				'title'           => __( 'That page is not here', 'bluegroup-project-blueworx' ),
				'title_highlight' => __( 'not here', 'bluegroup-project-blueworx' ),
				'lead'            => __( 'The address you followed does not match anything on the site. It may have moved, or the link that brought you here may be out of date.', 'bluegroup-project-blueworx' ),
				'max_width'       => 760,
				'cta'             => array(
					array(
						'label' => __( 'Back to the home page', 'bluegroup-project-blueworx' ),
						'href'  => home_url( '/' ),
						'class' => 'btn btn-brand btn-md',
					),
					array(
						'label' => __( 'Tell us what you were after', 'bluegroup-project-blueworx' ),
						'href'  => home_url( '/contact' ),
						'class' => 'btn btn-outline-w btn-md',
					),
				),
			)
		);
		?>

		<section class="sec">
			<div class="wrap">
				<h2 class="bw-sr-only"><?php echo esc_html__( 'Where to go instead', 'bluegroup-project-blueworx' ); ?></h2>
				<div class="nf-links">
					<?php foreach ( $blueworx_404_links as $blueworx_404_link ) : ?>
						<a class="nf-link" href="<?php echo esc_url( $blueworx_404_link['href'] ); ?>">
							<span class="nf-link-label"><?php echo esc_html( $blueworx_404_link['label'] ); ?></span>
							<span class="nf-link-desc"><?php echo esc_html( $blueworx_404_link['desc'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	</div>
</main>
<?php
blueworx_public_part( 'parts/footer.php' );
blueworx_public_document_close();
