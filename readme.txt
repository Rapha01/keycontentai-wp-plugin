=== SparkPlus ===
Contributors: olympagency
Tags: ai content, content generation, openai, custom post types, seo
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI content generation for any WordPress post type — text and images via OpenAI, Anthropic, or Gemini, plus a bundled set of Olymp marketing tools.

== Description ==

SparkPlus lets you generate AI-powered content for any WordPress post type — including custom post types and Advanced Custom Fields (ACF). Simply provide keywords and context, and SparkPlus creates professionally written, SEO-optimized content with matching images in seconds. Pick your preferred AI engine per model — OpenAI, Anthropic Claude, or Google Gemini.

The plugin also bundles **Olymp Tools**: a small collection of standalone marketing side-features (currently Google Reviews and Visitor Location) that live under their own admin menu and work completely independently of the AI content generator. Use as much or as little as you need.

**Key Features:**

* **Bulk keyword loading** — Enter one keyword per line to batch-create posts instantly. Duplicate detection prevents accidental duplicates.
* **AI text generation** — Generate post titles, content, excerpts, and any ACF text/textarea/WYSIWYG field using the latest OpenAI, Anthropic Claude, and Google Gemini models.
* **AI image generation** — Automatically generate featured images and ACF image fields. Images are converted to WebP for optimal performance.
* **Multi-provider support** — Choose a different provider and model for text and for images. All AI calls are made directly from your browser to the provider, so no long-running server requests can time out.
* **Custom post type support** — Works with all public post types including built-in posts, pages, and any registered CPTs.
* **ACF integration** — Automatically discovers ACF field groups and generates content for text and image fields.
* **Internal linking pool** — Populate ACF `post_object` (relationship) fields automatically from a pool of existing posts, so generated content links to related content on your site.
* **SEO integration** — Optional RankMath support generates SEO meta titles and descriptions, plus a keyword-rich URL slug applied as the post permalink.
* **Reference & related images** — Attach a reference image to an image field (used as visual context by Gemini), or link an image field to a text field so the generated image correlates with that text.
* **Three-tier context system** — Provide site-wide brand context, per-post-type instructions, and per-post specific context for highly targeted content.
* **Configurable formatting** — Control which HTML elements (headings, lists, bold, italic, links) the AI may use in rich text fields.
* **Queue-based generation** — Queue individual posts or batch-queue all posts, then generate sequentially with real-time progress.
* **Debug mode** — Inspect the exact prompts sent to your AI provider and the raw API responses for full transparency.
* **Auto-publish** — Optionally publish posts immediately upon keyword loading instead of saving as drafts.
* **Post editor integration** — Edit keywords and additional context directly from the WordPress post editor via a sidebar meta box.
* **Multi-language support** — Content is generated in your site's WordPress locale language. 50+ languages supported.

**Settings & Configuration:**

* **API Settings** — Enter an API key per provider (OpenAI, Anthropic, Google Gemini) and choose your text and image models.
* **General Context** — Define your company name, industry, target audience, USP, product advantages, and buying reasons to shape all generated content.
* **CPT Configuration** — Per-post-type field mapping with individual enable/disable toggles, custom descriptions/prompts, word counts, image size/quality settings, reference images, and image-to-text relations.
* **SEO** — Enable RankMath meta title/description generation and keyword-rich URL slugs.
* **Internal Linking** — Enable and configure the linking pool used to fill relationship fields.
* **Reset** — Easily reset all plugin settings or clear post meta data when needed.

**Supported AI Models:**

* OpenAI (text): gpt-5.5, gpt-5.5-pro, gpt-5.4, gpt-5.2, gpt-5.1, gpt-5-mini, gpt-5-nano
* Anthropic Claude (text): Claude Opus 4.5, Claude Sonnet 4.5, Claude Haiku 3.5
* Google Gemini (text): Gemini 3.1 Pro, Gemini 3 Flash, Gemini 3.1 Flash Lite, Gemini 2.5 Pro / Flash / Flash Lite
* OpenAI (image): gpt-image-2, gpt-image-1.5, gpt-image-1, gpt-image-1-mini
* Google Gemini (image): Gemini 3.1 Flash Image, Gemini 3 Pro Image, Gemini 2.5 Flash Image

