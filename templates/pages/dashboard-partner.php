<?php
/**
 * Client dashboard — your referrals (#100).
 *
 * Every row here was entered by the team against this partner, so the page is
 * exactly as complete as the register is. Nothing is totalled that has not been
 * paid, and nothing is projected: the design's running commission figure would
 * mean adding up amounts in whatever currency each was typed in, which is a
 * number that would be wrong for anyone we pay in anything but one currency.
 *
 * The section is only offered to somebody who actually has referrals (see the
 * `only_if` on it in includes/public/account.php), so most clients never see a
 * Partner tab at all. Reaching this page without any is still possible — a
 * bookmark, or a partner whose first referral has not been entered yet — and
 * that is what the empty state is for.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_dash_sections = blueworx_account_sections();
$blueworx_pt_referrals  = blueworx_partner_referrals();

blueworx_public_part(
	'parts/dash-shell.php',
	array(
		'section' => 'partner',
		'heading' => $blueworx_dash_sections['partner']['title'],
		'kicker'  => $blueworx_dash_sections['partner']['kicker'],
	)
);

if ( ! $blueworx_pt_referrals ) {
	blueworx_public_part(
		'parts/dash-empty.php',
		array(
			'message' => __( 'Nothing recorded against you yet. Send somebody our way and it will appear here once we have spoken to them.', 'bluegroup-project-blueworx' ),
			'cta'     => __( 'Ask us about the partner scheme', 'bluegroup-project-blueworx' ),
			'href'    => blueworx_account_url( 'support' ),
		)
	);

	blueworx_public_part( 'parts/dash-end.php' );

	return;
}

// dash-table.php takes the same result shape the SureCart sections use, so the
// referral list is rendered by the table the rest of the portal already uses
// rather than a second one that styles itself slightly differently.
$blueworx_pt_rows = array();

foreach ( $blueworx_pt_referrals as $blueworx_pt_referral ) {
	$blueworx_pt_rows[] = array(
		'name'         => $blueworx_pt_referral['name'],
		'date'         => $blueworx_pt_referral['date'],
		// The key drives the pill's colour, the label its wording — see the
		// status branch in parts/dash-table.php.
		'status'       => $blueworx_pt_referral['status'],
		'status_label' => $blueworx_pt_referral['status_label'],
		// An unpaid referral shows a dash rather than a zero: nothing has been
		// agreed yet, and "£0" reads as a decision that it is worth nothing.
		'amount'       => '' === $blueworx_pt_referral['amount'] ? '—' : $blueworx_pt_referral['amount'],
	);
}

blueworx_public_part(
	'parts/dash-table.php',
	array(
		'result'  => array(
			'ok'   => true,
			'rows' => $blueworx_pt_rows,
		),
		'columns' => array(
			'name'   => __( 'Business', 'bluegroup-project-blueworx' ),
			'date'   => __( 'Added', 'bluegroup-project-blueworx' ),
			'status' => __( 'Status', 'bluegroup-project-blueworx' ),
			'amount' => __( 'Commission', 'bluegroup-project-blueworx' ),
		),
		'empty'   => __( 'Nothing recorded against you yet.', 'bluegroup-project-blueworx' ),
	)
);
?>
<p class="dash-note">
	<?php esc_html_e( 'Commission is paid monthly, 30 days after the client’s first invoice is settled. Anything still marked as an enquiry has not been agreed yet.', 'bluegroup-project-blueworx' ); ?>
</p>
<?php
blueworx_public_part( 'parts/dash-end.php' );
