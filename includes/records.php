<?php
/**
 * Client records — websites and referrals (#100, #101).
 *
 * Both portal sections that were left out of #30 were left out for the same
 * reason: there was nowhere to read the answer from. The design assumed a
 * hosting monitor behind the Websites panel and a referral scheme behind the
 * Partner one, and the site has neither.
 *
 * This is the smallest honest thing that makes both real — a register. Two
 * private post types the team fills in from wp-admin, each row attached to the
 * client (or partner) it belongs to. Nothing here is inferred, generated or
 * estimated: a client's portal shows exactly the rows somebody entered for
 * them, and shows nothing when there are none.
 *
 * What is deliberately NOT here, and why:
 *
 * - **Uptime, monthly visits and core web vitals.** The design shows all three
 *   per site. Each is a live measurement from a monitoring service, and there
 *   is no such service connected. A hand-typed uptime figure is worse than no
 *   figure, because it looks measured. When a monitor is wired up it reads
 *   against `_bw_site_url`, which is why that field is a real URL rather than
 *   a label.
 * - **The site switcher.** It scopes the whole portal to one site, and the
 *   portal's other data — subscriptions, invoices, orders — is account-wide,
 *   not per site. A switcher that changes nothing on the page is a control
 *   that lies, which is the fault #77 was about. The Websites section lists
 *   them instead.
 *
 * Post types rather than user meta: the team needs to add, edit, find and sort
 * these from wp-admin, and a post type comes with all of that already built and
 * already permission-checked. `public => false` with `show_ui => true` keeps
 * them out of the front end entirely — they have no permalink, no archive and
 * no place in a sitemap.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post type holding one client website.
 */
const BLUEWORX_SITE_CPT = 'bw_client_site';

/**
 * Post type holding one referral.
 */
const BLUEWORX_REFERRAL_CPT = 'bw_referral';

/**
 * Registers the two record types.
 *
 * `capability_type => page` with `map_meta_cap` so editing a client's records
 * needs the same rights as editing a page — an Editor can maintain them, a
 * Subscriber (which is what a client is) cannot see them in wp-admin at all.
 *
 * @return void
 */
function blueworx_records_register() {
	$shared = array(
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'has_archive'         => false,
		'rewrite'             => false,
		'query_var'           => false,
		'capability_type'     => 'page',
		'map_meta_cap'        => true,
		'menu_position'       => 26,
		'supports'            => array( 'title' ),
	);

	register_post_type(
		BLUEWORX_SITE_CPT,
		array_merge(
			$shared,
			array(
				'labels'      => array(
					'name'               => __( 'Client Websites', 'bluegroup-project-blueworx' ),
					'singular_name'      => __( 'Client Website', 'bluegroup-project-blueworx' ),
					'add_new_item'       => __( 'Add a client website', 'bluegroup-project-blueworx' ),
					'edit_item'          => __( 'Edit client website', 'bluegroup-project-blueworx' ),
					'search_items'       => __( 'Search client websites', 'bluegroup-project-blueworx' ),
					'not_found'          => __( 'No client websites recorded yet.', 'bluegroup-project-blueworx' ),
					'not_found_in_trash' => __( 'No client websites in the bin.', 'bluegroup-project-blueworx' ),
					'menu_name'          => __( 'Client Websites', 'bluegroup-project-blueworx' ),
				),
				'description' => __( 'The websites we look after, each attached to the client whose portal shows it.', 'bluegroup-project-blueworx' ),
				'menu_icon'   => 'dashicons-admin-site-alt3',
			)
		)
	);

	register_post_type(
		BLUEWORX_REFERRAL_CPT,
		array_merge(
			$shared,
			array(
				'labels'      => array(
					'name'               => __( 'Referrals', 'bluegroup-project-blueworx' ),
					'singular_name'      => __( 'Referral', 'bluegroup-project-blueworx' ),
					'add_new_item'       => __( 'Add a referral', 'bluegroup-project-blueworx' ),
					'edit_item'          => __( 'Edit referral', 'bluegroup-project-blueworx' ),
					'search_items'       => __( 'Search referrals', 'bluegroup-project-blueworx' ),
					'not_found'          => __( 'No referrals recorded yet.', 'bluegroup-project-blueworx' ),
					'not_found_in_trash' => __( 'No referrals in the bin.', 'bluegroup-project-blueworx' ),
					'menu_name'          => __( 'Referrals', 'bluegroup-project-blueworx' ),
				),
				'description' => __( 'Businesses a partner sent us, and what has been paid on each.', 'bluegroup-project-blueworx' ),
				'menu_icon'   => 'dashicons-groups',
				'menu_position' => 27,
			)
		)
	);
}
add_action( 'init', 'blueworx_records_register' );

