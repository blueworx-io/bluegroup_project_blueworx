<?php
/**
 * Site navigation template part.
 *
 * Ported from Nav.tsx. As of the 2026-09 restructure the mega panel and the
 * About Us dropdown are gone — About and Journal now live only in the footer
 * (templates/parts/footer.php). What remains of the original port is the
 * mobile menu: the React source only mounts it while open, but a plain
 * document cannot slide open an element that does not exist yet, so this
 * renders it unconditionally and relies on assets/js/public-nav.js to toggle
 * an ".open" class, matched by the ".mobile-menu" rule in assets/css/public.css.
 *
 * Every internal href is built with home_url( '/support' ) etc. (matching
 * templates/parts/footer.php), not a bare "/support" — the source's own
 * <Link href="/support"> paths assume a root-domain deployment, but on a
 * subdirectory WordPress install (example.com/blog/) a bare root-relative
 * href points outside the site entirely. blueworx_public_nav_active_class()
 * still compares against the home-relative $blueworx_nav_path built below,
 * so active-state matching is unaffected by the subdirectory prefix either
 * way.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Resolve the current request path once, relative to the site root, so every
// active-state check below compares against the same value. blueworx_public_pages()
// registers "home" for "/"; every other href here is a future Plan 2 page reached
// the same root-relative way footer.php already links to them.
$blueworx_nav_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$blueworx_nav_path        = (string) wp_parse_url( sanitize_text_field( $blueworx_nav_request_uri ), PHP_URL_PATH );
$blueworx_nav_home_path   = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );

if ( '' !== $blueworx_nav_home_path && '/' !== $blueworx_nav_home_path && 0 === strpos( $blueworx_nav_path, $blueworx_nav_home_path ) ) {
	$blueworx_nav_path = substr( $blueworx_nav_path, strlen( $blueworx_nav_home_path ) );
}

$blueworx_nav_path = '/' . trim( $blueworx_nav_path, '/' );

if ( ! function_exists( 'blueworx_public_nav_active_class' ) ) {
	/**
	 * Whether a nav href is the current page.
	 *
	 * Exact match for "/", prefix match otherwise — ports Nav.tsx's
	 * `href === "/" ? pathname === "/" : pathname.startsWith(href)` verbatim.
	 *
	 * @param string $href         Root-relative href, e.g. '/support'.
	 * @param string $current_path Current request path, e.g. '/support/seo'.
	 * @return string 'active' or ''.
	 */
	function blueworx_public_nav_active_class( $href, $current_path ) {
		if ( '/' === $href ) {
			return '/' === $current_path ? 'active' : '';
		}

		return 0 === strpos( $current_path, $href ) ? 'active' : '';
	}
}

$blueworx_nav_logo_path = BLUEWORX_SITE_PATH . 'assets/img/logo.png';
$blueworx_nav_logo_url  = BLUEWORX_SITE_URL . 'assets/img/logo.png';
?>
<?php
// One list, rendered twice (desktop row and mobile panel), so the two can
// never disagree about what the site's pages are. ClubHouse is last and
// carries the "New" tag; AI Powered lives in the footer only.
$blueworx_nav_items = array(
	array( '/', __( 'Home', 'bluegroup-project-blueworx' ), home_url( '/' ) ),
	array( '/hosting', __( 'Hosting', 'bluegroup-project-blueworx' ), home_url( '/hosting' ) ),
	array( '/support', __( 'Support', 'bluegroup-project-blueworx' ), home_url( '/support' ) ),
	array( '/portfolio', __( 'Portfolio', 'bluegroup-project-blueworx' ), home_url( '/portfolio' ) ),
	array( '/clubhouse', __( 'ClubHouse', 'bluegroup-project-blueworx' ), home_url( '/clubhouse' ), true ),
);

