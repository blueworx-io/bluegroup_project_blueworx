/**
 * Support packages driven by SureCart (#41).
 *
 * The prices on the old Pricing page were written into the plugin, so they
 * drifted from what SureCart actually charges, and "Get started" went to the
 * contact form rather than to a checkout. The 2026-09 restructure retired
 * Pricing in favour of the Integrated Support page (tests/marketing-support.spec.js),
 * which shows three of the nine support packages as cards — the same
 * plan-card part, wired the same way. Growth is the featured, middle package
 * (£500/month, slug "growth"); it carries no annual-billing discount, and the
 * Support page has no billing toggle to swap to an annual figure at all, so
 * these only exercise the monthly price and buy link.
 *
 * SureCart keeps prices in its own cloud, so there is nothing to seed in
 * WordPress and no way to point these specs at real products without a real
 * SureCart account. Instead the fixture below declares the one class the plugin
 * reads through — \SureCart\Models\Price — with known amounts. That tests the
 * part this repo owns: that a configured price is read, converted, rendered and
 * turned into a buy link, and above all that every failure path leaves the page
 * exactly as it was before any of this existed.
 *
 * That last one is the important one. A support page showing a stale price is a
 * problem; a support page showing a fatal error is a worse one.
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
		public $is_zero_decimal = false;
		public $currency        = 'gbp';

		/**
		 * SureCart's own converted_amount accessor, copied.
		 *
		 * The reason it exists is the reason this fixture models it: a
		 * zero-decimal currency (yen, won) has no minor unit, so dividing its
		 * amount by 100 shows a price a hundred times too small.
		 */
		public function __get( $name ) {
			if ( 'converted_amount' !== $name ) {
				return null;
			}

			if ( $this->is_zero_decimal || empty( $this->amount ) ) {
				return $this->amount;
			}

			return $this->amount / 100;
		}

		public static function find( $id ) {
			$prices = array(
				'c9e06c21-7772-4d19-821a-93edc6326d54'  => array( 24900, false ),
				'7b31d0af-2c55-4a10-9f6e-1d84c0b7a2e9'   => array( 19900, false ),
				// 24900 yen is 24900, not 249.
				'3f5a91c2-8e47-4b63-b0d1-6a2f7c94e830'      => array( 24900, true ),
				// Present but with an amount SureCart could not give us — the
				// plan should keep its built-in figure and still be buyable.
				'd20b6e84-9153-4c72-8a3f-5e0947bd1c66' => array( null, false ),
				// A store priced in dollars: the amount is $249, not £249.
				'f4c1d2e3-5a6b-4c7d-8e9f-0a1b2c3d4e5f' => array( 24900, false, 'usd' ),
				// Hosting and ClubHouse, monthly and annual.
				'e1a2b3c4-1111-4a2b-8c3d-4e5f6a7b8c9d' => array( 2500, false ),
				'e1a2b3c4-2222-4a2b-8c3d-4e5f6a7b8c9d' => array( 24000, false ),
				'e1a2b3c4-3333-4a2b-8c3d-4e5f6a7b8c9d' => array( 3000, false ),
				'e1a2b3c4-4444-4a2b-8c3d-4e5f6a7b8c9d' => array( 30000, false ),
			);

			if ( ! array_key_exists( $id, $prices ) ) {
				// SureCart RETURNS its failures rather than throwing them, so
				// a fixture that threw here would test a path the plugin never
				// takes on a real site.
				return new \\WP_Error( 'not_found', 'No such price: ' . $id );
			}

			$price = new self();
			$price->amount          = $prices[ $id ][0];
			$price->is_zero_decimal = $prices[ $id ][1];
			$price->currency        = isset( $prices[ $id ][2] ) ? $prices[ $id ][2] : 'gbp';

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
					'growth' => array( 'm' => 'c9e06c21-7772-4d19-821a-93edc6326d54', 'a' => '7b31d0af-2c55-4a10-9f6e-1d84c0b7a2e9' ),
				) );
				break;

			case 'missing':
				update_option( 'blueworx_surecart_price_ids', array(
					'growth' => array( 'm' => 'a1c4f7e0-3b28-4d95-8c61-2f0e5a83b7d4', 'a' => 'a1c4f7e0-3b28-4d95-8c61-2f0e5a83b7d4' ),
				) );
				break;

			case 'zero-decimal':
				update_option( 'blueworx_surecart_price_ids', array(
					'growth' => array( 'm' => '3f5a91c2-8e47-4b63-b0d1-6a2f7c94e830', 'a' => '' ),
				) );
				break;

			case 'no-amount':
				update_option( 'blueworx_surecart_price_ids', array(
					'growth' => array( 'm' => 'd20b6e84-9153-4c72-8a3f-5e0947bd1c66', 'a' => '' ),
				) );
				break;

			case 'usd':
				update_option( 'blueworx_surecart_price_ids', array(
					'growth' => array( 'm' => 'f4c1d2e3-5a6b-4c7d-8e9f-0a1b2c3d4e5f', 'a' => '' ),
				) );
				break;

			case 'hosting-clubhouse-wired':
				update_option( 'blueworx_surecart_price_ids', array(
					'managed-hosting' => array( 'm' => 'e1a2b3c4-1111-4a2b-8c3d-4e5f6a7b8c9d', 'a' => 'e1a2b3c4-2222-4a2b-8c3d-4e5f6a7b8c9d' ),
					'clubhouse'       => array( 'm' => 'e1a2b3c4-3333-4a2b-8c3d-4e5f6a7b8c9d', 'a' => 'e1a2b3c4-4444-4a2b-8c3d-4e5f6a7b8c9d' ),
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
 * brackets in `line_items[0][price_id]` are percent-encoded in the URL, so a
 * substring check on the readable form silently never matches.
 *
 * The key is `price_id`. This helper used to read `price`, matching what the
 * plugin wrote, so the specs agreed with the code and both were wrong — the
 * checkout SureCart actually received had no line items on it.
 */
function buyPriceId(href) {
  return new URL(href, 'http://localhost').searchParams.get('line_items[0][price_id]');
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

test.describe('Support packages driven by SureCart', () => {
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
    await page.goto(cacheBust('/support/'));

    const card = planCard(page, 'Growth');

    // 24900 minor units rendered as whole units, not as 24900.
    await expect(card.locator('.plan-price b')).toHaveText('£249');

    const href = await card.locator('a.plan-btn').getAttribute('href');
    expect(new URL(href).pathname.replace(/\/$/, '')).toBe('/checkout');
    expect(buyPriceId(href)).toBe('c9e06c21-7772-4d19-821a-93edc6326d54');
  });

  // A currency with no minor unit is not cents. Dividing by 100 here would
  // advertise a plan at a hundredth of its price — and would look completely
  // normal to anyone not billing in yen.
  test('a zero-decimal currency is not divided by a hundred', async ({ page }) => {
    await setFixture(page, 'zero-decimal');
    await page.goto(cacheBust('/support/'));

    await expect(planCard(page, 'Growth').locator('.plan-price b')).toHaveText('£24,900');
  });

  test('an unwired plan keeps its built-in price and the contact form', async ({ page }) => {
    await setFixture(page, 'wired');
    await page.goto(cacheBust('/support/'));

    const card = planCard(page, 'Starter');

    await expect(card.locator('.plan-price b')).toHaveText('£100');
    expect(await card.locator('a.plan-btn').getAttribute('href')).toContain('/contact');
  });

  test('a price ID SureCart no longer has falls back rather than breaking the page', async ({
    page,
  }) => {
    await setFixture(page, 'missing');
    await page.goto(cacheBust('/support/'));

    // The page still renders in full, and the plan shows the figure written
    // into the plugin.
    await expect(page.locator('.plans .plan-card')).toHaveCount(3);
    await expect(planCard(page, 'Growth').locator('.plan-price b')).toHaveText('£500');
  });

  test('a price with no readable amount keeps its figure but stays buyable', async ({ page }) => {
    await setFixture(page, 'no-amount');
    await page.goto(cacheBust('/support/'));

    const card = planCard(page, 'Growth');

    await expect(card.locator('.plan-price b')).toHaveText('£500');
    expect(buyPriceId(await card.locator('a.plan-btn').getAttribute('href'))).toBe(
      'd20b6e84-9153-4c72-8a3f-5e0947bd1c66'
    );
  });

  // A SureCart store priced in dollars must show dollars — and must not then
  // be run through the pound-to-dollar conversion as though it were pounds.
  test('a price in another currency keeps its own sign and is never converted', async ({ page }) => {
    await setFixture(page, 'usd');
    await page.goto(cacheBust('/support/'));

    const price = planCard(page, 'Growth').locator('.plan-price b');

    await expect(price).toHaveText('$249');
    await expect(price).not.toHaveAttribute('data-bw-gbp', /.*/);

    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="EUR"]').click();
    await expect(price).toHaveText('$249');
  });

  test('with nothing configured the page is exactly as it was', async ({ page }) => {
    await setFixture(page, 'off');
    await page.goto(cacheBust('/support/'));

    await expect(planCard(page, 'Growth').locator('.plan-price b')).toHaveText('£500');

    const hrefs = await page
      .locator('.plans a.plan-btn')
      .evaluateAll((els) => els.map((el) => el.getAttribute('href') || ''));

    expect(hrefs).toHaveLength(3);
    expect(hrefs.every((href) => /\/contact/.test(href))).toBe(true);
  });
});

