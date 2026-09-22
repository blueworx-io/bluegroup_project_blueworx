<?php
/**
 * Public front-end layer — content data accessors.
 *
 * Every marketing page draws its copy and pricing from these accessors
 * instead of hard-coding it in a template. The Toolbox — twelve tools, their
 * solo prices and three subscription plans — was removed in 1.17.0; ClubHouse
 * took its place everywhere it was mentioned.
 *
 * Each accessor wraps its return value in
 * apply_filters( 'blueworx_content_<name>', $array ) so a later cycle can
 * override the content without editing this file.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A package's annual hours as a monthly figure, for display.
 *
 * Most packages do not divide evenly by twelve (75 hours is 6.25 a month),
 * so this keeps up to two decimals and drops any trailing zeros: 24 → "2",
 * 75 → "6.25", 140 → "11.67". The same rounding is applied in the browser
 * by the Support page's slider (assets/js/public-widgets.js).
 *
 * @param int $hours Hours a year.
 * @return string Hours a month.
 */
function blueworx_content_hours_a_month( $hours ) {
	return rtrim( rtrim( number_format( (int) $hours / 12, 2, '.', '' ), '0' ), '.' );
}

/**
 * The nine Integrated Support packages.
 *
 * Hours are the ANNUAL allowance; the price is what the client pays every
 * month, in GBP. priceA equals priceM — packages have no annual-billing
 * discount — and is kept so the plan-card part and the SureCart wiring (which
 * key on priceM/priceA) work unchanged. `featured` marks the three shown as
 * cards on the Support page; `feat` (the highlighted card) is Growth.
 *
 * Runs through the `blueworx_content_retainer_plans` filter so SureCart price
 * IDs configured per package (includes/public/commerce.php) still apply.
 *
 * @return array List of plan arrays (see blueworx_content_support_package()).
 */
function blueworx_content_support_packages() {
	$rows = array(
		// name, annual hours, £/month, blurb, featured. The hours and prices
		// mirror the catalogue in the Forge admin (2026-09).
		array( 'Starter', 24, 100, 'Keeping a simple site current — small edits, updates and the odd fix.', true ),
		array( 'Launch', 48, 200, 'A day a month of design and development time for a growing site.', false ),
		array( 'Scale', 75, 300, 'Steady improvement work alongside the everyday maintenance.', false ),
		array( 'Enhance', 105, 400, 'Regular new pages, campaigns and feature work on top of upkeep.', false ),
		array( 'Growth', 140, 500, 'A consistent programme of design, build and optimisation each month.', true ),
		array( 'Enterprise', 225, 750, 'A standing slice of the team for multi-site or multi-brand operations.', false ),
		array( 'Enterprise +', 320, 1000, 'Larger roadmaps with parallel workstreams and priority turnaround.', true ),
		array( 'Advantage', 430, 1250, 'A near-full-time partner embedded in your product and marketing.', false ),
		array( 'Advantage +', 600, 1500, 'Our deepest engagement — the whole team, on call, all year.', false ),
	);

	// The three featured packages carry hand-written feature lists; the rest
	// derive theirs from the numbers so every card has five lines.
	$written = array(
		'Starter'      => array( 'Content and small design edits', 'Core, plugin and theme updates', 'Email support, 2 business days', 'Monthly summary of hours used' ),
		'Growth'       => array( 'Design and development time', 'New pages, campaigns and fixes', 'Priority response, 1 business day', 'Quarterly review and roadmap' ),
		'Enterprise +' => array( 'Parallel design and build workstreams', 'Multi-site and multi-brand cover', 'Same-day response on urgent work', 'Named lead and monthly reporting' ),
	);
	$generic = array( 'Design and development time', 'Fixes, content and small changes', 'Core, plugin and theme updates', 'Hours logged and reported monthly' );

	$plans = array();

	foreach ( $rows as $row ) {
		list( $name, $hours, $gbp, $blurb, $featured ) = $row;

		$first = sprintf(
			/* translators: 1: hours a year, 2: hours a month. */
			__( '%1$d hours a year (%2$s a month)', 'bluegroup-project-blueworx' ),
			$hours,
			blueworx_content_hours_a_month( $hours )
		);
		$rest = isset( $written[ $name ] ) ? $written[ $name ] : $generic;

		$plans[] = array(
			'name'     => $name,
			'desc'     => $blurb,
			'blurb'    => $blurb,
			'hours'    => $hours,
			'priceM'   => $gbp,
			'priceA'   => $gbp,
			'currency' => 'GBP',
			'feat'     => 'Growth' === $name,
			'pop'      => 'Growth' === $name,
			'featured' => $featured,
			'features' => array_merge( array( $first ), $rest ),
		);
	}

	/**
	 * Filters the support packages. Historically named for the three retainer
	 * plans these replaced; the name is kept so configured SureCart hooks
	 * still fire.
	 *
	 * @param array $plans The 9 package arrays.
	 */
	return apply_filters( 'blueworx_content_retainer_plans', $plans );
}

