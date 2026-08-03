<?php
/**
 * About page template.
 *
 * Ported from app/about/page.tsx's five sections, in source order:
 * tech-hero (centered mode, default 820 width), "Why BlueWorx" (`.af-wrap
 * about-why`, copy column + a `svc-grid why-grid` of four plain svc-card
 * parts), a stats-band part, "Our Team" (`.team-grid` of three team-card
 * markup blocks — no shared part exists for these, and the source never
 * reuses the shape elsewhere, so it stays inline), and "Client Success
 * Stories" (`.work-grid` of three linked work-card parts on a tinted
 * background, plus a centered "View All Work" button).
 *
 * 100% static — no data varies per request. The <main><div> wrapper is
 * required, not stylistic: globals.css targets `main > div > .sec:last-child`
 * to zero the final section's bottom padding.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// A static, trusted SVG reused for the "View All Work" button arrow. Not one
// of blueworx_icon_paths()'s named icons — sized directly by `.btn svg`, the
// same reason home.php keeps its own copy rather than routing through
// blueworx_icon(). Ported verbatim from the ARROW constant in
// app/about/page.tsx.
$blueworx_about_arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>';

$blueworx_about_why_cards = array(
	array(
		'icon'  => 'users',
		'title' => __( 'One team, end to end', 'bluegroup-project-blueworx' ),
		'desc'  => __( 'Strategy to support handled in-house. No hand-offs, no finger-pointing.', 'bluegroup-project-blueworx' ),
	),
	array(
		'icon'  => 'chart',
		'title' => __( 'Built to perform', 'bluegroup-project-blueworx' ),
		'desc'  => __( 'Fast, accessible, search-ready builds measured against real goals.', 'bluegroup-project-blueworx' ),
	),
	array(
		'icon'  => 'server',
		'title' => __( 'Reliable by default', 'bluegroup-project-blueworx' ),
		'desc'  => __( 'Managed hosting, backups, and 99.9% uptime handled for you.', 'bluegroup-project-blueworx' ),
	),
	array(
		'icon'  => 'chat',
		'title' => __( 'Support that answers', 'bluegroup-project-blueworx' ),
		'desc'  => __( 'A real team one message away, usually within the same business day.', 'bluegroup-project-blueworx' ),
	),
);

$blueworx_about_stats = array(
	array(
		'value' => __( '5.0', 'bluegroup-project-blueworx' ),
		'star'  => true,
		'label' => __( 'Google Rating', 'bluegroup-project-blueworx' ),
	),
	array(
		'value' => __( '82+', 'bluegroup-project-blueworx' ),
		'label' => __( 'Projects Completed', 'bluegroup-project-blueworx' ),
	),
	array(
		'value' => __( '100k +', 'bluegroup-project-blueworx' ),
		'label' => __( 'Revenue Handled', 'bluegroup-project-blueworx' ),
	),
	array(
		'value' => __( '2K +', 'bluegroup-project-blueworx' ),
		'label' => __( 'Toolbox Value', 'bluegroup-project-blueworx' ),
	),
);

$blueworx_about_team = array(
	array(
		'name'    => 'Ross',
		'role'    => __( 'Project Manager', 'bluegroup-project-blueworx' ),
		'initial' => 'R',
	),
	array(
		'name'    => 'Jess',
		'role'    => __( 'Digital Designer', 'bluegroup-project-blueworx' ),
		'initial' => 'J',
	),
	array(
		'name'    => 'Jono',
		'role'    => __( 'Sales Manager', 'bluegroup-project-blueworx' ),
		'initial' => 'J',
	),
);

$blueworx_about_stories = array(
	array(
		'img_url'   => BLUEWORX_SITE_URL . 'assets/img/feature-image-1.jpg',
		'alt'       => __( 'Hirasté website', 'bluegroup-project-blueworx' ),
		'tags'      => array( __( 'Web Design', 'bluegroup-project-blueworx' ), __( 'Booking Platform', 'bluegroup-project-blueworx' ) ),
		'name'      => 'Hirasté',
		'desc'      => __( 'A curated platform for large group accommodation, with advanced search that helps groups book the perfect getaway.', 'bluegroup-project-blueworx' ),
		'res_value' => __( '+64%', 'bluegroup-project-blueworx' ),
		'res_text'  => __( 'group bookings', 'bluegroup-project-blueworx' ),
	),
	array(
		'img_url'   => BLUEWORX_SITE_URL . 'assets/img/feature-image-3.jpg',
		'alt'       => __( 'Padel365 website', 'bluegroup-project-blueworx' ),
		'tags'      => array( __( 'E-commerce', 'bluegroup-project-blueworx' ), __( 'Court Booking', 'bluegroup-project-blueworx' ) ),
		'name'      => 'Padel365',
		'desc'      => __( 'Powering the launch of padel clubs across Australia with seamless court discovery and booking in one experience.', 'bluegroup-project-blueworx' ),
		'res_value' => __( 'Sold-out', 'bluegroup-project-blueworx' ),
		'res_text'  => __( 'launch weekend', 'bluegroup-project-blueworx' ),
	),
	array(
		'img_url'   => BLUEWORX_SITE_URL . 'assets/img/feature-image-4.jpg',
		'alt'       => __( 'QURE website', 'bluegroup-project-blueworx' ),
		'tags'      => array( __( 'Brand', 'bluegroup-project-blueworx' ), __( 'Web Build', 'bluegroup-project-blueworx' ) ),
		'name'      => 'QURE',
		'desc'      => __( 'A clean, credible web presence for a cannabis science company, built to support growing research operations.', 'bluegroup-project-blueworx' ),
		'res_value' => __( '+38%', 'bluegroup-project-blueworx' ),
		'res_text'  => __( 'conversion rate', 'bluegroup-project-blueworx' ),
	),
);

blueworx_public_document_open( array( 'body_class' => 'bw-about' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main>
	<div>
		<?php
		blueworx_public_part(
			'parts/tech-hero.php',
			array(
				'badge'           => __( 'About BlueWorx', 'bluegroup-project-blueworx' ),
				'title'           => __( 'The Digital Agency That Works Like a Partner', 'bluegroup-project-blueworx' ),
				'title_highlight' => __( 'Works Like a Partner', 'bluegroup-project-blueworx' ),
				'lead'            => __( 'Helping your business grow without breaking the bank. One platform, one team, endless support.', 'bluegroup-project-blueworx' ),
			)
		);
		?>

		<section class="sec">
			<div class="af-wrap about-why">
				<div>
					<div class="eyebrow" style="margin-bottom:20px"><?php echo esc_html__( 'Why BlueWorx', 'bluegroup-project-blueworx' ); ?></div>
					<h2 class="h2"><?php echo esc_html__( "We're the team behind digital solutions that grow businesses", 'bluegroup-project-blueworx' ); ?></h2>
					<p class="lead" style="font-size:18px;margin-top:18px"><?php echo esc_html__( 'Founded to remove the complexity of running a modern digital business, BlueWorx brings strategy, design, build, hosting, and support under one roof, so you have one accountable partner instead of five vendors.', 'bluegroup-project-blueworx' ); ?></p>
					<div style="display:flex;gap:14px;margin-top:30px;flex-wrap:wrap">
						<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn btn-dark btn-md"><?php echo esc_html__( 'Book a Call', 'bluegroup-project-blueworx' ); ?></a>
						<a href="<?php echo esc_url( home_url( '/work' ) ); ?>" class="btn btn-outline btn-md"><?php echo esc_html__( 'View Our Work', 'bluegroup-project-blueworx' ); ?></a>
					</div>
				</div>
				<div class="svc-grid why-grid">
					<?php
					foreach ( $blueworx_about_why_cards as $blueworx_about_why_card ) {
						blueworx_public_part( 'parts/svc-card.php', $blueworx_about_why_card );
					}
					?>
				</div>
			</div>
		</section>

		<?php
		blueworx_public_part(
			'parts/stats-band.php',
			array(
				'title' => __( 'One partner powering everything digital your business needs.', 'bluegroup-project-blueworx' ),
				'copy'  => __( 'BlueWorx brings together premium services that let owners run and manage their business in one platform. The results show in the businesses we support, the projects we deliver, and the value our platform provides.', 'bluegroup-project-blueworx' ),
				'stats' => $blueworx_about_stats,
			)
		);
		?>

		<section class="sec">
			<div class="center-head" style="margin-bottom:40px">
				<h2 class="h2"><?php echo esc_html__( 'Our Team', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead"><?php echo esc_html__( 'Meet the team committed to collaboration and gold-standard delivery.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="team-grid">
				<?php foreach ( $blueworx_about_team as $blueworx_about_member ) : ?>
					<div class="team-card">
						<div class="team-photo"><span class="team-mono"><?php echo esc_html( $blueworx_about_member['initial'] ); ?></span></div>
						<h3><?php echo esc_html( $blueworx_about_member['name'] ); ?></h3>
						<p><?php echo esc_html( $blueworx_about_member['role'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="sec" style="background:#F5F6FF;padding-top:0">
			<div style="padding-top:96px">
				<div class="center-head" style="margin-bottom:40px">
					<h2 class="h2"><?php echo esc_html__( 'Client Success Stories', 'bluegroup-project-blueworx' ); ?></h2>
					<p class="lead"><?php echo esc_html__( "Explore a selection of projects we've delivered in collaboration with our partners.", 'bluegroup-project-blueworx' ); ?></p>
				</div>
				<div class="work-grid">
					<?php
					foreach ( $blueworx_about_stories as $blueworx_about_story ) {
						$blueworx_about_story['href'] = home_url( '/work' );
						blueworx_public_part( 'parts/work-card.php', $blueworx_about_story );
					}
					?>
				</div>
				<div style="display:flex;justify-content:center;margin-top:40px">
					<a href="<?php echo esc_url( home_url( '/work' ) ); ?>" class="btn btn-outline btn-md">
						<?php echo esc_html__( 'View All Work', 'bluegroup-project-blueworx' ); ?>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_about_arrow above.
						echo $blueworx_about_arrow;
						?>
					</a>
				</div>
			</div>
		</section>
	</div>
</main>
<?php
blueworx_public_part( 'parts/footer.php' );
blueworx_public_document_close();
