// The site-wide currency switcher (design pass, 2026-09). GBP is the base;
// the EUR, USD, ZAR and AUD rates come from the ECB via
// includes/public/currency.php (AED is derived from USD at the dirham's peg)
// and are handed to the page as window.blueworxCurrency — so the expected figures here
// are worked out from whatever rates the page was served with, not typed in.
// Only elements marked data-bw-gbp convert, so the Toolbox's dollar prices are
// untouched. The feed itself is covered by tests/currency-rates.spec.js.
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

const SYMBOLS = { GBP: '£', EUR: '€', USD: '$', ZAR: 'R', AUD: 'A$', AED: 'AED ' };

/** The rates the page was served with. */
async function pageRates(page) {
  const rates = await page.evaluate(() => window.blueworxCurrency && window.blueworxCurrency.rates);
  expect(rates, 'window.blueworxCurrency.rates is inlined on every owned page').toBeTruthy();
  for (const code of ['EUR', 'USD', 'ZAR', 'AUD', 'AED']) {
    expect(rates[code], `${code} rate`).toBeGreaterThan(0);
  }
  return rates;
}

/** Mirrors money() in assets/js/public-widgets.js. */
function money(gbp, rate, code, dp = 0) {
  const value = gbp * rate;
  return SYMBOLS[code] + (dp ? value.toFixed(dp) : Math.round(value).toLocaleString('en-GB'));
}

test.describe('Currency switcher', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('defaults to GBP and lists six currencies', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    await expect(page.locator('nav .bw-cur-btn [data-cur-label]')).toHaveText('£ GBP');
    await page.locator('nav .bw-cur-btn').click();
    const codes = await page.locator('nav .bw-cur-menu button').evaluateAll((els) => els.map((el) => el.getAttribute('data-cur')));
    expect(codes).toEqual(['GBP', 'EUR', 'USD', 'ZAR', 'AUD', 'AED']);
    await expect(page.locator('nav .bw-cur-menu button.on')).toHaveAttribute('data-cur', 'GBP');
  });

  test('switching to USD converts marked prices at the served rate and persists across pages', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    const { USD } = await pageRates(page);
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('£20');
    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="USD"]').click();
    await expect(page.locator('nav .bw-cur-btn [data-cur-label]')).toHaveText('$ USD');
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText(money(20, USD, 'USD'));
    await page.locator('#hosting-plans .bill-toggle button').nth(1).click();
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText(money(200, USD, 'USD'));

    await page.goto(cacheBust('/support/'));
    await expect(page.locator('nav .bw-cur-btn [data-cur-label]')).toHaveText('$ USD');
    await expect(page.locator('.plans .plan-card.feat .plan-price b')).toHaveText(money(500, USD, 'USD'));
  });

  test('EUR converts at the served rate and the effective rate keeps two decimals', async ({ page }) => {
    await page.goto(cacheBust('/support/'));
    const { EUR } = await pageRates(page);
    await page.locator('nav .bw-cur-btn').click();
    await page.locator('nav .bw-cur-menu button[data-cur="EUR"]').click();
    // Growth: £500/month; rate (500×12)/140 hrs = £42.86/hr.
    await expect(page.locator('.plans .plan-card.feat .plan-price b')).toHaveText(money(500, EUR, 'EUR'));
    await expect(page.locator('[data-testid="support-calc-rate"]')).toHaveText(`${money(42.86, EUR, 'EUR', 2)} / hr`);
  });

  test('rand, Australian dollars and dirhams convert with their own signs, the setup fee included', async ({ page }) => {
    await page.goto(cacheBust('/clubhouse/'));
    const rates = await pageRates(page);
    // AED is the dollar rate at the UAE's fixed peg, not a rate of its own.
    expect(rates.AED).toBeCloseTo(rates.USD * 3.6725, 2);

    for (const [code, label] of [['ZAR', 'R ZAR'], ['AUD', 'A$ AUD'], ['AED', 'AED']]) {
      await page.locator('nav .bw-cur-btn').click();
      await page.locator(`nav .bw-cur-menu button[data-cur="${code}"]`).click();
      await expect(page.locator('nav .bw-cur-btn [data-cur-label]')).toHaveText(label);
      await expect(page.locator('.plan-price b')).toHaveText(money(20, rates[code], code));
      await expect(page.locator('[data-testid="plan-setup"] [data-bw-gbp]')).toHaveText(money(499, rates[code], code));
    }
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
