// The site-wide currency switcher (design pass, 2026-09). GBP is the base;
// EUR and USD are fixed rates. Only elements marked data-bw-gbp convert, so
// the Toolbox's dollar prices are untouched.
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

test.describe('Currency switcher', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('defaults to GBP and lists three currencies', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    await expect(page.locator('nav .bw-cur-btn [data-cur-label]')).toHaveText('£ GBP');
    await page.locator('nav .bw-cur-btn').click();
    await expect(page.locator('nav .bw-cur-menu button')).toHaveCount(3);
    await expect(page.locator('nav .bw-cur-menu button.on')).toHaveAttribute('data-cur', 'GBP');
  });

  test('switching to USD converts marked prices at ×1.27 and persists across pages', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('£20');
    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="USD"]').click();
    await expect(page.locator('nav .bw-cur-btn [data-cur-label]')).toHaveText('$ USD');
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('$25');
    await page.locator('#hosting-plans .bill-toggle button').nth(1).click();
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('$254');

    await page.goto(cacheBust('/support/'));
    await expect(page.locator('nav .bw-cur-btn [data-cur-label]')).toHaveText('$ USD');
    await expect(page.locator('.plans .plan-card.feat .plan-price b')).toHaveText('$635');
  });

  test('EUR uses ×1.17 and the effective rate keeps two decimals', async ({ page }) => {
    await page.goto(cacheBust('/support/'));
    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="EUR"]').click();
    // Growth: £500 × 1.17 = €585; rate (500×12)/120 = £50.00 → €58.50
    await expect(page.locator('.plans .plan-card.feat .plan-price b')).toHaveText('€585');
    await expect(page.locator('[data-testid="support-calc-rate"]')).toHaveText('€58.50 / hr');
  });

  test('the Toolbox dollar prices are not converted', async ({ page }) => {
    await page.goto(cacheBust('/toolbox/'));
    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="EUR"]').click();
    const first = await page.locator('.plans .plan-price b').first().textContent();
    expect(first.startsWith('$')).toBe(true);
  });

  test('the menu closes after choosing a currency, on Escape, and on an outside click', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    const menu = page.locator('nav .bw-cur-menu');

    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="USD"]').click();
    await expect(menu).toBeHidden();

    await page.locator('nav .bw-cur-btn').click();
    await expect(menu).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(menu).toBeHidden();

    await page.locator('nav .bw-cur-btn').click();
    await expect(menu).toBeVisible();
    await page.locator('body').click({ position: { x: 10, y: 10 } });
    await expect(menu).toBeHidden();
  });
});
