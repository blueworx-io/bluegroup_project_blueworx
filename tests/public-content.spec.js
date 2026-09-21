// Hermetic PHP-level tests for the content data layer
// (includes/public/content.php). Follows the pattern established in
// tests/public-site.spec.js (see e.g. resolveOwnedTemplate()/runHelpersPublicPhp()
// there): `require`s the real production file with the handful of WordPress
// functions it touches stubbed out, then runs a snippet of PHP against it and
// captures stdout as JSON. No browser, no WordPress install — every accessor
// here is pure data, so a hermetic test is both the cheapest and the most
// direct way to pin the exact shape and values ported from lib/data.ts.
import { readFileSync, mkdtempSync, writeFileSync, rmSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect } from '@playwright/test';
import { test } from './helpers.js';

const CONTENT_PHP = fileURLToPath(new URL('../includes/public/content.php', import.meta.url));

/**
 * Directly `require`s the real includes/public/content.php with
 * apply_filters()/__() stubbed as pass-throughs (neither accessor needs more
 * than that — no options, no post lookups), runs the given PHP body against
 * it, and returns whatever the body echoes, parsed as JSON.
 *
 * @param {string} body PHP statements to run after content.php loads. Must
 *   `echo json_encode(...)` whatever the test wants to assert on.
 * @return {*} The parsed JSON the body echoed.
 */
function runContentPhp(body) {
  const workDir = mkdtempSync(join(tmpdir(), 'bw-content-test-'));

  try {
    const bwPath = `${workDir.replace(/\\/g, '/')}/`;
    const contentPath = CONTENT_PHP.replace(/\\/g, '/');

    const script = `<?php
define( 'ABSPATH', '${bwPath}' );

function __( $text, $domain = 'default' ) { return $text; }
function apply_filters( $tag, $value ) { return $value; }

require '${contentPath}';

${body}
`;

    const scriptPath = join(workDir, 'harness.php');
    writeFileSync(scriptPath, script);

    return JSON.parse(execFileSync('php', [scriptPath], { encoding: 'utf8' }).trim());
  } finally {
    rmSync(workDir, { recursive: true, force: true });
  }
}

