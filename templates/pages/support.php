<?php
/**
 * Integrated Support page template — monthly support packages.
 *
 * Built from the 2026-09 design (docs/superpowers/specs/2026-09-20-site-
 * restructure-design/BlueWorx Support.dc.html). Content comes from
 * blueworx_content_support_packages() (all nine) and
 * blueworx_content_support_faqs(). The hero is composed inline rather than
 * via the `tech-hero` part because the status row holds a converted price —
 * the same reason the retired Pricing page did this.
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

$blueworx_s_packages = blueworx_content_support_packages();
$blueworx_s_featured  = array_values(
	array_filter(
		$blueworx_s_packages,
		function ( $p ) {
			return ! empty( $p['featured'] );
		}
	)
);

// The design's period label for a featured card, e.g. "per month · 10 hrs a
// month" — the same string whether the card would show a monthly or annual
// figure, since Support has no billing toggle to switch between them.
foreach ( $blueworx_s_featured as $blueworx_s_i => $blueworx_s_plan ) {
	$blueworx_s_period = sprintf(
		/* translators: %d: hours a month. */
		__( 'per month · %d hrs a month', 'bluegroup-project-blueworx' ),
		(int) ( $blueworx_s_plan['hours'] / 12 )
	);

	$blueworx_s_featured[ $blueworx_s_i ]['subM'] = $blueworx_s_period;
	$blueworx_s_featured[ $blueworx_s_i ]['subA'] = $blueworx_s_period;
}

// The Growth package (index 4) is the calculator and the "Package" field's
// starting point, matching the slider's default value of 4.
$blueworx_s_growth = $blueworx_s_packages[4];

// The calculator's package table, read client-side from a data attribute
// rather than duplicated in JS — see assets/js/public-widgets.js's
// initSupportCalc().
$blueworx_s_calc_packages = array_map(
	function ( $p ) {
		return array(
			'name'  => $p['name'],
			'hours' => (int) $p['hours'],
			'gbp'   => (int) $p['priceM'],
			'blurb' => $p['blurb'],
		);
	},
	$blueworx_s_packages
);

$blueworx_s_chevron = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';

