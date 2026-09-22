// Public marketing page — logged out — so every navigation goes through
// cacheBust(). See tests/helpers.js.
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const WP_ROOT = process.env.WP_TEST_ROOT || join(process.cwd(), '.wp-test', 'wp');
const MU_DIR = join(WP_ROOT, 'wp-content', 'mu-plugins');
const FIXTURE = join(MU_DIR, 'bw-test-contact.php');
const canInstallFixture = existsSync(join(WP_ROOT, 'wp-settings.php'));

// The throwaway WordPress has no mail server, so wp_mail() would fail and the
// form would report "could not send". The fixture records the last enquiry
// instead of sending it, and hands it back on request so a spec can check what
// would have been mailed. Removed after the run.
const FIXTURE_PLUGIN = `<?php
add_filter( 'pre_wp_mail', function ( $null, $atts ) {
	update_option( 'bw_fixture_last_mail', $atts, false );
	return true;
}, 10, 2 );
add_filter( 'blueworx_contact_rate_limit', function () { return 1000; } );
add_action( 'init', function () {
	if ( ! isset( $_GET['bw_last_mail'] ) ) {
		return;
	}
	wp_send_json( get_option( 'bw_fixture_last_mail', null ) );
} );
`;

test.describe('Marketing contact page', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('renders the hero, contact grid, cards, FAQ and testimonials', async ({ page }) => {
    await page.goto(cacheBust('/contact/'));

    await expect(page.locator('.bw-page')).toHaveCount(1);
    await expect(page.locator('.tech-hero')).toContainText('With BlueWorx');
    await expect(page.locator('.contact-grid .contact-form')).toHaveCount(1);
    await expect(page.locator('.contact-cards .cc')).toHaveCount(3);
    // The FAQ list is native <details> so it works with no JavaScript.
    await expect(page.locator('.faq-list details.faq-item')).toHaveCount(5);
    await expect(page.locator('.tg .tc').first()).toBeVisible();
  });

  test('the hero pills sit centred under the centred copy', async ({ page }) => {
    await page.goto(cacheBust('/contact/'));

    const status = page.locator('.tech-hero .tech-status');
    await expect(status).toContainText('reply within 1 business day');
    await expect(status).toHaveCSS('justify-content', 'center');
  });

  test('the form column renders the enquiry form when no shortcode is configured', async ({
    page,
  }) => {
    await page.goto(cacheBust('/contact/'));

    const form = page.locator('.contact-form form[data-widget="contact-form"]');
    await expect(form).toBeVisible();
    for (const id of ['bw-name', 'bw-company', 'bw-email', 'bw-phone', 'bw-topic', 'bw-budget', 'bw-message', 'bw-agree']) {
      await expect(form.locator(`label[for="${id}"]`)).toHaveCount(1);
      await expect(form.locator(`#${id}`)).toHaveCount(1);
    }
    await expect(form.locator('#bw-topic option')).toHaveCount(5);
    await expect(form.locator('[data-cf-chips] [data-budget]')).toHaveCount(5);
    await expect(form.locator('[data-cf-submit]')).toHaveText(/Send Enquiry/);
    // The success panel is in the markup but not shown yet.
    await expect(page.locator('[data-cf-sent]')).toBeHidden();
  });

  test('a budget chip fills the budget field; clicking it again clears it', async ({ page }) => {
    await page.goto(cacheBust('/contact/'));

    const chip = page.locator('[data-budget="£5k–£15k"]');
    await chip.click();
    await expect(page.locator('#bw-budget')).toHaveValue('£5k–£15k');
    await expect(chip).toHaveClass(/bw-chip-on/);

    await page.locator('[data-budget="£15k+"]').click();
    await expect(page.locator('#bw-budget')).toHaveValue('£15k+');
    await expect(chip).not.toHaveClass(/bw-chip-on/);
    await expect(page.locator('.bw-chip-on')).toHaveCount(1);

    await page.locator('[data-budget="£15k+"]').click();
    await expect(page.locator('#bw-budget')).toHaveValue('');
    await expect(page.locator('.bw-chip-on')).toHaveCount(0);

    // Typing a figure clears the chip so the two never disagree.
    await page.locator('[data-budget="Under £2k"]').click();
    await page.fill('#bw-budget', '£3,000');
    await expect(page.locator('.bw-chip-on')).toHaveCount(0);
  });

  test('an empty submit shows inline errors on the required fields and sends nothing', async ({
    page,
  }) => {
    await page.goto(cacheBust('/contact/'));

    let posted = false;
    page.on('request', (req) => {
      if (req.method() === 'POST') {
        posted = true;
      }
    });

    await page.fill('#bw-email', 'not-an-address');
    await page.click('[data-cf-submit]');

    await expect(page.locator('[data-cf-msg="name"]')).toHaveText('Please tell us your name.');
    await expect(page.locator('[data-cf-msg="email"]')).toHaveText('That email address does not look right.');
    await expect(page.locator('[data-cf-msg="message"]')).toHaveText('Please tell us a little about the project.');
    await expect(page.locator('#bw-name')).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#bw-name')).toBeFocused();
    expect(posted).toBe(false);

    // Correcting a field clears its error straight away.
    await page.fill('#bw-name', 'Jane');
    await expect(page.locator('[data-cf-msg="name"]')).toHaveText('');
  });

  test.describe('sending', () => {
    test.skip(!canInstallFixture, 'Needs the local WordPress harness.');

    test.beforeAll(() => {
      mkdirSync(MU_DIR, { recursive: true });
      writeFileSync(FIXTURE, FIXTURE_PLUGIN);
    });

    test.afterAll(() => {
      if (existsSync(FIXTURE)) {
        rmSync(FIXTURE);
      }
    });

    test('a complete enquiry is mailed to sales and the success panel replaces the form', async ({
      page,
    }) => {
      await page.goto(cacheBust('/contact/'));

      await page.fill('#bw-name', 'Jane Whitfield');
      await page.fill('#bw-company', 'Whitfield & Co.');
      await page.fill('#bw-email', 'jane@example.com');
      await page.fill('#bw-phone', '01628 000 000');
      await page.selectOption('#bw-topic', 'hosting');
      await page.click('[data-budget="£2k–£5k"]');
      await page.fill('#bw-message', 'We need to move hosts before the season starts.');
      await page.check('#bw-agree');
      await page.click('[data-cf-submit]');

      await expect(page.locator('[data-cf-sent]')).toBeVisible();
      await expect(page.locator('[data-cf-sent] h2')).toHaveText(/with us/);
      await expect(page.locator('[data-cf-form]')).toBeHidden();
      // No reload: still the same page, no ?sent= in the address.
      expect(page.url()).not.toContain('sent=');

      const mail = await (await page.request.get(cacheBust('/?bw_last_mail=1'))).json();
      expect(mail.to).toBe('sales@blueworx.io');
      expect(mail.subject).toContain('Managed hosting or a migration');
      expect(mail.subject).toContain('Jane Whitfield');
      expect(mail.message).toContain('jane@example.com');
      expect(mail.message).toContain('Whitfield & Co.');
      expect(mail.message).toContain('£2k–£5k');
      expect(mail.message).toContain('move hosts');
      expect(String(mail.headers)).toContain('jane@example.com');

      // "Send another message" brings back an empty form.
      await page.click('[data-cf-again]');
      await expect(page.locator('[data-cf-form]')).toBeVisible();
      await expect(page.locator('#bw-name')).toHaveValue('');
      await expect(page.locator('.bw-chip-on')).toHaveCount(0);
    });

    test('without JavaScript a good enquiry redirects to the success panel', async ({
      page,
    }) => {
      const res = await page.request.post('/contact/', {
        form: {
          blueworx_contact: '1',
          full_name: 'No Script',
          email: 'noscript@example.com',
          message: 'Sent from a plain form post.',
        },
        maxRedirects: 0,
      });
      expect(res.status()).toBe(303);
      expect(res.headers().location).toContain('sent=1');

      await page.goto(cacheBust('/contact/?sent=1'));
      await expect(page.locator('[data-cf-sent]')).toBeVisible();
      await expect(page.locator('[data-cf-form]')).toBeHidden();
    });

    test('without JavaScript a bad enquiry re-renders the form with errors and what was typed', async ({
      page,
    }) => {
      const res = await page.request.post('/contact/', {
        form: {
          blueworx_contact: '1',
          full_name: '',
          company: 'Kept & Co.',
          email: 'bad',
          message: '',
        },
      });
      expect(res.status()).toBe(200);
      const html = await res.text();
      expect(html).toContain('Please tell us your name.');
      expect(html).toContain('That email address does not look right.');
      expect(html).toContain('value="Kept &amp; Co."');
      expect(html).toContain('class="cf-field err"');
    });

    test('a filled honeypot is quietly dropped', async ({ page }) => {
      await page.request.post('/contact/', {
        form: {
          blueworx_contact: '1',
          full_name: 'Robot',
          email: 'robot@example.com',
          message: 'Buy now.',
          website: 'https://spam.example',
        },
      });
      const last = await (await page.request.get(cacheBust('/?bw_last_mail=1'))).json();
      expect(String(last ? last.message : '')).not.toContain('Buy now.');
    });
  });

  test('the Support Flow panel sits beside the form with four steps and a footer stat', async ({ page }) => {
    await page.goto(cacheBust('/contact/'));

    const flow = page.locator('.contact-grid .support-flow');
    await expect(flow).toBeVisible();
    await expect(flow).toHaveAttribute('role', 'presentation');
    await expect(flow.locator('.sf-step')).toHaveCount(4);
    await expect(flow.locator('.sf-step .sf-title')).toHaveText([
      'Your message lands',
      'Triaged by a person',
      'Work in progress',
      'Fixed and confirmed',
    ]);
    // Three connectors: none under the last step.
    await expect(flow.locator('.sf-track')).toHaveCount(3);
    // The footer stat is the top Support package's hours, so it must match
    // what the Support page sells.
    await expect(flow.locator('.sf-foot')).toContainText('Support hours available');
    await expect(flow.locator('.sf-foot')).toContainText('up to 600 hrs / yr');
    await expect(page.locator('.contact-illus')).toHaveCount(0);
  });

  test('the cards point at the dashboard, the portfolio and the sales inbox', async ({ page }) => {
    await page.goto(cacheBust('/contact/'));

    const cards = page.locator('.contact-cards .cc');
    await expect(cards.nth(0)).toContainText('Already a customer?');
    await expect(cards.nth(0).locator('a')).toHaveAttribute('href', /\/dashboard\/?$/);
    await expect(cards.nth(1).locator('a')).toHaveAttribute('href', /\/portfolio\/?$/);
    await expect(cards.nth(2).locator('a')).toHaveAttribute('href', 'mailto:sales@blueworx.io');
    await expect(cards.nth(2).locator('a')).toHaveText('sales@blueworx.io');
    await expect(page.locator('.contact-cards')).not.toContainText('info@blueworx.com');
  });

  test('the footer credits BlueGroup', async ({ page }) => {
    await page.goto(cacheBust('/contact/'));

    await expect(page.locator('footer .fbot')).toContainText('A BlueGroup Company');
    await expect(page.locator('footer .fbot')).not.toContainText('BabyBlue');
  });

  test('renders the nav with no active item (Contact is not a nav link)', async ({ page }) => {
    await page.goto(cacheBust('/contact/'));
    // Contact is deliberately not a top-level nav item in the source design,
    // so nothing in the nav should be marked active on this page — and the nav
    // must still render.
    await expect(page.locator('nav .nav-links a').first()).toBeVisible();
    await expect(page.locator('nav .nav-links a.active')).toHaveCount(0);
  });
});
