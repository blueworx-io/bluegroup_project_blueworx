import { test, expect } from '@playwright/test';
import { deriveBases } from '../lib/config';

// Tests the pure derivation directly (static import) — no module re-evaluation or
// env juggling, which is both simpler and CI-safe (dynamic `import('...ts?query')`
// is not transformed under the CI Node/Playwright loader).
test.describe('config base derivation', () => {
  test('derives both REST bases from a live origin and strips a trailing slash', () => {
    const b = deriveBases('https://cms.blueworx.io/');
    expect(b.wpOrigin).toBe('https://cms.blueworx.io');
    expect(b.blueworxApi).toBe('https://cms.blueworx.io/wp-json/blueworx/v1');
    expect(b.wpApi).toBe('https://cms.blueworx.io/wp-json/wp/v2');
    expect(b.useMockData).toBe(false);
  });

  test('empty or missing origin means mock mode and empty bases', () => {
    for (const input of ['', undefined, null]) {
      const b = deriveBases(input);
      expect(b.wpOrigin).toBe('');
      expect(b.blueworxApi).toBe('');
      expect(b.wpApi).toBe('');
      expect(b.useMockData).toBe(true);
    }
  });
});
