<?php
/**
 * Anthropic Claude Provider
 * Implementation of AI Provider Interface for Claude
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Claude_Provider implements AutoBlogger_AI_Provider_Interface {
    
    private $api_key;
    private $api_endpoint = 'https://api.anthropic.com/v1/messages';
    private $model;
    private $settings;
    
    public function __construct($settings) {
        $this->settings = $settings;
        $this->api_key = $settings->get_api_key();
        $this->model = get_option('autoblogger_api_model', 'claude-3-5-sonnet-20241022');
    }
    
    /**
     * Generate content from prompt
     */
    public function generate($prompt, $options = []) {
        if (empty($this->api_key)) {
            throw new Exception('Claude API key not configured');
        }
        
        $max_tokens = $options['max_tokens'] ?? 4000;
        $temperature = $options['temperature'] ?? 1.0;
        
        $body = [
            'model' => $this->model,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];
        
        $response = wp_remote_post($this->api_endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01'
            ],
            'body' => wp_json_encode($body),
            'timeout' => AutoBlogger_Config::get('api_timeout')
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('Claude API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if ($status_code !== 200) {
            $error_message = $data['error']['message'] ?? 'Unknown API error';
            
            if ($status_code === 401) {
                throw new Exception('Invalid Claude API key');
            } elseif ($status_code === 429) {
                throw new Exception('Claude API rate limit exceeded');
            } elseif ($status_code === 500 || $status_code === 503) {
                throw new Exception('Claude API service unavailable');
            } else {
                throw new Exception("Claude API error ({$status_code}): {$error_message}");
            }
        }
        
        if (!isset($data['content'][0]['text'])) {
            throw new Exception('Invalid Claude API response format');
        }
        
        return [
            'content' => $data['content'][0]['text'],
            'usage' => $data['usage'] ?? [],
            'model' => $data['model'] ?? $this->model,
            'raw_response' => $data
        ];
    }
    
    /**
     * Estimate cost
     */
    public function estimate_cost($prompt, $max_output_tokens = 4000) {
        $input_tokens = $this->estimate_tokens($prompt);
        $output_tokens = $max_output_tokens;
        
        $pricing = $this->get_pricing();
        
        $input_cost = ($input_tokens / 1000000) * $pricing['input_per_million'];
        $output_cost = ($output_tokens / 1000000) * $pricing['output_per_million'];
        $total_cost = $input_cost + $output_cost;
        
        return [
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
            'total_tokens' => $input_tokens + $output_tokens,
            'input_cost' => round($input_cost, 6),
            'output_cost' => round($output_cost, 6),
            'total_cost' => round($total_cost, 6)
        ];
    }
    
    /**
     * Estimate tokens from text
     */
    private function estimate_tokens($text) {
        $char_count = mb_strlen($text, 'UTF-8');
        $token_char_ratio = 3; // 1 token ≈ 3 chars for Vietnamese
        return (int) ceil($char_count / $token_char_ratio);
    }
    
    /**
     * Get token usage from response
     */
    public function get_token_usage($response) {
        if (isset($response['usage'])) {
            return [
                'input_tokens' => $response['usage']['input_tokens'] ?? 0,
                'output_tokens' => $response['usage']['output_tokens'] ?? 0
            ];
        }
        
        return ['input_tokens' => 0, 'output_tokens' => 0];
    }
    
    /**
     * Validate API key format
     */
    public function validate_api_key($api_key) {
        return strpos($api_key, 'sk-ant-') === 0 && strlen($api_key) > 20;
    }
    
    /**
     * Test connection
     */
    public function test_connection() {
        try {
            $result = $this->generate('Hello', ['max_tokens' => 10]);
            return !empty($result['content']);
        } catch (Exception $e) {
            throw new Exception('Claude connection test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get provider name
     */
    public function get_provider_name() {
        return 'claude';
    }
    
    /**
     * Get available models
     */
    public function get_available_models() {
        return [
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Latest)',
            'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku (Latest)',
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku'
        ];
    }
    
    /**
     * Get current model
     */
    public function get_current_model() {
        return $this->model;
    }
    
    /**
     * Set model
     */
    public function set_model($model) {
        $available = array_keys($this->get_available_models());
        
        if (!in_array($model, $available)) {
            return false;
        }
        
        $this->model = $model;
        update_option('autoblogger_api_model', $model);
        
        return true;
    }
    
    /**
     * Get pricing
     */
    public function get_pricing() {
        // Pricing varies by model (as of December 2024)
        $model_pricing = [
            'claude-3-5-sonnet-20241022' => ['input' => 3.00, 'output' => 15.00],
            'claude-3-5-haiku-20241022' => ['input' => 1.00, 'output' => 5.00],
            'claude-3-opus-20240229' => ['input' => 15.00, 'output' => 75.00],
            'claude-3-sonnet-20240229' => ['input' => 3.00, 'output' => 15.00],
            'claude-3-haiku-20240307' => ['input' => 0.25, 'output' => 1.25]
        ];
        
        $pricing = $model_pricing[$this->model] ?? $model_pricing['claude-3-5-sonnet-20241022'];
        
        return [
            'input_per_million' => $pricing['input'],
            'output_per_million' => $pricing['output'],
            'currency' => 'USD'
        ];
    }
    
    /**
     * Get max context tokens
     */
    public function get_max_context_tokens() {
        // All Claude 3 and 3.5 models support 200k context window
        return 200000;
    }
    
    /**
     * Supports streaming
     */
    public function supports_streaming() {
        return true;
    }
    
    /**
     * Supports function calling
     */
    public function supports_function_calling() {
        return true; // Claude 3.5 supports tool use
    }
}

