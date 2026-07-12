import { test, expect } from '@playwright/test';

// The contact form POSTs here; the route validates server-side and (once
// CONTACT_FORWARD_URL is set) forwards to the plugin/SureForms. See
// docs/API_CONTRACT.md §4.
test.describe('POST /api/contact', () => {
  test('rejects a submission with missing or invalid fields', async ({ request }) => {
    const res = await request.post('/api/contact', {
      data: { firstName: '', lastName: 'Doe', email: 'not-an-email', phone: '', message: '' },
    });
    expect(res.status()).toBe(400);
    const body = await res.json();
    expect(body.ok).toBe(false);
    expect(body.errors.firstName).toBeTruthy();
    expect(body.errors.email).toBeTruthy();
    expect(body.errors.phone).toBeTruthy();
    expect(body.errors.message).toBeTruthy();
  });

  test('accepts a valid submission', async ({ request }) => {
    const res = await request.post('/api/contact', {
      data: {
        firstName: 'Ada',
        lastName: 'Lovelace',
        email: 'ada@example.com',
        phone: '+1 (555) 123-4567',
        countryCode: 'US',
        message: 'I need a new website.',
      },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.ok).toBe(true);
  });
});
