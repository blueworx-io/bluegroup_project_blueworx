=== BlueWorx | Marketing Site ===
Contributors:      blueworx
Tags:              marketing, landing page, site, front-end
Requires at least: 5.0
Tested up to:      6.9
Requires PHP:      8.0
Stable tag:        1.5.4
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

The BlueWorx public marketing site, rendered by the plugin itself so it is identical wherever it is hosted.

== Description ==

BlueWorx | Marketing Site is the public-facing blueworx.io marketing site packaged as a self-contained WordPress plugin. The plugin renders every page itself — home, services, work, pricing, about, contact, the AI page, and the toolbox — rather than relying on a theme, so the site looks the same on any host.

It is fully self-contained: no theme is required, and it has no dependency on any other plugin. On plugin-owned pages it loads only its own styles and scripts and steps aside from the active theme's stylesheet, so it never restyles content it does not own.

If the BlueWorx enhancement plugin happens to be active alongside it, the two integrate automatically (the site's pages are exempted from the enhancement plugin's site protection), but neither plugin requires the other.

== Installation ==

1. Upload the `bluegroup-project-blueworx` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress. On activation it installs its pages and sets the front page.
3. Visit the site's front page to see the rendered marketing site.

On deactivation the front page is handed back to whatever it pointed at before the plugin took it over.

== Frequently Asked Questions ==

= Does this need a specific theme? =
No. The plugin renders its own pages and does not rely on a theme.

= Does it require the BlueWorx enhancement plugin? =
No. It is fully standalone. If the enhancement plugin is also active, the two integrate automatically, but it is not required.

= Where does the Client Login link point? =
At /portal by default. Change it under Settings > BlueWorx Site — a full URL or a path such as /dashboard. All three nav links follow it. Developers can also hook the `blueworx_client_login_url` filter.

= How do I add a contact form? =
Paste the form shortcode into Settings > BlueWorx Site. The Contact page renders it, and shows a placeholder while it is empty. Developers can also hook the `blueworx_contact_form_shortcode` filter.

== Changelog ==

= 1.5.4 =
* Fixed: the plugin treated any page whose slug matched one of its own as its own, and stripped that page's theme and page-builder CSS — a site's existing "home", "about" or "pricing" page lost all its styling. A page now counts as the plugin's only if the plugin created it. Existing sites upgrade automatically and keep their pages.

= 1.5.3 =
* Documentation only: corrected the plugin replacement plan (ACF is unused and SureCart does have products) and fixed the zip naming rule to match the foundation. No change to the plugin.

= 1.5.2 =
* Documentation only: added a written proposal for the remaining plugin replacements and the client area. No change to the plugin.

= 1.5.1 =
* Fixed heading order across every marketing page: card headings that jumped a level now follow the section above them, and two sections that had no heading at all are named for screen readers. Nothing looks different.

= 1.5.0 =
* Added a settings screen at Settings > BlueWorx Site for the contact form shortcode and the Client Login link, so neither needs WP-CLI or a code change.

= 1.4.0 =
* The nav's Client Login link is now configurable in one place rather than hardcoded in three. It still points at /portal; set the `blueworx_client_login_url` option to repoint it when the new dashboard exists.

= 1.3.0 =
* Marketing pages no longer load styles and scripts belonging to plugins that do not render them. The About page drops from around 126KB to 55KB and makes six fewer script requests. Pages the plugin does not render are unaffected, and the Contact page keeps its form plugin's assets when a form is configured.

= 1.2.0 =
* Retired pages now redirect rather than 404: /shop goes to Pricing, /about-us to About, /features to Toolbox, and /test-page to the home page. Tracking parameters are carried across.

= 1.0.0 =
* Initial release: the BlueWorx marketing site as a self-contained, always-on WordPress plugin, decoupled from the enhancement plugin.
