<?php
/**
 * The quote builder (#113), as a panel on the Labs customer dashboard.
 *
 * The same calculator the public Support page carries, rendered from the same
 * part, with two differences: the questions are always shown here (the Settings
 * switch only governs the public page), and this one says what the quote pays
 * the person building it.
 *
 * Only salespeople and administrators get this — see
 * includes/public/labs-dashboard.php. Labs draws the panel's title and lede;
 * this is the body.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// No card around it. The marketing page frames the calculator because it sits
// in the middle of a long scrolling page; here the dashboard already provides
// the frame, and a card inside a card inside the calculator's own two panels
// is three borders to look through.
blueworx_public_part(
	'parts/quote-calculator.php',
	array(
		'full'       => true,
		'commission' => true,
		'plain'      => true,
	)
);
?>

<p class="comm-note">
	<?php blueworx_icon( 'info', 'comm-hint-icon' ); ?>
	<span><?php esc_html_e( 'A build is quoted as the smallest package that covers the hours. Commission is on the first year only.', 'bluegroup-project-blueworx' ); ?></span>
</p>
