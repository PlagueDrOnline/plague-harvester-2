=== Plague Harvester ===
Contributors: plague-dr
Tags: crawler, importer, exporter, affiliate, admin, content
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plague Harvester is a comprehensive automation toolkit designed to streamline content curation. Crawl external sources, enrich your content with AI, manage your affiliate campaigns, and maintain total control over your imported content—all from one centralized admin panel.

== Description ==

Plague Harvester provides a robust ecosystem for content creators and affiliate marketers:

* **Automated Extraction:** Intelligent URL crawling and video ID parsing.
* **AI Content Enrichment:** Automatically generates unique titles and SEO descriptions via AI before posting.
* **Streamlined Importing:** Import crawled content directly into your workflow as published posts or drafts.
* **Flexible Exporting:** Easily export your library to JSON or CSV formats.
* **Ad & Banner Management:** Dedicated interface for managing affiliate banners and ad campaigns.
* **Affiliate Dashboard:** Keep track of your partner URLs and affiliate performance metrics.

== Installation ==

1. **Upload:** Upload the `plague-harvester` folder to the `/wp-content/plugins/` directory of your WordPress installation.
2. **Activate:** Navigate to the 'Plugins' menu in your WordPress dashboard and activate Plague Harvester.
3. **Configure:** Click the new 'Plague Harvester' menu item in your sidebar to access the settings and input your OpenAI API key.
4. **Start Harvesting:** Enter your target URL into the crawler tool to begin fetching and generating content.

== Usage ==

* **Crawl:** Input target URLs to fetch raw content IDs.
* **Rewriter:** Automatically transform basic IDs into unique, catchy titles and SEO descriptions.
* **Import:** Review extracted and AI-generated data, pushing it directly into your site.
* **Export:** Generate JSON/CSV reports of your curated library.
* **Banners:** Manage creative assets for your affiliate campaigns.
* **Affiliate:** Configure tracking links and partner settings.

== Frequently Asked Questions ==

= Do I need an external API key for the AI features? =
Yes, you must add your OpenAI API key in the plugin settings to enable the `class-rewriter.php` functionality to generate unique titles and descriptions.

= How does the plugin handle duplicate videos? =
The plugin automatically checks for an existing post meta key `_video_id` before saving. If that video ID already exists in your database, the script skips it to prevent spam and duplicates.

= Can I schedule automatic crawls? =
The core orchestrator loop is designed to be triggered manually or via specific admin actions, but you can hook the `process_tube_site_automation()` method into a WP-Cron or WP-CLI command for total automation.

= What post type is created? =
All harvested, spun, and imported videos are published as standard WordPress 'post' types containing your AI text and iframe embed.

== Changelog ==

= 1.0.0 =
* Initial release: Core features include automated URL crawling, AI text generation, post importing, JSON/CSV data exporting, banner management, and an integrated affiliate dashboard.
