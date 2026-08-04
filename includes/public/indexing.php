<?php
/**
 * Public front-end layer — what search engines may index (#80).
 *
 * The sitemap handed to Google invited it to index the client's own account —
 * sign in, sign up, reset a password, the dashboard and its three sections —
 * and every one of them served `robots: index, follow`. The dashboard pages
 * also emitted a second, contradictory `noindex` tag of their own, so a page
 * carried two robots tags saying opposite things and the crawler picked one.
 *
 * Two separate problems, fixed here in one place:
 *
 * 1. Say noindex once, and say it in the way the site's SEO plugin respects.
 *    SureRank calls remove_all_filters( 'wp_robots' ) when it loads and prints
 *    the robots tag itself, from a per-post setting — so a tag echoed straight
 *    into wp_head sits alongside SureRank's rather than replacing it. Writing
 *    that setting instead makes SureRank print noindex, and, because the same
 *    setting is what its sitemap reads, drops the page from the sitemap at the
 *    same time. The wp_robots filter below is the fallback for a site with no
 *    SEO plugin at all, and is deliberately registered early enough that
 *    SureRank removes it — whichever is active, there is exactly one tag.
 *
 * 2. Keep them out of WordPress's own sitemap too, which is what a site
 *    without SureRank serves.
 *
 * Which pages are private is not a list of paths kept in step by hand: it is
 * the `account` and `auth` flags already on the registry entries in
 * account.php and auth.php. A client-area section added later is private by
 * existing.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SureRank's per-post "do not index this" setting.
 *
 * Its sitemap and its robots tag both read this key, so writing it is the one
 * change that answers both. Harmless on a site without SureRank — it is post
 * meta nothing else reads.
 */
if ( ! defined( 'BLUEWORX_SURERANK_NOINDEX_META' ) ) {
	define( 'BLUEWORX_SURERANK_NOINDEX_META', 'surerank_settings_post_no_index' );
}

/**
 * Registry keys of the pages that must never appear in search results.
 *
 * @return array List of registry keys.
 */
function blueworx_public_private_page_keys() {
	$keys = array();

	foreach ( blueworx_public_pages() as $key => $page ) {
		if ( ! empty( $page['account'] ) || ! empty( $page['auth'] ) ) {
			$keys[] = $key;
		}
	}

	return (array) apply_filters( 'blueworx_public_private_page_keys', $keys );
}

/**
 * IDs of the pages that must never appear in search results.
 *
 * Resolved through the stored map, so a page the admin has renamed or moved is
 * still recognised — the same rule the rest of the public layer follows.
 *
 * @return array List of page IDs.
 */
function blueworx_public_private_page_ids() {
	$map = (array) get_option( 'blueworx_public_page_ids', array() );
	$ids = array();

	foreach ( blueworx_public_private_page_keys() as $key ) {
		if ( isset( $map[ $key ] ) && (int) $map[ $key ] > 0 ) {
			$ids[] = (int) $map[ $key ];
		}
	}

	return $ids;
}

/**
 * Writes the SureRank noindex setting onto every private page.
 *
 * Called from blueworx_public_install_pages() so it runs on activation and on
 * the first request after any update, alongside the page creation it belongs
 * with. Idempotent: a page that already says 'yes' is not written again, which
 * matters because SureRank invalidates its cached sitemap on every write to
 * this key.
 *
 * @return void
 */
function blueworx_public_mark_private_pages() {
	foreach ( blueworx_public_private_page_ids() as $page_id ) {
		if ( 'yes' === (string) get_post_meta( $page_id, BLUEWORX_SURERANK_NOINDEX_META, true ) ) {
			continue;
		}

		update_post_meta( $page_id, BLUEWORX_SURERANK_NOINDEX_META, 'yes' );
	}
}

/**
 * Whether the current request is one of the private pages.
 *
 * QUERY-TIME ONLY — it reads the queried object.
 *
 * @return bool True when the current page must not be indexed.
 */
function blueworx_public_is_private_page() {
	if ( is_admin() || ! is_page() ) {
		return false;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	return in_array( (int) $post->ID, blueworx_public_private_page_ids(), true );
}

/**
 * Adds noindex to WordPress's own robots tag on the private pages.
 *
 * The fallback, not the main mechanism — see this file's header. Registered on
 * `plugins_loaded` at priority 1, BEFORE SureRank loads and calls
 * remove_all_filters( 'wp_robots' ), specifically so that SureRank takes this
 * one away with the rest. That is the whole point: with SureRank active, core
 * prints nothing and SureRank prints the single tag; without it, core prints
 * the single tag from here. Registering later would survive the removal and
 * put the site back to two contradictory tags, which is the bug.
 *
 * @param array $robots Robots directives.
 * @return array Filtered directives.
 */
function blueworx_public_robots_noindex( $robots ) {
	if ( ! blueworx_public_is_private_page() ) {
		return $robots;
	}

	$robots['noindex']  = true;
	$robots['nofollow'] = true;

	unset( $robots['index'], $robots['follow'] );

	return $robots;
}

/**
 * Registers the robots fallback early enough for SureRank to remove it.
 *
 * @return void
 */
function blueworx_public_register_robots_fallback() {
	add_filter( 'wp_robots', 'blueworx_public_robots_noindex' );
}
add_action( 'plugins_loaded', 'blueworx_public_register_robots_fallback', 1 );

/**
 * Drops the private pages from WordPress's own sitemap.
 *
 * SureRank's sitemap is handled by the meta written above. This is the same
 * answer for the sitemap core serves when no SEO plugin is installed.
 *
 * @param array  $args      Query args for the sitemap provider.
 * @param string $post_type Post type being listed.
 * @return array Filtered args.
 */
function blueworx_public_sitemap_exclude_private( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}

	$ids = blueworx_public_private_page_ids();

	if ( ! $ids ) {
		return $args;
	}

	$existing            = isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array();
	$args['post__not_in'] = array_values( array_unique( array_merge( $existing, $ids ) ) );

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'blueworx_public_sitemap_exclude_private', 10, 2 );