/**
 * The support packages under the name the commerce and admin code use.
 *
 * @return array See blueworx_content_support_packages().
 */
function blueworx_content_retainer_plans() {
	return blueworx_content_support_packages();
}

/**
 * The Integrated Support FAQ.
 *
 * @return array List of array( q, a ).
 */
function blueworx_content_support_faqs() {
	$faqs = array(
		array(
			'q' => 'Do unused hours roll over?',
			'a' => 'Your allowance is annual, so a quiet month simply leaves more in the pot for a busy one. Hours do not carry past the end of your support year.',
		),
		array(
			'q' => 'What if we go over our hours?',
			'a' => 'We tell you before you get there, not after. You can either move up a package or approve extra hours at your package rate for that piece of work.',
		),
		array(
			'q' => 'How quickly do you respond?',
			'a' => 'Two business days on the smaller packages, one business day from Growth upwards, and same-day on Enterprise + and above. Anything that takes a site offline is treated as urgent on every package.',
		),
		array(
			'q' => 'Can we change package mid-year?',
			'a' => 'Yes. Move up at any time and the new allowance applies from that month. Moving down takes effect at your next renewal.',
		),
		array(
			'q' => 'Is hosting included?',
			'a' => 'Hosting is separate at £20 a month or £200 a year per site, so you only pay for it on the sites we host. Support packages work with sites hosted anywhere.',
		),
	);

	/**
	 * Filters the support FAQ list.
	 *
	 * @param array $faqs List of array( q, a ).
	 */
	return apply_filters( 'blueworx_content_support_faqs', $faqs );
}

/**
 * The pricing FAQ list.
 *
 * Ported verbatim from lib/data.ts FAQS.
 *
 * @return array List of array( q, a ).
 */
function blueworx_content_faqs() {
	$faqs = array(
		array(
			'q' => 'How do payments work?',
			'a' => 'Pay and forget! Annual payments mean more time spent on your business and less time managing subscriptions. Choose monthly or annual billing at checkout, and you can switch at any point.',
		),
		array(
			'q' => 'How do I get started?',
			'a' => 'Pick a plan, create your account, and our team helps you onboard step by step. Most websites are designed, built, and live within a few days.',
		),
		array(
			'q' => 'Can I change my plan later?',
			'a' => 'Absolutely. Upgrade or downgrade at any time from your dashboard. Changes are prorated automatically so you only ever pay for what you use.',
		),
		array(
			'q' => 'Do I need to be a developer?',
			'a' => 'Not at all. BlueWorx is built for business owners. Our tools are no-code and our expert team handles anything technical on your behalf.',
		),
		array(
			'q' => 'Will I be able to edit my package?',
			'a' => 'Yes. Add sites, switch on ClubHouse modules, and adjust your support allowance whenever your needs change. Your package flexes with your business.',
		),
	);

	/**
	 * Filters the pricing FAQ list.
	 *
	 * @param array $faqs List of array( q, a ).
	 */
	return apply_filters( 'blueworx_content_faqs', $faqs );
}

/**
 * The Contact page's FAQ: what to expect from getting in touch.
 *
 * @return array List of array( q, a ).
 */
function blueworx_content_contact_faqs() {
	$faqs = array(
		array(
			'q' => 'How quickly will I hear back?',
			'a' => 'Within one business day, from the person who will actually work on your project — not an account manager reading from a script.',
		),
		array(
			'q' => 'Do you charge for the first conversation?',
			'a' => 'No. The first call is a proper look at your setup and an honest view on what would help. If that is not us, we will say so.',
		),
		array(
			'q' => 'What should I send you?',
			'a' => 'Your current website address, a sense of what is not working, and any deadline you are working to. That is enough for a useful first call.',
		),
		array(
			'q' => 'Do you work with clients outside the UK?',
			'a' => 'Yes. Most of our work is remote, and our hosting serves clients across the UK, Europe and further afield. Prices can be shown in pounds, euros, dollars, rand or dirhams.',
		),
		array(
			'q' => 'Can you take over a site someone else built?',
			'a' => 'Regularly. We audit what is there, tell you what is worth keeping, and quote for the rest before you commit to anything.',
		),
	);

	/**
	 * Filters the Contact page FAQ list.
	 *
	 * @param array $faqs List of array( q, a ).
	 */
	return apply_filters( 'blueworx_content_contact_faqs', $faqs );
}

