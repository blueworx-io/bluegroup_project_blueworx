/**
 * The dashboard's three sections, reading from SureCart (#38, #39, #40).
 *
 * SureCart keeps subscriptions, invoices and orders in its own cloud, so there
 * is nothing to seed in WordPress and no way to point these specs at real
 * records without a real SureCart account. The fixture below declares the model
 * classes the plugin reads through, with known records, and records the query
 * it was asked for.
 *
 * That last part is the point of the most important spec here. The rule this
 * code has to keep is that a client sees their own records and nobody else's,
 * and the way that breaks is not a visible bug — it is a query that quietly
 * loses its customer filter and starts returning everything. So the fixture
 * reports what it was actually asked, and the spec asserts on it.
 *
 * The other rule worth a spec: an API failure must not be shown to a paying
 * client as "you have no subscriptions".
 */

import { test, expect, login, isPlaceholder } from './helpers.js';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-surecart-records.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture: stand-ins for the SureCart models the client area reads, plus
 * an endpoint to choose what they do. Written by tests/dashboard-data.spec.js
 * and removed afterwards. Only ever exists inside the disposable local
 * WordPress the test run creates.
 */

namespace SureCart\\Models {
	class BwFixtureQuery {
		public $rows;

		public function __construct( $rows ) {
			$this->rows = $rows;
		}

		// Mirrors the real API: with() is how related records are asked for,
		// chained before get(). There is deliberately no orderBy() — SureCart
		// has none, and calling one recurses through its __call facade until
		// PHP gives up.
		public function with( $expand ) {
			$seen   = get_option( 'bw_sc_expand', array() );
			$seen[] = (array) $expand;
			update_option( 'bw_sc_expand', $seen );

			return $this;
		}

		public function get() {
			// SureCart RETURNS its failures rather than throwing them. A
			// fixture that threw would test a path the real plugin never
			// takes, and pass while the real one rendered a WP_Error as an
			// empty account.
			if ( 'fail' === get_option( 'bw_sc_mode' ) ) {
				return new \\WP_Error( 'surecart_down', 'SureCart is having a moment' );
			}

			return $this->rows;
		}
	}

	abstract class BwFixtureModel {
		public static function where( $args ) {
			// Record what the plugin actually asked for. The customer filter
			// going missing is the failure worth catching.
			$seen = get_option( 'bw_sc_queries', array() );
			$seen[ static::class ] = $args;
			update_option( 'bw_sc_queries', $seen );

			return new BwFixtureQuery( 'empty' === get_option( 'bw_sc_mode' ) ? array() : static::bwRows() );
		}
	}

	class Subscription extends BwFixtureModel {
		public static function bwRows() {
			$product = (object) array( 'name' => 'Growth Support' );
			// display_amount and interval_text are formatted by SureCart, and
			// are why nothing on our side divides an amount by 100 — a
			// zero-decimal currency has no minor unit to divide.
			$price = (object) array(
				'display_amount' => '$500.00',
				'interval_text'  => '/month',
				'product'        => $product,
			);

			return array(
				(object) array(
					'id'                         => 'sub_fixture1',
					'status'                     => 'active',
					'price'                      => $price,
					'current_period_end_at_date' => '14 September 2026',
				),
			);
		}
	}

	class Invoice extends BwFixtureModel {
		public static function bwRows() {
			return array(
				(object) array(
					'id'              => 'inv_fixture1',
					'status'          => 'paid',
					'issue_date_date' => '2 February 2026',
					'checkout_url'    => 'https://example.invalid/pay/1',
					'checkout'        => (object) array(
						'total_display_amount' => '$249.00',
						'order'                => (object) array( 'number' => 'BW-1041' ),
					),
				),
				(object) array(
					'id'              => 'inv_fixture2',
					'status'          => 'open',
					'issue_date_date' => '26 February 2026',
					'checkout_url'    => 'https://example.invalid/pay/2',
					'checkout'        => (object) array(
						'total_display_amount' => '$500.00',
						'order'                => (object) array( 'number' => 'BW-1042' ),
					),
				),
			);
		}
	}

	class Order extends BwFixtureModel {
		public static function bwRows() {
			$checkout = (object) array( 'total_display_amount' => '$750.00' );

			return array(
				(object) array(
					'id'         => 'ord_fixture1',
					'number'     => 'ORD-77',
					'status'     => 'paid',
					'checkout'   => $checkout,
					'created_at' => 1768000000,
				),
			);
		}
	}
}

namespace {
	add_action( 'init', function () {
		if ( ! isset( $_GET['bw_sc'] ) ) {
			return;
		}

		$mode    = sanitize_text_field( wp_unslash( $_GET['bw_sc'] ) );
		$user_id = get_current_user_id() ? get_current_user_id() : 1;

		delete_option( 'bw_sc_queries' );
		delete_option( 'bw_sc_expand' );

		if ( 'off' === $mode ) {
			delete_option( 'bw_sc_mode' );
			delete_user_meta( $user_id, 'sc_customer_ids' );
		} elseif ( 'no-customer' === $mode ) {
			update_option( 'bw_sc_mode', 'records' );
			delete_user_meta( $user_id, 'sc_customer_ids' );
		} else {
			update_option( 'bw_sc_mode', $mode );
			update_user_meta( $user_id, 'sc_customer_ids', array( 'live' => 'cus_fixture' ) );
		}

		wp_send_json( array(
			'mode'    => (string) get_option( 'bw_sc_mode', '' ),
			'queries' => (array) get_option( 'bw_sc_queries', array() ),
		) );
	} );

