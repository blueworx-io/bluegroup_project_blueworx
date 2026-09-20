repo: blueworx-io/bluegroup_project_blueworx
branch: main

## Last sync

date: 2026-09-19T19:45:00Z

### Updated in this project

- Added six new pages: ClubHouse, Hosting, Integrated Support, Work, AI Powered, Contact
- Added a clickable client dashboard prototype (packages, sites, hours, requests, invoices, documents)
- Nav and footer extracted into shared `Site Nav` / `Site Footer` components; Contact is now the top-right nav button, About moved into the footer
- Site-wide currency switcher (GBP / EUR / USD) in the nav, read by every price on the site
- Design pass: fixed collapsed FAQ answers (repo CSS needs an `open` state), invisible plan badges, duplicate work image (imported `assets/img/feature-image-2.jpg`), selectable budget chips on Contact
- Second design pass: dropped Toolbox from the nav/footer, larger logo, section hairline dividers, uniform 116px section rhythm, deterministic card grids (no orphan rows), removed double gutters inside dark sections

## Screen map

| Screen | Repo files |
| --- | --- |
| BlueWorx Home.dc.html | templates/pages/home.php; templates/parts/*.php; assets/css/public.css; assets/js/public-nav.js, public-widgets.js |
| Site Nav.dc.html / Site Footer.dc.html | templates/parts/nav.php, footer.php; assets/js/public-nav.js |
| BlueWorx Support.dc.html | templates/pages/pricing.php; templates/parts/plan-cards.php, tech-hero.php; pricing from client's Integrated Support product list |
| BlueWorx Hosting.dc.html | templates/pages/pricing.php, services.php; templates/parts/plan-cards.php, tech-hero.php |
| BlueWorx ClubHouse.dc.html | New page. Product reference: demo.305media.co.uk; styling from templates/parts/tech-hero.php, glass-card.php, plan-cards.php |
| BlueWorx Work.dc.html | templates/pages/work.php; templates/parts/work-card.php, stats-band.php, testimonials.php, glass-card.php |
| BlueWorx AI Powered.dc.html | templates/pages/ai.php (rebuilt on its real `.ai-*` markup: ai-hero, ai-demo, ai-pipe, ai-models, ai-stack, ai-off-grid); assets/js/public-widgets.js |
| BlueWorx Contact.dc.html | templates/pages/contact.php; templates/parts/tech-hero.php |
| BlueWorx Dashboard.dc.html | templates/parts/dash-shell.php; templates/pages/dashboard.php, dashboard-websites.php, dashboard-support.php, dashboard-invoices.php |

## Sync history

- 2026-09-19T17:18:32Z — recreated the plugin's public home page as a Design Component; imported public.css, fonts, logo, imagery and tool icons
