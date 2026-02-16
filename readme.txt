=== SparkWP ===
Contributors: olympagency
Tags: ai content, content generation, openai, custom post types, seo
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered content generation for WordPress. Create posts and populate custom post types with high-quality text and images using OpenAI.

== Description ==

SparkWP lets you generate AI-powered content for any WordPress post type — including custom post types and Advanced Custom Fields (ACF). Simply provide keywords and context, and SparkWP creates professionally written, SEO-optimized content with matching images in seconds.

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

1. Upload the `sparkwp` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress plugin screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **SparkWP > Settings > API Settings** and enter your OpenAI API key.
4. Configure your brand context under **SparkWP > Settings > General Context**.
5. Select your desired post type and configure fields under **SparkWP > Settings > CPT**.
6. Go to **SparkWP > Load Keywords** to create posts from keywords, then use **SparkWP > Generation** to generate AI content.

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes. SparkWP requires your own OpenAI API key to generate content. You can get one at [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys). OpenAI charges for API usage based on the model and volume.

= Which post types are supported? =

All public post types are supported, including the built-in "Post" and "Page" types as well as any custom post types registered by your theme or other plugins.

= Does it work with Advanced Custom Fields (ACF)? =

Yes. SparkWP automatically detects ACF field groups assigned to your selected post type and lets you enable AI generation for individual text, textarea, WYSIWYG, and image fields.

= What image format is used for generated images? =

All AI-generated images are automatically converted to WebP format (quality 90) for optimal file size and performance, then added to your WordPress Media Library.

= Can I control the writing style and tone? =

Yes. You can configure formal or informal addressing, provide brand context (company name, USP, target audience, etc.), add per-post-type instructions, and even provide per-post specific context. The AI uses all of this to tailor the content.

= What languages are supported? =

Content is generated in the language matching your WordPress site locale. Over 50 languages are supported, including English, German, Spanish, French, and many more.

= Can I review what the AI generates before publishing? =

Yes. Posts created via keyword loading are saved as drafts by default. You can review and edit them before publishing. There is also an auto-publish option if you prefer immediate publishing.

= How do I reset the plugin? =

Go to **SparkWP > Settings > Reset**. You can reset all plugin settings (API key, context, CPT configurations) or reset post meta data (keywords, context, generation timestamps) independently.

== Screenshots ==

1. Load Keywords — Bulk-create posts from a list of keywords.
2. Generation — Queue and generate AI content with real-time progress.
3. Settings: CPT — Configure fields, word counts, and image settings per post type.
4. Settings: General Context — Define your brand identity and content guidelines.
5. Settings: API — Configure your OpenAI API key and model preferences.

== Changelog ==

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

= 1.0.0 =
Initial release.