// Hosting and ClubHouse each show a single plan card (parts/plan-card.php),
// wired through the same blueworx_commerce_apply_live_single_plan() filter
// that overlays SureCart prices onto it — the single-plan counterpart to the
// nine-package grid above.
test.describe('Hosting and ClubHouse plans driven by SureCart', () => {
  test.beforeEach(() => {
    test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
    test.skip(!canInstallFixture, 'Needs the local WordPress harness.');
  });

  test.afterEach(async ({ page }) => {
    await restoreAll([['clear the commerce options', async () => setFixture(page, 'off')]]);
  });

  for (const { path, name, monthlyId, annualId, monthlyGbp } of [
    {
      path: '/hosting/',
      name: 'Managed Hosting',
      monthlyId: 'e1a2b3c4-1111-4a2b-8c3d-4e5f6a7b8c9d',
      annualId: 'e1a2b3c4-2222-4a2b-8c3d-4e5f6a7b8c9d',
      monthlyGbp: '£25',
    },
    {
      path: '/clubhouse/',
      name: 'ClubHouse',
      monthlyId: 'e1a2b3c4-3333-4a2b-8c3d-4e5f6a7b8c9d',
      annualId: 'e1a2b3c4-4444-4a2b-8c3d-4e5f6a7b8c9d',
      monthlyGbp: '£30',
    },
  ]) {
    test(`${name}: wired shows SureCart's price and the annual toggle swaps the checkout link`, async ({
      page,
    }) => {
      await setFixture(page, 'hosting-clubhouse-wired');
      await page.goto(cacheBust(path));

      const card = page.locator('.bw-plan-grid .plan-card').filter({
        has: page.locator('.plan-name span', { hasText: name }),
      });

      // 2500 / 3000 minor units read from the fixture, not the £20 built in.
      await expect(card.locator('.plan-price b')).toHaveText(monthlyGbp);

      const link = card.locator('a.plan-btn');
      await expect(link).toHaveAttribute('data-buy-m', new RegExp(monthlyId));
      await expect(link).toHaveAttribute('data-buy-a', new RegExp(annualId));
      expect(buyPriceId(await link.getAttribute('href'))).toBe(monthlyId);

      await page.locator('.bill-toggle button').nth(1).click();
      expect(buyPriceId(await link.getAttribute('href'))).toBe(annualId);
    });
  }

  test('with nothing configured Hosting and ClubHouse keep £20 and a contact button', async ({
    page,
  }) => {
    await setFixture(page, 'off');

    for (const path of ['/hosting/', '/clubhouse/']) {
      await page.goto(cacheBust(path));
      const card = page.locator('.bw-plan-grid .plan-card').first();
      await expect(card.locator('.plan-price b')).toHaveText('£20');
      expect(await card.locator('a.plan-btn').getAttribute('href')).toContain('/contact');
    }
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
    await expect(page.locator('#blueworx_price_growth_m')).toHaveCount(1);
    await expect(page.locator('#blueworx_price_growth_a')).toHaveCount(1);
    // The nine support packages plus Hosting and ClubHouse, two intervals each.
    await expect(page.locator('input[name^="blueworx_surecart_price_ids"]')).toHaveCount(22);
  });

  // The stored value ends up in a URL visitors are sent to, so the field takes
  // SureCart IDs and nothing else.
  test('anything that is not a SureCart price ID is not stored', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=bluegroup-project-blueworx');
    await page.fill('#blueworx_price_growth_m', 'javascript:alert(1)');
    await page.click('#submit');
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('#blueworx_price_growth_m')).toHaveValue('');
  });

  // The counterpart to the test above, and the one that was missing. Every
  // other spec here writes the option straight into the database, so nothing
  // ever put a real SureCart ID through the settings form — which is where the
  // ID was being thrown away. A real price ID is a UUID, and the field used to
  // demand a `price_` prefix that no SureCart ID has.
  test('a real SureCart price ID survives being saved', async ({ page }) => {
    const priceId = 'c9e06c21-7772-4d19-821a-93edc6326d54';

    await page.goto('/wp-admin/options-general.php?page=bluegroup-project-blueworx');
    await page.fill('#blueworx_price_growth_m', priceId);
    await page.click('#submit');
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('#blueworx_price_growth_m')).toHaveValue(priceId);
  });

  // Losing the ID was survivable; losing it without a word is what cost the
  // time. The page must say which plan it rejected.
  test('a rejected price ID is reported rather than silently blanked', async ({ page }) => {
    await page.goto('/wp-admin/options-general.php?page=bluegroup-project-blueworx');
    await page.fill('#blueworx_price_growth_m', 'javascript:alert(1)');
    await page.click('#submit');
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('.notice-error')).toContainText('Growth');
  });
});
