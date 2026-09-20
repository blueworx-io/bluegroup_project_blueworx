import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

test.describe('Marketing ClubHouse page', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('renders the hero, nine modules, demo band, four dark cards, plan, audiences and FAQ', async ({ page }) => {
    await page.goto(cacheBust('/clubhouse/'));
    await expect(page.locator('.tech-hero.bw-hero-tight .tech-2col')).toHaveCount(1);
    await expect(page.locator('.tech-hero .glass-card')).toHaveCount(1);
    await expect(page.locator('.tech-hero a[href="https://demo.305media.co.uk/"][target="_blank"]')).toHaveCount(1);
    await expect(page.locator('main .bw-g3 .bw-card')).toHaveCount(9);
    await expect(page.locator('.bw-demo .bw-demo-shot img')).toHaveCount(3);
    const srcs = await page.locator('.bw-demo img').evaluateAll((imgs) => imgs.map((i) => i.getAttribute('src') || ''));
    expect(srcs.every((s) => /\/assets\/img\/clubhouse-demo-/.test(s))).toBe(true);
    await expect(page.locator('.features-dark .bw-card-dark')).toHaveCount(4);
    await expect(page.locator('.plan-card.feat')).toHaveCount(1);
    await expect(page.locator('.plan-card .plan-name .pop')).toHaveText('Popular');
    await expect(page.locator('.bw-plan-aside')).toHaveCount(1);
    await expect(page.locator('.bw-g4 .bw-card').filter({ hasText: 'Multi-sport clubs' })).toHaveCount(1);
    await expect(page.locator('.faq-list details.faq-item')).toHaveCount(5);
  });

  test('billing toggle swaps £20 per month for £200 per year', async ({ page }) => {
    await page.goto(cacheBust('/clubhouse/'));
    await expect(page.locator('.plan-price b')).toHaveText('£20');
    await expect(page.locator('.plan-price em')).toHaveText('per month');
    await page.locator('.bill-toggle button').nth(1).click();
    await expect(page.locator('.plan-price b')).toHaveText('£200');
    await expect(page.locator('.plan-price em')).toHaveText('per year, billed annually');
  });

  test('the CTA band is the ClubHouse one and the Popular badge is legible', async ({ page }) => {
    await page.goto(cacheBust('/clubhouse/'));
    await expect(page.locator('.cta-soft h2')).toHaveText('Ready to Move Your Club Online?');
    const color = await page.locator('.plan-card.feat .pop').evaluate((el) => getComputedStyle(el).color);
    expect(color).not.toBe('rgb(255, 255, 255)');
  });
});
