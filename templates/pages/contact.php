<?php
/**
 * Contact page template.
 *
 * Ported from app/contact/page.tsx's five sections, in source order: a
 * centered tech-hero (780px), the contact grid (form column + illustration),
 * a dark contact-cards band (dashboard / portfolio / email), a static FAQ
 * section, and testimonials.
 *
 * The form column renders the plugin's own enquiry form
 * (templates/parts/contact-form.php, handled by includes/public/contact-form.php)
 * unless a third-party form has been configured: then it renders the
 * shortcode named by the `blueworx_contact_form_shortcode` option
 * (filterable), and nothing else — do_shortcode() is called on that single
 * configured value, NOT on arbitrary input, so it can never be coerced into
 * running some other shortcode.
 *
 * The FAQ list is a Plan 3 interactive accordion; until then it renders as
 * native <details> so it is fully functional with no JavaScript.
 *
 * The <main><div> wrapper is required, not stylistic: globals.css targets
 * `main > div > .sec:last-child` to zero the final section's bottom padding.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_contact_cards = array(
	array(
		'icon'  => 'users',
		'title' => __( 'Already a customer?', 'bluegroup-project-blueworx' ),
		'sub'   => __( 'Sign in to raise a request or check on your site.', 'bluegroup-project-blueworx' ),
		'link'  => __( 'Go to your dashboard', 'bluegroup-project-blueworx' ),
		'href'  => blueworx_account_url(),
	),
	array(
		'icon'  => 'sparkles',
		'title' => __( 'See what we build', 'bluegroup-project-blueworx' ),
		'sub'   => __( 'Live client sites we design, host and support.', 'bluegroup-project-blueworx' ),
		'link'  => __( 'View our portfolio', 'bluegroup-project-blueworx' ),
		'href'  => home_url( '/portfolio' ),
	),
	array(
		'icon'  => 'mail',
		'title' => __( 'Email us here', 'bluegroup-project-blueworx' ),
		'sub'   => __( 'Let us know how we can help.', 'bluegroup-project-blueworx' ),
		'link'  => blueworx_contact_email(),
		'href'  => 'mailto:' . blueworx_contact_email(),
	),
);

/**
 * The single shortcode the contact form column renders.
 *
 * Empty by default. Set the `blueworx_contact_form_shortcode` option, or hook
 * this filter, to the exact shortcode of your form plugin, e.g.
 * `[sureforms id="12"]`. Only this one configured value is ever passed to
 * do_shortcode().
 */
$blueworx_contact_form_shortcode = (string) apply_filters(
	'blueworx_contact_form_shortcode',
	(string) get_option( 'blueworx_contact_form_shortcode', '' )
);

blueworx_public_document_open( array( 'body_class' => 'bw-contact' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main id="content" tabindex="-1">
	<div>
		<?php
		blueworx_public_part(
			'parts/tech-hero.php',
			array(
				'badge'           => __( 'Contact Us', 'bluegroup-project-blueworx' ),
				'title'           => __( 'Start Your Conversation With BlueWorx', 'bluegroup-project-blueworx' ),
				'title_highlight' => __( 'With BlueWorx', 'bluegroup-project-blueworx' ),
				'lead'            => __( 'Tell us where your digital presence is holding you back and we&rsquo;ll show you exactly how to fix it.', 'bluegroup-project-blueworx' ),
				'max_width'       => 780,
				'meta'            => array(
					__( 'reply within 1 business day', 'bluegroup-project-blueworx' ),
					__( 'no obligation', 'bluegroup-project-blueworx' ),
				),
			)
		);
		?>

		<section style="padding:56px 0 0">
			<div class="contact-grid">
				<div class="contact-form">
					<?php
					if ( '' !== trim( $blueworx_contact_form_shortcode ) ) {
						// Only the single configured shortcode is rendered.
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_shortcode returns the form plugin's own escaped markup.
						echo do_shortcode( $blueworx_contact_form_shortcode );
					} else {
						blueworx_public_part( 'parts/contact-form.php' );
					}
					?>
				</div>
				<?php
				// A background image rather than an <img>, deliberately. The
				// design hides this illustration entirely below 900px, and a
				// hidden <img> is still downloaded — 140KB fetched on a phone to
				// render nothing. A background on a display:none element is not
				// fetched at all. It is decorative, so nothing is lost by it
				// having no alt text; the panel is marked presentational so a
				// screen reader does not announce an empty region.
				?>
				<div class="contact-illus" role="presentation"></div>
			</div>
		</section>

		<section class="contact-cards">
			<?php // No visible heading in the design, so these cards would follow the page h1 as h3s with nothing in between. Named for assistive tech only; sighted layout is unchanged. ?>
			<h2 class="bw-sr-only"><?php esc_html_e( 'Ways to reach us', 'bluegroup-project-blueworx' ); ?></h2>
			<div class="blob" style="width:280px;height:280px;bottom:-100px;right:-80px;opacity:.14"></div>
			<div class="cc-grid">
				<?php foreach ( $blueworx_contact_cards as $blueworx_contact_card ) : ?>
					<div class="cc">
						<div class="cc-ic"><?php blueworx_icon( $blueworx_contact_card['icon'] ); ?></div>
						<h3><?php echo esc_html( $blueworx_contact_card['title'] ); ?></h3>
						<p><?php echo esc_html( $blueworx_contact_card['sub'] ); ?></p>
						<a href="<?php echo esc_url( $blueworx_contact_card['href'] ); ?>"><?php echo esc_html( $blueworx_contact_card['link'] ); ?></a>
					</div>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="sec">
			<div class="center-head" style="margin-bottom:40px">
				<h2 class="h2"><?php esc_html_e( 'Frequently asked questions', 'bluegroup-project-blueworx' ); ?></h2>
				<p class="lead"><?php esc_html_e( 'Everything you need to know about the product and billing.', 'bluegroup-project-blueworx' ); ?></p>
			</div>
			<div class="faq-list">
				<?php foreach ( blueworx_content_faqs() as $blueworx_contact_faq ) : ?>
					<details class="faq-item">
						<summary class="faq-q"><?php echo esc_html( $blueworx_contact_faq['q'] ); ?></summary>
						<div class="faq-a"><?php echo esc_html( $blueworx_contact_faq['a'] ); ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		</section>

		<?php
		blueworx_public_part(
			'parts/testimonials.php',
			array(
				'testimonials' => blueworx_content_reviews(),
				'style'        => 'padding-top:0',
			)
		);
		?>
	</div>
</main>
<?php
blueworx_public_part( 'parts/footer.php' );
blueworx_public_document_close();
