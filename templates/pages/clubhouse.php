<?php
/**
 * ClubHouse page template — the club website platform.
 *
 * Built from the 2026-09 design (docs/superpowers/specs/2026-09-20-site-
 * restructure-design/BlueWorx ClubHouse.dc.html). Content comes from
 * blueworx_content_clubhouse(); the three demo screenshots are bundled
 * captures of demo.305media.co.uk. Per a controller ruling on the design, the
 * second image in the stacked demo pair is the fixtures calendar, not a
 * teams page — there is no bundled teams screenshot.
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

$blueworx_ch      = blueworx_content_clubhouse();
$blueworx_ch_demo = 'https://demo.305media.co.uk/';

// Static, trusted SVGs, ported verbatim from the design. Not routed through
// blueworx_icon(): the arrow is sized directly by .btn svg rules (matching
// $blueworx_home_arrow in home.php/about.php), and the upload/share glyphs on
// the "Need it tailored?" aside are one-off icons not in blueworx_icon_paths().
$blueworx_ch_arrow  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>';
$blueworx_ch_ic_up  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:100%;height:100%"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>';
$blueworx_ch_ic_shr = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:100%;height:100%"><circle cx="13.5" cy="6.5" r="2.5"></circle><circle cx="19" cy="13" r="2.5"></circle><circle cx="6" cy="12" r="2.5"></circle><path d="M12 22a9 9 0 0 1 0-18"></path></svg>';

blueworx_public_document_open( array( 'body_class' => 'bw-clubhouse' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main id="content" tabindex="-1">
	<div>
		<section class="tech-hero bw-hero-tight">
			<div class="tech-inner tech-2col">
				<div class="tc-copy">
					<?php
					blueworx_public_part(
						'parts/tech-hero.php',
						array(
							'centered'        => false,
							'badge'           => __( 'ClubHouse · Club Website Platform', 'bluegroup-project-blueworx' ),
							'title'           => __( 'Every Sport. Every Member. One Club Website.', 'bluegroup-project-blueworx' ),
							'title_highlight' => __( 'One Club Website.', 'bluegroup-project-blueworx' ),
							'lead'            => __( 'ClubHouse is a ready-made website platform for sports clubs and membership organisations. Teams, fixtures, memberships, bookings, events and a club shop, all running on managed hosting from day one.', 'bluegroup-project-blueworx' ),
							'cta'             => array(
								array(
									'label'    => __( 'View Live Demo', 'bluegroup-project-blueworx' ),
									'href'     => $blueworx_ch_demo,
									'class'    => 'btn btn-white btn-lg',
									'external' => true,
								),
								array(
									'label' => __( 'Talk to Us', 'bluegroup-project-blueworx' ),
									'href'  => home_url( '/contact' ),
									'class' => 'btn btn-outline-w btn-lg',
								),
							),
							'meta'            => array(
								__( 'live in 2 weeks', 'bluegroup-project-blueworx' ),
								__( 'hosting included', 'bluegroup-project-blueworx' ),
								__( 'no setup fee', 'bluegroup-project-blueworx' ),
							),
						)
					);
					?>
				</div>
				<?php
				ob_start();
				?>
				<div class="gc-metric"><small><?php esc_html_e( 'Members renewed', 'bluegroup-project-blueworx' ); ?></small><b>412</b><span class="up">▲</span></div>
				<div class="gc-metric"><small><?php esc_html_e( 'Fixtures published', 'bluegroup-project-blueworx' ); ?></small><b>96</b><span class="up">▲</span></div>
				<div class="gc-metric" style="border-bottom:none"><small><?php esc_html_e( 'Subs collected online', 'bluegroup-project-blueworx' ); ?></small><b>94%</b><span class="up">▲</span></div>
				<div class="gc-spark">
					<i style="height:34%"></i><i style="height:48%"></i><i style="height:42%"></i><i style="height:62%"></i><i style="height:74%"></i><i class="hi" style="height:96%"></i><i style="height:82%"></i><i style="height:90%"></i>
				</div>
				<?php
				$blueworx_ch_gc_body = ob_get_clean();

				blueworx_public_part(
					'parts/glass-card.php',
					array(
						'tag'    => __( 'clubhouse · season 26/27', 'bluegroup-project-blueworx' ),
						'body'   => $blueworx_ch_gc_body,
						'style'  => 'padding:28px',
						'floats' => array(
							array(
								'icon'  => 'users',
								'label' => __( 'Active members', 'bluegroup-project-blueworx' ),
								'value' => '1,240',
								'style' => 'top:-22px;right:-26px;animation-delay:.4s',
							),
							array(
								'icon'  => 'calendar',
								'label' => __( 'Next fixture', 'bluegroup-project-blueworx' ),
								'value' => __( 'Sat · 14:00', 'bluegroup-project-blueworx' ),
								'style' => 'bottom:-22px;left:-26px;animation-delay:1.1s',
							),
						),
					)
				);
				?>
			</div>
		</section>

		<section class="sec">
			<div class="center-head" style="margin-bottom:44px">
				<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( "What's Included", 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php esc_html_e( 'Every Part of Club Life, Already Built', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead"><?php esc_html_e( "Nine modules that cover how a club actually runs. Switch on what you need, leave the rest.", 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="bw-g3">
				<?php foreach ( $blueworx_ch['modules'] as $blueworx_ch_module ) : ?>
					<div class="bw-card">
						<div class="svc-ic" style="margin-bottom:18px">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:100%;height:100%">
								<?php foreach ( $blueworx_ch_module['paths'] as $blueworx_ch_path ) : ?>
									<path d="<?php echo esc_attr( $blueworx_ch_path ); ?>"></path>
								<?php endforeach; ?>
							</svg>
						</div>
						<h3><?php echo esc_html( $blueworx_ch_module['name'] ); ?></h3>
						<p><?php echo esc_html( $blueworx_ch_module['desc'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="sec bw-divided">
			<div class="bw-split-head">
				<div>
					<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( 'See It Live', 'bluegroup-project-blueworx' ); ?></div>
					<h2 class="h2"><?php esc_html_e( 'A Full Club Site, Running Today', 'bluegroup-project-blueworx' ); ?></h2>
				</div>
				<a href="<?php echo esc_url( $blueworx_ch_demo ); ?>" target="_blank" rel="noopener" class="btn btn-outline btn-md">
					<?php esc_html_e( 'Open the Demo', 'bluegroup-project-blueworx' ); ?>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_ch_arrow above.
					echo $blueworx_ch_arrow;
					?>
				</a>
			</div>
			<div class="bw-demo">
				<div class="bw-demo-shot">
					<?php blueworx_public_image( 'img/clubhouse-demo-home.jpg', __( 'The ClubHouse demo home page', 'bluegroup-project-blueworx' ) ); ?>
				</div>
				<div class="bw-demo-stack">
					<div class="bw-demo-shot">
						<?php blueworx_public_image( 'img/clubhouse-demo-calendar.jpg', __( 'The ClubHouse demo fixtures calendar', 'bluegroup-project-blueworx' ) ); ?>
					</div>
					<div class="bw-demo-shot">
						<?php blueworx_public_image( 'img/clubhouse-demo-membership.jpg', __( 'The ClubHouse demo membership page', 'bluegroup-project-blueworx' ) ); ?>
					</div>
				</div>
			</div>
		</section>

		<section class="features-dark">
			<?php blueworx_blob( 'width:360px;height:360px;top:-120px;right:-120px;opacity:.14' ); ?>
			<div class="fd-header">
				<h2 class="h2"><?php esc_html_e( 'Members Do It Themselves. Committees Get Their Evenings Back.', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="fd-sub"><?php esc_html_e( 'Joining, renewing, paying subs, booking a court, buying kit — all self-service, all online, all reconciled in one place.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="bw-g4">
				<?php foreach ( $blueworx_ch['self_serve'] as $blueworx_ch_item ) : ?>
					<div class="bw-card-dark">
						<div class="bw-tag"><?php echo esc_html( $blueworx_ch_item['tag'] ); ?></div>
						<h3><?php echo esc_html( $blueworx_ch_item['name'] ); ?></h3>
						<p><?php echo esc_html( $blueworx_ch_item['desc'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="sec">
			<div class="center-head" style="margin-bottom:36px">
				<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( 'Pricing', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php esc_html_e( 'One Price. The Whole Platform.', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead"><?php esc_html_e( 'Hosting, updates, and every ClubHouse module included. Cancel any time.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div style="display:flex;justify-content:center;margin-bottom:34px">
				<div class="bill-toggle" data-widget="billing-toggle">
					<button type="button" class="on"><?php esc_html_e( 'Monthly billing', 'bluegroup-project-blueworx' ); ?></button>
					<button type="button"><?php esc_html_e( 'Annual billing', 'bluegroup-project-blueworx' ); ?></button>
				</div>
			</div>
			<div class="bw-g2 bw-plan-grid">
				<?php blueworx_public_part( 'parts/plan-card.php', array( 'plan' => $blueworx_ch['plan'] ) ); ?>
				<div class="bw-plan-aside">
					<h3><?php esc_html_e( 'Need it tailored?', 'bluegroup-project-blueworx' ); ?></h3>
					<p><?php esc_html_e( 'Bigger clubs run bigger operations. We migrate your member data, brand the site to your colours, and bolt on custom modules — quoted as a one-off project on top of the monthly platform fee.', 'bluegroup-project-blueworx' ); ?></p>
					<div class="collab-list">
						<div class="fli">
							<div class="fli-icon">
								<?php
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_ch_ic_up above.
								echo $blueworx_ch_ic_up;
								?>
							</div>
							<span><?php esc_html_e( 'Member data migration', 'bluegroup-project-blueworx' ); ?></span>
						</div>
						<div class="fli">
							<div class="fli-icon">
								<?php
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_ch_ic_shr above.
								echo $blueworx_ch_ic_shr;
								?>
							</div>
							<span><?php esc_html_e( 'Club branding & kit colours', 'bluegroup-project-blueworx' ); ?></span>
						</div>
						<div class="fli">
							<div class="fli-icon"><?php blueworx_icon( 'code' ); ?></div>
							<span><?php esc_html_e( 'Custom modules & integrations', 'bluegroup-project-blueworx' ); ?></span>
						</div>
					</div>
					<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn btn-outline btn-md">
						<?php esc_html_e( 'Get a Quote', 'bluegroup-project-blueworx' ); ?>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_ch_arrow above.
						echo $blueworx_ch_arrow;
						?>
					</a>
				</div>
			</div>
		</section>

		<section class="sec bw-divided">
			<div class="center-head" style="margin-bottom:40px">
				<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( "Who It's For", 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php esc_html_e( 'Built for Clubs. Works for Any Membership Organisation.', 'bluegroup-project-blueworx' ); ?></h2>
			</div>
			<div class="bw-g4">
				<?php foreach ( $blueworx_ch['audiences'] as $blueworx_ch_aud ) : ?>
					<div class="bw-card">
						<h3 style="font-size:18px"><?php echo esc_html( $blueworx_ch_aud['name'] ); ?></h3>
						<p style="color:#667085"><?php echo esc_html( $blueworx_ch_aud['desc'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<?php
		blueworx_public_part(
			'parts/faq-section.php',
			array(
				'class' => 'bw-divided',
				'lead'  => __( 'Everything you need to know about running your club on ClubHouse.', 'bluegroup-project-blueworx' ),
				'faqs'  => $blueworx_ch['faqs'],
			)
		);
		?>
	</div>
</main>
<?php
blueworx_public_part(
	'parts/footer.php',
	array(
		'cta_title'     => __( 'Ready to Move Your Club Online?', 'bluegroup-project-blueworx' ),
		'cta_copy'      => __( "Tour the demo, then tell us about your club. We'll have you live inside a fortnight.", 'bluegroup-project-blueworx' ),
		'cta_primary'   => array(
			'label'    => __( 'View Live Demo', 'bluegroup-project-blueworx' ),
			'href'     => $blueworx_ch_demo,
			'external' => true,
		),
		'cta_secondary' => array(
			'label' => __( 'Talk to Us', 'bluegroup-project-blueworx' ),
			'href'  => home_url( '/contact' ),
		),
	)
);
blueworx_public_document_close();
