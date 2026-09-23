=== BlueWorx | Marketing Site ===
Contributors:      blueworx
Tags:              marketing, landing page, site, front-end
Requires at least: 5.0
Tested up to:      6.9
Requires PHP:      8.0
Stable tag:        1.23.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

The BlueWorx public marketing site, rendered by the plugin itself so it is identical wherever it is hosted.

== Description ==

BlueWorx | Marketing Site is the public-facing blueworx.io marketing site packaged as a self-contained WordPress plugin. The plugin renders every page itself — home, ClubHouse, hosting, support, portfolio, about, contact, the AI page and the journal — rather than relying on a theme, so the site looks the same on any host.

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
At the plugin's own sign-in page (/login) by default, which sends the client on to their dashboard. Change it under Settings > BlueWorx Site — a full URL or a path such as /customer-dashboard. All three nav links follow it. Developers can also hook the `blueworx_client_login_url` filter.

= How do I add a contact form? =
Paste the form shortcode into Settings > BlueWorx Site. The Contact page renders it, and shows a placeholder while it is empty. Developers can also hook the `blueworx_contact_form_shortcode` filter.

= How do I show real SureCart prices? =
Copy each plan's price ID out of SureCart and paste it into the matching box under Settings > BlueWorx Site, one for monthly and one for annual. The boxes there now list every Support package plus Hosting and ClubHouse, and each one then shows SureCart's price with its button going to checkout. Leave a plan blank to keep the price built into the plugin.

== Changelog ==

= 1.23.0 =
* Commission and Quote Builder now appear on the BlueWorx Labs customer dashboard, for salespeople and administrators only. This plugin's own /dashboard and its other sections are gone. Its pages are labelled "BlueWorx page" in the Pages list and can only be viewed or edited there, not trashed.

= 1.22.1 =
* Signing in now always lands on the dashboard, administrators included, instead of sending admins to wp-admin.

= 1.22.0 =
* Adds a quote builder: pick hosting or ClubHouse, then ongoing support or a build, and it sizes the work in hours and quotes the package that covers it, with the hours left over.
* Each stage shows its working, so you can see what one page costs to design and to build.
* A custom ClubHouse is costed at half a from-scratch build, since the platform is already there, and its pages build in 3 hours rather than 10.
* It appears in the Sales section and on the public Support page. Settings > BlueWorx Site turns the public one back to the plain hours slider.
* Sales staff also see what the quote pays them, and at what rate for each part; visitors never do.
* Fixes other plugins’ script modules loading on the marketing pages, which is what put SureCart’s code on pages that never asked for it.

= 1.21.0 =
* Salespeople get a commission calculator in the client area: build a sale and see what it pays, per product and in total, for the first year.
* Adds a "BlueWorx: Sales Staff" role. Only they and administrators can see the Sales section, or reach its address.

= 1.20.0 =
* Signing in now uses SureCart's own form on /login, which also handles a forgotten password. Administrators land in wp-admin, clients on their dashboard.
* The separate sign-up and password-reset pages are gone; /register and /reset-password redirect to /login.

= 1.19.0 =
* The Contact page's illustration is replaced by the animated Support Flow panel from the design: four ticket steps, a connector that fills between them and the support hours available. Hidden on phones.
* The Contact FAQ now answers questions about getting in touch rather than billing.

= 1.18.0 =
* The Contact page has its own enquiry form: name, company, email, phone, topic, budget bands and a message, sent to sales@blueworx.io. It works without JavaScript and shows a thank-you panel once sent.
* The three contact cards now point at the client dashboard, the portfolio and sales@blueworx.io.
* The hero pills on centred pages are centred; the footer reads "A BlueGroup Company".

= 1.17.0 =
* Fixed the Support page not loading on the live site: a media file held the /support address, so the page was never created. The installer now moves the file's slug aside.
* Added rand, Australian dollars and dirhams to the currency switcher.
* Work is now Portfolio, listing eleven live client sites with screenshots; /work redirects there.
* The Toolbox is gone: its pages, portal tab and admin screen are removed and every mention now points at ClubHouse; the old addresses redirect there.
* The home page introduces ClubHouse where the Toolbox grid was, and its second service card is Managed Hosting.
* ClubHouse shows its £499 one-off setup fee. Support package hours match the catalogue.
* One reviews section, three reviews, the same on every page. ClubHouse module icons sit inside their tiles again.

= 1.16.0 =
* Added three product pages: ClubHouse (the club website platform), Hosting (managed WordPress hosting) and Integrated Support (nine support packages with a slider and a comparison table).
* The menu is now Home, Hosting, Support, Work and ClubHouse, with Contact as the header button. AI Powered, About and the Journal are in the footer.
* Added a currency switcher so prices can show in GBP, EUR or USD, at the European Central Bank's daily rates.
* Pricing and Services now redirect to Support; the old pages are moved to the bin rather than deleted.
* Fixed FAQ answers collapsing to nothing when opened, and the "Popular" plan badge being white on white.

