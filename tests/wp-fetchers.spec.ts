import { test, expect } from '@playwright/test';
import { config } from '../lib/config';
import { getMenu, resolve, rewriteMenuUrl, getSite } from '../lib/api/wp';

// Static imports (CI-safe). wp.ts is stateless, so no per-test module reset is
// needed. URL assertions are relative to `config.blueworxApi`, so they hold
// whether the shared config resolved to a live origin or mock (empty) base —
// base derivation itself is covered by config-derivation.spec.ts.
test.describe('wp fetchers', () => {
  test('getMenu unwraps items and builds the namespaced URL', async () => {
    let calledUrl = '';
    globalThis.fetch = (async (url: string) => {
      calledUrl = url;
      return { ok: true, json: async () => ({ location: 'primary', items: [{ id: 1, title: 'About', url: 'https://cms.blueworx.io/about/', target: '', object: 'page', object_id: 12, children: [] }] }) };
    }) as unknown as typeof fetch;

    const items = await getMenu('primary');
    expect(calledUrl).toBe(`${config.blueworxApi}/menus/primary`);
    expect(items).toHaveLength(1);
    expect(items[0].title).toBe('About');
  });

  test('resolve builds the encoded query URL', async () => {
    let calledUrl = '';
    globalThis.fetch = (async (url: string) => { calledUrl = url; return { ok: true, json: async () => ({ type: 'page', id: 12, slug: 'about', rest_url: 'x', template: 'single' }) }; }) as unknown as typeof fetch;
    await resolve('/about');
    expect(calledUrl).toBe(`${config.blueworxApi}/resolve?uri=%2Fabout`);
  });

  test('rewriteMenuUrl strips the WP origin to a path', async () => {
    expect(rewriteMenuUrl('https://cms.blueworx.io/about/')).toBe('/about/');
    expect(rewriteMenuUrl('https://cms.blueworx.io')).toBe('/');
    expect(rewriteMenuUrl('/already/a/path')).toBe('/already/a/path');
  });

  test('a non-2xx response throws', async () => {
    globalThis.fetch = (async () => ({ ok: false, status: 500, statusText: 'Server Error' })) as unknown as typeof fetch;
    await expect(getSite()).rejects.toThrow(/failed: 500/);
  });
});
