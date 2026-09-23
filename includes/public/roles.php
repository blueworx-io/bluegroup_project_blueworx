<?php
/**
 * Public front-end layer — the Sales Staff role.
 *
 * The client area is for clients. Salespeople are the one group who need a
 * page in it that is about us rather than about them — what a sale pays —
 * so they get a role of their own rather than an administrator account,
 * which would hand a salesperson the whole site to run.
 *
 * The role is a client account plus one capability. The capability, not the
 * role, is what the Sales section is gated on: an administrator has it without
 * being given the role, and a future second sales role (a manager, say) would
 * only have to grant the same capability rather than be named in a list
 * somewhere that nobody remembers to update.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The role a salesperson is given, and the capability it carries.
 */
const BLUEWORX_SALES_ROLE = 'blueworx_sales';
const BLUEWORX_SALES_CAP  = 'blueworx_view_sales';

/**
 * Creates the Sales Staff role and grants its capability to administrators.
 *
 * Idempotent, and deliberately additive: add_role() does nothing when the role
 * already exists, so an administrator who has adjusted its capabilities keeps
 * those adjustments across an update rather than having them reset by every
 * release.
 *
 * Roles live in the database, not in code — a role added here survives the
 * plugin being deactivated, and the users holding it keep it. Removing it is
 * uninstall.php's job.
 *
 * @return void
 */
function blueworx_roles_install() {
	// A salesperson is a client who can also see the Sales section: the same
	// capabilities a Subscriber has, read from the role itself rather than
	// written out here, so a site that has changed what a Subscriber may do
	// does not end up with a sales role that quietly does more.
	$subscriber = get_role( 'subscriber' );
	$caps       = ( $subscriber && is_array( $subscriber->capabilities ) )
		? $subscriber->capabilities
		: array( 'read' => true );

	$caps[ BLUEWORX_SALES_CAP ] = true;

	add_role(
		BLUEWORX_SALES_ROLE,
		__( 'BlueWorx: Sales Staff', 'bluegroup-project-blueworx' ),
		$caps
	);

	// Administrators get the capability rather than the role. Somebody who can
	// already read every order and every price should not have to be made a
	// salesperson to open the calculator.
	$admin = get_role( 'administrator' );

	if ( $admin && ! $admin->has_cap( BLUEWORX_SALES_CAP ) ) {
		$admin->add_cap( BLUEWORX_SALES_CAP );
	}
}

/**
 * Installs the role once per release.
 *
 * Priority 4, so it runs before the pages are installed at 5 — a section gated
 * on the capability is decided after the capability exists, not during the one
 * request where it does not.
 *
 * @return void
 */
function blueworx_roles_maybe_install() {
	if ( get_option( 'blueworx_roles_version' ) === BLUEWORX_SITE_VERSION ) {
		return;
	}

	blueworx_roles_install();

	update_option( 'blueworx_roles_version', BLUEWORX_SITE_VERSION );
}
add_action( 'init', 'blueworx_roles_maybe_install', 4 );

/**
 * Whether the current user may see the Sales section.
 *
 * `manage_options` is checked as well as the capability so that an install
 * upgrading from a release without the role — where no administrator has been
 * granted anything yet — still shows its administrators the section.
 *
 * @return bool
 */
function blueworx_user_can_sell() {
	return current_user_can( BLUEWORX_SALES_CAP ) || current_user_can( 'manage_options' );
}
