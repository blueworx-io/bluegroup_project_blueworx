import { test, expect } from '@playwright/test';
import { api, login, getAccessToken, setAccessToken } from '../lib/wp-client';

// Static imports (CI-safe). wp-client keeps the access token in module state, so
// reset it between tests. URL assertions use endsWith, so they're independent of
// whether config resolved to a live or mock base.
const realFetch = globalThis.fetch;
test.afterEach(() => { globalThis.fetch = realFetch; });
test.beforeEach(() => { setAccessToken(null); });

test('api() refreshes once on 401 then retries with the new token', async () => {
  const calls: string[] = [];
  let authed = false;
  globalThis.fetch = (async (url: string) => {
    calls.push(url);
    if (url.endsWith('/auth/refresh')) { authed = true; return { ok: true, json: async () => ({ access_token: 'tok' }) }; }
    if (url.endsWith('/auth/me')) return { status: authed ? 200 : 401, ok: authed, json: async () => ({ id: 1 }) };
    return { status: 404, ok: false };
  }) as unknown as typeof fetch;

  const res = await api('/auth/me');
  expect(res.status).toBe(200);
  expect(calls.filter((u) => u.endsWith('/auth/me'))).toHaveLength(2); // initial + retry
  expect(calls.filter((u) => u.endsWith('/auth/refresh'))).toHaveLength(1);
});

test('login stores the access token and returns the user', async () => {
  globalThis.fetch = (async () => ({ ok: true, json: async () => ({ access_token: 't', user: { id: 7 } }) })) as unknown as typeof fetch;
  const user = await login('jane@example.com', 'pw');
  expect(user).toEqual({ id: 7 });
  expect(getAccessToken()).toBe('t');
});
