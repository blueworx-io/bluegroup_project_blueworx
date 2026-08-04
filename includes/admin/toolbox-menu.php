<?php
/**
 * Admin — the Toolbox screen.
 *
 * The twelve Toolbox tools are defined in blueworx_content_tools(), and the
 * plugin creates a page for each so /toolbox/<slug> resolves and renders
 * through templates/pages/single-tool.php. Those pages carry no content of
 * their own: the body is empty and every word on the rendered page comes from
 * the registry.
 *
 * So they were twelve rows in the Pages list that could not usefully be edited,
 * in the one screen somebody opens looking for a page they *can* change. This
 * file gives them a screen of their own and takes them out of that list.
 *
 * Read-only by design. A tool is added or reworded in content.php and ships
 * with a release, which keeps the wording in git, reviewed, and restored by
 * reinstalling the plugin. Moving it into the database was considered and
 * declined — see docs/superpowers/specs/2026-08-04-toolbox-admin-menu-design.md.
 *
 * Loaded only in wp-admin, from the main plugin file.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BLUEWORX_TOOLBOX_MENU_SLUG = 'blueworx-toolbox';

/**
 * Adds the Toolbox item to the admin sidebar.
 *
 * Sits directly below Pages (20) rather than at the bottom of the menu: the
 * tools are pages in everything but where they are edited, and somebody who has
 * just failed to find one under Pages should see this without scrolling.
 *
 * @return void
 */
function blueworx_toolbox_menu_register() {
	add_menu_page(
		__( 'Toolbox', 'bluegroup-project-blueworx' ),
		__( 'Toolbox', 'bluegroup-project-blueworx' ),
		'edit_pages',
		BLUEWORX_TOOLBOX_MENU_SLUG,
		'blueworx_toolbox_menu_render',
		'dashicons-screenoptions',
		21
	);
}
add_action( 'admin_menu', 'blueworx_toolbox_menu_register' );

/**
 * The IDs of the pages this plugin created for the Toolbox tools.
 *
 * Two facts have to agree before a page counts as a tool page, and both are
 * ones the plugin already keeps:
 *
 * 1. It is mapped in `blueworx_public_page_ids` under a `toolbox/<slug>` key,
 *    which is how blueworx_public_pages() registers the tools.
 * 2. It carries the ownership stamp, so it is a page this plugin created.
 *
 * The stamp is the important half. A site can have its own page at a colliding
 * slug, and hiding somebody's own content from their own Pages list because it
 * happens to be named after a tool would be a genuinely bad failure — it looks
 * exactly like the page having been deleted. This is the same rule
 * blueworx_public_install_pages() applies when it refuses to adopt a page it
 * did not create.
 *
 * @return int[] Page IDs, possibly empty.
 */
function blueworx_toolbox_menu_page_ids() {
	$map = (array) get_option( 'blueworx_public_page_ids', array() );
	$ids = array();

	foreach ( $map as $key => $id ) {
		if ( 0 !== strpos( (string) $key, 'toolbox/' ) ) {
			continue;
		}

		$id = (int) $id;

		if ( $id && function_exists( 'blueworx_public_page_is_ours' ) && blueworx_public_page_is_ours( $id ) ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

/**
 * Takes the tool pages out of the Pages list.
 *
 * Scoped as tightly as it can be: the Pages list screen, in wp-admin, main
 * query only. Everything else — menu building, the block editor's page lookups,
 * wp_list_pages(), the front end — still sees the pages, because they are real
 * published pages and hiding them anywhere else would break navigation and
 * permalinks rather than tidy a list.
 *
 * @param WP_Query $query The query about to run.
 * @return void
 */
function blueworx_toolbox_menu_hide_tool_pages( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'edit-page' !== $screen->id ) {
		return;
	}

	$ids = blueworx_toolbox_menu_page_ids();

	if ( ! $ids ) {
		return;
	}

	// Merged, never assigned. Another plugin — or a later version of this one —
	// may already be excluding something, and overwriting the list would
	// silently undo it.
	$query->set( 'post__not_in', array_merge( (array) $query->get( 'post__not_in' ), $ids ) );
}
add_action( 'pre_get_posts', 'blueworx_toolbox_menu_hide_tool_pages' );

/**
 * The page a tool renders on, if it has one yet.
 *
 * A tool can be in the registry without a page — the registry shipped in a
 * release that has been installed, but activation has not run since. Its row
 * then shows the tool with no link, which is the truth, rather than a link to a
 * 404.
 *
 * @param string $slug Tool slug.
 * @return string Permalink, or '' when the page does not exist.
 */
function blueworx_toolbox_menu_tool_url( $slug ) {
	$map = (array) get_option( 'blueworx_public_page_ids', array() );
	$key = 'toolbox/' . $slug;

	if ( ! isset( $map[ $key ] ) ) {
		return '';
	}

	$url = get_permalink( (int) $map[ $key ] );

	return is_string( $url ) ? $url : '';
}

/**
 * Renders the Toolbox screen.
 *
 * Writes nothing, so there is no form, no nonce and no capability check beyond
 * the one add_menu_page() already enforces on the menu item.
 *
 * @return void
 */
function blueworx_toolbox_menu_render() {
	$tools = function_exists( 'blueworx_content_tools' ) ? (array) blueworx_content_tools() : array();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Toolbox', 'bluegroup-project-blueworx' ); ?></h1>

		<p class="description">
			<?php echo esc_html__( 'The tools shown on the Toolbox page. They are part of the plugin, so adding one or changing its wording is a code change that ships with a release — there is nothing to edit here.', 'bluegroup-project-blueworx' ); ?>
		</p>

		<?php if ( ! $tools ) : ?>
			<p><?php echo esc_html__( 'No tools are defined.', 'bluegroup-project-blueworx' ); ?></p>
		<?php else : ?>
			<table class="widefat striped blueworx-toolbox-list">
				<thead>
					<tr>
						<th scope="col"><?php echo esc_html__( 'Tool', 'bluegroup-project-blueworx' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Category', 'bluegroup-project-blueworx' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Address', 'bluegroup-project-blueworx' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'View', 'bluegroup-project-blueworx' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tools as $blueworx_tool ) : ?>
						<?php
						$blueworx_slug = isset( $blueworx_tool['slug'] ) ? (string) $blueworx_tool['slug'] : '';
						$blueworx_url  = '' === $blueworx_slug ? '' : blueworx_toolbox_menu_tool_url( $blueworx_slug );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( isset( $blueworx_tool['name'] ) ? $blueworx_tool['name'] : $blueworx_slug ); ?></strong>
								<?php if ( ! empty( $blueworx_tool['desc'] ) ) : ?>
									<br /><span class="description"><?php echo esc_html( $blueworx_tool['desc'] ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( isset( $blueworx_tool['category'] ) ? $blueworx_tool['category'] : '' ); ?></td>
							<td><code><?php echo esc_html( '/toolbox/' . $blueworx_slug ); ?></code></td>
							<td>
								<?php if ( '' === $blueworx_url ) : ?>
									<span class="description"><?php echo esc_html__( 'No page yet', 'bluegroup-project-blueworx' ); ?></span>
								<?php else : ?>
									<a class="blueworx-toolbox-view" href="<?php echo esc_url( $blueworx_url ); ?>"><?php echo esc_html__( 'View', 'bluegroup-project-blueworx' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}
