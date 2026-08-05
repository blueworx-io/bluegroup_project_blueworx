<?php
/**
 * Client dashboard — the toolbox (#99).
 *
 * The design shows a per-tool "active" state, as though the site held a record
 * of which tools are switched on for each client. It does not, and inventing
 * one would make a client's own account the least trustworthy page here.
 *
 * What IS true is simpler and is what this page says: every plan includes all
 * twelve tools, so the honest state is per-account, not per-tool — either this
 * client has an active plan and has the lot, or they do not and this is a list
 * of what a plan would include. The tools themselves come from the same
 * registry the public Toolbox page uses, so this can never list a tool we do
 * not actually offer.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_dash_sections = blueworx_account_sections();
$blueworx_tb_subs       = blueworx_account_subscriptions();
$blueworx_tb_tools      = blueworx_content_tools();
$blueworx_tb_active     = false;

// "Active" and "trialing" both mean the client has the tools today. A past-due
// subscription deliberately counts as active too — access has not been
// withdrawn, and telling somebody their tools are off when they are not is a
// support call we would have caused.
foreach ( (array) $blueworx_tb_subs['rows'] as $blueworx_tb_row ) {
	if ( in_array( $blueworx_tb_row['status'], array( 'active', 'trialing', 'past_due' ), true ) ) {
		$blueworx_tb_active = true;
		break;
	}
}

blueworx_public_part(
	'parts/dash-shell.php',
	array(
		'section' => 'toolbox',
		'heading' => $blueworx_dash_sections['toolbox']['title'],
		'kicker'  => $blueworx_dash_sections['toolbox']['kicker'],
	)
);
?>
<div class="dash-card dash-tb-state">
	<?php if ( $blueworx_tb_active ) : ?>
		<p class="dash-card-title"><?php esc_html_e( 'All twelve tools are included with your plan.', 'bluegroup-project-blueworx' ); ?></p>
		<p class="dash-card-sub"><?php esc_html_e( 'They are installed and licensed on the sites we look after for you. Ask us if you want one set up somewhere else.', 'bluegroup-project-blueworx' ); ?></p>
	<?php else : ?>
		<p class="dash-card-title"><?php esc_html_e( 'These tools come with every plan.', 'bluegroup-project-blueworx' ); ?></p>
		<p class="dash-card-sub"><?php esc_html_e( 'There is no active plan on your account at the moment, so they are not licensed to you yet.', 'bluegroup-project-blueworx' ); ?></p>
		<a class="btn btn-brand btn-sm" href="<?php echo esc_url( home_url( '/pricing' ) ); ?>"><?php esc_html_e( 'See the plans', 'bluegroup-project-blueworx' ); ?></a>
	<?php endif; ?>
</div>

<?php // The tools are a list of things, so they are marked up as one. ?>
<ul class="dash-tools">
	<?php foreach ( $blueworx_tb_tools as $blueworx_tb_tool ) : ?>
		<li class="dash-tool">
			<a class="dash-tool-link" href="<?php echo esc_url( home_url( '/toolbox/' . $blueworx_tb_tool['slug'] ) ); ?>">
				<span class="dash-tool-logo">
					<?php
					// alt="" because the tool's name is right beside it — a
					// screen reader that hears "SureCart SureCart" learns
					// nothing the second time.
					blueworx_public_image( 'img/tools/' . $blueworx_tb_tool['slug'] . '.png', '' );
					?>
				</span>
				<span class="dash-tool-text">
					<span class="dash-tool-name"><?php echo esc_html( $blueworx_tb_tool['name'] ); ?></span>
					<span class="dash-tool-desc"><?php echo esc_html( $blueworx_tb_tool['desc'] ); ?></span>
				</span>
				<span class="dash-tool-state<?php echo $blueworx_tb_active ? ' on' : ''; ?>">
					<?php
					echo esc_html(
						$blueworx_tb_active
							? __( 'Included', 'bluegroup-project-blueworx' )
							: __( 'Not active', 'bluegroup-project-blueworx' )
					);
					?>
				</span>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
<?php
blueworx_public_part( 'parts/dash-end.php' );
