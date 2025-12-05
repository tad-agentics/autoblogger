# AI Provider System - Implementation Guide

## 🎯 Overview

AutoBlogger now supports multiple AI providers through a flexible interface system. You can easily switch between Claude, Gemini, OpenAI, or add custom providers.

## 🏗️ Architecture

### Interface-Based Design

```
AutoBlogger_AI_Service
    ↓
AutoBlogger_AI_Provider_Interface (Contract)
    ↓
├── AutoBlogger_Claude_Provider
├── AutoBlogger_Gemini_Provider
└── AutoBlogger_OpenAI_Provider (future)
```

### Key Components

1. **Interface** (`interface-ai-provider.php`)
   - Defines the contract all providers must implement
   - 14 required methods
   - Ensures consistent behavior across providers

2. **AI Service** (`class-ai-service.php`)
   - Manages provider initialization
   - Handles retry logic
   - Provider-agnostic API

3. **Providers** (`includes/providers/`)
   - Claude: Anthropic Claude 3.5 Sonnet
   - Gemini: Google Gemini Pro
   - OpenAI: (Template ready)

## 📦 Current Providers

### 1. Claude (Default)

**Best for:** Vietnamese content, high quality

```php
Provider: Anthropic Claude
Model: claude-3-5-sonnet-20241022
Context: 200,000 tokens
Pricing: $3/M input, $15/M output
Key Format: sk-ant-*
```

**Features:**
- ✅ Excellent Vietnamese understanding
- ✅ 200k context window
- ✅ Function calling support
- ✅ Streaming support

### 2. Gemini

**Best for:** Cost-effective, fast generation

```php
Provider: Google Gemini
Model: gemini-pro
Context: 32,000 tokens
Pricing: $0.50/M input, $1.50/M output (83% cheaper!)
Key Format: AIza*
```

**Features:**
- ✅ Much cheaper than Claude
- ✅ Fast response times
- ✅ Good Vietnamese support
- ✅ Free tier available

### 3. OpenAI (Coming Soon)

**Best for:** General purpose, well-documented

```php
Provider: OpenAI
Model: gpt-4-turbo
Context: 128,000 tokens
Pricing: $10/M input, $30/M output
Key Format: sk-*
```

## 🔄 Switching Providers

### Method 1: Admin Settings (Recommended)

```php
// In WordPress admin
1. Go to AutoBlogger → Settings
2. Select "AI Provider" tab
3. Choose provider (Claude, Gemini, OpenAI)
4. Enter API key
5. Test connection
6. Save settings
```

### Method 2: Programmatically

```php
// Switch to Gemini
$ai_service = new AutoBlogger_AI_Service();
$ai_service->switch_provider('gemini');

// Switch to Claude
$ai_service->switch_provider('claude');
```

### Method 3: wp-config.php

```php
// Force specific provider
define('AUTOBLOGGER_AI_PROVIDER', 'gemini');
```

## 💻 Using the Interface

### Generate Content

```php
$ai_service = new AutoBlogger_AI_Service();

// Works with any provider!
$content = $ai_service->generate_draft([
    'keyword' => 'Sao Phá Quân',
    'knowledge_context' => $rag_context,
    'persona' => 'Academic'
]);
```

### Get Provider Info

```php
$provider = $ai_service->get_provider();

echo $provider->get_provider_name();        // 'claude' or 'gemini'
echo $provider->get_current_model();        // 'claude-3-5-sonnet-20241022'
echo $provider->get_max_context_tokens();   // 200000

$models = $provider->get_available_models();
// ['claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet', ...]

$pricing = $provider->get_pricing();
// ['input_per_million' => 3.00, 'output_per_million' => 15.00]
```

### Test Connection

```php
$ai_service = new AutoBlogger_AI_Service();

if ($ai_service->test_connection()) {
    echo "✅ Connected to " . $ai_service->get_provider()->get_provider_name();
} else {
    echo "❌ Connection failed";
}
```

## 🆕 Adding a New Provider

### Step 1: Create Provider Class