// Deliberately outside any browser-gated describe: this suite needs neither a
// browser nor a live WordPress target, matching the hermetic suites at the
// bottom of tests/public-site.spec.js.
test.describe('Content data layer (includes/public/content.php)', () => {
  test('blueworx_content_tools() returns 12 tools, each with exactly 6 features', () => {
    const tools = runContentPhp('echo json_encode( blueworx_content_tools() );');

    expect(tools, 'must return exactly 12 tools').toHaveLength(12);
    for (const tool of tools) {
      expect(
        tool.features,
        `tool "${tool.slug}" must have exactly 6 features`
      ).toHaveLength(6);
      for (const feature of tool.features) {
        expect(Object.keys(feature).sort()).toEqual(['desc', 'icon', 'title']);
      }
      expect(Object.prototype.hasOwnProperty.call(tool, 'btn'), 'no tool carries a raw btn class').toBe(false);
    }
  });

  test('surecart is the only tool marked popular', () => {
    const tools = runContentPhp('echo json_encode( blueworx_content_tools() );');

    const popular = tools.filter((tool) => true === tool.popular).map((tool) => tool.slug);
    expect(popular, 'only surecart may be popular').toEqual(['surecart']);
  });

  test('every tool slug has a matching solo_prices entry (fixtures parity)', () => {
    const tools = runContentPhp('echo json_encode( blueworx_content_tools() );');
    const prices = runContentPhp('echo json_encode( blueworx_content_solo_prices() );');

    const toolSlugs = tools.map((tool) => tool.slug).sort();
    const priceSlugs = Object.keys(prices).sort();
    expect(priceSlugs, 'solo_prices must have exactly one entry per tool, no more, no fewer').toEqual(toolSlugs);
  });

  test('blueworx_content_tool() returns a single tool by slug, or null', () => {
    const surecart = runContentPhp("echo json_encode( blueworx_content_tool( 'surecart' ) );");
    expect(surecart.slug).toBe('surecart');
    expect(surecart.popular).toBe(true);

    const missing = runContentPhp("echo json_encode( blueworx_content_tool( 'does-not-exist' ) );");
    expect(missing).toBeNull();
  });

  test('blueworx_content_toolbox_plans() returns 3 plans with no btn field', () => {
    const plans = runContentPhp('echo json_encode( blueworx_content_toolbox_plans() );');
    expect(plans).toHaveLength(3);
    for (const plan of plans) {
      expect(plan).not.toHaveProperty('btn');
      expect(plan.features.length).toBeGreaterThan(0);
    }
  });

  test('blueworx_content_support_packages() returns the nine GBP packages in order', () => {
    const packages = runContentPhp('echo json_encode( blueworx_content_support_packages() );');
    expect(packages.map((p) => [p.name, p.hours, p.priceM])).toEqual([
      ['Starter', 24, 100],
      ['Launch', 48, 200],
      ['Scale', 75, 300],
      ['Enhance', 105, 400],
      ['Growth', 140, 500],
      ['Enterprise', 225, 750],
      ['Enterprise +', 320, 1000],
      ['Advantage', 430, 1250],
      ['Advantage +', 600, 1500],
    ]);
    for (const p of packages) {
      expect(p.currency).toBe('GBP');
      expect(p.priceA).toBe(p.priceM);
      expect(p.features).toHaveLength(5);
    }
    expect(packages.filter((p) => p.featured).map((p) => p.name)).toEqual(['Starter', 'Growth', 'Enterprise +']);
    expect(packages.filter((p) => p.feat).map((p) => p.name)).toEqual(['Growth']);
    // retainer_plans is the same list under its historical name.
    const retainer = runContentPhp('echo json_encode( blueworx_content_retainer_plans() );');
    expect(retainer).toEqual(packages);
  });

  test('hosting and clubhouse each carry one £20/£200 plan and their section data', () => {
    const hosting = runContentPhp('echo json_encode( blueworx_content_hosting() );');
    expect(hosting.plan).toMatchObject({ name: 'Managed Hosting', priceM: 20, priceA: 200, currency: 'GBP', feat: true, pop: 'Per site' });
    expect(hosting.plan.features).toHaveLength(8);
    expect(hosting.perf).toHaveLength(6);
    expect(hosting.security).toHaveLength(6);
    expect(hosting.compare).toHaveLength(8);
    expect(hosting.faqs).toHaveLength(5);

    const clubhouse = runContentPhp('echo json_encode( blueworx_content_clubhouse() );');
    expect(clubhouse.plan).toMatchObject({ name: 'ClubHouse', priceM: 20, priceA: 200, currency: 'GBP', feat: true, pop: true });
    expect(clubhouse.plan.features).toHaveLength(7);
    expect(clubhouse.modules).toHaveLength(9);
    expect(clubhouse.modules.every((m) => Array.isArray(m.paths) && m.paths.length > 0)).toBe(true);
    expect(clubhouse.self_serve).toHaveLength(4);
    expect(clubhouse.audiences).toHaveLength(4);
    expect(clubhouse.faqs).toHaveLength(5);
  });

  test('the support FAQ has five entries', () => {
    expect(runContentPhp('echo json_encode( blueworx_content_support_faqs() );')).toHaveLength(5);
  });

  test('blueworx_content_faqs() returns 5 question/answer pairs', () => {
    const faqs = runContentPhp('echo json_encode( blueworx_content_faqs() );');
    expect(faqs).toHaveLength(5);
    for (const faq of faqs) {
      expect(Object.keys(faq).sort()).toEqual(['a', 'q']);
    }
  });

  test('blueworx_content_reviews() returns 3 reviews with the expected shape', () => {
    const reviews = runContentPhp('echo json_encode( blueworx_content_reviews() );');
    expect(reviews).toHaveLength(3);
    for (const review of reviews) {
      expect(Object.keys(review).sort()).toEqual(['initials', 'name', 'role', 'text']);
    }
    expect(reviews[0].name).toBe('Andrew');
  });

  test('each accessor result is filterable via blueworx_content_<name>', () => {
    // apply_filters() is stubbed as a pass-through above, so this only proves
    // every accessor actually calls it (not that WordPress filtering itself
    // works) — a return value that never touched apply_filters() would be
    // unaffected by a later cycle's override, which is exactly the defect
    // this guards against.
    const src = readFileSync(CONTENT_PHP, 'utf8');
    const expectedFilters = [
      'blueworx_content_tools',
      'blueworx_content_solo_prices',
      'blueworx_content_toolbox_plans',
      // blueworx_content_support_packages() deliberately fires the
      // blueworx_content_retainer_plans filter (kept for the SureCart wiring
      // configured under that name), so that tag — not a
      // 'blueworx_content_support_packages' one — is what's asserted here.
      'blueworx_content_retainer_plans',
      'blueworx_content_support_faqs',
      'blueworx_content_faqs',
      'blueworx_content_hosting',
      'blueworx_content_clubhouse',
      'blueworx_content_reviews',
      'blueworx_content_portfolio',
    ];
    for (const filter of expectedFilters) {
      expect(src, `content.php must call apply_filters( '${filter}', ... )`).toContain(`apply_filters( '${filter}'`);
    }
  });
});