// The currency switcher, rendered in the desktop cluster and again in the
// mobile panel. Both copies are wired by public-nav.js.
$blueworx_nav_currency = '
	<div class="bw-cur">
		<button type="button" class="bw-cur-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="' . esc_attr__( 'Currency', 'bluegroup-project-blueworx' ) . '">
			<span data-cur-label>£ GBP</span>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
		</button>
		<div class="bw-cur-menu" role="listbox">
			<button type="button" role="option" data-cur="GBP">£ GBP<i>' . esc_html__( 'Pound', 'bluegroup-project-blueworx' ) . '</i></button>
			<button type="button" role="option" data-cur="EUR">€ EUR<i>' . esc_html__( 'Euro', 'bluegroup-project-blueworx' ) . '</i></button>
			<button type="button" role="option" data-cur="USD">$ USD<i>' . esc_html__( 'Dollar', 'bluegroup-project-blueworx' ) . '</i></button>
			<button type="button" role="option" data-cur="ZAR">R ZAR<i>' . esc_html__( 'Rand', 'bluegroup-project-blueworx' ) . '</i></button>
			<button type="button" role="option" data-cur="AUD">A$ AUD<i>' . esc_html__( 'Australian dollar', 'bluegroup-project-blueworx' ) . '</i></button>
			<button type="button" role="option" data-cur="AED">AED<i>' . esc_html__( 'Dirham', 'bluegroup-project-blueworx' ) . '</i></button>
		</div>
	</div>';
?>
<nav>
	<a class="nav-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php if ( file_exists( $blueworx_nav_logo_path ) ) : ?>
			<?php blueworx_public_image( 'img/logo.png', __( 'BlueWorx', 'bluegroup-project-blueworx' ), array( 'above_fold' => true ) ); ?>
		<?php else : ?>
			<span class="bw-nav-logo-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		<?php endif; ?>
	</a>
	<div class="nav-links">
		<?php foreach ( $blueworx_nav_items as $blueworx_nav_item ) : ?>
			<a class="<?php echo esc_attr( blueworx_public_nav_active_class( $blueworx_nav_item[0], $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( $blueworx_nav_item[2] ); ?>"<?php echo ! empty( $blueworx_nav_item[3] ) ? ' style="gap:7px"' : ''; ?>><?php echo esc_html( $blueworx_nav_item[1] ); ?><?php if ( ! empty( $blueworx_nav_item[3] ) ) : ?><span class="nav-tag tag-light"><?php echo esc_html__( 'New', 'bluegroup-project-blueworx' ); ?></span><?php endif; ?></a>
		<?php endforeach; ?>
	</div>
	<div class="nav-cta">
		<a class="nav-sign-in" href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
		<a class="nav-btn" href="<?php echo esc_url( home_url( '/contact' ) ); ?>">
			<?php echo esc_html__( 'Contact', 'bluegroup-project-blueworx' ); ?>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="7" y1="17" x2="17" y2="7" /><polyline points="7 7 17 7 17 17" /></svg>
		</a>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr__()/esc_html__() calls and static markup.
		echo $blueworx_nav_currency;
		?>
	</div>
	<a class="nav-sign-in-mobile" href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
	<button class="hamburger" aria-label="<?php echo esc_attr__( 'Toggle menu', 'bluegroup-project-blueworx' ); ?>" aria-expanded="false">
		<span></span>
		<span></span>
	</button>
</nav>
<div class="mobile-menu">
	<?php foreach ( $blueworx_nav_items as $blueworx_nav_item ) : ?>
		<a class="<?php echo esc_attr( blueworx_public_nav_active_class( $blueworx_nav_item[0], $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( $blueworx_nav_item[2] ); ?>"><?php echo esc_html( $blueworx_nav_item[1] ); ?><?php if ( ! empty( $blueworx_nav_item[3] ) ) : ?><span class="nav-tag tag-light"><?php echo esc_html__( 'New', 'bluegroup-project-blueworx' ); ?></span><?php endif; ?></a>
	<?php endforeach; ?>
	<a href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
	<a class="btn btn-brand btn-md" href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php echo esc_html__( 'Contact', 'bluegroup-project-blueworx' ); ?></a>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- see above.
	echo $blueworx_nav_currency;
	?>
</div>
