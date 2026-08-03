/**
 * The page-quality checks, in one place.
 *
 * Extracted from page-standards.spec.js when the signed-in pages needed the
 * same standard (#53, #55). They cannot live in the same spec file: the
 * dashboard needs a logged-in session, which needs the `test` from helpers.js
 * rather than Playwright's own, and a file can only have one.
 *
 * Copying the checks instead would have been the worse trade — two sets of
 * accessibility rules drift, and the signed-in pages would quietly end up held
 * to the weaker one.
 */

/**
 * Collects everything the assertions need, in one pass over the document.
 *
 * One evaluate rather than a locator per rule: these run over two dozen pages,
 * and the harness serves them from a single-threaded PHP server.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<object>} The audit.
 */
export async function audit(page) {
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

    // A form control's name can also come from a <label>, which is how every
    // field on the sign-in and sign-up pages is named.
    const fieldName = (el) => {
      const id = el.getAttribute('id');
      const label = id ? document.querySelector(`label[for="${CSS.escape(id)}"]`) : null;

      return (
        (el.getAttribute('aria-label') || '').trim() ||
        (label ? (label.textContent || '').trim() : '') ||
        (el.closest('label') ? (el.closest('label').textContent || '').trim() : '')
      );
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
      // An unlabelled field is a form a screen reader cannot fill in. Only
      // relevant where there are forms, which is why it was not needed until
      // the sign-in pages existed.
      fieldsWithoutLabel: [...document.querySelectorAll('input, select, textarea')]
        .filter((el) => !['hidden', 'submit', 'button'].includes(el.getAttribute('type') || ''))
        .filter((el) => !fieldName(el))
        .map((el) => el.getAttribute('name') || el.getAttribute('type') || '(unnamed)'),
    };
  });
}

/**
 * What the first Tab press lands on.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<object|null>} Tag, name and whether it has size.
 */
export async function firstFocusable(page) {
  await page.keyboard.press('Tab');

  return page.evaluate(() => {
    const el = document.activeElement;
    if (!el || el === document.body) return null;
    const rect = el.getBoundingClientRect();

    // Same trim-before-accept rule as audit()'s accessibleName: the first
    // focusable control on every page is the logo, which is an image inside a
    // link, so its name comes from the image's alt text.
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
}

/**
 * How far the document overflows its own width, and what is widest.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 * @return {Promise<object>} scrollWidth, clientWidth and the widest elements.
 */
export async function horizontalOverflow(page) {
  return page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
    widest: [...document.querySelectorAll('body *')]
      .filter((el) => el.getBoundingClientRect().right > document.documentElement.clientWidth + 1)
      .slice(0, 5)
      .map((el) => `${el.tagName.toLowerCase()}.${el.className || '(no class)'}`.slice(0, 80)),
  }));
}

/**
 * The narrowest screen still in common use.
 */
export const PHONE = { width: 375, height: 667 };
