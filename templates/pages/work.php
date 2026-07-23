<?php
/**
 * Work page template.
 *
 * Ported from app/work/page.tsx's four sections, in source order: a two-column
 * tech-hero (the `tech-hero` part in centered => false mode, wrapped in this
 * page's own `.tech-inner.tech-2col`, plus a `glass-card` part showing a
 * results.log with three metrics and an 8-bar spark), a `.work-grid` of six
 * non-linked project cards (the `work-card` part in its plain `<div>` mode —
 * no href), a `stats-band` part, and a testimonials section using Work's own
 * three testimonials (not the shared homepage reviews) and its own heading, via
 * the `testimonials` part's overridable eyebrow/title/testimonials vars.
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

// The six projects. Non-linked cards (the source renders plain <div>s), so no
// href is passed to the work-card part. Images are the re-encoded assets:
// feature-image-1..4 and fig-collab are .jpg; hero-image stays .png.
$blueworx_work_projects = array(
	array(
		'image'    => 'feature-image-1.jpg',
		'alt'      => __( 'Hirasté website', 'blueworx-site' ),
		'tags'     => array( __( 'Web Design', 'blueworx-site' ), __( 'Booking Platform', 'blueworx-site' ) ),
		'name'     => 'Hirasté',
		'res'      => '+64%',
		'res_text' => __( 'group booking enquiries', 'blueworx-site' ),
	),
	array(
		'image'    => 'feature-image-3.jpg',
		'alt'      => __( 'Padel365 website', 'blueworx-site' ),
		'tags'     => array( __( 'E-commerce', 'blueworx-site' ), __( 'Court Booking', 'blueworx-site' ) ),
		'name'     => 'Padel365',
		'res'      => __( 'Sold-out', 'blueworx-site' ),
		'res_text' => __( 'launch season', 'blueworx-site' ),
	),
	array(
		'image'    => 'feature-image-4.jpg',
		'alt'      => __( 'QURE website', 'blueworx-site' ),
		'tags'     => array( __( 'Brand', 'blueworx-site' ), __( 'Web Build', 'blueworx-site' ) ),
		'name'     => 'QURE',
		'res'      => '+38%',
		'res_text' => __( 'conversion rate', 'blueworx-site' ),
	),
	array(
		'image'    => 'feature-image-2.jpg',
		'alt'      => __( 'Bloom & Co. website', 'blueworx-site' ),
		'tags'     => array( __( 'Migration', 'blueworx-site' ), __( 'Managed Hosting', 'blueworx-site' ) ),
		'name'     => 'Bloom & Co.',
		'res'      => __( 'Zero-downtime', 'blueworx-site' ),
		'res_text' => __( 'platform migration', 'blueworx-site' ),
	),
	array(
		'image'    => 'fig-collab.jpg',
		'alt'      => __( 'chromaesthesia website', 'blueworx-site' ),
		'tags'     => array( __( 'Web Design', 'blueworx-site' ), __( 'CMS', 'blueworx-site' ) ),
		'name'     => 'chromaesthesia',
		'res'      => __( '2× faster', 'blueworx-site' ),
		'res_text' => __( 'publishing workflow', 'blueworx-site' ),
	),
	array(
		'image'    => 'hero-image.png',
		'alt'      => __( 'Reid Consulting website', 'blueworx-site' ),
		'tags'     => array( __( 'SEO', 'blueworx-site' ), __( 'Growth Retainer', 'blueworx-site' ) ),
		'name'     => 'Reid Consulting',
		'res'      => '3×',
		'res_text' => __( 'organic traffic in 12 months', 'blueworx-site' ),
	),
);

$blueworx_work_stats = array(
	array(
		'value' => '5.0',
		'star'  => true,
		'label' => __( 'Google Rating', 'blueworx-site' ),
	),
	array(
		'value' => '82+',
		'label' => __( 'Projects Completed', 'blueworx-site' ),
	),
	array(
		'value' => '100k +',
		'label' => __( 'Revenue Handled', 'blueworx-site' ),
	),
	array(
		'value' => '2K +',
		'label' => __( 'Toolbox Value', 'blueworx-site' ),
	),
);

// Work's own testimonials, distinct from the shared homepage reviews.
$blueworx_work_testimonials = array(
	array(
		'text'     => __( '"BlueWorx has completely transformed how we manage our website. The tools are powerful and the support team is incredibly responsive."', 'blueworx-site' ),
		'initials' => 'SJ',
		'name'     => 'Sarah Johnson',
		'role'     => __( 'Owner, Fresh Bakery Co.', 'blueworx-site' ),
	),
	array(
		'text'     => __( '"The live chat and booking system have increased our conversion rate significantly. Worth every penny — and then some."', 'blueworx-site' ),
		'initials' => 'MR',
		'name'     => 'Marcus Reid',
		'role'     => __( 'Director, Reid Consulting', 'blueworx-site' ),
	),
	array(
		'text'     => __( '"Finally, one platform that does it all. We cancelled three separate subscriptions when we switched to BlueWorx."', 'blueworx-site' ),
		'initials' => 'AL',
		'name'     => 'Amy Leung',
		'role'     => __( 'Founder, Leung Law Group', 'blueworx-site' ),
	),
);

blueworx_public_document_open( array( 'body_class' => 'bw-work' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main>
	<div>
		<section class="tech-hero">
			<div class="tech-inner tech-2col">
				<div class="tc-copy">
					<?php
					blueworx_public_part(
						'parts/tech-hero.php',
						array(
							'centered'        => false,
							'badge'           => __( 'Selected Work', 'blueworx-site' ),
							'title'           => __( 'Work That Moves the Needle', 'blueworx-site' ),
							'title_highlight' => __( 'the Needle', 'blueworx-site' ),
							'lead'            => __( "Digital solutions we've designed, built, and grown alongside our partners, with the outcomes to show for it.", 'blueworx-site' ),
							'cta'             => array(
								array(
									'label' => __( 'Start a Project', 'blueworx-site' ),
									'href'  => home_url( '/contact' ),
									'class' => 'btn btn-white btn-lg',
								),
								array(
									'label' => __( 'Our Services', 'blueworx-site' ),
									'href'  => home_url( '/services' ),
									'class' => 'btn btn-outline-w btn-lg',
								),
							),
							'meta'            => array(
								__( '82+ projects', 'blueworx-site' ),
								__( '4.9 rating', 'blueworx-site' ),
								__( '99.9% uptime', 'blueworx-site' ),
							),
						)
					);
					?>
				</div>
				<?php
				ob_start();
				?>
				<div class="gc-metric"><small><?php echo esc_html__( 'Hirasté — booking enquiries', 'blueworx-site' ); ?></small><b>+64%</b><span class="up">▲</span></div>
				<div class="gc-metric"><small><?php echo esc_html__( 'QURE — conversion rate', 'blueworx-site' ); ?></small><b>+38%</b><span class="up">▲</span></div>
				<div class="gc-metric" style="border-bottom:none"><small><?php echo esc_html__( 'Reid — organic traffic', 'blueworx-site' ); ?></small><b>3×</b><span class="up">▲</span></div>
				<div class="gc-spark">
					<i style="height:30%"></i><i style="height:44%"></i><i style="height:58%"></i><i style="height:52%"></i><i style="height:70%"></i><i class="hi" style="height:96%"></i><i style="height:78%"></i><i style="height:88%"></i>
				</div>
				<?php
				$blueworx_work_gc_body = ob_get_clean();

				blueworx_public_part(
					'parts/glass-card.php',
					array(
						'tag'    => __( 'results.log', 'blueworx-site' ),
						'body'   => $blueworx_work_gc_body,
						'floats' => array(
							array(
								'icon'  => 'chart',
								'label' => __( 'Avg. lift', 'blueworx-site' ),
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
			<div class="work-grid">
				<?php
				foreach ( $blueworx_work_projects as $blueworx_work_project ) {
					blueworx_public_part(
						'parts/work-card.php',
						array(
							'img_url'   => BLUEWORX_SITE_URL . 'assets/img/' . $blueworx_work_project['image'],
							'alt'       => $blueworx_work_project['alt'],
							'tags'      => $blueworx_work_project['tags'],
							'name'      => $blueworx_work_project['name'],
							'res_value' => $blueworx_work_project['res'],
							'res_text'  => $blueworx_work_project['res_text'],
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
				'title' => __( 'Outcomes, not just outputs.', 'blueworx-site' ),
				'copy'  => __( 'Every engagement is measured against the goals we set together: traffic, conversions, and revenue. Not vanity metrics.', 'blueworx-site' ),
				'stats' => $blueworx_work_stats,
			)
		);

		blueworx_public_part(
			'parts/testimonials.php',
			array(
				'eyebrow'      => __( 'What Our Clients Say', 'blueworx-site' ),
				'title'        => __( "Partners Who'd Recommend Us", 'blueworx-site' ),
				'testimonials' => $blueworx_work_testimonials,
			)
		);
		?>
	</div>
</main>
<?php
blueworx_public_part( 'parts/footer.php' );
blueworx_public_document_close();
