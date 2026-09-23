<?php
/**
 * Client dashboard — the commission calculator (#112).
 *
 * Only salespeople and administrators ever reach this: the section carries a
 * `restricted` callback (includes/public/account.php) which hides the tab AND
 * turns anybody else away from the address, because what a sale pays us is not
 * a client's business.
 *
 * The page is rendered with the default sale already worked out in PHP rather
 * than as an empty frame for JavaScript to fill, so it is correct before
 * assets/js/commission.js runs and it degrades to a readable summary if the
 * script never arrives. The script then takes the same numbers and recalculates
 * on every control, with no round trip.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_comm_sections = blueworx_account_sections();
$blueworx_comm_sale     = blueworx_commission_default_sale();
$blueworx_comm_summary  = blueworx_commission_summary( $blueworx_comm_sale );
$blueworx_comm_products = blueworx_commission_products();
$blueworx_comm_packages = blueworx_commission_packages();
$blueworx_comm_rates    = blueworx_commission_rates();

blueworx_public_part(
	'parts/dash-shell.php',
	array(
		'section' => 'commission',
		'heading' => $blueworx_comm_sections['commission']['title'],
		'kicker'  => $blueworx_comm_sections['commission']['kicker'],
	)
);
?>
<p class="dash-lede"><?php echo esc_html( $blueworx_comm_sections['commission']['blurb'] ); ?></p>

<div class="comm" data-commission>
	<section class="dash-card comm-build">
		<header class="comm-head">
			<h2 class="comm-head-title"><?php esc_html_e( 'Build the sale', 'bluegroup-project-blueworx' ); ?></h2>
			<button type="button" class="comm-reset" data-reset><?php esc_html_e( 'Reset', 'bluegroup-project-blueworx' ); ?></button>
		</header>

		<?php foreach ( $blueworx_comm_products as $blueworx_comm_key => $blueworx_comm_product ) : ?>
			<?php
			$blueworx_comm_line    = isset( $blueworx_comm_summary['lines'][ $blueworx_comm_key ] ) ? $blueworx_comm_summary['lines'][ $blueworx_comm_key ] : null;
			$blueworx_comm_billing = $blueworx_comm_sale[ $blueworx_comm_key ]['billing'];
			?>
			<div class="comm-row" data-row="<?php echo esc_attr( $blueworx_comm_key ); ?>">
				<div class="comm-row-name">
					<div class="comm-row-title"><?php echo esc_html( $blueworx_comm_product['name'] ); ?></div>
					<div class="comm-row-meta">
						<?php
						printf(
							/* translators: 1: yearly price, 2: monthly price. */
							esc_html__( '%1$s per year · %2$s per month', 'bluegroup-project-blueworx' ),
							esc_html( blueworx_commission_money( $blueworx_comm_product['year'] ) ),
							esc_html( blueworx_commission_money( $blueworx_comm_product['month'] ) )
						);
						?>
					</div>
				</div>

				<?php
				/* translators: %s: product name. */
				$blueworx_comm_count = sprintf( __( 'How many of %s', 'bluegroup-project-blueworx' ), $blueworx_comm_product['name'] );
				?>
				<div class="comm-step" role="group" aria-label="<?php echo esc_attr( $blueworx_comm_count ); ?>">
					<button type="button" class="comm-step-btn" data-step="down" aria-label="<?php esc_attr_e( 'One fewer', 'bluegroup-project-blueworx' ); ?>">&minus;</button>
					<span class="comm-step-val" data-qty><?php echo esc_html( (string) $blueworx_comm_sale[ $blueworx_comm_key ]['qty'] ); ?></span>
					<button type="button" class="comm-step-btn" data-step="up" aria-label="<?php esc_attr_e( 'One more', 'bluegroup-project-blueworx' ); ?>">+</button>
				</div>

				<div class="comm-seg" role="group" aria-label="<?php esc_attr_e( 'Billing', 'bluegroup-project-blueworx' ); ?>">
					<button type="button" class="comm-seg-btn<?php echo 'year' === $blueworx_comm_billing ? ' on' : ''; ?>" data-billing="year" aria-pressed="<?php echo 'year' === $blueworx_comm_billing ? 'true' : 'false'; ?>"><?php esc_html_e( 'Yearly', 'bluegroup-project-blueworx' ); ?></button>
					<button type="button" class="comm-seg-btn<?php echo 'month' === $blueworx_comm_billing ? ' on' : ''; ?>" data-billing="month" aria-pressed="<?php echo 'month' === $blueworx_comm_billing ? 'true' : 'false'; ?>"><?php esc_html_e( 'Monthly', 'bluegroup-project-blueworx' ); ?></button>
				</div>

				<div class="comm-row-result">
					<div class="comm-row-fee"><?php echo esc_html( blueworx_commission_money( $blueworx_comm_line ? $blueworx_comm_line['commission'] : 0 ) ); ?></div>
					<div class="comm-row-rate">
						<?php if ( $blueworx_comm_line ) : ?>
							<?php
							printf(
								/* translators: 1: rate as a percentage, 2: annual value. */
								esc_html__( '%1$s%% of %2$s a year', 'bluegroup-project-blueworx' ),
								esc_html( (string) round( $blueworx_comm_line['rate'] * 100 ) ),
								esc_html( blueworx_commission_money( $blueworx_comm_line['value'] ) )
							);
							?>
						<?php else : ?>
							<?php esc_html_e( 'Not included', 'bluegroup-project-blueworx' ); ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>

		<?php
		$blueworx_comm_support = isset( $blueworx_comm_summary['lines']['support'] ) ? $blueworx_comm_summary['lines']['support'] : null;
		$blueworx_comm_picked  = isset( $blueworx_comm_packages[ $blueworx_comm_sale['support'] ] ) ? $blueworx_comm_packages[ $blueworx_comm_sale['support'] ] : null;
		?>
		<div class="comm-row" data-row="support">
			<div class="comm-row-name">
				<div class="comm-row-title"><?php esc_html_e( 'Integrated Support', 'bluegroup-project-blueworx' ); ?></div>
				<div class="comm-row-meta" data-support-meta>
					<?php if ( $blueworx_comm_picked ) : ?>
						<?php
						printf(
							/* translators: 1: hours a year, 2: hours a month, 3: hourly rate. */
							esc_html__( '%1$d hrs a year · %2$s hrs a month · %3$s / hr', 'bluegroup-project-blueworx' ),
							esc_html( $blueworx_comm_picked['hours'] ),
							esc_html( blueworx_content_hours_a_month( $blueworx_comm_picked['hours'] ) ),
							esc_html( blueworx_commission_money( $blueworx_comm_picked['annual'] / max( 1, $blueworx_comm_picked['hours'] ) ) )
						);
						?>
					<?php else : ?>
						<?php esc_html_e( 'Pick a package — priced monthly, billed over 12 months', 'bluegroup-project-blueworx' ); ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="comm-step" role="group" aria-label="<?php esc_attr_e( 'How many support packages', 'bluegroup-project-blueworx' ); ?>">
				<button type="button" class="comm-step-btn" data-step="down" aria-label="<?php esc_attr_e( 'One fewer', 'bluegroup-project-blueworx' ); ?>">&minus;</button>
				<span class="comm-step-val" data-qty><?php echo esc_html( (string) $blueworx_comm_sale['supportQty'] ); ?></span>
				<button type="button" class="comm-step-btn" data-step="up" aria-label="<?php esc_attr_e( 'One more', 'bluegroup-project-blueworx' ); ?>">+</button>
			</div>

			<label class="screen-reader-text" for="bw-comm-package"><?php esc_html_e( 'Support package', 'bluegroup-project-blueworx' ); ?></label>
			<select class="comm-select" id="bw-comm-package" data-package>
				<option value=""><?php esc_html_e( 'No support package', 'bluegroup-project-blueworx' ); ?></option>
				<?php foreach ( $blueworx_comm_packages as $blueworx_comm_id => $blueworx_comm_package ) : ?>
					<option value="<?php echo esc_attr( $blueworx_comm_id ); ?>" <?php selected( $blueworx_comm_id, $blueworx_comm_sale['support'] ); ?>>
						<?php
						printf(
							/* translators: 1: package name, 2: monthly price. */
							esc_html__( '%1$s — %2$s / month', 'bluegroup-project-blueworx' ),
							esc_html( $blueworx_comm_package['name'] ),
							esc_html( blueworx_commission_money( $blueworx_comm_package['month'] ) )
						);
						?>
					</option>
				<?php endforeach; ?>
			</select>

			<div class="comm-row-result">
				<div class="comm-row-fee"><?php echo esc_html( blueworx_commission_money( $blueworx_comm_support ? $blueworx_comm_support['commission'] : 0 ) ); ?></div>
				<div class="comm-row-rate">
					<?php if ( $blueworx_comm_support ) : ?>
						<?php
						printf(
							/* translators: 1: rate as a percentage, 2: annual value. */
							esc_html__( '%1$s%% of %2$s a year', 'bluegroup-project-blueworx' ),
							esc_html( (string) round( $blueworx_comm_support['rate'] * 100 ) ),
							esc_html( blueworx_commission_money( $blueworx_comm_support['annual'] ) )
						);
						?>
					<?php else : ?>
						<?php esc_html_e( 'Not included', 'bluegroup-project-blueworx' ); ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="comm-hint"<?php echo $blueworx_comm_picked ? '' : ' hidden'; ?>>
				<?php blueworx_icon( 'info', 'comm-hint-icon' ); ?>
				<span data-hint>
					<?php if ( $blueworx_comm_picked && $blueworx_comm_picked['annual'] < $blueworx_comm_rates['threshold'] ) : ?>
						<?php
						printf(
							/* translators: %s: the shortfall in annual value. */
							esc_html__( '%s more in annual value moves this to the 20%% rate.', 'bluegroup-project-blueworx' ),
							esc_html( blueworx_commission_money( $blueworx_comm_rates['threshold'] - $blueworx_comm_picked['annual'] ) )
						);
						?>
					<?php elseif ( $blueworx_comm_picked ) : ?>
						<?php
						printf(
							/* translators: %s: the package's annual value. */
							esc_html__( 'At %s a year this package earns the higher 20%% rate.', 'bluegroup-project-blueworx' ),
							esc_html( blueworx_commission_money( $blueworx_comm_picked['annual'] ) )
						);
						?>
					<?php endif; ?>
				</span>
			</div>
		</div>
	</section>

	<aside class="dash-card comm-out">
		<header class="comm-head">
			<h2 class="comm-head-title"><?php esc_html_e( 'Your commission', 'bluegroup-project-blueworx' ); ?></h2>
			<div class="comm-seg" role="group" aria-label="<?php esc_attr_e( 'Show commission', 'bluegroup-project-blueworx' ); ?>">
				<button type="button" class="comm-seg-btn" data-view="month" aria-pressed="false"><?php esc_html_e( 'Monthly', 'bluegroup-project-blueworx' ); ?></button>
				<button type="button" class="comm-seg-btn on" data-view="year" aria-pressed="true"><?php esc_html_e( 'Annual', 'bluegroup-project-blueworx' ); ?></button>
			</div>
		</header>

		<div class="comm-total-block">
			<p class="comm-total">
				<?php echo esc_html( blueworx_commission_money( $blueworx_comm_summary['commission'] ) ); ?>
				<span class="comm-total-when"><?php esc_html_e( 'in year one', 'bluegroup-project-blueworx' ); ?></span>
			</p>
			<p class="comm-total-sub">
				<?php
				printf(
					/* translators: 1: monthly commission, 2: annual sale value. */
					esc_html__( '%1$s a month, on %2$s of annual sale value', 'bluegroup-project-blueworx' ),
					esc_html( blueworx_commission_money( $blueworx_comm_summary['commission'] / 12 ) ),
					esc_html( blueworx_commission_money( $blueworx_comm_summary['value'] ) )
				);
				?>
			</p>
		</div>

		<div class="comm-break" data-breakdown>
			<?php foreach ( $blueworx_comm_summary['lines'] as $blueworx_comm_line ) : ?>
				<div class="comm-break-row">
					<div>
						<div class="comm-break-name"><?php echo esc_html( $blueworx_comm_line['name'] ); ?></div>
						<div class="comm-break-detail">
							<?php if ( isset( $blueworx_comm_line['package'] ) ) : ?>
								<?php
								printf(
									/* translators: 1: quantity, 2: package name, 3: monthly price, 4: rate. */
									esc_html__( '%1$d × %2$s · %3$s a month · %4$s%%', 'bluegroup-project-blueworx' ),
									esc_html( $blueworx_comm_line['qty'] ),
									esc_html( $blueworx_comm_line['package'] ),
									esc_html( blueworx_commission_money( $blueworx_comm_line['unit'] ) ),
									esc_html( (string) round( $blueworx_comm_line['rate'] * 100 ) )
								);
								?>
							<?php else : ?>
								<?php
								printf(
									/* translators: 1: quantity, 2: unit price, 3: billing period, 4: rate. */
									esc_html__( '%1$d × %2$s %3$s · %4$s%%', 'bluegroup-project-blueworx' ),
									esc_html( $blueworx_comm_line['qty'] ),
									esc_html( blueworx_commission_money( $blueworx_comm_line['unit'] ) ),
									'month' === $blueworx_comm_line['billing'] ? esc_html__( 'monthly', 'bluegroup-project-blueworx' ) : esc_html__( 'yearly', 'bluegroup-project-blueworx' ),
									esc_html( (string) round( $blueworx_comm_line['rate'] * 100 ) )
								);
								?>
							<?php endif; ?>
						</div>
					</div>
					<div class="comm-break-fee"><?php echo esc_html( blueworx_commission_money( $blueworx_comm_line['commission'] ) ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="comm-rates">
			<strong><?php esc_html_e( 'Rates · first year only', 'bluegroup-project-blueworx' ); ?></strong>
			<span><?php esc_html_e( 'Website Hosting and ClubHouse: 20%', 'bluegroup-project-blueworx' ); ?></span>
			<span>
				<?php
				printf(
					/* translators: %s: the threshold, e.g. £9,000. */
					esc_html__( 'Integrated Support: 10%% under %1$s a year, 20%% from %1$s', 'bluegroup-project-blueworx' ),
					esc_html( blueworx_commission_money( $blueworx_comm_rates['threshold'] ) )
				);
				?>
			</span>
		</div>
	</aside>

	<p class="comm-note">
		<?php blueworx_icon( 'info', 'comm-hint-icon' ); ?>
		<span><?php esc_html_e( 'Commission is paid on the first year of each sale only. Renewals do not earn commission.', 'bluegroup-project-blueworx' ); ?></span>
	</p>

	<noscript>
		<p class="comm-note">
			<?php esc_html_e( 'The figures above are for one of each product on the Growth package. Turn JavaScript on to build a different sale.', 'bluegroup-project-blueworx' ); ?>
		</p>
	</noscript>
</div>
<?php
blueworx_public_part( 'parts/dash-end.php' );
