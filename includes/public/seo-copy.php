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
 * Tool pages are keyed "toolbox/<slug>" like the rest of the registry.
 *
 * @return array Registry key => array( title, description ).
 */
function blueworx_public_seo_copy() {
	$copy = array(
		'home'     => array(
			'title'       => __( 'Websites, Hosting and 12 Premium Tools in One Plan', 'bluegroup-project-blueworx' ),
			'description' => __( 'BlueWorx builds and looks after business websites, with hosting, support and twelve premium WordPress tools included in one straightforward monthly plan.', 'bluegroup-project-blueworx' ),
		),
		'services' => array(
			'title'       => __( 'Web Design, Managed Hosting and Ongoing Support', 'bluegroup-project-blueworx' ),
			'description' => __( 'Website design and build, managed hosting and ongoing support from one team. See exactly what each BlueWorx service covers and how a project actually runs.', 'bluegroup-project-blueworx' ),
		),
		'toolbox'  => array(
			'title'       => __( 'Twelve Premium WordPress Tools, One Subscription', 'bluegroup-project-blueworx' ),
			'description' => __( 'Twelve premium WordPress tools for forms, SEO, email, checkout, accessibility and AI, bundled into one subscription instead of twelve separate licences.', 'bluegroup-project-blueworx' ),
		),
		'pricing'  => array(
			'title'       => __( 'Plans and Pricing for Websites, Hosting and Tools', 'bluegroup-project-blueworx' ),
			'description' => __( 'Straightforward monthly plans covering your website, its hosting, your support and all twelve premium tools. Compare what each plan includes and what it costs.', 'bluegroup-project-blueworx' ),
		),
		'about'    => array(
			'title'       => __( 'Who We Are and How We Work With Clients', 'bluegroup-project-blueworx' ),
			'description' => __( 'BlueWorx is a small team building and looking after websites for growing businesses. How we work, what we care about, and who you actually deal with.', 'bluegroup-project-blueworx' ),
		),
		'work'     => array(
			'title'       => __( 'Client Projects and What They Changed', 'bluegroup-project-blueworx' ),
			'description' => __( 'Websites we have built and the businesses behind them: what the brief was, what we changed, and what it did for the people who have to run the thing.', 'bluegroup-project-blueworx' ),
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

	// The twelve tool pages. Written out here rather than generated from the
	// tagline in content.php: a description assembled from a sentence written
	// for a page heading reads like one, and comes out too short for the space
	// a search result gives it. The tagline says what the tool is; these say
	// why somebody searching would want it and that it comes with the plan.
	$tools = array(
		'sureforms'             => array(
			'title'       => __( 'SureForms — Forms That Convert, Without Code', 'bluegroup-project-blueworx' ),
			'description' => __( 'Build contact forms, quote requests and multi-step flows with conditional logic, payments and spam filtering. Included with every BlueWorx plan.', 'bluegroup-project-blueworx' ),
		),
		'surerank'              => array(
			'title'       => __( 'SureRank — Plain-English SEO for WordPress', 'bluegroup-project-blueworx' ),
			'description' => __( 'See what is holding your pages back in search, in plain English, with a health score and keyword tracking. Included with every BlueWorx plan.', 'bluegroup-project-blueworx' ),
		),
		'suremail'              => array(
			'title'       => __( 'SureMail — Email From Your Site That Arrives', 'bluegroup-project-blueworx' ),
			'description' => __( 'Reliable delivery for every order confirmation, form notification and password reset your site sends, with logs. Included with every BlueWorx plan.', 'bluegroup-project-blueworx' ),
		),
		'surewriter'            => array(
			'title'       => __( 'SureWriter — On-Brand Copy, Drafted in Seconds', 'bluegroup-project-blueworx' ),
			'description' => __( 'Draft page copy, product descriptions and marketing content that sounds like your business rather than a robot. Included with every BlueWorx plan.', 'bluegroup-project-blueworx' ),
		),
		'surecart'              => array(
			'title'       => __( 'SureCart — Modern Checkout and Subscriptions', 'bluegroup-project-blueworx' ),
			'description' => __( 'Sell products, services, downloads and subscriptions with a fast checkout that does not send buyers away. Included with every BlueWorx plan.', 'bluegroup-project-blueworx' ),
		),
		'zipwp'                 => array(
			'title'       => __( 'ZipWP — A Whole WordPress Site in Minutes', 'bluegroup-project-blueworx' ),
			'description' => __( 'Describe the business and get a complete WordPress site — pages, copy and images — to start from rather than a blank screen. Included with every plan.', 'bluegroup-project-blueworx' ),
		),
		'ottokit'               => array(
			'title'       => __( 'OttoKit — Automate the Jobs Nobody Wants', 'bluegroup-project-blueworx' ),
			'description' => __( 'Connect your website to the tools you already use and let the repetitive jobs run themselves, with no code. Included with every BlueWorx plan.', 'bluegroup-project-blueworx' ),
		),
		'ally'                  => array(
			'title'       => __( 'Ally — Make Your Site Usable by Everyone', 'bluegroup-project-blueworx' ),
			'description' => __( 'Ongoing accessibility improvements so visitors using a screen reader, a keyboard or a phone can all get where they need. Included with every plan.', 'bluegroup-project-blueworx' ),
		),
		'sweet-ai'              => array(
			'title'       => __( 'Sweet AI — A Second Read on Everything You Write', 'bluegroup-project-blueworx' ),
			'description' => __( 'An assistant that reviews your pages for clarity and impact and suggests what to cut, before a customer reads them. Included with every BlueWorx plan.', 'bluegroup-project-blueworx' ),
		),
		'elementor-ai-planner'  => array(
			'title'       => __( 'Elementor AI Planner — Plan the Site Before Building It', 'bluegroup-project-blueworx' ),
			'description' => __( 'Map out your pages, structure and navigation before a single one is built, so the site makes sense to visitors. Included with every BlueWorx plan.', 'bluegroup-project-blueworx' ),
		),
		'elementor'             => array(
			'title'       => __( 'Elementor — Change Your Own Pages, Safely', 'bluegroup-project-blueworx' ),
			'description' => __( 'The visual page builder behind every layout we design, so you can edit your own pages without touching code or breaking anything. Included with every plan.', 'bluegroup-project-blueworx' ),
		),
		'equalize-a11y-checker' => array(
			'title'       => __( 'Equalize — Catch Accessibility Problems as You Write', 'bluegroup-project-blueworx' ),
			'description' => __( 'Real-time WCAG checks that flag a missing alt text or an unreadable colour while the page is being edited, not months later. Included with every plan.', 'bluegroup-project-blueworx' ),
		),
	);

	foreach ( $tools as $blueworx_seo_slug => $blueworx_seo_entry ) {
		$copy[ 'toolbox/' . $blueworx_seo_slug ] = $blueworx_seo_entry;
	}

	return (array) apply_filters( 'blueworx_public_seo_copy', $copy );
}