= Bundled: Olymp Tools =

Olymp Tools is a lightweight container for standalone features that are independent of the AI generator. Each tool has its own submenu under the **Olymp Tools** admin menu.

**Google Reviews** — Display your Google rating and review count anywhere via shortcodes:

* `[olymp_google_reviews_average]` → average rating, e.g. "4.6"
* `[olymp_google_reviews_count]` → total review count, e.g. "128"

The values are fetched server-side from the Google Places API and cached; your API key never reaches the browser and the shortcodes keep serving the last known-good numbers if a refresh fails.

**Visitor Location** — Insert the current visitor's location into your copy for location-personalised marketing (e.g. "Best offers near [olymp_visitor_city]"):

* `[olymp_visitor_city default="..."]`, `[olymp_visitor_region ...]`, `[olymp_visitor_country ...]`, `[olymp_visitor_country_code ...]`
* `[olymp_visitor_location field="city,country" separator=", " default="..."]`

Lookups are 100% local against a DB-IP Lite City database stored on your server — the visitor's IP never leaves your site. Rendering is client-side, so it works correctly behind full-page caching.

= Third-Party Services =

SparkPlus and its bundled Olymp Tools connect to the following external services. Each connection only happens for the feature that uses it, and only once you have configured and used that feature.

**AI content generation (SparkPlus)**

When you trigger content generation, your request — including keywords, configured context (company name, industry, target audience, etc.), and field descriptions — is sent directly from your browser to the AI provider you selected for that model. You choose the provider per model and supply your own API key; usage is billed by that provider. Only the provider(s) you configure are ever contacted.

