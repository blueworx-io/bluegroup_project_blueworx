/**
 * Asset isolation on plugin-rendered pages (#26).
 *
 * Every marketing page on the live site ships styles and scripts belonging to
 * plugins the page does not use — SureCart alone inlines around a hundred block
 * stylesheets into a page that contains no blocks, and UiCore adds a global
 * script from the uploads directory. The plugin renders these pages itself, in
 * its own markup, so none of it is needed.
 *
 * TESTING A REMOVAL NEEDS SOMETHING TO REMOVE. The local WordPress has none of
 * those plugins installed, so asserting "no foreign assets" against it would
 * pass on an empty page and prove nothing — the exact shape of green-but-vacuous
 * that the shared test harness exists to prevent. So these specs install a
 * fixture plugin that enqueues assets in the same four ways the real offenders
 * do, and then assert both halves of the behaviour:
 *
 *   - on a page the plugin renders, the fixture's assets are gone, and
 *   - on a page it does not render, they are still there.
 *
 * The second half is what keeps the first half honest: if the fixture ever
 * failed to load, that assertion fails rather than silently making the whole
 * spec a tautology.
 */

import { test, expect } from '@playwright/test';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';
const isPlaceholder = /placeholder/i.test(baseURL);

// Only the local harness has a filesystem we may write a fixture into. Against
// any other target these specs skip rather than pretend.
const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-foreign-assets.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

// Mirrors how the real offenders arrive: a plain stylesheet, a stylesheet whose
// payload is inline (SureCart's block styles), a local script, and a script from
// somebody else's origin (js.surecart.com).
const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture: stands in for SureCart, UiCore and friends by enqueueing assets
 * on every front-end page. Written by tests/asset-isolation.spec.js and removed
 * again afterwards. Never shipped.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'bw-fixture-foreign', home_url( '/bw-fixture-foreign.css' ), array(), '1.0' );
	wp_register_style( 'bw-fixture-inline', false, array(), '1.0' );
	wp_enqueue_style( 'bw-fixture-inline' );
	wp_add_inline_style( 'bw-fixture-inline', '.bw-fixture-inline{color:red}' );
	wp_enqueue_script( 'bw-fixture-foreign-js', home_url( '/bw-fixture-foreign.js' ), array(), '1.0', true );
	wp_enqueue_script( 'bw-fixture-external-js', 'https://js.fixture-cdn.invalid/v1/tracker.js', array(), '1.0', true );
} );

/**
 * Stands in for SureForms on the contact page, but only when the request asks
 * for it — so the specs that assert the unconfigured placeholder still see an
 * unconfigured contact page.
 */
add_filter( 'blueworx_contact_form_shortcode', function ( $shortcode ) {
	return isset( $_GET['bw_fixture_form'] ) ? '[bw_fixture_form]' : $shortcode;
} );

add_shortcode( 'bw_fixture_form', function () {
	return '<form id="bw-fixture-form"><button type="submit">Send</button></form>';
} );
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

const skipUnlessLocal = () => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
  test.skip(
    !canInstallFixture,
    'Needs the local WordPress harness — the fixture plugin has to be installed on disk.'
  );
};

/** Every stylesheet href and script src the page actually asked the browser for. */
async function assetUrls(page) {
  return page.evaluate(() =>
    [
      ...[...document.querySelectorAll('link[rel="stylesheet"]')].map((el) => el.getAttribute('href')),
      ...[...document.querySelectorAll('script[src]')].map((el) => el.getAttribute('src')),
    ].filter(Boolean)
  );
}

test('a plugin-rendered page loads none of another plugin\'s assets', async ({ page }) => {
  skipUnlessLocal();

  await page.goto('/about/');

  const urls = await assetUrls(page);
  const foreign = urls.filter((url) => /bw-fixture-foreign|fixture-cdn\.invalid/.test(url));

  expect(foreign, `these belong to another plugin and should not be on a page it does not render`).toEqual([]);

  // The inline-payload style has no src to find in the DOM, so it is checked by
  // its printed <style> id — that is the form SureCart's hundred block styles
  // take, and the bulk of the weight.
  await expect(page.locator('style#bw-fixture-inline-inline-css')).toHaveCount(0);
});

test('a page the plugin does not render keeps another plugin\'s assets', async ({ page }) => {
  skipUnlessLocal();

  // WordPress's own Sample Page — the plugin has no claim on it, so removing
  // another plugin's CSS there would be this plugin overreaching.
  await page.goto('/sample-page/');

  const urls = await assetUrls(page);

  expect(
    urls.filter((url) => /bw-fixture-foreign/.test(url)).length,
    'the fixture must load here — if it does not, the test above proves nothing'
  ).toBeGreaterThan(0);
  await expect(page.locator('style#bw-fixture-inline-inline-css')).toHaveCount(1);
});

test('a plugin-rendered page still loads its own stylesheet and scripts', async ({ page }) => {
  skipUnlessLocal();

  await page.goto('/about/');

  const urls = await assetUrls(page);
  const own = urls.filter((url) => url.includes('/plugins/bluegroup-project-blueworx/'));

  expect(own.some((url) => url.includes('public.css'))).toBe(true);
  expect(own.some((url) => url.includes('public-nav.js'))).toBe(true);
  expect(own.some((url) => url.includes('public-widgets.js'))).toBe(true);
});

// The sweep is an allowlist, so it would happily strip the CSS and JS of a
// third-party form the contact page deliberately embeds — leaving a form that
// renders but does not work, which is worse than a heavy page. The contact page
// opts out whenever a form shortcode is actually configured.
test('the contact page keeps foreign assets when a form shortcode is configured', async ({ page }) => {
  skipUnlessLocal();

  await page.goto('/contact/?bw_fixture_form=1');

  await expect(page.locator('#bw-fixture-form')).toHaveCount(1);

  const urls = await assetUrls(page);
  expect(
    urls.filter((url) => /bw-fixture-foreign/.test(url)).length,
    'an embedded form needs its own plugin\'s assets to work'
  ).toBeGreaterThan(0);
});

test('the contact page sweeps foreign assets when no form shortcode is configured', async ({ page }) => {
  skipUnlessLocal();

  await page.goto('/contact/');

  const urls = await assetUrls(page);
  expect(urls.filter((url) => /bw-fixture-foreign/.test(url))).toEqual([]);
});

test('a plugin-rendered page still renders correctly with the foreign assets gone', async ({ page }) => {
  skipUnlessLocal();

  await page.goto('/about/');

  await expect(page.locator('body.bw-page')).toHaveCount(1);
  await expect(page.locator('nav').first()).toBeVisible();
  await expect(page.locator('main')).toBeVisible();
});
