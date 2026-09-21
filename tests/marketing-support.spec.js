import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

test.describe('Marketing Integrated Support page', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('renders the tall hero, three featured cards, calculator, nine-row table, six uses, process and FAQ', async ({ page }) => {
    await page.goto(cacheBust('/support/'));
    await expect(page.locator('.tech-hero.pb-tall')).toHaveCount(1);
    await expect(page.locator('.tech-hero .tech-status [data-bw-gbp="100"]')).toHaveText('£100');
    const cards = page.locator('.plans .plan-card');
    await expect(cards).toHaveCount(3);
    await expect(cards.nth(0).locator('.plan-name span').first()).toHaveText('Starter');
    await expect(cards.nth(1)).toHaveClass(/feat/);
    await expect(cards.nth(1).locator('.plan-name span').first()).toHaveText('Growth');
    await expect(cards.nth(2).locator('.plan-name span').first()).toHaveText('Enterprise +');
    await expect(cards.nth(1).locator('.plan-price b')).toHaveText('£500');
    await expect(cards.nth(1).locator('.plan-price em')).toHaveText('per month · 11.67 hrs a month');
    await expect(page.locator('.bill-toggle')).toHaveCount(0);
    await expect(page.locator('[data-widget="support-calc"]')).toBeVisible();
    await expect(page.locator('table.cmp tbody tr')).toHaveCount(9);
    await expect(page.locator('table.cmp tbody tr').nth(4)).toContainText('Growth');
    await expect(page.locator('table.cmp tbody tr').nth(4)).toContainText('£42.86 / hr');
    await expect(page.locator('table.cmp tbody tr').nth(2)).toContainText('6.25 hrs');
    // The shared reviews section, capped at three, sits on this page too.
    await expect(page.locator('.tg > .tc')).toHaveCount(3);
    await expect(page.locator('.features-dark .bw-card-dark')).toHaveCount(6);
    await expect(page.locator('.proc-grid .proc')).toHaveCount(4);
    await expect(page.locator('.faq-list details.faq-item')).toHaveCount(5);
  });

  test('the hours slider defaults to Growth and updates the recommendation live', async ({ page }) => {
    await page.goto(cacheBust('/support/'));
    const calc = page.locator('[data-widget="support-calc"]');
    await expect(calc.locator('[data-testid="support-calc-name"]')).toHaveText('Growth');
    await expect(calc.locator('[data-testid="support-calc-hours"]')).toHaveText('11.67');
    await expect(calc.locator('[data-testid="support-calc-price"]')).toHaveText('£500');
    await expect(calc.locator('[data-testid="support-calc-rate"]')).toHaveText('£42.86 / hr');
    await calc.locator('input.bw-range').fill('8');
    await expect(calc.locator('[data-testid="support-calc-name"]')).toHaveText('Advantage +');
    await expect(calc.locator('[data-testid="support-calc-hours"]')).toHaveText('50');
    await expect(calc.locator('[data-testid="support-calc-annual"]')).toHaveText('600');
    await expect(calc.locator('[data-testid="support-calc-price"]')).toHaveText('£1,500');
    await expect(calc.locator('[data-testid="support-calc-rate"]')).toHaveText('£30.00 / hr');
  });
});
