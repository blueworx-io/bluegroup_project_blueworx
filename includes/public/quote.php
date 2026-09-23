<?php
/**
 * Public front-end layer — the quote builder (#113).
 *
 * One calculator, shown in two places: the public Support page and the Sales
 * section of the client area. Everything it knows is here, so the two can
 * never quote different numbers — the only differences between them are that
 * the internal one also shows what the sale pays (includes/public/commission.php)
 * and that the public one can be put back to the plain slider from Settings.
 *
 * A build is sized in hours, not priced directly: the stages below add up, and
 * the quote is the smallest support package whose annual allowance covers the
 * total. That keeps one price list rather than introducing a second, one-off
 * rate card that would drift from it.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * How a build is sized.
 *
 * The four fixed stages are the ones that do not scale with the size of the
 * site: the same discovery, the same testing, the same deployment and the same
 * watching it afterwards, whether it is four pages or forty.
 *
 * @return array
 */
function blueworx_quote_model() {
	/**
	 * Filters the build model — the hours behind every quote.
	 *
	 * @param array $model Stage hours, per-page hours and the membership extra.
	 */
	return (array) apply_filters(
		'blueworx_quote_model',
		array(
			'fixed'            => array(
				'discovery'  => array(
					'label' => __( 'Discovery and planning', 'bluegroup-project-blueworx' ),
					'hours' => 6,
				),
				'testing'    => array(
					'label' => __( 'Testing period', 'bluegroup-project-blueworx' ),
					'hours' => 6,
				),
				'deployment' => array(
					'label' => __( 'Deployment', 'bluegroup-project-blueworx' ),
					'hours' => 6,
				),
				'monitoring' => array(
					'label' => __( 'Post-launch monitoring', 'bluegroup-project-blueworx' ),
					'hours' => 6,
				),
			),
			'design_per_page'  => 4,
			'build_per_page'   => 8,
			'membership'       => 30,
			// Each one is a system of somebody else's to learn, connect and test.
			'integration'      => 20,
			// A custom ClubHouse is the same build with the page count settled:
			// the platform's twelve screens.
			'clubhouse_pages'  => 12,
			'default_pages'    => 5,
		)
	);
}

/**
 * The hours a build comes to.
 *
 * @param int  $pages        How many pages are designed and built.
 * @param bool $membership   Whether it needs a membership system.
 * @param int  $integrations How many other systems it has to talk to.
 * @return int
 */
function blueworx_quote_build_hours( $pages, $membership, $integrations = 0 ) {
	$model = blueworx_quote_model();
	$pages = max( 1, (int) $pages );
	$hours = 0;

	foreach ( $model['fixed'] as $stage ) {
		$hours += (int) $stage['hours'];
	}

	$hours += $pages * ( (int) $model['design_per_page'] + (int) $model['build_per_page'] );

	if ( $membership ) {
		$hours += (int) $model['membership'];
	}

	$hours += max( 0, (int) $integrations ) * (int) $model['integration'];

	return $hours;
}

/**
 * The stages a build is made of, ready to list.
 *
 * @param int  $pages        Page count.
 * @param bool $membership   Whether a membership system is included.
 * @param int  $integrations How many other systems it has to talk to.
 * @return array List of array( key, label, hours, fixed ).
 */
