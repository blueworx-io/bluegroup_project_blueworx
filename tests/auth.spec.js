/**
 * Signing in, signing up and resetting a password (#43).
 *
 * These pages are the front door to the client area, so the specs here are
 * weighted towards the ways a login page goes wrong rather than the happy path:
 *
 * - a `redirect_to` that points off-site (an open redirect on a login page is
 *   the classic phishing setup),
 * - a failure message that says which half was wrong (a way to find out who
 *   has an account here),
 * - a reset form that says whether an address is on file (the same thing,
 *   more directly),
 * - a form that posts without a nonce.
 *
 * The happy paths are covered too, end to end: a client registers, is signed
 * in, lands on their dashboard, signs out and signs back in.
 */

import { test, expect, cacheBust, isPlaceholder, baseURL } from './helpers.js';
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
 * Test fixture for tests/auth.spec.js: turns registration on and off, makes and
 * removes the throwaway client, and hands back a real password-reset key so the
 * reset screen can be driven without reading email. Removed after the run, and
 * only ever exists inside the disposable local WordPress the tests create.
 */

// Registration and password resets both send mail, and the throwaway
// WordPress has no mail server — PHP's mail() then blocks on a TCP connect to
// localhost:25 until it times out, once per request. That turned a two-minute
// file into a twenty-three-minute one. Nothing here tests email delivery, so
// mail is short-circuited rather than waited for.
add_filter( 'pre_wp_mail', '__return_true' );

