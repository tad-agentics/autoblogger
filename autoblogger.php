<?php
/**
 * Plugin Name: AutoBlogger
 * Plugin URI: https://autoblogger.com
 * Description: AI-powered content generation with RankMath SEO optimization and E-E-A-T compliance
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://autoblogger.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: autoblogger
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define constants
define('AUTOBLOGGER_VERSION', '1.0.0');
define('AUTOBLOGGER_PATH', plugin_dir_path(__FILE__));
define('AUTOBLOGGER_URL', plugin_dir_url(__FILE__));
define('AUTOBLOGGER_BASENAME', plugin_basename(__FILE__));

// Simple autoloader
spl_autoload_register(function($class) {
    if (strpos($class, 'AutoBlogger_') === 0) {
        $file = AUTOBLOGGER_PATH . 'includes/class-' . 
                strtolower(str_replace('_', '-', substr($class, 12))) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Activation hook
register_activation_hook(__FILE__, ['AutoBlogger_Activator', 'activate']);

// Deactivation hook
register_deactivation_hook(__FILE__, ['AutoBlogger_Activator', 'deactivate']);

// Initialize plugin
add_action('plugins_loaded', 'autoblogger_init');

/**
 * Initialize AutoBlogger plugin
 * OPTIMIZED: Only loads heavy logic when needed (admin/REST API)
 */
function autoblogger_init() {
    // CRITICAL: Skip heavy initialization on frontend
    // Only load when actually needed (admin, REST API, or AJAX)
    if (!is_admin() && !wp_doing_ajax() && !wp_doing_cron() && !defined('REST_REQUEST')) {
        return; // Exit early on frontend - saves memory and processing
    }
    
    // Check minimum requirements (only in admin)
    if (is_admin()) {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', 'autoblogger_php_version_notice');
            return;
        }
        
        if (version_compare(get_bloginfo('version'), '6.0', '<')) {
            add_action('admin_notices', 'autoblogger_wp_version_notice');
            return;
        }
        
        // Check for OpenSSL extension
        if (!extension_loaded('openssl')) {
            add_action('admin_notices', 'autoblogger_openssl_notice');
            return;
        }
    }
    
    // Register all hooks
    $hooks = new AutoBlogger_Hooks();
    $hooks->register();
    
    // Log plugin initialization (only in admin/debug mode)
    if (is_admin() && class_exists('AutoBlogger_Logger')) {
        AutoBlogger_Logger::info('Plugin initialized', [
            'version' => AUTOBLOGGER_VERSION,
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'context' => is_admin() ? 'admin' : (wp_doing_ajax() ? 'ajax' : 'rest')
        ]);
    }
}

/**
 * PHP version notice
 */
function autoblogger_php_version_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e('AutoBlogger', 'autoblogger'); ?>:</strong>
            <?php
            printf(
                /* translators: %s: Required PHP version */
                esc_html__('This plugin requires PHP version %s or higher. Please upgrade PHP.', 'autoblogger'),
                '7.4'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * WordPress version notice
 */
function autoblogger_wp_version_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e('AutoBlogger', 'autoblogger'); ?>:</strong>
            <?php
            printf(
                /* translators: %s: Required WordPress version */
                esc_html__('This plugin requires WordPress version %s or higher. Please upgrade WordPress.', 'autoblogger'),
                '6.0'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * OpenSSL extension notice
 */
function autoblogger_openssl_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e('AutoBlogger', 'autoblogger'); ?>:</strong>
            <?php esc_html_e('This plugin requires the OpenSSL PHP extension for API key encryption. Please enable it.', 'autoblogger'); ?>
        </p>
    </div>
    <?php
}

/**
 * Add settings link on plugin page
 */
add_filter('plugin_action_links_' . AUTOBLOGGER_BASENAME, 'autoblogger_add_action_links');

function autoblogger_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=autoblogger') . '">' . 
                     esc_html__('Settings', 'autoblogger') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

