<?php
/**
 * Single journal article template (#95).
 *
 * Ported from Post.dc.html: a dark hero carrying the breadcrumb, title,
 * standfirst and byline; the cover image lifted up to overlap it; then the
 * article beside a sticky contents-and-CTA column, the share row, the author
 * box, and a "keep reading" row.
 *
 * This is the one template in the public layer that renders content the plugin
 * did not write. Everything structural is ours; everything inside .bw-post-body
 * is the author's, passed through the_content() with its filters intact so
 * blocks, embeds and shortcodes behave exactly as they do anywhere else in
 * WordPress. The plugin styles that output — it does not parse or rewrite it.
 *
 * The design's fixed thirteen-block article body, its invented author bio and
 * its hand-written contents list are all data here: the body is whatever was
 * published, the bio is the author's WordPress description (and the box is
 * dropped when there is none), and the contents list is built in the browser
 * from the headings the article actually has.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

blueworx_public_document_open( array( 'body_class' => 'bw-post' ) );
blueworx_public_part( 'parts/nav.php' );

while ( have_posts() ) :
	the_post();

	$blueworx_post_id       = get_the_ID();
	$blueworx_post_term     = blueworx_public_post_category( $blueworx_post_id );
	$blueworx_post_author   = (int) get_post_field( 'post_author', $blueworx_post_id );
	$blueworx_post_name     = (string) get_the_author_meta( 'display_name', $blueworx_post_author );
	$blueworx_post_initials = blueworx_public_initials( $blueworx_post_name );
	$blueworx_post_bio      = trim( (string) get_the_author_meta( 'description', $blueworx_post_author ) );
	$blueworx_post_standf   = trim( (string) get_the_excerpt() );
	$blueworx_post_url      = (string) get_permalink();

	// Related reading: the same category first, because that is what "keep
	// reading" means to somebody who just finished this one. Falls back to the
	// most recent articles on a site whose posts are not categorised, rather
	// than rendering an empty row with a heading over it.
	$blueworx_post_related_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'post__not_in'        => array( $blueworx_post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	if ( $blueworx_post_term ) {
		$blueworx_post_related_args['cat'] = (int) $blueworx_post_term->term_id;
	}

	$blueworx_post_related = get_posts( $blueworx_post_related_args );

	if ( ! $blueworx_post_related && $blueworx_post_term ) {
		unset( $blueworx_post_related_args['cat'] );
		$blueworx_post_related = get_posts( $blueworx_post_related_args );
	}
	?>
<main id="content" tabindex="-1">
	<div>
		<article class="jp">
			<section class="tech-hero">
				<div class="tech-inner jp-hero-inner">
					<nav class="jp-crumbs" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'bluegroup-project-blueworx' ); ?>">
						<a href="<?php echo esc_url( blueworx_public_journal_url() ); ?>"><?php echo esc_html__( 'Journal', 'bluegroup-project-blueworx' ); ?></a>
						<?php if ( $blueworx_post_term ) : ?>
							<span aria-hidden="true">/</span>
							<span><?php echo esc_html( $blueworx_post_term->name ); ?></span>
						<?php endif; ?>
					</nav>

					<h1 class="h1 jp-title"><?php the_title(); ?></h1>

					<?php if ( '' !== $blueworx_post_standf ) : ?>
						<p class="lead jp-standfirst"><?php echo esc_html( $blueworx_post_standf ); ?></p>
					<?php endif; ?>

					<div class="jp-byline">
						<?php if ( '' !== $blueworx_post_initials ) : ?>
							<span class="jp-avatar" aria-hidden="true"><?php echo esc_html( $blueworx_post_initials ); ?></span>
						<?php endif; ?>
						<span class="jp-byline-who"><?php echo esc_html( $blueworx_post_name ); ?></span>
						<span class="jp-byline-when">
							<time datetime="<?php echo esc_attr( (string) get_the_date( 'c' ) ); ?>"><?php echo esc_html( (string) get_the_date() ); ?></time>
							<span aria-hidden="true">·</span>
							<?php echo esc_html( blueworx_public_read_label( $blueworx_post_id ) ); ?>
						</span>
					</div>
				</div>
			</section>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="jp-cover">
					<div class="jp-cover-frame">
						<?php
						// The one image on the page that is genuinely
						// content, so it keeps the alt text the author set
						// rather than being marked decorative like the cards.
						the_post_thumbnail( 'large' );
						?>
					</div>
					<?php if ( '' !== trim( (string) get_the_post_thumbnail_caption() ) ) : ?>
						<p class="jp-cover-cap"><?php echo esc_html( (string) get_the_post_thumbnail_caption() ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<section class="sec jp-body-sec">
				<div class="jp-body-grid">
					<div class="bw-post-body" data-jp-body>
						<?php the_content(); ?>

						<div class="jp-share">
							<span class="jp-share-label"><?php echo esc_html__( 'Share', 'bluegroup-project-blueworx' ); ?></span>

							<?php
							// Real links, opened in a new tab — a share that
							// navigates the reader away from the article they
							// are halfway through is a share nobody uses. Only
							// the copy-to-clipboard control needs JS, and it is
							// the only one rendered as a button.
							?>
							<a class="jp-share-btn"
								href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $blueworx_post_url ) ); ?>"
								target="_blank" rel="noopener noreferrer"
								aria-label="<?php echo esc_attr__( 'Share this article on LinkedIn', 'bluegroup-project-blueworx' ); ?>">
								<?php blueworx_icon( 'linkedin' ); ?>
							</a>
							<a class="jp-share-btn"
								href="<?php echo esc_url( 'https://x.com/intent/post?url=' . rawurlencode( $blueworx_post_url ) . '&text=' . rawurlencode( get_the_title() ) ); ?>"
								target="_blank" rel="noopener noreferrer"
								aria-label="<?php echo esc_attr__( 'Share this article on X', 'bluegroup-project-blueworx' ); ?>">
								<?php blueworx_icon( 'x' ); ?>
							</a>
							<button type="button" class="jp-share-btn" data-jp-copy="<?php echo esc_url( $blueworx_post_url ); ?>" aria-label="<?php echo esc_attr__( 'Copy a link to this article', 'bluegroup-project-blueworx' ); ?>">
								<?php blueworx_icon( 'link' ); ?>
							</button>
							<?php // The confirmation text comes from PHP so it is translated with the rest of the page rather than hard-coded in the script. ?>
							<span class="jp-copied" data-jp-copied aria-live="polite" data-jp-copied-label="<?php echo esc_attr__( 'Link copied', 'bluegroup-project-blueworx' ); ?>"></span>
						</div>

						<?php if ( '' !== $blueworx_post_bio ) : ?>
							<div class="jp-author">
								<?php if ( '' !== $blueworx_post_initials ) : ?>
									<span class="jp-author-avatar" aria-hidden="true"><?php echo esc_html( $blueworx_post_initials ); ?></span>
								<?php endif; ?>
								<div class="jp-author-text">
									<p class="jp-author-name"><?php echo esc_html( $blueworx_post_name ); ?></p>
									<p class="jp-author-bio"><?php echo esc_html( $blueworx_post_bio ); ?></p>
								</div>
								<a class="btn btn-outline btn-sm" href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php echo esc_html__( 'Get in touch', 'bluegroup-project-blueworx' ); ?></a>
							</div>
						<?php endif; ?>
					</div>

					<aside class="jp-aside">
						<?php
						// Empty and hidden until JS finds headings to fill it
						// with. An article of straight prose has none, and a
						// contents box listing nothing is worse than no box.
						?>
						<nav class="jp-toc" data-jp-toc hidden aria-labelledby="jp-toc-title">
							<p class="jp-toc-title" id="jp-toc-title"><?php echo esc_html__( 'On this page', 'bluegroup-project-blueworx' ); ?></p>
							<ul class="jp-toc-list" data-jp-toc-list></ul>
						</nav>

						<div class="jp-cta">
							<p class="jp-cta-kicker"><?php echo esc_html__( 'Free review', 'bluegroup-project-blueworx' ); ?></p>
							<p class="jp-cta-text"><?php echo esc_html__( 'Want us to look at your site and tell you what we’d change?', 'bluegroup-project-blueworx' ); ?></p>
							<a class="btn btn-brand btn-sm" href="<?php echo esc_url( home_url( '/contact' ) ); ?>"><?php echo esc_html__( 'Book a call', 'bluegroup-project-blueworx' ); ?></a>
						</div>
					</aside>
				</div>
			</section>

			<?php if ( $blueworx_post_related ) : ?>
				<section class="sec jp-related-sec">
					<div class="wrap">
						<div class="jp-related-head">
							<h2 class="h2"><?php echo esc_html__( 'Keep reading', 'bluegroup-project-blueworx' ); ?></h2>
							<a class="jp-related-all" href="<?php echo esc_url( blueworx_public_journal_url() ); ?>"><?php echo esc_html__( 'All articles', 'bluegroup-project-blueworx' ); ?></a>
						</div>
						<div class="jr-grid">
							<?php
							foreach ( $blueworx_post_related as $blueworx_post_rel ) {
								blueworx_public_part(
									'parts/journal-card.php',
									array(
										'post'    => $blueworx_post_rel,
										'compact' => true,
									)
								);
							}
							?>
						</div>
					</div>
				</section>
			<?php endif; ?>
		</article>
	</div>
</main>
	<?php
endwhile;

blueworx_public_part( 'parts/footer.php' );
blueworx_public_document_close();