= 1.15.0 =
* Client websites and referrals are now recorded in wp-admin (two new menu items) and shown to the client in their portal. The Partner tab only appears for people who actually have referrals.
* Uptime and traffic figures are still not shown per site — nothing measures them yet, and the page says so rather than showing a number nobody took.

= 1.14.0 =
* The client portal gains three sections: your details (name, company, email and password), support (a request form that emails us, plus who to contact urgently), and your toolbox (the twelve tools, marked included or not by whether you have an active plan).
* The "New request" button in the portal now opens the portal's own form instead of sending you out to the public contact page.

= 1.13.0 =
* Adds a journal at /blog listing your published posts, with a featured article and topic filters, plus a full page for reading an article — contents list, share buttons and related reading. Links to it are in the menu and footer.
* The "page not found" screen is the new design: the address you asked for, a way home and a way back.

= 1.12.10 =
* Fixes the critical error in 1.12.9. Pages missing after an update were created too early in the WordPress start-up, before WordPress was ready to build their addresses.

= 1.12.9 =
* Marketing pages can now be cached and served instantly to repeat visitors instead of being rebuilt for every one. The client area and the sign-in pages are explicitly never cached.

= 1.12.8 =
* Pages no longer jump about while they load, images are smaller and load in a sensible order, and 15KB of WordPress styling for things this site does not use is gone from every page.

= 1.12.7 =
* Every page now has a title and a description written for it, instead of a nav label and a sentence cut off halfway through. Twenty pages in all, including the twelve tool pages.

= 1.12.6 =
* Sign in, sign up, reset a password and the client dashboard are no longer offered to search engines, and no longer contradict themselves with two conflicting instructions on one page. Three leftover addresses now redirect instead of showing an error.

= 1.12.5 =
* A bad address now shows a BlueWorx page with the menu, the footer and links to the main pages, instead of a blank "Not Found" screen with no way back.

= 1.12.4 =
* The footer no longer shows links that do nothing. Blog, Resources, Careers, the three social icons and the newsletter box are gone until there is something real behind them.

= 1.12.3 =
* The "Skip to the content" link no longer shows on every page, and it now takes you to the content. It is invisible until you reach it with the keyboard.

= 1.12.2 =
* Tests now cover updating an existing site rather than only a brand-new one, and the live site is checked automatically after each release. No change to the site itself.

= 1.12.1 =
* Fixed every Toolbox tool page being a dead link. The pages are now created — and repaired if they have drifted — on the first visit after any update, instead of only when the plugin is activated.

= 1.12.0 =
* The Toolbox tools now have their own item in the admin menu, listing every tool with a link to view it, and they no longer clutter the Pages list. Nothing changes on the site itself.

= 1.11.1 =
* Fixed pricing not connecting to SureCart. The settings screen silently discarded every price ID it was given, and the plan buttons sent SureCart a checkout with nothing in it. Both are fixed, and an ID that is refused now says so.

= 1.11.0 =
* The client area is restyled to the approved portal design: a dark sidebar, a header strip, and card and table styles to match. Sections the design shows but we have no data for — websites, support tickets, uptime figures — are deliberately left out rather than filled with invented numbers.

= 1.10.0 =
* The plan buttons now use SureCart’s own checkout page rather than assuming it lives at /checkout. Added a short setup guide covering everything that has to be switched on for the client area to work.

= 1.9.2 =
* Fixed the SureCart integration against the real SureCart plugin: plan names now resolve, amounts are formatted by SureCart rather than assumed to be cents, and a failed request is reported as a failure instead of an empty account. Invoices offer a payment link for unpaid ones rather than a PDF, which SureCart does not provide.

= 1.9.1 =
* The accessibility and mobile checks that already ran on every marketing page now run on the dashboard and sign-in pages too. No change to the site.

= 1.9.0 =
* Added: clients now sign in, create an account and reset a password on the site itself, instead of on pages belonging to a plugin we are removing. The Client Login link in the navigation points there by default. New accounts follow the WordPress "Anyone can register" setting.

= 1.8.0 =
* Added: the dashboard's subscriptions, invoices and orders sections now show the client's real records from SureCart, including invoice PDFs. A client only ever sees their own. If SureCart cannot be reached the page says so rather than showing an empty account.

= 1.7.0 =
* Added: a client dashboard at /dashboard, with sections for subscriptions, invoices and orders. Only signed-in clients can reach it, and it is never indexed by search engines. The sections show a plain "nothing here yet" message until each is connected to SureCart. Existing sites get the new pages automatically on update.

= 1.6.0 =
* Added: plan prices on the Pricing page can now be read from SureCart, and the "Get started" buttons can go straight to checkout. Enter a SureCart price ID per plan under Settings > BlueWorx Site. Plans left blank keep the price built into the plugin and the contact-form button, and the page falls back to that whenever SureCart cannot be reached.

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
