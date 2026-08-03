/**
 * Pricing driven by SureCart (#41).
 *
 * The prices on the Pricing page were written into the plugin, so they drifted
 * from what SureCart actually charges, and "Get started" went to the contact
 * form rather than to a checkout.
 *
 * SureCart keeps prices in its own cloud, so there is nothing to seed in
 * WordPress and no way to point these specs at real products without a real
 * SureCart account. Instead the fixture below declares the one class the plugin
 * reads through — \SureCart\Models\Price — with known amounts. That tests the
 * part this repo owns: that a configured price is read, converted, rendered and
 * turned into a buy link, that the billing toggle moves the link as well as the
 * figure, and above all that every failure path leaves the page exactly as it
 * was before any of this existed.
 *
 * That last one is the important one. A pricing page showing a stale price is a
 * problem; a pricing page showing a fatal error is a worse one.
 */

import { test, expect, login, restoreAll, cacheBust, isPlaceholder } from './helpers.js';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-surecart-price.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

// Amounts are in the currency's minor unit, as SureCart stores them — the
// conversion to whole units is one of the things under test here.
const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture: a stand-in for SureCart's price model, plus a way to set and
 * clear the plugin's commerce options over a logged-out GET. Written by
 * tests/commerce-pricing.spec.js and removed afterwards. Only ever exists
 * inside the disposable local WordPress the test run creates.
 *
 * mu-plugins load before regular plugins, so this class exists by the time the
 * plugin's class_exists() check runs — which is the whole point.
 */

namespace SureCart\\Models {
	class Price {
		public $amount;

		public static function find( $id ) {
			$amounts = array(
				'price_fixtureMonthly' => 24900,
				'price_fixtureAnnual'  => 19900,
				// Present but with an amount SureCart could not give us — the
				// plan should keep its built-in figure and still be buyable.
				'price_fixtureNoAmount' => null,
			);

			if ( ! array_key_exists( $id, $amounts ) ) {
				// What SureCart does for an ID that no longer exists.
				throw new \\Exception( 'No such price: ' . $id );
			}

			$price = new self();
			$price->amount = $amounts[ $id ];

			return $price;
		}
	}
}

