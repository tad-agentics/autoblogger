<?php
/**
 * Settings management with AES-256 encryption for API keys
 * Handles plugin configuration and secure storage
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Settings {
    
    private $encryption_key;
    private $cipher_method = 'AES-256-CBC';
    
    public function __construct() {
        $this->encryption_key = $this->get_encryption_key();
    }
    
    /**
     * Get or generate encryption key
     * Stored in wp-config.php for maximum security
     *
     * @return string Encryption key
     */
    private function get_encryption_key() {
        // Check if key is defined in wp-config.php (recommended)
        if (defined('AUTOBLOGGER_ENCRYPTION_KEY')) {
            return AUTOBLOGGER_ENCRYPTION_KEY;
        }
        
        // Fall back to database (less secure but works)
        $key = get_option('autoblogger_encryption_key');
        
        if (!$key) {
            // Generate new key
            $key = bin2hex(openssl_random_pseudo_bytes(32));
            update_option('autoblogger_encryption_key', $key);
            
            // Warn admin to move key to wp-config.php
            AutoBlogger_Logger::warning('Encryption key stored in database. For better security, add to wp-config.php');
        }
        
        return $key;
    }
    
    /**
     * Encrypt sensitive data
     *
     * @param string $data Data to encrypt
     * @return string Encrypted data (base64 encoded)
     */
    public function encrypt($data) {
        if (empty($data)) {
            return '';
        }
        
        $iv_length = openssl_cipher_iv_length($this->cipher_method);
        $iv = openssl_random_pseudo_bytes($iv_length);
        
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher_method,
            $this->encryption_key,
            0,
            $iv
        );
        
        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     *
     * @param string $encrypted_data Encrypted data (base64 encoded)
     * @return string Decrypted data
     */
    public function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }
        
        $data = base64_decode($encrypted_data);
        $iv_length = openssl_cipher_iv_length($this->cipher_method);
        
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        return openssl_decrypt(
            $encrypted,
            $this->cipher_method,
            $this->encryption_key,
            0,
            $iv
        );
    }
    
    /**
     * Save API key (encrypted)
     *
     * @param string $api_key API key
     * @return bool True on success
     */
    public function save_api_key($api_key) {
        $encrypted = $this->encrypt($api_key);
        // Small data, frequently needed - autoload=true (default)
        $result = update_option('autoblogger_api_key', $encrypted, true);
        
        if ($result) {
            AutoBlogger_Logger::info('API key saved (encrypted)');
        }
        
        return $result;
    }
    
    /**
     * Get API key (decrypted)
     *
     * @return string API key
     */
    public function get_api_key() {
        $encrypted = get_option('autoblogger_api_key', '');
        return $this->decrypt($encrypted);
    }
    
    /**
     * Check if API key is configured
     *
     * @return bool True if configured
     */
    public function has_api_key() {
        $api_key = $this->get_api_key();
        return !empty($api_key);
    }
    
    /**
     * Get all settings
     *
     * @return array All settings
     */
    public function get_all_settings() {
        return [
            'api_key_configured' => $this->has_api_key(),
            'api_model' => get_option('autoblogger_api_model', AutoBlogger_Config::API_MODEL),
            'daily_budget' => (float) get_option('autoblogger_daily_budget', AutoBlogger_Config::COST_DEFAULT_DAILY_BUDGET),
            'max_iterations' => (int) get_option('autoblogger_max_iterations', AutoBlogger_Config::OPT_MAX_ITERATIONS),
            'score_threshold' => (int) get_option('autoblogger_score_threshold', AutoBlogger_Config::OPT_SCORE_THRESHOLD),
            'disclaimer_text' => get_option('autoblogger_disclaimer_text', AutoBlogger_Config::SAFETY_DEFAULT_DISCLAIMER),
            'expert_name' => get_option('autoblogger_expert_name', 'Expert'),
            'system_prompt' => $this->get_system_prompt(),
            'personas' => $this->get_personas(),
            'negative_keywords' => $this->get_negative_keywords(),
            'language' => get_option('autoblogger_language', 'auto')
        ];
    }
    
    /**
     * Save settings
     *
     * @param array $settings Settings to save
     * @return bool True on success
     */
    public function save_settings($settings) {
        $updated = [];
        
        if (isset($settings['api_key'])) {
            $this->save_api_key($settings['api_key']);
            $updated[] = 'api_key';
        }
        
        if (isset($settings['api_model'])) {
            // Small, frequently needed - autoload=true
            update_option('autoblogger_api_model', sanitize_text_field($settings['api_model']), true);
            $updated[] = 'api_model';
        }
        
        if (isset($settings['daily_budget'])) {
            // Small, frequently needed - autoload=true
            update_option('autoblogger_daily_budget', (float) $settings['daily_budget'], true);
            $updated[] = 'daily_budget';
        }
        
        if (isset($settings['max_iterations'])) {
            // Small, frequently needed - autoload=true
            update_option('autoblogger_max_iterations', (int) $settings['max_iterations'], true);
            $updated[] = 'max_iterations';
        }
        
        if (isset($settings['score_threshold'])) {
            // Small, frequently needed - autoload=true
            update_option('autoblogger_score_threshold', (int) $settings['score_threshold'], true);
            $updated[] = 'score_threshold';
        }
        
        if (isset($settings['disclaimer_text'])) {
            // Can be large text - autoload=false
            update_option('autoblogger_disclaimer_text', wp_kses_post($settings['disclaimer_text']), false);
            $updated[] = 'disclaimer_text';
        }
        
        if (isset($settings['expert_name'])) {
            // Small, infrequently needed - autoload=false
            update_option('autoblogger_expert_name', sanitize_text_field($settings['expert_name']), false);
            $updated[] = 'expert_name';
        }
        
        if (isset($settings['system_prompt'])) {
            // Can be large text - autoload=false
            update_option('autoblogger_system_prompt', wp_kses_post($settings['system_prompt']), false);
            $updated[] = 'system_prompt';
        }
        
        if (isset($settings['personas'])) {
            $this->save_personas($settings['personas']);
            $updated[] = 'personas';
        }
        
        if (isset($settings['negative_keywords'])) {
            $this->save_negative_keywords($settings['negative_keywords']);
            $updated[] = 'negative_keywords';
        }
        
        if (isset($settings['language'])) {
            update_option('autoblogger_language', sanitize_text_field($settings['language']), true);
            $updated[] = 'language';
        }
        
        AutoBlogger_Logger::info('Settings updated', ['fields' => $updated]);
        
        return true;
    }
    
    /**
     * Get system prompt
     *
     * @return string System prompt
     */
    public function get_system_prompt() {
        $default = "You are an expert Vietnamese content writer specializing in astrology and spiritual topics (Tử Vi, Phong Thủy, Lá Số Tử Vi).\n\n" .
            "Your responsibilities:\n" .
            "1. Create accurate, well-researched content based on traditional Vietnamese astrology texts\n" .
            "2. Write in clear, engaging Vietnamese that's accessible to modern readers\n" .
            "3. Always cite sources when referencing specific astrological principles\n" .
            "4. Maintain a balanced, professional tone - avoid absolute predictions or medical/financial advice\n" .
            "5. Include practical examples and real-world applications\n" .
            "6. Follow SEO best practices while keeping content natural and reader-friendly\n\n" .
            "Safety guidelines:\n" .
            "- Never make definitive health or financial predictions\n" .
            "- Always include appropriate disclaimers for interpretive content\n" .
            "- Respect cultural sensitivity around spiritual beliefs\n" .
            "- Avoid sensationalism or fear-based language";
        
        return get_option('autoblogger_system_prompt', $default);
    }
    
    /**
     * Get personas
     *
     * @return array Personas
     */
    public function get_personas() {
        $personas = get_option('autoblogger_personas');
        
        if ($personas) {
            return json_decode($personas, true);
        }
        
        // Default personas
        return [
            [
                'name' => 'Academic',
                'prompt' => 'Write in a formal, scholarly tone with proper citations and technical terminology'
            ],
            [
                'name' => 'Simple',
                'prompt' => 'Write in a friendly, conversational tone that\'s easy to understand for general readers'
            ]
        ];
    }
    
    /**
     * Save personas
     *
     * @param array $personas Personas
     * @return bool True on success
     */
    public function save_personas($personas) {
        // Can be large JSON array - autoload=false
        return update_option('autoblogger_personas', wp_json_encode($personas), false);
    }
    
    /**
     * Get negative keywords
     *
     * @return array Negative keywords
     */
    public function get_negative_keywords() {
        $keywords = get_option('autoblogger_negative_keywords');
        
        if ($keywords) {
            return json_decode($keywords, true);
        }
        
        // Default negative keywords
        return [
            'chắc chắn chết',
            'bỏ thuốc',
            'đừng tin bác sĩ',
            'không cần bác sĩ',
            '100% chính xác',
            'chữa khỏi ung thư',
            'bỏ điều trị'
        ];
    }
    
    /**
     * Save negative keywords
     *
     * @param array $keywords Negative keywords
     * @return bool True on success
     */
    public function save_negative_keywords($keywords) {
        // Can be large array - autoload=false
        return update_option('autoblogger_negative_keywords', wp_json_encode($keywords), false);
    }
    
    /**
     * Get plugin language setting
     *
     * @return string Language code ('auto', 'en_US', 'vi_VN')
     */
    public function get_language() {
        return get_option('autoblogger_language', 'auto');
    }
    
    /**
     * Get the effective locale for AutoBlogger
     * Returns the plugin's language setting, or WordPress locale if set to 'auto'
     *
     * @return string Locale code
     */
    public function get_effective_locale() {
        $language = $this->get_language();
        
        if ($language === 'auto') {
            return get_locale();
        }
        
        return $language;
    }
    
    /**
     * Validate API key format
     *
     * @param string $api_key API key
     * @return bool True if valid format
     */
    public function validate_api_key($api_key) {
        // Anthropic API keys start with 'sk-ant-'
        return strpos($api_key, 'sk-ant-') === 0 && strlen($api_key) > 20;
    }
}