function blueworx_quote_build_stages( $pages, $membership, $integrations = 0 ) {
	$model = blueworx_quote_model();
	$pages = max( 1, (int) $pages );

	$stages = array(
		array(
			'key'   => 'discovery',
			'label' => $model['fixed']['discovery']['label'],
			'hours' => (int) $model['fixed']['discovery']['hours'],
			'fixed' => true,
		),
		array(
			'key'   => 'design',
			'label' => __( 'Design and review', 'bluegroup-project-blueworx' ),
			'hours' => $pages * (int) $model['design_per_page'],
			'fixed' => false,
		),
		array(
			'key'   => 'build',
			'label' => __( 'Build and review', 'bluegroup-project-blueworx' ),
			'hours' => $pages * (int) $model['build_per_page'],
			'fixed' => false,
		),
	);

	$integrations = max( 0, (int) $integrations );

	if ( $integrations > 0 ) {
		$stages[] = array(
			'key'   => 'integrations',
			'label' => __( 'Custom integrations', 'bluegroup-project-blueworx' ),
			'hours' => $integrations * (int) $model['integration'],
			'fixed' => false,
		);
	}

	if ( $membership ) {
		$stages[] = array(
			'key'   => 'membership',
			'label' => __( 'Membership system', 'bluegroup-project-blueworx' ),
			'hours' => (int) $model['membership'],
			'fixed' => false,
		);
	}

	foreach ( array( 'testing', 'deployment', 'monitoring' ) as $key ) {
		$stages[] = array(
			'key'   => $key,
			'label' => $model['fixed'][ $key ]['label'],
			'hours' => (int) $model['fixed'][ $key ]['hours'],
			'fixed' => true,
		);
	}

	return $stages;
}

/**
 * The package a number of hours is quoted as.
 *
 * The SMALLEST package whose annual allowance covers the work — quoting the
 * next one up would be padding, and quoting a smaller one would be selling
 * somebody hours they are going to run out of halfway through their build.
 *
 * @param int $hours Hours needed.
 * @return string|null Package slug, or null when no package is big enough.
 */
function blueworx_quote_package_for_hours( $hours ) {
	$best = null;

	foreach ( blueworx_commission_packages() as $slug => $package ) {
		if ( $package['hours'] >= $hours && ( null === $best || $package['hours'] < $best['hours'] ) ) {
			$best = array_merge( $package, array( 'slug' => $slug ) );
		}
	}

	return $best ? $best['slug'] : null;
}

/**
 * The quote the calculator opens on: support alone, on the Growth package,
 * which is where the Support page's slider has always started.
 *
 * @return array
 */
function blueworx_quote_default() {
	$model = blueworx_quote_model();

	return array(
		'hosting'          => false,
		'clubhouse'        => false,
		'hostingMode'      => 'support',
		'clubhouseMode'    => 'standard',
		// A standard ClubHouse is a platform somebody joins. Whether we also
		// look after it for them is a separate question, and the answer is what
		// decides whether a support package is quoted with it.
		'clubhouseSupport' => false,
		'pages'            => (int) $model['default_pages'],
		'integrations'     => 0,
		'membership'       => false,
		'support'          => 'growth',
	);
}

/**
 * Whether a quote is being sized as a build rather than as ongoing support.
 *
 * @param array $quote The quote.
 * @return bool
 */
function blueworx_quote_is_build( $quote ) {
	if ( ! empty( $quote['hosting'] ) ) {
		return 'build' === $quote['hostingMode'];
	}

	if ( ! empty( $quote['clubhouse'] ) ) {
		return 'custom' === $quote['clubhouseMode'];
	}

	return false;
}

/**
 * Works a quote out: the hours, the package that covers them, and the lines
 * that sit alongside it.
 *
 * @param array $quote A quote in blueworx_quote_default()'s shape.
 * @return array
 */
