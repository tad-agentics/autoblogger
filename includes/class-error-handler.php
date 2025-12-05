<?php
/**
 * Centralized error handling system
 * Provides consistent error responses with actionable messages
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Error_Handler {
    
    private static $error_codes = [
        'invalid_api_key' => [
            'message' => 'Invalid Anthropic API key. Please check your settings.',
            'action' => 'Go to Settings → Enter valid API key',
            'status' => 401
        ],
        'budget_exceeded' => [
            'message' => 'Daily budget limit reached.',
            'action' => 'Increase budget in Settings or wait until tomorrow',
            'status' => 429
        ],
        'rankmath_not_found' => [
            'message' => 'RankMath plugin not detected.',
            'action' => 'Install RankMath for advanced SEO features (optional)',
            'status' => 404
        ],
        'knowledge_base_empty' => [
            'message' => 'Knowledge base is empty.',
            'action' => 'Go to Knowledge Base → Import CSV/JSON data',
            'status' => 404
        ],
        'api_timeout' => [
            'message' => 'AI service request timed out.',
            'action' => 'Try again or reduce content length',
            'status' => 504
        ],
        'api_rate_limit' => [
            'message' => 'API rate limit exceeded.',
            'action' => 'Wait a few minutes and try again',
            'status' => 429
        ],
        'api_error' => [
            'message' => 'AI service error occurred.',
            'action' => 'Check API key and try again',
            'status' => 500
        ],
        'post_locked' => [
            'message' => 'Another user is generating content for this post.',
            'action' => 'Wait a few minutes and try again',
            'status' => 423
        ],
        'prompt_not_found' => [
            'message' => 'Prompt template not found.',
            'action' => 'Check prompt configuration in Settings',
            'status' => 404
        ],
        'invalid_request' => [
            'message' => 'Invalid request parameters.',
            'action' => 'Check your input and try again',
            'status' => 400
        ]
    ];
    
    /**
     * Handle error and return WP_Error
     *
     * @param string $error_code Error code
     * @param array $context Additional context
     * @return WP_Error
     */
    public static function handle($error_code, $context = []) {
        $error = self::$error_codes[$error_code] ?? [
            'message' => 'An unexpected error occurred.',
            'action' => 'Please try again or contact support',
            'status' => 500
        ];
        
        // Log error
        if (class_exists('AutoBlogger_Logger')) {
            $log_level = $error['status'] >= 500 ? 'error' : 'warning';
            AutoBlogger_Logger::$log_level(
                sprintf('[%s] %s', $error_code, $error['message']),
                $context
            );
        }
        
        // Send to admin if critical
        if ($error['status'] >= 500) {
            self::notify_admin($error_code, $error, $context);
        }
        
        return new WP_Error($error_code, $error['message'], [
            'status' => $error['status'],
            'action' => $error['action'],
            'context' => $context
        ]);
    }
    
    /**
     * Notify admin of critical error
     *
     * @param string $code Error code
     * @param array $error Error details
     * @param array $context Error context
     */
    private static function notify_admin($code, $error, $context) {
        $admin_email = get_option('admin_email');
        
        if (empty($admin_email)) {
            return;
        }
        
        $subject = 'AutoBlogger Critical Error';
        $body = sprintf(
            "Error Code: %s\nMessage: %s\nContext: %s\nTime: %s\nSite: %s",
            $code,
            $error['message'],
            wp_json_encode($context, JSON_PRETTY_PRINT),
            current_time('mysql'),
            get_site_url()
        );
        
        wp_mail($admin_email, $subject, $body);
    }
    
    /**
     * Get error details
     *
     * @param string $error_code Error code
     * @return array|null Error details
     */
    public static function get_error_details($error_code) {
        return self::$error_codes[$error_code] ?? null;
    }
    
    /**
     * Check if error code exists
     *
     * @param string $error_code Error code
     * @return bool
     */
    public static function error_exists($error_code) {
        return isset(self::$error_codes[$error_code]);
    }
}

/**
 * Custom exception for budget exceeded
 */
class AutoBlogger_Budget_Exception extends Exception {
    private $data;
    
    public function __construct($message, $code = 'budget_exceeded', $data = []) {
        parent::__construct($message);
        $this->data = $data;
    }
    
    public function getData() {
        return $this->data;
    }
}

