=== ISD - Image Simple Destruction ===
Contributors: theagoliddell
Donate link: https://github.com/theagoliddell/ISD-Image-Simple-Destruction
Tags: image, media, cleanup, optimization, database
Requires at least: 5.2
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

It automatically or manually removes media files (images) from the server that belong to old or broken posts, making the server lighter.

== Description ==

ISD - Image Simple Destruction is a lightweight, highly efficient WordPress plugin designed to keep your server clean and light. It automatically and manually deletes image attachments (including their generated thumbnail files) associated with posts that have exceeded a specific age (e.g. older than 30 days or several months), or images linked to broken resources (deleted parent posts, trashed posts, or orphaned media files).

= Key Features =
* **Automatic Periodic Cleanups**: Schedule daily, twicedaily, or weekly sweeps.
* **Manual Cleanup Tool**: An interactive, step-by-step scanner with real-time feedback (avoiding database lockups and php timeouts).
* **Category Exclusions**: Select specific categories to exempt from cleanup, keeping crucial post media safe.
* **Visual Identity Safety Lock**: Auto-detects and protects **favicons**, **custom logos**, header images, and background files from accidental deletion.
* **Disk Space Telemetry**: Logs how much space was cleared and how many files were deleted in a neat history dashboard.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or search and install it directly via the WordPress Admin.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the cleaning settings under **Settings > Image Destruction**.

== Frequently Asked Questions ==

= Does it delete the text of my posts? =
No. The plugin only targets attachment metadata and the actual graphic files (images) from the server uploads directory. Your text posts remain untouched.

= Are custom logos and favicons protected? =
Yes. The plugin queries customizer headers, site icons, and active theme mods for keyword matches like "logo", "icon", "favicon" to prevent their destruction.

== Changelog ==

= 1.0.0 =
* Initial release. Fully secure, compliant with WPCS standards, featuring interactive AJAX batch cleanups, category exclusions, and customizer branding locks.