* **OpenAI, L.L.C.** — Endpoint: [https://api.openai.com](https://api.openai.com) · Terms: [https://openai.com/policies/terms-of-use](https://openai.com/policies/terms-of-use) · Privacy: [https://openai.com/policies/privacy-policy](https://openai.com/policies/privacy-policy) · Keys: [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)
* **Anthropic (Claude)** — Endpoint: [https://api.anthropic.com](https://api.anthropic.com) · Terms: [https://www.anthropic.com/legal/consumer-terms](https://www.anthropic.com/legal/consumer-terms) · Privacy: [https://www.anthropic.com/legal/privacy](https://www.anthropic.com/legal/privacy) · Keys: [https://console.anthropic.com/](https://console.anthropic.com/)
* **Google Gemini** — Endpoint: [https://generativelanguage.googleapis.com](https://generativelanguage.googleapis.com) · Terms: [https://ai.google.dev/gemini-api/terms](https://ai.google.dev/gemini-api/terms) · Privacy: [https://policies.google.com/privacy](https://policies.google.com/privacy) · Keys: [https://aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)

**Google Reviews tool (Olymp Tools)**

If you configure the Google Reviews tool, your server (never the browser) requests your business's `rating` and `userRatingCount` from the Google Places API for the Place ID you enter. Your API key is stored on your server and used only for this request.

* **Service provider:** Google LLC — Places API (New)
* **API endpoint:** [https://places.googleapis.com/v1/places/](https://places.googleapis.com/v1/places/)
* **Terms of use:** [https://cloud.google.com/maps-platform/terms](https://cloud.google.com/maps-platform/terms)
* **Privacy policy:** [https://policies.google.com/privacy](https://policies.google.com/privacy)

**Visitor Location tool (Olymp Tools)**

If you enable the Visitor Location tool, your server downloads the free DB-IP Lite City database (a data file) and stores it locally. All IP-to-location lookups then happen entirely on your server — no visitor data or IP address is ever sent to DB-IP or any other third party.

* **Service provider:** DB-IP — "IP Geolocation by DB-IP", licensed CC-BY 4.0
* **Download endpoint:** [https://download.db-ip.com/free/](https://download.db-ip.com/free/)
* **Website / terms:** [https://db-ip.com/](https://db-ip.com/)

No data is sent to any of these services except as described above, and only when you configure and use the corresponding feature.

== Installation ==

1. Upload the `sparkplus` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress plugin screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **SparkPlus > Settings > API Settings** and enter an API key for at least one supported provider (OpenAI, Anthropic Claude, or Google Gemini).
4. Configure your brand context under **SparkPlus > Settings > General Context**.
5. Select your desired post type and configure fields under **SparkPlus > Settings > CPT**.
6. Go to **SparkPlus > Load Keywords** to create posts from keywords, then use **SparkPlus > Generation** to generate AI content.

== Frequently Asked Questions ==

= Do I need an API key? =

Yes. SparkPlus needs your own API key for at least one supported AI provider — OpenAI, Anthropic Claude, or Google Gemini — to generate content. For OpenAI you can get one at [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys). Each provider bills API usage based on the model and volume, and you only pay the provider(s) you actually use.

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

= Can I use a provider other than OpenAI? =

Yes. SparkPlus supports OpenAI, Anthropic Claude, and Google Gemini. Add the API key for each provider you want to use under **SparkPlus > Settings > API Settings**, then pick the text and image model you prefer. You only need a key for the provider(s) you actually use.

= What are Olymp Tools? =

Olymp Tools is a small set of standalone marketing features bundled with the plugin, under their own **Olymp Tools** admin menu. They are completely independent of the AI content generator — currently they include Google Reviews and Visitor Location. You can ignore them entirely if you only want AI content generation.

= How do the Google Reviews shortcodes work? =

Enter a Google Places API key and your business's Place ID under **Olymp Tools > Google Reviews**. Then place `[olymp_google_reviews_average]` or `[olymp_google_reviews_count]` anywhere in your content. The values are fetched server-side and cached, so your API key is never exposed and the numbers keep showing even if a refresh temporarily fails.

= Is the Visitor Location tool GDPR-friendly? =

Yes. Visitor Location resolves the visitor's city/region/country entirely on your own server using a locally-stored DB-IP Lite City database. The visitor's IP address never leaves your site, and no third-party geolocation API is called at request time. Use shortcodes like `[olymp_visitor_city default="your area"]` for location-personalised copy.

== Screenshots ==

1. Load Keywords — Bulk-create posts from a list of keywords.
2. Generation — Queue and generate AI content with real-time progress.
3. Settings: CPT — Configure fields, word counts, and image settings per post type.
4. Settings: General Context — Define your brand identity and content guidelines.
5. Settings: API — Configure your OpenAI API key and model preferences.

== Changelog ==

= 1.1.6 =
* **New Olymp Tool: Visitor Location.** Show the current visitor's location in your content via shortcodes — `[olymp_visitor_city]`, `[olymp_visitor_region]`, `[olymp_visitor_country]`, `[olymp_visitor_country_code]`, and the combined `[olymp_visitor_location field="city,country" separator=", "]` — for location-personalised marketing copy. Each shortcode takes a `default` fallback.
* IP-to-location lookups run 100% locally against a DB-IP Lite City database that the plugin downloads and stores on your server (via a background WP-Cron job, with an atomic swap and stale-while-revalidate refresh). The visitor's IP never leaves your site — no third-party geolocation API is called at request time.
* Rendering is client-side, so the shortcodes work correctly behind full-page caching: every visitor gets their own location instead of the first visitor's cached value.
* **SparkPlus: relate an image field to a text field.** In CPT settings, an image field can now be linked to one of the post's text fields (title, excerpt, content, an ACF field, an ACF group sub-field, a RankMath meta field, or the URL slug) so the generated image is created to correlate with that text. The reference-image picker and the new relation selector now sit together in the field's prompt column.
* Fixed the `SPARKPLUS_VERSION` constant lagging one version behind the plugin header.

= 1.1.5 =
* **Restructured into two independent modules under one plugin**, loaded by a thin bootstrap: the SparkPlus AI content generator (in `sparkplus/`) and the new Olymp Tools framework (in `olymp-tools/`). Each module is self-contained and unaware of the other.
* **New Olymp Tools framework** — a pluggable container for standalone side-features that are unrelated to AI generation, each exposed as its own submenu under a dedicated top-level **Olymp Tools** admin menu.
* **New Olymp Tool: Google Reviews.** Two shortcodes output your Google rating and review count as bare numbers: `[olymp_google_reviews_average]` (e.g. "4.6") and `[olymp_google_reviews_count]` (e.g. "128"). Data is fetched server-side from the Google Places API and cached; a failed refresh keeps serving the last known-good values, and the API key never reaches the browser.

= 1.1.4 =
* **Architecture: full client-side AI orchestration.** All AI API calls (text and image, for all providers) are now made directly from the browser. PHP no longer proxies any AI request. The server is a pure data layer: it supplies settings, field configs, existing content, and the linking pool on demand, then receives and saves the results the browser sends back.
* Removed the server-side generation pipeline entirely: `SparkPlus_Generation_Runner` (formerly `Generation_Cron`), `SparkPlus_Prompt_Builder`, `SparkPlus_OpenAI_API_Caller`, `SparkPlus_API_Manager`, and all three provider classes (`SparkPlusOpenAIProvider`, `SparkPlusAnthropicProvider`, `SparkPlusGeminiProvider`) have been deleted from PHP. None of the flush-early, transient polling, cron, or loopback-request infrastructure they relied on exists any longer.
* The `flush_early` / `fastcgi_finish_request()` approach introduced in 1.1.3 and the WP-Cron background dispatch introduced in 1.1.1 are gone. There is nothing left to time out on the server side.
* `content-generator.php` / `SparkPlus_Content_Generator` split into three focused files: `generation-helpers-trait.php` (shared field-map and settings helpers), `generation-meta.php` / `SparkPlus_Generation_Meta` (data provider: supplies all generation inputs to the client), and `generation-saver.php` / `SparkPlus_Generation_Saver` (result saver: persists AI output to the database).
* New JS module `sparkplus-providers.js`: browser-side provider classes (`SparkPlusOpenAIProvider`, `SparkPlusAnthropicProvider`, `SparkPlusGeminiProvider`) and `SparkPlusProviderFactory` mirror the deleted PHP provider layer.
* New JS module `sparkplus-prompt-builder.js`: browser-side `SparkPlusPromptBuilder` mirrors the deleted PHP prompt builder, constructing text and image prompts entirely in the browser.
* OpenAI model quirks handled client-side: `gpt-5.5` omits the `temperature` parameter (API only accepts the default); `gpt-5.5-pro` routes to the `/v1/responses` endpoint with the correct `input` / `text.format` shape and its response is parsed from `output[].content[].text`.
* Debug panel tabs (Last Text Prompt, Last Image Prompt, Last Text API Response, Last Image API Response) now populate correctly: `generation.js` emits `build_text_prompt`, `build_image_prompt`, and `full_api_response` entries at the right points in the client-side flow.
* Last Image API Response tab displays the saved WordPress media URL as a visual image preview above the raw JSON, using `wp_get_attachment_url()` returned by the save endpoint.

= 1.1.3 =
* Replaced WP-Cron background dispatch with a direct `flush_early` approach: the AJAX handler sends the job ID to the browser immediately via `fastcgi_finish_request()`, then runs the API call inline. This eliminates the server-to-itself loopback HTTP request that WP-Cron required, which was blocked on some hosting environments (including SiteGround).

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

= 1.1.6 =
Adds the Visitor Location Olymp Tool (local, GDPR-friendly geolocation shortcodes) and lets image fields be generated to match a linked text field. No configuration required.

= 1.1.5 =
Introduces the bundled Olymp Tools menu with the new Google Reviews shortcodes and reorganises the plugin into separate SparkPlus and Olymp Tools modules. Your existing SparkPlus settings and content are preserved.

= 1.1.3 =
Fixes generation not starting on hosting environments (including SiteGround) that block server-to-itself HTTP requests. No configuration required.

= 1.1.1 = into WP-Cron background jobs to resolve server timeout errors on slow API calls. No configuration required.

= 1.0.9 =
Generation requests are now asynchronous. The browser receives a job ID immediately and polls for the result. No configuration required, but custom integrations that rely on synchronous AJAX responses will need to be updated.

= 1.0.7 =
Major update: multi-provider AI support added (Google Gemini, Anthropic Claude). The API Settings tab has been redesigned. Your existing OpenAI key is preserved. New API keys for Gemini or Anthropic can be added optionally.

= 1.0.4 =
New SEO tab added to settings with optional RankMath integration for AI-generated meta titles and descriptions.

= 1.0.0 =
Initial release.
