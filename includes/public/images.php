<?php
/**
 * Public front-end layer — images (#82).
 *
 * Not one image on the site declared its size, so the browser could not
 * reserve space for any of them and the page jumped about as each one arrived.
 * The small tool icons in the menu were lazy-loaded even though they are needed
 * the moment somebody opens it, and the feature images at the top of a page
 * were not prioritised even though they are the biggest thing a visitor waits
 * for.
 *
 * Every one of those is a property of the file or of where it sits on the page,
 * so this puts them in one place rather than on eleven <img> tags spread across
 * the templates. Templates say what the image is and whether it is above the
 * fold; everything else is worked out here.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The pixel size of a bundled image.
 *
 * Read from the file itself rather than written into the templates, because a
 * number typed next to an <img> is right until somebody replaces the image.
 * getimagesize() opens the file, so the answer is cached for a day — the files
 * ship with the plugin and only change when it does.
 *
 * @param string $relative Path under assets/, e.g. 'img/hero-image.png'.
 * @return array|null array( width, height ), or null when the file is missing.
 */
function blueworx_public_image_size( $relative ) {
	$relative = ltrim( (string) $relative, '/' );
	$path     = BLUEWORX_SITE_PATH . 'assets/' . $relative;

	if ( ! file_exists( $path ) ) {
		return null;
	}

	$key    = 'blueworx_img_' . md5( $relative . '|' . BLUEWORX_SITE_VERSION );
	$cached = get_transient( $key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$size = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a corrupt or unreadable file must degrade to "no dimensions", not a warning on a live page.

	if ( ! is_array( $size ) || empty( $size[0] ) || empty( $size[1] ) ) {
		return null;
	}

	$result = array( (int) $size[0], (int) $size[1] );

	set_transient( $key, $result, DAY_IN_SECONDS );

	return $result;
}

/**
 * The WebP twin of a bundled image, if one was built.
 *
 * WebP files are generated at build time (scripts/build-webp.mjs) and ship
 * beside the original, so this is a file check rather than a conversion. No
 * twin means the <picture> wrapper is skipped entirely and the original is
 * served on its own — nothing depends on the build step having run.
 *
 * @param string $relative Path under assets/.
 * @return string|null Relative path of the WebP file, or null.
 */
function blueworx_public_image_webp( $relative ) {
	$relative = ltrim( (string) $relative, '/' );
	$webp     = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $relative );

	if ( null === $webp || $webp === $relative ) {
		return null;
	}

	return file_exists( BLUEWORX_SITE_PATH . 'assets/' . $webp ) ? $webp : null;
}

/**
 * Renders a bundled image.
 *
 * @param string $relative Path under assets/, e.g. 'img/hero-image.png'.
 * @param string $alt      Alt text. Empty string for a decorative image.
 * @param array  $args     Optional:
 *                         - 'above_fold' (bool) Default false. The one image
 *                           the visitor is waiting for at the top of the page:
 *                           loaded eagerly and prioritised. Lazy-loading this
 *                           delays the very thing being waited for.
 *                         - 'eager' (bool) Default false. Loaded eagerly but
 *                           not prioritised — for something needed the moment
 *                           it is asked for rather than on arrival, like the
 *                           tool icons in a menu that has not been opened yet.
 *                         - 'class', 'style' (string) Passed through.
 *                         - 'sizes'  (string) Rendered display width hint.
 * @return string The markup.
 */
