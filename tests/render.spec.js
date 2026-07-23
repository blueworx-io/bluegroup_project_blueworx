import { test, expect } from '@playwright/test';

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';
const isPlaceholder = /placeholder/i.test(baseURL);

// Every page the plugin installs on activation (blueworx_public_pages()). If any
// stops resolving to the plugin's own <body class="bw-page"> document — a broken
// template, a bad registry entry, a permalink regression — this catches it.
const OWNED_PAGES = ['/about', '/services', '/contact', '/work', '/ai', '/pricing', '/toolbox'];

for (const path of OWNED_PAGES) {
  test(`${path} renders the plugin document`, async ({ page }) => {
    test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

    const response = await page.goto(path);
    expect(response, `expected a response from ${path}`).toBeTruthy();
    expect(response.status(), `expected a non-error HTTP status for ${path}`).toBeLessThan(400);

    await expect(page.locator('body.bw-page')).toHaveCount(1);
  });
}
