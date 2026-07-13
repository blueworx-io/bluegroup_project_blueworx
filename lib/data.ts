// Shared site data ported from the BlueWorx Site v4 design export.

export type ToolFeature = { icon: string; title: string; desc: string };

export type Tool = {
  slug: string;
  name: string;
  desc: string;
  domain: string;
  category: string;
  popular?: boolean;
  tagline: string;
  features: ToolFeature[];
  soloPrice?: number;
};

export const TOOLBOX_TOOLS: Tool[] = [
  { slug: 'sureforms', name: 'SureForms', desc: 'Flexible form builder with smart, multi-step flows.', domain: 'sureforms.com', category: 'Build', tagline: 'Build forms that convert, from simple contact forms to smart, multi-step flows with conditional logic.', features: [
    { icon: 'workflow', title: 'Conditional logic', desc: 'Show or hide fields based on what a visitor enters, so every form feels built just for them.' },
    { icon: 'palette', title: 'On-brand templates', desc: 'Dozens of ready-made layouts that match your site design in a couple of clicks.' },
    { icon: 'chart', title: 'Submission insights', desc: 'See completion rates and drop-off points so you can improve conversion over time.' },
    { icon: 'mail', title: 'Instant notifications', desc: 'Route every submission to the right inbox, Slack channel, or CRM the moment it arrives.' },
    { icon: 'shield', title: 'Spam protection', desc: 'Honeypots and smart filtering keep junk out without making visitors solve puzzles.' },
    { icon: 'plug', title: 'Payment fields', desc: 'Take deposits or full payments right inside a form. No separate checkout needed.' },
  ] },
  { slug: 'surerank', name: 'SureRank', desc: 'SEO insights to improve search visibility.', domain: 'surerank.com', category: 'Grow', tagline: 'Plain-English SEO guidance that shows you exactly what to fix to climb the search results.', features: [
    { icon: 'gauge', title: 'Site health score', desc: 'A single score tracks technical SEO issues across every page of your site.' },
    { icon: 'chart', title: 'Keyword tracking', desc: 'Monitor rankings for the terms that matter most to your business.' },
    { icon: 'zap', title: 'One-click fixes', desc: 'Resolve common on-page SEO issues without touching a line of code.' },
    { icon: 'doc', title: 'Content analysis', desc: 'Page-by-page guidance on headings, length, and structure that search engines reward.' },
    { icon: 'workflow', title: 'Schema automation', desc: 'Structured data added automatically so your pages earn rich results.' },
    { icon: 'users', title: 'Competitor snapshots', desc: 'See who outranks you for key terms and exactly what they do differently.' },
  ] },
  { slug: 'suremail', name: 'SureMail', desc: 'Reliable delivery for transactional and automated emails.', domain: 'suremails.com', category: 'Grow', tagline: 'Rock-solid delivery for every transactional and automated email your site sends.', features: [
    { icon: 'mail', title: 'Guaranteed delivery', desc: 'Emails route through a trusted infrastructure so they land in the inbox, not spam.' },
    { icon: 'chart', title: 'Delivery logs', desc: 'See every email sent, opened, and clicked from one simple dashboard.' },
    { icon: 'shield', title: 'Built-in authentication', desc: 'SPF, DKIM, and DMARC handled for you automatically.' },
    { icon: 'clock', title: 'Smart retries', desc: 'Failed sends are queued and retried automatically, so nothing silently disappears.' },
    { icon: 'workflow', title: 'Multiple providers', desc: 'Fall back across sending services for uptime even when one provider has issues.' },
    { icon: 'doc', title: 'Template management', desc: 'Keep transactional email templates versioned, branded, and easy to update.' },
  ] },
  { slug: 'surewriter', name: 'SureWriter', desc: 'AI-assisted website and marketing copy.', domain: 'surewriter.com', category: 'Build', tagline: 'AI-assisted copywriting that drafts on-brand website and marketing content in seconds.', features: [
    { icon: 'sparkles', title: 'On-brand drafts', desc: 'Generates copy tuned to your tone of voice, ready to refine and publish.' },
    { icon: 'zap', title: 'Rewrite & polish', desc: 'Tighten, lengthen, or restyle existing copy without starting from scratch.' },
    { icon: 'doc', title: 'Every page type', desc: 'Landing pages, product descriptions, emails, and social posts, covered.' },
    { icon: 'users', title: 'Audience presets', desc: 'Tune drafts to a specific customer profile so copy speaks their language.' },
    { icon: 'chart', title: 'A/B variations', desc: 'Generate multiple headline and CTA options to test what converts best.' },
    { icon: 'workflow', title: 'Brief-to-draft flow', desc: 'Drop in a short brief and get a structured first draft, section by section.' },
  ] },
  { slug: 'surecart', name: 'SureCart', desc: 'Modern checkout, subscriptions, and digital sales.', domain: 'surecart.com', category: 'Sell', popular: true, tagline: 'A modern checkout and subscription engine for selling products, services, and digital downloads.', features: [
    { icon: 'cart', title: 'Optimised checkout', desc: 'A fast, distraction-free checkout built to lift conversion on every device.' },
    { icon: 'calendar', title: 'Subscriptions built in', desc: 'Recurring billing, upgrades, and dunning management with no extra plugins.' },
    { icon: 'shield', title: 'PCI-compliant payments', desc: 'Accept cards, wallets, and more with security handled for you.' },
    { icon: 'zap', title: 'One-page upsells', desc: 'Post-purchase offers and order bumps that raise average order value.' },
    { icon: 'doc', title: 'Digital delivery', desc: 'Secure, expiring download links for e-books, courses, and files.' },
    { icon: 'chart', title: 'Revenue analytics', desc: 'MRR, churn, and lifetime value tracked without a spreadsheet in sight.' },
  ] },
  { slug: 'zipwp', name: 'ZipWP', desc: 'AI-generated WordPress websites in minutes.', domain: 'zipwp.com', category: 'Build', tagline: 'Describe your business and get a complete, AI-generated WordPress website in minutes.', features: [
    { icon: 'sparkles', title: 'AI site generation', desc: 'Full pages, copy, and imagery generated from a short business description.' },
    { icon: 'palette', title: 'Instant redesigns', desc: 'Regenerate the look and feel until it fits, before you touch a single setting.' },
    { icon: 'zap', title: 'Launch-ready in minutes', desc: 'From idea to a live, editable website in one sitting.' },
    { icon: 'doc', title: 'Industry templates', desc: 'Starting points tuned to your sector, from trades to consultants to cafés.' },
    { icon: 'workflow', title: 'Fully editable output', desc: 'Everything generated lands in your normal editor. Nothing is locked in.' },
    { icon: 'chart', title: 'Built-in best practice', desc: 'Generated pages follow proven layout and conversion patterns by default.' },
  ] },
  { slug: 'ottokit', name: 'OttoKit', desc: 'Automated workflows connecting sites and tools.', domain: 'ottokit.com', category: 'Automate', tagline: 'Connect your site to the tools you already use with no-code automated workflows.', features: [
    { icon: 'workflow', title: 'Visual workflow builder', desc: 'Chain triggers and actions across apps without writing a single line of code.' },
    { icon: 'plug', title: 'Hundreds of integrations', desc: 'Connect CRMs, spreadsheets, marketing tools, and more out of the box.' },
    { icon: 'clock', title: 'Runs in the background', desc: 'Automations fire instantly and quietly, so nothing needs manual follow-up.' },
    { icon: 'shield', title: 'Error alerts', desc: 'Get notified the moment a workflow fails, with a clear trail of what happened.' },
    { icon: 'chart', title: 'Run history', desc: 'Every automation run is logged, so you can audit exactly what fired and when.' },
    { icon: 'zap', title: 'Conditional branches', desc: 'Split workflows by customer type, order value, or any field you choose.' },
  ] },
  { slug: 'ally', name: 'Ally', desc: 'Improves website accessibility and usability.', domain: 'useally.io', category: 'Support', tagline: 'Ongoing accessibility improvements so every visitor can use your site with ease.', features: [
    { icon: 'shield', title: 'WCAG monitoring', desc: 'Continuous scans flag accessibility issues as your site changes.' },
    { icon: 'zap', title: 'Guided remediation', desc: 'Clear, prioritised steps to fix issues instead of a raw audit dump.' },
    { icon: 'users', title: 'Usability for everyone', desc: 'Keyboard navigation, screen readers, and contrast handled properly.' },
    { icon: 'doc', title: 'Compliance reports', desc: 'Shareable reports that document your accessibility posture for stakeholders.' },
    { icon: 'zap', title: 'Automatic alt text', desc: 'Missing image descriptions flagged and suggested so nothing ships unlabeled.' },
    { icon: 'clock', title: 'Continuous coverage', desc: 'New pages and edits are scanned as they go live, not just at audit time.' },
  ] },
  { slug: 'sweet-ai', name: 'Sweet AI', desc: 'AI support for improving site content.', domain: 'sweetai.com', category: 'Build', tagline: 'An AI assistant that reviews and improves your site content for clarity and impact.', features: [
    { icon: 'sparkles', title: 'Content suggestions', desc: 'Surfaces specific rewrites for weak or unclear copy across your site.' },
    { icon: 'chart', title: 'Readability scoring', desc: 'Flags dense or confusing passages before visitors bounce.' },
    { icon: 'zap', title: 'One-click apply', desc: 'Accept a suggestion and it publishes straight to the live page.' },
    { icon: 'users', title: 'Tone matching', desc: 'Suggestions respect your brand voice instead of flattening it.' },
    { icon: 'workflow', title: 'Site-wide review', desc: 'Scan every page at once and work through improvements from one queue.' },
    { icon: 'shield', title: 'Human-in-the-loop', desc: 'Nothing changes on your site until you approve it. AI proposes, you decide.' },
  ] },
  { slug: 'elementor-ai-planner', name: 'Elementor AI Planner', desc: 'AI-guided website structure and planning.', domain: 'elementor.com', category: 'Build', tagline: 'AI-guided planning that maps out your site structure before you build a single page.', features: [
    { icon: 'sparkles', title: 'Sitemap generation', desc: 'Get a full page and navigation structure from a short project brief.' },
    { icon: 'workflow', title: 'Content outlines', desc: 'Section-by-section outlines for every page, ready to hand to a writer.' },
    { icon: 'chart', title: 'Goal-based planning', desc: 'Structures tuned to lead generation, sales, or content, based on your goal.' },
    { icon: 'users', title: 'Audience mapping', desc: 'Plans pages around the questions your actual customers arrive with.' },
    { icon: 'doc', title: 'Exportable briefs', desc: 'Hand a complete, structured brief to your designer or builder in one click.' },
    { icon: 'zap', title: 'Revise in seconds', desc: 'Adjust the brief and regenerate the whole plan instantly, with no rework.' },
  ] },
  { slug: 'elementor', name: 'Elementor', desc: 'Visual page building without code.', domain: 'elementor.com', category: 'Build', tagline: 'The visual page builder behind every custom layout we design, no code required.', features: [
    { icon: 'palette', title: 'Drag-and-drop design', desc: 'Build pixel-perfect layouts visually, with instant live preview.' },
    { icon: 'code', title: 'Theme building', desc: 'Design headers, footers, and archive templates without touching code.' },
    { icon: 'zap', title: 'Fast, responsive output', desc: 'Every layout adapts cleanly across desktop, tablet, and mobile.' },
    { icon: 'workflow', title: 'Global styles', desc: 'Change a color or font once and it updates consistently across the site.' },
    { icon: 'plug', title: 'Deep integrations', desc: 'Forms, popups, and dynamic content connect to the rest of your stack.' },
    { icon: 'clock', title: 'Revision history', desc: 'Roll any page back to an earlier version whenever you need to.' },
  ] },
  { slug: 'equalize-a11y-checker', name: 'Equalize A11y Checker', desc: 'Real-time WCAG accessibility checks.', domain: 'equalizedigital.com', category: 'Support', tagline: 'Real-time WCAG accessibility checks that catch issues as pages are edited.', features: [
    { icon: 'shield', title: 'Real-time scanning', desc: 'Flags accessibility issues the moment a page is edited or published.' },
    { icon: 'chart', title: 'Detailed reporting', desc: 'Clear reports mapped to WCAG success criteria, not vague warnings.' },
    { icon: 'gauge', title: 'Compliance tracking', desc: 'Track accessibility scores across your entire site over time.' },
    { icon: 'zap', title: 'In-editor warnings', desc: 'Issues surface while editing, so problems are fixed before they publish.' },
    { icon: 'doc', title: 'Fix documentation', desc: 'Every flagged issue links to plain-English guidance on how to resolve it.' },
    { icon: 'users', title: 'Team accountability', desc: 'Assign issues to teammates and track them through to resolution.' },
  ] },
];

