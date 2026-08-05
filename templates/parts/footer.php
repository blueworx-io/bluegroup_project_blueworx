<?php
/**
 * CTA band + site footer template part.
 *
 * Ported from CtaBand.tsx and Footer.tsx. The CTA band renders on every page,
 * outside <main>, immediately before the footer — that is the source's
 * layout, not a stylistic choice, so callers should not move it inside main.
 *
 * The design's social icons, its Blog/Resources/Careers links and its
 * newsletter box were all ported as markup with nothing behind them — no
 * href, no form, no handler. On a live site that is worse than leaving them
 * out: they look like working controls, and a visitor who clicks one learns
 * the site is broken. All four are removed until there is something real to
 * point them at (#77). Nothing is styled to look interactive here unless it
 * is.
 *
 * The source's <img src="/assets/logo.png"> is bundled by the plugin itself
 * at assets/img/logo.png, matching what the front-end design ships. This
 * deliberately does NOT read get_theme_mod('custom_logo') — a theme mod is
 * stored per-theme, so it changes or vanishes on theme switch, which would
 * make the footer's output depend on which theme happens to be active. The
 * whole point of this public layer is that output is identical regardless
 * of theme, so the plugin owns its own brand asset instead. A graceful text
 * fallback (the site name) still applies if the bundled file is somehow
 * absent, so `.fb` never renders a broken image.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blueworx_footer_logo_path = BLUEWORX_SITE_PATH . 'assets/img/logo.png';
$blueworx_footer_logo_url  = BLUEWORX_SITE_URL . 'assets/img/logo.png';
?>
<div class="cta-soft">
	<div class="cta-inner">
		<?php
		blueworx_blob( 'width:220px;height:220px;bottom:-80px;left:-40px;opacity:.4' );
		blueworx_blob( 'width:180px;height:180px;top:-60px;right:-20px;opacity:.35' );
		?>
		<h2 class="h2"><?php echo esc_html__( 'Ready to Build a Digital Solution That Wins?', 'bluegroup-project-blueworx' ); ?></h2>
		<p><?php echo esc_html__( "Book a free strategy call. We'll review your current setup and show you exactly where the opportunities are.", 'bluegroup-project-blueworx' ); ?></p>
		<div class="cta-actions">
			<a href="<?php echo esc_url( home_url( '/pricing' ) ); ?>" class="btn btn-brand btn-md"><?php echo esc_html__( 'Get a Quote', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn btn-outline-w btn-md"><?php echo esc_html__( 'Book a Call', 'bluegroup-project-blueworx' ); ?></a>
		</div>
	</div>
</div>
<footer>
	<div class="ft">
		<div class="fb">
			<?php if ( file_exists( $blueworx_footer_logo_path ) ) : ?>
				<?php
				blueworx_public_image(
					'img/logo.png',
					__( 'BlueWorx', 'bluegroup-project-blueworx' ),
					array( 'style' => 'filter:brightness(0) invert(1)' )
				);
				?>
			<?php else : ?>
				<span class="bw-footer-logo-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<?php endif; ?>
			<p><?php echo esc_html__( 'BlueWorx supports growing businesses worldwide with premium tools, hosting, and expert support.', 'bluegroup-project-blueworx' ); ?></p>
		</div>
		<div class="fcol">
			<h3><?php echo esc_html__( 'Pages', 'bluegroup-project-blueworx' ); ?></h3>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( 'Home', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/services' ) ); ?>"><?php echo esc_html__( 'Services', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/ai' ) ); ?>"><?php echo esc_html__( 'AI Powered', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/work' ) ); ?>"><?php echo esc_html__( 'Work', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/toolbox' ) ); ?>"><?php echo esc_html__( 'Toolbox', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/about' ) ); ?>"><?php echo esc_html__( 'About Us', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/pricing' ) ); ?>"><?php echo esc_html__( 'Pricing', 'bluegroup-project-blueworx' ); ?></a>
			<?php // Resolved through the page map rather than home_url('/blog'), so the link survives a rename (#94). ?>
			<a href="<?php echo esc_url( blueworx_public_journal_url() ); ?>"><?php echo esc_html__( 'Journal', 'bluegroup-project-blueworx' ); ?></a>
		</div>
		<div class="fcol">
			<h3><?php echo esc_html__( 'About', 'bluegroup-project-blueworx' ); ?></h3>
			<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php echo esc_html__( 'Contact', 'bluegroup-project-blueworx' ); ?></a>
			<a href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
		</div>
	</div>
	<div class="fbot">
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: current year. */
					__( '© %s BlueWorx. All rights reserved.', 'bluegroup-project-blueworx' ),
					gmdate( 'Y' )
				)
			);
			?>
		</p>
		<p><?php echo esc_html__( 'Powered by BabyBlue Digital.', 'bluegroup-project-blueworx' ); ?></p>
	</div>
</footer>
