/**
 * The plugin's settings screen (#27, #28).
 *
 * Two things about this site are already configurable — which form shortcode
 * the Contact page renders, and where the nav's Client Login link points — but
 * both live in options with no screen behind them. In practice that means
 * "configurable" only for someone willing to run WP-CLI or write a filter, and
 * the Contact page has been showing a placeholder instead of a form because of
 * it, on a site that has a published contact form sitting ready to use.
 *
 * So these specs are written from the point of view of the person who has to do
 * it: open the settings screen, paste a shortcode, save, and see the form on the
 * public page.
 */

import { test, expect, login, restoreAll, cacheBust } from './helpers.js';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';
const isPlaceholder = /placeholder/i.test(baseURL);

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-settings-shortcode.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

// A shortcode to paste into the field. Registering our own keeps the spec
// independent of whether a form plugin happens to be installed on the target.
const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture: a stand-in form shortcode, and a way to put the two settings
 * back without going through wp-admin. Written by tests/admin-settings.spec.js
 * and removed afterwards. Only ever exists inside the disposable local
 * WordPress the test run creates.
 */
add_shortcode( 'bw_fixture_contact', function () {
	return '<form id="bw-fixture-contact"><label for="bw-f-msg">Message</label><textarea id="bw-f-msg"></textarea></form>';
} );

/**
 * Resets both settings on a plain GET.
 *
 * These specs change SITE-WIDE options, so cleanup that can fail is cleanup
 * that leaks: when an admin login flaked mid-run, the wp-admin cleanup could
 * not run either, the contact shortcode stayed set, and an unrelated spec
 * asserting the unconfigured placeholder failed several files later. Resetting
 * over a logged-out GET has nothing left to go wrong.
 */
add_action( 'init', function () {
	if ( ! isset( $_GET['bw_fixture_reset'] ) ) {
		return;
	}
	delete_option( 'blueworx_contact_form_shortcode' );
	delete_option( 'blueworx_client_login_url' );
	wp_die( 'bw-fixture-reset-ok', 'OK', array( 'response' => 200 ) );
} );
`;

const SETTINGS_PATH = '/wp-admin/options-general.php?page=bluegroup-project-blueworx';

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
  test.skip(!canInstallFixture, 'Needs the local WordPress harness.');
};

/** Saves the settings form with the given field values. */
async function saveSettings(page, { contactShortcode, clientLoginUrl }) {
  await page.goto(SETTINGS_PATH);

  if (contactShortcode !== undefined) {
    await page.fill('#blueworx_contact_form_shortcode', contactShortcode);
  }
  if (clientLoginUrl !== undefined) {
    await page.fill('#blueworx_client_login_url', clientLoginUrl);
  }

  await page.click('#submit');
  await page.waitForLoadState('domcontentloaded');
}

test.describe('BlueWorx site settings', () => {
  test.beforeEach(async ({ page }) => {
    skipUnlessLocal();
    await login(page);
  });

  // Reset over a logged-out GET rather than through the settings form. These
  // options are site-wide, so cleanup that depends on a working admin session
  // is cleanup that leaks into other specs the moment a login flakes.
  test.afterEach(async ({ page }) => {
    await restoreAll([
      [
        'clear both settings',
        async () => {
          const response = await page.request.get('/?bw_fixture_reset=1');
          expect(response.status()).toBe(200);
        },
      ],
    ]);
  });

  test('the screen is registered under Settings and shows both fields', async ({ page }) => {
    await page.goto(SETTINGS_PATH);

    await expect(page.locator('#blueworx_contact_form_shortcode')).toHaveCount(1);
    await expect(page.locator('#blueworx_client_login_url')).toHaveCount(1);
  });

  // The whole point of the screen: the Contact page has been showing a
  // placeholder rather than a form, and this is the step that ends that.
  test('a shortcode saved here renders on the public Contact page', async ({ page }) => {
    await saveSettings(page, { contactShortcode: '[bw_fixture_contact]' });

    await page.goto(cacheBust('/contact/'));

    await expect(page.locator('#bw-fixture-contact')).toHaveCount(1);
    await expect(page.locator('[data-widget="contact-form"]')).toHaveCount(0);
  });

  test('clearing the shortcode puts the placeholder back', async ({ page }) => {
    await saveSettings(page, { contactShortcode: '[bw_fixture_contact]' });
    await saveSettings(page, { contactShortcode: '' });

    await page.goto(cacheBust('/contact/'));

    await expect(page.locator('#bw-fixture-contact')).toHaveCount(0);
    await expect(page.locator('[data-widget="contact-form"]')).toHaveCount(1);
  });

  test('a Client Login URL saved here repoints every nav link', async ({ page }) => {
    await saveSettings(page, { clientLoginUrl: '/dashboard' });

    await page.goto(cacheBust('/about/'));

    const hrefs = await page.evaluate(() =>
      [...document.querySelectorAll('a')]
        .filter((el) => el.textContent.trim() === 'Client Login')
        .map((el) => el.getAttribute('href'))
    );

    expect(hrefs).toHaveLength(4);
    for (const href of hrefs) {
      expect(new URL(href, baseURL).pathname.replace(/\/$/, '')).toBe('/dashboard');
    }
  });

  // A shortcode field that renders whatever it is given is a shortcode field;
  // one that renders whatever HTML it is given is a way to put a script tag on
  // every visitor's Contact page. Only an administrator can reach this screen,
  // but "only an admin can do it" is not a reason to store markup unchecked.
  test('markup pasted into the shortcode field is not stored as markup', async ({ page }) => {
    await saveSettings(page, {
      contactShortcode: '<script>window.bwPwned = true</script>[bw_fixture_contact]',
    });

    await page.goto(cacheBust('/contact/'));

    expect(await page.evaluate(() => window.bwPwned)).toBeUndefined();
  });
});
