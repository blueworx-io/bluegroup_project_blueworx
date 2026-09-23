/**
 * Signing in (#43, and the move to the shop's form).
 *
 * The site used to carry its own sign-in, sign-up and reset screens. They are
 * gone: the shop's `<sc-login-form>` is the one front door now, the same swap
 * the ClubHouse plugin made, so there is no second login to keep working. What
 * is still ours is tested here:
 *
 * - the page renders the shop's form rather than a form of our own,
 * - the shop's script reaches the page, which our own asset sweep would
 *   otherwise strip — leaving correct markup that never comes alive,
 * - where a member lands afterwards, including an admin going to wp-admin and
 *   an off-site `redirect_to` being refused (an open redirect on a login page
 *   is the classic phishing setup),
 * - the retired /register and /reset-password addresses still go somewhere.
 *
 * The shop is not installed in the throwaway WordPress these tests run against,
 * so a fixture stands in for it — the same approach tests/commerce-pricing.spec.js
 * takes with its price model. The parts under test are ours either way: the
 * markup, the enqueue, and the filter.
 */

import { test, expect, cacheBust, isPlaceholder, baseURL, login } from './helpers.js';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-auth.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

const CLIENT_EMAIL = 'bw-fixture-client@example.invalid';
const CLIENT_PASS = 'correct-horse-battery-staple';

const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture for tests/auth.spec.js: stands in for the shop, makes and removes
 * the throwaway client, signs it in, and reports where the shop would be told to
 * send somebody. Removed after the run, and only ever exists inside the
 * disposable local WordPress the tests create.
 */

// The throwaway WordPress has no mail server — PHP's mail() then blocks on a TCP
// connect to localhost:25 until it times out, once per request. Nothing here
// tests email delivery.
add_filter( 'pre_wp_mail', '__return_true' );

/**
 * Stands in for SureCart's front-end bundle.
 *
 * Registered, not enqueued: the plugin decides for itself whether the sign-in
 * page needs it, which is exactly what the specs check. Priority 1 so these
 * exist before the plugin's own asset pass looks for them.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! get_option( 'bw_test_shop' ) ) {
		return;
	}

	wp_register_script( 'surecart-components', home_url( '/bw-fixture-surecart.js' ), array(), '1.0', true );
	wp_register_style( 'surecart-themes-default', home_url( '/bw-fixture-surecart.css' ), array(), '1.0' );
}, 1 );

add_action( 'init', function () {
	if ( ! isset( $_GET['bw_auth'] ) ) {
		return;
	}

	$email  = '${CLIENT_EMAIL}';
	$action = sanitize_text_field( wp_unslash( $_GET['bw_auth'] ) );
	$user   = get_user_by( 'email', $email );

	switch ( $action ) {
		case 'shop_on':
			update_option( 'bw_test_shop', 1 );
			break;

		case 'shop_off':
			update_option( 'bw_test_shop', 0 );
			break;

		case 'make_client':
			if ( ! $user ) {
				$id   = wp_create_user( $email, '${CLIENT_PASS}', $email );
				$user = get_user_by( 'id', $id );
			}
			break;

		case 'sign_in_client':
			if ( ! $user ) {
				$id   = wp_create_user( $email, '${CLIENT_PASS}', $email );
				$user = get_user_by( 'id', $id );
			}
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID );
			break;

		case 'redirect':
			// What the shop asks the site, on its own filter, once it has signed
			// somebody in. Null is the shop having no opinion of its own.
			$target = isset( $_GET['target'] ) ? sanitize_text_field( wp_unslash( $_GET['target'] ) ) : null;
			wp_send_json( array( 'url' => apply_filters( 'sc_login_redirect_url', $target ) ) );
			break;

		case 'cleanup':
			update_option( 'bw_test_shop', 0 );
			if ( $user ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				wp_delete_user( $user->ID );
			}
			break;
	}

	wp_send_json( array(
		'shop'   => (int) get_option( 'bw_test_shop', 0 ),
		'client' => $user ? (int) $user->ID : 0,
	) );
} );
`;

test.beforeAll(() => {
  if (isPlaceholder || !canInstallFixture) {
    return;
  }
  mkdirSync(MU_DIR, { recursive: true });
  writeFileSync(FIXTURE, FIXTURE_PLUGIN);
});

test.afterAll(async ({ playwright }) => {
  if (!isPlaceholder && canInstallFixture) {
    const request = await playwright.request.newContext({ baseURL });
    await request.get('/?bw_auth=cleanup').catch(() => {});
    await request.dispose();
  }

  if (existsSync(FIXTURE)) {
    rmSync(FIXTURE);
  }
});

/** Drives the fixture. */
async function fixture(page, action, args = '') {
  const response = await page.request.get(`/?bw_auth=${action}${args}`);
  expect(response.ok(), `fixture action "${action}" failed`).toBe(true);
  return response.json();
}

