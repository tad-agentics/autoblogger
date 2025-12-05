<?php
/**
 * AI Provider Interface
 * Defines the contract for all AI service providers
 * Allows easy switching between Claude, Gemini, OpenAI, etc.
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

interface AutoBlogger_AI_Provider_Interface {
    
    /**
     * Generate content from prompt
     *
     * @param string $prompt The prompt text
     * @param array $options Generation options
     * @return array Response with content and metadata
     * @throws Exception On API error
     */
    public function generate($prompt, $options = []);
    
    /**
     * Estimate cost for a given prompt
     *
     * @param string $prompt The prompt text
     * @param int $max_output_tokens Expected output tokens
     * @return array Cost estimate breakdown
     */
    public function estimate_cost($prompt, $max_output_tokens = 4000);
    
    /**
     * Get actual token usage from last response
     *
     * @param mixed $response Raw API response
     * @return array Token usage (input_tokens, output_tokens)
     */
    public function get_token_usage($response);
    
    /**
     * Validate API key format
     *
     * @param string $api_key The API key to validate
     * @return bool True if valid format
     */
    public function validate_api_key($api_key);
    
    /**
     * Test API connection
     *
     * @return bool True if connection successful
     * @throws Exception On connection error
     */
    public function test_connection();
    
    /**
     * Get provider name
     *
     * @return string Provider name (e.g., 'claude', 'gemini', 'openai')
     */
    public function get_provider_name();
    
    /**
     * Get available models for this provider
     *
     * @return array List of available models
     */
    public function get_available_models();
    
    /**
     * Get current model being used
     *
     * @return string Current model name
     */
    public function get_current_model();
    
    /**
     * Set model to use
     *
     * @param string $model Model name
     * @return bool True on success
     */
    public function set_model($model);
    
    /**
     * Get pricing information
     *
     * @return array Pricing per million tokens (input and output)
     */
    public function get_pricing();
    
    /**
     * Get maximum context window size
     *
     * @return int Maximum tokens in context window
     */
    public function get_max_context_tokens();
    
    /**
     * Check if provider supports streaming
     *
     * @return bool True if streaming supported
     */
    public function supports_streaming();
    
    /**
     * Check if provider supports function calling
     *
     * @return bool True if function calling supported
     */
    public function supports_function_calling();
}

