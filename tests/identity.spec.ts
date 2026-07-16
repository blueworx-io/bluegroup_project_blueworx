import { test, expect } from '@playwright/test';
import { initialsOf } from '../lib/auth/identity';

test('builds up-to-two uppercase initials', () => {
  expect(initialsOf('Hannah Whitfield')).toBe('HW');
  expect(initialsOf('dana')).toBe('D');
  expect(initialsOf('Mary Jane Watson')).toBe('MJ');
  expect(initialsOf('  spaced   out ')).toBe('SO');
});
