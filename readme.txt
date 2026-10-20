=== Calendar+ (An Accessible Events Calendar) ===
Contributors: campuspress
Tags: events, calendar, accessibility, blocks
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 2.2.27
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accessible events calendar with TEC import, media migration, and editable event feed, grid and list blocks.

== Description ==
Calendar Plus provides events, Locations, categories, tags, accessible calendars and event display blocks. Import The Events Calendar WordPress XML exports, preview mappings, and transfer available media. Events Showcase provides editable grid and list displays with search and filters. Optional single-event and showcase page templates require a block theme.

External services are optional. Selecting a remote media import contacts the URLs in the uploaded export to retrieve files. Configuring a Google Maps API key enables Google Maps on locations. Configuring Google Calendar or an iCalendar feed contacts the selected service to import or synchronize events. No analytics or automatic GitHub update checks are enabled.
Google terms: https://policies.google.com/terms
Google privacy: https://policies.google.com/privacy
The source site operator controls any selected XML media URLs or iCalendar feed. Contact that operator for its policies. Check CampusPress deployment policy before enabling an external integration.

== Installation ==
1. Install and activate Calendar Plus using the WordPress Plugins screen.
2. Manage Events and Locations from the Events menu.
3. Use Events > Importers for TEC XML exports; review the preview before importing.
4. Insert Events Showcase or Event Feed from the block inserter. Use Event Display to opt into block-theme templates.

== Frequently Asked Questions ==
= Does an event-only WordPress export include every dependency? =
No. Supply the separate Venues and Organisers exports as well, or use the companion TEC Migration Exporter to include terms and related media in one WXR file. Media files must still be accessible.
= Can every TEC Pro feature be migrated? =
No. Unsupported source details are retained for review. Verify complex recurrence, tickets and third-party integrations separately.
= Is a database backup required before migration? =
Keep a backup of the database and uploads, test on staging, then review the preview and import results.

== Changelog ==
= 2.2.27 =
* Scope native scheduling tables and cached settings correctly during multisite blog switching.
* Reject foreign-origin non-public media IPs and validate each bounded redirect before requesting it.
* Recover missing Gutenberg Image attachment IDs after failed media transfers without replacing existing IDs.
* Keep native role objects and stored capabilities in sync on first activation, including new multisite sites.
* Defer admin/widget/module translations and classic theme detection to the appropriate WordPress lifecycle hooks.
* Preserve WordPress HTTP timeouts and declare native formatter state for PHP 8.2+ compatibility.
= 2.2.26 =
* Whole-plugin security and Plugin Check remediation; validated saves, escaped output, prepared and cached native schedule queries.
* Correct activation/deactivation hooks, calendar edit persistence and REST date ownership checks.
* Remove GitHub automatic updater and default debug logging; stream safe CSV exports.
= 2.2.25 =
* Wrap the selected Showcase Event Field label in the editor List View.
= 2.2.24 =
* Show the selected field purpose in Showcase Event Field List View labels.
= 2.2.23 =
* Introduce editable Events Showcase grid/list blocks and optional showcase page template.
= 2.2.22 =
* TEC migration mapping, preview, media transfer and rerun improvements; Event Feed, responsive images and optional event display template.

== Upgrade Notice ==
= 2.2.26 =
Review on staging before deployment. Existing schedules are preserved. Automatic GitHub updating was removed; deploy updates through the approved WordPress/CampusPress process.
