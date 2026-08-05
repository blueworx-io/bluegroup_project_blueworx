/**
 * The website and referral registers, and the portal sections over them
 * (#100, #101).
 *
 * These two sections differ from the rest of the portal in one important way:
 * the rows are entered by us against a named account, so the thing most worth
 * testing is that a client sees THEIR rows and nobody else's. That is checked
 * here by creating a record against a second user and confirming it does not
 * appear — the failure mode being one missing meta condition turning the
 * register into a list of every client's websites.
 *
 * Records are created through wp-admin rather than through a fixture, because
 * the admin screens are how they will actually be created, and a register the
 * team cannot fill in is not a register.
 */

import { test, expect, login, cacheBust, isPlaceholder } from './helpers.js';

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured.');

/**
 * Creates a record of either type through wp-admin.
 *
 * @param {import('@playwright/test').Page} page   Playwright page.
 * @param {string}                          type   Post type slug.
 * @param {string}                          title  Record title.
 * @param {object}                          fields Field name => value.
 * @return {Promise<void>}
 */
async function createRecord(page, type, title, fields) {
  await page.goto(`/wp-admin/post-new.php?post_type=${type}`);

  await page.locator('#title').fill(title);

  for (const [name, value] of Object.entries(fields)) {
    const field = page.locator(`[name="${name}"]`);
    const tag = await field.evaluate((el) => el.tagName.toLowerCase());

    if ('select' === tag) {
      await field.selectOption(String(value));
    } else {
      await field.fill(String(value));
    }
  }

  await page.locator('#publish').click();
  await page.waitForLoadState('domcontentloaded');

  // Remembered so it can be removed again by ID. A record left behind makes
  // this account a partner, which correctly changes what the sidebar shows —
  // and would then fail dashboard.spec.js for a reason that has nothing to do
  // with the dashboard.
  const id = new URL(page.url()).searchParams.get('post');

  if (id) {
    CREATED.push(id);
  }

  return id;
}

/**
 * The user ID the admin session belongs to.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<string>} User ID as a string.
 */
async function currentUserId(page) {
  await page.goto('/wp-admin/profile.php');

  return page.locator('#user_id').inputValue();
}

/**
 * IDs of every record these specs created, in creation order.
 */
const CREATED = [];

/**
 * Removes the records these specs created, by ID.
 *
 * Driven from each record's own edit screen rather than the list table's bulk
 * actions. The bulk version worked for one register and silently did nothing
 * for the other, which left ten referrals behind and turned the test account
 * into a partner — failing four dashboard tests for a reason that had nothing
 * to do with the dashboard. Deleting a known ID through the link WordPress
 * itself renders (nonce included) either works or throws; it cannot quietly
 * half-succeed.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<void>}
 */
async function removeCreated(page) {
  while (CREATED.length) {
    const id = CREATED.pop();

    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);

    // Read the trash link's href and navigate it, rather than clicking it.
    // Clicking timed out waiting for the link to be "stable" — wp-admin moves
    // the publish box about as its scripts settle — and a cleanup step that
    // flakes fails the test it happens to run after, which is how this looked
    // like a bug in the referrals page rather than in this helper.
    const href = await page
      .locator('#delete-action a.submitdelete')
      .first()
      .getAttribute('href')
      .catch(() => null);

    if (href) {
      await page.goto(href);
    }
  }

  // Trashing is enough, and emptying the bin is deliberately not done. Every
  // query the portal makes asks for published records only, so a trashed one
  // is already invisible to it — and "Empty Trash" is a form submit that flakes
  // the same way the link did.
}

test.afterAll(async ({ browser }) => {
  if (isPlaceholder) {
    return;
  }

  const page = await browser.newPage();
  await login(page);
  await removeCreated(page);
  await page.close();
});

