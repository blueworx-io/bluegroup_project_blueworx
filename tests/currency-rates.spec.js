/**
 * Live exchange rates for the currency switcher (includes/public/currency.php).
 *
 * The switcher launched with two fixed rates typed into the script. This spec
 * covers what replaced them: rates fetched from the ECB feed, stored, and
 * handed to every owned page as window.blueworxCurrency — and, above all, what
 * happens when that feed is down, slow, or returns rubbish. A price page that
 * shows a day-old euro figure is fine; one that shows €0, or nothing, or takes
 * eight seconds to load because a rate service is timing out, is not.
 *
 * Same fixture strategy as commerce-pricing.spec.js: a throwaway mu-plugin that
 * answers the feed's HTTP request itself (WordPress's pre_http_request filter),
 * so nothing here ever reaches the real service, and a logged-out GET that puts
 * the site into each state.
 */

import { test, expect, login, restoreAll, cacheBust, isPlaceholder } from './helpers.js';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-currency-rates.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

const SETTINGS_PATH = '/wp-admin/options-general.php?page=bluegroup-project-blueworx';

const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture for tests/currency-rates.spec.js: stands in for the exchange
 * rate service and puts the plugin's stored rates into a known state. Written
 * by the spec and removed again afterwards. Never shipped.
 */

/** A WP_Http-shaped success response. */
function bw_test_rates_response( $body ) {
	return array(
		'response'      => array( 'code' => 200, 'message' => 'OK' ),
		'headers'       => array(),
		'body'          => $body,
		'cookies'       => array(),
		'filename'      => null,
		'http_response' => null,
	);
}

add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
	if ( false === strpos( (string) $url, 'frankfurter' ) ) {
		return $pre;
	}

	// Only page loads are counted: the scheduled refresh may fire at any
	// point during the run, and it is the on-demand throttle under test.
	if ( ! wp_doing_cron() ) {
		update_option( 'bw_test_rates_calls', (int) get_option( 'bw_test_rates_calls', 0 ) + 1, false );
	}

	switch ( get_option( 'bw_test_rates_mode', 'ok' ) ) {
		case 'fail':
			return new WP_Error( 'http_request_failed', 'fixture: the feed is down' );

		case 'junk':
			return bw_test_rates_response( '{"rates":{"EUR":0,"USD":"soon","ZAR":20,"AUD":2}}' );

		case 'partial':
			// The feed answered, but without every currency the site offers.
			return bw_test_rates_response( '{"rates":{"EUR":1.5,"USD":2}}' );

		default:
			return bw_test_rates_response( wp_json_encode( array(
				'amount' => 1,
				'base'   => 'GBP',
				'date'   => '2026-09-19',
				'rates'  => array( 'EUR' => 1.5, 'USD' => 2, 'ZAR' => 25, 'AUD' => 2.5 ),
			) ) );
	}
}, 10, 3 );

