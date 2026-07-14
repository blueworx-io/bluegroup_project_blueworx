// Front-end mapping of SureCart's raw proxied JSON (blueworx/v1/surecart/me/*)
// into the Portal's Subscription/Invoice shapes. This is the SINGLE place that
// holds SureCart shape knowledge — the raw field names are a documented guess
// (no live SureCart API in this repo) and are corrected here against live data.
// Everything is read defensively so a missing field degrades to a default.
import { api } from "@/lib/wp-client";
import type { Subscription, Invoice } from "@/lib/api/portal";

type Raw = Record<string, unknown>;
const obj = (v: unknown): Raw => (v && typeof v === "object" ? (v as Raw) : {});
const str = (v: unknown): string | undefined => (typeof v === "string" ? v : undefined);
const num = (v: unknown): number | undefined => (typeof v === "number" ? v : undefined);

/** SureCart amounts are in the currency's minor unit (cents). */
function money(amount: unknown, currency: unknown): string {
  const cents = num(amount) ?? 0;
  const cur = (str(currency) ?? "usd").toUpperCase();
  const symbol = cur === "USD" ? "$" : cur === "GBP" ? "£" : cur === "EUR" ? "€" : "";
  const whole = cents / 100;
  const body = whole.toFixed(2);
  return `${symbol}${body}`;
}

function cycle(interval: unknown): string {
  const i = str(interval);
  return i === "year" || i === "yearly" || i === "annual" ? "/yr" : "/mo";
}

/** Accepts unix seconds (number) or an ISO string; formats "Mon D, YYYY" or "". */
function formatDate(v: unknown): string {
  let d: Date | null = null;
  const n = num(v);
  if (n !== undefined) d = new Date(n * 1000);
  else {
    const s = str(v);
    if (s) { const t = Date.parse(s); if (!Number.isNaN(t)) d = new Date(t); }
  }
  return d ? d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "";
}

const SUB_STATUS: Record<string, string> = {
  active: "Active", trialing: "Trial ends soon", past_due: "Past due",
  canceled: "Cancelled", cancelled: "Cancelled",
};
const INV_STATUS: Record<string, string> = {
  paid: "Paid", open: "Due", uncollectible: "Overdue", void: "Void",
};

export function mapSubscription(raw: unknown): Subscription {
  const r = obj(raw);
  const price = obj(r.price);
  const product = obj(r.product);
  const metadata = obj(r.metadata);
  let priceStr = money(price.amount ?? r.amount, price.currency ?? r.currency);
  // Strip ".00" from prices for display (e.g., "$490.00" → "$490")
  priceStr = priceStr.replace(/\.00$/, '');
  return {
    name: str(product.name) ?? str(price.name) ?? str(r.name) ?? "Subscription",
    site: str(metadata.site) ?? "All sites",
    price: priceStr,
    cycle: cycle(price.recurring_interval ?? price.interval),
    status: SUB_STATUS[str(r.status) ?? ""] ?? "Active",
    renews: formatDate(r.current_period_end_at ?? r.current_period_end),
    icon: "plug",
  };
}

export function mapInvoice(raw: unknown): Invoice {
  const r = obj(raw);
  const url = str(r.url) ?? str(r.hosted_invoice_url);
  return {
    id: str(r.number) ?? str(r.id) ?? "—",
    date: formatDate(r.created_at ?? r.date),
    amount: money(r.total ?? r.amount, r.currency),
    status: INV_STATUS[str(r.status) ?? ""] ?? "Paid",
    ...(url ? { url } : {}),
  };
}

type ScList = { data?: unknown[] };

export async function getSubscriptions(): Promise<Subscription[]> {
  const res = await api("/surecart/me/subscriptions");
  if (!res.ok) throw new Error(`surecart subscriptions failed: ${res.status}`);
  const body = (await res.json()) as ScList;
  return (body.data ?? []).map(mapSubscription);
}

export async function getInvoices(): Promise<Invoice[]> {
  const res = await api("/surecart/me/invoices");
  if (!res.ok) throw new Error(`surecart invoices failed: ${res.status}`);
  const body = (await res.json()) as ScList;
  return (body.data ?? []).map(mapInvoice);
}
