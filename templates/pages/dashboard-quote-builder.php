<?php
/**
 * Client dashboard — the quote builder (#113).
 *
 * The same calculator the public Support page carries, rendered from the same
 * part, with two differences: the questions are always shown here (the Settings
 * switch only governs the public page), and this one says what the quote pays
 * the person building it.
 *
 * Restricted to sales staff and administrators by the section registry — see
 * includes/public/account.php.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_qb_sections = blueworx_account_sections();

blueworx_public_part(
	'parts/dash-shell.php',
	array(
		'section' => 'quote-builder',
		'heading' => $blueworx_qb_sections['quote-builder']['title'],
		'kicker'  => $blueworx_qb_sections['quote-builder']['kicker'],
	)
);
?>
<p class="dash-lede"><?php echo esc_html( $blueworx_qb_sections['quote-builder']['blurb'] ); ?></p>

<div class="dash-card quote-card">
	<?php
	blueworx_public_part(
		'parts/quote-calculator.php',
		array(
			'full'       => true,
			'commission' => true,
		)
	);
	?>
</div>

<p class="comm-note">
	<?php blueworx_icon( 'info', 'comm-hint-icon' ); ?>
	<span><?php esc_html_e( 'A build is quoted as the smallest package that covers the hours. Commission is on the first year only.', 'bluegroup-project-blueworx' ); ?></span>
</p>
<?php
blueworx_public_part( 'parts/dash-end.php' );
