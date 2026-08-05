<?php
/**
 * A table of client records, or the right message when there are none.
 *
 * Shared by all three dashboard sections. It takes the result array the data
 * layer returns — which reports whether the read succeeded as well as what it
 * found — so "we could not load this" and "you have none" are never confused
 * for each other. Telling a paying client they have no subscriptions because an
 * API call failed is the failure worth designing against here.
 *
 * $vars:
 * - result  (array, required)  From blueworx_account_subscriptions() etc.
 * - columns (array, required)  Row key => column heading.
 * - empty   (string, required) Shown when the read worked and found nothing.
 * - cta     (string, optional) Link text for the empty state.
 * - href    (string, optional) Link target for the empty state.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_tbl_result  = isset( $result ) && is_array( $result ) ? $result : array( 'ok' => false, 'rows' => array() );
$blueworx_tbl_columns = isset( $columns ) && is_array( $columns ) ? $columns : array();
$blueworx_tbl_rows    = isset( $blueworx_tbl_result['rows'] ) ? (array) $blueworx_tbl_result['rows'] : array();

if ( empty( $blueworx_tbl_result['ok'] ) ) :
	?>
	<div class="dash-empty dash-error" role="status">
		<p><?php esc_html_e( 'We could not load this just now. It is us, not your account — please try again in a moment.', 'bluegroup-project-blueworx' ); ?></p>
		<a class="btn btn-brand btn-md" href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php esc_html_e( 'Tell us if it keeps happening', 'bluegroup-project-blueworx' ); ?></a>
	</div>
	<?php
elseif ( empty( $blueworx_tbl_rows ) ) :
	blueworx_public_part(
		'parts/dash-empty.php',
		array(
			'message' => isset( $empty ) ? $empty : '',
			'cta'     => isset( $cta ) ? $cta : '',
			'href'    => isset( $href ) ? $href : '',
		)
	);
else :
	?>
	<div class="dash-panel dash-table-wrap">
		<table class="dash-table">
			<thead>
				<tr>
					<?php foreach ( $blueworx_tbl_columns as $blueworx_tbl_heading ) : ?>
						<th scope="col">
							<?php
							// A column of links needs no visible heading, but a
							// screen reader still announces the header cell for
							// every cell under it — so an empty one would be
							// announced as nothing at all.
							if ( '' === $blueworx_tbl_heading ) {
								echo '<span class="bw-sr-only">' . esc_html__( 'Actions', 'bluegroup-project-blueworx' ) . '</span>';
							} else {
								echo esc_html( $blueworx_tbl_heading );
							}
							?>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $blueworx_tbl_rows as $blueworx_tbl_row ) : ?>
					<tr>
						<?php foreach ( array_keys( $blueworx_tbl_columns ) as $blueworx_tbl_key ) : ?>
							<td data-col="<?php echo esc_attr( $blueworx_tbl_key ); ?>">
								<?php
								$blueworx_tbl_value = isset( $blueworx_tbl_row[ $blueworx_tbl_key ] ) ? $blueworx_tbl_row[ $blueworx_tbl_key ] : '';

								if ( 'status' === $blueworx_tbl_key ) {
									// A row may carry its own wording. The
									// mapping below is SureCart's vocabulary,
									// and the referral register (#100) has its
									// own — "Became a client" is not a status
									// SureCart has ever heard of, and running
									// it through that map turns it into
									// something else.
									$blueworx_tbl_label = isset( $blueworx_tbl_row['status_label'] ) && '' !== $blueworx_tbl_row['status_label']
										? $blueworx_tbl_row['status_label']
										: blueworx_account_status_label( $blueworx_tbl_value );

									printf(
										'<span class="dash-status dash-status-%1$s">%2$s</span>',
										esc_attr( sanitize_html_class( $blueworx_tbl_value ) ),
										esc_html( $blueworx_tbl_label )
									);
								} elseif ( 'pay' === $blueworx_tbl_key ) {
									// Only an unpaid invoice has somewhere to
									// go, so a paid one gets a blank cell
									// rather than a disabled-looking link.
									if ( '' !== $blueworx_tbl_value ) {
										printf(
											'<a class="dash-pay" href="%1$s" rel="noopener">%2$s</a>',
											esc_url( $blueworx_tbl_value ),
											esc_html__( 'Pay now', 'bluegroup-project-blueworx' )
										);
									}
								} elseif ( '' === $blueworx_tbl_value ) {
									echo '<span class="dash-none">&mdash;</span>';
								} else {
									echo esc_html( $blueworx_tbl_value );
								}
								?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
endif;
