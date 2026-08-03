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

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';
const isPlaceholder = /placeholder/i.test(baseURL);

const TOOL_SLUGS = [
  'sureforms',
  'surerank',
  'suremail',
  'surewriter',
  'surecart',
  'zipwp',
  'ottokit',
  'ally',
  'sweet-ai',
  'elementor-ai-planner',
  'elementor',
  'equalize-a11y-checker',
];

// #44 home, #45 about, #46 services, #47 work, #48 ai, #49 pricing, #50 toolbox,
// #52 contact, and #51 the twelve tool pages.
const PAGES = [
  { path: '/', issue: '#44 Home' },
  { path: '/about/', issue: '#45 About' },
  { path: '/services/', issue: '#46 Services' },
  { path: '/work/', issue: '#47 Work' },
  { path: '/ai/', issue: '#48 AI Powered' },
  { path: '/pricing/', issue: '#49 Pricing' },
  { path: '/toolbox/', issue: '#50 Toolbox' },
  { path: '/contact/', issue: '#52 Contact' },
  ...TOOL_SLUGS.map((slug) => ({ path: `/toolbox/${slug}/`, issue: `#51 Toolbox — ${slug}` })),
];

const skipPlaceholder = () =>
  test.skip(isPlaceholder, 'No real WordPress target configured (placeholder base URL).');

/**
 * Collects everything the assertions below need, in one pass over the document.
 *
 * One evaluate rather than a locator per rule: these run over twenty pages, and
 * the harness serves them from a single-threaded PHP server.
 */
async function audit(page) {
  return page.evaluate(() => {
    // Each candidate is trimmed BEFORE it is accepted. A link wrapping only an
    // image has a textContent of "\n\t\t" — whitespace, which is truthy, so an
    // untrimmed `||` chain stops there and never reaches the image's alt text.
    // That reports every logo link on the site as nameless, which is a bug in
    // the check rather than a finding.
    const accessibleName = (el) => {
      const candidates = [
        el.getAttribute('aria-label'),
        el.getAttribute('title'),
        el.textContent,
        [...el.querySelectorAll('img')].map((img) => img.getAttribute('alt') || '').join(' '),
        [...el.querySelectorAll('svg title')].map((t) => t.textContent || '').join(' '),
      ];

      for (const candidate of candidates) {
        const name = (candidate || '').trim();
        if (name) return name;
      }

      return '';
    };

    return {
      lang: document.documentElement.getAttribute('lang'),
      title: (document.title || '').trim(),
      headings: [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')].map((el) => ({
        level: Number(el.tagName[1]),
        text: el.textContent.trim().slice(0, 60),
      })),
      imagesWithoutAlt: [...document.querySelectorAll('img')]
        .filter((img) => {
          // alt="" is the correct, meaningful marking for a decorative image.
          // A MISSING alt attribute is the bug: a screen reader falls back to
          // announcing the file name.
          if (img.getAttribute('alt') !== null) return false;
          return img.getAttribute('aria-hidden') !== 'true' && img.getAttribute('role') !== 'presentation';
        })
        .map((img) => img.getAttribute('src') || '(no src)'),
      linksWithoutName: [...document.querySelectorAll('a[href]')]
        .filter((a) => a.getAttribute('aria-hidden') !== 'true' && !accessibleName(a))
        .map((a) => a.getAttribute('href')),
      buttonsWithoutName: [...document.querySelectorAll('button')]
        .filter((b) => b.getAttribute('aria-hidden') !== 'true' && !accessibleName(b))
        .map((b) => b.className || '(no class)'),
    };
  });
}

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

    test('declares a language and a page title', async ({ page }) => {
      skipPlaceholder();
      await page.goto(path);

      const { lang, title } = await audit(page);
      expect(lang, `${path} must declare a lang for screen readers`).toBeTruthy();
      expect(title, `${path} must have a non-empty <title>`).not.toBe('');
    });

    test('does not scroll sideways on a phone', async ({ page }) => {
      skipPlaceholder();
      // iPhone SE, the narrowest screen still in common use.
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto(path);

      const overflow = await page.evaluate(() => ({
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
        widest: [...document.querySelectorAll('body *')]
          .filter((el) => el.getBoundingClientRect().right > document.documentElement.clientWidth + 1)
          .slice(0, 5)
          .map((el) => `${el.tagName.toLowerCase()}.${el.className || '(no class)'}`.slice(0, 80)),
      }));

      expect(
        overflow.scrollWidth,
        `${path} scrolls sideways at 375px. Widest elements: ${overflow.widest.join(', ') || 'none identified'}`
      ).toBeLessThanOrEqual(overflow.clientWidth + 1);
    });

    test('the first thing keyboard focus reaches is a real, visible control', async ({ page }) => {
      skipPlaceholder();
      await page.goto(path);

      await page.keyboard.press('Tab');

      const focused = await page.evaluate(() => {
        const el = document.activeElement;
        if (!el || el === document.body) return null;
        const rect = el.getBoundingClientRect();

        // Same trim-before-accept rule as audit()'s accessibleName: the first
        // focusable control on every page is the logo, which is an image inside
        // a link, so its name comes from the image's alt text.
        const name =
          [
            el.getAttribute('aria-label'),
            el.textContent,
            [...el.querySelectorAll('img')].map((img) => img.getAttribute('alt') || '').join(' '),
          ]
            .map((candidate) => (candidate || '').trim())
            .find(Boolean) || '';

        return {
          tag: el.tagName.toLowerCase(),
          name: name.slice(0, 40),
          hasSize: rect.width > 0 && rect.height > 0,
        };
      });

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
