<?php
/**
 * Client dashboard — your websites (#101).
 *
 * The design's Websites panel carries uptime, monthly visits, core web vitals
 * and SSL/backup state per site. Every one of those is a live measurement, and
 * there is no monitoring service connected to measure them — so this page shows
 * the register instead: which sites we look after for this client, where each
 * one is, what it is hosted on and whether it is live.
 *
 * That is a smaller page than the design, and deliberately so. A typed-in
 * "99.98% uptime" is worse than no uptime figure at all, because it reads as
 * measured and a client would act on it. When a monitor is wired up it reads
 * against each record's address and the missing panels can be filled in for
 * real — see includes/records.php.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_dash_sections = blueworx_account_sections();
$blueworx_ws_sites      = blueworx_client_sites();

blueworx_public_part(
	'parts/dash-shell.php',
	array(
		'section' => 'websites',
		'heading' => $blueworx_dash_sections['websites']['title'],
		'kicker'  => $blueworx_dash_sections['websites']['kicker'],
	)
);

if ( ! $blueworx_ws_sites ) {
	blueworx_public_part(
		'parts/dash-empty.php',
		array(
			'message' => __( 'We have not recorded any websites against your account yet. If we look after a site for you, tell us and we will add it.', 'bluegroup-project-blueworx' ),
			'cta'     => __( 'Tell us about a site', 'bluegroup-project-blueworx' ),
			'href'    => blueworx_account_url( 'support' ),
		)
	);

	blueworx_public_part( 'parts/dash-end.php' );

	return;
}
?>
<ul class="dash-sites">
	<?php foreach ( $blueworx_ws_sites as $blueworx_ws_site ) : ?>
		<li class="dash-site">
			<div class="dash-site-head">
				<span class="dash-site-name"><?php echo esc_html( $blueworx_ws_site['name'] ); ?></span>
				<span class="dash-site-status dash-site-<?php echo esc_attr( $blueworx_ws_site['status'] ); ?>">
					<?php echo esc_html( $blueworx_ws_site['status_label'] ); ?>
				</span>
			</div>

			<?php if ( '' !== $blueworx_ws_site['url'] ) : ?>
				<a class="dash-site-url" href="<?php echo esc_url( $blueworx_ws_site['url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php
					// The bare host, not the full URL: it is a label here, and
					// a long address with a path wraps the card badly.
					echo esc_html( (string) wp_parse_url( $blueworx_ws_site['url'], PHP_URL_HOST ) );
					?>
				</a>
			<?php endif; ?>

			<?php if ( '' !== $blueworx_ws_site['host'] ) : ?>
				<p class="dash-site-host">
					<span class="dash-site-label"><?php esc_html_e( 'Hosting', 'bluegroup-project-blueworx' ); ?></span>
					<?php echo esc_html( $blueworx_ws_site['host'] ); ?>
				</p>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>

<?php
// Said plainly rather than left as an absence. A client who knows the design
// showed uptime figures should be told why this page does not, instead of
// wondering whether it is broken.
?>
<p class="dash-note">
	<?php esc_html_e( 'Uptime and traffic figures are not shown here yet — we would rather show nothing than a number we have not measured. Ask us any time and we will pull the real ones for you.', 'bluegroup-project-blueworx' ); ?>
</p>
<?php
blueworx_public_part( 'parts/dash-end.php' );