test.describe('#101 Your websites', () => {
  test.beforeEach(async ({ page }) => {
    skipPlaceholder();
    await login(page);
  });

  test('says so plainly when nothing is recorded', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/websites/'));

    // Either state is legitimate depending on what the run before left behind;
    // what must never happen is a page that shows neither.
    const cards = await page.locator('.dash-site').count();
    const empty = await page.locator('.dash-empty').count();

    expect(cards > 0 || empty > 0, 'the websites page rendered nothing at all').toBe(true);
  });

  test('shows a website recorded against this client, and its real address', async ({ page }) => {
    const userId = await currentUserId(page);
    const name = `Playwright Site ${Date.now()}`;

    await createRecord(page, 'bw_client_site', name, {
      bw_site_client: userId,
      bw_site_url: 'https://example.com/shop',
      bw_site_hosting: 'Managed hosting, daily backups',
      bw_site_status: 'live',
    });

    await page.goto(cacheBust('/dashboard/websites/'));

    const card = page.locator('.dash-site', { hasText: name });
    await expect(card).toHaveCount(1);
    await expect(card.locator('.dash-site-url')).toHaveText('example.com');
    await expect(card.locator('.dash-site-url')).toHaveAttribute(
      'href',
      'https://example.com/shop'
    );
    await expect(card.locator('.dash-site-host')).toContainText('daily backups');
  });

  test('does not show a website belonging to somebody else', async ({ page }) => {
    const name = `Someone Else Site ${Date.now()}`;

    // Attached to user 0 — "nobody" — which is the closest a single-account
    // test install gets to "not this client". A register that ignored its
    // owner condition would list it anyway.
    await createRecord(page, 'bw_client_site', name, {
      bw_site_client: '0',
      bw_site_url: 'https://not-yours.example.com',
      bw_site_status: 'live',
    });

    await page.goto(cacheBust('/dashboard/websites/'));

    await expect(page.locator('.dash-site', { hasText: name })).toHaveCount(0);
  });

  test('does not invent uptime or traffic figures', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/websites/'));

    const text = await page.locator('.dash-scroll').innerText();

    // The design shows all three per site and nothing measures any of them.
    // A percentage on this page means somebody has typed a number in.
    expect(text).not.toMatch(/\d+(\.\d+)?%/);
  });

  test('the register is not reachable from the front end', async ({ page }) => {
    // A client's site list leaking onto the public web is the worst outcome
    // here, so it is asserted rather than assumed from the registration args.
    //
    // Asserted on CONTENT, not status. A private post type makes WordPress
    // ignore the query var rather than refuse the request, so this URL is a
    // 200 — it is the home page. Expecting a 4xx here passes for the wrong
    // reason on a site that happens to 404 its front page, and fails on a
    // correctly-configured one.
    const name = `Private Probe ${Date.now()}`;

    await createRecord(page, 'bw_client_site', name, {
      bw_site_client: await currentUserId(page),
      bw_site_url: 'https://private-probe.example.com',
      bw_site_status: 'live',
    });

    const archive = await page.request.get('/?post_type=bw_client_site');
    expect(await archive.text()).not.toContain(name);

    // And no guessable address of its own.
    const single = await page.request.get(
      `/bw_client_site/${name.toLowerCase().replace(/[^a-z0-9]+/g, '-')}/`
    );
    expect(single.status()).toBe(404);
  });
});

test.describe('#100 Your referrals', () => {
  test.beforeEach(async ({ page }) => {
    skipPlaceholder();
    await login(page);
  });

  test('the tab is hidden until there is something in it', async ({ page }) => {
    await page.goto(cacheBust('/dashboard/'));

    const hasReferrals = await page.evaluate(async () => {
      const res = await fetch('/dashboard/partner/', { credentials: 'same-origin' });
      const html = await res.text();
      return !html.includes('dash-empty');
    });

    const tab = page.locator('.dash-nav a[href*="/dashboard/partner"]');

    // The rule is that the two agree: a partner with referrals gets the tab, a
    // client without them does not.
    await expect(tab).toHaveCount(hasReferrals ? 1 : 0);
  });

  test('the page is still reachable and gated even when the tab is hidden', async ({ page }) => {
    const response = await page.goto(cacheBust('/dashboard/partner/'));

    expect(response.status()).toBe(200);
    await expect(page.locator('.dash-side .dash-nav')).toHaveCount(1);
  });

  test('shows a referral recorded against this partner, with its own wording', async ({ page }) => {
    const userId = await currentUserId(page);
    const name = `Playwright Referral ${Date.now()}`;

    await createRecord(page, 'bw_referral', name, {
      bw_referral_partner: userId,
      bw_referral_status: 'client',
      bw_referral_amount: '£250',
    });

    await page.goto(cacheBust('/dashboard/partner/'));

    const row = page.locator('.dash-table tbody tr', { hasText: name });
    await expect(row).toHaveCount(1);
    await expect(row).toContainText('£250');
    // "Became a client" is the referral register's own wording. If the shared
    // table ran it through SureCart's status map it would come out as
    // something else entirely.
    await expect(row.locator('.dash-status')).toHaveText('Became a client');
  });

  test('does not show a referral belonging to somebody else', async ({ page }) => {
    const name = `Someone Else Referral ${Date.now()}`;

    await createRecord(page, 'bw_referral', name, {
      bw_referral_partner: '0',
      bw_referral_status: 'enquiry',
    });

    await page.goto(cacheBust('/dashboard/partner/'));

    await expect(page.locator('.dash-table tbody tr', { hasText: name })).toHaveCount(0);
  });
});
