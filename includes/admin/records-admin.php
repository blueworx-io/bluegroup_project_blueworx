<?php
/**
 * wp-admin for the client website and referral registers (#100, #101).
 *
 * A register nobody can fill in is not a register, so this is the other half of
 * includes/records.php: a field box on each record, and list columns that show
 * the fields at a glance rather than a screen of identical titles.
 *
 * The client/partner field is a user dropdown rather than a free-text name.
 * A typed name cannot be matched to an account, and a record attached to
 * nobody is a row that will never appear in the portal it was entered for —
 * which is the failure that looks like the feature is broken.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the field boxes to both record types.
 *
 * @return void
 */
function blueworx_records_meta_boxes() {
	add_meta_box(
		'blueworx-site-fields',
		__( 'Website details', 'bluegroup-project-blueworx' ),
		'blueworx_records_site_box',
		BLUEWORX_SITE_CPT,
		'normal',
		'high'
	);

	add_meta_box(
		'blueworx-referral-fields',
		__( 'Referral details', 'bluegroup-project-blueworx' ),
		'blueworx_records_referral_box',
		BLUEWORX_REFERRAL_CPT,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'blueworx_records_meta_boxes' );

/**
 * A dropdown of the people a record can belong to.
 *
 * @param string $name     Field name.
 * @param int    $selected Currently attached user ID.
 * @return void
 */
function blueworx_records_user_select( $name, $selected ) {
	wp_dropdown_users(
		array(
			'name'              => $name,
			'selected'          => $selected,
			'show_option_none'  => __( '— nobody yet —', 'bluegroup-project-blueworx' ),
			'option_none_value' => 0,
			'show'              => 'display_name_with_login',
			// Every role, not just subscribers: clients are created by whatever
			// route the site uses, and filtering by role here silently hides
			// the person somebody is trying to attach the record to.
			'who'               => '',
		)
	);
}

/**
 * The website record's fields.
 *
 * @param WP_Post $post The record.
 * @return void
 */
function blueworx_records_site_box( $post ) {
	wp_nonce_field( 'blueworx_records_save', 'blueworx_records_nonce' );

	$client   = (int) get_post_meta( $post->ID, '_bw_site_client', true );
	$url      = (string) get_post_meta( $post->ID, '_bw_site_url', true );
	$hosting  = (string) get_post_meta( $post->ID, '_bw_site_hosting', true );
	$status   = (string) get_post_meta( $post->ID, '_bw_site_status', true );
	$statuses = blueworx_site_statuses();
	?>
	<p>
		<label for="bw-site-client"><strong><?php esc_html_e( 'Client', 'bluegroup-project-blueworx' ); ?></strong></label><br />
		<?php blueworx_records_user_select( 'bw_site_client', $client ); ?>
		<span class="description"><?php esc_html_e( 'Whose portal this website appears in. Attached to nobody, it appears in no portal.', 'bluegroup-project-blueworx' ); ?></span>
	</p>
	<p>
		<label for="bw-site-url"><strong><?php esc_html_e( 'Address', 'bluegroup-project-blueworx' ); ?></strong></label><br />
		<input type="url" class="large-text code" id="bw-site-url" name="bw_site_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://example.com" />
	</p>
	<p>
		<label for="bw-site-hosting"><strong><?php esc_html_e( 'Hosting', 'bluegroup-project-blueworx' ); ?></strong></label><br />
		<input type="text" class="large-text" id="bw-site-hosting" name="bw_site_hosting" value="<?php echo esc_attr( $hosting ); ?>" />
		<span class="description"><?php esc_html_e( 'Shown to the client as-is, so write it the way you would say it to them.', 'bluegroup-project-blueworx' ); ?></span>
	</p>
	<p>
		<label for="bw-site-status"><strong><?php esc_html_e( 'Status', 'bluegroup-project-blueworx' ); ?></strong></label><br />
		<select id="bw-site-status" name="bw_site_status">
			<?php foreach ( $statuses as $blueworx_key => $blueworx_label ) : ?>
				<option value="<?php echo esc_attr( $blueworx_key ); ?>" <?php selected( $status, $blueworx_key ); ?>><?php echo esc_html( $blueworx_label ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<?php
}

/**
 * The referral record's fields.
 *
 * @param WP_Post $post The record.
 * @return void
 */
function blueworx_records_referral_box( $post ) {
	wp_nonce_field( 'blueworx_records_save', 'blueworx_records_nonce' );

	$partner  = (int) get_post_meta( $post->ID, '_bw_referral_partner', true );
	$status   = (string) get_post_meta( $post->ID, '_bw_referral_status', true );
	$amount   = (string) get_post_meta( $post->ID, '_bw_referral_amount', true );
	$statuses = blueworx_referral_statuses();
	?>
	<p>
		<label for="bw-referral-partner"><strong><?php esc_html_e( 'Partner', 'bluegroup-project-blueworx' ); ?></strong></label><br />
		<?php blueworx_records_user_select( 'bw_referral_partner', $partner ); ?>
		<span class="description"><?php esc_html_e( 'Who sent us this business. They see it in their portal; nobody else does.', 'bluegroup-project-blueworx' ); ?></span>
	</p>
	<p>
		<label for="bw-referral-status"><strong><?php esc_html_e( 'Status', 'bluegroup-project-blueworx' ); ?></strong></label><br />
		<select id="bw-referral-status" name="bw_referral_status">
			<?php foreach ( $statuses as $blueworx_key => $blueworx_label ) : ?>
				<option value="<?php echo esc_attr( $blueworx_key ); ?>" <?php selected( $status, $blueworx_key ); ?>><?php echo esc_html( $blueworx_label ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="bw-referral-amount"><strong><?php esc_html_e( 'Commission', 'bluegroup-project-blueworx' ); ?></strong></label><br />
		<input type="text" class="regular-text" id="bw-referral-amount" name="bw_referral_amount" value="<?php echo esc_attr( $amount ); ?>" placeholder="£250" />
		<span class="description"><?php esc_html_e( 'Include the currency symbol — this is shown to the partner exactly as typed.', 'bluegroup-project-blueworx' ); ?></span>
	</p>
	<?php
}

/**
 * Saves both record types' fields.
 *
 * One handler for both because the checks are identical and duplicating them
 * is how one of the two ends up missing a capability check.
 *
 * @param int     $post_id Record ID.
 * @param WP_Post $post    The record.
 * @return void
 */
function blueworx_records_save( $post_id, $post ) {
	if ( ! in_array( $post->post_type, array( BLUEWORX_SITE_CPT, BLUEWORX_REFERRAL_CPT ), true ) ) {
		return;
	}

	// An autosave has no field values in it, so writing here would blank every
	// field the moment WordPress autosaved the record.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$nonce = isset( $_POST['blueworx_records_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['blueworx_records_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'blueworx_records_save' ) ) {
		return;
	}

	// These records are other people's business details, so the bar is the
	// same as editing the record itself — checked against THIS record, not
	// against the post type in general.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( BLUEWORX_SITE_CPT === $post->post_type ) {
		$statuses = blueworx_site_statuses();
		$status   = isset( $_POST['bw_site_status'] ) ? sanitize_key( wp_unslash( $_POST['bw_site_status'] ) ) : '';

		update_post_meta( $post_id, '_bw_site_client', isset( $_POST['bw_site_client'] ) ? absint( wp_unslash( $_POST['bw_site_client'] ) ) : 0 );
		// esc_url_raw, not sanitize_text_field: this is rendered as an href in
		// the client's portal, so a javascript: value must be stripped here
		// rather than relied on being caught at output.
		update_post_meta( $post_id, '_bw_site_url', isset( $_POST['bw_site_url'] ) ? esc_url_raw( wp_unslash( $_POST['bw_site_url'] ) ) : '' );
		update_post_meta( $post_id, '_bw_site_hosting', isset( $_POST['bw_site_hosting'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_site_hosting'] ) ) : '' );
		update_post_meta( $post_id, '_bw_site_status', isset( $statuses[ $status ] ) ? $status : 'live' );

		return;
	}

	$statuses = blueworx_referral_statuses();
	$status   = isset( $_POST['bw_referral_status'] ) ? sanitize_key( wp_unslash( $_POST['bw_referral_status'] ) ) : '';

	update_post_meta( $post_id, '_bw_referral_partner', isset( $_POST['bw_referral_partner'] ) ? absint( wp_unslash( $_POST['bw_referral_partner'] ) ) : 0 );
	update_post_meta( $post_id, '_bw_referral_status', isset( $statuses[ $status ] ) ? $status : 'enquiry' );
	update_post_meta( $post_id, '_bw_referral_amount', isset( $_POST['bw_referral_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['bw_referral_amount'] ) ) : '' );
}
add_action( 'save_post', 'blueworx_records_save', 10, 2 );

/**
 * Columns for the website register's list table.
 *
 * @param array $columns Default columns.
 * @return array
 */
function blueworx_records_site_columns( $columns ) {
	return array(
		'cb'         => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'      => __( 'Website', 'bluegroup-project-blueworx' ),
		'bw_client'  => __( 'Client', 'bluegroup-project-blueworx' ),
		'bw_url'     => __( 'Address', 'bluegroup-project-blueworx' ),
		'bw_status'  => __( 'Status', 'bluegroup-project-blueworx' ),
	);
}
add_filter( 'manage_' . BLUEWORX_SITE_CPT . '_posts_columns', 'blueworx_records_site_columns' );

/**
 * Columns for the referral register's list table.
 *
 * @param array $columns Default columns.
 * @return array
 */
function blueworx_records_referral_columns( $columns ) {
	return array(
		'cb'        => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'     => __( 'Referred business', 'bluegroup-project-blueworx' ),
		'bw_client' => __( 'Partner', 'bluegroup-project-blueworx' ),
		'bw_status' => __( 'Status', 'bluegroup-project-blueworx' ),
		'bw_amount' => __( 'Commission', 'bluegroup-project-blueworx' ),
		'date'      => __( 'Added', 'bluegroup-project-blueworx' ),
	);
}
add_filter( 'manage_' . BLUEWORX_REFERRAL_CPT . '_posts_columns', 'blueworx_records_referral_columns' );

/**
 * Fills the custom columns on both list tables.
 *
 * @param string $column  Column key.
 * @param int    $post_id Record ID.
 * @return void
 */
function blueworx_records_column( $column, $post_id ) {
	$type = get_post_type( $post_id );
	$site = ( BLUEWORX_SITE_CPT === $type );

	switch ( $column ) {
		case 'bw_client':
			$user_id = (int) get_post_meta( $post_id, $site ? '_bw_site_client' : '_bw_referral_partner', true );
			$user    = $user_id ? get_userdata( $user_id ) : null;

			// Said out loud rather than left blank: an unattached record shows
			// in nobody's portal, and a blank cell reads as "not filled in yet"
			// rather than "this is not working".
			echo $user
				? esc_html( $user->display_name )
				: '<em>' . esc_html__( 'Nobody — not shown in any portal', 'bluegroup-project-blueworx' ) . '</em>';
			break;

		case 'bw_url':
			$url = (string) get_post_meta( $post_id, '_bw_site_url', true );
			echo $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a>' : '—';
			break;

		case 'bw_status':
			$statuses = $site ? blueworx_site_statuses() : blueworx_referral_statuses();
			$status   = (string) get_post_meta( $post_id, $site ? '_bw_site_status' : '_bw_referral_status', true );
			echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : '—' );
			break;

		case 'bw_amount':
			$amount = (string) get_post_meta( $post_id, '_bw_referral_amount', true );
			echo esc_html( '' === $amount ? '—' : $amount );
			break;
	}
}
add_action( 'manage_posts_custom_column', 'blueworx_records_column', 10, 2 );
