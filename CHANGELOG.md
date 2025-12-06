# Changelog

All notable changes to AutoBlogger will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2024-12-06

### Added
- **Google Gemini 2.5 Flash** - Latest and most capable Gemini model (now default)
- **Google Gemini 2.0 Flash** - Experimental model (FREE during beta phase)
- **Claude 3.5 Haiku** - Faster, more affordable Claude model
- Model-specific pricing for accurate cost tracking
- Improved model selection dropdown in admin settings
- Extended context window support (up to 2M tokens for Gemini Pro)

### Changed
- Default AI model changed from `gemini-1.5-flash-002` to `gemini-2.5-flash-latest`
- Updated pricing for all AI models to reflect December 2024 rates
- Reorganized model list with clear labels (Latest, Experimental, Legacy)
- Enhanced AI provider documentation with latest models

### Updated
- Gemini Provider: Added 6 new model options
- Claude Provider: Added Claude 3.5 Haiku model
- Settings UI: Better model descriptions and recommendations
- Documentation: Comprehensive comparison of all available models

### Technical Details

#### New Gemini Models
- `gemini-2.5-flash-latest` - $0.075/M input, $0.30/M output, 1M context
- `gemini-2.0-flash-exp` - FREE (experimental), 1M context
- Model-specific context windows and pricing

#### New Claude Models
- `claude-3-5-haiku-20241022` - $1/M input, $5/M output, 200k context

#### Pricing Updates
All providers now use model-specific pricing for accurate cost estimation:
- Gemini 2.5 Flash: 98% cheaper than Claude Sonnet
- Claude 3.5 Haiku: 67% cheaper than Claude Sonnet
- Gemini 2.0 Flash: 100% FREE during experimental phase

## [1.0.0] - 2024-12-05

### Added
- Initial release
- AI-powered content generation
- Multi-provider support (Claude, Gemini)
- RankMath SEO integration
- Knowledge base management (RAG)
- Cost tracking and budget management
- Content versioning and recovery
- Safety filtering and E-E-A-T compliance
- Multi-language support (Vietnamese)
- Editor integration with Gutenberg
- Custom blocks (Disclaimer, Expert Note)
- Automatic content optimization
- Real-time SEO scoring
- API key encryption
- Comprehensive logging system

### Features
- Claude 3.5 Sonnet integration
- Gemini 1.5 Pro/Flash integration
- Provider switching capability
- Token usage tracking
- Daily budget limits
- Content filter system
- Prompt template manager
- Database optimization
- REST API endpoints
- Admin dashboard
- Usage statistics

---

## Upgrade Guide

### From 1.0.0 to 1.0.1

This is a minor update that adds new AI models. No breaking changes.

**What's New:**
1. New AI models are automatically available in Settings
2. Default model upgraded to Gemini 2.5 Flash (better performance)
3. Existing configurations remain unchanged

**Recommended Actions:**
1. Go to **AutoBlogger → Settings → API Settings**
2. Try the new **Gemini 2.5 Flash** model (recommended)
3. Or try **Gemini 2.0 Flash** for FREE experimental access
4. Review updated pricing in the cost tracker

**No action required** - your existing settings and content are fully compatible.

---

## Version Support

| Version | Status | Support Until | PHP | WordPress |
|---------|--------|---------------|-----|-----------|
| 1.0.1 | Current | Active | 7.4+ | 6.0+ |
| 1.0.0 | Supported | 2024-12-31 | 7.4+ | 6.0+ |

---

For detailed information about AI models and pricing, see [AI_PROVIDERS.md](docs/AI_PROVIDERS.md)

