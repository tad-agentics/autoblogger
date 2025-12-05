<?php
/**
 * AI Service with provider abstraction
 * Supports multiple AI providers through interface
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_AI_Service {
    
    private $provider;
    private $settings;
    private $prompt_manager;
    
    public function __construct() {
        $this->settings = new AutoBlogger_Settings();
        $this->prompt_manager = new AutoBlogger_Prompt_Manager();
        $this->provider = $this->initialize_provider();
    }
    
    /**
     * Initialize AI provider based on settings
     *
     * @return AutoBlogger_AI_Provider_Interface
     */
    private function initialize_provider() {
        $provider_name = get_option('autoblogger_ai_provider', 'claude');
        
        switch ($provider_name) {
            case 'gemini':
                if (class_exists('AutoBlogger_Gemini_Provider')) {
                    return new AutoBlogger_Gemini_Provider($this->settings);
                }
                break;
                
            case 'openai':
                if (class_exists('AutoBlogger_OpenAI_Provider')) {
                    return new AutoBlogger_OpenAI_Provider($this->settings);
                }
                break;
                
            case 'claude':
            default:
                if (class_exists('AutoBlogger_Claude_Provider')) {
                    return new AutoBlogger_Claude_Provider($this->settings);
                }
                break;
        }
        
        // Fallback to Claude if selected provider doesn't exist
        if (class_exists('AutoBlogger_Claude_Provider')) {
            return new AutoBlogger_Claude_Provider($this->settings);
        }
        
        // If no provider available, throw error
        throw new Exception('No AI provider available. Please check plugin installation.');
    }
    
    /**
     * Get current provider
     *
     * @return AutoBlogger_AI_Provider_Interface
     */
    public function get_provider() {
        return $this->provider;
    }
    
    /**
     * Generate content with retry logic
     *
     * @param string $prompt Prompt text
     * @param int $max_tokens Maximum output tokens
     * @return string Generated content
     * @throws Exception On API error
     */
    public function generate_content_with_retry($prompt, $max_tokens = 4000) {
        // Fire before generation
        do_action('autoblogger_before_api_call', $prompt, $max_tokens);
        
        $max_retries = AutoBlogger_Config::get('api_max_retries');
        $retry_delay_base = AutoBlogger_Config::get('api_retry_delay_base');
        
        for ($attempt = 0; $attempt <= $max_retries; $attempt++) {
            try {
                $result = $this->provider->generate($prompt, [
                    'max_tokens' => $max_tokens
                ]);
                
                $content = $result['content'];
                
                // Fire after successful generation
                do_action('autoblogger_after_api_call', $content, $prompt);
                
                return $content;
                
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                
                // Check if we should retry
                if ($attempt < $max_retries && $this->should_retry($error_message)) {
                    // Exponential backoff: 2^attempt * base delay
                    $delay = pow($retry_delay_base, $attempt + 1);
                    
                    AutoBlogger_Logger::warning("API call failed, retrying in {$delay}s", [
                        'provider' => $this->provider->get_provider_name(),
                        'attempt' => $attempt + 1,
                        'max_retries' => $max_retries,
                        'error' => $error_message
                    ]);
                    
                    sleep($delay);
                    continue;
                }
                
                // Max retries reached or non-retryable error
                do_action('autoblogger_api_call_failed', $error_message, $prompt);
                
                AutoBlogger_Logger::error('API call failed after retries', [
                    'provider' => $this->provider->get_provider_name(),
                    'attempts' => $attempt + 1,
                    'error' => $error_message
                ]);
                
                throw $e;
            }
        }
    }
    
    /**
     * Check if error should trigger retry
     *
     * @param string $error_message Error message
     * @return bool True if should retry
     */
    private function should_retry($error_message) {
        $retryable_errors = [
            'rate limit',
            'timeout',
            'service unavailable',
            'connection',
            '500',
            '503',
            '429'
        ];
        
        $error_lower = strtolower($error_message);
        
        foreach ($retryable_errors as $retryable) {
            if (strpos($error_lower, $retryable) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate draft using template
     *
     * @param array $data Template data
     * @return string Generated content
     */
    public function generate_draft($data) {
        $prompt = $this->prompt_manager->render('generate-draft', $data);
        return $this->generate_content_with_retry($prompt);
    }
    
    /**
     * Generate outline using template
     *
     * @param array $data Template data
     * @return string Generated outline
     */
    public function generate_outline($data) {
        $prompt = $this->prompt_manager->render('generate-outline', $data);
        return $this->generate_content_with_retry($prompt, 2000);
    }
    
    /**
     * Optimize content using template
     *
     * @param array $data Template data
     * @return string Optimized content
     */
    public function optimize_content($data) {
        $prompt = $this->prompt_manager->render('optimize-content', $data);
        return $this->generate_content_with_retry($prompt);
    }
    
    /**
     * Expand text using template
     *
     * @param array $data Template data
     * @return string Expanded text
     */
    public function expand_text($data) {
        $prompt = $this->prompt_manager->render('expand-text', $data);
        return $this->generate_content_with_retry($prompt, 2000);
    }
    
    /**
     * Generate section using template
     *
     * @param array $data Template data
     * @return string Generated section
     */
    public function generate_section($data) {
        $prompt = $this->prompt_manager->render('generate-section', $data);
        return $this->generate_content_with_retry($prompt, 1500);
    }
    
    /**
     * Get actual token usage from last response
     *
     * @param mixed $response API response
     * @return array Token usage
     */
    public function extract_token_usage($response) {
        return $this->provider->get_token_usage($response);
    }
    
    /**
     * Test API connection
     *
     * @return bool True if connection successful
     */
    public function test_connection() {
        try {
            return $this->provider->test_connection();
        } catch (Exception $e) {
            AutoBlogger_Logger::error('API connection test failed', [
                'provider' => $this->provider->get_provider_name(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get available providers
     *
     * @return array List of available providers
     */
    public static function get_available_providers() {
        return [
            'claude' => [
                'name' => 'Anthropic Claude',
                'description' => 'Claude 3.5 Sonnet - Best for Vietnamese content',
                'requires_key' => true,
                'key_format' => 'sk-ant-*'
            ],
            'gemini' => [
                'name' => 'Google Gemini',
                'description' => 'Gemini Pro - Google\'s AI model',
                'requires_key' => true,
                'key_format' => 'AIza*'
            ],
            'openai' => [
                'name' => 'OpenAI GPT',
                'description' => 'GPT-4 - OpenAI\'s flagship model',
                'requires_key' => true,
                'key_format' => 'sk-*'
            ]
        ];
    }
    
    /**
     * Switch to different provider
     *
     * @param string $provider_name Provider name
     * @return bool True on success
     */
    public function switch_provider($provider_name) {
        update_option('autoblogger_ai_provider', $provider_name);
        $this->provider = $this->initialize_provider();
        
        AutoBlogger_Logger::info('AI provider switched', [
            'provider' => $provider_name
        ]);
        
        return true;
    }
}

