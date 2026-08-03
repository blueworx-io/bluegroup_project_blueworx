<?php
/**
 * An empty state for a client-area section.
 *
 * A section with nothing in it should say so plainly and offer the one useful
 * next step, rather than showing an empty table and leaving the client to work
 * out whether that is a bug.
 *
 * $vars:
 * - message (string, required) What there is none of, in plain words.
 * - cta     (string, optional) Link text. Omitted for no link.
 * - href    (string, optional) Link target.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_empty_message = isset( $message ) ? (string) $message : '';
$blueworx_empty_cta     = isset( $cta ) ? (string) $cta : '';
$blueworx_empty_href    = isset( $href ) ? (string) $href : '';
?>
<div class="dash-empty">
	<p><?php echo esc_html( $blueworx_empty_message ); ?></p>
	<?php if ( '' !== $blueworx_empty_cta && '' !== $blueworx_empty_href ) : ?>
		<a class="btn btn-brand btn-md" href="<?php echo esc_url( $blueworx_empty_href ); ?>"><?php echo esc_html( $blueworx_empty_cta ); ?></a>
	<?php endif; ?>
</div>
