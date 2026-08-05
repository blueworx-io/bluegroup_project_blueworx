<?php
/**
 * Public front-end layer — shared helpers.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inline SVG path geometry, keyed by icon name.
 *
 * Ported verbatim from the front-end's lib/icons.ts. Values are the inner
 * markup of a 24x24 lucide-style icon, not complete <svg> elements.
 *
 * @return array Name => inner SVG markup.
 */
function blueworx_icon_paths() {
	return array(
		'chat'     => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
		'mail'     => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'chart'    => '<path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/>',
		'clock'    => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
		'sms'      => '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>',
		'doc'      => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
		'server'   => '<rect width="20" height="8" x="2" y="2" rx="2" ry="2"/><rect width="20" height="8" x="2" y="14" rx="2" ry="2"/><line x1="6" x2="6.01" y1="6" y2="6"/><line x1="6" x2="6.01" y1="18" y2="18"/>',
		'users'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
		'plug'     => '<path d="M12 22v-5"/><path d="M9 8V2"/><path d="M15 8V2"/><path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z"/>',
		'book'     => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
		'cart'     => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
		'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
		'phone'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'sparkles' => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
		'code'     => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
		'zap'      => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
		'git'      => '<circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M6 21V9a9 9 0 0 0 9 9"/>',
		'palette'  => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
		'workflow' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><path d="M10 6.5h3A2.5 2.5 0 0 1 15.5 9v5"/>',
		'gauge'    => '<path d="M12 14l4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>',
		'shield'   => '<path d="M20 13c0 5-3.5 7.5-8 8.5-4.5-1-8-3.5-8-8.5V6.5l8-3 8 3z"/><path d="m9 12 2 2 4-4"/>',
		// Article sharing (#95). Drawn in the same single-weight stroke style
		// as the set above rather than dropped in as the platforms' own filled
		// marks: these sit in a row of identical outline buttons, and two
		// filled glyphs among them read as a rendering fault.
		'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/>',
		'x'        => '<path d="M17.5 3h3.2l-7 8 8.3 10h-6.5l-4.6-6-5.3 6H2.4l7.5-8.6L2 3h6.7l4.2 5.5z"/>',
		'link'     => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
	);
}

/**
 * Gets the wp_kses() allowlist for the inline icon SVGs.
 *
 * Deliberately separate from admin-menu-icons.php's own allowlist: that one
 * covers only the shapes the admin menu re-skin uses (no line/polyline/
 * polygon). The public icon set needs those too (clock, code, zap, server),
 * so the two allowlists are kept independent per module rather than shared.
 *
 * @return array Allowed tags and attributes.
 */
function blueworx_icon_allowed_svg() {
	$geometry = array(
		'd'      => true,
		'x'      => true,
		'y'      => true,
		'x1'     => true,
		'x2'     => true,
		'y1'     => true,
		'y2'     => true,
		'cx'     => true,
		'cy'     => true,
		'r'      => true,
		'rx'     => true,
		'ry'     => true,
		'width'  => true,
		'height' => true,
		'points' => true,
		'fill'   => true,
		'stroke' => true,
		'class'  => true,
	);

	return array(
		'path'     => $geometry,
		'circle'   => $geometry,
		'rect'     => $geometry,
		'line'     => $geometry,
		'polyline' => $geometry,
		'polygon'  => $geometry,
	);
}

/**
 * Echoes an icon.
 *
 * The wrapping span carries data-ic and is what CSS sizes; the svg always
 * fills it. Emitting a bare svg breaks icon sizing across the site.
 *
 * @param string $name  Icon key.
 * @param string $class Optional CSS class for the span.
 * @param string $style Optional inline style for the span.
 * @return void
 */
function blueworx_icon( $name, $class = '', $style = '' ) {
	$paths = blueworx_icon_paths();

	if ( ! isset( $paths[ $name ] ) ) {
		return;
	}

	printf(
		'<span data-ic="%1$s"%2$s%3$s><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:100%%;height:100%%" aria-hidden="true" focusable="false">%4$s</svg></span>',
		esc_attr( $name ),
		'' === $class ? '' : ' class="' . esc_attr( $class ) . '"',
		'' === $style ? '' : ' style="' . esc_attr( $style ) . '"',
		wp_kses( $paths[ $name ], blueworx_icon_allowed_svg() )
	);
}

/**
 * Echoes a decorative background blob.
 *
 * Purely decorative (aria-hidden by nature of carrying no content), sized
 * and positioned entirely via the optional inline style — see CtaBand.tsx's
 * per-instance width/height/offset/opacity values, which callers pass
 * through verbatim as a CSS string.
 *
 * @param string $style Optional inline style, e.g. 'width:220px;height:220px'.
 * @return void
 */
function blueworx_blob( $style = '' ) {
	printf(
		'<div class="blob"%s></div>',
		'' === $style ? '' : ' style="' . esc_attr( $style ) . '"'
	);
}

