// Portal data-access layer (server-side, authenticated).
//
// Returns everything the client portal renders for the signed-in customer.
// Subscriptions and invoices come from SureCart (via the plugin); the rest come
// from the plugin's own account/project endpoints. Today this serves the captured
// mock data; once NEXT_PUBLIC_WP_API_URL is set it fetches GET /portal/me for the
// authenticated user. See docs/API_CONTRACT.md §5.
//
// Presentation-derived fields (chip classes, "x / y hrs", percentages) are NOT in
// these shapes — Portal.tsx derives them from the raw values below, matching the
// contract so the plugin returns pure data.

import { config, useMockData } from "@/lib/config";

export type PortalClient = { name: string; first: string; company: string; initials: string; tier: string };
export type PortalSite = {
  label: string; url: string; platform: string; status: string;
  uptime: string; ssl: string; plan: string; visits: string; shot: string; dot: string;
};
export type Subscription = { name: string; site: string; price: string; cycle: string; status: string; renews: string; icon: string };
export type HoursPackage = { name: string; used: number; total: number; period: string; color: string };
export type StepState = "done" | "current" | "waiting" | "todo";
export type OnboardingStep = { title: string; desc: string; state: StepState };
export type OnboardingDoc = { name: string; hint: string; state: "received" | "pending" };
export type OnboardingProject = {
  name: string; type: string; stage: string;
  steps: OnboardingStep[]; docs: OnboardingDoc[];
  milestone: { label: string; date: string; who: string };
};
export type LearningStarter = { icon: string; title: string; meta: string };
export type PortalStat = { icon: string; value: string; label: string };
export type PlatformFact = { icon: string; label: string; value: string };
export type ActivityItem = { icon: string; text: string; time: string };
export type Invoice = { id: string; date: string; amount: string; status: string };
export type TimeLogEntry = { date: string; task: string; who: string; hrs: string };
export type Ticket = { title: string; ref: string; time: string; status: string };
export type TeamMember = { initial: string; name: string; role: string };
export type PartnerTier = { key: string; name: string; rate: number; desc: string; req: string };
export type PartnerBrand = { name: string; mult: number };
export type PartnerEarner = { name: string; type: string; refs: string; mrr: string; month: string; status: string };
export type PartnerData = { tiers: PartnerTier[]; brands: PartnerBrand[]; earners: PartnerEarner[] };

export type PortalData = {
  client: PortalClient;
  sites: PortalSite[];
  subscriptions: Subscription[];
  packages: HoursPackage[];
  onboarding: OnboardingProject[];
  learning: LearningStarter[];
  stats: PortalStat[];
  platform: PlatformFact[];
  activity: ActivityItem[];
  invoices: Invoice[];
  timeLog: TimeLogEntry[];
  tickets: Ticket[];
  team: TeamMember[];
  partner: PartnerData;
};

