/**
 * The portal's details, support and toolbox sections (#97, #98, #99).
 *
 * These are the first pages in the client area that accept input rather than
 * only showing what we already hold, so most of what is checked here is about
 * refusing input rather than accepting it: a form without a valid nonce must
 * not save, a password change must not go through on the wrong current
 * password, and neither may be reachable logged out.
 *
 * dashboard.spec.js already covers the gate on every client-area path, and
 * dashboard-standards.spec.js covers the accessibility standard including
 * unlabelled fields. This covers behaviour.
 */

import { test, expect, login, cacheBust, isPlaceholder, TOOL_SLUGS } from './helpers.js';

test.describe('#97 Your details', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(isPlaceholder, 'No real WordPress target configured.');
    await login(page);
  });

  test('shows the account it is actually signed in as', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/details/'));

    const email = await page.locator('#bw-email').inputValue();
    const sidebar = (await page.locator('.dash-who-sub').textContent()).trim();

    // The form and the sidebar read the same user, so a mismatch means one of
    // them is showing somebody else's record.
    expect(email).toBe(sidebar);
  });

  test('saves a change to the client’s own details', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/details/'));

    const original = await page.locator('#bw-company').inputValue();
    const company = `Test Co ${Date.now()}`;

    await page.locator('#bw-company').fill(company);
    await page.locator('form:has(#bw-company) button[type="submit"]').click();

    await expect(page.locator('.dash-notice-success')).toBeVisible();
    await expect(page.locator('#bw-company')).toHaveValue(company);

    // Reloading proves it was stored rather than echoed back from the POST.
    await page.goto(cacheBust('/dashboard/details/'));
    await expect(page.locator('#bw-company')).toHaveValue(company);

    // Put the account back as it was, so a re-run starts from the same state.
    await page.locator('#bw-company').fill(original);
    await page.locator('form:has(#bw-company) button[type="submit"]').click();
    await expect(page.locator('.dash-notice-success')).toBeVisible();
  });

  test('refuses a details save with no valid nonce', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/details/'));

    const before = await page.locator('#bw-company').inputValue();

    // The nonce is the proof this request came from our form. Break it and the
    // save must not happen — this is the check that stops a third-party page
    // changing a signed-in client's email address.
    await page.evaluate(() => {
      document.querySelector('#blueworx_account_details_nonce').value = 'not-a-nonce';
      document.querySelector('#bw-company').value = 'Injected Ltd';
    });

    await page.locator('form:has(#bw-company) button[type="submit"]').click();

    await expect(page.locator('.dash-notice-error')).toBeVisible();

    await page.goto(cacheBust('/dashboard/details/'));
    await expect(page.locator('#bw-company')).toHaveValue(before);
  });

  test('refuses an email address that is not one', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/details/'));

    const before = await page.locator('#bw-email').inputValue();

    // novalidate so the browser lets it through — the check under test is the
    // server's, and the server is the only one that counts.
    await page.evaluate(() => {
      document.querySelector('form:has(#bw-email)').setAttribute('novalidate', 'novalidate');
      document.querySelector('#bw-email').value = 'not-an-email';
    });

    await page.locator('form:has(#bw-email) button[type="submit"]').click();

    await expect(page.locator('.dash-notice-error')).toBeVisible();

    await page.goto(cacheBust('/dashboard/details/'));
    await expect(page.locator('#bw-email')).toHaveValue(before);
  });

  test('will not change a password without the current one', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/details/'));

    await page.locator('#bw-current-password').fill('definitely-not-the-password');
    await page.locator('#bw-new-password').fill('a-long-enough-new-one');
    await page.locator('form:has(#bw-new-password) button[type="submit"]').click();

    await expect(page.locator('.dash-notice-error')).toBeVisible();

    // The session must survive a refused change. If the password had been
    // changed, or the session dropped, this would bounce to the sign-in page.
    await page.goto(cacheBust('/dashboard/details/'));
    await expect(page.locator('#bw-email')).toBeVisible();
  });
});

test.describe('#98 Support', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(isPlaceholder, 'No real WordPress target configured.');
    await login(page);
  });

  test('offers a request form with a real choice of type', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/support/'));

    await expect(page.locator('fieldset.dash-fieldset legend')).toBeVisible();
    await expect(page.locator('input[name="request_type"]')).toHaveCount(3);
    // Exactly one preselected: none, and a client can submit without choosing;
    // several, and the browser picks for them.
    await expect(page.locator('input[name="request_type"]:checked')).toHaveCount(1);
  });

  test('will not send an empty request', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/support/'));

    await page.evaluate(() => {
      document.querySelector('form:has(#bw-support-message)').setAttribute('novalidate', 'novalidate');
    });

    await page.locator('form:has(#bw-support-message) button[type="submit"]').click();

    await expect(page.locator('.dash-notice-error')).toBeVisible();
  });

  test('refuses a request with no valid nonce', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/support/'));

    await page.evaluate(() => {
      document.querySelector('#blueworx_account_support_nonce').value = 'not-a-nonce';
      document.querySelector('#bw-support-message').value = 'Hello';
    });

    await page.locator('form:has(#bw-support-message) button[type="submit"]').click();

    await expect(page.locator('.dash-notice-error')).toBeVisible();
  });

  test('gives a way to reach us that is not the form', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/support/'));

    const mailto = await page.locator('.dash-contact a').getAttribute('href');
    expect(mailto).toMatch(/^mailto:.+@.+/);
  });
});

test.describe('#99 Your toolbox', () => {
  test.beforeEach(async ({ page }) => {
    test.skip(isPlaceholder, 'No real WordPress target configured.');
    await login(page);
  });

  test('lists every tool in the registry, and each links to its page', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/toolbox/'));

    await expect(page.locator('.dash-tool')).toHaveCount(TOOL_SLUGS.length);

    const hrefs = await page
      .locator('.dash-tool-link')
      .evaluateAll((links) => links.map((a) => new URL(a.href).pathname.replace(/\/$/, '')));

    for (const slug of TOOL_SLUGS) {
      expect(hrefs, `${slug} is missing from the portal toolbox`).toContain(`/toolbox/${slug}`);
    }
  });

  test('says one thing about the account, not twelve different things', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/toolbox/'));

    // Every plan includes every tool, so the state is per-account. A page where
    // some tools read "Included" and others do not would be showing a client an
    // entitlement the site does not actually track.
    const states = await page
      .locator('.dash-tool-state')
      .evaluateAll((els) => [...new Set(els.map((el) => el.textContent.trim()))]);

    expect(states).toHaveLength(1);
  });
});