function blueworx_quote_summary( $quote ) {
	$model     = blueworx_quote_model();
	$products  = blueworx_commission_products();
	$packages  = blueworx_commission_packages();
	$clubhouse = blueworx_content_clubhouse();

	$build  = blueworx_quote_is_build( $quote );
	$pages  = ! empty( $quote['clubhouse'] ) && 'custom' === $quote['clubhouseMode']
		? (int) $model['clubhouse_pages']
		: (int) $quote['pages'];
	$hours  = $build ? blueworx_quote_build_hours( $pages, ! empty( $quote['membership'] ), isset( $quote['integrations'] ) ? (int) $quote['integrations'] : 0 ) : 0;
	$lines  = array();
	$slug   = '';

	if ( $build ) {
		$slug = blueworx_quote_package_for_hours( $hours );
	} elseif ( ! empty( $quote['clubhouse'] ) ) {
		// Standard ClubHouse is a platform somebody joins, not a build, so
		// there are no hours to cover. A package is only quoted with it if they
		// also want us looking after it.
		$slug = ! empty( $quote['clubhouseSupport'] ) && isset( $quote['support'] ) ? (string) $quote['support'] : '';
	} else {
		$slug = isset( $quote['support'] ) ? (string) $quote['support'] : '';
	}

	if ( ! empty( $quote['hosting'] ) ) {
		$lines[] = array(
			'label'  => $products['hosting']['name'],
			'detail' => __( 'per year, per site', 'bluegroup-project-blueworx' ),
			'amount' => (float) $products['hosting']['year'],
		);
	}

	if ( ! empty( $quote['clubhouse'] ) ) {
		// The setup fee is for standing a membership system up, so it is only
		// charged when they want one. A custom ClubHouse never carries it: it
		// is being built from scratch and the build hours already cover that
		// work, so charging it as well charges the same thing twice.
		if ( 'standard' === $quote['clubhouseMode'] && ! empty( $quote['membership'] ) ) {
			$lines[] = array(
				'label'  => __( 'Membership setup', 'bluegroup-project-blueworx' ),
				'detail' => __( 'one-off, charged at sign-up', 'bluegroup-project-blueworx' ),
				'amount' => (float) $clubhouse['plan']['setup'],
			);
		}

		$lines[] = array(
			'label'  => $products['clubhouse']['name'],
			'detail' => __( 'per year', 'bluegroup-project-blueworx' ),
			'amount' => (float) $products['clubhouse']['year'],
		);
	}

	return array(
		'build'   => $build,
		'pages'   => $pages,
		'hours'   => $hours,
		'package' => ( '' !== $slug && isset( $packages[ $slug ] ) ) ? $slug : '',
		// A build nobody's biggest package covers. Said out loud rather than
		// rounded down to Advantage +, which would promise 600 hours for work
		// that needs more.
		'over'    => $build && null === blueworx_quote_package_for_hours( $hours ),
		'lines'   => $lines,
	);
}

/**
 * The quote expressed as a sale, so the commission code can price it.
 *
 * Yearly billing throughout: a quote is made against the list price, and the
 * list price for a site is the annual one.
 *
 * @param array $quote The quote.
 * @return array A sale in blueworx_commission_default_sale()'s shape.
 */
function blueworx_quote_to_sale( $quote ) {
	$summary = blueworx_quote_summary( $quote );

	return array(
		'hosting'    => array(
			'qty'     => empty( $quote['hosting'] ) ? 0 : 1,
			'billing' => 'year',
		),
		'clubhouse'  => array(
			'qty'     => empty( $quote['clubhouse'] ) ? 0 : 1,
			'billing' => 'year',
		),
		'support'    => $summary['package'],
		'supportQty' => '' === $summary['package'] ? 0 : 1,
	);
}

/**
 * Whether the public Support page shows the whole quote builder.
 *
 * On by default, and turned off from Settings → BlueWorx Site. Off puts that
 * page back to the plain hours slider it had before — the questions behind a
 * build quote (what we allow for a page, for a membership system) are ours to
 * show or not, and that is a commercial decision rather than a technical one.
 * The Sales section is never affected by this.
 *
 * @return bool
 */
function blueworx_quote_public_enabled() {
	/**
	 * Filters whether the public Support page carries the quote builder.
	 *
	 * @param bool $enabled Whether it does.
	 */
	return (bool) apply_filters( 'blueworx_quote_builder_public', (bool) get_option( 'blueworx_quote_builder_public', 1 ) );
}

/**
 * Everything the browser needs to work a quote out without asking the server.
 *
 * @return array
 */
function blueworx_quote_payload() {
	return array(
		'model'    => blueworx_quote_model(),
		'packages' => blueworx_commission_packages(),
		'products' => blueworx_commission_products(),
		'setup'    => (float) blueworx_content_clubhouse()['plan']['setup'],
		'defaults' => blueworx_quote_default(),
		'rates'    => blueworx_commission_rates(),
	);
}
