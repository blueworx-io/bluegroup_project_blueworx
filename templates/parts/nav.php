<?php
/**
 * Site navigation template part.
 *
 * Ported from Nav.tsx. The React source conditionally mounts the Toolbox
 * mega panel, the About Us dropdown and the mobile menu only while each is
 * open — a plain document cannot hover into, or slide open, an element that
 * does not exist yet, so this port renders all three unconditionally and
 * relies on assets/js/public-nav.js to toggle an ".open" class, matched by
 * ".mega-panel"/".about-panel"/".mobile-menu" rules in assets/css/public.css.
 * That is the one deliberate structural difference from the source; markup
 * order and every class name otherwise match it exactly.
 *
 * Every internal href is built with home_url( '/services' ) etc. (matching
 * templates/parts/footer.php), not a bare "/services" — the source's own
 * <Link href="/services"> paths assume a root-domain deployment, but on a
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

/*
 * The Toolbox tools, from the plugin's own registry.
 *
 * This list used to be hand-transcribed here — the same twelve tools, written
 * out a second time. Two copies of the same list drift, and the copy the menu
 * reads is not the copy the pages are created from, so the menu can link to a
 * tool that has no page (or miss one that does). blueworx_content_tools() is
 * the single source of truth for both.
 */
$blueworx_nav_tools = blueworx_content_tools();

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
	 * @param string $href         Root-relative href, e.g. '/services'.
	 * @param string $current_path Current request path, e.g. '/services/seo'.
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
<nav>
	<a class="nav-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php if ( file_exists( $blueworx_nav_logo_path ) ) : ?>
			<?php blueworx_public_image( 'img/logo.png', __( 'BlueWorx', 'bluegroup-project-blueworx' ), array( 'above_fold' => true ) ); ?>
		<?php else : ?>
			<span class="bw-nav-logo-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		<?php endif; ?>
	</a>
	<div class="nav-links">
		<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( 'Home', 'bluegroup-project-blueworx' ); ?></a>
		<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/services', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/services' ) ); ?>"><?php echo esc_html__( 'Services', 'bluegroup-project-blueworx' ); ?></a>

		<div class="nav-drop" data-nav-drop="mega">
			<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/toolbox', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/toolbox' ) ); ?>">
				<?php echo esc_html__( 'Toolbox', 'bluegroup-project-blueworx' ); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><polyline points="6 9 12 15 18 9" /></svg>
			</a>
			<div class="mega-panel">
				<?php foreach ( $blueworx_nav_tools as $blueworx_nav_tool ) : ?>
					<a
						href="<?php echo esc_url( home_url( '/toolbox/' . $blueworx_nav_tool['slug'] ) ); ?>"
						class="mega-item"
						style="display:flex;gap:12px;align-items:flex-start;padding:12px;border-radius:12px;"
					>
						<div style="width:38px;height:38px;border-radius:10px;background:#fff;flex-shrink:0;display:flex;align-items:center;justify-content:center;overflow:hidden">
							<?php
							// Eager, not lazy: the menu is in the document from
							// the start but hidden, so a lazy icon only begins
							// loading once the panel opens — the one moment the
							// visitor is looking straight at it.
							blueworx_public_image(
								'img/tools/' . $blueworx_nav_tool['slug'] . '.png',
								$blueworx_nav_tool['name'],
								array(
									'eager' => true,
									'style' => 'width:22px;height:22px;object-fit:contain',
								)
							);
							?>
						</div>
						<div>
							<div style="font-size:14.5px;font-weight:600;color:#fff;display:flex;align-items:center;gap:7px">
								<?php echo esc_html( $blueworx_nav_tool['name'] ); ?>
								<?php if ( ! empty( $blueworx_nav_tool['popular'] ) ) : ?>
									<span class="nav-tag tag-dark"><?php echo esc_html__( 'Popular', 'bluegroup-project-blueworx' ); ?></span>
								<?php endif; ?>
							</div>
							<div style="font-size:12.5px;color:rgba(255,255,255,.5);line-height:1.4;margin-top:2px"><?php echo esc_html( $blueworx_nav_tool['desc'] ); ?></div>
						</div>
					</a>
				<?php endforeach; ?>
				<div style="grid-column:1 / -1;border-top:1px solid rgba(255,255,255,.1);margin-top:8px;padding-top:16px;display:flex;justify-content:space-between;align-items:center">
					<span style="font-size:13px;color:rgba(255,255,255,.5)"><?php echo esc_html__( '12 tools, one subscription.', 'bluegroup-project-blueworx' ); ?></span>
					<a href="<?php echo esc_url( home_url( '/toolbox' ) ); ?>" style="font-size:14px;font-weight:600;color:#A5A7FF;cursor:pointer;display:flex;align-items:center;gap:6px;text-decoration:none">
						<?php echo esc_html__( 'Browse the full Toolbox', 'bluegroup-project-blueworx' ); ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><line x1="7" y1="17" x2="17" y2="7" /><polyline points="7 7 17 7 17 17" /></svg>
					</a>
				</div>
			</div>
		</div>

		<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/pricing', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/pricing' ) ); ?>"><?php echo esc_html__( 'Pricing', 'bluegroup-project-blueworx' ); ?></a>

		<div class="nav-drop" data-nav-drop="about">
			<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/about', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/about' ) ); ?>">
				<?php echo esc_html__( 'About Us', 'bluegroup-project-blueworx' ); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><polyline points="6 9 12 15 18 9" /></svg>
			</a>
			<div class="about-panel">
				<a
					class="<?php echo esc_attr( blueworx_public_nav_active_class( '/work', $blueworx_nav_path ) ); ?>"
					href="<?php echo esc_url( home_url( '/work' ) ); ?>"
					style="display:block;padding:10px 14px;color:#fff;font-size:14.5px;font-weight:500;border-radius:8px;text-decoration:none"
				>
					<?php echo esc_html__( 'Work', 'bluegroup-project-blueworx' ); ?>
				</a>
				<?php
				// The journal sits under About Us beside Work, which is where
				// the design puts it (#94). The top row is already six items
				// wide and a seventh wraps it onto two lines at laptop widths.
				?>
				<a
					class="<?php echo esc_attr( blueworx_public_nav_active_class( '/blog', $blueworx_nav_path ) ); ?>"
					href="<?php echo esc_url( blueworx_public_journal_url() ); ?>"
					style="display:block;padding:10px 14px;color:#fff;font-size:14.5px;font-weight:500;border-radius:8px;text-decoration:none"
				>
					<?php echo esc_html__( 'Journal', 'bluegroup-project-blueworx' ); ?>
				</a>
			</div>
		</div>

		<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/ai', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/ai' ) ); ?>" style="gap:7px"><?php echo esc_html__( 'AI Powered', 'bluegroup-project-blueworx' ); ?><span class="nav-tag tag-light"><?php echo esc_html__( 'New', 'bluegroup-project-blueworx' ); ?></span></a>
	</div>
	<div class="nav-cta">
		<a class="nav-sign-in" href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
		<a class="nav-btn" href="<?php echo esc_url( home_url( '/pricing' ) ); ?>">
			<?php echo esc_html__( 'Get a Quote', 'bluegroup-project-blueworx' ); ?>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="7" y1="17" x2="17" y2="7" /><polyline points="7 7 17 7 17 17" /></svg>
		</a>
	</div>
	<a class="nav-sign-in-mobile" href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
	<button class="hamburger" aria-label="<?php echo esc_attr__( 'Toggle menu', 'bluegroup-project-blueworx' ); ?>" aria-expanded="false">
		<span></span>
		<span></span>
	</button>
