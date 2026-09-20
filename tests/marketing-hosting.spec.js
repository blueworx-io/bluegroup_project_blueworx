import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

test.describe('Marketing Hosting page', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('renders hero, six performance cards, six security cards, migration split, plan, comparison and FAQ', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    await expect(page.locator('.tech-hero .tech-2col .glass-card')).toHaveCount(1);
    await expect(page.locator('.tech-hero a[href="#hosting-plans"]')).toHaveCount(1);
    await expect(page.locator('main .sec .bw-g3 .bw-card')).toHaveCount(6);
    await expect(page.locator('.features-dark .bw-card-dark')).toHaveCount(6);
    await expect(page.locator('section.split .collab-list .fli')).toHaveCount(4);
    await expect(page.locator('#hosting-plans.sec.bw-divided')).toHaveCount(1);
    await expect(page.locator('#hosting-plans .plan-card.feat .pop')).toHaveText('Per site');
    await expect(page.locator('#hosting-plans .bw-plan-aside a[href*="/support"]')).toHaveCount(1);
    await expect(page.locator('table.cmp tbody tr')).toHaveCount(8);
    await expect(page.locator('.faq-list details.faq-item')).toHaveCount(5);
  });

  test('billing toggle swaps £20 per month for £200 per year', async ({ page }) => {
    await page.goto(cacheBust('/hosting/'));
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('£20');
    await page.locator('#hosting-plans .bill-toggle button').nth(1).click();
    await expect(page.locator('#hosting-plans .plan-price b')).toHaveText('£200');
    await expect(page.locator('#hosting-plans .plan-price em')).toHaveText('per year, billed annually');
  });
});
