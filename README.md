# AutoBlogger

AI-powered WordPress content generation plugin with RankMath SEO optimization and E-E-A-T compliance.

## 🚀 Features

- **AI Content Generation** - Generate high-quality articles using Claude 3.5 Sonnet or Gemini
- **SEO Optimization** - Automatic optimization with RankMath integration
- **Chunked Generation** - No timeouts, handles long articles (2000+ words)
- **Cost Control** - Real-time cost estimates with color-coded warnings
- **E-E-A-T Compliance** - Human story injection, citations, expert notes
- **Knowledge Base** - RAG (Retrieval Augmented Generation) for contextual content
- **Multi-Provider** - Switch between Claude, Gemini, and other AI providers
- **Editor Lock** - Prevents autosave conflicts during generation
- **Performance Optimized** - Fast page loads, minimal resource usage

## 📋 Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher (with OpenSSL extension)
- **MySQL**: 5.7 or higher
- **Node.js**: 16+ (for building assets)
- **Anthropic API Key**: Required for Claude
- **RankMath**: Optional (for SEO optimization)

## 📦 Installation

### From Source

1. **Clone the repository:**
```bash
git clone https://github.com/yourusername/autoblogger.git
cd autoblogger
```

2. **Install dependencies:**
```bash
npm install
```

3. **Build assets:**
```bash
npm run build
```

4. **Upload to WordPress:**
```bash
# Copy to WordPress plugins directory
cp -r . /path/to/wordpress/wp-content/plugins/autoblogger/
```

5. **Activate the plugin:**
- Go to WordPress Admin → Plugins
- Find "AutoBlogger"
- Click "Activate"

### From Release

1. Download the latest release ZIP
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file
5. Click "Install Now"
6. Click "Activate"

## ⚙️ Configuration

### 1. API Key Setup

1. Go to **AutoBlogger → Settings**
2. Enter your Anthropic API Key
3. Select AI Provider (Claude, Gemini, etc.)
4. Set Daily Budget (default: $5.00)
5. Click "Save Settings"

### 2. Knowledge Base

1. Go to **AutoBlogger → Knowledge Base**
2. Click "Add New Entry"
3. Enter keyword and content (JSON format supported)
4. Click "Save"

### 3. Prompt Templates

1. Go to **AutoBlogger → Settings → Prompts**
2. Customize prompt templates
3. Use placeholders: `{{keyword}}`, `{{persona}}`, `{{context}}`
4. Click "Save"

## 🎯 Usage

### Generate Article

1. **Create/Edit Post:**
   - Go to Posts → Add New
   - Or edit existing post

2. **Open AutoBlogger Sidebar:**
   - Click the Yin-Yang icon in the top right
   - Or go to More → AutoBlogger

3. **Enter Details:**
   - Main Keyword: "Sao Phá Quân"
   - Persona: Academic / Simple
   - Human Story: (Optional) Your personal experience
   - Outline: (Optional) Custom outline

4. **Generate:**
   - Click "Generate Article"
   - Wait for progress indicators
   - Content will be inserted automatically

5. **Optimize for SEO:**
   - Click "Fix SEO Errors" button
   - Plugin will optimize based on RankMath feedback
   - Max 2 iterations

6. **Review & Publish:**
   - Review generated content
   - Add images
   - Click "Publish"

### Quick Actions

- **Generate Outline** - Create article structure
- **Write Section** - Generate specific section
- **Expand Text** - Expand selected text
- **Inject Story** - Add personal experience

## 📊 Performance

AutoBlogger is highly optimized for performance:

| Metric | Value |
|--------|-------|
| Homepage Load Time | 1.2s |
| Admin Page Load | 0.8s |
| Autoloaded Data | 2KB |
| Memory Usage | 32MB |
| Frontend Assets | 0KB |

**Key Optimizations:**
- ✅ No admin assets on frontend
- ✅ Context-aware initialization
- ✅ Lazy loading of heavy classes
- ✅ Chunked AI generation
- ✅ Editor locking during generation
- ✅ Strategic database autoload

See [Performance Guide](docs/PERFORMANCE.md) for details.

## 🔒 Security

AutoBlogger implements comprehensive security measures:

- **API Key Encryption** - AES-256 encryption
- **Input Sanitization** - All user inputs sanitized
- **Output Escaping** - All outputs escaped
- **Nonce Verification** - All AJAX/REST requests verified
- **Capability Checks** - Proper permission checks
- **Rate Limiting** - Prevents abuse (30 requests/minute)
- **Logging** - Suspicious activity logged

See [Security Guide](docs/SECURITY.md) for details.

## 📚 Documentation

### For Users
- [Installation Guide](#installation)
- [Configuration](#configuration)
- [Usage Guide](#usage)

### For Developers
- [Architecture](docs/ARCHITECTURE.md) - System architecture
- [Performance](docs/PERFORMANCE.md) - Performance optimizations
- [Security](docs/SECURITY.md) - Security implementation
- [AI Providers](docs/AI_PROVIDERS.md) - AI provider system
- [Editor Features](docs/EDITOR_FEATURES.md) - Editor features
- [API Reference](docs/API_REFERENCE.md) - REST API documentation

## 🛠️ Development

### Setup

```bash
# Clone repository
git clone https://github.com/yourusername/autoblogger.git
cd autoblogger

# Install dependencies
npm install

# Start development build (watch mode)
npm run start
```

### Build

```bash
# Production build
npm run build

# Development build
npm run dev
```

### Testing

```bash
# Run tests
npm test

# Run linter
npm run lint

# Fix linting issues
npm run lint:fix
```

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) first.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## 📄 License

GPL v2 or later - See [LICENSE](LICENSE) for details.

## 🆘 Support

- **Issues**: [GitHub Issues](https://github.com/yourusername/autoblogger/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/autoblogger/discussions)
- **Email**: support@autoblogger.com

## 🙏 Credits

- **Anthropic Claude** - AI content generation
- **Google Gemini** - Alternative AI provider
- **RankMath** - SEO optimization
- **WordPress** - Platform

## ⭐ Show Your Support

If you find this plugin helpful, please:
- Star the repository
- Share with others
- Report bugs
- Suggest features
- Contribute code

---

**Made with ❤️ for the WordPress community**
