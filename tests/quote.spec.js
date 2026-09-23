/**
 * The quote builder (#113).
 *
 * One calculator, in two places: the public Support page and the Sales section
 * of the client area. The numbers are the same in both — that is the point of
 * sharing it — so the sums are tested once, on whichever page is cheaper to
 * reach, and what differs between the two is tested separately:
 *
 * - the commission panel, which only a salesperson or an administrator sees,
 * - the Support page's switch, which puts the public page back to the plain
 *   slider without touching the internal one.
 *
 * The hours model is the source of truth for a build: 6 discovery, 4 a page to
 * design, 8 a page to build, 30 for a membership system, then 6 each for
 * testing, deployment and monitoring. The total is quoted as the smallest
 * support package whose annual hours cover it.
 */

import { test, expect, cacheBust, isPlaceholder, baseURL, login } from './helpers.js';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-quote.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

const SALES_EMAIL = 'bw-fixture-quoter@example.invalid';

const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture for tests/quote.spec.js: flips the Support page's quote-builder
 * switch, and signs in a salesperson. Removed after the run.
 */

add_filter( 'pre_wp_mail', '__return_true' );

add_action( 'init', function () {
	if ( ! isset( $_GET['bw_quote'] ) ) {
		return;
	}

	switch ( sanitize_text_field( wp_unslash( $_GET['bw_quote'] ) ) ) {
		case 'public_on':
			update_option( 'blueworx_quote_builder_public', 1 );
			break;

		case 'public_off':
			update_option( 'blueworx_quote_builder_public', 0 );
			break;

		case 'sales_in':
			$user = get_user_by( 'email', '${SALES_EMAIL}' );
			if ( ! $user ) {
				$id   = wp_create_user( '${SALES_EMAIL}', wp_generate_password( 20 ), '${SALES_EMAIL}' );
				$user = get_user_by( 'id', $id );
			}
			$user->set_role( 'blueworx_sales' );
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID );
			break;

		case 'cleanup':
			update_option( 'blueworx_quote_builder_public', 1 );
			$user = get_user_by( 'email', '${SALES_EMAIL}' );
			if ( $user ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				wp_delete_user( $user->ID );
			}
			break;
	}

	wp_send_json( array( 'public' => (int) get_option( 'blueworx_quote_builder_public', 1 ) ) );
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
    await request.get('/?bw_quote=cleanup').catch(() => {});
    await request.dispose();
  }

  if (existsSync(FIXTURE)) {
    rmSync(FIXTURE);
  }
});

/** Drives the fixture. */
async function fixture(page, action) {
  const response = await page.request.get(`/?bw_quote=${action}`);
  expect(response.ok(), `fixture action "${action}" failed`).toBe(true);
  return response.json();
}

const skipUnlessLocal = () => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
  test.skip(!canInstallFixture, 'Needs the local WordPress harness.');
};

const path = (url) => new URL(url, baseURL).pathname.replace(/\/$/, '');

/** Sets a Yes/No toggle. */
async function toggle(page, name, value) {
  await page.click(`[data-toggle="${name}"] [data-value="${value}"]`);
}

/** Sets the page count with the stepper. */
async function setPages(page, count) {
  const value = page.locator('[data-pages] [data-qty]');

  for (let guard = 0; guard < 60; guard += 1) {
    const current = Number(await value.textContent());

    if (current === count) {
      return;
    }

    await page.click(`[data-pages] [data-step="${current < count ? 'up' : 'down'}"]`);
  }

  throw new Error(`could not set pages to ${count}`);
}