blueworx_public_document_open( array( 'body_class' => 'bw-support' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main id="content" tabindex="-1">
	<div>
		<section class="tech-hero pb-tall" style="text-align:center">
			<div class="tech-inner" style="max-width:820px;margin:0 auto">
				<div class="tech-badge" style="margin-bottom:22px"><span class="dot"></span><?php esc_html_e( 'Integrated Support', 'bluegroup-project-blueworx' ); ?></div>
				<h1 class="h1"><?php esc_html_e( 'Your Design & Development Team, ', 'bluegroup-project-blueworx' ); ?><span class="tech-grad"><?php esc_html_e( 'On Retainer', 'bluegroup-project-blueworx' ); ?></span></h1>
				<p class="lead"><?php esc_html_e( 'Buy a block of hours each month and use them however your business needs: design, development, fixes, content, SEO, or a new landing page. All of the team, none of the admin.', 'bluegroup-project-blueworx' ); ?></p>
				<div class="tech-status" style="justify-content:center">
					<span><?php esc_html_e( 'from', 'bluegroup-project-blueworx' ); ?> <b style="font:inherit" data-bw-gbp="100">£100</b> <?php esc_html_e( 'per month', 'bluegroup-project-blueworx' ); ?></span>
					<span><?php esc_html_e( 'hours pooled annually', 'bluegroup-project-blueworx' ); ?></span>
					<span><?php esc_html_e( 'cancel any time', 'bluegroup-project-blueworx' ); ?></span>
				</div>
			</div>
		</section>

		<?php blueworx_public_part( 'parts/plan-cards.php', array( 'plans' => $blueworx_s_featured ) ); ?>

		<section class="sec">
			<div class="center-head" style="margin-bottom:40px">
				<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( 'Find Your Level', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php esc_html_e( 'How Many Hours Do You Need?', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead"><?php esc_html_e( "Slide to the support you use each month. We'll show the package that covers it.", 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="calc" data-widget="support-calc" data-packages="<?php echo esc_attr( wp_json_encode( $blueworx_s_calc_packages ) ); ?>">
				<div class="calc-panel">
					<div class="calc-field">
						<label for="bw-support-hours"><?php esc_html_e( 'Support hours per month', 'bluegroup-project-blueworx' ); ?></label>
						<div class="bw-calc-big">
							<b data-testid="support-calc-hours"><?php echo esc_html( (string) ( $blueworx_s_growth['hours'] / 12 ) ); ?></b>
							<span><?php esc_html_e( 'hours', 'bluegroup-project-blueworx' ); ?> · <span data-testid="support-calc-annual"><?php echo esc_html( (string) $blueworx_s_growth['hours'] ); ?></span> <?php esc_html_e( 'hours a year', 'bluegroup-project-blueworx' ); ?></span>
						</div>
						<input class="bw-range" id="bw-support-hours" name="hours" type="range" min="0" max="8" step="1" value="4" aria-label="<?php esc_attr_e( 'Support hours per month', 'bluegroup-project-blueworx' ); ?>" />
						<div class="bw-range-ends"><span>2 hrs</span><span>50 hrs</span></div>
					</div>
					<div class="calc-field">
						<label><?php esc_html_e( 'Package', 'bluegroup-project-blueworx' ); ?></label>
						<div class="bw-calc-name" data-testid="support-calc-name"><?php echo esc_html( $blueworx_s_growth['name'] ); ?></div>
						<p class="bw-calc-blurb" data-testid="support-calc-blurb"><?php echo esc_html( $blueworx_s_growth['blurb'] ); ?></p>
					</div>
					<div class="calc-field" style="display:flex;align-items:center;justify-content:space-between;gap:16px">
						<label style="margin:0"><?php esc_html_e( 'Effective hourly rate', 'bluegroup-project-blueworx' ); ?></label>
						<?php
						$blueworx_s_growth_rate = $blueworx_s_growth['priceM'] * 12 / $blueworx_s_growth['hours'];
						?>
						<b class="bw-calc-rate" data-testid="support-calc-rate" data-bw-gbp="<?php echo esc_attr( number_format( $blueworx_s_growth_rate, 2, '.', '' ) ); ?>" data-bw-dp="2" data-bw-suffix=" / hr"><?php echo esc_html( '£' . number_format( $blueworx_s_growth_rate, 2, '.', '' ) . ' / hr' ); ?></b>
					</div>
				</div>
				<div class="calc-out">
					<div class="cl"><?php esc_html_e( 'Your package', 'bluegroup-project-blueworx' ); ?></div>
					<div class="cv" data-testid="support-calc-price" data-bw-gbp="<?php echo esc_attr( (int) $blueworx_s_growth['priceM'] ); ?>"><?php echo esc_html( '£' . number_format( (float) $blueworx_s_growth['priceM'], 0, '.', ',' ) ); ?></div>
					<div class="cp"><?php esc_html_e( 'per month', 'bluegroup-project-blueworx' ); ?></div>
					<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn btn-brand btn-md" style="width:100%;text-decoration:none"><?php esc_html_e( 'Get this plan', 'bluegroup-project-blueworx' ); ?></a>
				</div>
			</div>
		</section>

		<section class="sec bw-divided">
			<div class="center-head" style="margin-bottom:40px">
				<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( 'All Packages', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php esc_html_e( 'Nine Levels of Integrated Support', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead"><?php esc_html_e( 'Hours are an annual allowance, drawn down whenever you need them. Move up or down a level as your workload changes.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="cmp-scroll">
				<table class="cmp">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Package', 'bluegroup-project-blueworx' ); ?></th>
							<th><?php esc_html_e( 'Hours a year', 'bluegroup-project-blueworx' ); ?></th>
							<th><?php esc_html_e( 'Hours a month', 'bluegroup-project-blueworx' ); ?></th>
							<th><?php esc_html_e( 'Effective rate', 'bluegroup-project-blueworx' ); ?></th>
							<th><?php esc_html_e( 'Monthly price', 'bluegroup-project-blueworx' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $blueworx_s_packages as $blueworx_s_pkg ) : ?>
							<?php $blueworx_s_rate = $blueworx_s_pkg['priceM'] * 12 / $blueworx_s_pkg['hours']; ?>
							<tr>
								<td><?php echo esc_html( $blueworx_s_pkg['name'] ); ?></td>
								<td><?php echo esc_html( $blueworx_s_pkg['hours'] . ' ' . __( 'hrs', 'bluegroup-project-blueworx' ) ); ?></td>
								<td><?php echo esc_html( ( $blueworx_s_pkg['hours'] / 12 ) . ' ' . __( 'hrs', 'bluegroup-project-blueworx' ) ); ?></td>
								<td>
									<span data-bw-gbp="<?php echo esc_attr( number_format( $blueworx_s_rate, 2, '.', '' ) ); ?>" data-bw-dp="2" data-bw-suffix=" / hr"><?php echo esc_html( '£' . number_format( $blueworx_s_rate, 2, '.', '' ) . ' / hr' ); ?></span>
								</td>
								<td style="font-weight:600;color:#0A0C29">
									<span data-bw-gbp="<?php echo esc_attr( (int) $blueworx_s_pkg['priceM'] ); ?>"><?php echo esc_html( '£' . number_format( (float) $blueworx_s_pkg['priceM'], 0, '.', ',' ) ); ?></span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>

		<section class="features-dark">
			<?php blueworx_blob( 'width:360px;height:360px;top:-120px;right:-120px;opacity:.14' ); ?>
			<div class="fd-header">
				<h2 class="h2"><?php esc_html_e( 'What You Can Spend Your Hours On', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="fd-sub"><?php esc_html_e( 'Anything we do, you can draw from the same allowance. No separate quotes for small jobs, no surprise invoices.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="bw-g3">
				<?php
				$blueworx_s_uses = array(
					array(
						'name' => __( 'Design work', 'bluegroup-project-blueworx' ),
						'desc' => __( 'New pages, landing pages, campaign creative and refinements to what is already live.', 'bluegroup-project-blueworx' ),
					),
					array(
						'name' => __( 'Development', 'bluegroup-project-blueworx' ),
						'desc' => __( 'Features, integrations, fixes and the technical jobs nobody in-house wants to own.', 'bluegroup-project-blueworx' ),
					),
					array(
						'name' => __( 'Content', 'bluegroup-project-blueworx' ),
						'desc' => __( 'Copy edits, product and service pages, image work and publishing the things you keep putting off.', 'bluegroup-project-blueworx' ),
					),
					array(
						'name' => __( 'SEO & growth', 'bluegroup-project-blueworx' ),
						'desc' => __( 'Technical SEO, page-speed work, tracking and conversion improvements.', 'bluegroup-project-blueworx' ),
					),
					array(
						'name' => __( 'Maintenance', 'bluegroup-project-blueworx' ),
						'desc' => __( 'Updates, compatibility checks and the routine housekeeping a site needs to stay healthy.', 'bluegroup-project-blueworx' ),
					),
					array(
						'name' => __( 'Advice', 'bluegroup-project-blueworx' ),
						'desc' => __( 'Strategy calls, scoping and second opinions before you commit budget elsewhere.', 'bluegroup-project-blueworx' ),
					),
				);
				foreach ( $blueworx_s_uses as $blueworx_s_use ) :
					?>
					<div class="bw-card-dark">
						<h3><?php echo esc_html( $blueworx_s_use['name'] ); ?></h3>
						<p><?php echo esc_html( $blueworx_s_use['desc'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="sec">
			<div class="center-head" style="margin-bottom:40px">
				<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( 'How It Works', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php esc_html_e( 'Support Without the Ticket Queue', 'bluegroup-project-blueworx' ); ?></h2>
			</div>
			<?php
			blueworx_public_part(
				'parts/proc-grid.php',
				array(
					'items' => array(
						array(
							'num'   => '01',
							'title' => __( 'Pick a level', 'bluegroup-project-blueworx' ),
							'desc'  => __( 'Choose the package that matches how much you typically need each month. Start lower if you are unsure.', 'bluegroup-project-blueworx' ),
						),
						array(
							'num'   => '02',
							'title' => __( 'Ask us anything', 'bluegroup-project-blueworx' ),
							'desc'  => __( 'Send requests by email or from your client dashboard. No forms to fill in, no tickets to chase.', 'bluegroup-project-blueworx' ),
						),
						array(
							'num'   => '03',
							'title' => __( 'We draw down hours', 'bluegroup-project-blueworx' ),
							'desc'  => __( 'Every job is logged against your allowance so you can see exactly where the time went.', 'bluegroup-project-blueworx' ),
						),
						array(
							'num'   => '04',
							'title' => __( 'Review and adjust', 'bluegroup-project-blueworx' ),
							'desc'  => __( 'Running hot or barely touching it? Move up or down a package at any point in the year.', 'bluegroup-project-blueworx' ),
						),
					),
				)
			);
			?>
		</section>

		<section class="sec bw-divided">
			<div class="center-head" style="margin-bottom:40px">
				<h2 class="h2"><?php esc_html_e( 'Frequently asked questions', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead"><?php esc_html_e( 'Everything you need to know about Integrated Support and billing.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="faq-list">
				<?php foreach ( blueworx_content_support_faqs() as $blueworx_s_faq ) : ?>
					<details class="faq-item">
						<summary class="faq-q">
							<?php echo esc_html( $blueworx_s_faq['q'] ); ?>
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_s_chevron above.
							echo $blueworx_s_chevron;
							?>
						</summary>
						<div class="faq-a"><p><?php echo esc_html( $blueworx_s_faq['a'] ); ?></p></div>
					</details>
				<?php endforeach; ?>
			</div>
		</section>
	</div>
</main>
<?php
blueworx_public_part(
	'parts/footer.php',
	array(
		'cta_title'     => __( 'Not Sure Which Level Fits?', 'bluegroup-project-blueworx' ),
		'cta_copy'      => __( "Tell us what you have been asking your last developer for. We'll tell you honestly which package covers it.", 'bluegroup-project-blueworx' ),
		'cta_primary'   => array(
			'label' => __( 'Talk to Us', 'bluegroup-project-blueworx' ),
			'href'  => home_url( '/contact' ),
		),
		'cta_secondary' => array(
			'label' => __( 'See Hosting', 'bluegroup-project-blueworx' ),
			'href'  => home_url( '/hosting' ),
		),
	)
);
blueworx_public_document_close();
