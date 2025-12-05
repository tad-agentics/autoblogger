<?php
/**
 * Prompt Template Management System
 * Manages AI prompts with placeholder support and hot-reload capability
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

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
     *
     * @param array $array Array to format
     * @return string Formatted string
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
                $formatted[] = "- " . wp_json_encode($value);
            } else {
                $formatted[] = "- {$key}: {$value}";
            }
        }
        
        return implode("\n", $formatted);
    }
    
    /**
     * Save custom prompt to database (hot-edit from admin)
     *
     * @param string $template_name Template name
     * @param string $prompt_content Prompt content
     * @return bool True on success
     */
    public function save_custom_prompt($template_name, $prompt_content) {
        $sanitized = wp_kses_post($prompt_content);
        // Prompts can be large (1-2KB) and are only needed during AI generation - autoload=false
        $result = update_option("autoblogger_prompt_{$template_name}", $sanitized, false);
        
        AutoBlogger_Logger::info("Custom prompt saved: {$template_name}");
        
        // Fire event for cache clearing, etc.
        do_action('autoblogger_prompt_updated', $template_name, $sanitized);
        
        return $result;
    }
    
    /**
     * Reset prompt to default (from file)
     *
     * @param string $template_name Template name
     * @return bool True on success
     */
    public function reset_to_default($template_name) {
        $result = delete_option("autoblogger_prompt_{$template_name}");
        
        AutoBlogger_Logger::info("Prompt reset to default: {$template_name}");
        
        return $result;
    }
    
    /**
     * Get all available prompts
     *
     * @return array Array of prompt templates
     */
    public function get_all_templates() {
        $files = glob($this->prompts_dir . '*.txt');
        $templates = [];
        
        if (!$files) {
            return $templates;
        }
        
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
     *
     * @param string $template_name Template name
     * @return array Array of placeholder names
     */
    public function get_placeholders($template_name) {
        try {
            $template = $this->get_template($template_name);
            preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
            
            return array_unique($matches[1]);
        } catch (Exception $e) {
            return [];
        }
    }
}

