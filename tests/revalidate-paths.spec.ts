import { test, expect } from '@playwright/test';
import { filterPaths } from '../lib/revalidate';

test('keeps only string entries', () => {
  expect(filterPaths(['/a', 1, null, '/b', { p: '/c' }, true])).toEqual(['/a', '/b']);
});

test('non-array input → empty array', () => {
  expect(filterPaths(undefined)).toEqual([]);
  expect(filterPaths('/a')).toEqual([]);
  expect(filterPaths(null)).toEqual([]);
});
