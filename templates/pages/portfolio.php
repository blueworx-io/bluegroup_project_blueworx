<?php
/**
 * Portfolio page template (the page formerly called Work).
 *
 * A two-column tech-hero (the `tech-hero` part in centered => false mode
 * beside a `glass-card` results.log), a `.work-grid` of every live client
 * site from blueworx_content_portfolio() — each card a link that opens the
 * site in a new tab — a `stats-band` part, and the shared reviews section
 * every marketing page carries (blueworx_content_reviews()).
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

$blueworx_pf_sites = blueworx_content_portfolio();

$blueworx_pf_stats = array(
	array(
		'value' => '5.0',
		'star'  => true,
		'label' => __( 'Google Rating', 'bluegroup-project-blueworx' ),
	),
	array(
		'value' => '82+',
		'label' => __( 'Projects Completed', 'bluegroup-project-blueworx' ),
	),
	array(
		'value' => '100k +',
		'label' => __( 'Revenue Handled', 'bluegroup-project-blueworx' ),
	),
	array(
		'value' => '99.9%',
		'label' => __( 'Uptime Maintained', 'bluegroup-project-blueworx' ),
	),
);

blueworx_public_document_open( array( 'body_class' => 'bw-portfolio' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main id="content" tabindex="-1">
	<div>
		<section class="tech-hero">
			<div class="tech-inner tech-2col">
				<div class="tc-copy">
					<?php
					blueworx_public_part(
						'parts/tech-hero.php',
						array(
							'centered'        => false,
							'badge'           => __( 'Portfolio', 'bluegroup-project-blueworx' ),
							'title'           => __( 'Websites We Have Built and Look After', 'bluegroup-project-blueworx' ),
							'title_highlight' => __( 'Look After', 'bluegroup-project-blueworx' ),
							'lead'            => __( 'Live sites for sports bodies, studios, schools, suppliers and destinations. Every one designed, built and hosted by BlueWorx, and every one still running on our support.', 'bluegroup-project-blueworx' ),
							'cta'             => array(
								array(
									'label' => __( 'Start a Project', 'bluegroup-project-blueworx' ),
									'href'  => home_url( '/contact' ),
									'class' => 'btn btn-white btn-lg',
								),
								array(
									'label' => __( 'Integrated Support', 'bluegroup-project-blueworx' ),
									'href'  => home_url( '/support' ),
									'class' => 'btn btn-outline-w btn-lg',
								),
							),
							'meta'            => array(
								__( '82+ projects', 'bluegroup-project-blueworx' ),
								__( '4.9 rating', 'bluegroup-project-blueworx' ),
								__( '99.9% uptime', 'bluegroup-project-blueworx' ),
							),
						)
					);
					?>
				</div>
				<?php
				ob_start();
				?>
				<div class="gc-metric"><small><?php echo esc_html__( 'Hirasté — booking enquiries', 'bluegroup-project-blueworx' ); ?></small><b>+64%</b><span class="up">▲</span></div>
				<div class="gc-metric"><small><?php echo esc_html__( 'PadLX — launch season', 'bluegroup-project-blueworx' ); ?></small><b><?php echo esc_html__( 'Sold out', 'bluegroup-project-blueworx' ); ?></b><span class="up">▲</span></div>
				<div class="gc-metric" style="border-bottom:none"><small><?php echo esc_html__( 'Live sites on support', 'bluegroup-project-blueworx' ); ?></small><b><?php echo esc_html( (string) count( $blueworx_pf_sites ) ); ?></b><span class="up">▲</span></div>
				<div class="gc-spark">
					<i style="height:30%"></i><i style="height:44%"></i><i style="height:58%"></i><i style="height:52%"></i><i style="height:70%"></i><i class="hi" style="height:96%"></i><i style="height:78%"></i><i style="height:88%"></i>
				</div>
				<?php
				$blueworx_pf_gc_body = ob_get_clean();

				blueworx_public_part(
					'parts/glass-card.php',
					array(
						'tag'    => __( 'results.log', 'bluegroup-project-blueworx' ),
						'body'   => $blueworx_pf_gc_body,
						'floats' => array(
							array(
								'icon'  => 'chart',
								'label' => __( 'Avg. lift', 'bluegroup-project-blueworx' ),
								'value' => '+41%',
								'style' => 'bottom:-22px;left:-26px;animation-delay:.6s',
							),
						),
					)
				);
				?>
			</div>
		</section>

		<section class="sec" style="padding-top:52px">
			<?php // The design gives this section no visible heading, so its cards would follow the page h1 as h3s with nothing in between — a screen reader announces a level as missing. Named here for assistive tech only; sighted layout is unchanged. ?>
			<h2 class="bw-sr-only"><?php esc_html_e( 'Live client sites', 'bluegroup-project-blueworx' ); ?></h2>
			<div class="work-grid">
				<?php
				foreach ( $blueworx_pf_sites as $blueworx_pf_site ) {
					blueworx_public_part(
						'parts/work-card.php',
						array(
							'img'       => 'img/portfolio/' . $blueworx_pf_site['slug'] . '.jpg',
							'alt'       => sprintf(
								/* translators: %s: client name. */
								__( 'The %s website', 'bluegroup-project-blueworx' ),
								$blueworx_pf_site['name']
							),
							'tags'      => $blueworx_pf_site['tags'],
							'name'      => $blueworx_pf_site['name'],
							'desc'      => $blueworx_pf_site['sector'],
							'res_value' => (string) wp_parse_url( $blueworx_pf_site['url'], PHP_URL_HOST ),
							'res_text'  => '↗',
							'href'      => $blueworx_pf_site['url'],
							'external'  => true,
						)
					);
				}
				?>
			</div>
		</section>

		<?php
		blueworx_public_part(
			'parts/stats-band.php',
			array(
				'title' => __( 'Outcomes, not just outputs.', 'bluegroup-project-blueworx' ),
				'copy'  => __( 'Every engagement is measured against the goals we set together: traffic, conversions, and revenue. Not vanity metrics.', 'bluegroup-project-blueworx' ),
				'stats' => $blueworx_pf_stats,
			)
		);

		blueworx_public_part(
			'parts/testimonials.php',
			array(
				'testimonials' => blueworx_content_reviews(),
			)
		);
		?>
	</div>
</main>
<?php
blueworx_public_part( 'parts/footer.php' );
blueworx_public_document_close();
