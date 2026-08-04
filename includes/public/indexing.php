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
 * SureRank's per-post general settings.
 *
 * Where its per-page title and description live — NOT, as the naming of the
 * noindex key above suggests, in `surerank_settings_page_title` and
 * `surerank_settings_page_description`. Those keys exist nowhere: SureRank
 * writes the whole "general" group as one array under this single key, and
 * reads it back the same way, so a plausible-looking per-field key is simply
 * ignored. Verified against SureRank 1.9.3 installed locally, because the first
 * version of this wrote the per-field keys, changed nothing, and looked right.
 */
if ( ! defined( 'BLUEWORX_SURERANK_GENERAL_META' ) ) {
	define( 'BLUEWORX_SURERANK_GENERAL_META', 'surerank_settings_general' );
}

/**
 * A page's stored SEO title and description, whoever wrote them.
 *
 * @param int $page_id Page ID.
 * @return array array( title, description ), either possibly empty.
 */
function blueworx_public_stored_seo_copy( $page_id ) {
	$general = get_post_meta( (int) $page_id, BLUEWORX_SURERANK_GENERAL_META, true );
	$general = is_array( $general ) ? $general : array();

	return array(
		'title'       => isset( $general['page_title'] ) ? (string) $general['page_title'] : '',
		'description' => isset( $general['page_description'] ) ? (string) $general['page_description'] : '',
	);
}

/**
 * Writes the shipped title and description onto every page that has none.
 *
 * Deliberately only where the site has none. These are defaults the plugin
 * ships so a fresh install is not blank; the moment somebody writes their own
 * in wp-admin, theirs is the answer and no later update touches it. The rest of
 * the group is read and written back untouched, so this cannot wipe a canonical
 * URL or focus keyword somebody set alongside.
 *
 * @return void
 */
function blueworx_public_apply_seo_copy() {
	$map  = (array) get_option( 'blueworx_public_page_ids', array() );
	$copy = blueworx_public_seo_copy();

	foreach ( $copy as $key => $entry ) {
		if ( ! isset( $map[ $key ] ) || (int) $map[ $key ] <= 0 ) {
			continue;
		}

		$page_id = (int) $map[ $key ];
		$general = get_post_meta( $page_id, BLUEWORX_SURERANK_GENERAL_META, true );
		$general = is_array( $general ) ? $general : array();
		$before  = $general;

		if ( '' === trim( isset( $general['page_title'] ) ? (string) $general['page_title'] : '' ) ) {
			$general['page_title'] = $entry['title'];
		}

		if ( '' === trim( isset( $general['page_description'] ) ? (string) $general['page_description'] : '' ) ) {
			$general['page_description'] = $entry['description'];
		}

		// Idempotent: SureRank invalidates its cached sitemap on every write to
		// its meta, so writing an unchanged value has a real cost.
		if ( $general !== $before ) {
			update_post_meta( $page_id, BLUEWORX_SURERANK_GENERAL_META, $general );
		}
	}
}

/**
 * The shipped title and description for the current request, or null.
 *
 * Reads the stored meta first so an admin's own wording wins, and falls back
 * to what the plugin ships. Rename-robust for the same reason as everything
 * else in the public layer: the answer comes from the ID map, not the slug.
 *
 * @return array|null array( title, description ), or null when not one of ours.
 */
function blueworx_public_current_seo_copy() {
	if ( is_admin() || ! is_page() ) {
		return null;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return null;
	}

	$map = (array) get_option( 'blueworx_public_page_ids', array() );
	$key = array_search( (int) $post->ID, array_map( 'intval', $map ), true );

	if ( false === $key ) {
		return null;
	}

	$copy   = blueworx_public_seo_copy();
	$stored = blueworx_public_stored_seo_copy( $post->ID );

	$title       = $stored['title'];
	$description = $stored['description'];

	if ( '' === trim( $title ) ) {
		$title = isset( $copy[ $key ]['title'] ) ? $copy[ $key ]['title'] : '';
	}

	if ( '' === trim( $description ) ) {
		$description = isset( $copy[ $key ]['description'] ) ? $copy[ $key ]['description'] : '';
	}

	if ( '' === trim( $title ) && '' === trim( $description ) ) {
		return null;
	}

	return array(
		'title'       => $title,
		'description' => $description,
	);
}

/**
 * Whether this plugin should print the title and description itself.
 *
 * Only when there is no SEO plugin to do it. SureRank prints its own from the
 * meta written above, and printing a second set alongside is exactly the fault
 * fixed for the robots tag: two answers to one question on the same page.
 *
 * @return bool True when the plugin must print them.
 */
function blueworx_public_owns_seo_output() {
	return (bool) apply_filters( 'blueworx_public_owns_seo_output', ! defined( 'SURERANK_VERSION' ) );
}

/**
 * Uses the page's own title rather than "<nav label> - <site name>".
 *
 * @param string $title Title WordPress built.
 * @return string
 */
function blueworx_public_document_title( $title ) {
	if ( ! blueworx_public_owns_seo_output() ) {
		return $title;
	}

	$copy = blueworx_public_current_seo_copy();

	if ( null === $copy || '' === trim( $copy['title'] ) ) {
		return $title;
	}

	return $copy['title'] . ' - ' . get_bloginfo( 'name' );
}
add_filter( 'pre_get_document_title', 'blueworx_public_document_title', 20 );

/**
 * Prints the description, and the Open Graph and Twitter tags that repeat it.
 *
 * Same text in all three on purpose: they are answering the same question for
 * three different readers, and the live site's problem was never that the
 * wording differed — it was that there was none.
 *
 * @return void
 */
function blueworx_public_print_seo_meta() {
	if ( ! blueworx_public_owns_seo_output() ) {
		return;
	}

	$copy = blueworx_public_current_seo_copy();

	if ( null === $copy ) {
		return;
	}

	$title       = '' === trim( $copy['title'] ) ? wp_get_document_title() : $copy['title'];
	$description = $copy['description'];

	if ( '' !== trim( $description ) ) {
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
		printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $description ) );
	}

	printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
	printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:type" content="website" />' . "\n" );
	printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( home_url( add_query_arg( array() ) ) ) );
	printf( '<meta name="twitter:card" content="summary_large_image" />' . "\n" );
}
add_action( 'wp_head', 'blueworx_public_print_seo_meta', 2 );

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
