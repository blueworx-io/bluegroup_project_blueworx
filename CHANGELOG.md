# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2026-07-07

### Added

- Implemented the full BlueWorx site from the Claude Design handoff (`BlueWorx Site v4.dc.html`) as a Next.js App Router + TypeScript app: home, services, about, work, contact, toolbox (plans, comparison table, savings calculator), tool detail pages for all 12 toolbox tools, pricing (retainers + pricing calculator), AI Powered page (animated prompt→code→site demo and pipeline), and the client portal mock (overview, onboarding, websites, toolbox, learning center, subscriptions, hours, invoices, support, partner portal).
- Shared chrome and components: sticky hide-on-scroll nav with toolbox mega menu and mobile menu, footer, CTA band, logos marquee, FAQ accordion, testimonials, and a ported design-token stylesheet in `app/globals.css` (Sora via `next/font`).
- Playwright browser tests covering navigation, feature tabs, billing toggles, the pricing calculator, contact-form validation, and portal tab/site switching, with a `webServer` config so `npx playwright test` runs against the production build.

### Changed

- Scaffolded the approved headless stack (`next`, `react`, `react-dom`, TypeScript, ESLint with `eslint-config-next`) and recorded it in `approved-deps.json`; `npm run lint` and `npm run build` are now real commands.

## [0.1.1] - 2026-07-07

### Changed

- Synced `CLAUDE.md` with the updated foundation template — adds the standard headless framework line (Next.js App Router + TypeScript).

### Fixed

- Made the Playwright CI step runnable: installed `@playwright/test` (approved in `approved-deps.json`), added a `.gitignore`, and added a release-hygiene smoke test so `npx playwright test` has a real test to run.

## [0.1.0] - 2026-07-07

### Added

- Initial project scaffold: CI guardrail workflow (pointing at `bluegroup_core_foundation`), Claude Code settings, PR/issue templates, `approved-deps.json`, and a basic Playwright config.