	add_action( 'init', function () {
		if ( ! isset( $_GET['bw_sc_queries'] ) ) {
			return;
		}

		wp_send_json( array(
			'queries' => (array) get_option( 'bw_sc_queries', array() ),
			'expand'  => (array) get_option( 'bw_sc_expand', array() ),
		) );
	} );
}
`;

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

/** Puts the fake SureCart into one of its states. */
async function setMode(page, mode) {
  const response = await page.request.get(`/?bw_sc=${mode}`);
  expect(response.ok(), `could not set fixture mode "${mode}"`).toBe(true);
}

test.describe('Dashboard sections', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
    test.skip(!canInstallFixture, 'Needs the local WordPress harness.');
    await login(page);
  });

  test.afterEach(async ({ page }) => {
    await setMode(page, 'off');
  });

  test('subscriptions shows the plan, its price and when it renews', async ({ page }) => {
    await setMode(page, 'records');
    await page.goto('/dashboard/subscriptions/');

    await expect(page.locator('.dash-table tbody tr')).toHaveCount(1);
    // The product name only resolves because the price and its product were
    // expanded in the query — see the next spec.
    await expect(page.locator('td[data-col="name"]')).toHaveText('Growth Support');
    // SureCart formats the money itself; nothing here divides by 100.
    await expect(page.locator('td[data-col="amount"]')).toHaveText('$500.00 /month');
    await expect(page.locator('td[data-col="renews"]')).toHaveText('14 September 2026');
    // SureCart's own word is "active"; a client should read a word, not a flag.
    await expect(page.locator('.dash-status')).toHaveText('Active');
  });

  // Silent when wrong: SureCart returns a related record as a bare ID unless
  // it is expanded, so every plan would quietly render as the fallback name
  // with nothing to say anything had gone wrong.
  test('related records are asked for, not assumed', async ({ page }) => {
    await setMode(page, 'records');
    await page.goto('/dashboard/subscriptions/');

    const { expand } = await (await page.request.get('/?bw_sc_queries=1')).json();

    expect(expand.flat()).toEqual(expect.arrayContaining(['price', 'price.product']));
  });

  test('invoices lists each one, offering payment only on the unpaid one', async ({ page }) => {
    await setMode(page, 'records');
    await page.goto('/dashboard/invoices/');

    await expect(page.locator('.dash-table tbody tr')).toHaveCount(2);
    await expect(page.locator('td[data-col="number"]').first()).toHaveText('BW-1041');
    await expect(page.locator('td[data-col="date"]').first()).toHaveText('2 February 2026');
    await expect(page.locator('td[data-col="amount"]').first()).toHaveText('$249.00');
    // Only the open invoice gets a Pay now link — offering one against an
    // already-paid invoice is how a client pays twice.
    await expect(page.locator('.dash-pay')).toHaveCount(1);
  });

  test('orders lists each order with its total', async ({ page }) => {
    await setMode(page, 'records');
    await page.goto('/dashboard/orders/');

    await expect(page.locator('.dash-table tbody tr')).toHaveCount(1);
    await expect(page.locator('td[data-col="number"]')).toHaveText('ORD-77');
    await expect(page.locator('td[data-col="amount"]')).toHaveText('$750.00');
  });

  // The rule that matters most: a client sees their own records and nobody
  // else's. The way that breaks is a query that quietly loses its filter.
  test('every section asks SureCart only for this client’s records', async ({ page }) => {
    await setMode(page, 'records');

    for (const section of ['subscriptions', 'invoices', 'orders']) {
      await page.goto(`/dashboard/${section}/`);
    }

    const { queries } = await (await page.request.get('/?bw_sc_queries=1')).json();
    const asked = Object.values(queries);

    expect(asked.length).toBe(3);
    for (const args of asked) {
      expect(args.customer_ids).toEqual(['cus_fixture']);
    }
  });

  // Telling a paying client they have no subscriptions because an API call
  // failed is worse than telling them something went wrong.
  test('a SureCart failure says so rather than claiming the account is empty', async ({ page }) => {
    await setMode(page, 'fail');
    await page.goto('/dashboard/subscriptions/');

    await expect(page.locator('.dash-error')).toHaveCount(1);
    await expect(page.locator('.dash-table')).toHaveCount(0);
    await expect(page.locator('.dash-error')).not.toContainText('no active plans');
  });

  test('a client with nothing yet gets the empty state, not an error', async ({ page }) => {
    await setMode(page, 'empty');
    await page.goto('/dashboard/orders/');

    await expect(page.locator('.dash-empty')).toHaveCount(1);
    await expect(page.locator('.dash-error')).toHaveCount(0);
    await expect(page.locator('.dash-empty')).toContainText('not placed any orders');
  });

  // No SureCart customer means no records — never an unfiltered query.
  test('a user with no SureCart customer is never queried for', async ({ page }) => {
    await setMode(page, 'no-customer');
    await page.goto('/dashboard/invoices/');

    await expect(page.locator('.dash-empty')).toHaveCount(1);
    await expect(page.locator('.dash-error')).toHaveCount(0);

    const { queries } = await (await page.request.get('/?bw_sc_queries=1')).json();
    expect(Object.keys(queries)).toHaveLength(0);
  });
});
