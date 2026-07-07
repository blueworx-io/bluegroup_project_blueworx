# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.1] - 2026-07-07

### Changed

- Synced `CLAUDE.md` with the updated foundation template — adds the standard headless framework line (Next.js App Router + TypeScript).

### Fixed

- Made the Playwright CI step runnable: installed `@playwright/test` (approved in `approved-deps.json`), added a `.gitignore`, and added a release-hygiene smoke test so `npx playwright test` has a real test to run.

## [0.1.0] - 2026-07-07

### Added

- Initial project scaffold: CI guardrail workflow (pointing at `bluegroup_core_foundation`), Claude Code settings, PR/issue templates, `approved-deps.json`, and a basic Playwright config.
