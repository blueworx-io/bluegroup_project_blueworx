<?php
/**
 * Home page template.
 *
 * Ported from app/page.tsx's nine sections, in source order: home-hero (a
 * timeline glass-card visual + the scrolling service ticker, both bespoke to
 * this page, so they stay inline rather than becoming shared parts), "What
 * We Do" (`.svc2`, two svc-card parts), LogosBand, Selected Work (three
 * work-card parts), FeatureTabs (a Plan 3 interactive widget — renders a
 * labelled static placeholder here, see the note at that section below), How
 * We Work (a proc-grid part), Ongoing Partnership (`.split`, bespoke — the
 * source never reuses this collab-list/collab-visual layout elsewhere),
 * ToolboxGrid (inline: not one of the parts this task builds, and its only
 * other consumer, the Toolbox archive page, is Task 9, not this one) and
 * Testimonials (a testimonials part fed the real review content).
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

// A static, trusted SVG reused several times below (button arrows, the
// "Explore …" service-card links). Not one of blueworx_icon_paths()'s named
// icons: those are always wrapped in a `span[data-ic]` sized to 100%/100%,
// but this arrow is sized directly by `.btn svg`/`.svc-link svg` rules in
// public.css, so going through blueworx_icon() would break its sizing.
// Ported verbatim from the ARROW constant in app/page.tsx.
$blueworx_home_arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>';

// The five-step delivery timeline shown inside the hero's glass card. Kept
// local to this page (not a shared part's $vars) because no other source
// page reuses this exact shape — Services/Work's own glass cards render
// `.gc-metric` rows instead, which is why glass-card.php's contract is a
// generic pre-built `body` slot rather than trying to model this timeline.
// Ported verbatim from TimelineRow's per-state styling in app/page.tsx.
$blueworx_home_timeline = array(
	array(
		'state'  => 'done',
		'title'  => __( 'Discovery call', 'bluegroup-project-blueworx' ),
		'desc'   => __( 'Goals, scope & strategy agreed', 'bluegroup-project-blueworx' ),
		'status' => __( 'Done', 'bluegroup-project-blueworx' ),
	),
	array(
		'state'  => 'done',
		'title'  => __( 'Design', 'bluegroup-project-blueworx' ),
		'desc'   => __( 'On-brand, conversion-first layouts', 'bluegroup-project-blueworx' ),
		'status' => __( 'Done', 'bluegroup-project-blueworx' ),
	),
	array(
		'state'  => 'current',
		'icon'   => 'code',
		'title'  => __( 'Development', 'bluegroup-project-blueworx' ),
		'desc'   => __( 'Fast, responsive build in progress', 'bluegroup-project-blueworx' ),
		'status' => __( 'In progress', 'bluegroup-project-blueworx' ),
	),
	array(
		'state'  => 'todo',
		'icon'   => 'server',
		'title'  => __( 'Deploy', 'bluegroup-project-blueworx' ),
		'desc'   => __( 'Launch on managed hosting', 'bluegroup-project-blueworx' ),
		'status' => __( 'Queued', 'bluegroup-project-blueworx' ),
	),
	array(
		'state'  => 'todo',
		'icon'   => 'chat',
		'title'  => __( 'Support & growth', 'bluegroup-project-blueworx' ),
		'desc'   => __( 'Updates, SEO & a team on call', 'bluegroup-project-blueworx' ),
		'status' => __( 'Always on', 'bluegroup-project-blueworx' ),
	),
);

$blueworx_home_timeline_circle = array(
	'done'    => array(
		'background' => 'linear-gradient(rgba(1,208,132,.13),rgba(1,208,132,.13)),#10122E',
		'border'     => '1px solid rgba(1,208,132,.4)',
	),
	'current' => array(
		'background' => 'linear-gradient(rgba(139,142,255,.16),rgba(139,142,255,.16)),#10122E',
		'border'     => '1px solid rgba(154,155,255,.55)',
		'box-shadow' => '0 0 16px rgba(122,124,255,.35)',
	),
	'todo'    => array(
		'background' => 'linear-gradient(rgba(255,255,255,.05),rgba(255,255,255,.05)),#10122E',
		'border'     => '1px solid rgba(139,142,255,.25)',
	),
);

$blueworx_home_status_colors = array(
	'done'    => '#01D084',
	'current' => '#B9BAFF',
	'todo'    => 'rgba(226,228,255,.4)',
);

ob_start();
?>
<div style="position:relative;display:flex;flex-direction:column">
	<div style="position:absolute;left:17.5px;top:29px;bottom:29px;width:1px;margin-left:-0.5px;background:linear-gradient(180deg,rgba(1,208,132,.45) 0%,rgba(1,208,132,.45) 30%,rgba(139,142,255,.22) 50%,rgba(139,142,255,.22) 100%)"></div>
	<?php foreach ( $blueworx_home_timeline as $blueworx_home_step ) : ?>
		<?php
		$blueworx_home_circle       = $blueworx_home_timeline_circle[ $blueworx_home_step['state'] ];
		$blueworx_home_circle_style = 'background:' . $blueworx_home_circle['background'] . ';border:' . $blueworx_home_circle['border'] . ';' . ( isset( $blueworx_home_circle['box-shadow'] ) ? 'box-shadow:' . $blueworx_home_circle['box-shadow'] . ';' : '' );
		$blueworx_home_status_color = $blueworx_home_status_colors[ $blueworx_home_step['state'] ];
		$blueworx_home_title_color  = 'todo' === $blueworx_home_step['state'] ? 'rgba(255,255,255,.75)' : '#fff';
		$blueworx_home_desc_color   = 'todo' === $blueworx_home_step['state'] ? 'rgba(226,228,255,.45)' : 'rgba(226,228,255,.5)';
		$blueworx_home_icon_color   = 'current' === $blueworx_home_step['state'] ? '#B9BAFF' : 'rgba(226,228,255,.45)';
		?>
		<div style="position:relative;display:flex;align-items:center;gap:15px;padding:11px 0">
			<div style="width:35px;height:35px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;<?php echo esc_attr( $blueworx_home_circle_style ); ?>">
				<?php if ( 'done' === $blueworx_home_step['state'] ) : ?>
					<svg viewBox="0 0 24 24" fill="none" stroke="#01D084" stroke-width="2.5" style="width:14px;height:14px"><polyline points="20 6 9 17 4 12" /></svg>
				<?php elseif ( ! empty( $blueworx_home_step['icon'] ) ) : ?>
					<span style="width:15px;height:15px;color:<?php echo esc_attr( $blueworx_home_icon_color ); ?>"><?php blueworx_icon( $blueworx_home_step['icon'] ); ?></span>
				<?php endif; ?>
			</div>
			<div style="flex:1">
				<div style="font-size:15px;font-weight:600;color:<?php echo esc_attr( $blueworx_home_title_color ); ?>"><?php echo esc_html( $blueworx_home_step['title'] ); ?></div>
				<div style="font-size:12.5px;color:<?php echo esc_attr( $blueworx_home_desc_color ); ?>;margin-top:1px"><?php echo esc_html( $blueworx_home_step['desc'] ); ?></div>
			</div>
			<span style="font-family:'SF Mono',ui-monospace,Menlo,monospace;font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:<?php echo esc_attr( $blueworx_home_status_color ); ?>"><?php echo esc_html( $blueworx_home_step['status'] ); ?></span>
		</div>
	<?php endforeach; ?>
</div>
<?php
$blueworx_home_timeline_body = ob_get_clean();

$blueworx_home_ticker = array(
	__( 'strategy & UX', 'bluegroup-project-blueworx' ),
	__( 'web & platform design', 'bluegroup-project-blueworx' ),
	__( 'e-commerce builds', 'bluegroup-project-blueworx' ),
	__( 'SEO & growth', 'bluegroup-project-blueworx' ),
	__( 'managed hosting', 'bluegroup-project-blueworx' ),
	__( 'brand & identity', 'bluegroup-project-blueworx' ),
	__( 'automation', 'bluegroup-project-blueworx' ),
	__( 'ongoing support', 'bluegroup-project-blueworx' ),
);
$blueworx_home_ticker = array_merge( $blueworx_home_ticker, $blueworx_home_ticker );

$blueworx_home_collab_items = array(
	array(
		'icon'  => 'server',
		'label' => __( 'Maintain', 'bluegroup-project-blueworx' ),
	),
	array(
		'icon'  => 'plug',
		'label' => __( 'Integrate', 'bluegroup-project-blueworx' ),
	),
	array(
		'icon'  => 'chart',
		'label' => __( 'Improve', 'bluegroup-project-blueworx' ),
	),
	array(
		'icon'  => 'clock',
		'label' => __( 'Optimise', 'bluegroup-project-blueworx' ),
	),
);

blueworx_public_document_open( array( 'body_class' => 'bw-home' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main id="content" tabindex="-1">
	<div>
		<section class="home-hero">
			<div class="hh-inner">
				<div class="hh-copy">
					<div class="tech-badge"><span class="dot"></span><?php echo esc_html__( 'Digital Agency & Platform', 'bluegroup-project-blueworx' ); ?></div>
					<h1 class="h1">
						<?php echo esc_html__( 'We Design, Build & Grow', 'bluegroup-project-blueworx' ); ?>
						<span class="tech-grad"><?php echo esc_html__( 'Digital Solutions', 'bluegroup-project-blueworx' ); ?></span>
						<?php echo esc_html__( 'That Win Business', 'bluegroup-project-blueworx' ); ?>
					</h1>
					<p class="lead"><?php echo esc_html__( 'BlueWorx is the agency behind high-performing digital solutions: websites, platforms, and automations. Strategy, design, build, hosting, and ongoing support from one dedicated team.', 'bluegroup-project-blueworx' ); ?></p>
					<div class="hh-cta">
						<a href="<?php echo esc_url( home_url( '/pricing' ) ); ?>" class="btn btn-white btn-lg"><?php echo esc_html__( 'Get a Quote', 'bluegroup-project-blueworx' ); ?></a>
						<a href="<?php echo esc_url( home_url( '/work' ) ); ?>" class="btn btn-outline-w btn-lg">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_home_arrow above.
							echo $blueworx_home_arrow;
							?>
							<?php echo esc_html__( 'View Our Work', 'bluegroup-project-blueworx' ); ?>
						</a>
					</div>
					<div class="hh-stats">
						<div><b><?php echo esc_html__( '82+', 'bluegroup-project-blueworx' ); ?></b><span><?php echo esc_html__( 'Projects delivered', 'bluegroup-project-blueworx' ); ?></span></div>
						<div>
							<b>
								4.9
								<svg width="20" height="20" viewBox="0 0 24 24" fill="#FFB300"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.5 1.6 6.7L12 17l-6.2 3.6 1.6-6.7L2.2 8.9l6.9-.6z" /></svg>
							</b>
							<span><?php echo esc_html__( 'Google rating', 'bluegroup-project-blueworx' ); ?></span>
						</div>
						<div><b><?php echo esc_html__( '99.9%', 'bluegroup-project-blueworx' ); ?></b><span><?php echo esc_html__( 'Uptime maintained', 'bluegroup-project-blueworx' ); ?></span></div>
					</div>
				</div>
				<div class="hh-visual">
					<div class="hh-ring"></div>
					<?php
					blueworx_public_part(
						'parts/glass-card.php',
						array(
							'tag'          => __( 'yourproject · status', 'bluegroup-project-blueworx' ),
							'status_label' => __( 'On track', 'bluegroup-project-blueworx' ),
							'style'        => 'padding:28px',
							'body'         => $blueworx_home_timeline_body,
							'floats'       => array(
								array(
									'icon'  => 'clock',
									'label' => __( 'Avg. launch', 'bluegroup-project-blueworx' ),
									'value' => __( '3–6 weeks', 'bluegroup-project-blueworx' ),
									'style' => 'top:-22px;right:-26px;animation-delay:.4s',
								),
								array(
									'icon'  => 'server',
									'label' => __( 'Uptime', 'bluegroup-project-blueworx' ),
									'value' => __( 'Live · 99.9%', 'bluegroup-project-blueworx' ),
									'style' => 'bottom:-22px;left:-26px;animation-delay:1.1s',
								),
							),
						)
					);
					?>
				</div>
			</div>
			<div class="hh-ticker">
				<div class="hh-ticker-mask">
					<div class="hh-ticker-track">
						<?php foreach ( $blueworx_home_ticker as $blueworx_home_ticker_item ) : ?>
							<span><?php echo esc_html( $blueworx_home_ticker_item ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>

		<section class="sec" style="padding-bottom:0">
			<div class="center-head" style="margin-bottom:40px">
				<div class="eyebrow" style="margin-bottom:20px"><?php echo esc_html__( 'What We Do', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php echo esc_html__( 'Two Services. Everything Your Business Needs Online.', 'bluegroup-project-blueworx' ); ?></h2>
			</div>
			<div class="svc2">
				<?php
				blueworx_public_part(
					'parts/svc-card.php',
					array(
						'icon'      => 'users',
						'eyebrow'   => __( 'Service 01', 'bluegroup-project-blueworx' ),
						'title'     => __( 'Integrated Support', 'bluegroup-project-blueworx' ),
						'desc'      => __( 'A full design and development team, integrated into your business. We scope, design, and build your digital solution, then stay on as your support and growth partner.', 'bluegroup-project-blueworx' ),
						'chips'     => array(
							__( 'Design', 'bluegroup-project-blueworx' ),
							__( 'Development', 'bluegroup-project-blueworx' ),
							__( 'Support', 'bluegroup-project-blueworx' ),
							__( 'Reporting', 'bluegroup-project-blueworx' ),
						),
						'link_text' => __( 'Explore Integrated Support', 'bluegroup-project-blueworx' ),
						'href'      => home_url( '/services' ),
					)
				);
				blueworx_public_part(
					'parts/svc-card.php',
					array(
						'icon'      => 'plug',
						'eyebrow'   => __( 'Service 02', 'bluegroup-project-blueworx' ),
						'title'     => __( 'Digital Toolbox', 'bluegroup-project-blueworx' ),
						'desc'      => __( 'Every premium tool your business needs, from forms and SEO to e-commerce and automation, in one subscription with hosting included. No individual licences to manage.', 'bluegroup-project-blueworx' ),
						'chips'     => array(
							__( '12+ premium tools', 'bluegroup-project-blueworx' ),
							__( 'Hosting included', 'bluegroup-project-blueworx' ),
							__( 'Learning Center', 'bluegroup-project-blueworx' ),
						),
						'link_text' => __( 'Explore the Toolbox', 'bluegroup-project-blueworx' ),
						'href'      => home_url( '/toolbox' ),
					)
				);
				?>
			</div>
		</section>

		<?php blueworx_public_part( 'parts/logos-band.php' ); ?>

		<section class="sec" style="padding-top:0">
			<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:24px;flex-wrap:wrap;margin-bottom:40px">
				<div>
					<div class="eyebrow" style="margin-bottom:20px"><?php echo esc_html__( 'Selected Work', 'bluegroup-project-blueworx' ); ?></div>
					<h2 class="h2" style="max-width:560px"><?php echo esc_html__( 'Recent Projects, Real Results', 'bluegroup-project-blueworx' ); ?></h2>
				</div>
				<a href="<?php echo esc_url( home_url( '/work' ) ); ?>" class="btn btn-outline btn-md">
					<?php echo esc_html__( 'View All Work', 'bluegroup-project-blueworx' ); ?>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_home_arrow above.
					echo $blueworx_home_arrow;
					?>
				</a>
			</div>
			<div class="work-grid">
				<?php
				blueworx_public_part(
					'parts/work-card.php',
					array(
						'img_url'   => BLUEWORX_SITE_URL . 'assets/img/feature-image-1.jpg',
						'alt'       => __( 'Hirasté website', 'bluegroup-project-blueworx' ),
						'tags'      => array( __( 'Web Design', 'bluegroup-project-blueworx' ), __( 'Booking Platform', 'bluegroup-project-blueworx' ) ),
						'name'      => 'Hirasté',
						'res_value' => __( '+64%', 'bluegroup-project-blueworx' ),
						'res_text'  => __( 'group booking enquiries', 'bluegroup-project-blueworx' ),
						'href'      => home_url( '/work' ),
					)
				);
				blueworx_public_part(
					'parts/work-card.php',
					array(
						'img_url'   => BLUEWORX_SITE_URL . 'assets/img/feature-image-3.jpg',
						'alt'       => __( 'Padel365 website', 'bluegroup-project-blueworx' ),
						'tags'      => array( __( 'E-commerce', 'bluegroup-project-blueworx' ), __( 'Court Booking', 'bluegroup-project-blueworx' ) ),
						'name'      => 'Padel365',
						'res_value' => __( 'Sold-out', 'bluegroup-project-blueworx' ),
						'res_text'  => __( 'launch season', 'bluegroup-project-blueworx' ),
						'href'      => home_url( '/work' ),
					)
				);
				blueworx_public_part(
					'parts/work-card.php',
					array(
						'img_url'   => BLUEWORX_SITE_URL . 'assets/img/feature-image-4.jpg',
						'alt'       => __( 'QURE website', 'bluegroup-project-blueworx' ),
						'tags'      => array( __( 'Brand', 'bluegroup-project-blueworx' ), __( 'Web Build', 'bluegroup-project-blueworx' ) ),
						'name'      => 'QURE',
						'res_value' => __( '+38%', 'bluegroup-project-blueworx' ),
						'res_text'  => __( 'conversion rate', 'bluegroup-project-blueworx' ),
						'href'      => home_url( '/work' ),
					)
				);
				?>
			</div>
		</section>

		<?php
		/*
		 * FeatureTabs (Plan 3b): a client-side tabbed analytics showcase.
		 * PHP renders the full Support-tab default (markup, chart path,
		 * legend); assets/js/public-widgets.js's initFeatureTabs() reads
		 * each tab button's data-* attributes to recompute the SVG line
		 * chart and swap the heading/desc/CTA/legend on click. Tab data
		 * ported verbatim from FeatureTabs.tsx's AF/LEGEND arrays.
		 */
		$blueworx_home_feature_tabs = array(
			array(
				'label'   => __( 'Support', 'bluegroup-project-blueworx' ),
				'heading' => __( 'Support Guides', 'bluegroup-project-blueworx' ),
				'desc'    => __( 'Get ahead by accessing our dedicated support guides, designed to give you an edge.', 'bluegroup-project-blueworx' ),
				'cta'     => __( 'View Guides', 'bluegroup-project-blueworx' ),
				'color'   => '#4F46E5',
				'pts'     => '150 118 138 82 110 64 96 74 88',
				'value'   => '120,456',
			),
			array(
				'label'   => __( 'Toolbox', 'bluegroup-project-blueworx' ),
				'heading' => __( 'Digital Toolbox', 'bluegroup-project-blueworx' ),
				'desc'    => __( 'Access a curated set of tools that power your website, automations, and integrations, all set up, managed, and maintained for you.', 'bluegroup-project-blueworx' ),
				'cta'     => __( 'View Toolbox', 'bluegroup-project-blueworx' ),
				'color'   => '#A5A7FF',
				'pts'     => '120 96 112 60 84 46 72 54 62',
				'value'   => '245,877',
			),
			array(
				'label'   => __( 'Hosting', 'bluegroup-project-blueworx' ),
				'heading' => __( 'Website Hosting', 'bluegroup-project-blueworx' ),
				'desc'    => __( 'Remove the headache of WordPress hosting with our high-performance hosting supported by integrated growth & security functionality.', 'bluegroup-project-blueworx' ),
				'cta'     => __( 'View Hosting', 'bluegroup-project-blueworx' ),
				'color'   => '#3686F7',
				'pts'     => '168 150 158 128 146 120 136 126 142',
				'value'   => '78,987',
			),
		);
		?>
		<section class="features-dark" data-widget="feature-tabs">
			<div class="blob" style="width:360px;height:360px;top:-120px;right:-120px;opacity:.14"></div>
			<div class="fd-header">
				<h2 class="h2"><?php echo esc_html__( 'One Platform. Every Tool. Real Results.', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="fd-sub"><?php echo esc_html__( 'Every BlueWorx build ships on a managed platform with tools, hosting, and support included, so your site keeps performing long after launch.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="tab-bar">
				<?php foreach ( $blueworx_home_feature_tabs as $blueworx_home_ft_i => $blueworx_home_ft_tab ) : ?>
					<button
						type="button"
						class="tab <?php echo 0 === $blueworx_home_ft_i ? 'on' : 'off'; ?>"
						data-tab="<?php echo esc_attr( $blueworx_home_ft_i ); ?>"
						data-heading="<?php echo esc_attr( $blueworx_home_ft_tab['heading'] ); ?>"
						data-desc="<?php echo esc_attr( $blueworx_home_ft_tab['desc'] ); ?>"
						data-cta="<?php echo esc_attr( $blueworx_home_ft_tab['cta'] ); ?>"
						data-color="<?php echo esc_attr( $blueworx_home_ft_tab['color'] ); ?>"
						data-value="<?php echo esc_attr( $blueworx_home_ft_tab['value'] ); ?>"
						data-pts="<?php echo esc_attr( $blueworx_home_ft_tab['pts'] ); ?>"
					><?php echo esc_html( $blueworx_home_ft_tab['label'] ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="af-wrap">
				<div class="af-panel">
					<div class="af-panel-head">
						<h3><?php echo esc_html__( 'Quick Analytics', 'bluegroup-project-blueworx' ); ?></h3>
						<span class="af-range">
							<?php echo esc_html__( 'All time', 'bluegroup-project-blueworx' ); ?>
							<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
						</span>
					</div>
					<div class="af-legend">
						<?php foreach ( $blueworx_home_feature_tabs as $blueworx_home_ft_i => $blueworx_home_ft_tab ) : ?>
							<div class="af-leg <?php echo 0 === $blueworx_home_ft_i ? 'on' : 'off'; ?>">
								<small><i style="background:<?php echo esc_attr( $blueworx_home_ft_tab['color'] ); ?>"></i><?php echo esc_html( $blueworx_home_ft_tab['label'] ); ?></small>
								<b><?php echo esc_html( $blueworx_home_ft_tab['value'] ); ?></b>
							</div>
						<?php endforeach; ?>
					</div>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup: the Support-tab default chart, built from the $blueworx_home_feature_tabs constants above.
					echo '<svg class="af-chart" viewBox="0 0 520 210" preserveAspectRatio="none">
						<defs>
							<linearGradient id="afGrad" x1="0" y1="0" x2="0" y2="1">
								<stop offset="0%" stop-color="#4F46E5" stop-opacity="0.16" />
								<stop offset="100%" stop-color="#4F46E5" stop-opacity="0" />
							</linearGradient>
						</defs>
						<line x1="0" y1="52" x2="520" y2="52" stroke="#EFEFF0" stroke-width="1" />
						<line x1="0" y1="104" x2="520" y2="104" stroke="#EFEFF0" stroke-width="1" />
						<line x1="0" y1="156" x2="520" y2="156" stroke="#EFEFF0" stroke-width="1" />
						<path class="af-area" d="M0,150 L65,118 L130,138 L195,82 L260,110 L325,64 L390,96 L455,74 L520,88 L520,210 L0,210 Z" fill="url(#afGrad)" />
						<path class="af-line" d="M0,150 L65,118 L130,138 L195,82 L260,110 L325,64 L390,96 L455,74 L520,88" fill="none" stroke="#4F46E5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
						<circle class="af-dot" cx="325" cy="64" r="5" fill="#fff" stroke="#4F46E5" stroke-width="3" />
					</svg>';
					?>
				</div>
				<div class="af-text">
					<h2 class="h2" style="font-size:34px;margin-bottom:14px;color:#fff"><?php echo esc_html( $blueworx_home_feature_tabs[0]['heading'] ); ?></h2>
					<p class="lead" style="font-size:17px;margin-bottom:28px;color:rgba(255,255,255,.66)"><?php echo esc_html( $blueworx_home_feature_tabs[0]['desc'] ); ?></p>
					<a class="btn btn-brand btn-md" href="<?php echo esc_url( home_url( '/toolbox' ) ); ?>"><?php echo esc_html( $blueworx_home_feature_tabs[0]['cta'] ); ?>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_home_arrow above.
						echo $blueworx_home_arrow;
						?>
					</a>
				</div>
			</div>
		</section>

		<section class="sec" style="padding-bottom:80px">
			<div class="center-head" style="margin-bottom:40px">
				<div class="eyebrow" style="margin-bottom:20px"><?php echo esc_html__( 'How We Work', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php echo esc_html__( 'From First Conversation to Long-Term Partner', 'bluegroup-project-blueworx' ); ?></h2>
			</div>
			<?php
			blueworx_public_part(
				'parts/proc-grid.php',
				array(
					'items' => array(
						array(
							'num'   => '01',
							'title' => __( 'Talk It Through', 'bluegroup-project-blueworx' ),
							'desc'  => __( 'Bring us the problem. We talk it through together and identify exactly what your business needs.', 'bluegroup-project-blueworx' ),
						),
						array(
							'num'   => '02',
							'title' => __( 'Configure Your Package', 'bluegroup-project-blueworx' ),
							'desc'  => __( 'We shape a package around your needs and budget. You sign up when it fits, not before.', 'bluegroup-project-blueworx' ),
						),
						array(
							'num'   => '03',
							'title' => __( 'Scope, Design & Build', 'bluegroup-project-blueworx' ),
							'desc'  => __( 'We scope, design, and build your digital solution, powered by the BlueWorx Toolbox.', 'bluegroup-project-blueworx' ),
						),
						array(
							'num'   => '04',
							'title' => __( 'Support & Grow', 'bluegroup-project-blueworx' ),
							'desc'  => __( 'After launch we move into a support and growth role: your dev team, on call.', 'bluegroup-project-blueworx' ),
						),
					),
				)
			);
			?>
		</section>

		<section class="split" style="padding-top:20px">
			<div>
				<div class="eyebrow" style="margin-bottom:20px"><?php echo esc_html__( 'Ongoing Partnership', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php echo esc_html__( 'Integrate smarter, collaborate better, and scale with BlueWorx', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead" style="font-size:18px;margin:18px 0 30px"><?php echo esc_html__( 'Through ongoing support and retainer services, BlueWorx works alongside your team to support your digital solutions as your business grows.', 'bluegroup-project-blueworx' ); ?></p>
				<div class="collab-list">
					<?php foreach ( $blueworx_home_collab_items as $blueworx_home_collab_item ) : ?>
						<div class="fli" style="border-bottom:none;padding:10px 0">
							<div class="fli-icon"><?php blueworx_icon( $blueworx_home_collab_item['icon'] ); ?></div>
							<span style="font-size:17px"><?php echo esc_html( $blueworx_home_collab_item['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
				<div style="margin-top:30px">
					<a href="<?php echo esc_url( home_url( '/services' ) ); ?>" class="btn btn-outline btn-md">
						<?php echo esc_html__( 'Find Out More', 'bluegroup-project-blueworx' ); ?>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_home_arrow above.
						echo $blueworx_home_arrow;
						?>
					</a>
				</div>
			</div>
			<div class="collab-visual">
				<img src="<?php echo esc_url( BLUEWORX_SITE_URL . 'assets/img/fig-collab.jpg' ); ?>" alt="<?php echo esc_attr__( 'BlueWorx collaboration tools', 'bluegroup-project-blueworx' ); ?>" />
				<div class="collab-chip" style="top:26px;left:-14px">
					<div class="ci" style="background:#E8E7F7"><span style="width:20px;height:20px;color:#4F46E5"><?php blueworx_icon( 'chart' ); ?></span></div>
					<div><small><?php echo esc_html__( 'Conversion', 'bluegroup-project-blueworx' ); ?></small><b><?php echo esc_html__( '+38.6%', 'bluegroup-project-blueworx' ); ?></b></div>
				</div>
				<div class="collab-chip" style="bottom:30px;right:-14px">
					<div class="ci" style="background:#E7F6EE"><span style="width:20px;height:20px;color:#01824C"><?php blueworx_icon( 'server' ); ?></span></div>
					<div><small><?php echo esc_html__( 'Uptime', 'bluegroup-project-blueworx' ); ?></small><b><?php echo esc_html__( '99.9%', 'bluegroup-project-blueworx' ); ?></b></div>
				</div>
			</div>
		</section>

		<?php blueworx_public_part( 'parts/toolbox-grid.php' ); ?>

		<?php
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
