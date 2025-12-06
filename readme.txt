=== AutoBlogger ===
Contributors: yourname
Tags: ai, content-generation, seo, rankmath, gutenberg, gemini, claude
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered content generation with RankMath SEO optimization and E-E-A-T compliance.

== Description ==

AutoBlogger automates the blog writing process with AI-powered content generation, integrated SEO optimization via RankMath, and built-in E-E-A-T compliance features.

**Key Features:**

* AI-powered content generation using Claude 3.5 Sonnet
* RankMath integration for real-time SEO scoring
* Retrieval Augmented Generation (RAG) with knowledge base
* Cost tracking and budget management
* Content versioning and recovery
* Safety filtering and E-E-A-T compliance
* Multi-language support (Vietnamese)

**Requirements:**

* WordPress 6.0+
* PHP 7.4+ with OpenSSL extension
* Anthropic API key
* RankMath plugin (optional, for SEO features)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/autoblogger/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to AutoBlogger → Settings to configure your API key
4. Import your knowledge base data
5. Start creating AI-powered content!

== Frequently Asked Questions ==

= Do I need an Anthropic API key? =

Yes, you need an Anthropic API key to use the AI content generation features.

= Does this work with RankMath? =

Yes! AutoBlogger integrates seamlessly with RankMath for real-time SEO optimization.

= How much does it cost to use? =

The plugin is free, but you'll need to pay for Anthropic API usage based on your token consumption.

== Changelog ==

= 1.0.5 =
* Fixed settings showing default values after page reload
* Settings now load from server before UI renders
* Improved loading state handling

= 1.0.4 =
* Added settings reload after successful save
* Added comprehensive console logging for debugging
* Fixed settings not reflecting changes after save

= 1.0.3 =
* Fixed REST API routes not registering automatically
* Fixed settings not persisting after save (GET endpoint response format)
* Added lazy-loading for heavy dependencies in REST API
* Improved REST API initialization error handling
* Added comprehensive debug logging

= 1.0.2 =
* Added Global System Prompt feature for consistent AI behavior
* System prompt applies to all content generation requests
* Claude integration uses native `system` parameter
* Gemini integration uses `systemInstruction` parameter
* Added comprehensive default system prompt for Vietnamese astrology content
* Fixed prompt templates not loading in settings UI
* Fixed Import JSON infinite loading issue in Knowledge Base
* Added helpful tooltips to prompt template sections
* Improved UI with system prompt textarea in Content Settings

= 1.0.1 =
* Added Google Gemini 2.5 Flash (latest model)
* Added Google Gemini 2.0 Flash (experimental, free)
* Added Claude 3.5 Haiku (faster, more affordable)
* Updated all model pricing
* Improved model selection UI
* Fixed Babel build issues
* Default model changed to Gemini 2.5 Flash

= 1.0.0 =
* Initial release
* AI content generation
* RankMath integration
* Knowledge base management
* Cost tracking
* E-E-A-T compliance features

== Upgrade Notice ==

= 1.0.3 =
Critical fix for settings persistence and REST API registration!

= 1.0.2 =
Important update! Global System Prompt feature allows you to define AI behavior across all content generation. Includes multiple UI fixes.

= 1.0.1 =
Major update with latest AI models including Gemini 2.5 Flash and Claude 3.5 Haiku!

= 1.0.0 =
Initial release of AutoBlogger.

