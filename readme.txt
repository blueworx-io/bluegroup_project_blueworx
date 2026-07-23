=== BlueWorx | Marketing Site ===
Contributors:      blueworx
Tags:              marketing, landing page, site, front-end
Requires at least: 5.0
Tested up to:      6.9
Requires PHP:      8.0
Stable tag:        1.1.3
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

= How do I add a contact form? =
Set the `blueworx_contact_form_shortcode` option (or hook the filter of the same name) to any form shortcode; the contact page renders it.

== Changelog ==

= 1.0.0 =
* Initial release: the BlueWorx marketing site as a self-contained, always-on WordPress plugin, decoupled from the enhancement plugin.
