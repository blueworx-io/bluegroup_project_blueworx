<?php
/**
 * Journal index template (#94).
 *
 * Ported from Blog.dc.html: a tech hero, a row of category pills, one featured
 * article across the full width, then a grid of the rest.
 *
 * Everything on it is real. The design ships seven invented articles with
 * invented authors; this queries the site's own published posts and shows what
 * is there — including nothing, which is what a fresh install has and what the
 * empty state is for. There is deliberately no fallback set of example posts: a
 * journal listing articles nobody wrote is the one page on the site that cannot
 * be trusted.
 *
 * Filtering happens in the browser rather than by reloading with a query
 * parameter, matching the design's behaviour and avoiding a second problem:
 * blueworx_public_is_owned_request_path() treats any unrecognised query
 * parameter as reason to stop treating the request as ours, so /blog?cat=seo
 * would quietly lose its Site Protection exemption. Every card is in the
 * document and JS hides the ones that do not match — so the page still works,
 * unfiltered, with JS off.
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

// One page of articles, newest first. Capped rather than paginated: filtering
// happens in the browser, so a second page would silently filter only the posts
// that happen to be on it. A journal that outgrows this cap needs real
// pagination and server-side filtering, which is a change of shape rather than
// a bigger number.
$blueworx_blog_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => (int) apply_filters( 'blueworx_journal_posts_per_page', 24 ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

$blueworx_blog_posts = $blueworx_blog_query->posts;
$blueworx_blog_total = count( $blueworx_blog_posts );

// The featured slot goes to a sticky post where the site has chosen one, and to
// the newest article otherwise — so it is always filled without anyone having
// to curate it.
$blueworx_blog_featured = null;

foreach ( $blueworx_blog_posts as $blueworx_blog_post ) {
	if ( is_sticky( $blueworx_blog_post->ID ) ) {
		$blueworx_blog_featured = $blueworx_blog_post;
		break;
	}
}

if ( null === $blueworx_blog_featured && $blueworx_blog_posts ) {
	$blueworx_blog_featured = $blueworx_blog_posts[0];
}

// The pills are built from the categories actually in use, so a filter can
// never come up empty on first click and an unused category never appears.
$blueworx_blog_cats = array();

foreach ( $blueworx_blog_posts as $blueworx_blog_post ) {
	$blueworx_blog_term = blueworx_public_post_category( $blueworx_blog_post );

	if ( $blueworx_blog_term && ! isset( $blueworx_blog_cats[ $blueworx_blog_term->slug ] ) ) {
		$blueworx_blog_cats[ $blueworx_blog_term->slug ] = $blueworx_blog_term->name;
	}
}

$blueworx_blog_count_label = sprintf(
	/* translators: %d: number of published articles. */
	_n( '%d article', '%d articles', $blueworx_blog_total, 'bluegroup-project-blueworx' ),
	$blueworx_blog_total
);

