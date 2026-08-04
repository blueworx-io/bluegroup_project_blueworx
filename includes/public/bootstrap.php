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
// After pages.php — the client area registers its pages on that file's filter.
require_once BLUEWORX_SITE_PATH . 'includes/public/account.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/account-data.php';
// After account.php — the auth pages are where its gate sends people.
require_once BLUEWORX_SITE_PATH . 'includes/public/auth.php';
// After account.php and auth.php — it reads the `account`/`auth` flags those
// files add to the page registry to decide what must never be indexed.
require_once BLUEWORX_SITE_PATH . 'includes/public/seo-copy.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/indexing.php';
// After pages.php — the backfill calls blueworx_public_page_is_ours().
require_once BLUEWORX_SITE_PATH . 'includes/public/upgrade.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/render.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/assets.php';
require_once BLUEWORX_SITE_PATH . 'includes/public/redirects.php';