export const SOLO_PRICES: Record<string, number> = {
  sureforms: 9, surerank: 16, suremail: 8, surewriter: 12, surecart: 19, zipwp: 12,
  ottokit: 17, ally: 29, 'sweet-ai': 12, 'elementor-ai-planner': 6, elementor: 8, 'equalize-a11y-checker': 12,
};

export type Plan = {
  name: string;
  desc: string;
  priceM: number;
  priceA: number;
  feat: boolean;
  pop?: boolean;
  btn: string;
  features: string[];
};

export const TOOLBOX_PLANS: Plan[] = [
  { name: 'Personal', desc: 'For individuals and single-site projects.', priceM: 30, priceA: 25, feat: false, btn: 'plan-btn out', features: ['All 12+ premium tools', 'Managed website hosting included', '1 website', 'Learning Center access', 'Site stability support'] },
  { name: 'Business', desc: 'For businesses running one or more sites.', priceM: 60, priceA: 50, feat: true, pop: true, btn: 'plan-btn dark', features: ['All 12+ premium tools', 'Managed website hosting included', 'Up to 5 websites', 'Learning Center access', 'Priority support'] },
  { name: 'Agency', desc: 'Bulk licensing for agencies and resellers.', priceM: 200, priceA: 160, feat: false, btn: 'plan-btn out', features: ['All 12+ premium tools', 'Managed hosting for every site', 'Up to 25 client websites', 'Bulk licensing for client sites', 'Dedicated account manager'] },
];

