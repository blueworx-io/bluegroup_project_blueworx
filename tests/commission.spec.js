/**
 * The commission calculator, and who is allowed to see it.
 *
 * Two halves, and the first matters more than the second: this page shows what
 * we pay on a sale, so the specs here are weighted towards a client not being
 * able to reach it. The dashboard deliberately hides tabs without hiding
 * addresses (see blueworx_account_visible_sections()), so "the link is not in
 * the sidebar" is not the test — "the address turns them away" is.
 *
 * The sums come from the handoff's worked example: the default sale is
 * £40 + £40 + £600 = £680 on £6,400 of annual value, and a support package is
 * worth 10% under £9,000 a year and 20% at or above it.
 */

import { test, expect, cacheBust, isPlaceholder, baseURL, login } from './helpers.js';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-commission.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

const SALES_EMAIL = 'bw-fixture-sales@example.invalid';
const CLIENT_EMAIL = 'bw-fixture-buyer@example.invalid';

const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture for tests/commission.spec.js: makes a salesperson and a plain
 * client, signs either of them in, and reports the roles WordPress knows about.
 * Removed after the run, and only ever exists inside the disposable local
 * WordPress the tests create.
 */

add_filter( 'pre_wp_mail', '__return_true' );

add_action( 'init', function () {
	if ( ! isset( $_GET['bw_comm'] ) ) {
		return;
	}

	$action = sanitize_text_field( wp_unslash( $_GET['bw_comm'] ) );

	$make = function ( $email, $role ) {
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			$id   = wp_create_user( $email, wp_generate_password( 20 ), $email );
			$user = get_user_by( 'id', $id );
		}

		$user->set_role( $role );

		return $user;
	};

	switch ( $action ) {
		case 'sales_in':
			$user = $make( '${SALES_EMAIL}', 'blueworx_sales' );
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID );
			break;

		case 'client_in':
			$user = $make( '${CLIENT_EMAIL}', 'subscriber' );
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID );
			break;

		case 'cleanup':
			require_once ABSPATH . 'wp-admin/includes/user.php';
			foreach ( array( '${SALES_EMAIL}', '${CLIENT_EMAIL}' ) as $email ) {
				$user = get_user_by( 'email', $email );
				if ( $user ) {
					wp_delete_user( $user->ID );
				}
			}
			break;
	}

	wp_send_json( array( 'roles' => wp_roles()->get_names() ) );
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
    await request.get('/?bw_comm=cleanup').catch(() => {});
    await request.dispose();
  }

  if (existsSync(FIXTURE)) {
    rmSync(FIXTURE);
  }
});

/** Drives the fixture. */
async function fixture(page, action) {
  const response = await page.request.get(`/?bw_comm=${action}`);
  expect(response.ok(), `fixture action "${action}" failed`).toBe(true);
  return response.json();
}

const skipUnlessLocal = () => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
  test.skip(!canInstallFixture, 'Needs the local WordPress harness.');
};

const path = (url) => new URL(url, baseURL).pathname.replace(/\/$/, '');

test.describe('Who can see the Sales section', () => {
  test.beforeEach(() => skipUnlessLocal());

  test('the plugin adds a "BlueWorx: Sales Staff" role', async ({ page }) => {
    const { roles } = await fixture(page, 'roles');

    expect(Object.values(roles)).toContain('BlueWorx: Sales Staff');
  });

  test('a salesperson gets a Sales heading and a Commission link', async ({ page }) => {
    await fixture(page, 'sales_in');
    await page.goto(cacheBust('/dashboard/'));

    await expect(page.locator('.dash-navlabel', { hasText: 'Sales' })).toHaveCount(1);
    await expect(page.locator('.dash-nav a[href*="/dashboard/commission"]')).toHaveCount(1);
  });

  test('a client is offered neither', async ({ page }) => {
    await fixture(page, 'client_in');
    await page.goto(cacheBust('/dashboard/'));

    await expect(page.locator('.dash-navlabel', { hasText: 'Sales' })).toHaveCount(0);
    await expect(page.locator('.dash-nav a[href*="/dashboard/commission"]')).toHaveCount(0);
  });

  // The link being absent is not the control — the address is.
  test('a client who knows the address is turned away from it', async ({ page }) => {
    await fixture(page, 'client_in');

    await page.goto(cacheBust('/dashboard/commission/'));

    expect(path(page.url())).toBe('/dashboard');
    await expect(page.locator('body')).not.toContainText('Work out what you earn');
  });

  test('an administrator sees it without being given the role', async ({ page }) => {
    await login(page);
    await page.goto(cacheBust('/dashboard/commission/'));

    expect(path(page.url())).toBe('/dashboard/commission');
    await expect(page.locator('.comm-total')).toBeVisible();
  });
});

