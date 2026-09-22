<?php
/**
 * The Support Flow panel (`.support-flow`): the dark, animated column beside
 * the Contact page's enquiry form.
 *
 * Four steps of a support ticket — lands, triaged, worked on, confirmed — with
 * a connector that fills between them and an active state that walks down the
 * list on an 8s loop (assets/css/public.css, the `sf*` keyframes). Purely
 * illustrative: the ticket number and times are made up, so the whole panel is
 * marked presentational and hidden below 900px where only the form matters.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_sf_steps = array(
	array(
		'icon'  => '<path d="M4 5h16v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><path d="m4 6 8 6 8-6"/>',
		'title' => __( 'Your message lands', 'bluegroup-project-blueworx' ),
		'time'  => '09:41',
		'note'  => __( 'Portal, email or WhatsApp — all one thread.', 'bluegroup-project-blueworx' ),
	),
	array(
		'icon'  => '<path d="M3 5h18l-7 8v6l-4 2v-8Z"/>',
		'title' => __( 'Triaged by a person', 'bluegroup-project-blueworx' ),
		'time'  => '09:47',
		'note'  => __( 'Priority set and an engineer named, not a queue.', 'bluegroup-project-blueworx' ),
	),
	array(
		'icon'  => '<polyline points="8 6 3 12 8 18"/><polyline points="16 6 21 12 16 18"/>',
		'title' => __( 'Work in progress', 'bluegroup-project-blueworx' ),
		'time'  => '10:02',
		'note'  => __( 'Changes staged, tested, then pushed live.', 'bluegroup-project-blueworx' ),
	),
	array(
		'icon'  => '<polyline points="4 12.5 9.5 18 20 6.5"/>',
		'title' => __( 'Fixed and confirmed', 'bluegroup-project-blueworx' ),
		'time'  => '10:19',
		'note'  => __( 'Closed with you — never quietly closed at you.', 'bluegroup-project-blueworx' ),
	),
);

$blueworx_sf_last = count( $blueworx_sf_steps ) - 1;
?>
<div class="support-flow" role="presentation" data-support-flow>
	<div class="sf-head">
		<div class="sf-head-label"><span class="sf-dot"></span><?php esc_html_e( 'Support Flow', 'bluegroup-project-blueworx' ); ?></div>
		<div class="sf-ticket">#BW-4182</div>
	</div>

	<div class="sf-steps">
		<?php foreach ( $blueworx_sf_steps as $blueworx_sf_i => $blueworx_sf_step ) : ?>
			<div class="sf-step">
				<?php if ( $blueworx_sf_i < $blueworx_sf_last ) : ?>
					<div class="sf-track"><div class="sf-fill"></div></div>
				<?php endif; ?>
				<div class="sf-node">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="<?php echo $blueworx_sf_i === $blueworx_sf_last ? '2' : '1.8'; ?>" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG path data written in this file, not input.
						echo $blueworx_sf_step['icon'];
						?>
					</svg>
				</div>
				<div class="sf-body">
					<div class="sf-titlerow">
						<div class="sf-title"><?php echo esc_html( $blueworx_sf_step['title'] ); ?></div>
						<div class="sf-time"><?php echo esc_html( $blueworx_sf_step['time'] ); ?></div>
					</div>
					<div class="sf-note"><?php echo esc_html( $blueworx_sf_step['note'] ); ?></div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="sf-foot">
		<div class="sf-foot-label"><?php esc_html_e( 'Average first reply', 'bluegroup-project-blueworx' ); ?></div>
		<div class="sf-foot-value"><?php esc_html_e( '38 minutes', 'bluegroup-project-blueworx' ); ?></div>
	</div>
</div>
