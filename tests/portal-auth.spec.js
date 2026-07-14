import { test, expect } from '@playwright/test';

// Runs under the 'portal-auth' project (server started with PORTAL_REQUIRE_AUTH=true).
// The server shell passes requireAuth=true to the client AuthProvider, so it makes
// real auth calls. NEXT_PUBLIC_WORDPRESS_URL is unset in the test build, so the
// client API base is empty and calls hit relative /auth/* and /surecart/me/* paths,
// which we intercept below — no live CMS needed. See the Cycle 2 spec §6.

test.describe('portal auth (PORTAL_REQUIRE_AUTH=true)', () => {
  test('unauthenticated visitor sees the sign-in screen, not portal data', async ({ page }) => {
    await page.route('**/auth/refresh', (r) => r.fulfill({ status: 401, contentType: 'application/json', body: '{}' }));

    await page.goto('/portal');

    await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
    await expect(page.locator('.pt-welcome')).toHaveCount(0); // no client data leaked
  });

  test('signing in renders the portal with mapped SureCart billing', async ({ page }) => {
    await page.route('**/auth/refresh', (r) => r.fulfill({ status: 401, contentType: 'application/json', body: '{}' }));
    await page.route('**/auth/login', (r) => r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({
      access_token: 't', token_type: 'Bearer', expires_in: 3600,
      user: { id: 5, display_name: 'Dana Lee', first_name: 'Dana' },
    }) }));
    await page.route('**/auth/me', (r) => r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({
      id: 5, email: 'dana@acme.com', username: 'dana', display_name: 'Dana Lee',
      first_name: 'Dana', last_name: 'Lee', roles: ['subscriber'], capabilities: ['read'],
    }) }));
    await page.route('**/surecart/me/subscriptions', (r) => r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({
      data: [{ status: 'active', current_period_end_at: 1775001600, price: { amount: 49000, currency: 'usd', recurring_interval: 'month' }, product: { name: 'Growth Retainer' } }],
    }) }));
    await page.route('**/surecart/me/invoices', (r) => r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({
      data: [{ number: 'INV-9001', created_at: 1772323200, total: 93800, currency: 'usd', status: 'paid' }],
    }) }));

    await page.goto('/portal');
    await page.getByLabel('Email or username').fill('dana@acme.com');
    await page.getByLabel('Password').fill('supersecret');
    await page.getByRole('button', { name: 'Sign in' }).click();

    await expect(page.getByText('Growth Retainer')).toBeVisible();       // mapped subscription name
    await expect(page.getByText('$490').first()).toBeVisible();          // 49000 cents → $490
    await expect(page.locator('.pt-welcome')).toContainText('Dana');     // identity from /auth/me
  });

  test('a billing fetch failure shows an inline error, still no other client data', async ({ page }) => {
    await page.route('**/auth/refresh', (r) => r.fulfill({ status: 401, contentType: 'application/json', body: '{}' }));
    await page.route('**/auth/login', (r) => r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({
      access_token: 't', user: { id: 5, display_name: 'Dana Lee', first_name: 'Dana' },
    }) }));
    await page.route('**/auth/me', (r) => r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({
      id: 5, email: 'd@a.com', username: 'd', display_name: 'Dana Lee', first_name: 'Dana', last_name: 'Lee', roles: ['subscriber'],
    }) }));
    await page.route('**/surecart/me/subscriptions', (r) => r.fulfill({ status: 500, body: '{}' }));
    await page.route('**/surecart/me/invoices', (r) => r.fulfill({ status: 500, body: '{}' }));

    await page.goto('/portal');
    await page.getByLabel('Email or username').fill('d@a.com');
    await page.getByLabel('Password').fill('supersecret');
    await page.getByRole('button', { name: 'Sign in' }).click();

    await expect(page.locator('.pt-welcome')).toContainText('Dana');     // portal shell rendered
    // Next.js App Router injects its own accessibility announcer (role="alert",
    // id "__next-route-announcer__") into every page, so a bare getByRole('alert')
    // is ambiguous once a real in-page alert also exists (the announcer has no
    // accessible name, so filtering by { name } finds nothing — filter by
    // rendered text instead). Same non-weakening disambiguation as .first() below
    // for $490 — the containment assertion still enforces the real content.
    await expect(page.getByRole('alert').filter({ hasText: /billing/i })).toContainText(/billing/i); // inline billing error
  });
});
