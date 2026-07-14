import { test, expect } from '@playwright/test';

test.describe('wp fetchers', () => {
  test.beforeEach(() => { process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io'; });

  test('getMenu unwraps items and builds the namespaced URL', async () => {
    let calledUrl = '';
    globalThis.fetch = (async (url: string) => {
      calledUrl = url;
      return { ok: true, json: async () => ({ location: 'primary', items: [{ id: 1, title: 'About', url: 'https://cms.blueworx.io/about/', target: '', object: 'page', object_id: 12, children: [] }] }) };
    }) as unknown as typeof fetch;

    const wp = await import(`../lib/api/wp.ts?menu=${Date.now()}`);
    const items = await wp.getMenu('primary');
    // Assert the namespaced path suffix (origin-independent) — no config import,
    // matching the CI-safe endsWith pattern used in wp-client.spec.ts.
    expect(calledUrl.endsWith('/wp-json/blueworx/v1/menus/primary')).toBe(true);
    expect(items).toHaveLength(1);
    expect(items[0].title).toBe('About');
  });

  test('resolve builds the encoded query URL', async () => {
    let calledUrl = '';
    globalThis.fetch = (async (url: string) => { calledUrl = url; return { ok: true, json: async () => ({ type: 'page', id: 12, slug: 'about', rest_url: 'x', template: 'single' }) }; }) as unknown as typeof fetch;
    const wp = await import(`../lib/api/wp.ts?res=${Date.now()}`);
    await wp.resolve('/about');
    expect(calledUrl.endsWith('/wp-json/blueworx/v1/resolve?uri=%2Fabout')).toBe(true);
  });

  test('rewriteMenuUrl strips the WP origin to a path', async () => {
    const wp = await import(`../lib/api/wp.ts?rw=${Date.now()}`);
    expect(wp.rewriteMenuUrl('https://cms.blueworx.io/about/')).toBe('/about/');
    expect(wp.rewriteMenuUrl('https://cms.blueworx.io')).toBe('/');
    expect(wp.rewriteMenuUrl('/already/a/path')).toBe('/already/a/path');
  });

  test('a non-2xx response throws', async () => {
    globalThis.fetch = (async () => ({ ok: false, status: 500, statusText: 'Server Error' })) as unknown as typeof fetch;
    const wp = await import(`../lib/api/wp.ts?err=${Date.now()}`);
    await expect(wp.getSite()).rejects.toThrow(/failed: 500/);
  });
});
