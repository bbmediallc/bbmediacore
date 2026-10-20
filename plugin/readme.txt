=== BB Media Core ===
Contributors: bandbmedia, austinbollinger
Tags: core functionality, admin, custom dashboard, support form, white label
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 5.6
Stable tag: 2.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Core functionality for B&B Media websites and the agencies B&B Media white-labels for: a custom dashboard, support form, and branded login.

== Description ==

One plugin serves every company B&B Media builds sites for. Each site is set to one company (B&B Media, KNC Marketing, Pace Marketing, or one added later), and that company's branding is used everywhere:

* A branded admin footer.
* A branded login screen.
* A custom dashboard replacing the default WordPress dashboard.
* A support form that emails the company's support team.
* The company's three latest blog posts on the dashboard ([latest_blog_posts]).
* Optional Tutorials page of video links (e.g. Loom), turned on per site.
* [bbyear] shortcode for the current year.

Only the support@bandbmedia.com account can choose the company or change brand settings (Dashboard > Brand Settings).

== Installation ==

1. Upload bbmediacore.zip under Plugins > Add New > Upload Plugin, or let an existing install update itself.
2. Activate it while logged in as support@bandbmedia.com.
3. Choose the company on the screen that opens.

Sites updating from BB Media Core 1.6.x are set to B&B Media automatically.

== Changelog ==

= 2.0.5 =
* Multisite: when network-activated, one company choice applies to every site.
* Multisite: the Network Admin dashboard and its Dashboard menu work again.

= 2.0.4 =
* The Updates screen no longer says "Not tested" with the current WordPress version.
* The plugin is always named BB Media Core, whichever company is active.

= 2.0.3 =
* Fixed: other plugins' screens at /wp-admin/?... (WooCommerce email preview, Gravity Forms column picker) no longer redirect to the dashboard.
* WooCommerce customers no longer get the custom dashboard or support form.
* Company settings files can no longer be read in a browser.
* Tested up to WordPress 7.1.

= 2.0.2 =
* Update screens always show the B&B Media icon.
* Only support@bandbmedia.com sees this plugin on the Plugins page and its available updates.

= 2.0.1 =
* The Plugins page always shows "Enable auto-updates" for this plugin.
* Brand Settings: the company picker's radio button sits next to the company name.
* Brand Settings: the company list shows each company's support ticket recipients.

= 2.0.0 =
* Version 2.0: the all-in-one release (one plugin for every company, Brand Settings, Tutorials).
* KNC and Pace logos now show in Brand Settings.
* Tutorial Videos only appears under Dashboard while tutorials are enabled.

= 1.7.1 =
* Brand Settings and Tutorial Videos are now under the Dashboard menu.

= 1.7.0 =
* One plugin for every company, chosen per site on first activation.
* New Settings > Brand Settings panel to edit or add companies.
* Optional Tutorials page, replacing the ACF-based version (videos are imported automatically).
* Fixed update checks: no longer hard-coded to 1.6.5, and GitHub is checked at most hourly.
* Support form accepts logged-in users only.