test.describe('The quote builder on the Support page', () => {
  test.beforeEach(async ({ page }) => {
    skipUnlessLocal();
    await fixture(page, 'public_on');
    await page.goto(cacheBust('/support/'));
  });

  // What the page did before the quote builder, and still does with nothing
  // turned on: the slider, starting at Growth.
  test('opens on the plain slider, at Growth', async ({ page }) => {
    await expect(page.locator('[data-testid="support-calc-name"]')).toHaveText('Growth');
    await expect(page.locator('[data-testid="support-calc-price"]')).toHaveText('£500');
    await expect(page.locator('[data-hosting-mode]')).toBeHidden();
  });

  test('hosting and ClubHouse cannot both be chosen', async ({ page }) => {
    await toggle(page, 'hosting', 'yes');
    await expect(page.locator('[data-toggle="hosting"] [data-value="yes"]')).toHaveClass(/on/);

    await toggle(page, 'clubhouse', 'yes');

    await expect(page.locator('[data-toggle="clubhouse"] [data-value="yes"]')).toHaveClass(/on/);
    await expect(page.locator('[data-toggle="hosting"] [data-value="yes"]')).not.toHaveClass(/on/);
  });

  test('hosting with ongoing support keeps the slider and adds the hosting', async ({ page }) => {
    await toggle(page, 'hosting', 'yes');
    await page.click('[data-hosting-mode] [data-value="support"]');

    await expect(page.locator('input[type="range"]')).toBeVisible();
    await expect(page.locator('[data-quote-lines]')).toContainText('Website Hosting');
    await expect(page.locator('[data-quote-lines]')).toContainText('£200');
  });

  // 6 discovery + (5 × 4) design + (5 × 8) build + 30 membership + 6 + 6 + 6.
  test('a five-page rebuild with a membership system comes to 114 hours, on Growth', async ({
    page,
  }) => {
    await toggle(page, 'hosting', 'yes');
    await page.click('[data-hosting-mode] [data-value="build"]');
    await setPages(page, 5);
    await toggle(page, 'membership', 'yes');

    await expect(page.locator('[data-testid="quote-hours"]')).toHaveText('114 hrs');
    await expect(page.locator('[data-testid="support-calc-name"]')).toHaveText('Growth');
    await expect(page.locator('[data-testid="support-calc-price"]')).toHaveText('£500');
    // Growth is 140 hours a year, so 26 are left once the build is out of it.
    await expect(page.locator('[data-testid="quote-remaining"]')).toHaveText('26 hrs');
  });

  // Each integration is 20 hours, so two take the same 84-hour build to 124 —
  // past what Growth's 140 leaves room for comfortably, but still inside it.
  test('each custom integration adds twenty hours', async ({ page }) => {
    await toggle(page, 'hosting', 'yes');
    await page.click('[data-hosting-mode] [data-value="build"]');
    await setPages(page, 5);
    await page.click('[data-integrations] [data-step="up"]');
    await page.click('[data-integrations] [data-step="up"]');

    await expect(page.locator('[data-integrations] [data-qty]')).toHaveText('2');
    await expect(page.locator('[data-testid="quote-hours"]')).toHaveText('124 hrs');
    await expect(page.locator('[data-stage="integrations"]')).toContainText('40 hrs');
  });

  test('a build with no integrations does not list them', async ({ page }) => {
    await toggle(page, 'hosting', 'yes');
    await page.click('[data-hosting-mode] [data-value="build"]');

    await expect(page.locator('[data-integrations] [data-qty]')).toHaveText('0');
    await expect(page.locator('[data-stage="integrations"]')).toHaveCount(0);
  });

  // The ClubHouse page count belongs to the ClubHouse branch. It was showing
  // on a hosting build because a flex row beats the browser's own [hidden].
  test('the ClubHouse page count stays out of a hosting build', async ({ page }) => {
    await toggle(page, 'hosting', 'yes');
    await page.click('[data-hosting-mode] [data-value="build"]');

    await expect(page.locator('[data-pages]')).toBeVisible();
    await expect(page.locator('[data-pages-fixed]')).toBeHidden();
  });

  test('taking the membership system away drops it to 84 hours, on Enhance', async ({ page }) => {
    await toggle(page, 'hosting', 'yes');
    await page.click('[data-hosting-mode] [data-value="build"]');
    await setPages(page, 5);
    await toggle(page, 'membership', 'no');

    await expect(page.locator('[data-testid="quote-hours"]')).toHaveText('84 hrs');
    await expect(page.locator('[data-testid="support-calc-name"]')).toHaveText('Enhance');
  });

  test('the fixed stages are not editable', async ({ page }) => {
    await toggle(page, 'hosting', 'yes');
    await page.click('[data-hosting-mode] [data-value="build"]');

    // Discovery, testing, deployment and monitoring are 6 hours each, always.
    await expect(page.locator('[data-stage="discovery"]')).toContainText('6');
    await expect(page.locator('[data-stage="discovery"] input, [data-stage="discovery"] button')).toHaveCount(0);
  });

  test('a build too big for the largest package says so instead of quoting one', async ({
    page,
  }) => {
    await toggle(page, 'hosting', 'yes');
    await page.click('[data-hosting-mode] [data-value="build"]');
    await setPages(page, 50);

    await expect(page.locator('[data-testid="quote-hours"]')).toHaveText('624 hrs');
    await expect(page.locator('[data-testid="quote-over"]')).toBeVisible();
  });

  test('standard ClubHouse quotes the subscription alone, with no hours', async ({ page }) => {
    await toggle(page, 'clubhouse', 'yes');
    await page.click('[data-clubhouse-mode] [data-value="standard"]');

    await expect(page.locator('[data-quote-lines]')).toContainText('£200');
    await expect(page.locator('[data-testid="quote-hours"]')).toBeHidden();
    // Nothing to look after unless they ask for it, so no package either.
    await expect(page.locator('input[type="range"]')).toBeHidden();
  });

  // The setup fee pays for standing a membership system up, so it follows
  // that question rather than the ClubHouse itself.
  test('the setup fee arrives with the membership system, not before it', async ({ page }) => {
    await toggle(page, 'clubhouse', 'yes');
    await page.click('[data-clubhouse-mode] [data-value="standard"]');

    await expect(page.locator('[data-quote-lines]')).not.toContainText('£499');

    await toggle(page, 'membership', 'yes');

    await expect(page.locator('[data-quote-lines]')).toContainText('£499');
  });

  test('ongoing management on a standard ClubHouse brings the slider back', async ({ page }) => {
    await toggle(page, 'clubhouse', 'yes');
    await page.click('[data-clubhouse-mode] [data-value="standard"]');
    await toggle(page, 'management', 'yes');

    await expect(page.locator('input[type="range"]')).toBeVisible();
    await expect(page.locator('[data-testid="support-calc-name"]')).toHaveText('Growth');
  });

  // 12 pages, fixed: 6 + 48 + 96 + 6 + 6 + 6.
  test('a custom ClubHouse is a twelve-page build, at 168 hours', async ({ page }) => {
    await toggle(page, 'clubhouse', 'yes');
    await page.click('[data-clubhouse-mode] [data-value="custom"]');

    await expect(page.locator('[data-testid="quote-hours"]')).toHaveText('168 hrs');
    await expect(page.locator('[data-testid="support-calc-name"]')).toHaveText('Enterprise');
    // Twelve pages is what a ClubHouse is, so there is nothing to count up or
    // down — the stepper goes and a plain statement takes its place.
    await expect(page.locator('[data-pages]')).toBeHidden();
    await expect(page.locator('[data-pages-fixed]')).toContainText('12');
  });

  // The build hours already cover standing the site up, so charging the setup
  // fee as well charges the same work twice.
  test('a custom ClubHouse carries no setup fee', async ({ page }) => {
    await toggle(page, 'clubhouse', 'yes');
    await page.click('[data-clubhouse-mode] [data-value="custom"]');

    await expect(page.locator('[data-quote-lines]')).not.toContainText('setup');
    await expect(page.locator('[data-quote-lines]')).toContainText('£200');
  });


  test('a visitor is never shown what the sale pays us', async ({ page }) => {
    await toggle(page, 'hosting', 'yes');

    await expect(page.locator('.quote-commission')).toHaveCount(0);
  });
});