add_action( 'init', function () {
	if ( ! isset( $_GET['bw_auth'] ) ) {
		return;
	}

	$email  = '${CLIENT_EMAIL}';
	$action = sanitize_text_field( wp_unslash( $_GET['bw_auth'] ) );
	$user   = get_user_by( 'email', $email );

	switch ( $action ) {
		case 'open':
			update_option( 'users_can_register', 1 );
			break;

		case 'closed':
			update_option( 'users_can_register', 0 );
			break;

		case 'make_client':
			if ( ! $user ) {
				$id   = wp_create_user( $email, '${CLIENT_PASS}', $email );
				$user = get_user_by( 'id', $id );
			}
			break;

		case 'reset_key':
			if ( $user ) {
				$key = get_password_reset_key( $user );
				wp_send_json( array(
					'key'   => is_wp_error( $key ) ? '' : $key,
					'login' => $user->user_login,
				) );
			}
			wp_send_json( array( 'key' => '', 'login' => '' ) );
			break;

		case 'cleanup':
			update_option( 'users_can_register', 0 );
			if ( $user ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				wp_delete_user( $user->ID );
			}
			break;
	}

	wp_send_json( array(
		'registration' => (int) get_option( 'users_can_register', 0 ),
		'client'       => $user ? (int) $user->ID : 0,
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
async function fixture(page, action) {
  const response = await page.request.get(`/?bw_auth=${action}`);
  expect(response.ok(), `fixture action "${action}" failed`).toBe(true);
  return response.json();
}

const skipUnlessLocal = () => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
  test.skip(!canInstallFixture, 'Needs the local WordPress harness.');
};

const path = (url) => new URL(url, baseURL).pathname.replace(/\/$/, '');

test.describe('Signing in', () => {
  test.beforeEach(async ({ page }) => {
    skipUnlessLocal();
    await fixture(page, 'make_client');
  });

  test('a client signs in and lands on their dashboard', async ({ page }) => {
    await page.goto(cacheBust('/login/'));
    await page.fill('#blueworx_email', CLIENT_EMAIL);
    await page.fill('#blueworx_password', CLIENT_PASS);
    await page.click('.auth-submit');

    expect(path(page.url())).toBe('/dashboard');
    await expect(page.locator('.dash-panel')).toHaveCount(2);
  });

  test('signing in returns them to the page they were heading for', async ({ page }) => {
    // The whole point of the gate carrying redirect_to (#37).
    await page.goto(cacheBust('/dashboard/invoices/'));
    await page.fill('#blueworx_email', CLIENT_EMAIL);
    await page.fill('#blueworx_password', CLIENT_PASS);
    await page.click('.auth-submit');

    expect(path(page.url())).toBe('/dashboard/invoices');
  });

  // A login page that will forward to anywhere is a phishing tool with a
  // trustworthy address in front of it.
  test('an off-site redirect_to is refused', async ({ page }) => {
    await page.goto(cacheBust('/login/?redirect_to=https://example.invalid/steal'));
    await page.fill('#blueworx_email', CLIENT_EMAIL);
    await page.fill('#blueworx_password', CLIENT_PASS);
    await page.click('.auth-submit');

    expect(new URL(page.url()).host).toBe(new URL(baseURL).host);
    expect(path(page.url())).toBe('/dashboard');
  });

  // Different messages for "no such user" and "wrong password" turn a login
  // form into a way to enumerate who has an account.
  test('a wrong password and an unknown address say the same thing', async ({ page }) => {
    await page.goto(cacheBust('/login/'));
    await page.fill('#blueworx_email', CLIENT_EMAIL);
    await page.fill('#blueworx_password', 'not-the-right-password');
    await page.click('.auth-submit');
    const wrongPassword = await page.locator('.auth-notice').innerText();

    await page.goto(cacheBust('/login/'));
    await page.fill('#blueworx_email', 'nobody-at-all@example.invalid');
    await page.fill('#blueworx_password', 'not-the-right-password');
    await page.click('.auth-submit');
    const noSuchUser = await page.locator('.auth-notice').innerText();

    expect(noSuchUser).toBe(wrongPassword);
    expect(path(page.url())).toBe('/login');
  });

  test('a post without a valid nonce is refused', async ({ page }) => {
    const response = await page.request.post('/login/', {
      form: {
        blueworx_auth_action: 'login',
        blueworx_auth_nonce: 'not-a-real-nonce',
        blueworx_email: CLIENT_EMAIL,
        blueworx_password: CLIENT_PASS,
      },
      maxRedirects: 0,
    });

    expect(response.status()).toBe(302);
    expect(response.headers().location).toContain('notice=expired');
  });

  test('a signed-in client visiting the sign-in page is sent to their dashboard', async ({
    page,
  }) => {
    await page.goto(cacheBust('/login/'));
    await page.fill('#blueworx_email', CLIENT_EMAIL);
    await page.fill('#blueworx_password', CLIENT_PASS);
    await page.click('.auth-submit');

    await page.goto('/login/');

    expect(path(page.url())).toBe('/dashboard');
  });

  test('signing out returns them to the site, not to wp-login', async ({ page }) => {
    await page.goto(cacheBust('/login/'));
    await page.fill('#blueworx_email', CLIENT_EMAIL);
    await page.fill('#blueworx_password', CLIENT_PASS);
    await page.click('.auth-submit');

    await page.click('.dash-signout');

    expect(path(page.url())).toBe('/login');
    await expect(page.locator('.auth-notice-ok')).toContainText('signed out');
  });
});

test.describe('Creating an account', () => {
  test.beforeEach(async ({ page }) => {
    skipUnlessLocal();
    await fixture(page, 'cleanup');
  });

  test.afterEach(async ({ page }) => {
    await fixture(page, 'cleanup');
  });

  test('a new client registers, is signed in, and lands on the dashboard', async ({ page }) => {
    await fixture(page, 'open');
    await page.goto(cacheBust('/register/'));

    await page.fill('#blueworx_name', 'Fixture Client');
    await page.fill('#blueworx_email', CLIENT_EMAIL);
    await page.fill('#blueworx_password', CLIENT_PASS);
    await page.click('.auth-submit');

    expect(path(page.url())).toBe('/dashboard');
    await expect(page.locator('.dash-hello')).toContainText('Fixture Client');
  });

  test('a short password is refused', async ({ page }) => {
    await fixture(page, 'open');
    await page.goto(cacheBust('/register/'));

    // The field has minlength, so the browser would block this. The server is
    // what has to refuse it, so the post is made directly.
    const nonce = await page.locator('#blueworx_auth_nonce').inputValue();
    const response = await page.request.post('/register/', {
      form: {
        blueworx_auth_action: 'register',
        blueworx_auth_nonce: nonce,
        blueworx_email: CLIENT_EMAIL,
        blueworx_password: 'short',
      },
      maxRedirects: 0,
    });

    expect(response.headers().location).toContain('notice=password-short');
    expect((await fixture(page, 'noop')).client).toBe(0);
  });

  // The form is HTML, and HTML is not a control.
  test('registration stays closed even if the form is posted directly', async ({ page }) => {
    await fixture(page, 'open');
    await page.goto(cacheBust('/register/'));
    const nonce = await page.locator('#blueworx_auth_nonce').inputValue();

    await fixture(page, 'closed');

    const response = await page.request.post('/register/', {
      form: {
        blueworx_auth_action: 'register',
        blueworx_auth_nonce: nonce,
        blueworx_email: CLIENT_EMAIL,
        blueworx_password: CLIENT_PASS,
      },
      maxRedirects: 0,
    });

    expect(response.headers().location).toContain('notice=registration-off');
    expect((await fixture(page, 'noop')).client).toBe(0);
  });

  test('with registration closed the page offers a way to ask instead', async ({ page }) => {
    await fixture(page, 'closed');
    await page.goto(cacheBust('/register/'));

    await expect(page.locator('form.auth-form')).toHaveCount(0);
    await expect(page.locator('a.auth-submit')).toHaveAttribute('href', /contact/);
  });
});

test.describe('Resetting a password', () => {
  test.beforeEach(async ({ page }) => {
    skipUnlessLocal();
    await fixture(page, 'make_client');
  });

  // Saying "no account with that address" here is the most direct possible way
  // to check who has an account.
  test('the same answer whether or not the address is on file', async ({ page }) => {
    await page.goto(cacheBust('/reset-password/'));
    await page.fill('#blueworx_email', CLIENT_EMAIL);
    await page.click('.auth-submit');
    const known = await page.locator('.auth-notice').innerText();

    await page.goto(cacheBust('/reset-password/'));
    await page.fill('#blueworx_email', 'nobody-at-all@example.invalid');
    await page.click('.auth-submit');
    const unknown = await page.locator('.auth-notice').innerText();

    expect(unknown).toBe(known);
  });

  test('a valid link lets the client set a new password and sign in with it', async ({ page }) => {
    const { key, login } = await fixture(page, 'reset_key');
    expect(key).toBeTruthy();

    await page.goto(
      cacheBust(`/reset-password/?key=${encodeURIComponent(key)}&login=${encodeURIComponent(login)}`)
    );

    await expect(page.locator('.auth-title')).toContainText('new password');
    await page.fill('#blueworx_password', 'a-brand-new-passphrase');
    await page.click('.auth-submit');

    expect(path(page.url())).toBe('/login');
    await expect(page.locator('.auth-notice-ok')).toContainText('password is changed');

    await page.fill('#blueworx_email', CLIENT_EMAIL);
    await page.fill('#blueworx_password', 'a-brand-new-passphrase');
    await page.click('.auth-submit');

    expect(path(page.url())).toBe('/dashboard');
  });

  test('an expired or made-up link shows the request form, not the password form', async ({
    page,
  }) => {
    await page.goto(cacheBust('/reset-password/?key=made-up-key&login=someone'));

    await expect(page.locator('#blueworx_email')).toHaveCount(1);
    await expect(page.locator('#blueworx_password')).toHaveCount(0);
  });
});
