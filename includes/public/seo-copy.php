<?php
/**
 * Public front-end layer — the title and description of every page (#79).
 *
 * The site gave Google almost nothing. Five of the nine main pages had no
 * description at all, and the ones that did had a fragment scraped from the
 * middle of a sentence on the page — because the SEO plugin's default is
 * "generate the description from the content", and the content of a designed
 * marketing page is not a paragraph. Titles were the nav label and the site
 * name, which says what the page is called rather than what it offers.
 *
 * Every one of these is written by hand, page by page, and ships with the
 * plugin so a fresh install is never blank. They are DEFAULTS, not settings:
 * blueworx_public_apply_seo_copy() writes a value only where the site has none,
 * so anyone editing a page's SEO in wp-admin keeps their edit through every
 * later update.
 *
 * Descriptions are 140–160 characters. Shorter wastes the space Google gives
 * you; longer is cut off mid-word in the result.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The title and description for each page, keyed by registry key.
 *
 * @return array Registry key => array( title, description ).
 */
function blueworx_public_seo_copy() {
	$copy = array(
		'home'     => array(
			'title'       => __( 'Websites, Hosting, Support and ClubHouse, From One Team', 'bluegroup-project-blueworx' ),
			'description' => __( 'BlueWorx designs, builds and looks after business websites, with managed hosting, integrated support and ClubHouse, the club website platform, from one team.', 'bluegroup-project-blueworx' ),
		),
		'clubhouse' => array(
			'title'       => __( 'ClubHouse — The Website Platform for Sports Clubs', 'bluegroup-project-blueworx' ),
			'description' => __( 'A ready-made club website with memberships, teams, fixtures, bookings, events and a shop, on managed hosting from day one. £20 a month, live in two weeks.', 'bluegroup-project-blueworx' ),
		),
		'hosting'   => array(
			'title'       => __( 'Managed WordPress Hosting, One Price Per Site', 'bluegroup-project-blueworx' ),
			'description' => __( 'Fast managed WordPress hosting with daily backups, free SSL, a firewall, staging and monitoring handled for you. £20 a month per site, and we move you for free.', 'bluegroup-project-blueworx' ),
		),
		'support'   => array(
			'title'       => __( 'Integrated Support — Design and Development on Retainer', 'bluegroup-project-blueworx' ),
			'description' => __( 'Nine support packages from £100 a month. Buy a block of hours and spend them on design, development, content, SEO or fixes, with the whole BlueWorx team behind them.', 'bluegroup-project-blueworx' ),
		),
		'about'    => array(
			'title'       => __( 'Who We Are and How We Work With Clients', 'bluegroup-project-blueworx' ),
			'description' => __( 'BlueWorx is a small team building and looking after websites for growing businesses. How we work, what we care about, and who you actually deal with.', 'bluegroup-project-blueworx' ),
		),
		'portfolio' => array(
			'title'       => __( 'Our Portfolio — Websites We Have Built', 'bluegroup-project-blueworx' ),
			'description' => __( 'Eleven live websites BlueWorx designed, built and looks after, from sports governing bodies and tutoring companies to lighting suppliers and studios. Visit each one.', 'bluegroup-project-blueworx' ),
		),
		'ai'       => array(
			'title'       => __( 'Practical AI for Your Website, Without the Hype', 'bluegroup-project-blueworx' ),
			'description' => __( 'Where AI genuinely helps a business website — writing, planning, support and search — and where it does not. Practical uses, already built into your plan.', 'bluegroup-project-blueworx' ),
		),
		'contact'  => array(
			'title'       => __( 'Talk to Us About Your Website', 'bluegroup-project-blueworx' ),
			'description' => __( 'Tell us where your website is holding the business back and we will show you what to fix. A reply within one business day, no obligation and no sales script.', 'bluegroup-project-blueworx' ),
		),
		'blog'     => array(
			'title'       => __( 'The BlueWorx Journal — Notes on Running a Site', 'bluegroup-project-blueworx' ),
			'description' => __( 'What we learn running websites, stores and campaigns for clients: speed, checkout, SEO, automation and accessibility, written plainly enough to act on.', 'bluegroup-project-blueworx' ),
		),
	);

	return (array) apply_filters( 'blueworx_public_seo_copy', $copy );
}
