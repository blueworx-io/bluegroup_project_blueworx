// tests/revalidate.spec.js
import { test, expect } from '@playwright/test';

// The test server does not set REVALIDATE_SECRET, so the route must fail closed:
// every request is rejected until a secret is configured. This pins the
// security-critical branch (constant-time compare, reject on mismatch/absence).
test.describe('POST /api/revalidate', () => {
  test('rejects a request with no secret header (fails closed)', async ({ request }) => {
    const res = await request.post('/api/revalidate', { data: { paths: ['/about'] } });
    expect(res.status()).toBe(401);
  });

  test('rejects a request with a wrong secret', async ({ request }) => {
    const res = await request.post('/api/revalidate', {
      headers: { 'x-blueworx-revalidate': 'definitely-wrong' },
      data: { paths: ['/about'] },
    });
    expect(res.status()).toBe(401);
  });
});
