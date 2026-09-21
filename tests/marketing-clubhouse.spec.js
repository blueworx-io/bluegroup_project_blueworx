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
    // Module icons sit inside their tinted tile rather than filling it.
    const icon = await page.locator('main .bw-g3 .bw-card .svc-ic svg').first().boundingBox();
    expect(icon.width).toBeLessThanOrEqual(26);
    await expect(page.locator('.bw-demo .bw-demo-shot img')).toHaveCount(3);
    const srcs = await page.locator('.bw-demo img').evaluateAll((imgs) => imgs.map((i) => i.getAttribute('src') || ''));
    expect(srcs.every((s) => /\/assets\/img\/clubhouse-demo-/.test(s))).toBe(true);
    await expect(page.locator('.features-dark .bw-card-dark')).toHaveCount(4);
    await expect(page.locator('.plan-card.feat')).toHaveCount(1);
    await expect(page.locator('.plan-card .plan-name .pop')).toHaveText('Popular');
    // The £499 setup fee, on the card and in the hero, both marked for the
    // currency switcher; and no trace of the old "no setup fee" claim.
    await expect(page.locator('.plan-card [data-testid="plan-setup"]')).toContainText('£499 one-off setup fee');
    await expect(page.locator('.tech-hero .tech-status [data-bw-gbp="499"]')).toHaveText('£499');
    await expect(page.locator('main')).not.toContainText('no setup fee');
    // The shared reviews section, capped at three.
    await expect(page.locator('.tg > .tc')).toHaveCount(3);
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