/**
 * Where the nav's "Client Login" link should send a visitor.
 *
 * The nav shows this link in three places (desktop, the mobile bar, the mobile
 * menu) and each had `/portal` written into it by hand. `/portal` belongs to
 * SureDash, which is being removed (#34) — so on the day it goes, all three
 * become 404s, and three copies is three chances to miss one.
 *
 * It now points at the plugin's own sign-in page (#28, #43), which sends a
 * client on to the dashboard — so `/portal` and SureDash are no longer in the
 * path at all. A site that has set the option keeps whatever it set; the
 * default is what changed.
 *
 * A stored value is passed through as-is when it is absolute, and resolved
 * against the site otherwise, so both '/dashboard' and a full URL work.
 *
 * @return string Absolute URL.
 */
function blueworx_public_client_login_url() {
	$configured = trim( (string) get_option( 'blueworx_client_login_url', '' ) );

	if ( '' === $configured ) {
		$url = home_url( '/login' );
	} elseif ( wp_parse_url( $configured, PHP_URL_SCHEME ) ) {
		$url = $configured;
	} else {
		$url = home_url( '/' . ltrim( $configured, '/' ) );
	}

	/**
	 * Filters the Client Login destination.
	 *
	 * @param string $url Absolute URL the link points at.
	 */
	return (string) apply_filters( 'blueworx_client_login_url', $url );
}

/**
 * How long an article takes to read, in whole minutes (#94, #95).
 *
 * 200 words a minute is the conventional figure for adult reading of ordinary
 * prose, and it is the one every publisher's "N min read" label uses. Rounded
 * up rather than to nearest, and floored at one: a 40-word note that claims
 * "0 min read" reads as a bug, not as brevity.
 *
 * Counted on the stripped content so markup, shortcodes and block comments do
 * not inflate the figure.
 *
 * @param WP_Post|int|null $post Post or ID. Defaults to the current post.
 * @return int Minutes, at least 1.
 */
function blueworx_public_read_minutes( $post = null ) {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post ) {
		return 1;
	}

	$text  = wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) );
	$words = preg_match_all( '/\S+/u', $text );

	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * The "6 min read" label for an article.
 *
 * @param WP_Post|int|null $post Post or ID. Defaults to the current post.
 * @return string Translated label.
 */
function blueworx_public_read_label( $post = null ) {
	$minutes = blueworx_public_read_minutes( $post );

	return sprintf(
		/* translators: %d: number of minutes. */
		_n( '%d min read', '%d min read', $minutes, 'bluegroup-project-blueworx' ),
		$minutes
	);
}

/**
 * Initials for the avatar chip beside an author's name (#94, #95).
 *
 * Deliberately not an image: the design's avatar is a solid disc with initials
 * in it, and reaching for get_avatar() would hand a client's face to Gravatar
 * on every article view — a third-party request, and one the author never
 * agreed to. Two letters at most, because three-part names overflow the disc.
 *
 * @param string $name Display name.
 * @return string One or two uppercase letters, or '' when there is no name.
 */
function blueworx_public_initials( $name ) {
	$name  = trim( (string) $name );
	$parts = preg_split( '/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY );

	if ( ! $parts ) {
		return '';
	}

	$first = function_exists( 'mb_substr' ) ? mb_substr( $parts[0], 0, 1 ) : substr( $parts[0], 0, 1 );
	$last  = '';

	if ( count( $parts ) > 1 ) {
		$tail = $parts[ count( $parts ) - 1 ];
		$last = function_exists( 'mb_substr' ) ? mb_substr( $tail, 0, 1 ) : substr( $tail, 0, 1 );
	}

	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first . $last ) : strtoupper( $first . $last );
}

/**
 * The one category an article is filed under, for its card label (#94).
 *
 * A post can carry several; the card and the breadcrumb each have room for one.
 * The lowest term ID wins rather than the alphabetically first, so the same
 * post always shows the same label wherever it appears — an unstable label is
 * how a filter and a card end up disagreeing about the same article.
 *
 * Uncategorised posts return null rather than the "Uncategorized" default,
 * which is a WordPress implementation detail and not a thing to show a reader.
 *
 * @param WP_Post|int|null $post Post or ID. Defaults to the current post.
 * @return WP_Term|null The term, or null.
 */
function blueworx_public_post_category( $post = null ) {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post ) {
		return null;
	}

	$terms = get_the_category( $post->ID );

	if ( ! is_array( $terms ) || ! $terms ) {
		return null;
	}

	usort(
		$terms,
		static function ( $a, $b ) {
			return (int) $a->term_id - (int) $b->term_id;
		}
	);

	foreach ( $terms as $term ) {
		if ( 'uncategorized' !== $term->slug ) {
			return $term;
		}
	}

	return null;
}

/**
 * Where the journal lives (#94).
 *
 * Resolved through the page ID map rather than hard-coding "/blog", so the
 * link still works after an admin renames the page — the same rule every other
 * cross-page link in the public layer follows.
 *
 * @return string Absolute URL.
 */
function blueworx_public_journal_url() {
	$map = (array) get_option( 'blueworx_public_page_ids', array() );

	if ( isset( $map['blog'] ) && blueworx_public_page_is_ours( (int) $map['blog'] ) ) {
		$permalink = get_permalink( (int) $map['blog'] );

		if ( $permalink ) {
			return (string) $permalink;
		}
	}

	return home_url( '/blog' );
}