function blueworx_public_image_markup( $relative, $alt = '', $args = array() ) {
	$relative = ltrim( (string) $relative, '/' );
	$path     = BLUEWORX_SITE_PATH . 'assets/' . $relative;

	if ( ! file_exists( $path ) ) {
		return '';
	}

	$above_fold = ! empty( $args['above_fold'] );
	$size       = blueworx_public_image_size( $relative );
	$webp       = blueworx_public_image_webp( $relative );

	$attributes = array(
		'src' => esc_url( BLUEWORX_SITE_URL . 'assets/' . $relative ),
		'alt' => esc_attr( (string) $alt ),
	);

	if ( is_array( $size ) ) {
		$attributes['width']  = (int) $size[0];
		$attributes['height'] = (int) $size[1];
	}

	// Below the fold is lazy; above it is not, and never should be — a lazy
	// hero is a hero that starts loading later than it otherwise would.
	$attributes['loading']  = ( $above_fold || ! empty( $args['eager'] ) ) ? 'eager' : 'lazy';
	$attributes['decoding'] = 'async';

	if ( $above_fold ) {
		$attributes['fetchpriority'] = 'high';
	}

	foreach ( array( 'class', 'style', 'sizes' ) as $passthrough ) {
		if ( ! empty( $args[ $passthrough ] ) ) {
			$attributes[ $passthrough ] = esc_attr( (string) $args[ $passthrough ] );
		}
	}

	$rendered = '';

	foreach ( $attributes as $name => $value ) {
		$rendered .= sprintf( ' %s="%s"', $name, $value );
	}

	$img = '<img' . $rendered . ' />';

	if ( null === $webp ) {
		return $img;
	}

	// A <picture> rather than a srcset: WebP is a different format, not a
	// different size, so the browser has to be told the type and left to fall
	// back to the original if it cannot read it.
	return sprintf(
		'<picture><source srcset="%s" type="image/webp" />%s</picture>',
		esc_url( BLUEWORX_SITE_URL . 'assets/' . $webp ),
		$img
	);
}

/**
 * Echoes blueworx_public_image_markup().
 *
 * @param string $relative Path under assets/.
 * @param string $alt      Alt text.
 * @param array  $args     See blueworx_public_image_markup().
 * @return void
 */
function blueworx_public_image( $relative, $alt = '', $args = array() ) {
	echo blueworx_public_image_markup( $relative, $alt, $args ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- every value is escaped as it is assembled above.
}

/**
 * Preloads the fonts the top of every page is set in.
 *
 * A `font-display: swap` face is drawn in a fallback font first and swapped
 * when the real one arrives, and the two are different widths — so the heading
 * and the paragraph under it reflow, and everything below them moves. Measured
 * on Services: 0.18 of layout shift on its own, nearly twice the whole budget,
 * with no image involved at all.
 *
 * Only the three faces used above the fold, and only as a preload — a font
 * nobody sees until they scroll does not need to compete with the page for
 * bandwidth. `crossorigin` is not optional even for a same-origin font: without
 * it the browser fetches the file twice and the preload does nothing.
 *
 * @return void
 */
function blueworx_public_preload_fonts() {
	if ( ! blueworx_public_renders_request() ) {
		return;
	}

	foreach ( array( 'sora-700', 'inter-400', 'inter-500' ) as $blueworx_font ) {
		$path = 'assets/fonts/' . $blueworx_font . '.woff2';

		if ( ! file_exists( BLUEWORX_SITE_PATH . $path ) ) {
			continue;
		}

		printf(
			'<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin />' . "\n",
			esc_url( BLUEWORX_SITE_URL . $path )
		);
	}
}
add_action( 'wp_head', 'blueworx_public_preload_fonts', 1 );

/**
 * Stops WordPress inlining its block theme.json styles on plugin-rendered
 * pages.
 *
 * About 15KB of CSS for blocks this site does not render, inlined into the head
 * of every page. The asset sweep in assets.php already dequeues the handle —
 * and it came back anyway, because core enqueues global styles TWICE: once on
 * wp_enqueue_scripts, and again on wp_footer at priority 1, long after the
 * sweep has run. Removing the actions themselves is the only thing that holds.
 *
 * @return void
 */
function blueworx_public_drop_global_styles() {
	if ( ! blueworx_public_renders_request() ) {
		return;
	}

	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );

	wp_dequeue_style( 'global-styles' );
	wp_deregister_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'blueworx_public_drop_global_styles', PHP_INT_MAX );
