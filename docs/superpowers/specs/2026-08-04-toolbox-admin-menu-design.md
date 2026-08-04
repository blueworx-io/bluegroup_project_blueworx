# Toolbox admin menu

## The problem

The twelve Toolbox tools are defined in `blueworx_content_tools()` in
`includes/public/content.php`. The plugin creates a WordPress page for each one,
nested under Toolbox, so `/toolbox/sureforms` and friends resolve and render
through `templates/pages/single-tool.php`.

Those twelve pages sit in the wp-admin Pages list alongside Home, Pricing,
Contact and the client area. They cannot usefully be edited there — the page
body is empty and every word on the rendered page comes from the code registry —
so they are twelve rows of noise in the one screen where somebody goes looking
for a page they can actually change.

## What this builds

A **Toolbox** item in the wp-admin sidebar, and the removal of those twelve
pages from the Pages list.

The Toolbox screen lists the tools from the code registry: name, category, slug,
and a link to view each one on the site. It is read-only, and says so — a tool
is added or reworded in the plugin, and ships with a release.

## What this deliberately is not

Not a custom post type. Tool content stays in `content.php`, in git, versioned
and reviewed with everything else, and keeps shipping with the plugin. Moving it
into the database would make it editable without a release, at the cost of it no
longer being versioned, reviewed, or restored by reinstalling the plugin. That
trade was considered and declined.

Nothing about the public site changes. `/toolbox` and `/toolbox/<slug>` render
exactly as they do now.

## Components

**`includes/admin/toolbox-menu.php`** — the whole feature, loaded from the main
plugin file next to `settings.php`, inside the existing `is_admin()` guard.

- `blueworx_toolbox_menu_register()` on `admin_menu` — a top-level menu page.
- `blueworx_toolbox_menu_render()` — the listing screen. Reads
  `blueworx_content_tools()` and renders a `wp-list-table`-styled table. No
  editing, no form, no nonce, because it writes nothing.
- `blueworx_toolbox_menu_hide_tool_pages()` on `pre_get_posts` — excludes the
  tool pages from the Pages list screen only.

**Which pages get hidden.** The tool pages, and only those. They are identified
by intersecting two facts the plugin already keeps: the page IDs mapped in the
`blueworx_public_page_ids` option under a `toolbox/<slug>` key, and the
ownership stamp `BLUEWORX_PUBLIC_PAGE_META`. A page that is not stamped is not
ours and is never hidden, which matters on a site where somebody has made their
own page at a colliding slug — the same rule `blueworx_public_install_pages()`
already applies when it refuses to adopt a page it did not create.

Home, Pricing, Contact and the client-area pages stay in the Pages list. Only
the Toolbox children leave it.

**Where the hiding applies.** `edit.php?post_type=page` only, and only in
wp-admin, and only for the main query. Menu building, search, the block editor's
page lookups, `wp_list_pages()` and the front end all still see the pages —
hiding them there would break navigation and permalinks rather than tidy a list.

## Error handling

The registry is the only input and it is a literal array in the plugin, so there
is no failure path to handle: no I/O, no API, no user input, nothing stored. An
empty registry renders an empty table with a "no tools" line rather than a
broken screen.

If a tool has no page yet — the registry lists it but activation has not run
since — its row shows the tool with no view link, rather than a link to a 404.

## Testing

Playwright, against the local WordPress harness, in
`tests/toolbox-admin-menu.spec.js`:

1. The Toolbox menu item exists in the sidebar and its screen loads.
2. The screen lists all twelve tools, and each view link resolves to a real tool
   page rather than a 404.
3. The Pages list does not contain the tool pages.
4. The Pages list still contains Home, Pricing and Contact — the guard against a
   hiding rule that is too broad.
5. The tool pages still render on the front end, and `/toolbox` still links to
   them — the guard against hiding leaking out of the admin list.
