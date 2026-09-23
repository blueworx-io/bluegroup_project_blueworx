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

	register_setting(
		BLUEWORX_SITE_SETTINGS_GROUP,
		'blueworx_checkout_url',
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'blueworx_site_sanitize_link_target',
		)
	);

	register_setting(
		BLUEWORX_SITE_SETTINGS_GROUP,
		'blueworx_quote_builder_public',
		array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => 'blueworx_site_sanitize_checkbox',
		)
	);

	register_setting(
		BLUEWORX_SITE_SETTINGS_GROUP,
		'blueworx_surecart_price_ids',
		array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => 'blueworx_site_sanitize_price_ids',
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

	add_settings_field(
		'blueworx_checkout_url',
		__( 'Checkout page', 'bluegroup-project-blueworx' ),
		'blueworx_site_render_checkout_url_field',
		BLUEWORX_SITE_SETTINGS_SLUG,
		'blueworx_site_main',
		array( 'label_for' => 'blueworx_checkout_url' )
	);

	add_settings_field(
		'blueworx_surecart_price_ids',
		__( 'SureCart plan prices', 'bluegroup-project-blueworx' ),
		'blueworx_site_render_price_ids_field',
		BLUEWORX_SITE_SETTINGS_SLUG,
		'blueworx_site_main'
	);

	add_settings_field(
		'blueworx_quote_builder_public',
		__( 'Quote builder on Support', 'bluegroup-project-blueworx' ),
		'blueworx_site_render_quote_builder_field',
		BLUEWORX_SITE_SETTINGS_SLUG,
		'blueworx_site_main',
		array( 'label_for' => 'blueworx_quote_builder_public' )
	);

	add_settings_field(
		'blueworx_currency_rates',
		__( 'Exchange rates', 'bluegroup-project-blueworx' ),
		'blueworx_site_render_currency_rates_field',
		BLUEWORX_SITE_SETTINGS_SLUG,
		'blueworx_site_main'
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
 * this site ("/customer-dashboard") or on another host entirely, and rejecting either
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
 * Sanitises a checkbox. An unticked box posts nothing at all, so anything
 * that is not the box's own value is off.
 *
 * @param mixed $value Posted value.
 * @return int 1 or 0.
 */
function blueworx_site_sanitize_checkbox( $value ) {
	return ( '1' === (string) $value || 1 === $value || true === $value ) ? 1 : 0;
}

/**
 * Renders the switch for the Support page's quote builder.
 *
 * Off is a real position, not a fallback: the questions behind a build quote
 * say how we size work, and whether visitors see that is a commercial call.
 * The Sales section keeps the full calculator either way.
 *
 * @return void
 */
function blueworx_site_render_quote_builder_field() {
	$on = (bool) get_option( 'blueworx_quote_builder_public', 1 );
	?>
	<?php
	// An unticked box posts nothing, and a setting that is not posted is left
	// exactly as it was — so without this the switch could be turned on and
	// never off again.
	?>
	<input type="hidden" name="blueworx_quote_builder_public" value="0" />
	<label for="blueworx_quote_builder_public">
		<input type="checkbox" id="blueworx_quote_builder_public" name="blueworx_quote_builder_public" value="1" <?php checked( $on ); ?> />
		<?php echo esc_html__( 'Show the full quote builder on the Support page', 'bluegroup-project-blueworx' ); ?>
	</label>
	<p class="description">
		<?php echo esc_html__( 'On, visitors can size a build or a rebuild themselves — including what we allow per page and for a membership system. Off, the page shows the plain hours slider instead. Sales staff always get the full one in their dashboard.', 'bluegroup-project-blueworx' ); ?>
	</p>
	<?php
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
	<input type="text" class="regular-text" id="blueworx_client_login_url" name="blueworx_client_login_url" value="<?php echo esc_attr( $value ); ?>" placeholder="/login" />
	<p class="description">
		<?php echo esc_html__( 'Where the Client Login link in the navigation points. A full URL or a path such as /customer-dashboard. Leave it empty to use the sign-in page this plugin renders.', 'bluegroup-project-blueworx' ); ?>
	</p>
	<?php
}

/**
 * Sanitises the SureCart price-ID map.
 *
 * Rebuilt from the known plans rather than from whatever was posted, so a
 * crafted request cannot add keys.
 *
 * On the shape of an ID: SureCart's own REST routes accept any id segment, and
 * a real price ID is a UUID — `c9e06c21-7772-4d19-821a-93edc6326d54`. An earlier
 * version of this function demanded a `price_` prefix, which no SureCart ID has,
 * so every ID pasted into these fields was thrown away on save. The check here
 * is therefore about what an ID may safely contain, not about what it must look
 * like: letters, digits, hyphens and underscores, and nothing that could travel
 * into a URL as anything other than an ID.
 *
 * A rejected ID is reported rather than silently blanked. Silently blanking is
 * what made the prefix bug so expensive to find — the field simply emptied
 * itself and the pricing page carried on looking plausible.
 *
 * @param mixed $value Raw submitted value.
 * @return array Map of plan slug => array( 'm' => id, 'a' => id ).
 */
function blueworx_site_sanitize_price_ids( $value ) {
	$value    = is_array( $value ) ? $value : array();
	$clean    = array();
	$rejected = array();

	foreach ( blueworx_commerce_sellable_plans() as $plan ) {
		if ( empty( $plan['name'] ) ) {
			continue;
		}

		$slug = blueworx_commerce_plan_slug( $plan['name'] );

		foreach ( array( 'm', 'a' ) as $interval ) {
			$raw = isset( $value[ $slug ][ $interval ] ) ? trim( (string) $value[ $slug ][ $interval ] ) : '';

			if ( '' === $raw || preg_match( '/^[A-Za-z0-9_-]{6,64}$/', $raw ) ) {
				$clean[ $slug ][ $interval ] = $raw;
				continue;
			}

			$clean[ $slug ][ $interval ] = '';
			$rejected[]                  = $plan['name'];
		}
	}

	if ( $rejected ) {
		add_settings_error(
			'blueworx_surecart_price_ids',
			'blueworx_price_id_rejected',
			sprintf(
				/* translators: %s: comma-separated list of plan names. */
				__( 'These price IDs did not look like SureCart IDs and were not saved: %s. Copy the ID from the price in SureCart — it looks like c9e06c21-7772-4d19-821a-93edc6326d54.', 'bluegroup-project-blueworx' ),
				implode( ', ', array_unique( $rejected ) )
			),
			'error'
		);
	}

	return $clean;
}

/**
 * Renders the checkout page field.
 *
 * @return void
 */
function blueworx_site_render_checkout_url_field() {
	$value = (string) get_option( 'blueworx_checkout_url', '' );
	?>
	<input type="text" class="regular-text" id="blueworx_checkout_url" name="blueworx_checkout_url" value="<?php echo esc_attr( $value ); ?>" placeholder="/checkout" />
	<p class="description">
		<?php echo esc_html__( 'Where the plan buttons send a visitor to pay. Leave it empty and SureCart’s own checkout page is used, which is almost always what you want.', 'bluegroup-project-blueworx' ); ?>
		<br />
		<?php echo esc_html__( 'To send customers to their dashboard after paying, set the success URL on the SureCart checkout form itself — that setting belongs to SureCart, not here.', 'bluegroup-project-blueworx' ); ?>
	</p>
	<?php
}

/**
 * Renders the per-plan SureCart price ID fields.
 *
 * One row per plan, two IDs each. Filling a row in makes that plan show
 * SureCart's price and sends its button to checkout; leaving it empty keeps the
 * price written into the plugin and the button on the contact form.
 *
 * @return void
 */
function blueworx_site_render_price_ids_field() {
	$stored = blueworx_commerce_price_ids();
	?>
	<table class="widefat striped" style="max-width:640px">
		<thead>
			<tr>
				<th scope="col"><?php echo esc_html__( 'Plan', 'bluegroup-project-blueworx' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Monthly price ID', 'bluegroup-project-blueworx' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Annual price ID', 'bluegroup-project-blueworx' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( blueworx_commerce_sellable_plans() as $plan ) : ?>
				<?php
				if ( empty( $plan['name'] ) ) {
					continue;
				}
				$slug = blueworx_commerce_plan_slug( $plan['name'] );
				?>
				<tr>
					<th scope="row"><?php echo esc_html( $plan['name'] ); ?></th>
					<?php foreach ( array( 'm', 'a' ) as $interval ) : ?>
						<?php
						$field = 'blueworx_price_' . $slug . '_' . $interval;
						$value = isset( $stored[ $slug ][ $interval ] ) ? (string) $stored[ $slug ][ $interval ] : '';
						?>
						<td>
							<label class="screen-reader-text" for="<?php echo esc_attr( $field ); ?>">
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: plan name, 2: billing interval. */
										__( '%1$s, %2$s SureCart price ID', 'bluegroup-project-blueworx' ),
										$plan['name'],
										'm' === $interval ? __( 'monthly', 'bluegroup-project-blueworx' ) : __( 'annual', 'bluegroup-project-blueworx' )
									)
								);
								?>
							</label>
							<input type="text" id="<?php echo esc_attr( $field ); ?>" name="blueworx_surecart_price_ids[<?php echo esc_attr( $slug ); ?>][<?php echo esc_attr( $interval ); ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="price_..." class="regular-text" />
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">
		<?php echo esc_html__( 'Find these in SureCart under each product. A plan left empty keeps the price built into the plugin and sends its button to the contact form.', 'bluegroup-project-blueworx' ); ?>
		<?php if ( ! blueworx_commerce_ready() ) : ?>
			<br /><strong><?php echo esc_html__( 'SureCart is not active, so these are stored but not used yet.', 'bluegroup-project-blueworx' ); ?></strong>
		<?php endif; ?>
	</p>
	<?php
}

/**
 * Shows the exchange rates the currency switcher is using right now.
 *
 * Read-only: the figures come from the European Central Bank twice a day
 * (includes/public/currency.php) and there is nothing to type in. It is here
 * so someone checking a euro or dollar price on the site can see where the
 * number came from, and whether the live feed is working.
 *
 * @return void
 */
function blueworx_site_render_currency_rates_field() {
	$current = blueworx_currency_rates();
	$rates   = isset( $current['rates'] ) ? (array) $current['rates'] : array();
	$figures = array();

	foreach ( array_keys( blueworx_currency_fallback_rates() ) as $code ) {
		$figures[] = blueworx_public_currency_sign( $code ) . ( isset( $rates[ $code ] ) ? number_format_i18n( (float) $rates[ $code ], 4 ) : '—' );
	}
	?>
	<p id="blueworx_currency_rates">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s: the pound's value in each currency, e.g. "€1.1700 = $1.2700". */
				__( '£1 = %s', 'bluegroup-project-blueworx' ),
				implode( ' = ', $figures )
			)
		);
		?>
	</p>
	<p class="description">
		<?php if ( 'live' === $current['source'] ) : ?>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: date the rates are for. */
					__( 'European Central Bank reference rates for %s, refreshed twice a day.', 'bluegroup-project-blueworx' ),
					$current['date']
				)
			);
			?>
		<?php else : ?>
			<strong><?php echo esc_html__( 'The live rate feed has not been reached yet, so the fixed launch rates are in use.', 'bluegroup-project-blueworx' ); ?></strong>
		<?php endif; ?>
	</p>
	<?php
}

/**
 * Adds the screen under Settings.
 *
 * Under Settings rather than as a top-level menu: this is a handful of fields,
 * and a top-level item for a handful of fields is how admin menus become
 * unusable.
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
