// tests/config-derivation.spec.ts
import { test, expect } from '@playwright/test';

test.describe('config base derivation', () => {
  test('derives both REST bases from a live origin and disables mock', async () => {
    process.env.NEXT_PUBLIC_WORDPRESS_URL = 'https://cms.blueworx.io/';
    const mod = await import(`../lib/config.ts?live=${Date.now()}`);
    expect(mod.WP_ORIGIN).toBe('https://cms.blueworx.io');           // trailing slash stripped
    expect(mod.BLUEWORX_API).toBe('https://cms.blueworx.io/wp-json/blueworx/v1');
    expect(mod.WP_API).toBe('https://cms.blueworx.io/wp-json/wp/v2');
    expect(mod.useMockData).toBe(false);
  });

  test('empty origin means mock mode and empty bases', async () => {
    delete process.env.NEXT_PUBLIC_WORDPRESS_URL;
    const mod = await import(`../lib/config.ts?mock=${Date.now()}`);
    expect(mod.WP_ORIGIN).toBe('');
    expect(mod.BLUEWORX_API).toBe('');
    expect(mod.useMockData).toBe(true);
  });
});
