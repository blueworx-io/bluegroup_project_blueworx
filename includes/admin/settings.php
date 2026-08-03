<?php
/**
 * Admin — the plugin's settings screen.
 *
 * Two things about the site were already configurable through options and
 * filters: the shortcode the Contact page renders, and where the nav's Client
 * Login link points. Neither had a screen behind it, which made them
 * configurable only for somebody willing to run WP-CLI or write a filter.
 *
 * That is not an abstract gap. The Contact page has been showing a grey
 * placeholder rather than a form (#27) on a site that already has a published
 * contact form ready to use — the only missing piece was somewhere to paste its
 * shortcode. And the Client Login link (#28) has to be repointed the day
 * SureDash is removed, which should not need a plugin release.
 *
 * Loaded only in wp-admin, from the main plugin file.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BLUEWORX_SITE_SETTINGS_GROUP = 'blueworx_site_settings';
const BLUEWORX_SITE_SETTINGS_SLUG  = 'bluegroup-project-blueworx';

/**
 * Registers the settings, their sanitisers, and the fields that edit them.
 *
 * @return void
 */
function blueworx_site_register_settings() {
	register_setting(
		BLUEWORX_SITE_SETTINGS_GROUP,
		'blueworx_contact_form_shortcode',
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'blueworx_site_sanitize_shortcode',
		)
	);

	register_setting(
		BLUEWORX_SITE_SETTINGS_GROUP,
		'blueworx_client_login_url',
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'blueworx_site_sanitize_link_target',
		)
	);

	add_settings_section(
		'blueworx_site_main',
		'',
		'__return_false',
		BLUEWORX_SITE_SETTINGS_SLUG
	);

	add_settings_field(
		'blueworx_contact_form_shortcode',
		__( 'Contact form shortcode', 'bluegroup-project-blueworx' ),
		'blueworx_site_render_shortcode_field',
		BLUEWORX_SITE_SETTINGS_SLUG,
		'blueworx_site_main',
		array( 'label_for' => 'blueworx_contact_form_shortcode' )
	);

	add_settings_field(
		'blueworx_client_login_url',
		__( 'Client Login link', 'bluegroup-project-blueworx' ),
		'blueworx_site_render_login_url_field',
		BLUEWORX_SITE_SETTINGS_SLUG,
		'blueworx_site_main',
		array( 'label_for' => 'blueworx_client_login_url' )
	);
}
add_action( 'admin_init', 'blueworx_site_register_settings' );

/**
 * Sanitises the contact form shortcode.
 *
 * A field that renders whatever it is given is a shortcode field; a field that
 * renders whatever HTML it is given is a way to put a script tag on every
 * visitor's Contact page. Only an administrator can reach this screen, but
 * "only an admin can do it" is not a reason to store markup unchecked — an
 * admin session is exactly what a CSRF or a compromised account gives an
 * attacker.
 *
 * wp_strip_all_tags() removes tags and, for script and style, their contents
 * too — while leaving shortcode brackets, attributes and quotes alone, which is
 * everything a shortcode actually needs.
 *
 * @param string $value Raw submitted value.
 * @return string Sanitised value.
 */
function blueworx_site_sanitize_shortcode( $value ) {
	return trim( wp_strip_all_tags( (string) $value ) );
}

/**
 * Sanitises a link target that may be an absolute URL or a site-relative path.
 *
 * Both forms are legitimate here: the client dashboard may end up as a page on
 * this site ("/dashboard") or on another host entirely, and rejecting either
 * would just push somebody back to editing PHP.
 *
 * @param string $value Raw submitted value.
 * @return string Sanitised value.
 */
function blueworx_site_sanitize_link_target( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	if ( wp_parse_url( $value, PHP_URL_SCHEME ) ) {
		// esc_url_raw() also enforces the allowed protocol list, so a
		// javascript: target is refused rather than stored.
		return (string) esc_url_raw( $value );
	}

	// A path. Keep the leading slash, drop anything that is not part of one.
	return '/' . ltrim( sanitize_text_field( $value ), '/' );
}

/**
 * Renders the contact form shortcode field.
 *
 * @return void
 */
function blueworx_site_render_shortcode_field() {
	$value = (string) get_option( 'blueworx_contact_form_shortcode', '' );
	?>
	<input type="text" class="regular-text" id="blueworx_contact_form_shortcode" name="blueworx_contact_form_shortcode" value="<?php echo esc_attr( $value ); ?>" placeholder="[sureforms id=&quot;389&quot;]" />
	<p class="description">
		<?php echo esc_html__( 'The shortcode for the form shown on the Contact page. Leave it empty and the page shows a placeholder instead of a form.', 'bluegroup-project-blueworx' ); ?>
	</p>
	<?php
}

/**
 * Renders the Client Login link field.
 *
 * @return void
 */
function blueworx_site_render_login_url_field() {
	$value = (string) get_option( 'blueworx_client_login_url', '' );
	?>
	<input type="text" class="regular-text" id="blueworx_client_login_url" name="blueworx_client_login_url" value="<?php echo esc_attr( $value ); ?>" placeholder="/portal" />
	<p class="description">
		<?php echo esc_html__( 'Where the Client Login link in the navigation points. A full URL or a path such as /dashboard. Leave it empty to use /portal.', 'bluegroup-project-blueworx' ); ?>
	</p>
	<?php
}

/**
 * Adds the screen under Settings.
 *
 * Under Settings rather than as a top-level menu: this is two fields, and a
 * top-level item for two fields is how admin menus become unusable.
 *
 * @return void
 */
function blueworx_site_add_settings_page() {
	add_options_page(
		__( 'BlueWorx Marketing Site', 'bluegroup-project-blueworx' ),
		__( 'BlueWorx Site', 'bluegroup-project-blueworx' ),
		'manage_options',
		BLUEWORX_SITE_SETTINGS_SLUG,
		'blueworx_site_render_settings_page'
	);
}
add_action( 'admin_menu', 'blueworx_site_add_settings_page' );

/**
 * Renders the settings screen.
 *
 * @return void
 */
function blueworx_site_render_settings_page() {
	// add_options_page() already gates the menu entry on this capability, but
	// the callback is reachable by URL, so it is checked here too.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to change these settings.', 'bluegroup-project-blueworx' ) );
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'BlueWorx Marketing Site', 'bluegroup-project-blueworx' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( BLUEWORX_SITE_SETTINGS_GROUP );
			do_settings_sections( BLUEWORX_SITE_SETTINGS_SLUG );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
