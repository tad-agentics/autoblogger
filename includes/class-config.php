<?php
/**
 * Centralized configuration management
 * Single source of truth for all plugin constants
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Config {
    
    // API Configuration
    const API_MODEL = 'claude-3-5-sonnet-20241022';
    const API_MAX_TOKENS = 4000;
    const API_TIMEOUT = 60;
    const API_MAX_RETRIES = 3;
    const API_RETRY_DELAY_BASE = 2; // Exponential backoff base (seconds)
    
    // Cost Configuration (Claude 3.5 Sonnet pricing)
    const COST_INPUT_PER_MILLION = 3.00;
    const COST_OUTPUT_PER_MILLION = 15.00;
    const COST_DEFAULT_DAILY_BUDGET = 5.00;
    const COST_TOKEN_CHAR_RATIO = 3; // 1 token ≈ 3 chars for Vietnamese
    
    // Optimization Configuration
    const OPT_MAX_ITERATIONS = 2;
    const OPT_SCORE_THRESHOLD = 80;
    const OPT_BLOCK_CONTEXT_SIZE = 1; // Blocks before/after for context
    
    // Cache Configuration
    const CACHE_TTL = 3600; // 1 hour
    const CACHE_GROUP = 'autoblogger';
    
    // Lock Configuration
    const LOCK_TIMEOUT = 300; // 5 minutes
    
    // Version Configuration
    const VERSION_MAX_KEEP = 5; // Keep last 5 versions
    
    // Auto-save Configuration
    const AUTOSAVE_INTERVAL = 30000; // 30 seconds (JavaScript)
    const RECOVERY_MAX_AGE = 86400; // 24 hours
    
    // Safety Configuration
    const SAFETY_DEFAULT_DISCLAIMER = 'This information is for reference purposes only. For health/financial matters, please consult qualified professionals.';
    
    // Prompt Configuration
    const PROMPT_CACHE_TTL = 3600; // Cache rendered prompts for 1 hour
    const PROMPT_MAX_LENGTH = 50000; // Max prompt length in characters
    const PROMPT_DEFAULT_PERSONA = 'Academic';
    
    /**
     * Get configuration value
     * Allows runtime override via WordPress options
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    public static function get($key, $default = null) {
        $option_key = 'autoblogger_' . strtolower($key);
        $option_value = get_option($option_key);
        
        if ($option_value !== false) {
            return $option_value;
        }
        
        // Fall back to constant
        $constant = 'self::' . strtoupper($key);
        return defined($constant) ? constant($constant) : $default;
    }
    
    /**
     * Set configuration value at runtime
     *
     * @param string $key Configuration key
     * @param mixed $value Value to set
     * @return bool True on success
     */
    public static function set($key, $value) {
        $option_key = 'autoblogger_' . strtolower($key);
        // Config values are typically small and frequently accessed - autoload=true by default
        // For large values, caller should use update_option() directly with autoload=false
        return update_option($option_key, $value, true);
    }
    
    /**
     * Get all configuration as array
     *
     * @return array All configuration values
     */
    public static function get_all() {
        $reflection = new ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();
        
        $config = [];
        foreach ($constants as $key => $value) {
            $config[strtolower($key)] = self::get($key, $value);
        }
        
        return $config;
    }
}