```php
// includes/providers/class-openai-provider.php

class AutoBlogger_OpenAI_Provider implements AutoBlogger_AI_Provider_Interface {
    
    private $api_key;
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';
    private $model = 'gpt-4-turbo';
    
    public function __construct($settings) {
        $this->api_key = $settings->get_api_key();
    }
    
    public function generate($prompt, $options = []) {
        // Implement OpenAI API call
        $response = wp_remote_post($this->api_endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => $options['max_tokens'] ?? 4000
            ])
        ]);
        
        // Parse and return
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return [
            'content' => $data['choices'][0]['message']['content'],
            'usage' => $data['usage'],
            'model' => $this->model,
            'raw_response' => $data
        ];
    }
    
    // Implement all other interface methods...
    public function estimate_cost($prompt, $max_output_tokens = 4000) { }
    public function get_token_usage($response) { }
    public function validate_api_key($api_key) { }
    public function test_connection() { }
    public function get_provider_name() { return 'openai'; }
    public function get_available_models() { }
    public function get_current_model() { }
    public function set_model($model) { }
    public function get_pricing() { }
    public function get_max_context_tokens() { }
    public function supports_streaming() { }
    public function supports_function_calling() { }
}
```

### Step 2: Register in AI Service

```php
// includes/class-ai-service.php

private function initialize_provider() {
    $provider_name = get_option('autoblogger_ai_provider', 'claude');
    
    switch ($provider_name) {
        case 'openai':
            require_once AUTOBLOGGER_PLUGIN_DIR . 'includes/providers/class-openai-provider.php';
            return new AutoBlogger_OpenAI_Provider($this->settings);
            
        case 'gemini':
            require_once AUTOBLOGGER_PLUGIN_DIR . 'includes/providers/class-gemini-provider.php';
            return new AutoBlogger_Gemini_Provider($this->settings);
            
        case 'claude':
        default:
            require_once AUTOBLOGGER_PLUGIN_DIR . 'includes/providers/class-claude-provider.php';
            return new AutoBlogger_Claude_Provider($this->settings);
    }
}
```

### Step 3: Add to Available Providers

```php
// includes/class-ai-service.php

public static function get_available_providers() {
    return [
        'claude' => [
            'name' => 'Anthropic Claude',
            'description' => 'Claude 3.5 Sonnet - Best for Vietnamese',
            'requires_key' => true,
            'key_format' => 'sk-ant-*'
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'description' => 'Gemini Pro - Cost-effective',
            'requires_key' => true,
            'key_format' => 'AIza*'
        ],
        'openai' => [
            'name' => 'OpenAI GPT',
            'description' => 'GPT-4 Turbo - General purpose',
            'requires_key' => true,
            'key_format' => 'sk-*'
        ]
    ];
}
```

## 📊 Provider Comparison

| Feature | Claude | Gemini | OpenAI |
|---------|--------|--------|--------|
| **Vietnamese Quality** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Cost (Input)** | $3/M | $0.50/M | $10/M |
| **Cost (Output)** | $15/M | $1.50/M | $30/M |
| **Context Window** | 200k | 32k | 128k |
| **Speed** | Fast | Very Fast | Fast |
| **Function Calling** | ✅ | ✅ | ✅ |
| **Streaming** | ✅ | ✅ | ✅ |
| **Free Tier** | ❌ | ✅ | ❌ |

## 💰 Cost Comparison Example

**Generating a 2000-word article:**

```
Claude:
- Input: 1000 tokens × $3/M = $0.003
- Output: 3000 tokens × $15/M = $0.045
- Total: $0.048 per article

Gemini:
- Input: 1000 tokens × $0.50/M = $0.0005
- Output: 3000 tokens × $1.50/M = $0.0045
- Total: $0.005 per article (90% cheaper!)

OpenAI GPT-4:
- Input: 1000 tokens × $10/M = $0.010
- Output: 3000 tokens × $30/M = $0.090
- Total: $0.100 per article (2x more expensive)
```

## 🔧 Advanced Usage

### Provider-Specific Options