add_action( 'init', function () {
	if ( ! isset( $_GET['bw_rates_fixture'] ) ) {
		return;
	}

	$state = (string) $_GET['bw_rates_fixture'];

	if ( 'state' === $state ) {
		wp_send_json( array(
			'current'   => blueworx_currency_rates(),
			'calls'     => (int) get_option( 'bw_test_rates_calls', 0 ),
			'scheduled' => (bool) wp_next_scheduled( 'blueworx_currency_refresh' ),
		) );
	}

	delete_option( 'blueworx_currency_rates' );
	delete_transient( 'blueworx_currency_rates_lock' );
	delete_option( 'bw_test_rates_calls' );

	switch ( $state ) {
		case 'ok':
		case 'fail':
		case 'junk':
		case 'partial':
			update_option( 'bw_test_rates_mode', $state, false );
			break;

		case 'old-set':
			// Figures stored by a release that only knew euros and dollars,
			// fetched an hour ago — fresh by age, but missing currencies.
			update_option( 'bw_test_rates_mode', 'ok', false );
			update_option( 'blueworx_currency_rates', array(
				'rates'   => array( 'EUR' => 1.5, 'USD' => 2 ),
				'date'    => '2026-09-18',
				'fetched' => time() - HOUR_IN_SECONDS,
			), false );
			break;

		case 'stale-fail':
			update_option( 'bw_test_rates_mode', 'fail', false );
			update_option( 'blueworx_currency_rates', array(
				'rates'   => array( 'EUR' => 1.5, 'USD' => 2, 'ZAR' => 25, 'AUD' => 2.5, 'AED' => 7.345 ),
				'date'    => '2026-09-18',
				'fetched' => time() - 2 * DAY_IN_SECONDS,
			), false );
			break;

		default:
			delete_option( 'bw_test_rates_mode' );
			break;
	}

	wp_die( 'bw-rates-fixture-ok', 'OK', array( 'response' => 200 ) );
}, 1 );
`;

/** Puts the fixture into one of its states. */
async function setFixture(page, state) {
  const response = await page.request.get(`/?bw_rates_fixture=${state}`);
  expect(response.status()).toBe(200);
}

/** What the plugin would serve right now, and how often the feed was called. */
async function fixtureState(page) {
  const response = await page.request.get('/?bw_rates_fixture=state');
  expect(response.status()).toBe(200);
  return response.json();
}

/** The rates a rendered page carries. */
const pageRates = (page) => page.evaluate(() => window.blueworxCurrency);

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

test.describe('Live exchange rates', () => {
  test.beforeEach(() => {
    test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
    test.skip(!canInstallFixture, 'Needs the local WordPress harness.');
  });

  test.afterEach(async ({ page }) => {
    await restoreAll([['clear the stored rates', async () => setFixture(page, 'off')]]);
  });

  test('rates from the feed are stored and every page converts with them', async ({ page }) => {
    await setFixture(page, 'ok');
    await page.goto(cacheBust('/hosting/'));

    const served = await pageRates(page);
    expect(served.source).toBe('live');
    expect(served.date).toBe('2026-09-19');
    // AED is USD × 3.6725, the dirham's peg — the feed never sends it.
    expect(served.rates).toEqual({ GBP: 1, EUR: 1.5, USD: 2, ZAR: 25, AUD: 2.5, AED: 7.345 });

    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="USD"]').click();
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('$40');

    // Stored, so the next page does not ask the feed again.
    await page.goto(cacheBust('/support/'));
    expect((await fixtureState(page)).calls).toBe(1);
  });

  test('a feed that is down leaves the launch rates in place and is not retried on every page', async ({ page }) => {
    await setFixture(page, 'fail');
    await page.goto(cacheBust('/hosting/'));

    const served = await pageRates(page);
    expect(served.source).toBe('fallback');
    expect(served.rates).toEqual({ GBP: 1, EUR: 1.17, USD: 1.27, ZAR: 21.8, AUD: 1.88, AED: 4.66 });

    await page.goto(cacheBust('/support/'));
    await page.goto(cacheBust('/clubhouse/'));
    expect((await fixtureState(page)).calls).toBe(1);
  });

  test('a broken response is thrown away rather than painting prices at zero', async ({ page }) => {
    await setFixture(page, 'junk');
    await page.goto(cacheBust('/hosting/'));

    const served = await pageRates(page);
    expect(served.source).toBe('fallback');
    expect(served.rates.EUR).toBe(1.17);
  });

  test('a feed missing a currency is thrown away too', async ({ page }) => {
    await setFixture(page, 'partial');
    await page.goto(cacheBust('/hosting/'));

    const served = await pageRates(page);
    expect(served.source).toBe('fallback');
    expect(served.rates.ZAR).toBe(21.8);
  });

  test('rates stored before a currency was added are refreshed, and the fallback fills the gap until then', async ({ page }) => {
    await setFixture(page, 'old-set');
    await page.goto(cacheBust('/hosting/'));

    const served = await pageRates(page);
    expect(served.source).toBe('live');
    // Fresh by age but short of ZAR, AUD and AED, so it was refreshed at once.
    expect(served.rates.ZAR).toBe(25);
    expect(served.rates.AED).toBe(7.345);
    expect((await fixtureState(page)).calls).toBe(1);
  });

  test('yesterday’s rates outlive a failed refresh', async ({ page }) => {
    await setFixture(page, 'stale-fail');
    await page.goto(cacheBust('/hosting/'));

    const served = await pageRates(page);
    expect(served.source).toBe('live');
    expect(served.date).toBe('2026-09-18');
    expect(served.rates.USD).toBe(2);
    expect((await fixtureState(page)).calls).toBe(1);
  });

  test('the twice-daily refresh is scheduled', async ({ page }) => {
    await page.goto(cacheBust('/'));
    expect((await fixtureState(page)).scheduled).toBe(true);
  });

  test('the settings screen shows the rates in use and where they came from', async ({ page }) => {
    await setFixture(page, 'ok');
    await login(page);
    await page.goto(SETTINGS_PATH);

    await expect(page.locator('#blueworx_currency_rates')).toHaveText('£1 = €1.5000 = $2.0000 = R25.0000 = A$2.5000 = AED 7.3450');
    await expect(page.locator('#blueworx_currency_rates + .description')).toContainText('2026-09-19');
  });
});