/** Where the shop would be told to send the current user. */
async function landsOn(page, target) {
  const { url } = await fixture(page, 'redirect', target ? `&target=${encodeURIComponent(target)}` : '');
  return new URL(url, baseURL).pathname.replace(/\/$/, '');
}

const skipUnlessLocal = () => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
  test.skip(!canInstallFixture, 'Needs the local WordPress harness.');
};

const path = (url) => new URL(url, baseURL).pathname.replace(/\/$/, '');

test.describe('The sign-in page', () => {
  test.beforeEach(async ({ page }) => {
    skipUnlessLocal();
    await fixture(page, 'shop_on');
  });

  test("offers the shop's form, and none of our own", async ({ page }) => {
    await page.goto(cacheBust('/login/'));

    await expect(page.locator('sc-login-form')).toHaveCount(1);
    await expect(page.locator('input[name="blueworx_password"]')).toHaveCount(0);
  });

  // The form is a web component: correct markup that never comes alive without
  // the script, and the plugin's asset sweep refuses anything it does not name.
  test("loads the shop's script, which the asset sweep would otherwise strip", async ({ page }) => {
    await page.goto(cacheBust('/login/'));

    await expect(page.locator('script[src*="bw-fixture-surecart.js"]')).toHaveCount(1);
  });

  test("does not put the shop's script on a page that has no form", async ({ page }) => {
    await page.goto(cacheBust('/about/'));

    await expect(page.locator('script[src*="bw-fixture-surecart.js"]')).toHaveCount(0);
  });

  test('without the shop, says where to sign in instead of showing a dead form', async ({
    page,
  }) => {
    await fixture(page, 'shop_off');
    await page.goto(cacheBust('/login/'));

    await expect(page.locator('sc-login-form')).toHaveCount(0);
    await expect(page.locator('.auth-card a[href*="login"]')).toHaveCount(1);
  });

  test('sends a signed-in client to their dashboard', async ({ page }) => {
    await fixture(page, 'sign_in_client');

    await page.goto('/login/');

    expect(path(page.url())).toBe('/dashboard');
  });

  test('is where signing out lands, not wp-login', async ({ page }) => {
    await fixture(page, 'sign_in_client');
    await page.goto(cacheBust('/dashboard/'));

    await page.click('.dash-signout');

    expect(path(page.url())).toBe('/login');
  });
});

test.describe('Where the shop sends people once it has signed them in', () => {
  test.beforeEach(async ({ page }) => {
    skipUnlessLocal();
  });

  test('a client goes to their dashboard', async ({ page }) => {
    await fixture(page, 'sign_in_client');

    expect(await landsOn(page, null)).toBe('/dashboard');
  });

  test('an admin goes straight to wp-admin', async ({ page }) => {
    await login(page);

    expect(await landsOn(page, null)).toBe('/wp-admin');
  });

  test('a client is returned to the page they were heading for', async ({ page }) => {
    await fixture(page, 'sign_in_client');

    expect(await landsOn(page, '/dashboard/invoices/')).toBe('/dashboard/invoices');
  });

  // A login page that will forward to anywhere is a phishing tool with a
  // trustworthy address in front of it.
  test('an off-site destination is refused', async ({ page }) => {
    await fixture(page, 'sign_in_client');

    const { url } = await fixture(page, 'redirect', '&target=https%3A%2F%2Fexample.invalid%2Fsteal');

    expect(new URL(url, baseURL).host).toBe(new URL(baseURL).host);
    expect(path(url)).toBe('/dashboard');
  });
});

test.describe('The retired sign-up and reset pages', () => {
  test.beforeEach(() => skipUnlessLocal());

  for (const retired of ['/register/', '/reset-password/']) {
    test(`${retired} goes to the sign-in page`, async ({ page }) => {
      const response = await page.request.get(retired, { maxRedirects: 0 });

      expect(response.status()).toBe(301);
      expect(path(response.headers().location)).toBe('/login');
    });
  }
});
