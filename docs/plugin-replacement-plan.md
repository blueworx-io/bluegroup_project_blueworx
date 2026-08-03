# Replacing the remaining plugins, and building the client area

**Status: mostly built, 3 August 2026.** Covers issues #30–#43 and #53–#55.

Steps 2 to 5 below are done: the Pricing page reads SureCart, the client
dashboard and its three sections exist, and login, registration and password
reset are in the plugin. What remains is configuration and the removals —
see `client-area-setup.md` for the settings, and steps 6 to 8 here for what
comes after them.

The customer-migration question this document raised is closed: there are no
live clients, confirmed 3 August 2026, so the new dashboard replaces SureDash
outright rather than needing a cutover.

The rest of this document is the original proposal, kept because the reasoning
about order still holds.

Written 3 August 2026, from a read-only audit of blueworx.io. Every count below
was read off the live site rather than assumed.

## What is actually on the site

Nineteen active plugins. The five under discussion, and what each is really
doing today:

| Plugin | What it still does | Real size of removing it |
|---|---|---|
| **Code Snippets** (#32) | **Nothing.** Both snippets on the site are switched off. | Near zero. Confirm the two disabled snippets are dead, then deactivate. |
| **Advanced Custom Fields Pro** (#31) | Nothing the site depends on — confirmed by Luke, 3 Aug 2026. | Small. Deactivate, and check nothing on a page goes blank. |
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

**The SureCart products exist.** An earlier draft of this document said there
were none. That was wrong: it counted the `sc_product` post type in WordPress,
which is empty because SureCart keeps products in its own cloud rather than as
WordPress posts. The lesson for #41 and #42 is worth keeping — anything that
reads product or price data has to go through SureCart's API, and a local
WordPress query will quietly return nothing.

## The order, and why

The dependencies run one way, so the order is not really a matter of taste:

1. **Retire ACF Pro** (#31) and **Code Snippets** (#32) — neither is holding
   anything up. ACF is confirmed unused, and both Code Snippets entries are
   switched off. Deactivate, check nothing goes blank, done. Cheap, reversible,
   and it removes two plugins that could put site behaviour back into the
   database at any time.
2. **Link Pricing to SureCart** (#41) — prices stop being hard-coded and start
   matching what is actually sold. Then **checkout** (#42). Both read through
   SureCart's API, not through WordPress.
3. **Build the client dashboard** (#37), then its sections: **subscriptions**
   (#38), **invoices** (#39), **orders** (#40). Each reads live from SureCart.
   This is the substantial build.
4. **Login and registration in the plugin** (#43). Needs the dashboard to exist
   to land in.
5. **Repoint the Client Login link** (#28) — now a setting, not a code change.
6. **Retire SureDash** (#34), then **Elementor and Elementor Pro** (#33), then
   **UiCore** (#35). Removal only after the replacement is live and tested,
   never before.
7. **Apply the new visuals** (#30) — deliberately last of the build work, so the
   design is applied once to finished pages rather than twice.
8. **Test the client area** (#53, #54, #55) — these three cannot be done at all
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
- **A dashboard holds personal and payment data.** Everything up to step 2 has
  been marketing pages, where the worst case is a page looking wrong. From step
  2 onward the worst case is customer data exposure. That deserves a security
  review as a gate, not as a final check.

## Honest estimate

Step 1 is a day. Steps 2–4 are the real programme: a payment path and an
authenticated client area, built against a third-party API, on a live site with
real customers. Weeks, not days, and the largest single risk is step 3.

Steps 5–8 are only cheap *because* 2–4 were done properly.

## Recommendation

Do step 1 now — it is a day's work, reversible, and it takes two plugins off the
site. Bring steps 2–4 back as their own proposal, with the customer-migration
question answered first, because the answer changes the shape of the build.
