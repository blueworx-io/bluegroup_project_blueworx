import { test, expect } from '@playwright/test';

// The demo server runs in mock mode (no NEXT_PUBLIC_WORDPRESS_URL), so the catch-all
// must not hijack unknown paths — they still 404 exactly as before. (The live
// resolve→wp/v2 path is verified against a real CMS, not here: Server-Component
// fetches run server-side and can't be intercepted from the browser context.)
test('an unmatched path returns 404 in mock mode', async ({ page }) => {
  const res = await page.goto('/this-path-does-not-exist-xyz');
  expect(res.status()).toBe(404);
});

test('a real front-end route still renders (catch-all does not shadow it)', async ({ page }) => {
  const res = await page.goto('/pricing');
  expect(res.status()).toBe(200);
});
