import { test, expect } from '@playwright/test';
import { errorMessage, isUnverified, isRegistrationClosed, retryAfterSeconds, passwordTooShort, PASSWORD_MIN } from '../lib/auth/errors';
import { WpAuthError } from '../lib/wp-client';

test('maps known codes to friendly messages', () => {
  expect(errorMessage(new WpAuthError('blueworx_invalid_login', 'x', 401))).toMatch(/incorrect/i);
  expect(errorMessage(new WpAuthError('blueworx_weak_password', 'x', 400))).toMatch(/8 characters/i);
});

test('unknown code and non-WpAuthError fall back to generic', () => {
  expect(errorMessage(new WpAuthError('something_else', 'x', 400))).toMatch(/something went wrong/i);
  expect(errorMessage(new Error('boom'))).toMatch(/something went wrong/i);
});

test('flag helpers detect their codes', () => {
  expect(isUnverified(new WpAuthError('blueworx_email_unverified', 'x', 403))).toBe(true);
  expect(isRegistrationClosed(new WpAuthError('blueworx_registration_closed', 'x', 403))).toBe(true);
  expect(retryAfterSeconds(new WpAuthError('blueworx_rate_limited', 'x', 429, 480))).toBe(480);
});

test('passwordTooShort enforces the 8-char minimum', () => {
  expect(PASSWORD_MIN).toBe(8);
  expect(passwordTooShort('1234567')).toBe(true);
  expect(passwordTooShort('12345678')).toBe(false);
});
