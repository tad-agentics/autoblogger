# AutoBlogger - Complete Architecture & Planning Document

## Plugin Overview

**Name**: AutoBlogger  
**Version**: 1.0.0  
**Purpose**: Enterprise-grade AI content generation with SEO optimization and E-E-A-T compliance  
**Philosophy**: Complex where necessary, simple everywhere else

## Architecture Principles

✅ **Security First**: Encryption, sanitization, capability checks  
✅ **WordPress-Native**: Use WordPress APIs, don't reinvent the wheel  
✅ **Simple by Default**: Add complexity only when justified  
✅ **Extensible**: Event system for third-party integration  
✅ **Maintainable**: Centralized configuration, logging, and prompt management  

## Two-Interface Design

### Interface 1: Action Interface (Gutenberg Sidebar)
**Location**: Post editor sidebar, Yin-Yang icon  
**Purpose**: Content creation and optimization (90% of daily work)  
**Features**:
- Topic input with keyword field
- Human experience story injection (E-E-A-T)
- One-click actions (Generate Outline, Write Draft, Write Section, Expand)
- Tone selector (Academic, Simple, Custom personas)
- SEO Doctor (real-time score + automated optimization)
- Cost display (real-time estimates + daily budget tracker)
- Content versioning (undo/redo AI changes)
- Auto-save and recovery

### Interface 2: Management Interface (Admin Menu)
**Location**: Top-level "AutoBlogger" menu  
**Purpose**: Configuration and management  
**Sub-menus**:
- **Settings**: API keys, personas, optimization settings, budget limits
- **Knowledge Base**: CRUD with Monaco editor, CSV/JSON import, source tracking
- **Usage Dashboard**: Cost tracking, charts, analytics, export
- **Prompt Management**: Edit AI prompts with placeholders, A/B testing
- **Safety**: Disclaimer text, negative keywords, review workflow

## Complete File Structure

```
autoblogger/
├── autoblogger.php                      # Main plugin file
├── readme.txt                           # WordPress.org readme
├── package.json                         # npm dependencies
├── webpack.config.js                    # Build configuration
├── ARCHITECTURE.md                      # This file
├── .gitignore
├── LICENSE
│
├── includes/
│   ├── class-activator.php              # Activation + migrations
│   ├── class-hooks.php                  # ⭐ Centralized hook registration
│   ├── class-config.php                 # ⭐ Configuration management
│   ├── class-logger.php                 # ⭐ Structured logging
│   ├── class-prompt-manager.php         # ⭐ Prompt template management
│   ├── class-database.php               # Tables + CRUD (simplified caching)
│   ├── class-ai-service.php             # Claude API + retry + encryption
│   ├── class-rag-engine.php             # RAG + JSON slicing
│   ├── class-cost-tracker.php           # Usage tracking + budget
│   ├── class-content-filter.php         # Safety filtering
│   ├── class-post-interceptor.php       # Review workflow + locks + versioning
│   ├── class-error-handler.php          # Centralized errors
│   ├── class-settings.php               # Settings management
│   ├── class-admin.php                  # Admin menu
│   │
│   ├── prompts/                         # ⭐ Prompt templates (editable via admin)
│   │   ├── generate-draft.txt           # Main content generation prompt
│   │   ├── generate-outline.txt         # Outline generation prompt
│   │   ├── optimize-content.txt         # SEO optimization prompt
│   │   ├── expand-text.txt              # Text expansion prompt
│   │   └── generate-section.txt         # Section generation prompt
│   │
│   └── blocks/
│       ├── disclaimer-block/
│       │   ├── block.json
│       │   ├── index.php
│       │   └── style.css
│       └── expert-note-block/
│           ├── block.json
│           ├── edit.jsx
│           ├── save.jsx
│           └── style.css
│
├── editor/                              # Gutenberg sidebar
│   ├── class-gutenberg.php
│   └── js/src/
│       ├── index.js
│       ├── SidebarPanel.jsx
│       ├── services/
│       │   ├── RankMathService.js       # Simplified (no caching)
│       │   ├── CostEstimator.js
│       │   ├── ContentOptimizer.js
│       │   ├── ErrorHandler.js
│       │   └── AutoSave.js
│       └── components/
│           ├── TopicInput.jsx
│           ├── HumanStoryInput.jsx
│           ├── ActionButtons.jsx
│           ├── ToneSelector.jsx
│           ├── SEODoctor.jsx
│           ├── CostDisplay.jsx
│           ├── VersionHistory.jsx       # Reads from post meta
│           ├── ErrorNotice.jsx
│           └── RecoveryModal.jsx
│
├── admin/                               # Admin dashboard
│   └── js/src/
│       ├── index.js
│       ├── SettingsPage.jsx
│       ├── KnowledgeBasePage.jsx
│       ├── UsageDashboard.jsx
│       ├── PromptEditor.jsx             # ⭐ NEW: Edit prompts
│       └── components/
│           ├── CodeEditor.jsx           # Monaco with mobile fallback
│           ├── KnowledgeTable.jsx
│           ├── CostChart.jsx
│           └── PromptTemplateEditor.jsx # ⭐ NEW: Prompt editing UI
│
├── api/
│   └── class-rest-api.php
│
├── assets/
│   ├── icons/
│   │   └── yin-yang.svg
│   └── css/
│       ├── admin-styles.css
│       └── frontend-styles.css
│
└── languages/
    ├── autoblogger.pot
    └── autoblogger-vi.po
```

