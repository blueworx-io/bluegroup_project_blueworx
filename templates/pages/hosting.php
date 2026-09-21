<?php
/**
 * Hosting page template — managed WordPress hosting.
 *
 * Built from the 2026-09 design (docs/superpowers/specs/2026-09-20-site-
 * restructure-design/BlueWorx Hosting.dc.html). Content comes from
 * blueworx_content_hosting(). The migration section reuses the `.split` /
 * `.collab-list` / `.collab-visual` layout home.php's "Ongoing Partnership"
 * section already established.
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

$blueworx_h = blueworx_content_hosting();

// The "from £100 a month" in the support aside is the cheapest support
// package's real price, so it cannot drift from the Support page.
$blueworx_h_support_packages = blueworx_content_support_packages();
$blueworx_h_support_from     = reset( $blueworx_h_support_packages );

// Static, trusted SVGs ported verbatim from the design. See clubhouse.php's
// $blueworx_ch_arrow for why these are not routed through blueworx_icon().
$blueworx_h_arrow    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>';
$blueworx_h_ic_check = '<svg viewBox="0 0 24 24" fill="none" stroke="#01824C" stroke-width="2.4" style="width:20px;height:20px"><polyline points="20 6 9 17 4 12"></polyline></svg>';
$blueworx_h_ic_clock = '<svg viewBox="0 0 24 24" fill="none" stroke="#4F46E5" stroke-width="2" stroke-linecap="round" style="width:20px;height:20px"><circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15 14"></polyline></svg>';

blueworx_public_document_open( array( 'body_class' => 'bw-hosting' ) );
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
							'badge'           => __( 'Managed Hosting', 'bluegroup-project-blueworx' ),
							'title'           => __( 'Hosting That Stays Up, Stays Fast, and Stays Patched', 'bluegroup-project-blueworx' ),
							'title_highlight' => __( 'Stays Fast', 'bluegroup-project-blueworx' ),
							'lead'            => __( 'High-performance managed WordPress hosting with backups, SSL, security and updates handled by us. One price per site, and we move you across for free.', 'bluegroup-project-blueworx' ),
							'cta'             => array(
								array(
									'label' => __( 'Move My Site', 'bluegroup-project-blueworx' ),
									'href'  => home_url( '/contact' ),
									'class' => 'btn btn-white btn-lg',
								),
								array(
									'label' => __( 'See Pricing', 'bluegroup-project-blueworx' ),
									'href'  => '#hosting-plans',
									'class' => 'btn btn-outline-w btn-lg',
								),
							),
							'meta'            => array(
								__( '99.9% uptime', 'bluegroup-project-blueworx' ),
								__( 'free migration', 'bluegroup-project-blueworx' ),
								__( 'daily backups', 'bluegroup-project-blueworx' ),
							),
						)
					);
					?>
				</div>
				<?php
				ob_start();
				?>
				<div class="gc-metric"><small><?php esc_html_e( 'Uptime', 'bluegroup-project-blueworx' ); ?></small><b>99.98%</b><span class="up">▲</span></div>
				<div class="gc-metric"><small><?php esc_html_e( 'Median server response', 'bluegroup-project-blueworx' ); ?></small><b>184ms</b><span class="up">▲</span></div>
				<div class="gc-metric" style="border-bottom:none"><small><?php esc_html_e( 'Blocked malicious requests', 'bluegroup-project-blueworx' ); ?></small><b>41,208</b><span class="up">▲</span></div>
				<div class="gc-spark">
					<i style="height:62%"></i><i style="height:70%"></i><i style="height:58%"></i><i style="height:76%"></i><i style="height:68%"></i><i class="hi" style="height:94%"></i><i style="height:80%"></i><i style="height:86%"></i>
				</div>
				<?php
				$blueworx_h_gc_body = ob_get_clean();

				blueworx_public_part(
					'parts/glass-card.php',
					array(
						'tag'          => __( 'status · last 30 days', 'bluegroup-project-blueworx' ),
						'status_label' => __( 'All systems live', 'bluegroup-project-blueworx' ),
						'status_color' => '#01D084',
						'body'         => $blueworx_h_gc_body,
						'style'        => 'padding:28px',
						'floats'       => array(
							array(
								'icon'  => 'server',
								'label' => __( 'Backups', 'bluegroup-project-blueworx' ),
								'value' => __( 'Daily · 30 days', 'bluegroup-project-blueworx' ),
								'style' => 'top:-22px;right:-26px;animation-delay:.4s',
							),
							array(
								'icon'  => 'shield',
								'label' => __( 'WAF & SSL', 'bluegroup-project-blueworx' ),
								'value' => __( 'Always on', 'bluegroup-project-blueworx' ),
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
				<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( 'Performance', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php esc_html_e( 'Built for Speed, Measured Constantly', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead"><?php esc_html_e( 'NVMe storage, object caching and a global CDN, tuned for WordPress rather than for everything.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="bw-g3">
				<?php foreach ( $blueworx_h['perf'] as $blueworx_h_item ) : ?>
					<div class="bw-card">
						<div class="bw-stat"><?php echo esc_html( $blueworx_h_item['stat'] ); ?></div>
						<h3><?php echo esc_html( $blueworx_h_item['name'] ); ?></h3>
						<p><?php echo esc_html( $blueworx_h_item['desc'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="features-dark">
			<?php blueworx_blob( 'width:360px;height:360px;top:-120px;right:-120px;opacity:.14' ); ?>
			<div class="fd-header">
				<h2 class="h2"><?php esc_html_e( 'Security and Backups You Never Have to Think About', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="fd-sub"><?php esc_html_e( 'The jobs that only get noticed when they have not been done. We do them, every day, on every site we host.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="bw-g3">
				<?php foreach ( $blueworx_h['security'] as $blueworx_h_item ) : ?>
					<div class="bw-card-dark">
						<h3><?php echo esc_html( $blueworx_h_item['name'] ); ?></h3>
						<p><?php echo esc_html( $blueworx_h_item['desc'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="split">
			<div>
				<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( 'Migration', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php esc_html_e( 'We Move You. You Do Nothing.', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead" style="font-size:18px;margin:18px 0 30px"><?php esc_html_e( 'Send us your current login. We copy the site, test it on a staging URL, get your sign-off, then switch the DNS out of hours. No downtime, no lost orders, no charge.', 'bluegroup-project-blueworx' ); ?></p>
				<div class="collab-list">
					<div class="fli" style="border-bottom:none;padding:10px 0"><div class="fli-num">1</div><span style="font-size:17px"><?php esc_html_e( 'Copy and test on staging', 'bluegroup-project-blueworx' ); ?></span></div>
					<div class="fli" style="border-bottom:none;padding:10px 0"><div class="fli-num">2</div><span style="font-size:17px"><?php esc_html_e( 'You review and sign off', 'bluegroup-project-blueworx' ); ?></span></div>
					<div class="fli" style="border-bottom:none;padding:10px 0"><div class="fli-num">3</div><span style="font-size:17px"><?php esc_html_e( 'Out-of-hours DNS switch', 'bluegroup-project-blueworx' ); ?></span></div>
					<div class="fli" style="border-bottom:none;padding:10px 0"><div class="fli-num">4</div><span style="font-size:17px"><?php esc_html_e( 'We watch it for 48 hours', 'bluegroup-project-blueworx' ); ?></span></div>
				</div>
				<div style="margin-top:30px">
					<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn btn-outline btn-md">
						<?php esc_html_e( 'Start a Migration', 'bluegroup-project-blueworx' ); ?>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_h_arrow above.
						echo $blueworx_h_arrow;
						?>
					</a>
				</div>
			</div>
			<div class="collab-visual">
				<?php blueworx_public_image( 'img/feature-image-3.jpg', __( 'BlueWorx managed hosting', 'bluegroup-project-blueworx' ) ); ?>
				<div class="collab-chip" style="top:26px;left:-14px">
					<div class="ci" style="background:#E7F6EE">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_h_ic_check above.
						echo $blueworx_h_ic_check;
						?>
					</div>
					<div><small><?php esc_html_e( 'Downtime', 'bluegroup-project-blueworx' ); ?></small><b><?php esc_html_e( '0 minutes', 'bluegroup-project-blueworx' ); ?></b></div>
				</div>
				<div class="collab-chip" style="bottom:30px;right:-14px">
					<div class="ci" style="background:#E8E7F7">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_h_ic_clock above.
						echo $blueworx_h_ic_clock;
						?>
					</div>
					<div><small><?php esc_html_e( 'Typical move', 'bluegroup-project-blueworx' ); ?></small><b><?php esc_html_e( '2–3 days', 'bluegroup-project-blueworx' ); ?></b></div>
				</div>
			</div>
		</section>

		<section class="sec bw-divided" id="hosting-plans">
			<div class="center-head" style="margin-bottom:36px">
				<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( 'Pricing', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php esc_html_e( 'One Site. One Price. Everything In.', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead"><?php esc_html_e( 'No traffic tiers, no per-feature upsells, no renewal jump in year two.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div style="display:flex;justify-content:center;margin-bottom:34px">
				<div class="bill-toggle" data-widget="billing-toggle">
					<button type="button" class="on"><?php esc_html_e( 'Monthly billing', 'bluegroup-project-blueworx' ); ?></button>
					<button type="button"><?php esc_html_e( 'Annual billing', 'bluegroup-project-blueworx' ); ?></button>
				</div>
			</div>
			<div class="bw-g2 bw-plan-grid">
				<?php blueworx_public_part( 'parts/plan-card.php', array( 'plan' => $blueworx_h['plan'] ) ); ?>
				<div class="bw-plan-aside">
					<h3><?php esc_html_e( 'Hosting is better with support', 'bluegroup-project-blueworx' ); ?></h3>
					<p>
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s: the cheapest support package's monthly price, in a span. */
								__( 'Hosting keeps the site running. Integrated Support keeps it improving — design and development hours you can spend on anything, from %s a month.', 'bluegroup-project-blueworx' ),
								blueworx_public_money( $blueworx_h_support_from['priceM'], $blueworx_h_support_from['currency'] )
							),
							array( 'span' => array( 'data-bw-gbp' => true ) )
						);
						?>
					</p>
					<div style="display:flex;flex-direction:column;gap:10px">
						<div class="pf" style="font-size:15px"><?php esc_html_e( 'Hours pooled across the year', 'bluegroup-project-blueworx' ); ?></div>
						<div class="pf" style="font-size:15px"><?php esc_html_e( 'Priority response on hosted sites', 'bluegroup-project-blueworx' ); ?></div>
						<div class="pf" style="font-size:15px"><?php esc_html_e( 'One invoice for the lot', 'bluegroup-project-blueworx' ); ?></div>
					</div>
					<a href="<?php echo esc_url( home_url( '/support' ) ); ?>" class="btn btn-outline btn-md">
						<?php esc_html_e( 'View Integrated Support', 'bluegroup-project-blueworx' ); ?>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted markup, see $blueworx_h_arrow above.
						echo $blueworx_h_arrow;
						?>
					</a>
				</div>
			</div>
		</section>

		<section class="sec bw-divided">
			<div class="center-head" style="margin-bottom:40px">
				<div class="eyebrow" style="margin-bottom:20px"><?php esc_html_e( 'Compare', 'bluegroup-project-blueworx' ); ?></div>
				<h2 class="h2"><?php esc_html_e( 'How We Stack Up', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead"><?php esc_html_e( 'Against the two places most sites end up: a cheap shared host, or a server somebody in the business has to look after.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="cmp-scroll">
				<table class="cmp">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Feature', 'bluegroup-project-blueworx' ); ?></th>
							<th><?php esc_html_e( 'BlueWorx', 'bluegroup-project-blueworx' ); ?></th>
							<th><?php esc_html_e( 'Shared hosting', 'bluegroup-project-blueworx' ); ?></th>
							<th><?php esc_html_e( 'Self-managed VPS', 'bluegroup-project-blueworx' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $blueworx_h['compare'] as $blueworx_h_row ) : ?>
							<tr>
								<td><?php echo esc_html( $blueworx_h_row['label'] ); ?></td>
								<td><?php echo esc_html( $blueworx_h_row['a'] ); ?></td>
								<td><?php echo esc_html( $blueworx_h_row['b'] ); ?></td>
								<td><?php echo esc_html( $blueworx_h_row['c'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>

		<?php
		blueworx_public_part(
			'parts/faq-section.php',
			array(
				'class' => 'bw-divided',
				'lead'  => __( 'Everything you need to know about hosting with BlueWorx.', 'bluegroup-project-blueworx' ),
				'faqs'  => $blueworx_h['faqs'],
			)
		);
		?>
	</div>
</main>
<?php
blueworx_public_part(
	'parts/footer.php',
	array(
		'cta_title'     => __( 'Tired of Chasing Your Host?', 'bluegroup-project-blueworx' ),
		'cta_copy'      => __( "Send us your current setup. We'll tell you what it is costing you in speed, and move you across for nothing.", 'bluegroup-project-blueworx' ),
		'cta_primary'   => array(
			'label' => __( 'Move My Site', 'bluegroup-project-blueworx' ),
			'href'  => home_url( '/contact' ),
		),
		'cta_secondary' => array(
			'label' => __( 'Add Support', 'bluegroup-project-blueworx' ),
			'href'  => home_url( '/support' ),
		),
	)
);
blueworx_public_document_close();
