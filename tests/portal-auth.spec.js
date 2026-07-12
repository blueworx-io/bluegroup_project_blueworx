import { test, expect } from '@playwright/test';

// This spec runs only under the 'portal-auth' project, whose server is started
// with PORTAL_REQUIRE_AUTH=true (see playwright.config.js). With auth enforced
// and no auth backend wired yet, getSession() returns null, so the portal page
// must redirect unauthenticated visitors to the home page rather than render the
// demo client's data. See lib/auth.ts and docs/API_CONTRACT.md §5.1 / §7.
test.describe('portal auth enforcement (PORTAL_REQUIRE_AUTH=true)', () => {
  test('redirects unauthenticated visitors away from /portal', async ({ page }) => {
    await page.goto('/portal');

    // Landed on the public home page, not the portal.
    await expect(page).toHaveURL(/\/$/);
    await expect(page.locator('h1')).toContainText('We Design, Build & Grow');

    // None of the portal shell rendered — no other client's data leaked.
    await expect(page.locator('.pt-welcome')).toHaveCount(0);
  });
});