</nav>
<div class="mobile-menu">
	<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( 'Home', 'bluegroup-project-blueworx' ); ?></a>
	<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/services', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/services' ) ); ?>"><?php echo esc_html__( 'Services', 'bluegroup-project-blueworx' ); ?></a>
	<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/toolbox', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/toolbox' ) ); ?>"><?php echo esc_html__( 'Toolbox', 'bluegroup-project-blueworx' ); ?></a>
	<div style="display:flex;flex-direction:column;gap:0;padding-left:12px;border-left:2px solid rgba(79,70,229,.15);margin:0 0 4px">
		<?php foreach ( $blueworx_nav_tools as $blueworx_nav_tool ) : ?>
			<a href="<?php echo esc_url( home_url( '/toolbox/' . $blueworx_nav_tool['slug'] ) ); ?>" style="font-size:13.5px;padding:8px 8px"><?php echo esc_html( $blueworx_nav_tool['name'] ); ?></a>
		<?php endforeach; ?>
	</div>
	<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/pricing', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/pricing' ) ); ?>"><?php echo esc_html__( 'Pricing', 'bluegroup-project-blueworx' ); ?></a>
	<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/about', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/about' ) ); ?>"><?php echo esc_html__( 'About Us', 'bluegroup-project-blueworx' ); ?></a>
	<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/work', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/work' ) ); ?>" style="font-size:13.5px;padding-left:24px"><?php echo esc_html__( 'Work', 'bluegroup-project-blueworx' ); ?></a>
	<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/blog', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( blueworx_public_journal_url() ); ?>" style="font-size:13.5px;padding-left:24px"><?php echo esc_html__( 'Journal', 'bluegroup-project-blueworx' ); ?></a>
	<a class="<?php echo esc_attr( blueworx_public_nav_active_class( '/ai', $blueworx_nav_path ) ); ?>" href="<?php echo esc_url( home_url( '/ai' ) ); ?>"><?php echo esc_html__( 'AI Powered', 'bluegroup-project-blueworx' ); ?><span class="nav-tag tag-light"><?php echo esc_html__( 'New', 'bluegroup-project-blueworx' ); ?></span></a>
	<a href="<?php echo esc_url( blueworx_public_client_login_url() ); ?>"><?php echo esc_html__( 'Client Login', 'bluegroup-project-blueworx' ); ?></a>
	<a class="btn btn-brand btn-md" href="<?php echo esc_url( home_url( '/pricing' ) ); ?>"><?php echo esc_html__( 'Get a Quote', 'bluegroup-project-blueworx' ); ?></a>
</div>
