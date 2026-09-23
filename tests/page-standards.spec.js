/**
 * The checks every marketing page has to pass (#44–#52, #56).
 *
 * The "Test: <page>" issues each ask for the same review — desktop and mobile,
 * links and buttons, keyboard access, headings and alt text. Written out once
 * per page that would be thirteen near-identical specs that drift apart; written
 * once and run over every page, a new page gets the same standard for free, and
 * a regression on any page fails by name.
 *
 * These deliberately assert things a user would notice or a screen reader would
 * trip on, not implementation details. The per-page content specs
 * (marketing-*.spec.js) already pin what each page says; this pins that it is
 * usable.
 *
 * The site's accessibility standard — meaningful alt text, real labels,
 * readable contrast, keyboard access, correct heading order — is in the project
 * CLAUDE.md. Contrast is the one item here that is not automated: it needs
 * rendered pixels and a judgement about intent, so it stays a manual check.
 */

import { test, expect } from '@playwright/test';
import { audit, firstFocusable, horizontalOverflow, PHONE } from './standards.js';

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';
const isPlaceholder = /placeholder/i.test(baseURL);

// #44 home, #45 about, #46 services, #47 work, #48 ai, #49 pricing, #50 toolbox,
// #52 contact, and #51 the twelve tool pages.
const PAGES = [
  { path: '/', issue: '#44 Home' },
  { path: '/about/', issue: '#45 About' },
  { path: '/clubhouse/', issue: 'ClubHouse' },
  { path: '/hosting/', issue: 'Hosting' },
  { path: '/support/', issue: 'Integrated Support' },
  { path: '/portfolio/', issue: '#47 Work (now Portfolio)' },
  { path: '/ai/', issue: '#48 AI Powered' },
  { path: '/contact/', issue: '#52 Contact' },
  // #94. The journal is held to the same standard as the pages above, and it
  // is the one page whose content is the client's rather than ours — so an
  // unlabelled control or a heading level skipped by a card is exactly the
  // kind of thing that would otherwise only show up once posts exist.
  { path: '/blog/', issue: '#94 Journal' },
  // #55. The sign-in page — the shop's form covers signing up and resetting a
  // password — is held to exactly the same standard as the marketing pages.
  // The dashboard it leads to is the Labs plugin's, and is tested there.
  { path: '/login/', issue: '#55 Sign in' },
];

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

for (const { path, issue } of PAGES) {
  test.describe(`${issue} — ${path}`, () => {
    test('has one h1, in a heading order that does not skip a level', async ({ page }) => {
      skipPlaceholder();
      await page.goto(path);

      const { headings } = await audit(page);
      const h1s = headings.filter((h) => h.level === 1);

      expect(h1s.map((h) => h.text), `${path} must have exactly one h1`).toHaveLength(1);

      // A jump from h2 straight to h4 tells a screen-reader user a section is
      // missing. Going back UP any number of levels is normal and fine.
      let previous = 1;
      for (const heading of headings) {
        expect(
          heading.level,
          `${path}: "${heading.text}" is an h${heading.level} directly after an h${previous}`
        ).toBeLessThanOrEqual(previous + 1);
        previous = heading.level;
      }
    });

    test('every image has an alt attribute', async ({ page }) => {
      skipPlaceholder();
      await page.goto(path);

      const { imagesWithoutAlt } = await audit(page);
      expect(imagesWithoutAlt, `${path}: images with no alt attribute at all`).toEqual([]);
    });

    test('every link and button has an accessible name', async ({ page }) => {
      skipPlaceholder();
      await page.goto(path);

      const { linksWithoutName, buttonsWithoutName } = await audit(page);
      expect(linksWithoutName, `${path}: links a screen reader would announce as blank`).toEqual([]);
      expect(buttonsWithoutName, `${path}: buttons a screen reader would announce as blank`).toEqual([]);
    });

    // An unlabelled field is a form a screen reader cannot fill in. Only the
    // contact and sign-in pages have fields, and those are the two where being
    // unable to fill the form in costs the visitor something.
    test('every form field has a label', async ({ page }) => {
      skipPlaceholder();
      await page.goto(path);

      const { fieldsWithoutLabel } = await audit(page);
      expect(fieldsWithoutLabel, `${path}: fields with no label a screen reader can use`).toEqual([]);
    });

    test('declares a language and a page title', async ({ page }) => {
      skipPlaceholder();
      await page.goto(path);

      const { lang, title } = await audit(page);
      expect(lang, `${path} must declare a lang for screen readers`).toBeTruthy();
      expect(title, `${path} must have a non-empty <title>`).not.toBe('');
    });

    test('does not scroll sideways on a phone', async ({ page }) => {
      skipPlaceholder();
      await page.setViewportSize(PHONE);
      await page.goto(path);

      const overflow = await horizontalOverflow(page);

      expect(
        overflow.scrollWidth,
        `${path} scrolls sideways at 375px. Widest elements: ${overflow.widest.join(', ') || 'none identified'}`
      ).toBeLessThanOrEqual(overflow.clientWidth + 1);
    });

    test('the first thing keyboard focus reaches is a real, visible control', async ({ page }) => {
      skipPlaceholder();
      await page.goto(path);

      const focused = await firstFocusable(page);

      expect(focused, `${path}: pressing Tab from the top moves focus nowhere`).not.toBeNull();
      expect(['a', 'button', 'input', 'select', 'textarea']).toContain(focused.tag);
      expect(focused.hasSize, `${path}: focus lands on a zero-size element`).toBe(true);
      expect(focused.name, `${path}: the first focusable control has no name`).not.toBe('');
    });
  });
}

// #56. A 404 is a page too: it is what a visitor sees when a link rots, and it
// has to both say so and give them a way out.
test.describe('#56 — the 404 page', () => {
  const MISSING = '/this-page-does-not-exist-a8f3e2/';

  test('returns a real 404 status, not a 200', async ({ page }) => {
    skipPlaceholder();

    const response = await page.goto(MISSING);

    // A "soft 404" — a not-found page served as 200 — leaves search engines
    // indexing it as real content.
    expect(response.status()).toBe(404);
  });

  test('offers a way back to the site', async ({ page }) => {
    skipPlaceholder();
    await page.goto(MISSING);

    const internalLinks = await page.evaluate(
      () =>
        [...document.querySelectorAll('a[href]')].filter((a) => {
          const href = a.getAttribute('href') || '';
          return href.startsWith('/') || href.includes(location.host);
        }).length
    );

    expect(internalLinks, 'a 404 with no links out is a dead end').toBeGreaterThan(0);
  });
});