const MOCK_PORTAL: PortalData = {
  client: { name: "Hannah Whitfield", first: "Hannah", company: "Bloom & Co.", initials: "HW", tier: "Growth Partner" },
  sites: [
    { label: "Bloom & Co.", url: "bloomandco.com", platform: "WordPress + Woo", status: "Live", uptime: "99.98%", ssl: "Valid · auto-renew", plan: "Growth Retainer", visits: "18,420", shot: "/assets/feature-image-2.png", dot: "#1F9D57" },
    { label: "Bloom Store", url: "shop.bloomandco.com", platform: "Shopify Plus", status: "Live", uptime: "99.95%", ssl: "Valid · auto-renew", plan: "E-commerce Care", visits: "9,110", shot: "/assets/feature-image-4.png", dot: "#1F9D57" },
    { label: "Bloom Events", url: "events.bloomandco.com", platform: "WordPress", status: "Staging", uptime: "—", ssl: "Pending", plan: "Build in progress", visits: "—", shot: "/assets/feature-image-3.png", dot: "#C4610C" },
  ],
  subscriptions: [
    { name: "Growth Support Retainer", site: "bloomandco.com", price: "$490", cycle: "/mo", status: "Active", renews: "Apr 1, 2026", icon: "users" },
    { name: "Managed Hosting — Pro", site: "bloomandco.com", price: "$79", cycle: "/mo", status: "Active", renews: "Apr 1, 2026", icon: "server" },
    { name: "E-commerce Care Plan", site: "shop.bloomandco.com", price: "$320", cycle: "/mo", status: "Active", renews: "Apr 12, 2026", icon: "cart" },
    { name: "Toolbox — Business", site: "All sites", price: "$49", cycle: "/mo", status: "Trial ends soon", renews: "Mar 20, 2026", icon: "plug" },
  ],
  packages: [
    { name: "Design & Dev Hours", used: 6.5, total: 10, period: "This month", color: "#4F46E5" },
    { name: "SEO & Content Hours", used: 3, total: 4, period: "This month", color: "#6C63FF" },
    { name: "Priority Support Credits", used: 2, total: 8, period: "This quarter", color: "#3686F7" },
  ],
  onboarding: [
    {
      name: "Bloom Events",
      type: "New website build",
      stage: "Design stage",
      steps: [
        { title: "Discovery call", desc: "Goals, scope & strategy agreed", state: "done" },
        { title: "Package & sign-up", desc: "Growth retainer configured and signed", state: "done" },
        { title: "Content collection", desc: "Brand assets, copy, and photography", state: "waiting" },
        { title: "Design", desc: "On-brand, conversion-first layouts", state: "current" },
        { title: "Build", desc: "Development on the BlueWorx platform", state: "todo" },
        { title: "Review & launch", desc: "Your sign-off, then we go live", state: "todo" },
        { title: "Support & growth", desc: "Ongoing updates, SEO & reporting", state: "todo" },
      ],
      docs: [
        { name: "Logo files", hint: "SVG or high-res PNG", state: "received" },
        { name: "Domain access", hint: "Registrar login or DNS delegation", state: "received" },
        { name: "Brand guidelines", hint: "PDF — colors, fonts, tone", state: "pending" },
        { name: "Event photography", hint: "10–20 images, min 1600px wide", state: "pending" },
      ],
      milestone: { label: "Design presentation", date: "Mar 21, 2026", who: "Jess Moreau" },
    },
    {
      name: "Bloom Store refresh",
      type: "E-commerce update",
      stage: "Scope stage",
      steps: [
        { title: "Discovery call", desc: "Refresh goals & priorities agreed", state: "done" },
        { title: "Scope & quote", desc: "Update pack confirmed", state: "current" },
        { title: "Design", desc: "Product page & checkout refinements", state: "todo" },
        { title: "Build", desc: "Implementation & QA", state: "todo" },
        { title: "Launch", desc: "Staged rollout with zero downtime", state: "todo" },
      ],
      docs: [
        { name: "Updated price list", hint: "Spreadsheet or PDF", state: "received" },
        { name: "New product imagery", hint: "From your photographer", state: "pending" },
      ],
      milestone: { label: "Scope sign-off", date: "Mar 18, 2026", who: "Ross Palmer" },
    },
  ],
  learning: [
    { icon: "book", title: "Getting started with your BlueWorx site", meta: "6 guides · approx 25 min" },
    { icon: "palette", title: "Editing pages with Elementor", meta: "8 guides · video walkthroughs" },
    { icon: "cart", title: "Managing orders & subscriptions in SureCart", meta: "5 guides · approx 20 min" },
    { icon: "chart", title: "Reading your monthly performance report", meta: "3 guides · approx 10 min" },
  ],
  stats: [
    { icon: "server", value: "3", label: "Active websites" },
    { icon: "plug", value: "4", label: "Active subscriptions" },
    { icon: "clock", value: "9.5", label: "Hours left this month" },
    { icon: "cart", value: "$938", label: "Next invoice · Apr 1" },
  ],
  platform: [
    { icon: "server", label: "Primary host", value: "BlueWorx Cloud · EU-West" },
    { icon: "clock", label: "Last backup", value: "Today, 04:00 AM" },
    { icon: "chart", label: "CDN & caching", value: "Enabled · Cloudflare" },
    { icon: "doc", label: "PHP / runtime", value: "PHP 8.3 · MySQL 8" },
  ],
  activity: [
    { icon: "server", text: "Automated backup completed for bloomandco.com", time: "2 hours ago" },
    { icon: "chart", text: "February performance report is ready to view", time: "Yesterday" },
    { icon: "users", text: "Ross logged 2.5 hrs — homepage redesign", time: "2 days ago" },
    { icon: "plug", text: "Stripe integration updated on shop.bloomandco.com", time: "4 days ago" },
  ],
  invoices: [
    { id: "INV-2026-014", date: "Mar 1, 2026", amount: "$938.00", status: "Paid" },
    { id: "INV-2026-009", date: "Feb 1, 2026", amount: "$938.00", status: "Paid" },
    { id: "INV-2026-003", date: "Jan 1, 2026", amount: "$889.00", status: "Paid" },
    { id: "INV-2025-142", date: "Dec 1, 2025", amount: "$889.00", status: "Paid" },
  ],
  timeLog: [
    { date: "Mar 12", task: "Homepage hero redesign", who: "Ross P.", hrs: "2.5" },
    { date: "Mar 9", task: "Product schema & SEO fixes", who: "Jess M.", hrs: "1.5" },
    { date: "Mar 5", task: "Checkout bug — Woo plugin", who: "Ross P.", hrs: "1.0" },
    { date: "Mar 2", task: "Monthly content refresh", who: "Jess M.", hrs: "1.5" },
  ],
  tickets: [
    { title: "Add gift-card option at checkout", ref: "#BW-4821", time: "Updated 3h ago", status: "In progress" },
    { title: "Newsletter signup not syncing to CRM", ref: "#BW-4799", time: "Updated 2 days ago", status: "Awaiting you" },
    { title: "Update team photos on About page", ref: "#BW-4750", time: "Closed Mar 4", status: "Resolved" },
  ],
  team: [
    { initial: "R", name: "Ross Palmer", role: "Project Manager" },
    { initial: "J", name: "Jess Moreau", role: "Digital Designer" },
  ],
  partner: {
    tiers: [
      { key: "referral", name: "Referral", rate: 0.1, desc: "Send us a lead and earn on every invoice they pay.", req: "1+ referral" },
      { key: "certified", name: "Certified Partner", rate: 0.15, desc: "Trained on the BlueWorx platform, with priority hand-offs.", req: "5+ active clients" },
      { key: "agency", name: "Agency Partner", rate: 0.2, desc: "White-label delivery under your own brand for your clients.", req: "10+ active clients" },
    ],
    brands: [
      { name: "BlueWorx", mult: 1 },
      { name: "BlueWorx Commerce", mult: 1.3 },
      { name: "BabyBlue Digital", mult: 1.15 },
    ],
    earners: [
      { name: "Harbour & Co Creative", type: "Agency Partner", refs: "14", mrr: "$6,420", month: "$1,284", status: "Paid" },
      { name: "Nina Okafor", type: "Certified Partner", refs: "6", mrr: "$2,890", month: "$433", status: "Paid" },
      { name: "Studio Meridian", type: "Certified Partner", refs: "5", mrr: "$2,140", month: "$321", status: "Pending" },
      { name: "Westgate Accounting", type: "Referral", refs: "3", mrr: "$1,110", month: "$111", status: "Pending" },
      { name: "Bloom & Co", type: "Referral", refs: "2", mrr: "$640", month: "$64", status: "Paid" },
    ],
  },
};

/**
 * Fetch the portal payload for the authenticated customer. Per-user data, so it
 * is never cached. Until the plugin is live, returns the demo client's data.
 */
export async function getPortalData(): Promise<PortalData> {
  if (useMockData) return MOCK_PORTAL;
  const res = await fetch(`${config.wpApiUrl}/portal/me`, {
    headers: config.wpApiToken ? { Authorization: `Bearer ${config.wpApiToken}` } : undefined,
    cache: "no-store",
  });
  if (!res.ok) throw new Error(`BlueWorx portal API failed: ${res.status} ${res.statusText}`);
  return res.json() as Promise<PortalData>;
}
