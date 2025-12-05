<?php
/**
 * Structured logging system
 * Provides consistent logging across the plugin
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Logger {
    
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_CRITICAL = 4;
    
    private static $level_names = [
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_CRITICAL => 'CRITICAL'
    ];
    
    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function debug($message, $context = []) {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function info($message, $context = []) {
        self::log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function warning($message, $context = []) {
        self::log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function error($message, $context = []) {
        self::log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log critical message (sends admin email)
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public static function critical($message, $context = []) {
        self::log(self::LEVEL_CRITICAL, $message, $context);
        self::notify_admin($message, $context);
    }
    
    /**
     * Core logging method
     *
     * @param int $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     */
    private static function log($level, $message, $context) {
        // Check if logging is enabled
        if (!self::should_log($level)) {
            return;
        }
        
        $log_entry = sprintf(
            '[%s] [%s] %s',
            current_time('mysql'),
            self::$level_names[$level],
            $message
        );
        
        if (!empty($context)) {
            $log_entry .= ' | Context: ' . wp_json_encode($context);
        }
        
        // Write to WordPress debug log
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('AutoBlogger: ' . $log_entry);
        }
        
        // Optionally store critical errors in database for admin viewing
        if ($level >= self::LEVEL_ERROR) {
            self::store_in_option($log_entry);
        }
        
        // Fire action for extensibility
        do_action('autoblogger_log', $level, $message, $context);
    }
    
    /**
     * Check if should log based on WP_DEBUG setting
     *
     * @param int $level Log level
     * @return bool Whether to log
     */
    private static function should_log($level) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return true; // Log everything in debug mode
        }
        
        return $level >= self::LEVEL_WARNING; // Only warnings+ in production
    }
    
    /**
     * Store error in wp_options for admin viewing
     *
     * @param string $log_entry Log entry
     */
    private static function store_in_option($log_entry) {
        $logs = get_option('autoblogger_error_logs', []);
        
        array_unshift($logs, [
            'message' => $log_entry,
            'timestamp' => current_time('mysql')
        ]);
        
        // Keep last 50 errors
        $logs = array_slice($logs, 0, 50);
        
        // CRITICAL: Set autoload=false for logs (can be large, not needed on every page load)
        update_option('autoblogger_error_logs', $logs, false);
    }
    
    /**
     * Send admin notification for critical errors
     *
     * @param string $message Error message
     * @param array $context Error context
     */
    private static function notify_admin($message, $context) {
        $admin_email = get_option('admin_email');
        
        if (empty($admin_email)) {
            return;
        }
        
        $subject = 'AutoBlogger Critical Error';
        $body = sprintf(
            "Critical Error: %s\n\nContext: %s\n\nTime: %s\n\nSite: %s",
            $message,
            wp_json_encode($context, JSON_PRETTY_PRINT),
            current_time('mysql'),
            get_site_url()
        );
        
        wp_mail($admin_email, $subject, $body);
    }
    
    /**
     * Get stored error logs
     *
     * @param int $limit Number of logs to retrieve
     * @return array Error logs
     */
    public static function get_error_logs($limit = 50) {
        $logs = get_option('autoblogger_error_logs', []);
        return array_slice($logs, 0, $limit);
    }
    
    /**
     * Clear stored error logs
     *
     * @return bool True on success
     */
    public static function clear_error_logs() {
        return delete_option('autoblogger_error_logs');
    }
}