test.describe('Turning the Support page back to the plain slider', () => {
  test.beforeEach(() => skipUnlessLocal());

  test.afterEach(async ({ page }) => {
    await fixture(page, 'public_on');
  });

  test('the switch removes the questions but keeps the slider working', async ({ page }) => {
    await fixture(page, 'public_off');
    await page.goto(cacheBust('/support/'));

    await expect(page.locator('[data-toggle="hosting"]')).toHaveCount(0);
    await expect(page.locator('[data-testid="support-calc-name"]')).toHaveText('Growth');
    await expect(page.locator('[data-testid="support-calc-price"]')).toHaveText('£500');
  });

  test('the internal one is unaffected by it', async ({ page }) => {
    await fixture(page, 'public_off');
    await fixture(page, 'sales_in');
    await page.goto(cacheBust('/dashboard/quote-builder/'));

    await expect(page.locator('[data-toggle="hosting"]')).toHaveCount(1);
  });
});

test.describe('The quote builder in the Sales section', () => {
  test.beforeEach(() => skipUnlessLocal());

  test('a salesperson gets it, and sees what the quote pays them', async ({ page }) => {
    await fixture(page, 'sales_in');
    await page.goto(cacheBust('/dashboard/quote-builder/'));

    await expect(page.locator('.dash-nav a[href*="/dashboard/quote-builder"]')).toHaveCount(1);
    // The default quote is Growth alone: £6,000 a year at 10%.
    await expect(page.locator('.quote-commission')).toContainText('£600');
  });

  test('the commission follows the quote', async ({ page }) => {
    await fixture(page, 'sales_in');
    await page.goto(cacheBust('/dashboard/quote-builder/'));

    await toggle(page, 'hosting', 'yes');
    await page.click('[data-hosting-mode] [data-value="support"]');

    // Growth's £600, plus 20% of £200 of hosting.
    await expect(page.locator('.quote-commission')).toContainText('£640');
  });

  // A site and a support package are not paid at the same rate, and a single
  // total cannot say which rate did what.
  test('it says what each part of the quote pays, and at what rate', async ({ page }) => {
    await fixture(page, 'sales_in');
    await page.goto(cacheBust('/dashboard/quote-builder/'));

    await toggle(page, 'hosting', 'yes');
    await page.click('[data-hosting-mode] [data-value="support"]');

    const parts = page.locator('[data-commission-parts]');
    await expect(parts).toContainText('£40 hosting at 20%');
    await expect(parts).toContainText('£600 support at 10%');
  });

  // Growth is £6,000 a year (10%); Enterprise is £9,000 (20%).
  test('a package over the threshold pays the higher rate here too', async ({ page }) => {
    await fixture(page, 'sales_in');
    await page.goto(cacheBust('/dashboard/quote-builder/'));

    await toggle(page, 'clubhouse', 'yes');
    await page.click('[data-clubhouse-mode] [data-value="custom"]');

    await expect(page.locator('[data-commission-parts]')).toContainText('£1,800 support at 20%');
    await expect(page.locator('.quote-commission')).toContainText('£1,840');
  });

  test('an administrator can reach it and a client cannot', async ({ page }) => {
    await login(page);
    await page.goto(cacheBust('/dashboard/quote-builder/'));

    expect(path(page.url())).toBe('/dashboard/quote-builder');
  });
});
