import { test, expect } from '@playwright/test';
import { HOME_REVIEWS } from '../lib/data';

test.describe('marketing pages', () => {
  test('home page renders hero and key sections', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('h1')).toContainText('We Design, Build & Grow');
    await expect(page.locator('.svc2 .svc')).toHaveCount(2);
    await expect(page.locator('.tbx-grid .tbx-card')).toHaveCount(12);
  });

  test('testimonials render from the content data layer', async ({ page }) => {
    await page.goto('/');
    const cards = page.locator('.tg .tc');
    // Sources from HOME_REVIEWS via getTestimonials(), not the old inline copy.
    await expect(cards).toHaveCount(HOME_REVIEWS.length);
    for (const review of HOME_REVIEWS) {
      const card = cards.filter({ hasText: review.name });
      await expect(card.locator('.tname')).toHaveText(review.name);
      await expect(card.locator('.trole')).toHaveText(review.role);
    }
  });

  test('nav links reach every page', async ({ page }) => {
    await page.goto('/');
    await page.locator('.nav-links').getByText('Services', { exact: true }).click();
    await expect(page.locator('h1')).toContainText('Two Services.');
    await page.locator('.nav-links').getByText('Pricing', { exact: true }).click();
    await expect(page.locator('h1')).toContainText('Choose Your');
    await page.locator('.nav-links').getByText('About Us').click();
    await expect(page.locator('h1')).toContainText('Works Like a Partner');
    await page.locator('.nav-links').getByText('AI Powered').click();
    await expect(page.locator('h1')).toContainText('From Prompt to Production');
  });

  test('toolbox mega menu items stay inside the panel', async ({ page }) => {
    await page.goto('/');
    await page.locator('.nav-links a[href="/toolbox"]').hover();
    const item = page.locator('.mega-item').first();
    await expect(item).toBeVisible();
    const overflow = await item.evaluate((el) => {
      const panel = el.parentElement;
      const panelRight = panel.getBoundingClientRect().right;
      const worst = Math.max(...[...panel.querySelectorAll('.mega-item')].map((it) => it.getBoundingClientRect().right));
      return { spill: Math.round(worst - panelRight), scrollOverflow: panel.scrollWidth - panel.clientWidth };
    });
    expect(overflow.spill).toBeLessThanOrEqual(0);
    expect(overflow.scrollOverflow).toBeLessThanOrEqual(0);
  });

  test('home feature tabs switch the analytics panel', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('.af-text h2')).toHaveText('Support Guides');
    await page.locator('.tab-bar button', { hasText: 'Hosting' }).click();
    await expect(page.locator('.af-text h2')).toHaveText('Website Hosting');
  });

  test('tool detail page renders for a toolbox tool', async ({ page }) => {
    await page.goto('/toolbox/surecart');
    await expect(page.locator('h1')).toHaveText('SureCart');
    await expect(page.locator('.svc-grid .svc')).toHaveCount(6);
  });
});

test.describe('pricing', () => {
  test('toolbox plans react to the billing toggle', async ({ page }) => {
    await page.goto('/toolbox');
    const firstPrice = page.locator('.plan-card .plan-price b').first();
    await expect(firstPrice).toHaveText('$30');
    await page.getByRole('button', { name: 'Annual billing' }).click();
    await expect(firstPrice).toHaveText('$25');
  });

  test('pricing calculator updates the estimated total', async ({ page }) => {
    await page.goto('/pricing');
    const total = page.getByTestId('calc-total');
    // growth (500) + 1 extra update pack (60) + hosting (40)
    await expect(total).toHaveText('$600');
    await page.getByRole('button', { name: 'Advanced' }).click();
    await expect(total).toHaveText('$850');
    await page.getByRole('button', { name: 'More websites' }).click();
    await expect(total).toHaveText('$970');
  });

  test('FAQ accordion opens and closes items', async ({ page }) => {
    await page.goto('/pricing');
    const first = page.locator('.faq-item').first();
    await expect(first).toHaveClass(/open/);
    await page.locator('.faq-q', { hasText: 'Do I need to be a developer?' }).click();
    await expect(first).not.toHaveClass(/open/);
    await expect(page.locator('.faq-item', { hasText: 'Do I need to be a developer?' })).toHaveClass(/open/);
  });
});