test.describe('What the calculator works out', () => {
  test.beforeEach(async ({ page }) => {
    skipUnlessLocal();
    await fixture(page, 'sales_in');
    await page.goto(cacheBust('/dashboard/commission/'));
  });

  // The handoff's worked example: Hosting £40 + ClubHouse £40 + Growth £600.
  test('the default sale earns £680 on £6,400 of annual value', async ({ page }) => {
    await expect(page.locator('.comm-total')).toContainText('£680');
    await expect(page.locator('.comm-total-sub')).toContainText('£6,400');
  });

  test('each product shows its own commission', async ({ page }) => {
    await expect(page.locator('[data-row="hosting"] .comm-row-fee')).toContainText('£40');
    await expect(page.locator('[data-row="clubhouse"] .comm-row-fee')).toContainText('£40');
    await expect(page.locator('[data-row="support"] .comm-row-fee')).toContainText('£600');
  });

  // £9,000 a year is the 20% tier, and Enterprise is exactly £9,000.
  test('a support package at exactly £9,000 a year earns the higher rate', async ({ page }) => {
    await page.selectOption('[data-package]', 'enterprise');

    await expect(page.locator('[data-row="support"] .comm-row-fee')).toContainText('£1,800');
    await expect(page.locator('[data-row="support"] .comm-row-rate')).toContainText('20%');
    await expect(page.locator('.comm-total')).toContainText('£1,880');
  });

  test('a package below the threshold earns 10% and says what would close the gap', async ({
    page,
  }) => {
    await page.selectOption('[data-package]', 'growth');

    await expect(page.locator('[data-row="support"] .comm-row-rate')).toContainText('10%');
    await expect(page.locator('.comm-hint')).toContainText('£3,000 more');
  });

  test('a quantity of zero takes the product out of the sale', async ({ page }) => {
    await page.click('[data-row="hosting"] [data-step="down"]');

    await expect(page.locator('[data-row="hosting"] [data-qty]')).toHaveText('0');
    await expect(page.locator('.comm-total')).toContainText('£640');
    await expect(page.locator('.comm-break')).not.toContainText('Website Hosting');
  });

  test('monthly billing is worth twelve months of the monthly price', async ({ page }) => {
    // £20 a month over the first year is £240, not £200 — so 20% is £48.
    await page.click('[data-row="hosting"] [data-billing="month"]');

    await expect(page.locator('[data-row="hosting"] .comm-row-fee')).toContainText('£48');
    await expect(page.locator('.comm-total')).toContainText('£688');
  });

  test('the monthly view divides the year, without changing the rate', async ({ page }) => {
    await page.click('[data-view="month"]');

    await expect(page.locator('.comm-total')).toContainText('£56.67');
    await expect(page.locator('.comm-total-sub')).toContainText('£680');
  });

  test('reset puts the sale back to the default', async ({ page }) => {
    await page.selectOption('[data-package]', '');
    await expect(page.locator('.comm-total')).toContainText('£80');

    await page.click('[data-reset]');

    await expect(page.locator('.comm-total')).toContainText('£680');
  });

  test('the rates it quotes are the ones written on the page', async ({ page }) => {
    await expect(page.locator('.comm-rates')).toContainText('20%');
    await expect(page.locator('.comm-rates')).toContainText('£9,000');
    await expect(page.locator('.comm-note')).toContainText('first year');
  });
});
