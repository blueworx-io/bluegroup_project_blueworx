<?php
/**
 * Client dashboard — overview (#37, restyled to the portal design in #30).
 *
 * The design's overview is a wall of figures: live websites, monthly visits,
 * uptime per site, tools active, recent activity. Every one of those needs a
 * data source this site does not have — there is no hosting monitor, no site
 * register and no ticket system behind it.
 *
 * So this page keeps the design's shape and typography and fills it with what
 * is true: who is signed in, what they are paying and when it is next due
 * (both read from SureCart), and the way into each section. Inventing the rest
 * would make a client's own account the least trustworthy page on the site.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_dash_user = wp_get_current_user();
$blueworx_dash_subs = blueworx_account_subscriptions();
$blueworx_dash_inv  = blueworx_account_invoices();

// The design's headline card is "Next invoice". The nearest true version of
// that is the oldest invoice still awaiting payment; with none, the card
// becomes a plain "nothing due" rather than a fabricated figure.
$blueworx_dash_due = null;

foreach ( (array) $blueworx_dash_inv['rows'] as $blueworx_dash_row ) {
	if ( 'open' === $blueworx_dash_row['status'] ) {
		$blueworx_dash_due = $blueworx_dash_row;
		break;
	}
}

$blueworx_dash_active = 0;

foreach ( (array) $blueworx_dash_subs['rows'] as $blueworx_dash_row ) {
	if ( in_array( $blueworx_dash_row['status'], array( 'active', 'trialing' ), true ) ) {
		++$blueworx_dash_active;
	}
}

blueworx_public_part(
	'parts/dash-shell.php',
	array(
		'section' => '',
		'heading' => __( 'Overview', 'bluegroup-project-blueworx' ),
		'kicker'  => __( 'Your account', 'bluegroup-project-blueworx' ),
	)
);
?>
<div class="dash-welcome">
	<div>
		<h2 class="dash-h1">
			<?php
			if ( '' === blueworx_account_display_name() ) {
				esc_html_e( 'Welcome back', 'bluegroup-project-blueworx' );
			} else {
				echo esc_html(
					sprintf(
						/* translators: %s: the client's first name. */
						__( 'Welcome back, %s', 'bluegroup-project-blueworx' ),
						blueworx_account_display_name()
					)
				);
			}
			?>
		</h2>
		<p class="dash-lede"><?php esc_html_e( 'Everything BlueWorx is running for you, in one place.', 'bluegroup-project-blueworx' ); ?></p>
	</div>
	<?php if ( $blueworx_dash_subs['ok'] && $blueworx_dash_active > 0 ) : ?>
		<span class="dash-tier">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of active plans. */
					_n( '%d active plan', '%d active plans', $blueworx_dash_active, 'bluegroup-project-blueworx' ),
					$blueworx_dash_active
				)
			);
			?>
		</span>
	<?php endif; ?>
</div>

<div class="dash-split">
	<section class="dash-card">
		<div class="dash-card-head">
			<h3 class="dash-h3"><?php esc_html_e( 'Your account', 'bluegroup-project-blueworx' ); ?></h3>
		</div>
		<dl class="dash-facts">
			<div>
				<dt><?php esc_html_e( 'Name', 'bluegroup-project-blueworx' ); ?></dt>
				<dd><?php echo esc_html( $blueworx_dash_user->display_name ); ?></dd>
			</div>
			<div>
				<dt><?php esc_html_e( 'Email', 'bluegroup-project-blueworx' ); ?></dt>
				<dd><?php echo esc_html( $blueworx_dash_user->user_email ); ?></dd>
			</div>
			<?php
			// The design's overview leads on "live websites". That figure is
			// now real (#101) — it counts the register, not a monitoring feed —
			// so it is shown, and only when there is something to count. The
			// visits and uptime cards beside it in the design still are not,
			// and still are not here.
			$blueworx_dash_sites = blueworx_client_sites();
			?>
			<?php if ( $blueworx_dash_sites ) : ?>
				<div>
					<dt><?php esc_html_e( 'Websites', 'bluegroup-project-blueworx' ); ?></dt>
					<dd>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of websites on the account. */
								_n( '%d site we look after', '%d sites we look after', count( $blueworx_dash_sites ), 'bluegroup-project-blueworx' ),
								count( $blueworx_dash_sites )
							)
						);
						?>
					</dd>
				</div>
			<?php endif; ?>
		</dl>
		<p class="dash-note">
			<?php esc_html_e( 'Need something changed on your account? Tell us and we will sort it.', 'bluegroup-project-blueworx' ); ?>
			<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php esc_html_e( 'Get in touch', 'bluegroup-project-blueworx' ); ?></a>
		</p>
	</section>

	<section class="dash-due">
		<div class="dash-due-kicker"><?php esc_html_e( 'Next payment', 'bluegroup-project-blueworx' ); ?></div>
		<?php if ( null !== $blueworx_dash_due ) : ?>
			<div class="dash-due-amount"><?php echo esc_html( $blueworx_dash_due['amount'] ); ?></div>
			<div class="dash-due-sub">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: invoice reference. */
						__( 'Invoice %s is awaiting payment.', 'bluegroup-project-blueworx' ),
						$blueworx_dash_due['number']
					)
				);
				?>
			</div>
			<?php if ( '' !== $blueworx_dash_due['pay'] ) : ?>
				<a class="dash-due-btn" href="<?php echo esc_url( $blueworx_dash_due['pay'] ); ?>" rel="noopener"><?php esc_html_e( 'Pay now', 'bluegroup-project-blueworx' ); ?></a>
			<?php endif; ?>
		<?php elseif ( ! $blueworx_dash_inv['ok'] ) : ?>
			<div class="dash-due-sub"><?php esc_html_e( 'We could not reach your billing just now.', 'bluegroup-project-blueworx' ); ?></div>
		<?php else : ?>
			<div class="dash-due-amount"><?php esc_html_e( 'Nothing due', 'bluegroup-project-blueworx' ); ?></div>
			<div class="dash-due-sub"><?php esc_html_e( 'You are all paid up.', 'bluegroup-project-blueworx' ); ?></div>
		<?php endif; ?>
		<a class="dash-due-link" href="<?php echo esc_url( blueworx_account_url( 'invoices' ) ); ?>"><?php esc_html_e( 'See all invoices', 'bluegroup-project-blueworx' ); ?></a>
	</section>
</div>

<div class="dash-tiles">
	<?php foreach ( blueworx_account_visible_sections() as $blueworx_dash_slug => $blueworx_dash_section ) : ?>
		<a class="dash-tile" href="<?php echo esc_url( blueworx_account_url( $blueworx_dash_slug ) ); ?>">
			<span class="dash-tile-icon"><?php blueworx_icon( isset( $blueworx_dash_section['icon'] ) ? $blueworx_dash_section['icon'] : 'doc' ); ?></span>
			<span class="dash-tile-text">
				<span class="dash-tile-name"><?php echo esc_html( $blueworx_dash_section['label'] ); ?></span>
				<span class="dash-tile-desc"><?php echo esc_html( $blueworx_dash_section['blurb'] ); ?></span>
			</span>
		</a>
	<?php endforeach; ?>
</div>
<?php
blueworx_public_part( 'parts/dash-end.php' );
