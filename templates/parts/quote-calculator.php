<?php
/**
 * The quote builder (#113) — one calculator, two homes.
 *
 * The public Support page and the Sales section both render this part, so a
 * salesperson and a visitor can never be quoted different numbers. What each
 * one gets is decided by two flags rather than by two copies of the markup:
 *
 * $vars:
 * - full       (bool) Show the hosting/ClubHouse questions. False renders the
 *              plain hours slider the Support page had before, which is what
 *              the Settings switch turns it back to.
 * - commission (bool) Show what the quote pays. Never true on a public page.
 * - plain      (bool) Drop the decorative frame the marketing page gives it.
 *              The client area has its own page furniture and does not need a
 *              second card around the calculator inside it.
 *
 * The slider's markup and its data-testid hooks are unchanged from when this
 * lived in pages/support.php: the currency painter reads them, and so do the
 * specs that have covered that calculator since it was built.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_q_full       = ! empty( $full );
$blueworx_q_commission = ! empty( $commission );
$blueworx_q_plain      = ! empty( $plain );
$blueworx_q_packages   = blueworx_content_support_packages();
$blueworx_q_quote      = blueworx_quote_default();
$blueworx_q_model      = blueworx_quote_model();
$blueworx_q_summary    = blueworx_quote_summary( $blueworx_q_quote );

// The slider starts at Growth, wherever Growth sits in the table.
$blueworx_q_at = 0;
foreach ( $blueworx_q_packages as $blueworx_q_i => $blueworx_q_plan ) {
	if ( 'growth' === blueworx_commerce_plan_slug( $blueworx_q_plan['name'] ) ) {
		$blueworx_q_at = $blueworx_q_i;
		break;
	}
}

$blueworx_q_growth = $blueworx_q_packages[ $blueworx_q_at ];
$blueworx_q_first  = reset( $blueworx_q_packages );
$blueworx_q_last   = end( $blueworx_q_packages );
$blueworx_q_rate   = $blueworx_q_growth['priceM'] * 12 / $blueworx_q_growth['hours'];
$blueworx_q_gbp    = 'GBP' === $blueworx_q_growth['currency'];

// The package table, read client-side from a data attribute rather than
// duplicated in JS.
$blueworx_q_table = array_map(
	function ( $p ) {
		return array(
			'name'     => $p['name'],
			'slug'     => blueworx_commerce_plan_slug( $p['name'] ),
			'hours'    => (int) $p['hours'],
			'price'    => (int) $p['priceM'],
			// A SureCart price can arrive in another currency; the script
			// only converts pounds.
			'currency' => isset( $p['currency'] ) ? strtoupper( (string) $p['currency'] ) : 'GBP',
			'sign'     => blueworx_public_currency_sign( isset( $p['currency'] ) ? $p['currency'] : 'GBP' ),
			'blurb'    => $p['blurb'],
		);
	},
	$blueworx_q_packages
);

/**
 * Renders a Yes/No toggle.
 *
 * @param string $name  Toggle name, used as its hook.
 * @param string $label What the question is.
 * @param bool   $on    Whether Yes is the current answer.
 * @return void
 */
