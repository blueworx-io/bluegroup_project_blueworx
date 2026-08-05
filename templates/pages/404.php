<?php
/**
 * Not-found page template (#78, redesigned in #96).
 *
 * The first version of this page was composed from the marketing parts — nav,
 * tech hero, a five-card link list, footer — so that a rotted link still landed
 * somewhere recognisably BlueWorx rather than on the theme's bare "Not Found".
 *
 * The Claude Design replaces it with a quieter idea, and that is the version
 * that ships now: one full-height panel, the code ghosted huge behind the
 * copy, and exactly two things to do — go to the home page, or go back to
 * wherever you came from. A 404 is not a page anybody wants to read; giving it
 * a nav, a footer and five equally-weighted choices asks a visitor to make a
 * decision they did not come here to make.
 *
 * Two departures from the design, both deliberate:
 *
 * - It is branded. The design is theme-neutral (a generic font stack and a
 *   single-letter logo mark) because it was drawn to be reusable. Here it uses
 *   the real wordmark, Sora and the site's own accent, like every other page.
 * - The contact line at the foot stays. The design ends at the two buttons,
 *   but somebody who followed a dead link often wanted something specific, and
 *   a way to say so costs one line.
 *
 * The requested path is echoed back so a visitor can see what was actually
 * asked for — most often a truncated or mistyped URL, which is the one piece of
 * information that makes the page useful rather than decorative.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The address that missed. Read from the request rather than from the query,
// which by definition resolved to nothing. Sanitized and length-capped before
// it is shown: this is attacker-controlled text on a page that renders for any
// URL at all, so it is treated as hostile input even though it is only ever
// echoed through esc_html().
$blueworx_404_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading the request path on a 404, not processing a form.
$blueworx_404_path = (string) wp_parse_url( $blueworx_404_uri, PHP_URL_PATH );

// The home page is not a miss, and a path that is only "/" tells the visitor
// nothing, so the chip is dropped rather than shown empty.
if ( strlen( $blueworx_404_path ) < 2 ) {
	$blueworx_404_path = '';
}

if ( strlen( $blueworx_404_path ) > 80 ) {
	$blueworx_404_path = substr( $blueworx_404_path, 0, 80 ) . '…';
}

$blueworx_404_logo_path = BLUEWORX_SITE_PATH . 'assets/img/logo.png';

blueworx_public_document_open( array( 'body_class' => 'bw-404' ) );
?>
<div class="nf">
	<div class="nf-ghost" aria-hidden="true">404</div>

	<header class="nf-head">
		<a class="nf-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( file_exists( $blueworx_404_logo_path ) ) : ?>
				<?php blueworx_public_image( 'img/logo.png', __( 'BlueWorx', 'bluegroup-project-blueworx' ) ); ?>
			<?php else : ?>
				<span class="nf-brand-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<?php endif; ?>
		</a>
	</header>

	<main id="content" class="nf-main" tabindex="-1">
		<p class="nf-code"><?php echo esc_html__( 'Error 404', 'bluegroup-project-blueworx' ); ?></p>

		<h1 class="nf-title"><?php echo esc_html__( 'We can’t find that page.', 'bluegroup-project-blueworx' ); ?></h1>

		<p class="nf-msg"><?php echo esc_html__( 'The link may be out of date, or the page may have been moved or removed. Nothing is wrong on your end.', 'bluegroup-project-blueworx' ); ?></p>

		<?php if ( '' !== $blueworx_404_path ) : ?>
			<p class="nf-path">
				<span class="nf-path-label"><?php echo esc_html__( 'You asked for', 'bluegroup-project-blueworx' ); ?></span>
				<span class="nf-path-value"><?php echo esc_html( $blueworx_404_path ); ?></span>
			</p>
		<?php endif; ?>

		<div class="nf-actions">
			<a class="btn btn-brand btn-md" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( 'Go to homepage', 'bluegroup-project-blueworx' ); ?></a>
			<?php
			// A real <button>, not a link: "back" is a browser action, and there
			// is no URL that means it. public-widgets.js wires it and falls back
			// to the home page when there is no history to go back to — a fresh
			// tab opened straight onto a dead link, which is common enough from
			// search results to be worth handling.
			?>
			<button type="button" class="btn btn-outline btn-md" data-bw-back data-bw-back-fallback="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( 'Go back', 'bluegroup-project-blueworx' ); ?></button>
		</div>
	</main>

	<footer class="nf-foot">
		<p>
			<?php
			printf(
				/* translators: %s: link to the contact page. */
				esc_html__( 'Looking for something specific? %s and we will point you at it.', 'bluegroup-project-blueworx' ),
				'<a href="' . esc_url( home_url( '/contact' ) ) . '">' . esc_html__( 'Tell us what you were after', 'bluegroup-project-blueworx' ) . '</a>'
			);
			?>
		</p>
	</footer>
</div>
<?php
blueworx_public_document_close();
