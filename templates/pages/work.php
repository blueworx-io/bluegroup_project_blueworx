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
		'alt'      => __( 'Hirasté website', 'bluegroup-project-blueworx' ),
		'tags'     => array( __( 'Web Design', 'bluegroup-project-blueworx' ), __( 'Booking Platform', 'bluegroup-project-blueworx' ) ),
		'name'     => 'Hirasté',
		'res'      => '+64%',
		'res_text' => __( 'group booking enquiries', 'bluegroup-project-blueworx' ),
	),
	array(
		'image'    => 'feature-image-3.jpg',
		'alt'      => __( 'Padel365 website', 'bluegroup-project-blueworx' ),
		'tags'     => array( __( 'E-commerce', 'bluegroup-project-blueworx' ), __( 'Court Booking', 'bluegroup-project-blueworx' ) ),
		'name'     => 'Padel365',
		'res'      => __( 'Sold-out', 'bluegroup-project-blueworx' ),
		'res_text' => __( 'launch season', 'bluegroup-project-blueworx' ),
	),
	array(
		'image'    => 'feature-image-4.jpg',
		'alt'      => __( 'QURE website', 'bluegroup-project-blueworx' ),
		'tags'     => array( __( 'Brand', 'bluegroup-project-blueworx' ), __( 'Web Build', 'bluegroup-project-blueworx' ) ),
		'name'     => 'QURE',
		'res'      => '+38%',
		'res_text' => __( 'conversion rate', 'bluegroup-project-blueworx' ),
	),
	array(
		'image'    => 'feature-image-2.jpg',
		'alt'      => __( 'Bloom & Co. website', 'bluegroup-project-blueworx' ),
		'tags'     => array( __( 'Migration', 'bluegroup-project-blueworx' ), __( 'Managed Hosting', 'bluegroup-project-blueworx' ) ),
		'name'     => 'Bloom & Co.',
		'res'      => __( 'Zero-downtime', 'bluegroup-project-blueworx' ),
		'res_text' => __( 'platform migration', 'bluegroup-project-blueworx' ),
	),
	array(
		'image'    => 'fig-collab.jpg',
		'alt'      => __( 'chromaesthesia website', 'bluegroup-project-blueworx' ),
		'tags'     => array( __( 'Web Design', 'bluegroup-project-blueworx' ), __( 'CMS', 'bluegroup-project-blueworx' ) ),
		'name'     => 'chromaesthesia',
		'res'      => __( '2× faster', 'bluegroup-project-blueworx' ),
		'res_text' => __( 'publishing workflow', 'bluegroup-project-blueworx' ),
	),
	array(
		'image'    => 'hero-image.png',
		'alt'      => __( 'Reid Consulting website', 'bluegroup-project-blueworx' ),
		'tags'     => array( __( 'SEO', 'bluegroup-project-blueworx' ), __( 'Growth Retainer', 'bluegroup-project-blueworx' ) ),
		'name'     => 'Reid Consulting',
		'res'      => '3×',
		'res_text' => __( 'organic traffic in 12 months', 'bluegroup-project-blueworx' ),
	),
);

$blueworx_work_stats = array(
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
		'value' => '2K +',
		'label' => __( 'Toolbox Value', 'bluegroup-project-blueworx' ),
	),
);

// Work's own testimonials, distinct from the shared homepage reviews.
$blueworx_work_testimonials = array(
	array(
		'text'     => __( '"BlueWorx has completely transformed how we manage our website. The tools are powerful and the support team is incredibly responsive."', 'bluegroup-project-blueworx' ),
		'initials' => 'SJ',
		'name'     => 'Sarah Johnson',
		'role'     => __( 'Owner, Fresh Bakery Co.', 'bluegroup-project-blueworx' ),
	),
	array(
		'text'     => __( '"The live chat and booking system have increased our conversion rate significantly. Worth every penny — and then some."', 'bluegroup-project-blueworx' ),
		'initials' => 'MR',
		'name'     => 'Marcus Reid',
		'role'     => __( 'Director, Reid Consulting', 'bluegroup-project-blueworx' ),
	),
	array(
		'text'     => __( '"Finally, one platform that does it all. We cancelled three separate subscriptions when we switched to BlueWorx."', 'bluegroup-project-blueworx' ),
		'initials' => 'AL',
		'name'     => 'Amy Leung',
		'role'     => __( 'Founder, Leung Law Group', 'bluegroup-project-blueworx' ),
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
							'badge'           => __( 'Selected Work', 'bluegroup-project-blueworx' ),
							'title'           => __( 'Work That Moves the Needle', 'bluegroup-project-blueworx' ),
							'title_highlight' => __( 'the Needle', 'bluegroup-project-blueworx' ),
							'lead'            => __( "Digital solutions we've designed, built, and grown alongside our partners, with the outcomes to show for it.", 'bluegroup-project-blueworx' ),
							'cta'             => array(
								array(
									'label' => __( 'Start a Project', 'bluegroup-project-blueworx' ),
									'href'  => home_url( '/contact' ),
									'class' => 'btn btn-white btn-lg',
								),
								array(
									'label' => __( 'Our Services', 'bluegroup-project-blueworx' ),
									'href'  => home_url( '/services' ),
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
				<div class="gc-metric"><small><?php echo esc_html__( 'QURE — conversion rate', 'bluegroup-project-blueworx' ); ?></small><b>+38%</b><span class="up">▲</span></div>
				<div class="gc-metric" style="border-bottom:none"><small><?php echo esc_html__( 'Reid — organic traffic', 'bluegroup-project-blueworx' ); ?></small><b>3×</b><span class="up">▲</span></div>
				<div class="gc-spark">
					<i style="height:30%"></i><i style="height:44%"></i><i style="height:58%"></i><i style="height:52%"></i><i style="height:70%"></i><i class="hi" style="height:96%"></i><i style="height:78%"></i><i style="height:88%"></i>
				</div>
				<?php
				$blueworx_work_gc_body = ob_get_clean();

				blueworx_public_part(
					'parts/glass-card.php',
					array(
						'tag'    => __( 'results.log', 'bluegroup-project-blueworx' ),
						'body'   => $blueworx_work_gc_body,
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
			<?php // The design gives this section no visible heading, so its project cards would follow the page h1 as h3s with nothing in between — a screen reader announces a level as missing. Named here for assistive tech only; sighted layout is unchanged. ?>
			<h2 class="bw-sr-only"><?php esc_html_e( 'Selected work', 'bluegroup-project-blueworx' ); ?></h2>
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
				'title' => __( 'Outcomes, not just outputs.', 'bluegroup-project-blueworx' ),
				'copy'  => __( 'Every engagement is measured against the goals we set together: traffic, conversions, and revenue. Not vanity metrics.', 'bluegroup-project-blueworx' ),
				'stats' => $blueworx_work_stats,
			)
		);

		blueworx_public_part(
			'parts/testimonials.php',
			array(
				'eyebrow'      => __( 'What Our Clients Say', 'bluegroup-project-blueworx' ),
				'title'        => __( "Partners Who'd Recommend Us", 'bluegroup-project-blueworx' ),
				'testimonials' => $blueworx_work_testimonials,
			)
		);
		?>
	</div>
</main>
<?php
blueworx_public_part( 'parts/footer.php' );
blueworx_public_document_close();