function blueworx_quote_toggle( $name, $label, $on ) {
	?>
	<div class="quote-ask">
		<span class="quote-ask-label"><?php echo esc_html( $label ); ?></span>
		<span class="comm-seg" role="group" data-toggle="<?php echo esc_attr( $name ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
			<button type="button" class="comm-seg-btn<?php echo $on ? '' : ' on'; ?>" data-value="no" aria-pressed="<?php echo $on ? 'false' : 'true'; ?>"><?php esc_html_e( 'No', 'bluegroup-project-blueworx' ); ?></button>
			<button type="button" class="comm-seg-btn<?php echo $on ? ' on' : ''; ?>" data-value="yes" aria-pressed="<?php echo $on ? 'true' : 'false'; ?>"><?php esc_html_e( 'Yes', 'bluegroup-project-blueworx' ); ?></button>
		</span>
	</div>
	<?php
}
?>
<div class="calc<?php echo $blueworx_q_full ? ' calc-quote' : ''; ?><?php echo $blueworx_q_plain ? ' calc-plain' : ''; ?>" data-widget="support-calc"
	data-packages="<?php echo esc_attr( wp_json_encode( $blueworx_q_table ) ); ?>"
	<?php echo $blueworx_q_full ? ' data-quote="full"' : ''; ?>>
	<div class="calc-panel">
		<?php if ( $blueworx_q_full ) : ?>
			<div class="calc-field quote-asks">
				<?php
				blueworx_quote_toggle( 'hosting', __( 'Website hosting?', 'bluegroup-project-blueworx' ), false );
				// One or the other: a site is hosted with us or it is a
				// ClubHouse, and a quote for both is a quote for two sites.
				blueworx_quote_toggle( 'clubhouse', __( 'ClubHouse?', 'bluegroup-project-blueworx' ), false );
				?>
			</div>

			<div class="calc-field quote-branch" data-hosting-mode hidden>
				<div class="bw-calc-label"><?php esc_html_e( 'What is the work?', 'bluegroup-project-blueworx' ); ?></div>
				<div class="comm-seg" role="group" aria-label="<?php esc_attr_e( 'What is the work', 'bluegroup-project-blueworx' ); ?>">
					<button type="button" class="comm-seg-btn on" data-value="support" aria-pressed="true"><?php esc_html_e( 'Ongoing support', 'bluegroup-project-blueworx' ); ?></button>
					<button type="button" class="comm-seg-btn" data-value="build" aria-pressed="false"><?php esc_html_e( 'New build or rebuild', 'bluegroup-project-blueworx' ); ?></button>
				</div>
			</div>

			<div class="calc-field quote-branch" data-clubhouse-mode hidden>
				<div class="bw-calc-label"><?php esc_html_e( 'Which ClubHouse?', 'bluegroup-project-blueworx' ); ?></div>
				<div class="comm-seg" role="group" aria-label="<?php esc_attr_e( 'Which ClubHouse', 'bluegroup-project-blueworx' ); ?>">
					<button type="button" class="comm-seg-btn on" data-value="standard" aria-pressed="true"><?php esc_html_e( 'Standard', 'bluegroup-project-blueworx' ); ?></button>
					<button type="button" class="comm-seg-btn" data-value="custom" aria-pressed="false"><?php esc_html_e( 'Custom built', 'bluegroup-project-blueworx' ); ?></button>
				</div>
			</div>

			<?php
			// Only for a standard ClubHouse: the platform is theirs either way,
			// and this is whether we look after it. Yes brings the hours slider
			// back so a package can be picked alongside it.
			?>
			<div class="calc-field quote-branch" data-clubhouse-support hidden>
				<div class="quote-rows">
					<?php blueworx_quote_toggle( 'management', __( 'Ongoing management?', 'bluegroup-project-blueworx' ), false ); ?>
				</div>
			</div>

			<?php
			// Asked by a build (it is 30 hours of work) and by a standard
			// ClubHouse (it is what the setup fee is for), so it lives outside
			// the build box rather than being written out twice.
			?>
			<div class="calc-field quote-branch" data-membership-ask hidden>
				<div class="quote-rows">
					<?php blueworx_quote_toggle( 'membership', __( 'Membership system?', 'bluegroup-project-blueworx' ), false ); ?>
				</div>
			</div>

			<div class="calc-field quote-build" data-build hidden>
				<div class="quote-rows">
					<div class="quote-ask" data-pages>
						<span class="quote-ask-label"><?php esc_html_e( 'How many pages?', 'bluegroup-project-blueworx' ); ?></span>
						<span class="comm-step" role="group" aria-label="<?php esc_attr_e( 'How many pages', 'bluegroup-project-blueworx' ); ?>">
							<button type="button" class="comm-step-btn" data-step="down" aria-label="<?php esc_attr_e( 'One fewer', 'bluegroup-project-blueworx' ); ?>">&minus;</button>
							<span class="comm-step-val" data-qty><?php echo esc_html( (string) $blueworx_q_model['default_pages'] ); ?></span>
							<button type="button" class="comm-step-btn" data-step="up" aria-label="<?php esc_attr_e( 'One more', 'bluegroup-project-blueworx' ); ?>">+</button>
						</span>
					</div>

					<?php
					// Asked on every build, ClubHouse included: a booking system
					// or a CRM is somebody else's system to learn, connect and
					// test whatever the site is built on.
					?>
					<div class="quote-ask" data-integrations>
						<span class="quote-ask-label"><?php esc_html_e( 'Custom integrations', 'bluegroup-project-blueworx' ); ?></span>
						<span class="comm-step" role="group" aria-label="<?php esc_attr_e( 'How many custom integrations', 'bluegroup-project-blueworx' ); ?>">
							<button type="button" class="comm-step-btn" data-step="down" aria-label="<?php esc_attr_e( 'One fewer', 'bluegroup-project-blueworx' ); ?>">&minus;</button>
							<span class="comm-step-val" data-qty>0</span>
							<button type="button" class="comm-step-btn" data-step="up" aria-label="<?php esc_attr_e( 'One more', 'bluegroup-project-blueworx' ); ?>">+</button>
						</span>
					</div>

					<div class="quote-ask" data-pages-fixed hidden>
						<span class="quote-ask-label"><?php esc_html_e( 'Pages', 'bluegroup-project-blueworx' ); ?></span>
						<span class="quote-ask-value">
							<?php
							printf(
								/* translators: %d: number of pages. */
								esc_html__( '%d, the ClubHouse set', 'bluegroup-project-blueworx' ),
								esc_html( $blueworx_q_model['clubhouse_pages'] )
							);
							?>
						</span>
					</div>

				</div>

				<ul class="quote-stages" data-stages>
					<?php foreach ( blueworx_quote_build_stages( $blueworx_q_model['default_pages'], false ) as $blueworx_q_stage ) : ?>
						<li data-stage="<?php echo esc_attr( $blueworx_q_stage['key'] ); ?>">
							<span><?php echo esc_html( $blueworx_q_stage['label'] ); ?></span>
							<b>
								<?php
								printf(
									/* translators: %d: hours. */
									esc_html__( '%d hrs', 'bluegroup-project-blueworx' ),
									esc_html( $blueworx_q_stage['hours'] )
								);
								?>
							</b>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="quote-hours">
					<span><?php esc_html_e( 'Hours needed', 'bluegroup-project-blueworx' ); ?></span>
					<b data-testid="quote-hours">0</b>
				</div>

				<?php
				// What is left of the package's annual allowance once the build
				// has been taken out of it — the number a salesperson is asked
				// about next, since it is the support the client gets for the
				// rest of that first year.
				?>
				<div class="quote-hours quote-hours-left" data-left hidden>
					<span><?php esc_html_e( 'Left for the rest of the year', 'bluegroup-project-blueworx' ); ?></span>
					<b data-testid="quote-remaining">0</b>
				</div>

				<p class="quote-over" data-testid="quote-over" hidden>
					<?php esc_html_e( 'This is more work than the largest package covers. Quote it by hand.', 'bluegroup-project-blueworx' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="calc-field" data-slider>
			<label for="bw-support-hours"><?php esc_html_e( 'Support hours per month', 'bluegroup-project-blueworx' ); ?></label>
			<div class="bw-calc-big">
				<b data-testid="support-calc-hours"><?php echo esc_html( blueworx_content_hours_a_month( $blueworx_q_growth['hours'] ) ); ?></b>
				<span><?php esc_html_e( 'hours', 'bluegroup-project-blueworx' ); ?> · <span data-testid="support-calc-annual"><?php echo esc_html( (string) $blueworx_q_growth['hours'] ); ?></span> <?php esc_html_e( 'hours a year', 'bluegroup-project-blueworx' ); ?></span>
			</div>
			<input class="bw-range" id="bw-support-hours" name="hours" type="range" min="0" max="<?php echo esc_attr( (string) ( count( $blueworx_q_packages ) - 1 ) ); ?>" step="1" value="<?php echo esc_attr( (string) $blueworx_q_at ); ?>" aria-label="<?php esc_attr_e( 'Support hours per month', 'bluegroup-project-blueworx' ); ?>" />
			<div class="bw-range-ends">
				<?php foreach ( array( $blueworx_q_first, $blueworx_q_last ) as $blueworx_q_end ) : ?>
					<span>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: hours a month. */
								__( '%s hrs', 'bluegroup-project-blueworx' ),
								blueworx_content_hours_a_month( $blueworx_q_end['hours'] )
							)
						);
						?>
					</span>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="calc-field">
			<div class="bw-calc-label"><?php esc_html_e( 'Package', 'bluegroup-project-blueworx' ); ?></div>
			<div class="bw-calc-name" data-testid="support-calc-name"><?php echo esc_html( $blueworx_q_growth['name'] ); ?></div>
			<p class="bw-calc-blurb" data-testid="support-calc-blurb"><?php echo esc_html( $blueworx_q_growth['blurb'] ); ?></p>
		</div>

		<div class="calc-field" style="display:flex;align-items:center;justify-content:space-between;gap:16px">
			<div class="bw-calc-label" style="margin:0"><?php esc_html_e( 'Effective hourly rate', 'bluegroup-project-blueworx' ); ?></div>
			<b class="bw-calc-rate" data-testid="support-calc-rate"<?php echo $blueworx_q_gbp ? ' data-bw-gbp="' . esc_attr( number_format( $blueworx_q_rate, 2, '.', '' ) ) . '" data-bw-dp="2" data-bw-suffix=" / hr"' : ''; ?>><?php echo esc_html( blueworx_public_currency_sign( $blueworx_q_growth['currency'] ) . number_format( $blueworx_q_rate, 2, '.', '' ) . ' / hr' ); ?></b>
		</div>
	</div>

