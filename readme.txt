=== SparkPlus ===
Contributors: olympagency
Tags: ai content, content generation, openai, custom post types, seo
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered content generation for WordPress. Create posts and populate custom post types with high-quality text and images using OpenAI.

== Description ==

SparkPlus lets you generate AI-powered content for any WordPress post type — including custom post types and Advanced Custom Fields (ACF). Simply provide keywords and context, and SparkPlus creates professionally written, SEO-optimized content with matching images in seconds.

**Key Features:**

* **Bulk keyword loading** — Enter one keyword per line to batch-create posts instantly. Duplicate detection prevents accidental duplicates.
* **AI text generation** — Generate post titles, content, excerpts, and any ACF text/textarea/WYSIWYG field using OpenAI's latest models.
* **AI image generation** — Automatically generate featured images and ACF image fields. Images are converted to WebP for optimal performance.
* **Custom post type support** — Works with all public post types including built-in posts, pages, and any registered CPTs.
* **ACF integration** — Automatically discovers ACF field groups and generates content for text and image fields.
* **Three-tier context system** — Provide site-wide brand context, per-post-type instructions, and per-post specific context for highly targeted content.
* **Configurable formatting** — Control which HTML elements (headings, lists, bold, italic, links) the AI may use in rich text fields.
* **Queue-based generation** — Queue individual posts or batch-queue all posts, then generate sequentially with real-time progress.
* **Debug mode** — Inspect the exact prompts sent to OpenAI and the raw API responses for full transparency.
* **Auto-publish** — Optionally publish posts immediately upon keyword loading instead of saving as drafts.
* **Post editor integration** — Edit keywords and additional context directly from the WordPress post editor via a sidebar meta box.
* **Multi-language support** — Content is generated in your site's WordPress locale language. 50+ languages supported.

**Settings & Configuration:**

* **API Settings** — Enter your OpenAI API key and choose from multiple text and image generation models.
* **General Context** — Define your company name, industry, target audience, USP, product advantages, and buying reasons to shape all generated content.
* **CPT Configuration** — Per-post-type field mapping with individual enable/disable toggles, custom descriptions/prompts, word counts, and image size/quality settings.
* **Reset** — Easily reset all plugin settings or clear post meta data when needed.

**Supported OpenAI Models:**

* Text: gpt-5.2, gpt-5.2-pro, gpt-5.1, gpt-5, gpt-4.1, gpt-5-mini, gpt-5-nano, gpt-3.5-turbo
* Image: gpt-image-1.5, gpt-image-1, gpt-image-1-mini

= Third-Party Services =

This plugin connects to the **OpenAI API** to generate text and image content. When you trigger content generation, data including your keywords, configured context (company name, industry, target audience, etc.), and field descriptions are sent to OpenAI's servers for processing.