## Database Schema (2 Tables Only)

### Table 1: `wp_autoblogger_knowledge`
```sql
CREATE TABLE wp_autoblogger_knowledge (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  keyword VARCHAR(255) NOT NULL,
  content LONGTEXT NOT NULL,              -- JSON with sources
  metadata JSON,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  
  FULLTEXT KEY keyword_content (keyword, content),
  INDEX idx_keyword_created (keyword, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**JSON Content Structure**:
```json
{
  "definition": "Sao Phá Quân là...",
  "characteristics": "...",
  "meanings": {
    "money": "Về tài chính...",
    "love": "Về tình duyên...",
    "career": "Về sự nghiệp..."
  },
  "sources": [
    {
      "title": "Tử Vi Đẩu Số Tân Biên",
      "author": "Nguyễn Văn A",
      "year": 2015,
      "type": "book"
    }
  ]
}
```

### Table 2: `wp_autoblogger_usage`
```sql
CREATE TABLE wp_autoblogger_usage (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  operation VARCHAR(50) NOT NULL,         -- 'generate_draft', 'optimize', etc.
  tokens_input INT UNSIGNED NOT NULL,
  tokens_output INT UNSIGNED NOT NULL,
  cost DECIMAL(10, 6) NOT NULL,
  created_at DATETIME NOT NULL,
  
  INDEX idx_user_date (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Note**: Content versioning stored in post meta (no separate table needed)

## NEW: Prompt Management System

### Why Prompt Management?

**Problem**: Hard-coded prompts in PHP code are:
- Hard to modify (requires code deployment)
- Impossible to A/B test
- Not accessible to non-developers
- Can't be hot-fixed in production

**Solution**: Database-backed prompt templates with placeholder system

### Prompt Manager Class

**File**: `includes/class-prompt-manager.php`

```php
<?php
/**
 * Prompt Template Management System
 * Manages AI prompts with placeholder support and hot-reload capability
 */
class AutoBlogger_Prompt_Manager {
    
    private $prompts_dir;
    private $cache_key = 'autoblogger_prompts';
    
    public function __construct() {
        $this->prompts_dir = AUTOBLOGGER_PATH . 'includes/prompts/';
    }
    
    /**
     * Get prompt template by name
     * First checks database (for custom edits), then falls back to file
     * 
     * @param string $template_name Template name (e.g., 'generate-draft')
     * @return string Prompt template with placeholders
     */
    public function get_template($template_name) {
        // Check database first (allows hot-editing from admin)
        $custom_prompt = get_option("autoblogger_prompt_{$template_name}");
        
        if ($custom_prompt !== false && !empty($custom_prompt)) {
            AutoBlogger_Logger::debug("Using custom prompt: {$template_name}");
            return $custom_prompt;
        }
        
        // Fall back to file
        $file_path = $this->prompts_dir . $template_name . '.txt';
        
        if (!file_exists($file_path)) {
            AutoBlogger_Logger::error("Prompt template not found: {$template_name}");
            throw new Exception("Prompt template '{$template_name}' not found");
        }
        
        return file_get_contents($file_path);
    }
    
    /**
     * Render prompt with placeholders replaced
     * 
     * @param string $template_name Template name
     * @param array $data Placeholder data
     * @return string Rendered prompt
     */
    public function render($template_name, $data = []) {
        $template = $this->get_template($template_name);
        
        // Replace placeholders: {{keyword}}, {{context}}, etc.
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            
            // Handle arrays (like sources, context)
            if (is_array($value)) {
                $value = $this->format_array_for_prompt($value);
            }
            
            $template = str_replace($placeholder, $value, $template);
        }
        
        // Check for unreplaced placeholders (helps catch errors)
        if (preg_match('/\{\{([^}]+)\}\}/', $template, $matches)) {
            AutoBlogger_Logger::warning("Unreplaced placeholder in {$template_name}: {$matches[1]}");
        }
        
        // Allow filtering by third-party plugins
        $template = apply_filters('autoblogger_prompt_rendered', $template, $template_name, $data);
        
        return $template;
    }
    
    /**
     * Format array data for prompt insertion
     */
    private function format_array_for_prompt($array) {
        if (empty($array)) {
            return '';
        }
        
        // If array of strings, join with newlines
        if (isset($array[0]) && is_string($array[0])) {
            return implode("\n", $array);
        }
        
        // If associative array or objects, format as bullet points
        $formatted = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $formatted[] = "- " . json_encode($value);
            } else {
                $formatted[] = "- {$key}: {$value}";
            }
        }
        
        return implode("\n", $formatted);
    }
    
    /**
     * Save custom prompt to database (hot-edit from admin)
     */
    public function save_custom_prompt($template_name, $prompt_content) {
        $sanitized = wp_kses_post($prompt_content);
        update_option("autoblogger_prompt_{$template_name}", $sanitized);
        
        AutoBlogger_Logger::info("Custom prompt saved: {$template_name}");
        
        // Fire event for cache clearing, etc.
        do_action('autoblogger_prompt_updated', $template_name, $sanitized);
    }
    
    /**
     * Reset prompt to default (from file)
     */
    public function reset_to_default($template_name) {
        delete_option("autoblogger_prompt_{$template_name}");
        
        AutoBlogger_Logger::info("Prompt reset to default: {$template_name}");
    }
    
    /**
     * Get all available prompts
     */
    public function get_all_templates() {
        $files = glob($this->prompts_dir . '*.txt');
        $templates = [];
        
        foreach ($files as $file) {
            $name = basename($file, '.txt');
            $templates[$name] = [
                'name' => $name,
                'file_path' => $file,
                'has_custom' => get_option("autoblogger_prompt_{$name}") !== false,
                'default_content' => file_get_contents($file)
            ];
        }
        
        return $templates;
    }
    
    /**
     * Get available placeholders for a template
     */
    public function get_placeholders($template_name) {
        $template = $this->get_template($template_name);
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        
        return array_unique($matches[1]);
    }
}
```

### Default Prompt Templates

**File**: `includes/prompts/generate-draft.txt`

```
You are an expert content writer specializing in {{topic_domain}}.

TASK: Write a comprehensive article about "{{keyword}}"

KNOWLEDGE BASE:
{{knowledge_context}}

OUTLINE:
{{outline}}

CITATION REQUIREMENTS:
Every factual claim must include source attribution in parentheses.
Available sources:
{{sources}}

Example: "Sao Phá Quân belongs to Water element (According to Tử Vi Đẩu Số Tân Biên)..."

{{#if human_story}}
REAL-WORLD EXPERIENCE:
Weave this authentic experience into the article:
"{{human_story}}"

Present it in first-person perspective ("I observed...") and analyze why this case validates the theory.
{{/if}}

WRITING STYLE: {{persona}}

REQUIREMENTS:
- Write in Vietnamese
- Use clear, engaging language
- Include practical examples
- Maintain consistent tone throughout
- Aim for 1500-2000 words
- Use proper heading structure (H2, H3)

Begin writing:
```

**File**: `includes/prompts/generate-outline.txt`

```
Create a detailed outline for an article about "{{keyword}}"

KNOWLEDGE BASE:
{{knowledge_context}}

REQUIREMENTS:
- Create 5-7 main sections (H2 headings)
- Each main section should have 2-3 subsections (H3 headings)
- Use Vietnamese
- Follow logical flow: Introduction → Main Content → Practical Application → Conclusion
- Make headings descriptive and keyword-rich

FORMAT:
## Main Section Title
### Subsection 1
### Subsection 2

Output only the outline:
```

**File**: `includes/prompts/optimize-content.txt`

```
TASK: Optimize the following content to fix SEO issues

CURRENT CONTENT:
{{content}}

FOCUS KEYWORD: {{keyword}}

SEO ISSUES DETECTED:
{{seo_issues}}

OPTIMIZATION REQUIREMENTS:
1. Address each issue listed above
2. Maintain the original meaning and flow
3. Keep the writing style: {{persona}}
4. Do NOT add new sections, only improve existing content
5. Ensure keyword appears naturally (avoid keyword stuffing)
6. Improve readability where possible

Output only the optimized content:
```

**File**: `includes/prompts/expand-text.txt`

```
Expand and enhance the following text while maintaining its core message:

ORIGINAL TEXT:
{{text}}

REQUIREMENTS:
- Expand to approximately {{target_length}} words
- Add relevant details and examples
- Maintain writing style: {{persona}}
- Keep the same tone and perspective
- Use Vietnamese

Output only the expanded text:
```

**File**: `includes/prompts/generate-section.txt`

```
Write content for the following section:

SECTION HEADING: {{heading}}

CONTEXT (previous sections):
{{context}}

KNOWLEDGE BASE:
{{knowledge_context}}

REQUIREMENTS:
- Write 200-300 words
- Writing style: {{persona}}
- Include practical examples
- Use Vietnamese
- Maintain flow with previous sections

Output only the section content:
```

### Updated AI Service (Using Prompt Manager)

**File**: `includes/class-ai-service.php`

```php
class AutoBlogger_AI_Service {
    private $api_key;
    private $cost_tracker;
    private $prompt_manager;
    private $max_retries = 3;
    
    public function __construct() {
        $this->api_key = $this->get_decrypted_api_key();
        $this->cost_tracker = new AutoBlogger_Cost_Tracker();
        $this->prompt_manager = new AutoBlogger_Prompt_Manager();
    }
    
    /**
     * Generate draft content using prompt template
     */
    public function generate_draft($keyword, $outline, $persona, $rag_context, $human_story = '') {
        // Prepare data for prompt
        $prompt_data = [
            'keyword' => $keyword,
            'outline' => $outline,
            'persona' => $persona,
            'knowledge_context' => $rag_context,
            'human_story' => $human_story,
            'sources' => $this->extract_sources($rag_context),
            'topic_domain' => 'Vietnamese astrology' // Could be dynamic
        ];
        
        // Render prompt from template
        $prompt = $this->prompt_manager->render('generate-draft', $prompt_data);
        
        AutoBlogger_Logger::debug('Draft generation prompt rendered', [
            'keyword' => $keyword,
            'prompt_length' => strlen($prompt)
        ]);
        
        // Generate with retry
        return $this->generate_content_with_retry($prompt);
    }
    
    /**
     * Generate outline using prompt template
     */
    public function generate_outline($keyword, $rag_context) {
        $prompt_data = [
            'keyword' => $keyword,
            'knowledge_context' => $rag_context
        ];
        
        $prompt = $this->prompt_manager->render('generate-outline', $prompt_data);
        
        return $this->generate_content_with_retry($prompt, 1000);
    }
    
    /**
     * Optimize content for SEO using prompt template
     */
    public function optimize_content($content, $seo_issues, $keyword, $persona) {
        $prompt_data = [
            'content' => $content,
            'seo_issues' => $seo_issues,
            'keyword' => $keyword,
            'persona' => $persona
        ];
        
        $prompt = $this->prompt_manager->render('optimize-content', $prompt_data);
        
        return $this->generate_content_with_retry($prompt);
    }
    
    // ... rest of methods (retry logic, encryption, etc.)
}
```

### Prompt Editor Admin Page

**File**: `admin/js/src/PromptEditor.jsx`

```jsx
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, SelectControl, TextareaControl, Notice, Panel, PanelBody } from '@wordpress/components';
import CodeEditor from './components/CodeEditor';

const PromptEditor = () => {
  const [templates, setTemplates] = useState([]);
  const [selectedTemplate, setSelectedTemplate] = useState('');
  const [promptContent, setPromptContent] = useState('');
  const [defaultContent, setDefaultContent] = useState('');
  const [placeholders, setPlaceholders] = useState([]);
  const [hasChanges, setHasChanges] = useState(false);
  const [saveStatus, setSaveStatus] = useState(null);
  
  // Load templates on mount
  useEffect(() => {
    wp.apiFetch({ path: '/autoblogger/v1/prompts' })
      .then(data => {
        setTemplates(data);
        if (data.length > 0) {
          setSelectedTemplate(data[0].name);
        }
      });
  }, []);
  
  // Load selected template
  useEffect(() => {
    if (!selectedTemplate) return;
    
    wp.apiFetch({ path: `/autoblogger/v1/prompts/${selectedTemplate}` })
      .then(data => {
        setPromptContent(data.content);
        setDefaultContent(data.default_content);
        setPlaceholders(data.placeholders);
        setHasChanges(false);
      });
  }, [selectedTemplate]);
  
  const handleSave = async () => {
    try {
      await wp.apiFetch({
        path: `/autoblogger/v1/prompts/${selectedTemplate}`,
        method: 'POST',
        data: { content: promptContent }
      });
      
      setSaveStatus('success');
      setHasChanges(false);
      setTimeout(() => setSaveStatus(null), 3000);
    } catch (error) {
      setSaveStatus('error');
    }
  };
  
  const handleReset = () => {
    if (confirm(__('Reset to default prompt? This cannot be undone.', 'autoblogger'))) {
      setPromptContent(defaultContent);
      setHasChanges(true);
    }
  };
  
  const handlePreview = () => {
    // Show preview with sample data
    const sampleData = {
      keyword: 'Sao Phá Quân',
      persona: 'Academic',
      // ... more sample data
    };
    
    // Render preview (would call API to render with sample data)
  };
  
  return (
    <div className="prompt-editor">
      <h1>{__('Prompt Management', 'autoblogger')}</h1>
      
      <div className="prompt-editor-header">
        <SelectControl
          label={__('Select Template', 'autoblogger')}
          value={selectedTemplate}
          options={templates.map(t => ({
            label: t.name.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
            value: t.name
          }))}
          onChange={setSelectedTemplate}
        />
        
        {hasChanges && (
          <Notice status="warning" isDismissible={false}>
            {__('You have unsaved changes', 'autoblogger')}
          </Notice>
        )}
        
        {saveStatus === 'success' && (
          <Notice status="success" isDismissible>
            {__('Prompt saved successfully!', 'autoblogger')}
          </Notice>
        )}
        
        {saveStatus === 'error' && (
          <Notice status="error" isDismissible>
            {__('Failed to save prompt', 'autoblogger')}
          </Notice>
        )}
      </div>
      
      <div className="prompt-editor-content">
        <Panel>
          <PanelBody title={__('Available Placeholders', 'autoblogger')} initialOpen={true}>
            <div className="placeholders-list">
              {placeholders.map(placeholder => (
                <code key={placeholder} className="placeholder-tag">
                  {`{{${placeholder}}}`}
                </code>
              ))}
            </div>
            <p className="help-text">
              {__('Use these placeholders in your prompt. They will be replaced with actual data.', 'autoblogger')}
            </p>
          </PanelBody>
        </Panel>
        
        <div className="prompt-editor-textarea">
          <label>{__('Prompt Template', 'autoblogger')}</label>
          <TextareaControl
            value={promptContent}
            onChange={(value) => {
              setPromptContent(value);
              setHasChanges(true);
            }}
            rows={20}
            className="prompt-textarea"
          />
        </div>
        
        <div className="prompt-editor-actions">
          <Button 
            isPrimary 
            onClick={handleSave}
            disabled={!hasChanges}
          >
            {__('Save Changes', 'autoblogger')}
          </Button>
          
          <Button 
            isSecondary 
            onClick={handleReset}
          >
            {__('Reset to Default', 'autoblogger')}
          </Button>
          
          <Button 
            isTertiary 
            onClick={handlePreview}
          >
            {__('Preview with Sample Data', 'autoblogger')}
          </Button>
        </div>
      </div>
      
      <Panel>
        <PanelBody title={__('Tips for Prompt Engineering', 'autoblogger')} initialOpen={false}>
          <ul>
            <li>{__('Be specific about the task and expected output format', 'autoblogger')}</li>
            <li>{__('Use clear section headers (TASK, REQUIREMENTS, etc.)', 'autoblogger')}</li>
            <li>{__('Provide examples of desired output', 'autoblogger')}</li>
            <li>{__('Test changes with "Preview" before saving', 'autoblogger')}</li>
            <li>{__('Use placeholders for dynamic content', 'autoblogger')}</li>
          </ul>
        </PanelBody>
      </Panel>
    </div>
  );
};

export default PromptEditor;
```

### REST API Endpoints for Prompts

**In**: `api/class-rest-api.php`

```php
// Get all prompt templates
register_rest_route('autoblogger/v1', '/prompts', [
    'methods' => 'GET',
    'callback' => [$this, 'get_prompts'],
    'permission_callback' => [$this, 'check_admin_permission']
]);

// Get specific prompt
register_rest_route('autoblogger/v1', '/prompts/(?P<name>[a-z-]+)', [
    'methods' => 'GET',
    'callback' => [$this, 'get_prompt'],
    'permission_callback' => [$this, 'check_admin_permission']
]);

// Save custom prompt
register_rest_route('autoblogger/v1', '/prompts/(?P<name>[a-z-]+)', [
    'methods' => 'POST',
    'callback' => [$this, 'save_prompt'],
    'permission_callback' => [$this, 'check_admin_permission'],
    'args' => [
        'content' => ['required' => true, 'sanitize_callback' => 'wp_kses_post']
    ]
]);

public function get_prompts($request) {
    $prompt_manager = new AutoBlogger_Prompt_Manager();
    return $prompt_manager->get_all_templates();
}

public function get_prompt($request) {
    $name = $request['name'];
    $prompt_manager = new AutoBlogger_Prompt_Manager();
    
    return [
        'name' => $name,
        'content' => $prompt_manager->get_template($name),
        'default_content' => file_get_contents(AUTOBLOGGER_PATH . "includes/prompts/{$name}.txt"),
        'placeholders' => $prompt_manager->get_placeholders($name)
    ];
}

public function save_prompt($request) {
    $name = $request['name'];
    $content = $request['content'];
    
    $prompt_manager = new AutoBlogger_Prompt_Manager();
    $prompt_manager->save_custom_prompt($name, $content);
    
    return ['success' => true];
}

private function check_admin_permission() {
    return current_user_can('manage_options');
}
```

## Benefits of Prompt Management System

1. **Hot-Reload**: Edit prompts from admin without code deployment
2. **A/B Testing**: Easy to test different prompt variations
3. **Version Control**: Default prompts in files, custom edits in database
4. **Extensibility**: Third-party plugins can add their own prompts
5. **Maintainability**: Prompts separated from business logic
6. **Collaboration**: Non-developers can improve prompts
7. **Debugging**: Easy to see exactly what prompt was sent to AI

## Complete Workflow with Prompt Management

1. **Initial Setup**:
   - Default prompts loaded from `includes/prompts/*.txt`
   - Admin can edit via "AutoBlogger → Prompt Management"
   
2. **Content Generation**:
   - User clicks "Generate Draft"
   - System loads prompt template (database first, then file)
   - Replaces placeholders with actual data
   - Sends to Claude API
   
3. **Prompt Optimization**:
   - Admin notices content quality issues
   - Goes to Prompt Management
   - Edits prompt, adds more specific instructions
   - Saves (stored in database)
   - Next generation uses new prompt immediately
   
4. **A/B Testing**:
   - Admin can save multiple versions
   - Test which prompt produces better content
   - Reset to default if needed

## Configuration (Updated)

**File**: `includes/class-config.php`

```php
// Prompt Configuration
const PROMPT_CACHE_TTL = 3600; // Cache rendered prompts for 1 hour
const PROMPT_MAX_LENGTH = 50000; // Max prompt length in characters
const PROMPT_DEFAULT_PERSONA = 'Academic';
```

## Testing Checklist (Updated)

**Prompt Management**:
- [ ] Default prompts load from files
- [ ] Custom prompts save to database
- [ ] Placeholders replaced correctly
- [ ] Reset to default works
- [ ] Prompt editor UI functional
- [ ] Preview with sample data works
- [ ] Third-party can filter prompts

**Integration**:
- [ ] AI Service uses Prompt Manager
- [ ] All generation methods use templates
- [ ] Logging shows which prompt used
- [ ] Events fire on prompt updates

## WordPress Options (Updated)

- `autoblogger_prompt_generate-draft`: Custom draft generation prompt
- `autoblogger_prompt_generate-outline`: Custom outline prompt
- `autoblogger_prompt_optimize-content`: Custom optimization prompt
- `autoblogger_prompt_expand-text`: Custom expansion prompt
- `autoblogger_prompt_generate-section`: Custom section prompt

## Summary of Changes

### Added:
1. ✅ `class-prompt-manager.php` - Prompt template management
2. ✅ `includes/prompts/` folder - Default prompt templates (5 files)
3. ✅ `PromptEditor.jsx` - Admin UI for editing prompts
4. ✅ REST API endpoints for prompt CRUD
5. ✅ Placeholder system (`{{keyword}}`, `{{context}}`, etc.)
6. ✅ Database storage for custom prompts (hot-reload)
7. ✅ Reset to default functionality

### Benefits:
- ✅ No hard-coded prompts in PHP
- ✅ Hot-reload without deployment
- ✅ A/B testing capability
- ✅ Non-developer friendly
- ✅ Version control (files + database)
- ✅ Extensible via filters

## Final Architecture Assessment

**Complexity**: 6.5/10 (slightly increased due to prompt management, but justified)  
**Maintainability**: 9/10 (excellent - prompts separated from code)  
**Extensibility**: 9/10 (event system + prompt filters)  
**Production-Ready**: ✅ Yes

---

**Status**: Complete architecture with all critical features  
**Ready for**: Implementation

**Next Steps**:
1. Review and approve architecture
2. Begin implementation following todo list
3. Test each component as built
4. Deploy to staging environment
5. User acceptance testing
6. Production deployment

