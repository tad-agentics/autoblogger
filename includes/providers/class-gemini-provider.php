<?php
/**
 * Google Gemini Provider
 * Implementation of AI Provider Interface for Gemini
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Gemini_Provider implements AutoBlogger_AI_Provider_Interface {
    
    private $api_key;
    private $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $model;
    private $settings;
    
    public function __construct($settings) {
        $this->settings = $settings;
        $this->api_key = $settings->get_api_key();
        $this->model = get_option('autoblogger_api_model', 'gemini-2.5-flash-latest');
    }
    
    /**
     * Generate content from prompt
     */
    public function generate($prompt, $options = []) {
        if (empty($this->api_key)) {
            throw new Exception('Gemini API key not configured');
        }
        
        $max_tokens = $options['max_tokens'] ?? 4000;
        $temperature = $options['temperature'] ?? 1.0;
        $system_prompt = $options['system_prompt'] ?? '';
        
        $endpoint = $this->api_endpoint . $this->model . ':generateContent?key=' . $this->api_key;
        
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $max_tokens,
                'temperature' => $temperature
            ]
        ];
        
        // Add system instruction if provided (Gemini-specific parameter)
        if (!empty($system_prompt)) {
            $body['systemInstruction'] = [
                'parts' => [
                    ['text' => $system_prompt]
                ]
            ];
        }
        
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode($body),
            'timeout' => AutoBlogger_Config::get('api_timeout')
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('Gemini API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if ($status_code !== 200) {
            $error_message = $data['error']['message'] ?? 'Unknown API error';
            throw new Exception("Gemini API error ({$status_code}): {$error_message}");
        }
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Invalid Gemini API response format');
        }
        
        return [
            'content' => $data['candidates'][0]['content']['parts'][0]['text'],
            'usage' => $data['usageMetadata'] ?? [],
            'model' => $this->model,
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
        $token_char_ratio = 4; // Gemini: 1 token ≈ 4 chars
        return (int) ceil($char_count / $token_char_ratio);
    }
    
    /**
     * Get token usage from response
     */
    public function get_token_usage($response) {
        if (isset($response['usage'])) {
            return [
                'input_tokens' => $response['usage']['promptTokenCount'] ?? 0,
                'output_tokens' => $response['usage']['candidatesTokenCount'] ?? 0
            ];
        }
        
        return ['input_tokens' => 0, 'output_tokens' => 0];
    }
    
    /**
     * Validate API key format
     */
    public function validate_api_key($api_key) {
        return strpos($api_key, 'AIza') === 0 && strlen($api_key) > 30;
    }
    
    /**
     * Test connection
     */
    public function test_connection() {
        try {
            $result = $this->generate('Hello', ['max_tokens' => 10]);
            return !empty($result['content']);
        } catch (Exception $e) {
            throw new Exception('Gemini connection test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get provider name
     */
    public function get_provider_name() {
        return 'gemini';
    }
    
    /**
     * Get available models
     */
    public function get_available_models() {
        return [
            'gemini-2.5-flash-latest' => 'Gemini 2.5 Flash (Latest)',
            'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash (Experimental)',
            'gemini-1.5-pro-002' => 'Gemini 1.5 Pro',
            'gemini-1.5-flash-002' => 'Gemini 1.5 Flash',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro (Legacy)',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash (Legacy)',
            'gemini-pro' => 'Gemini Pro (Legacy)'
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
        // Pricing varies by model
        // Note: Gemini 2.0 and 2.5 Flash experimental models are currently free
        $model_pricing = [
            'gemini-2.5-flash-latest' => ['input' => 0.075, 'output' => 0.30], // Same as 1.5 Flash
            'gemini-2.0-flash-exp' => ['input' => 0.00, 'output' => 0.00], // Free during experimental
            'gemini-1.5-pro-002' => ['input' => 1.25, 'output' => 5.00],
            'gemini-1.5-flash-002' => ['input' => 0.075, 'output' => 0.30],
            'gemini-1.5-pro' => ['input' => 1.25, 'output' => 5.00],
            'gemini-1.5-flash' => ['input' => 0.075, 'output' => 0.30],
            'gemini-pro' => ['input' => 0.50, 'output' => 1.50]
        ];
        
        $pricing = $model_pricing[$this->model] ?? $model_pricing['gemini-2.5-flash-latest'];
        
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
        // Context windows vary by model
        $context_windows = [
            'gemini-2.5-flash-latest' => 1000000, // 1M context
            'gemini-2.0-flash-exp' => 1000000,
            'gemini-1.5-pro-002' => 2000000,
            'gemini-1.5-flash-002' => 1000000,
            'gemini-1.5-pro' => 2000000,
            'gemini-1.5-flash' => 1000000,
            'gemini-pro' => 32000
        ];
        
        return $context_windows[$this->model] ?? 1000000;
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
        return true;
    }
}