* **Service provider:** OpenAI, L.L.C.
* **API endpoint (text):** [https://api.openai.com/v1/chat/completions](https://api.openai.com/v1/chat/completions)
* **API endpoint (images):** [https://api.openai.com/v1/images/generations](https://api.openai.com/v1/images/generations)
* **Terms of use:** [https://openai.com/policies/terms-of-use](https://openai.com/policies/terms-of-use)
* **Privacy policy:** [https://openai.com/policies/privacy-policy](https://openai.com/policies/privacy-policy)

An OpenAI API key is required. You can obtain one at [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys). API usage is billed by OpenAI according to their pricing.

No data is sent to any third party other than OpenAI, and only when you explicitly trigger content generation.

== Installation ==

1. Upload the `sparkplus` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress plugin screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **SparkPlus > Settings > API Settings** and enter your OpenAI API key.
4. Configure your brand context under **SparkPlus > Settings > General Context**.
5. Select your desired post type and configure fields under **SparkPlus > Settings > CPT**.
6. Go to **SparkPlus > Load Keywords** to create posts from keywords, then use **SparkPlus > Generation** to generate AI content.

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes. SparkPlus requires your own OpenAI API key to generate content. You can get one at [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys). OpenAI charges for API usage based on the model and volume.

= Which post types are supported? =

All public post types are supported, including the built-in "Post" and "Page" types as well as any custom post types registered by your theme or other plugins.

= Does it work with Advanced Custom Fields (ACF)? =

Yes. SparkPlus automatically detects ACF field groups assigned to your selected post type and lets you enable AI generation for individual text, textarea, WYSIWYG, and image fields.

= What image format is used for generated images? =

All AI-generated images are automatically converted to WebP format (quality 90) for optimal file size and performance, then added to your WordPress Media Library.

= Can I control the writing style and tone? =

Yes. You can configure formal or informal addressing, provide brand context (company name, USP, target audience, etc.), add per-post-type instructions, and even provide per-post specific context. The AI uses all of this to tailor the content.

= What languages are supported? =

Content is generated in the language matching your WordPress site locale. Over 50 languages are supported, including English, German, Spanish, French, and many more.

= Can I review what the AI generates before publishing? =

Yes. Posts created via keyword loading are saved as drafts by default. You can review and edit them before publishing. There is also an auto-publish option if you prefer immediate publishing.

= How do I reset the plugin? =

Go to **SparkPlus > Settings > Reset**. You can reset all plugin settings (API key, context, CPT configurations) or reset post meta data (keywords, context, generation timestamps) independently.

== Screenshots ==

1. Load Keywords — Bulk-create posts from a list of keywords.
2. Generation — Queue and generate AI content with real-time progress.
3. Settings: CPT — Configure fields, word counts, and image settings per post type.
4. Settings: General Context — Define your brand identity and content guidelines.
5. Settings: API — Configure your OpenAI API key and model preferences.

== Changelog ==

= 1.1.2 =
* Added changelog entries for all previous versions.
* Fixed js/css caching problem by increasing the SPARKPLUS_VERSION constant.

= 1.1.1 =
* Moved AI generation work into WP-Cron background jobs to eliminate server-side timeout errors on long API calls.
* Generation cron callbacks are now registered on every request type (including wp-cron.php) via a dedicated `generation-cron.php` class.
* AJAX handlers for text and image generation now return a job ID immediately and trigger cron asynchronously.

= 1.1.0 =
* Debug log entries are now streamed to the client continuously during generation rather than delivered all at once at the end.
* The server writes each debug entry to the transient in real time; the polling client reads and displays new entries incrementally on every poll.

= 1.0.9 =
* Generation requests (`generate_text`, `generate_image`) now return a job ID immediately and close the HTTP connection early.
* The actual API work runs after the response is flushed, with the result stored in a WordPress transient.
* The JavaScript client polls a new `check_job_status` endpoint every 2 seconds until the job completes.
* This eliminates 504 gateway timeout errors caused by long-running API calls holding open HTTP connections.

= 1.0.8 =
* Added URL slug generation: a new "URL Slug" field can be enabled in the SEO settings tab and will appear in the CPT field list.
* The AI generates a keyword-rich URL slug and applies it as the post permalink (`post_name`).
* Added reference image support for image fields: a media picker allows attaching a reference image per image field (used by Gemini as inline image data in the prompt).
* Image settings layout in CPT tab reorganised into two rows (Aspect Ratio + WebP % on row 1; Generation Quality + Resolution on row 2).
* Resolution dropdown labels simplified (Low / Medium / High).
* Tooltip icons added to all four image settings.

= 1.0.7 =
* Added multi-AI-provider support: Google Gemini and Anthropic Claude can now be used alongside OpenAI.
* New abstract provider base class with concrete implementations for OpenAI, Gemini, and Anthropic.
* API Settings tab redesigned to show separate API key fields per provider, grouped by provider section.
* Deprecated model detection: a warning is shown in settings if a previously saved model is no longer supported.
* Added `util.php` with shared helper functions including `sparkplus_get_supported_models()` and `sparkplus_get_model_provider()`.
* Content generator refactored to route all API calls through the new `SparkPlus_API_Manager` factory.

= 1.0.6 =
* Version bump / compatibility update (no functional changes).

= 1.0.5 =
* Added optional parent post selection to the keyword loader: when a published post of the selected post type exists, a dropdown allows setting all newly created posts as children of a selected parent.
* Draft parents are shown in the parent post dropdown.
* Prompt builder updated with minor SEO title instruction improvements.

= 1.0.4 =
* Added SEO tab in settings with RankMath integration: enables AI generation for RankMath title and description meta fields.
* Added Taxonomies tab in settings (foundation for taxonomy term generation).
* Added `post_object` ACF field type support in the CPT field list, with a "Nr. of Posts" option instead of word count.
* Warning shown in CPT settings when a `post_object` field is enabled but the linking pool is not active.
* `true_false` ACF fields now correctly hide the options column in the CPT field table.

= 1.0.3 =
* Added `post_object` ACF field type detection inside group sub-fields in the CPT settings UI.
* `true_false` sub-fields inside groups now correctly hide the options column.
* Linking pool enabled state is checked and shown as a warning for `post_object` sub-fields when the pool is disabled.
* Prompt builder improvements for field instruction generation.

= 1.0.2 =
* Improved debug panel: added `source` parameter (`client` / `server`) to all debug entries and debug UI.
* Centralised error handling: all generation errors now show a WordPress admin notice with the post keyword/ID in addition to logging to the debug panel.
* Error notices from previous runs are cleared when a new generation starts.
* Debug panel CSS improvements.

= 1.0.1 =
* Internal test release / version bump (no functional changes).

= 1.0.0 =
* Initial release.
* Bulk keyword loading with duplicate detection.
* AI text generation for post titles, content, excerpts, and ACF fields.
* AI image generation with automatic WebP conversion.
* Support for all public post types and ACF field groups.
* Three-tier context system (site-wide, per-CPT, per-post).
* Configurable WYSIWYG formatting rules.
* Queue-based generation with debug mode.
* Post editor meta box for keyword and context editing.
* Full settings management with reset capability.

== Upgrade Notice ==

= 1.1.1 =
Moves AI generation into WP-Cron background jobs to resolve server timeout errors on slow API calls. No configuration required.

= 1.0.9 =
Generation requests are now asynchronous. The browser receives a job ID immediately and polls for the result. No configuration required, but custom integrations that rely on synchronous AJAX responses will need to be updated.

= 1.0.7 =
Major update: multi-provider AI support added (Google Gemini, Anthropic Claude). The API Settings tab has been redesigned. Your existing OpenAI key is preserved. New API keys for Gemini or Anthropic can be added optionally.

= 1.0.4 =
New SEO tab added to settings with optional RankMath integration for AI-generated meta titles and descriptions.

= 1.0.0 =
Initial release.
