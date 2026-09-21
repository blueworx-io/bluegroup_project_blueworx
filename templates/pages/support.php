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

// The Growth package is where the calculator starts, so the slider's default
// position is wherever Growth sits in the table, and its ends are the
// smallest and largest packages' monthly hours.
$blueworx_s_growth_at = 0;
foreach ( $blueworx_s_packages as $blueworx_s_i => $blueworx_s_plan ) {
	if ( 'growth' === blueworx_commerce_plan_slug( $blueworx_s_plan['name'] ) ) {
		$blueworx_s_growth_at = $blueworx_s_i;
		break;
	}
}
$blueworx_s_growth = $blueworx_s_packages[ $blueworx_s_growth_at ];
$blueworx_s_first  = reset( $blueworx_s_packages );
$blueworx_s_last   = end( $blueworx_s_packages );

// The calculator's package table, read client-side from a data attribute
// rather than duplicated in JS — see assets/js/public-widgets.js's
// initSupportCalc().
$blueworx_s_calc_packages = array_map(
	function ( $p ) {
		return array(
			'name'     => $p['name'],
			'hours'    => (int) $p['hours'],
			'price'    => (int) $p['priceM'],
			// A SureCart price can arrive in another currency; the script
			// only converts pounds.
			'currency' => isset( $p['currency'] ) ? strtoupper( (string) $p['currency'] ) : 'GBP',
			'sign'     => blueworx_public_currency_sign( isset( $p['currency'] ) ? $p['currency'] : 'GBP' ),
			'blurb'    => $p['blurb'],
		);
	},
	$blueworx_s_packages
);

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
					<span>
						<?php esc_html_e( 'from', 'bluegroup-project-blueworx' ); ?>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- blueworx_public_money() escapes.
						echo blueworx_public_money( $blueworx_s_first['priceM'], $blueworx_s_first['currency'] );
						?>
						<?php esc_html_e( 'per month', 'bluegroup-project-blueworx' ); ?>
					</span>
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
						<input class="bw-range" id="bw-support-hours" name="hours" type="range" min="0" max="<?php echo esc_attr( (string) ( count( $blueworx_s_packages ) - 1 ) ); ?>" step="1" value="<?php echo esc_attr( (string) $blueworx_s_growth_at ); ?>" aria-label="<?php esc_attr_e( 'Support hours per month', 'bluegroup-project-blueworx' ); ?>" />
						<div class="bw-range-ends">
							<?php foreach ( array( $blueworx_s_first, $blueworx_s_last ) as $blueworx_s_end ) : ?>
								<span>
									<?php
									echo esc_html(
										sprintf(
											/* translators: %d: hours a month. */
											__( '%d hrs', 'bluegroup-project-blueworx' ),
											(int) ( $blueworx_s_end['hours'] / 12 )
										)
									);
									?>
								</span>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="calc-field">
						<div class="bw-calc-label"><?php esc_html_e( 'Package', 'bluegroup-project-blueworx' ); ?></div>
						<div class="bw-calc-name" data-testid="support-calc-name"><?php echo esc_html( $blueworx_s_growth['name'] ); ?></div>
						<p class="bw-calc-blurb" data-testid="support-calc-blurb"><?php echo esc_html( $blueworx_s_growth['blurb'] ); ?></p>
					</div>
					<div class="calc-field" style="display:flex;align-items:center;justify-content:space-between;gap:16px">
						<div class="bw-calc-label" style="margin:0"><?php esc_html_e( 'Effective hourly rate', 'bluegroup-project-blueworx' ); ?></div>
						<?php
						$blueworx_s_growth_rate = $blueworx_s_growth['priceM'] * 12 / $blueworx_s_growth['hours'];
						?>
						<b class="bw-calc-rate" data-testid="support-calc-rate"<?php echo 'GBP' === $blueworx_s_growth['currency'] ? ' data-bw-gbp="' . esc_attr( number_format( $blueworx_s_growth_rate, 2, '.', '' ) ) . '" data-bw-dp="2" data-bw-suffix=" / hr"' : ''; ?>><?php echo esc_html( blueworx_public_currency_sign( $blueworx_s_growth['currency'] ) . number_format( $blueworx_s_growth_rate, 2, '.', '' ) . ' / hr' ); ?></b>
					</div>
				</div>
				<div class="calc-out">
					<div class="cl"><?php esc_html_e( 'Your package', 'bluegroup-project-blueworx' ); ?></div>
					<div class="cv" data-testid="support-calc-price"<?php echo 'GBP' === $blueworx_s_growth['currency'] ? ' data-bw-gbp="' . esc_attr( (int) $blueworx_s_growth['priceM'] ) . '"' : ''; ?>><?php echo esc_html( blueworx_public_currency_sign( $blueworx_s_growth['currency'] ) . number_format( (float) $blueworx_s_growth['priceM'], 0, '.', ',' ) ); ?></div>
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
								<td style="font-weight:600;color:#0A0C29"><?php echo esc_html( $blueworx_s_pkg['name'] ); ?></td>
								<td><?php echo esc_html( $blueworx_s_pkg['hours'] . ' ' . __( 'hrs', 'bluegroup-project-blueworx' ) ); ?></td>
								<td><?php echo esc_html( ( $blueworx_s_pkg['hours'] / 12 ) . ' ' . __( 'hrs', 'bluegroup-project-blueworx' ) ); ?></td>
								<td>
									<?php
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- blueworx_public_money() escapes.
									echo blueworx_public_money( $blueworx_s_rate, $blueworx_s_pkg['currency'], array( 'dp' => 2, 'suffix' => ' / hr' ) );
									?>
								</td>
								<td style="font-weight:600;color:#0A0C29">
									<?php
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- blueworx_public_money() escapes.
									echo blueworx_public_money( $blueworx_s_pkg['priceM'], $blueworx_s_pkg['currency'] );
									?>
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

		<?php
		blueworx_public_part(
			'parts/faq-section.php',
			array(
				'class' => 'bw-divided',
				'lead'  => __( 'Everything you need to know about Integrated Support and billing.', 'bluegroup-project-blueworx' ),
				'faqs'  => blueworx_content_support_faqs(),
			)
		);
		?>
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
