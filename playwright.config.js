import { defineConfig } from '@playwright/test';

const port = Number(process.env.PORT || 3000);
const baseURL = process.env.BASE_URL || `http://localhost:${port}`;

// A second server started with PORTAL_REQUIRE_AUTH=true, so we can test the
// authenticated-portal behaviour (redirect when no session) without disturbing
// the main server, which runs the demo portal. See tests/portal-auth.spec.js.
const authPort = port + 1;
const authBaseURL = `http://localhost:${authPort}`;

export default defineConfig({
  testDir: './tests',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  reporter: process.env.CI ? 'list' : 'html',
  projects: [
    {
      // Everything except the auth-enforced portal check, against the demo server.
      name: 'app',
      testIgnore: /portal-auth\.spec\.js/,
      use: { baseURL, trace: 'on-first-retry' },
    },
    {
      // Only the auth-enforced portal check, against the PORTAL_REQUIRE_AUTH server.
      name: 'portal-auth',
      testMatch: /portal-auth\.spec\.js/,
      use: { baseURL: authBaseURL, trace: 'on-first-retry' },
    },
  ],
  webServer: [
    {
      // `next start` serves the production build created by the CI Build step
      // (run `npm run build` first when testing locally).
      command: `npm run start -- -p ${port}`,
      url: baseURL,
      reuseExistingServer: !process.env.CI,
      timeout: 120000,
    },
    {
      // Same build, but with portal auth enforced. getSession() returns null (no
      // auth backend is wired yet), so /portal must redirect unauthenticated visitors.
      command: `npm run start -- -p ${authPort}`,
      url: authBaseURL,
      reuseExistingServer: !process.env.CI,
      timeout: 120000,
      env: { PORTAL_REQUIRE_AUTH: 'true' },
    },
  ],
});
