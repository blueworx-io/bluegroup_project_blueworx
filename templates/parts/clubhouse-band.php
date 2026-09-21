<?php
/**
 * Dark ClubHouse band (`.tbx.bw-ch-band`), used by the Home page.
 *
 * The dark band that used to hold the Toolbox grid (2026-09), now introducing ClubHouse — the demo home page beside four of the
 * nine modules, the price with its setup fee, and two buttons. Content comes
 * from blueworx_content_clubhouse(), so the modules and prices can never
 * drift from the ClubHouse page's own.
 *
 * $vars:
 * - title (string, optional) Heading.
 * - sub   (string, optional) Sub-copy under the heading.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_chb       = blueworx_content_clubhouse();
$blueworx_chb_title = isset( $title ) ? (string) $title : __( 'Introducing BlueWorx ClubHouse', 'bluegroup-project-blueworx' );
$blueworx_chb_sub   = isset( $sub ) ? (string) $sub : __( 'A ready-made website platform for sports clubs and membership organisations. Memberships, fixtures, bookings and a club shop, live inside a fortnight on managed hosting.', 'bluegroup-project-blueworx' );
$blueworx_chb_demo  = 'https://demo.305media.co.uk/';

// Four of the nine modules — the ones a committee asks about first.
$blueworx_chb_pick    = array( 'Memberships', 'Fixtures & results', 'Bookings', 'Club shop' );
$blueworx_chb_modules = array_values(
	array_filter(
		$blueworx_chb['modules'],
		function ( $module ) use ( $blueworx_chb_pick ) {
			return in_array( $module['name'], $blueworx_chb_pick, true );
		}
	)
);

// Static trusted arrow glyph, sized by `.btn svg`.
$blueworx_chb_arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>';
?>
<section class="tbx bw-ch-band">
	<?php blueworx_blob( 'width:320px;height:320px;top:-100px;left:-120px;opacity:.14' ); ?>
	<div class="tbx-head">
		<div class="bw-ch-band-tag"><span class="nav-tag tag-light"><?php esc_html_e( 'New', 'bluegroup-project-blueworx' ); ?></span><?php esc_html_e( 'Club Website Platform', 'bluegroup-project-blueworx' ); ?></div>
		<h2 class="h2"><?php echo esc_html( $blueworx_chb_title ); ?></h2>
		<p><?php echo esc_html( $blueworx_chb_sub ); ?></p>
	</div>
	<div class="bw-ch-band-grid">
		<a class="bw-ch-band-shot" href="<?php echo esc_url( $blueworx_chb_demo ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Open the ClubHouse live demo', 'bluegroup-project-blueworx' ); ?>">
			<?php blueworx_public_image( 'img/clubhouse-demo-home.jpg', __( 'The ClubHouse demo home page', 'bluegroup-project-blueworx' ) ); ?>
		</a>
		<div class="bw-ch-band-copy">
			<div class="bw-ch-band-list">
				<?php foreach ( $blueworx_chb_modules as $blueworx_chb_module ) : ?>
					<div class="bw-ch-band-item">
						<div class="bw-ch-band-ic">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
								<?php foreach ( $blueworx_chb_module['paths'] as $blueworx_chb_path ) : ?>
									<path d="<?php echo esc_attr( $blueworx_chb_path ); ?>"></path>
								<?php endforeach; ?>
							</svg>
						</div>
						<div>
							<h3><?php echo esc_html( $blueworx_chb_module['name'] ); ?></h3>
							<p><?php echo esc_html( $blueworx_chb_module['desc'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="bw-ch-band-price">
				<b>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- blueworx_public_money() escapes.
					echo blueworx_public_money( $blueworx_chb['plan']['priceM'], $blueworx_chb['plan']['currency'] );
					?>
					<span class="bw-ch-band-per"><?php esc_html_e( '/ month', 'bluegroup-project-blueworx' ); ?></span>
				</b>
				<small>
					+
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- blueworx_public_money() escapes.
					echo blueworx_public_money( $blueworx_chb['plan']['setup'], $blueworx_chb['plan']['currency'] );
					?>
					<?php esc_html_e( 'one-off setup · hosting, updates and every module included', 'bluegroup-project-blueworx' ); ?>
				</small>
			</div>
			<div class="bw-ch-band-cta">
				<a href="<?php echo esc_url( home_url( '/clubhouse' ) ); ?>" class="btn btn-brand btn-md">
					<?php esc_html_e( 'Explore ClubHouse', 'bluegroup-project-blueworx' ); ?>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted arrow glyph.
					echo $blueworx_chb_arrow;
					?>
				</a>
				<a href="<?php echo esc_url( $blueworx_chb_demo ); ?>" target="_blank" rel="noopener" class="btn btn-outline-w btn-md"><?php esc_html_e( 'View Live Demo', 'bluegroup-project-blueworx' ); ?></a>
			</div>
		</div>
	</div>
</section>
