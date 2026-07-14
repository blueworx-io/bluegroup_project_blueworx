import { test, expect } from '@playwright/test';
import { TOOLBOX_TOOLS } from '../lib/data';

const realFetch = globalThis.fetch;
test.afterEach(() => { globalThis.fetch = realFetch; });

// Mock-mode fallback (useMockData → static data) is covered end-to-end by
// tests/site.spec.js (the mock-mode server renders the static content on every
// page) and the static shapes are pinned by tests/fixtures-parity.spec.ts. It is
// deliberately NOT unit-tested here: doing so requires loading the shared
// `@/lib/config` module with no origin set, which evaluates canonical config in
// mock mode and poisons other specs sharing the Playwright worker.

test('live mode maps CPT items from wp/v2', async () => {
  process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io';
  globalThis.fetch = (async () => ({ ok: true, json: async () => ([
    { id: 1, slug: 'demo', title: { rendered: 'Demo' }, content: { rendered: '' },
      acf: { desc: 'd', domain: 'demo.com', category: 'Build', tagline: 't', features: [] } },
  ]) })) as unknown as typeof fetch;
  const c = await import(`../lib/api/content.ts?l=${Date.now()}`);
  const tools = await c.getTools();
  expect(tools).toHaveLength(1);
  expect(tools[0]).toMatchObject({ slug: 'demo', name: 'Demo', category: 'Build' });
});

test('live fetch failure falls back to static data (no throw, no blank)', async () => {
  process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io';
  globalThis.fetch = (async () => ({ ok: false, status: 500, statusText: 'err' })) as unknown as typeof fetch;
  const c = await import(`../lib/api/content.ts?f=${Date.now()}`);
  expect(await c.getTools()).toEqual(TOOLBOX_TOOLS);
});

test('empty CPT result falls back to static data', async () => {
  process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io';
  globalThis.fetch = (async () => ({ ok: true, json: async () => ([]) })) as unknown as typeof fetch;
  const c = await import(`../lib/api/content.ts?e=${Date.now()}`);
  expect(await c.getTools()).toEqual(TOOLBOX_TOOLS);
});