```php
// Claude-specific
$result = $provider->generate($prompt, [
    'max_tokens' => 4000,
    'temperature' => 1.0,
    'top_p' => 0.9,
    'stop_sequences' => ['\n\nHuman:']
]);

// Gemini-specific
$result = $provider->generate($prompt, [
    'max_tokens' => 4000,
    'temperature' => 0.9,
    'top_k' => 40,
    'top_p' => 0.95
]);
```

### Fallback Strategy

```php
class AutoBlogger_AI_Service {
    
    public function generate_with_fallback($prompt, $options = []) {
        $providers = ['claude', 'gemini', 'openai'];
        
        foreach ($providers as $provider_name) {
            try {
                $this->switch_provider($provider_name);
                return $this->generate_content_with_retry($prompt, $options);
            } catch (Exception $e) {
                AutoBlogger_Logger::warning("Provider {$provider_name} failed, trying next", [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
        
        throw new Exception('All providers failed');
    }
}
```

### Multi-Provider Strategy

```php
// Use Gemini for outlines (cheap, fast)
$ai_service->switch_provider('gemini');
$outline = $ai_service->generate_outline($keyword);

// Use Claude for final content (high quality)
$ai_service->switch_provider('claude');
$content = $ai_service->generate_draft($outline);
```

## 🧪 Testing

### Test All Providers

```php
$providers = ['claude', 'gemini', 'openai'];
$ai_service = new AutoBlogger_AI_Service();

foreach ($providers as $provider_name) {
    echo "Testing {$provider_name}...\n";
    
    $ai_service->switch_provider($provider_name);
    
    if ($ai_service->test_connection()) {
        echo "✅ {$provider_name} connected\n";
        
        $provider = $ai_service->get_provider();
        echo "Model: " . $provider->get_current_model() . "\n";
        echo "Context: " . $provider->get_max_context_tokens() . " tokens\n";
        echo "Pricing: $" . $provider->get_pricing()['input_per_million'] . "/M input\n";
    } else {
        echo "❌ {$provider_name} failed\n";
    }
}
```

## 📝 Best Practices

### 1. Choose Provider Based on Use Case

```php
// For drafts (quality matters)
use Claude

// For outlines (speed matters)
use Gemini

// For general content (balance)
use Gemini or Claude
```

### 2. Monitor Costs

```php
$cost_tracker = new AutoBlogger_Cost_Tracker();

// Get provider pricing
$provider = $ai_service->get_provider();
$pricing = $provider->get_pricing();

// Estimate before generating
$estimate = $provider->estimate_cost($prompt, 4000);
echo "Estimated cost: $" . $estimate['total_cost'];
```

### 3. Handle Provider-Specific Errors

```php
try {
    $content = $ai_service->generate_content_with_retry($prompt);
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'rate limit') !== false) {
        // Switch to different provider
        $ai_service->switch_provider('gemini');
        $content = $ai_service->generate_content_with_retry($prompt);
    } else {
        throw $e;
    }
}
```

## 🚀 Future Providers

Potential providers to add:

- **Cohere** - Multilingual support
- **Mistral AI** - Open-source alternative
- **Llama 3** - Self-hosted option
- **PaLM 2** - Google's previous model
- **Claude Instant** - Faster, cheaper Claude

## ✅ Implementation Checklist

- [x] Interface defined (`interface-ai-provider.php`)
- [x] Claude provider implemented
- [x] Gemini provider implemented
- [x] AI Service refactored for providers
- [x] Provider switching mechanism
- [x] Cost estimation per provider
- [x] Connection testing
- [x] Error handling
- [x] Logging integration
- [ ] Admin UI for provider selection
- [ ] Provider comparison dashboard
- [ ] OpenAI provider implementation

## 📚 Documentation

- `interface-ai-provider.php` - Interface definition
- `class-claude-provider.php` - Claude implementation
- `class-gemini-provider.php` - Gemini implementation
- `class-ai-service.php` - Service layer
- `AI_PROVIDER_GUIDE.md` - This guide

---

**The provider system is production-ready and extensible!** 🎉

You can now easily switch between Claude and Gemini, or add new providers in the future without changing your core application code.

