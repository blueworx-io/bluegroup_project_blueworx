import { test, expect } from '@playwright/test';
import { login, errorFromResponse, WpAuthError, setAccessToken } from '../lib/wp-client';

// Static imports (CI-safe). wp-client holds the access token in module state.
const realFetch = globalThis.fetch;
test.afterEach(() => { globalThis.fetch = realFetch; });
test.beforeEach(() => { setAccessToken(null); });

test('errorFromResponse maps the WP_Error envelope into a typed error', async () => {
  const res = { status: 429, json: async () => ({
    code: 'blueworx_rate_limited', message: 'Too many attempts.',
    data: { status: 429, retry_after: 480 },
  }) };
  const err = await errorFromResponse(res as unknown as Response);
  expect(err).toBeInstanceOf(WpAuthError);
  expect(err.code).toBe('blueworx_rate_limited');
  expect(err.status).toBe(429);
  expect(err.retryAfter).toBe(480);
});

test('errorFromResponse falls back to a generic error for a non-JSON body', async () => {
  const res = { status: 500, json: async () => { throw new Error('not json'); } };
  const err = await errorFromResponse(res as unknown as Response);
  expect(err.code).toBe('blueworx_request_failed');
  expect(err.status).toBe(500);
});

test('login throws a WpAuthError (not the raw body) on a non-2xx response', async () => {
  globalThis.fetch = (async () => ({
    ok: false, status: 401,
    json: async () => ({ code: 'blueworx_invalid_login', message: 'Invalid username/email or password.', data: { status: 401 } }),
  })) as unknown as typeof fetch;
  await expect(login('jane@example.com', 'wrong')).rejects.toBeInstanceOf(WpAuthError);
});
