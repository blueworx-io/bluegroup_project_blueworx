import { defineConfig } from '@playwright/test';
import dotenv from 'dotenv';

// Loads .env into process.env for local runs so credentials aren't pasted onto
// the command line. .env is gitignored; copy .env.example to .env. Never
// overrides an already-set variable, so CI — which injects env directly, with
// no .env present — always wins.
dotenv.config({ quiet: true });

const baseURL =
  process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'https://staging.placeholder.blueworx.io';

export default defineConfig({
  testDir: './tests',
  // The specs are read-only public GETs, but the local harness serves
  // WordPress from PHP's single-threaded built-in server, so keep concurrency
  // low and allow one retry to absorb the occasional slow response without
  // hiding a genuine failure (which fails twice).
  workers: 1,
  fullyParallel: false,
  retries: 1,
  timeout: 60_000,
  expect: { timeout: 10_000 },
  use: {
    baseURL,
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
  },
  reporter: 'line',
});