test.describe('contact form', () => {
  test('validates required fields and shows success state', async ({ page }) => {
    await page.goto('/contact');
    await page.getByRole('button', { name: 'Send Message' }).click();
    await expect(page.locator('.field.err')).toHaveCount(5);

    await page.getByPlaceholder('First name').fill('Luke');
    await page.getByPlaceholder('Last name').fill('McFarland');
    await page.getByPlaceholder('you@company.com').fill('luke@example.com');
    await page.getByPlaceholder('+1 (555) 000-0000').fill('+1 555 000 0000');
    await page.getByPlaceholder('Leave us a message...').fill('Tell me about retainers.');
    await page.locator('#agree').check();
    await page.getByRole('button', { name: 'Send Message' }).click();
    await expect(page.locator('.form-success h3')).toHaveText('Message sent!');
  });

  test('includes the selected country code in the submission', async ({ page }) => {
    await page.goto('/contact');

    let posted = null;
    await page.route('**/api/contact', async (route) => {
      posted = route.request().postDataJSON();
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ ok: true }) });
    });

    await page.getByPlaceholder('First name').fill('Luke');
    await page.getByPlaceholder('Last name').fill('McFarland');
    await page.getByPlaceholder('you@company.com').fill('luke@example.com');
    await page.getByLabel('Country code').selectOption('AU');
    await page.getByPlaceholder('+1 (555) 000-0000').fill('+61 400 000 000');
    await page.getByPlaceholder('Leave us a message...').fill('Tell me about retainers.');
    await page.locator('#agree').check();
    await page.getByRole('button', { name: 'Send Message' }).click();

    await expect(page.locator('.form-success h3')).toHaveText('Message sent!');
    expect(posted, 'the form should POST to /api/contact').toBeTruthy();
    expect(posted.countryCode).toBe('AU');
  });
});

test.describe('mobile layout', () => {
  test.use({ viewport: { width: 375, height: 812 } });

  const pages = ['/', '/services', '/work', '/pricing', '/about', '/toolbox', '/ai', '/contact', '/portal'];
  for (const path of pages) {
    test(`no horizontal overflow on ${path} at 375px`, async ({ page }) => {
      await page.goto(path);
      const { vw, scrollW } = await page.evaluate(() => ({
        vw: document.documentElement.clientWidth,
        scrollW: document.documentElement.scrollWidth,
      }));
      expect(scrollW).toBeLessThanOrEqual(vw + 1);
    });
  }

  test('all home feature tabs stay reachable at 375px', async ({ page }) => {
    await page.goto('/');
    for (const label of ['Support', 'Toolbox', 'Hosting']) {
      const tab = page.locator('.tab-bar button', { hasText: label });
      await tab.scrollIntoViewIfNeeded();
      await expect(tab).toBeInViewport();
    }
  });
});

test.describe('client portal', () => {
  test('tabs switch views and the site switcher works', async ({ page }) => {
    await page.goto('/portal');
    await expect(page.locator('.pt-welcome h2')).toContainText('Welcome back, Hannah');

    await page.locator('.pt-nav-item', { hasText: 'Invoices' }).click();
    await expect(page.locator('.pt-top h1')).toHaveText('Invoices');
    await expect(page.locator('.pt-table tbody tr')).toHaveCount(4);

    await page.locator('.pt-nav-item', { hasText: 'Onboarding' }).click();
    await expect(page.locator('.pt-card-head h3').first()).toContainText('Bloom Events — progress');

    await page.locator('.pt-switch-btn').click();
    await page.locator('.pt-switch-item', { hasText: 'Bloom Store' }).click();
    await expect(page.locator('.pt-switch-btn b')).toHaveText('Bloom Store');
  });
});