export const RETAINER_PLANS: Plan[] = [
  { name: 'Essential Support', desc: 'Designed for smaller businesses that require occasional updates and ongoing maintenance.', priceM: 200, priceA: 160, feat: false, btn: 'plan-btn out', features: ['Access to the free toolbox', 'Basic template site', '3 small updates per year', 'Up to 6 hours of expert design & developer support per month', 'Minimum 1 year commitment'] },
  { name: 'Growth Support', desc: 'Ideal for digital solutions that require regular updates, feature improvements, and ongoing development support.', priceM: 500, priceA: 400, feat: true, pop: true, btn: 'plan-btn dark', features: ['Access to free toolbox', 'Customised template site', '1 major update per year', '3 minor updates per year', 'Up to 12 hours of expert design & developer support per month'] },
  { name: 'Advanced Support', desc: 'Designed for fully customised digital solutions requiring ongoing development and improvements.', priceM: 750, priceA: 600, feat: false, btn: 'plan-btn out', features: ['Access to free toolbox', 'Completely customised site', '2 major updates per year', '3 minor updates per year', 'Unlimited expert design & developer support'] },
];

export const FAQS = [
  { q: 'How do payments work?', a: 'Pay and forget! Annual payments mean more time spent on your business and less time managing subscriptions. Choose monthly or annual billing at checkout, and you can switch at any point.' },
  { q: 'How do I get started?', a: 'Pick a plan, create your account, and our team helps you onboard step by step. Most websites are designed, built, and live within a few days.' },
  { q: 'Can I change my plan later?', a: 'Absolutely. Upgrade or downgrade at any time from your dashboard. Changes are prorated automatically so you only ever pay for what you use.' },
  { q: 'Do I need to be a developer?', a: 'Not at all. BlueWorx is built for business owners. Our tools are no-code and our expert team handles anything technical on your behalf.' },
  { q: 'Will I be able to edit my package?', a: 'Yes. Add tools, spin up new sites, and adjust your support allowance whenever your needs change. Your package flexes with your business.' },
];

export const HOME_REVIEWS = [
  { text: 'BlueWorx took our site off three separate platforms and put everything in one place. Hosting, tools, and support — it just works.', initials: 'H', name: 'Hannah Whitfield', role: 'Owner, Bloom & Co.' },
  { text: 'They rebuilt our booking flow and our conversions climbed within weeks. It genuinely feels like an extension of our own team.', initials: 'D', name: 'Daniel Okafor', role: 'Director, Padel365' },
  { text: 'Fast, reliable, and always one message away. The toolbox alone saved us hundreds a month in subscriptions.', initials: 'P', name: 'Priya Nair', role: 'Founder, Hirasté' },
  { text: 'Migrated our entire site with zero downtime. The support has been outstanding at every single step.', initials: 'M', name: 'Marcus Reed', role: 'CEO, QURE' },
];

export function faviconUrl(domain: string, size = 64) {
  return `https://www.google.com/s2/favicons?domain=${domain}&sz=${size}`;
}