/**
 * Everything the Managed Hosting page says: the plan, the performance and
 * security cards, the comparison table and the FAQ.
 *
 * @return array array( plan, perf, security, compare, faqs ).
 */
function blueworx_content_hosting() {
	$data = array(
		'plan'     => array(
			'name'     => 'Managed Hosting',
			'desc'     => 'Everything one WordPress site needs to stay fast, safe and online.',
			'priceM'   => 20,
			'priceA'   => 200,
			'currency' => 'GBP',
			'feat'     => true,
			'pop'      => __( 'Per site', 'bluegroup-project-blueworx' ),
			'subA'     => __( 'per year, billed annually', 'bluegroup-project-blueworx' ),
			'lbl'      => __( 'INCLUDED', 'bluegroup-project-blueworx' ),
			'features' => array(
				'Managed WordPress hosting for one site',
				'Free migration from your current host',
				'Daily off-site backups, 30-day history',
				'Free SSL and global CDN',
				'Firewall, malware scanning and clean-up',
				'Core, plugin and theme updates',
				'One-click staging environment',
				'24/7 monitoring and email support',
			),
		),
		'perf'     => array(
			array( 'stat' => '99.9%', 'name' => 'Uptime target', 'desc' => 'Monitored every minute from multiple regions, with alerts that reach a human, not a dashboard.' ),
			array( 'stat' => '<200ms', 'name' => 'Server response', 'desc' => 'Object and page caching tuned per site, so the first byte arrives before a visitor notices the wait.' ),
			array( 'stat' => 'NVMe', 'name' => 'Storage', 'desc' => 'All-flash NVMe disks and PHP 8 workers, not oversold spinning platters shared with 400 neighbours.' ),
			array( 'stat' => 'Global', 'name' => 'CDN included', 'desc' => 'Static assets served from the edge, so a visitor in Sydney gets the same site speed as one in Slough.' ),
			array( 'stat' => 'Staging', 'name' => 'Safe changes', 'desc' => 'A one-click staging copy for every site, so nothing risky is ever tried on the live version.' ),
			array( 'stat' => '24/7', 'name' => 'Monitoring', 'desc' => 'Uptime, certificates, disk and error rates watched around the clock, with fixes started before you call.' ),
		),
		'security' => array(
			array( 'name' => 'Daily backups, 30-day history', 'desc' => 'Off-site, restorable to any point in the last month, and tested — a backup nobody has restored is a rumour.' ),
			array( 'name' => 'Free SSL, renewed automatically', 'desc' => 'Certificates issued and renewed for you, with HTTPS enforced site-wide.' ),
			array( 'name' => 'Web application firewall', 'desc' => 'Malicious traffic, brute-force attempts and known exploits blocked at the edge before they reach WordPress.' ),
			array( 'name' => 'Managed updates', 'desc' => 'Core, plugin and theme updates applied on a schedule and checked afterwards, not fired blind at 3am.' ),
			array( 'name' => 'Isolated environments', 'desc' => 'Every site runs in its own container, so a neighbour being compromised is not your problem.' ),
			array( 'name' => 'Malware clean-up included', 'desc' => 'If something does get through on a site we host, we clean it and restore it at no extra cost.' ),
		),
		'compare'  => array(
			array( 'label' => 'Managed updates', 'a' => 'Included', 'b' => 'Your job', 'c' => 'Your job' ),
			array( 'label' => 'Daily off-site backups', 'a' => '30-day history', 'b' => 'Often paid extra', 'c' => 'You configure it' ),
			array( 'label' => 'Free migration', 'a' => 'Included', 'b' => 'Sometimes', 'c' => 'You do it' ),
			array( 'label' => 'Staging environment', 'a' => 'One click', 'b' => 'Rarely', 'c' => 'You build it' ),
			array( 'label' => 'WAF & malware clean-up', 'a' => 'Included', 'b' => 'Paid add-on', 'c' => 'You configure it' ),
			array( 'label' => 'Support that knows your site', 'a' => 'The team who built it', 'b' => 'Generic ticket queue', 'c' => 'Nobody' ),
			array( 'label' => 'Server maintenance', 'a' => 'Ours', 'b' => 'Theirs', 'c' => 'Yours' ),
			array( 'label' => 'Typical cost of ownership', 'a' => 'One monthly fee', 'b' => 'Cheap, plus add-ons', 'c' => 'Server + your time' ),
		),
		'faqs'     => array(
			array( 'q' => 'Is there a contract?', 'a' => 'No. Monthly hosting is rolling and you can leave whenever you like — we will hand over a full copy of the site if you do. Annual is paid up front and works out at ten months for twelve.' ),
			array( 'q' => 'What counts as one site?', 'a' => 'One WordPress installation on one primary domain, including its staging copy. Multi-site networks and second brands are quoted separately.' ),
			array( 'q' => 'Do you have traffic limits?', 'a' => 'There is no hard cap. If a site consistently uses far more resource than a typical business site we will talk to you about it rather than throttle it or bill you by surprise.' ),
			array( 'q' => 'Can you host a site you did not build?', 'a' => 'Yes, as long as it is a WordPress site in reasonable health. We audit it during migration and tell you anything that needs attention first.' ),
			array( 'q' => 'Where are the servers?', 'a' => 'UK and EU data centres, with the CDN serving assets globally. Tell us if you have a data residency requirement and we will confirm the region before you sign up.' ),
		),
	);

	/**
	 * Filters the hosting page content.
	 *
	 * @param array $data See above.
	 */
	return apply_filters( 'blueworx_content_hosting', $data );
}

