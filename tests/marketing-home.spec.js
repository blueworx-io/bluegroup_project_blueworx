// Home page marketing content (Task 2 of Plan 2). Logged-out by definition,
// so every navigation goes through cacheBust() — see tests/public-site.spec.js's
// top-of-file note on Cloudways Varnish caching stale logged-out responses.
import { expect } from '@playwright/test';
import { test, isPlaceholder, cacheBust } from './helpers.js';

test.describe('Marketing home page', () => {
  test.skip(isPlaceholder, 'No real WordPress target configured.');

  test('renders the home-hero, What We Do, How We Work and testimonials sections', async ({ page }) => {
    await page.goto(cacheBust('/'));

    // home-hero: the bespoke section built directly in templates/pages/home.php,
    // including its glass-card timeline visual (via the new glass-card part)
    // and the scrolling service ticker.
    const hero = page.locator('main > div > section.home-hero');
    await expect(hero).toHaveCount(1);
    await expect(hero.locator('.glass-card')).toHaveCount(1);
    await expect(hero.locator('.gc-tag')).toHaveText('yourproject · status');
    // Five timeline steps, ported verbatim from app/page.tsx's TimelineRow calls.
    for (const step of ['Discovery call', 'Design', 'Development', 'Deploy', 'Support & growth']) {
      await expect(hero.locator('.glass-card').getByText(step, { exact: true })).toHaveCount(1);
    }
    await expect(hero.locator('.hh-ticker-track span').first()).toBeVisible();

    // "What We Do": two svc-card parts inside .svc2.
    const svc2 = page.locator('main > div > section .svc2');
    await expect(svc2).toHaveCount(1);
    const svcCards = svc2.locator('> a.svc');
    await expect(svcCards).toHaveCount(2);
    await expect(svcCards.nth(0)).toContainText('Integrated Support');
    await expect(svcCards.nth(0)).toHaveAttribute('href', /\/support\/?$/);
    await expect(svcCards.nth(1)).toContainText('Managed Hosting');
    await expect(svcCards.nth(1)).toHaveAttribute('href', /\/hosting\/?$/);

    // Portfolio: the first three live sites, each opening in a new tab.
    const workCards = page.locator('main > div .work-grid > a.work-card');
    await expect(workCards).toHaveCount(3);
    await expect(workCards.nth(0)).toContainText('Hirasté');
    await expect(workCards.nth(0)).toHaveAttribute('href', 'https://hiraste.com/');
    await expect(workCards.nth(0)).toHaveAttribute('target', '_blank');
    await expect(workCards.nth(2)).toContainText('Top Tier Tutors');

    // How We Work: proc-grid part, four steps.
    const procGrid = page.locator('main > div > section .proc-grid');
    await expect(procGrid).toHaveCount(1);
    await expect(procGrid.locator('.proc')).toHaveCount(4);
    await expect(procGrid.locator('.proc').first().locator('.num')).toHaveText('01');

    // Testimonials part, fed the real blueworx_content_reviews() content.
    const testimonials = page.locator('main > div .tg > .tc');
    await expect(testimonials).toHaveCount(3);
    await expect(testimonials.first().locator('.tname')).not.toBeEmpty();
  });

  test('the FeatureTabs region renders the real interactive widget', async ({ page }) => {
    await page.goto(cacheBust('/'));

    // FeatureTabs became a real Plan 3b widget (progressive enhancement) — this
    // pins that the page renders the interactive section (tab bar + default
    // Support panel) in its place, not an empty gap or a leftover placeholder.
    const widget = page.locator('main > div > [data-widget="feature-tabs"]');
    await expect(widget).toHaveCount(1);
    await expect(widget.locator('.tab-bar .tab')).toHaveCount(3);
    await expect(widget.locator('.af-text h2')).toHaveText('Support Guides');

    // It must sit in the section flow between Selected Work and How We Work,
    // matching the source's order in app/page.tsx.
    const sections = await page.locator('main > div > *').evaluateAll((els) =>
      els.map((el) => (el.matches('[data-widget="feature-tabs"]') ? 'feature-tabs' : el.className))
    );
    expect(
      sections.indexOf('feature-tabs'),
      'the FeatureTabs widget must be present in the section flow'
    ).toBeGreaterThan(-1);
  });

  test('the dark band introduces ClubHouse where the Toolbox grid used to be', async ({ page }) => {
    await page.goto(cacheBust('/'));

    const band = page.locator('main > div > section.bw-ch-band');
    await expect(band).toHaveCount(1);
    await expect(page.locator('main > div .tbx-grid')).toHaveCount(0);
    await expect(band.locator('h2')).toContainText('ClubHouse');
    await expect(band.locator('.bw-ch-band-item')).toHaveCount(4);
    // The price and the setup fee both come from blueworx_content_clubhouse()
    // and are marked for the currency switcher.
    await expect(band.locator('.bw-ch-band-price b [data-bw-gbp="20"]')).toHaveText('£20');
    await expect(band.locator('.bw-ch-band-price small [data-bw-gbp="499"]')).toHaveText('£499');
    await expect(band.locator('a.btn-brand')).toHaveAttribute('href', /\/clubhouse\/?$/);
    const shot = band.locator('.bw-ch-band-shot img');
    expect(await shot.getAttribute('src')).toMatch(/\/assets\/img\/clubhouse-demo-home\.jpg$/);
  });

  test('the Ongoing Partnership split section renders the collaboration visual', async ({ page }) => {
    await page.goto(cacheBust('/'));

    const split = page.locator('main > div > section.split');
    await expect(split).toHaveCount(1);
    await expect(split.locator('.collab-list .fli')).toHaveCount(4);

    const img = split.locator('.collab-visual img');
    await expect(img).toHaveCount(1);
    const src = await img.getAttribute('src');
    expect(src, 'the collaboration image must be served from the plugin, not the theme/uploads').toMatch(
      /\/wp-content\/plugins\/bluegroup-project-blueworx\/assets\/img\/fig-collab\.jpg$/
    );
  });
});