/**
 * The states a recorded website can be in.
 *
 * @return array Key => label.
 */
function blueworx_site_statuses() {
	return array(
		'live'     => __( 'Live', 'bluegroup-project-blueworx' ),
		'building' => __( 'In build', 'bluegroup-project-blueworx' ),
		'paused'   => __( 'Paused', 'bluegroup-project-blueworx' ),
	);
}

/**
 * The states a referral moves through.
 *
 * Deliberately few, and each one a fact rather than a forecast: a partner
 * should be able to tell what they are owed without interpreting a pipeline
 * stage.
 *
 * @return array Key => label.
 */
function blueworx_referral_statuses() {
	return array(
		'enquiry'  => __( 'Enquiry', 'bluegroup-project-blueworx' ),
		'client'   => __( 'Became a client', 'bluegroup-project-blueworx' ),
		'paid'     => __( 'Commission paid', 'bluegroup-project-blueworx' ),
		'declined' => __( 'Did not go ahead', 'bluegroup-project-blueworx' ),
	);
}

/**
 * The websites recorded against a client.
 *
 * @param int $user_id Client user ID. Defaults to the current user.
 * @return array List of array( id, name, url, host, status, status_label ).
 */
function blueworx_client_sites( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();

	if ( ! $user_id ) {
		return array();
	}

	$posts = get_posts(
		array(
			'post_type'      => BLUEWORX_SITE_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			// A meta_query on an indexed integer, not a scan of every record:
			// this runs on a page load for every client.
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- the register is small and this is the only way to scope rows to their owner.
				array(
					'key'   => '_bw_site_client',
					'value' => $user_id,
				),
			),
		)
	);

	$statuses = blueworx_site_statuses();
	$rows     = array();

	foreach ( $posts as $post ) {
		$status = (string) get_post_meta( $post->ID, '_bw_site_status', true );

		$rows[] = array(
			'id'           => (int) $post->ID,
			'name'         => (string) get_the_title( $post ),
			'url'          => (string) get_post_meta( $post->ID, '_bw_site_url', true ),
			'host'         => (string) get_post_meta( $post->ID, '_bw_site_hosting', true ),
			'status'       => $status,
			'status_label' => isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['live'],
		);
	}

	return $rows;
}

/**
 * The referrals recorded against a partner.
 *
 * @param int $user_id Partner user ID. Defaults to the current user.
 * @return array List of array( id, name, date, status, status_label, amount ).
 */
function blueworx_partner_referrals( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();

	if ( ! $user_id ) {
		return array();
	}

	$posts = get_posts(
		array(
			'post_type'      => BLUEWORX_REFERRAL_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- as above.
				array(
					'key'   => '_bw_referral_partner',
					'value' => $user_id,
				),
			),
		)
	);

	$statuses = blueworx_referral_statuses();
	$rows     = array();

	foreach ( $posts as $post ) {
		$status = (string) get_post_meta( $post->ID, '_bw_referral_status', true );

		$rows[] = array(
			'id'           => (int) $post->ID,
			'name'         => (string) get_the_title( $post ),
			'date'         => (string) get_the_date( '', $post ),
			'status'       => $status,
			'status_label' => isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['enquiry'],
			// Stored as text, with its currency symbol, exactly as typed. Not a
			// number: this plugin does not know the partner's currency, and a
			// bare "250" beside a pound sign we assumed is a number somebody
			// would act on.
			'amount'       => (string) get_post_meta( $post->ID, '_bw_referral_amount', true ),
		);
	}

	return $rows;
}

/**
 * Whether a user is on the partner scheme at all.
 *
 * Having referrals is what makes somebody a partner — there is no separate flag
 * to fall out of step with the records. A partner whose first referral has not
 * been entered yet sees the section's empty state, which is correct: we have
 * nothing to show them.
 *
 * @param int $user_id User ID. Defaults to the current user.
 * @return bool
 */
function blueworx_is_partner( $user_id = 0 ) {
	return (bool) blueworx_partner_referrals( $user_id );
}
