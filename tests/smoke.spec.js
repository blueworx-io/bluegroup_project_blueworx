import { test, expect } from '@playwright/test';

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';
const isPlaceholder = /placeholder/i.test(baseURL);

// The plugin takes over the front page on activation and renders it itself,
// wrapping every page it owns in a <body class="bw-page ...">. Asserting that
// wrapper on the front page proves the plugin is active and rendering — not
// merely that WordPress returned some page.
test('front page renders the plugin document', async ({ page }) => {
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

  const response = await page.goto('/');
  expect(response, 'expected a response from the front page').toBeTruthy();
  expect(response.status(), 'expected a non-error HTTP status').toBeLessThan(400);

  await expect(page.locator('body.bw-page')).toHaveCount(1);
  await expect(page.locator('nav').first()).toBeVisible();
});