namespace {
	add_action( 'init', function () {
		if ( ! isset( $_GET['bw_price_fixture'] ) ) {
			return;
		}

		delete_transient( 'blueworx_commerce_price_amounts' );

		switch ( $_GET['bw_price_fixture'] ) {
			case 'wired':
				update_option( 'blueworx_surecart_price_ids', array(
					'growth-support' => array( 'm' => 'price_fixtureMonthly', 'a' => 'price_fixtureAnnual' ),
				) );
				break;

			case 'missing':
				update_option( 'blueworx_surecart_price_ids', array(
					'growth-support' => array( 'm' => 'price_fixtureDeleted', 'a' => 'price_fixtureDeleted' ),
				) );
				break;

			case 'no-amount':
				update_option( 'blueworx_surecart_price_ids', array(
					'growth-support' => array( 'm' => 'price_fixtureNoAmount', 'a' => '' ),
				) );
				break;

			default:
				delete_option( 'blueworx_surecart_price_ids' );
				delete_option( 'blueworx_checkout_url' );
				break;
		}

		wp_die( 'bw-price-fixture-ok', 'OK', array( 'response' => 200 ) );
	} );
}
`;

/** The card for a named plan. */
const planCard = (page, name) =>
  page.locator('.plan-card').filter({ has: page.locator('.plan-name span', { hasText: name }) });

/**
 * The price ID a buy link starts checkout with.
 *
 * Read through URLSearchParams rather than by matching the raw href: the
 * brackets in `line_items[0][price]` are percent-encoded in the URL, so a
 * substring check on the readable form silently never matches.
 */
function buyPriceId(href) {
  return new URL(href, 'http://localhost').searchParams.get('line_items[0][price]');
}

/** Puts the fixture into one of its states. */
async function setFixture(page, state) {
  const response = await page.request.get(`/?bw_price_fixture=${state}`);
  expect(response.status()).toBe(200);
}

test.beforeAll(() => {
  if (isPlaceholder || !canInstallFixture) {
    return;
  }
  mkdirSync(MU_DIR, { recursive: true });
  writeFileSync(FIXTURE, FIXTURE_PLUGIN);
});

test.afterAll(() => {
  if (existsSync(FIXTURE)) {
    rmSync(FIXTURE);
  }
});

test.describe('Pricing driven by SureCart', () => {
  test.beforeEach(() => {
    test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
    test.skip(!canInstallFixture, 'Needs the local WordPress harness.');
  });

  // These options are site-wide, so cleanup goes over a logged-out GET rather
  // than through wp-admin: cleanup that needs a working admin session is
  // cleanup that leaks into other specs the moment a login flakes.
  test.afterEach(async ({ page }) => {
    await restoreAll([['clear the commerce options', async () => setFixture(page, 'off')]]);
  });

  test('a wired plan shows SureCart’s price and a checkout link', async ({ page }) => {
    await setFixture(page, 'wired');
    await page.goto(cacheBust('/pricing/'));

    const card = planCard(page, 'Growth Support');

    // 24900 minor units rendered as whole units, not as 24900.
    await expect(card.locator('.plan-price b')).toHaveText('$249');
    await expect(card.locator('.plan-price')).toHaveAttribute('data-price-a', '199');

    const href = await card.locator('a.plan-btn').getAttribute('href');
    expect(new URL(href).pathname.replace(/\/$/, '')).toBe('/checkout');
    expect(buyPriceId(href)).toBe('price_fixtureMonthly');
  });

  test('the billing toggle moves the checkout link as well as the price', async ({ page }) => {
    await setFixture(page, 'wired');
    await page.goto(cacheBust('/pricing/'));

    const card = planCard(page, 'Growth Support');
    await page.locator('.bill-toggle button', { hasText: 'Annual' }).click();

    await expect(card.locator('.plan-price b')).toHaveText('$199');

    // The failure this guards against is silent and expensive: choosing annual
    // billing and being charged monthly.
    const href = await card.locator('a.plan-btn').getAttribute('href');
    expect(buyPriceId(href)).toBe('price_fixtureAnnual');
  });

  test('an unwired plan keeps its built-in price and the contact form', async ({ page }) => {
    await setFixture(page, 'wired');
    await page.goto(cacheBust('/pricing/'));

    const card = planCard(page, 'Essential Support');

    await expect(card.locator('.plan-price b')).toHaveText('$200');
    expect(await card.locator('a.plan-btn').getAttribute('href')).toContain('/contact');
  });

  test('a price ID SureCart no longer has falls back rather than breaking the page', async ({
    page,
  }) => {
    await setFixture(page, 'missing');
    await page.goto(cacheBust('/pricing/'));

    // The page still renders in full, and the plan shows the figure written
    // into the plugin.
    await expect(page.locator('.plans .plan-card')).toHaveCount(3);
    await expect(planCard(page, 'Growth Support').locator('.plan-price b')).toHaveText('$500');
  });

  test('a price with no readable amount keeps its figure but stays buyable', async ({ page }) => {
    await setFixture(page, 'no-amount');
    await page.goto(cacheBust('/pricing/'));

    const card = planCard(page, 'Growth Support');

    await expect(card.locator('.plan-price b')).toHaveText('$500');
    expect(buyPriceId(await card.locator('a.plan-btn').getAttribute('href'))).toBe(
      'price_fixtureNoAmount'
    );
  });

  test('with nothing configured the page is exactly as it was', async ({ page }) => {
    await setFixture(page, 'off');
    await page.goto(cacheBust('/pricing/'));

    await expect(planCard(page, 'Growth Support').locator('.plan-price b')).toHaveText('$500');

    const hrefs = await page
      .locator('.plans a.plan-btn')
      .evaluateAll((els) => els.map((el) => el.getAttribute('href') || ''));

    expect(hrefs).toHaveLength(3);
    expect(hrefs.every((href) => /\/contact/.test(href))).toBe(true);
  });
});

test.describe('Pricing settings', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
    test.skip(!canInstallFixture, 'Needs the local WordPress harness.');
    await login(page);
  });

  test.afterEach(async ({ page }) => {
    await restoreAll([['clear the commerce options', async () => setFixture(page, 'off')]]);
  });

  test('the screen offers a price ID per plan per interval, and a checkout page', async ({
    page,
  }) => {
    await page.goto('/wp-admin/options-general.php?page=bluegroup-project-blueworx');

    await expect(page.locator('#blueworx_checkout_url')).toHaveCount(1);
    await expect(page.locator('#blueworx_price_growth-support_m')).toHaveCount(1);
    await expect(page.locator('#blueworx_price_growth-support_a')).toHaveCount(1);
    // Three plans, two intervals.
    await expect(page.locator('input[name^="blueworx_surecart_price_ids"]')).toHaveCount(6);
  });

  // The stored value ends up in a URL visitors are sent to, so the field takes
  // SureCart IDs and nothing else.
  test('anything that is not a SureCart price ID is not stored', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=bluegroup-project-blueworx');
    await page.fill('#blueworx_price_growth-support_m', 'javascript:alert(1)');
    await page.click('#submit');
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('#blueworx_price_growth-support_m')).toHaveValue('');
  });
});
