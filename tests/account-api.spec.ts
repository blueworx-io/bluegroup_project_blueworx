import { test, expect } from '@playwright/test';
import { register, forgotPassword, getMe } from '../lib/api/account';
import { setAccessToken } from '../lib/wp-client';
import { WpAuthError } from '../lib/wp-client';

const realFetch = globalThis.fetch;
test.afterEach(() => { globalThis.fetch = realFetch; });
test.beforeEach(() => { setAccessToken(null); });

test('register with verification on returns verificationRequired=true (generic success)', async () => {
  globalThis.fetch = (async () => ({ ok: true, status: 200, json: async () => ({ ok: true, message: 'If that email can be used, …' }) })) as unknown as typeof fetch;
  const r = await register('new@example.com', 'longenough');
  expect(r.verificationRequired).toBe(true);
  expect(r.user).toBeUndefined();
});

test('register with verification off returns a session (verificationRequired=false)', async () => {
  globalThis.fetch = (async () => ({ ok: true, status: 200, json: async () => ({ access_token: 't', user: { id: 9, display_name: 'New User' } }) })) as unknown as typeof fetch;
  const r = await register('new@example.com', 'longenough');
  expect(r.verificationRequired).toBe(false);
  expect(r.user?.id).toBe(9);
});

test('register surfaces registration-closed as a typed error', async () => {
  globalThis.fetch = (async () => ({ ok: false, status: 403, json: async () => ({ code: 'blueworx_registration_closed', message: 'Closed', data: { status: 403 } }) })) as unknown as typeof fetch;
  await expect(register('x@y.com', 'longenough')).rejects.toBeInstanceOf(WpAuthError);
});

test('forgotPassword resolves (generic) on a 200', async () => {
  globalThis.fetch = (async () => ({ ok: true, status: 200, json: async () => ({ ok: true }) })) as unknown as typeof fetch;
  await expect(forgotPassword('a@b.com')).resolves.toBeUndefined();
});

test('getMe returns the parsed user on success', async () => {
  globalThis.fetch = (async () => ({ ok: true, status: 200, json: async () => ({ id: 1, email: 'a@b.com', username: 'a', display_name: 'A', first_name: 'A', last_name: 'B', roles: ['subscriber'], capabilities: ['read'] }) })) as unknown as typeof fetch;
  const me = await getMe();
  expect(me.display_name).toBe('A');
  expect(me.capabilities).toContain('read');
});
