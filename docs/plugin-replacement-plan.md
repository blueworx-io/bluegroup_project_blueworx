# Replacing the remaining plugins, and building the client area

**Status: a proposal, not approved work.** Covers issues #30–#43 and #53–#55.
Nothing here has been built. It exists so the size and the order of the work are
agreed before anyone starts, because most of it is irreversible on a live
commercial site.

Written 3 August 2026, from a read-only audit of blueworx.io. Every count below
was read off the live site rather than assumed.

## What is actually on the site

Nineteen active plugins. The five under discussion, and what each is really
doing today:

| Plugin | What it still does | Real size of removing it |
|---|---|---|
| **Code Snippets** (#32) | **Nothing.** Both snippets on the site are switched off. | Near zero. Confirm the two disabled snippets are dead, then deactivate. |
| **Advanced Custom Fields Pro** (#31) | Unknown — ACF does not expose field groups over REST, so this is the one thing the audit could not see. | Unknown until someone lists the field groups in wp-admin. Could be nothing; could be the largest item here. |
| **UiCore** (#35) | Three plugins (Framework, Elements, Animate), two theme-builder templates, and a global script loaded on every page. | Medium. The global script is already stopped on marketing pages by 1.3.0. |
| **Elementor + Pro** (#33) | After the four page deletions land, only `/portal-login` and `/portal-register`, plus three library templates. | **Much smaller than it looks** — see below. |
| **SureDash** (#34) | The whole client area: `/portal`, `/customer-dashboard`, four portal spaces. | Large. This is the client dashboard programme, not a plugin swap. |

**The Elementor finding is the useful one.** Elementor looks like the biggest
item on the list, and the issue calls it "the largest thing to remove". On the
live site today it builds exactly four pages: `/features`, `/about-us`,
`/portal-login` and `/portal-register`. The first two are already being deleted
(#24, #23, shipped in 1.2.0). The other two disappear when login and
registration move into the plugin (#43). So Elementor is not a project of its
own — it is the last step of the client-area work, and it should be scheduled
there rather than treated as a separate migration.

**SureCart has no products.** Zero. This matters because #41 (link Pricing to
SureCart) and #42 (checkout end to end) both assume there is something to sell.
Neither can start until somebody creates the plans in SureCart, and that is a
commercial decision about names and prices, not a build task.

## The order, and why

The dependencies run one way, so the order is not really a matter of taste:

1. **Audit ACF** — the only genuine unknown. One session in wp-admin listing
   field groups and where they are used. Everything else can be estimated; this
   cannot. Do it first so the plan stops having a hole in it.
2. **Retire Code Snippets** (#32) — confirm the two disabled snippets are dead,
   deactivate. Cheap, and it removes a plugin that could put site behaviour back
   into the database at any time.
3. **Create the SureCart products** — a commercial decision, needed before #41
   and #42, and it blocks nothing else. Start it early so it is not the thing
   everyone waits on.
4. **Link Pricing to SureCart** (#41) — prices stop being hard-coded and start
   matching what is actually sold. Then **checkout** (#42).
5. **Build the client dashboard** (#37), then its sections: **subscriptions**
   (#38), **invoices** (#39), **orders** (#40). Each reads live from SureCart.
   This is the substantial build.
6. **Login and registration in the plugin** (#43). Needs the dashboard to exist
   to land in.
7. **Repoint the Client Login link** (#28) — now a setting, not a code change.
8. **Retire SureDash** (#34), then **Elementor and Elementor Pro** (#33), then
   **UiCore** (#35). Removal only after the replacement is live and tested,
   never before.
9. **Apply the new visuals** (#30) — deliberately last of the build work, so the
   design is applied once to finished pages rather than twice.
10. **Test the client area** (#53, #54, #55) — these three cannot be done at all
    until the pages they cover exist. They are the acceptance pass on steps 4–6.

## What is not in scope here, and should be

Three things the issues do not mention that will otherwise be discovered late:

- **Existing customers.** SureDash currently holds the client portal. If anyone
  is logged in and using it, moving to a new dashboard is a migration with a
  cutover, not a switch. Nobody has established whether there are live users.
- **Payment and login are Recipe Book gaps.** The foundation's recipe book has
  "Login", "Payment" and "Contact form" all marked *not written yet*. Per the
  foundation's own rule, an approach should be proposed and approved rather than
  invented here — and then written back as the recipe, so the next project does
  not relitigate it.
- **A dashboard holds personal and payment data.** Everything before this point
  has been marketing pages, where the worst case is a page looking wrong. From
  step 5 onward the worst case is customer data exposure. That deserves a
  security review as a gate, not as a final check.

## Honest estimate

Steps 1–3 are days. Steps 4–6 are the real programme: a payment path and an
authenticated client area, built against a third-party API, on a live site with
real customers. Weeks, not days, and the largest single risk is step 5.

Steps 7–10 are only cheap *because* 4–6 were done properly.

## Recommendation

Approve steps 1–3 now: they are cheap, they are reversible, and step 1 removes
the only real unknown in this document. Bring steps 4–6 back as their own
proposal once the ACF audit is in and the SureCart products exist — with the
customer-migration question answered, because it changes the shape of the build.
