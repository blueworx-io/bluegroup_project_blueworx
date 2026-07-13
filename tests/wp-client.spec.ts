// tests/wp-client.spec.ts
import { test, expect } from '@playwright/test';

const realFetch = globalThis.fetch;
test.afterEach(() => { globalThis.fetch = realFetch; });

test('api() refreshes once on 401 then retries with the new token', async () => {
  process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io';
  const calls: string[] = [];
  let authed = false;
  globalThis.fetch = (async (url: string, init?: RequestInit) => {
    calls.push(url);
    if (url.endsWith('/auth/refresh')) { authed = true; return { ok: true, json: async () => ({ access_token: 'tok' }) }; }
    if (url.endsWith('/auth/me')) return { status: authed ? 200 : 401, ok: authed, json: async () => ({ id: 1 }) };
    return { status: 404, ok: false };
  }) as unknown as typeof fetch;

  const wc = await import(`../lib/wp-client.ts?a=${Date.now()}`);
  const res = await wc.api('/auth/me');
  expect(res.status).toBe(200);
  expect(calls.filter((u) => u.endsWith('/auth/me'))).toHaveLength(2); // initial + retry
  expect(calls.filter((u) => u.endsWith('/auth/refresh'))).toHaveLength(1);
});

test('login stores the access token and returns the user', async () => {
  process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io';
  globalThis.fetch = (async () => ({ ok: true, json: async () => ({ access_token: 't', user: { id: 7 } }) })) as unknown as typeof fetch;
  const wc = await import(`../lib/wp-client.ts?b=${Date.now()}`);
  const user = await wc.login('jane@example.com', 'pw');
  expect(user).toEqual({ id: 7 });
  expect(wc.getAccessToken()).toBe('t');
});
