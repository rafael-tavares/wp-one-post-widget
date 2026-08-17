=== WP One Post Widget ===
Contributors: Rafael Tavares
Tags: widget, post, sidebar, featured post, one post
Requires at least: 5.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 3.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Select one specific published post and display it in a widget area.

== Description ==

WP One Post Widget lets you pick a single published post and show it in any widget area.

Features:

* Search and select a published post from the Widgets screen
* Optional custom title
* Optional “Read more” label linking to the post permalink
* Optional featured image with left, right, or top placement
* Uses the post excerpt when available, otherwise a safe plain-text summary

The plugin does one job: display one selected post in a widget.

== Installation ==

1. Upload the `wp-one-post-widget` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Go to Appearance → Widgets (or the Customizer widgets panel).
4. Add “WP One Post Widget”, search for a published post, and save.
5. Optional: set a custom title, read-more label, and thumbnail options.

Tip: for best results with summaries, fill in the post Excerpt field in the editor.

== Frequently Asked Questions ==

= Does it support multiple widget instances? =

Yes. Each instance stores its own selected post ID and options.

= What happens if the selected post is deleted? =

The widget renders nothing and does not produce PHP warnings.

= Does the plugin send telemetry? =

No.

== Changelog ==

= 3.0.0 =

* Migrated registration to `WP_Widget`.
* Store selection by `post_id` instead of post title.
* Autocomplete via authenticated `admin-ajax` + `WP_Query`.
* Removed insecure public `data.php` endpoint.
* Removed PressTrends telemetry.
* Removed external Google jQuery / jQuery UI assets.
* Security hardening: nonces, capabilities, sanitization, and escaping.
* PHP modernization (no short open tags; current WordPress APIs).
* Featured image rendering and left/right/top positioning fixes.
* Frontend CSS limited to structural layout (theme keeps visual identity).
* Safe excerpt generation without cutting raw HTML.

= 2.1 =

* Added field use image?

= 2.0 =

* Added custom title field.
* Added search suggestion field.
* Added field thumbnail position: left, right, top

= 1.1 =

* Added field to customize the link read more.

= 1.0 =

* Initial version.

== Upgrade Notice ==

= 3.0.0 =
Major structural update. Existing widgets are migrated conservatively to post ID when possible. Re-check each widget after upgrading.
