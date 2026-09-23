<?php
/**
 * Public front-end layer — bootstrap.
 *
 * The plugin renders the marketing site itself rather than relying on a theme,
 * so the site is identical wherever it is hosted. Loaded only when the
 * public_site feature is on.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BLUEWORX_SITE_PATH . 'includes/public/helpers-public.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/content.php';
// After content.php — commerce filters the plans that file defines.
require_once BLUEWORX_SITE_PATH . 'includes/public/commerce.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/pages.php';
// The Sales section is gated on the capability this file installs.
require_once BLUEWORX_SITE_PATH . 'includes/public/roles.php';
// After content.php and commerce.php — the calculator reads its prices from the
// first and names its packages with the second.
require_once BLUEWORX_SITE_PATH . 'includes/public/commission.php';
// After commission.php — a quote is priced with the same packages and rates.
require_once BLUEWORX_SITE_PATH . 'includes/public/quote.php';
// After roles.php — the Sales panels it adds to the Labs dashboard are gated on
// that file's capability.
require_once BLUEWORX_SITE_PATH . 'includes/public/labs-dashboard.php';
// After pages.php — the handler asks it which page is being rendered.
require_once BLUEWORX_SITE_PATH . 'includes/public/contact-form.php';
// After labs-dashboard.php — signing in lands people on the dashboard it finds.
require_once BLUEWORX_SITE_PATH . 'includes/public/auth.php';
// After auth.php — it reads the `auth` flag that file adds to the page
// registry to decide what must never be indexed.
require_once BLUEWORX_SITE_PATH . 'includes/public/seo-copy.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/indexing.php';
// After pages.php — the backfill calls blueworx_public_page_is_ours().
require_once BLUEWORX_SITE_PATH . 'includes/public/upgrade.php';
// After pages.php — blueworx_public_drop_global_styles() asks it whether the
// plugin is rendering this request.
require_once BLUEWORX_SITE_PATH . 'includes/public/images.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/render.php';
// Before assets.php — the widgets script is handed the rates this file keeps.
require_once BLUEWORX_SITE_PATH . 'includes/public/currency.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/assets.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/redirects.php';
// After pages.php and indexing.php — it asks both whether this response is the
// same for every visitor before it may be cached.
require_once BLUEWORX_SITE_PATH . 'includes/public/cache.php';
