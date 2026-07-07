import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

// Server-free smoke test so CI's Playwright step exercises something real
// before the app exists. Replace/extend with browser tests once the
// framework is scaffolded.

test('package.json version matches the newest CHANGELOG.md entry', () => {
  const pkg = JSON.parse(readFileSync('package.json', 'utf8'));
  const changelog = readFileSync('CHANGELOG.md', 'utf8');

  const newestHeading = changelog.match(/^## \[(\d+\.\d+\.\d+)\]/m);
  expect(newestHeading, 'CHANGELOG.md should have at least one "## [x.y.z]" heading').not.toBeNull();
  expect(newestHeading[1]).toBe(pkg.version);
});
