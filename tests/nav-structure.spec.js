// The nav after the 2026-09 restructure: five links with ClubHouse last and
// tagged "New", Contact as the button, About, Journal and AI Powered only in
// the footer, no Toolbox mega panel.
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

const LINKS = ['Home', 'Hosting', 'Support', 'Portfolio', 'ClubHouse'];

test.describe('Site nav structure', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('lists the five pages in order, ClubHouse last and tagged New, and nothing else', async ({ page }) => {
    await page.goto(cacheBust('/'));
    const links = page.locator('nav .nav-links > a');
    await expect(links).toHaveCount(5);
    for (let i = 0; i < LINKS.length; i++) {
      await expect(links.nth(i)).toContainText(LINKS[i]);
    }
    await expect(links.last().locator('.nav-tag')).toHaveText('New');
    await expect(page.locator('nav .nav-links a', { hasText: 'AI Powered' })).toHaveCount(0);
    await expect(page.locator('footer a', { hasText: 'AI Powered' })).toHaveCount(1);
    await expect(page.locator('nav .mega-panel')).toHaveCount(0);
    await expect(page.locator('nav .about-panel')).toHaveCount(0);
    await expect(page.locator('nav .nav-links a', { hasText: 'Toolbox' })).toHaveCount(0);
  });

  test('the right cluster is Client Login, a Contact button and the currency switcher', async ({ page }) => {
    await page.goto(cacheBust('/'));
    await expect(page.locator('nav .nav-cta .nav-sign-in')).toHaveText('Client Login');
    const btn = page.locator('nav .nav-cta .nav-btn');
    await expect(btn).toContainText('Contact');
    await expect(btn).toHaveAttribute('href', /\/contact\/?$/);
    await expect(page.locator('nav .nav-cta .bw-cur')).toHaveCount(1);
  });

  test('each product page marks its own link active', async ({ page }) => {
    for (const [path, label] of [['/clubhouse/', 'ClubHouse'], ['/hosting/', 'Hosting'], ['/support/', 'Support']]) {
      await page.goto(cacheBust(path));
      await expect(page.locator('nav .nav-links a.active')).toHaveText(new RegExp(label));
    }
  });

  test('the mobile menu mirrors the nav and carries the switcher', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(cacheBust('/'));
    await page.locator('nav .hamburger').click();
    const menu = page.locator('.mobile-menu');
    await expect(menu).toHaveClass(/open/);
    for (const label of LINKS) {
      await expect(menu.locator('a', { hasText: label })).toHaveCount(1);
    }
    await expect(menu.locator('a.btn', { hasText: 'Contact' })).toHaveCount(1);
    await expect(menu.locator('.bw-cur')).toHaveCount(1);
    await expect(menu.locator('a[href*="/toolbox/"]')).toHaveCount(0);
  });

  test('the footer holds About, Journal, Contact and Client Login', async ({ page }) => {
    await page.goto(cacheBust('/'));
    const cols = page.locator('footer .fcol');
    await expect(cols).toHaveCount(2);
    for (const label of LINKS) {
      await expect(cols.nth(0).locator('a', { hasText: label.replace('Support', 'Integrated Support') })).toHaveCount(1);
    }
    await expect(cols.nth(0).locator('a', { hasText: 'Toolbox' })).toHaveCount(0);
    for (const label of ['About Us', 'Journal', 'Contact', 'Client Login']) {
      await expect(cols.nth(1).locator('a', { hasText: label })).toHaveCount(1);
    }
  });
});
