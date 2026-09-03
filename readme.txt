=== LC HoverPeek ===
Contributors: deep7197
Tags: link preview, hover, link, post preview, preview
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

LC HoverPeek adds a lightweight preview popup when users hover over links. It supports internal WordPress posts and external links.

== Description ==

**LC HoverPeek** enhances user experience by showing a preview popup when visitors hover over links inside your content.

When a user hovers over a link, the plugin fetches preview information and displays a small popup containing:

* Post title
* Featured image
* Short excerpt
* Link preview

For external links, the plugin automatically fetches metadata such as:

* Page title
* Description
* Open Graph image (if available)

This allows users to quickly preview the destination without leaving the current page.

The plugin is lightweight, optimized for performance, and designed to work with most WordPress themes.

= Key Features =

* Hover preview for internal WordPress posts.
* Preview external links automatically.
* Change appearance colors (Background, Title, Excerpt, Link)
* Choose which specific Post Types (Posts, Pages, etc.) trigger a preview.
* Featured image support.
* Automatic excerpt generation.
* AJAX-powered loading.
* External link metadata scraping.
* Transient caching for better performance.
* Lightweight JavaScript and CSS.
* Works with most themes and page builders.
* No shortcode required.

= Use Cases =

* Blog posts referencing other articles
* Documentation websites
* Knowledge bases
* Educational content
* News websites
* Internal linking strategies

== Customization Options ==

You can customize popup appearance and behavior from **Hover Preview** Menu.

**Display Settings:**
* Enable/Disable Internal Links
* Enable/Disable External Links
* Select Supported Post Types (Posts, Pages, etc.)

**Appearance Settings:**
* Popup Background Color
* Title Color
* Excerpt Color
* Link Color

==Screenshots==
1. Frontend Preview
2. Hover Preview Settings

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. The plugin will automatically start showing previews on hover

No additional configuration is required.

== Frequently Asked Questions ==

= Does the plugin work with external links? =

Yes. LC HoverPeek automatically detects external URLs and fetches preview metadata such as title, description, and image.

= Does it slow down my website? =

No. The plugin uses AJAX and transient caching to ensure minimal performance impact.

= Does it work with page builders? =

Yes. The plugin works with most page builders as long as links are rendered as standard `<a>` tags.

= Can I disable previews for certain links? =

Currently the plugin automatically processes links in the content. Future versions may include granular controls.

= Does it require JavaScript? =

Yes. The hover preview functionality uses JavaScript and AJAX.

== Screenshots ==

1. Link preview popup on hover
2. Internal post preview
3. External link preview

== Changelog ==

= 1.0.5 =
* Tested Upto WP 7.1

= 1.0.4 =
* Security Hardening: Patched unauthenticated SSRF vulnerability in preview handlers.
* Security Hardening: Added input validation, rate limiting, and output sanitization for remote URL fetching.
* Security Hardening: Fixed potential DOM-based XSS when rendering external link previews in the frontend.

= 1.0.3 =
* Textual Changes

= 1.0.2 =
* Tested Upto WordPress 7.0.

= 1.0.1 =
* Textual changes.

= 1.0.0 =

* Initial plugin release.

== Upgrade Notice ==

= 1.0.1 =
Textual changes.
