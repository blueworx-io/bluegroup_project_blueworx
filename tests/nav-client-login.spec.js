/**
 * Where the Client Login link points (#28).
 *
 * The nav shows "Client Login" in three places — desktop, the mobile bar, and
 * the mobile menu — and all three had `/portal` written into them by hand.
 * `/portal` is a SureDash page, and SureDash is being removed, so every one of
 * those links becomes a 404 the day it goes.
 *
 * The replacement dashboard does not exist yet (#37), so this does not change
 * where the link goes. What it changes is that the destination is now stated
 * once and configurable, so pointing it at the new dashboard is a setting rather
 * than a code change — and so the three copies cannot drift apart in the
 * meantime, which is the failure that leaves one stale link nobody notices.
 */

import { test, expect } from '@playwright/test';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';
const isPlaceholder = /placeholder/i.test(baseURL);

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-client-login.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

// Gated on a query parameter so the default-destination spec below still sees
// an unconfigured site.
const FIXTURE_PLUGIN = `<?php
/**
 * Test fixture: points the Client Login link somewhere recognisable, on
 * request. Written by tests/nav-client-login.spec.js and removed afterwards.
 */
add_filter( 'blueworx_client_login_url', function ( $url ) {
	return isset( $_GET['bw_fixture_login'] ) ? 'https://dashboard.fixture.invalid/here' : $url;
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

/**
 * The href of every "Client Login" link in the document.
 *
 * Read straight from the DOM rather than with getByRole, because two of the
 * three are the mobile nav's and are hidden by CSS at a desktop viewport.
 * Hidden elements are not in the accessibility tree, so a role query finds one
 * link and silently passes a test meant to compare all three.
 */
const clientLoginHrefs = (page) =>
  page.evaluate(() =>
    [...document.querySelectorAll('a')]
      .filter((el) => el.textContent.trim() === 'Client Login')
      .map((el) => el.getAttribute('href'))
  );

test('every Client Login link in the nav agrees on one destination', async ({ page }) => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

  await page.goto('/about/');

  const hrefs = await clientLoginHrefs(page);

  // Desktop, mobile bar, mobile menu. If a fourth is ever added, this fails and
  // asks whether it was wired to the same place — which is the point.
  expect(hrefs).toHaveLength(3);
  expect(new Set(hrefs).size, `the three links disagree: ${hrefs.join(', ')}`).toBe(1);
});

test('Client Login defaults to /portal until the new dashboard exists', async ({ page }) => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

  await page.goto('/about/');

  const href = (await clientLoginHrefs(page))[0];
  expect(new URL(href, baseURL).pathname.replace(/\/$/, '')).toBe('/portal');
});

test('the Client Login destination can be repointed without touching the nav', async ({ page }) => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');
  test.skip(
    !canInstallFixture,
    'Needs the local WordPress harness — the fixture plugin has to be installed on disk.'
  );

  await page.goto('/about/?bw_fixture_login=1');

  const hrefs = await clientLoginHrefs(page);

  expect(hrefs).toHaveLength(3);
  for (const href of hrefs) {
    expect(href).toBe('https://dashboard.fixture.invalid/here');
  }
});