/**
 * Everything the ClubHouse page says: the plan, the nine modules (with their
 * stroke-icon SVG paths), the self-service cards, the audiences and the FAQ.
 *
 * @return array array( plan, modules, self_serve, audiences, faqs ).
 */
function blueworx_content_clubhouse() {
	$data = array(
		'plan'       => array(
			'name'     => 'ClubHouse',
			'desc'     => 'The complete club website platform, hosted and maintained by us.',
			'priceM'   => 20,
			'priceA'   => 200,
			// One-off, charged once at sign-up; covers the build and launch.
			'setup'    => 499,
			'currency' => 'GBP',
			'feat'     => true,
			'pop'      => true,
			'subA'     => __( 'per year, billed annually', 'bluegroup-project-blueworx' ),
			'lbl'      => __( 'INCLUDED', 'bluegroup-project-blueworx' ),
			'features' => array(
				'All nine ClubHouse modules',
				'Managed hosting, SSL and daily backups',
				'Online payments for subs, tickets and kit',
				'Unlimited teams, fixtures and members',
				'Mobile-first club site, branded to you',
				'Platform updates and security patches',
				'Email support from the BlueWorx team',
			),
		),
		'modules'    => array(
			array( 'name' => 'Memberships', 'desc' => 'Tiers, joining flows, renewals and subs collected by direct debit or card.', 'paths' => array( 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8', 'M22 21v-2a4 4 0 0 0-3-3.87' ) ),
			array( 'name' => 'Teams & squads', 'desc' => 'A page per team with squad lists, coaches, league tables and results.', 'paths' => array( 'M12 2 4 6v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V6Z' ) ),
			array( 'name' => 'Fixtures & results', 'desc' => 'Season fixtures, scores and reports, published once and shown everywhere.', 'paths' => array( 'M8 2v4', 'M16 2v4', 'M3 10h18', 'M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z' ) ),
			array( 'name' => 'Bookings', 'desc' => 'Courts, pitches, lanes and function rooms, bookable by members online.', 'paths' => array( 'M12 6v6l4 2', 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20' ) ),
			array( 'name' => 'Events & socials', 'desc' => 'Open days, camps, awards nights — with tickets and capacity limits.', 'paths' => array( 'M21 11.5a8.4 8.4 0 0 1-.9 3.8A8.5 8.5 0 0 1 12.5 20 8.4 8.4 0 0 1 8.7 19L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 4Z' ) ),
			array( 'name' => 'Club shop', 'desc' => 'Kit, merchandise and match-day extras, with member-only pricing.', 'paths' => array( 'M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z', 'M3 6h18', 'M16 10a4 4 0 0 1-8 0' ) ),
			array( 'name' => 'News & notices', 'desc' => 'Match reports, committee notices and a ticker for anything urgent.', 'paths' => array( 'M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Z', 'M8 7h8', 'M8 12h4' ) ),
			array( 'name' => 'Sponsors', 'desc' => 'Partner tiers, logo boards and a sponsorship enquiry route that converts.', 'paths' => array( 'M12 2 15 8.3l6.9.6-5.2 4.5 1.6 6.7L12 17l-6.3 3.1 1.6-6.7L2.1 8.9l6.9-.6Z' ) ),
			array( 'name' => 'Calendar', 'desc' => 'Everything the club is doing this month, in one subscribable feed.', 'paths' => array( 'M8 2v4', 'M16 2v4', 'M3 10h18', 'M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z', 'M9 16h6' ) ),
		),
		'self_serve' => array(
			array( 'tag' => 'Join', 'name' => 'Sign-up in minutes', 'desc' => 'A member joins, picks a tier, pays, and lands in the club directory without a committee member touching a spreadsheet.' ),
			array( 'tag' => 'Renew', 'name' => 'Renewals that chase themselves', 'desc' => 'Automatic reminders, card retries and a renewal page every member can reach from their phone.' ),
			array( 'tag' => 'Book', 'name' => 'Courts and pitches online', 'desc' => 'Availability, rules and member-only slots, with the bar and grounds team seeing the same diary.' ),
			array( 'tag' => 'Buy', 'name' => 'Kit and tickets in one basket', 'desc' => 'Shop and event ticketing share a checkout, so one order covers the kit bag and the awards night.' ),
		),
		'audiences'  => array(
			array( 'name' => 'Multi-sport clubs', 'desc' => 'Rugby, cricket, hockey, netball and tennis under one roof, each section with its own space.' ),
			array( 'name' => 'Single-sport clubs', 'desc' => 'Juniors through to first team, with squads, fixtures and subs handled in one place.' ),
			array( 'name' => 'Societies & associations', 'desc' => 'Membership tiers, an events calendar and a members-only area, without the sport.' ),
			array( 'name' => 'Gyms & studios', 'desc' => 'Class booking, recurring memberships and a shop, on hosting that stays up at 6am.' ),
		),
		'faqs'       => array(
			array( 'q' => 'Can we keep our existing domain?', 'a' => 'Yes. We point your current domain at the new site and handle the DNS and SSL for you, with no downtime on the day of the switch.' ),
			array( 'q' => 'What happens to our current member list?', 'a' => 'We import it. Send us whatever you have — a spreadsheet, an export from your old system — and we map it into membership tiers before you go live.' ),
			array( 'q' => 'Is hosting really included?', 'a' => 'It is. Managed hosting, backups, SSL, updates and monitoring are part of the monthly fee, so there is no separate hosting bill.' ),
			array( 'q' => 'Who updates the site day to day?', 'a' => 'Your committee does, through a simple editor. Fixtures, news and events take a couple of minutes each, and we are on the end of an email when something bigger is needed.' ),
			array( 'q' => 'Can we take payments through the site?', 'a' => 'Yes — memberships, subs, event tickets and shop orders all run through the same checkout, with money landing in the club account.' ),
		),
	);

	/**
	 * Filters the ClubHouse page content.
	 *
	 * @param array $data See above.
	 */
	return apply_filters( 'blueworx_content_clubhouse', $data );
}

/**
 * The portfolio: live client sites, in the order the Portfolio page shows
 * them. The first three are also the home page's "Selected Work" cards.
 *
 * Each screenshot is bundled at assets/img/portfolio/<slug>.jpg with its
 * WebP twin; the card links out to the live site.
 *
 * @return array List of array( name, slug, url, sector, tags[] ).
 */
function blueworx_content_portfolio() {
	$sites = array(
		array(
			'name'   => 'Hirasté',
			'slug'   => 'hiraste',
			'url'    => 'https://hiraste.com/',
			'sector' => __( 'Hospitality & group bookings', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Web Design', 'bluegroup-project-blueworx' ), __( 'Booking Platform', 'bluegroup-project-blueworx' ) ),
		),
		array(
			'name'   => 'PadLX',
			'slug'   => 'padlx',
			'url'    => 'https://padlx.com.au/',
			'sector' => __( 'Padel & lifestyle destination, Gold Coast', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Court Booking', 'bluegroup-project-blueworx' ), __( 'Web Build', 'bluegroup-project-blueworx' ) ),
		),
		array(
			'name'   => 'Top Tier Tutors',
			'slug'   => 'top-tier-tutors',
			'url'    => 'https://toptiertutors.co.za/',
			'sector' => __( 'Private tutoring, readers and scribes, South Africa', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Web Design', 'bluegroup-project-blueworx' ), __( 'Bookings', 'bluegroup-project-blueworx' ) ),
		),
		array(
			'name'   => 'World Squash Officiating',
			'slug'   => 'world-squash-officiating',
			'url'    => 'https://worldsquashofficiating.com/',
			'sector' => __( 'Training platform for squash officials', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Learning Platform', 'bluegroup-project-blueworx' ), __( 'Membership', 'bluegroup-project-blueworx' ) ),
		),
		array(
			'name'   => 'FIFTH Movement',
			'slug'   => 'fifth-movement',
			'url'    => 'https://fifthmovement.co.uk/',
			'sector' => __( 'PE, school sport and swimming for UK schools', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Web Design', 'bluegroup-project-blueworx' ), __( 'Enquiries', 'bluegroup-project-blueworx' ) ),
		),
		array(
			'name'   => 'Forum Lighting Solutions',
			'slug'   => 'forum-lighting-solutions',
			'url'    => 'https://forumlightingsolutions.com/',
			'sector' => __( 'Commercial lighting supplier', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Product Catalogue', 'bluegroup-project-blueworx' ), __( 'Web Build', 'bluegroup-project-blueworx' ) ),
		),
		array(
			'name'   => 'The Change Work',
			'slug'   => 'the-change',
			'url'    => 'https://thechange.work/',
			'sector' => __( 'Behavioural change specialists', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Brand', 'bluegroup-project-blueworx' ), __( 'Web Design', 'bluegroup-project-blueworx' ) ),
		),
		array(
			'name'   => 'Jens Pflüger',
			'slug'   => 'jens-pflueger',
			'url'    => 'https://jens-pflueger.de/en/',
			'sector' => __( 'Event and trade-fair presenter, Germany', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Multilingual', 'bluegroup-project-blueworx' ), __( 'Web Design', 'bluegroup-project-blueworx' ) ),
		),
		array(
			'name'   => 'Studio LalaLand',
			'slug'   => 'studio-lalaland',
			'url'    => 'https://studiolalaland.com/',
			'sector' => __( 'Full-service music production', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Web Design', 'bluegroup-project-blueworx' ), __( 'Portfolio', 'bluegroup-project-blueworx' ) ),
		),
		array(
			'name'   => 'Chromaesthesia',
			'slug'   => 'chromaesthesia',
			'url'    => 'https://chromaesthesia.space/',
			'sector' => __( 'Music taste-sharing platform', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Web App', 'bluegroup-project-blueworx' ), __( 'CMS', 'bluegroup-project-blueworx' ) ),
		),
		array(
			'name'   => 'Can Sakhara',
			'slug'   => 'cansakhara',
			'url'    => 'https://cansakhara.com/',
			'sector' => __( 'Private villa, Ibiza', 'bluegroup-project-blueworx' ),
			'tags'   => array( __( 'Web Design', 'bluegroup-project-blueworx' ), __( 'Enquiries', 'bluegroup-project-blueworx' ) ),
		),
	);

	/**
	 * Filters the portfolio list.
	 *
	 * @param array $sites List of array( name, slug, url, sector, tags[] ).
	 */
	return apply_filters( 'blueworx_content_portfolio', $sites );
}

/**
 * The customer review list shown on every marketing page.
 *
 * One set, three reviews: the reviews section is the same wherever it
 * appears, so a page never carries its own variant.
 *
 * @return array List of array( text, initials, name, role ).
 */
function blueworx_content_reviews() {
	$reviews = array(
		// Real Trustpilot reviews, quoted as written.
		array(
			'text'     => 'The brief I gave was carried out in a prompt and efficient manner. The website was professional in appearance and a cost effective product.',
			'initials' => 'A',
			'name'     => 'Andrew',
			'role'     => 'Sarmac',
		),
		array(
			'text'     => 'I have had websites designed in the past, but no one has been as thorough, understanding, patient, and most of all professional. I can\'t recommend them enough.',
			'initials' => 'A',
			'name'     => 'Alicia',
			'role'     => 'Style Me Slim',
		),
		array(
			'text'     => 'Great organisation with a can-do attitude. Our website is looking good.',
			'initials' => 'C',
			'name'     => 'Cheetham Hill Sports Club',
			'role'     => 'Trustpilot review',
		),
	);

	/**
	 * Filters the customer review list.
	 *
	 * @param array $reviews List of array( text, initials, name, role ).
	 */
	return array_slice( (array) apply_filters( 'blueworx_content_reviews', $reviews ), 0, 3 );
}