blueworx_public_document_open( array( 'body_class' => 'bw-blog' ) );
blueworx_public_part( 'parts/nav.php' );
?>
<main id="content" tabindex="-1">
	<div>
		<section class="tech-hero">
			<div class="tech-inner jr-hero">
				<?php
				blueworx_public_part(
					'parts/tech-hero.php',
					array(
						'centered'        => false,
						'badge'           => __( 'The BlueWorx Journal', 'bluegroup-project-blueworx' ),
						'title'           => __( 'Practical notes on building & growing online', 'bluegroup-project-blueworx' ),
						'title_highlight' => __( 'building & growing', 'bluegroup-project-blueworx' ),
						'lead'            => __( 'What we learn running sites, stores and campaigns for our clients, written up plainly so you can use it.', 'bluegroup-project-blueworx' ),
						'meta'            => array(
							$blueworx_blog_count_label,
							__( 'Written by the team that does the work', 'bluegroup-project-blueworx' ),
							__( 'No fluff', 'bluegroup-project-blueworx' ),
						),
					)
				);
				?>
			</div>
		</section>

		<?php if ( ! $blueworx_blog_posts ) : ?>
			<section class="sec">
				<div class="wrap">
					<h2 class="bw-sr-only"><?php echo esc_html__( 'Articles', 'bluegroup-project-blueworx' ); ?></h2>
					<div class="jr-empty">
						<p class="jr-empty-title"><?php echo esc_html__( 'Nothing published yet', 'bluegroup-project-blueworx' ); ?></p>
						<p class="jr-empty-msg"><?php echo esc_html__( 'The first articles are being written. In the meantime, tell us what you would find useful and we will start there.', 'bluegroup-project-blueworx' ); ?></p>
						<a class="btn btn-brand btn-sm" href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php echo esc_html__( 'Get in touch', 'bluegroup-project-blueworx' ); ?></a>
					</div>
				</div>
			</section>
		<?php else : ?>

			<?php if ( $blueworx_blog_cats ) : ?>
				<section class="sec jr-filters-sec">
					<div class="wrap">
						<?php
						// A real group of toggle buttons rather than links:
						// nothing navigates, and aria-pressed is what tells a
						// screen reader which filter is currently on.
						?>
						<div class="jr-filters" role="group" aria-label="<?php echo esc_attr__( 'Filter articles by topic', 'bluegroup-project-blueworx' ); ?>" data-widget="journal-filter">
							<button type="button" class="jr-pill on" data-jr-filter="" aria-pressed="true"><?php echo esc_html__( 'All', 'bluegroup-project-blueworx' ); ?></button>
							<?php foreach ( $blueworx_blog_cats as $blueworx_blog_slug => $blueworx_blog_name ) : ?>
								<button type="button" class="jr-pill" data-jr-filter="<?php echo esc_attr( $blueworx_blog_slug ); ?>" aria-pressed="false"><?php echo esc_html( $blueworx_blog_name ); ?></button>
							<?php endforeach; ?>
							<?php
							// aria-live so the count is announced when it
							// changes: a sighted visitor sees the grid redraw,
							// and this is the equivalent of that.
							?>
							<?php
							// The singular and plural forms are handed to the
							// browser rather than assembled there. A count
							// label built in JS as `n + ' articles'` is a
							// string that cannot be translated and gets the
							// plural rule wrong in most languages that have
							// more than two.
							?>
							<span class="jr-count" data-jr-count aria-live="polite"
								data-jr-one="<?php echo esc_attr__( '%d article', 'bluegroup-project-blueworx' ); ?>"
								data-jr-many="<?php echo esc_attr__( '%d articles', 'bluegroup-project-blueworx' ); ?>"><?php echo esc_html( $blueworx_blog_count_label ); ?></span>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<section class="sec jr-list-sec">
				<div class="wrap">
					<h2 class="bw-sr-only"><?php echo esc_html__( 'Articles', 'bluegroup-project-blueworx' ); ?></h2>

					<?php if ( $blueworx_blog_featured ) : ?>
						<div class="jr-feat" data-jr-featured>
							<?php
							blueworx_public_part(
								'parts/journal-card.php',
								array(
									'post'     => $blueworx_blog_featured,
									'featured' => true,
								)
							);
							?>
						</div>
					<?php endif; ?>

					<div class="jr-grid">
						<?php
						foreach ( $blueworx_blog_posts as $blueworx_blog_post ) {
							if ( $blueworx_blog_featured && (int) $blueworx_blog_post->ID === (int) $blueworx_blog_featured->ID ) {
								continue;
							}

							blueworx_public_part( 'parts/journal-card.php', array( 'post' => $blueworx_blog_post ) );
						}
						?>
					</div>

					<?php
					// Hidden until a filter empties the grid. Rendered in the
					// document rather than built by JS so its copy stays
					// translatable and reviewable with the rest of the page.
					?>
					<div class="jr-empty jr-empty-filter" data-jr-empty hidden>
						<p class="jr-empty-title"><?php echo esc_html__( 'Nothing here yet', 'bluegroup-project-blueworx' ); ?></p>
						<p class="jr-empty-msg"><?php echo esc_html__( 'We haven’t published in this category so far. Try another topic.', 'bluegroup-project-blueworx' ); ?></p>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</div>
</main>
<?php
blueworx_public_part( 'parts/footer.php' );
blueworx_public_document_close();
