<?php
/**
 * The outcome of a client-area form submission (#97, #98).
 *
 * Shown once and then gone — the notice is cleared as it is read, so it cannot
 * reappear on a later visit and stop meaning anything.
 *
 * role="status" rather than role="alert": these follow an action the client
 * just took, so they should be announced politely at the next pause rather than
 * interrupting whatever a screen reader is mid-sentence on.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_notice = blueworx_account_take_notice();

if ( null === $blueworx_notice ) {
	return;
}
?>
<div class="dash-notice dash-notice-<?php echo esc_attr( $blueworx_notice['type'] ); ?>" role="status">
	<?php echo esc_html( $blueworx_notice['message'] ); ?>
</div>