<?php
// The right-hand column. The quote and what it pays belong together and are
// stacked, with the quote panel taking whatever height the questions beside it
// leave over, so the two columns finish level however long the left one gets.
?>
<div class="calc-side">
	<div class="calc-out">
		<div class="cl"><?php esc_html_e( 'Suggested Package', 'bluegroup-project-blueworx' ); ?></div>
		<div class="cv" data-testid="support-calc-price"<?php echo $blueworx_q_gbp ? ' data-bw-gbp="' . esc_attr( (int) $blueworx_q_growth['priceM'] ) . '"' : ''; ?>><?php echo esc_html( blueworx_public_currency_sign( $blueworx_q_growth['currency'] ) . number_format( (float) $blueworx_q_growth['priceM'], 0, '.', ',' ) ); ?></div>
		<div class="cp"><?php esc_html_e( 'per month', 'bluegroup-project-blueworx' ); ?></div>

		<?php if ( $blueworx_q_full ) : ?>
			<ul class="quote-lines" data-quote-lines hidden></ul>
		<?php endif; ?>

		<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn btn-brand btn-md" style="width:100%;text-decoration:none"><?php esc_html_e( 'Get this plan', 'bluegroup-project-blueworx' ); ?></a>
	</div>

	<?php if ( $blueworx_q_commission ) : ?>
		<?php
		// Never rendered on a public page — see the call in pages/support.php.
		$blueworx_q_pay = blueworx_commission_summary( blueworx_quote_to_sale( $blueworx_q_quote ) );
		?>
		<div class="quote-commission">
			<span class="quote-commission-label"><?php esc_html_e( 'This quote commission of', 'bluegroup-project-blueworx' ); ?></span>
			<b data-testid="quote-commission"><?php echo esc_html( blueworx_commission_money( $blueworx_q_pay['commission'] ) ); ?></b>
			<span class="quote-commission-note"><?php esc_html_e( 'in year one, on the first year of the sale', 'bluegroup-project-blueworx' ); ?></span>
			<?php
			// Written out part by part because the parts are not paid at the
			// same rate: hosting and ClubHouse earn 20% flat, a support package
			// 10% or 20% depending on what it is worth. One total hides which
			// of those is which.
			?>
			<span class="quote-commission-parts" data-commission-parts></span>
		</div>
	<?php endif; ?>
	</div>
</div>
