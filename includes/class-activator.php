<?php
/**
 * Plugin activation and deactivation handler
 * Handles database table creation, migrations, and cleanup
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Activator {
    
    private static $db_version = '1.0.0';
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Check for class name collisions FIRST
        require_once AUTOBLOGGER_PLUGIN_DIR . 'includes/class-collision-checker.php';
        
        $conflicts = AutoBlogger_Collision_Checker::check_collisions();
        
        if (!empty($conflicts)) {
            // Show error and prevent activation
            $conflict_list = array_map(function($c) {
                return $c['class'] . ' (' . $c['file'] . ')';
            }, $conflicts);
            
            wp_die(
                '<h1>' . __('Plugin Activation Failed', 'autoblogger') . '</h1>' .
                '<p>' . __('AutoBlogger cannot activate because another plugin is using the same class names:', 'autoblogger') . '</p>' .
                '<ul><li>' . implode('</li><li>', array_map('esc_html', $conflict_list)) . '</li></ul>' .
                '<p>' . __('Please deactivate the conflicting plugin first, or contact support.', 'autoblogger') . '</p>',
                __('Plugin Activation Error', 'autoblogger'),
                ['back_link' => true]
            );
        }
        
        // Check requirements
        self::check_requirements();
        
        // Run migrations
        $installed_version = get_option('autoblogger_db_version', '0.0.0');
        
        if (version_compare($installed_version, self::$db_version, '<')) {
            self::run_migrations($installed_version);
        }
        
        // Update database version
        // Small, rarely changes - autoload=true
        update_option('autoblogger_db_version', self::$db_version, true);
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        if (class_exists('AutoBlogger_Logger')) {
            AutoBlogger_Logger::info('Plugin activated', [
                'version' => AUTOBLOGGER_VERSION,
                'db_version' => self::$db_version
            ]);
        }
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        if (class_exists('AutoBlogger_Logger')) {
            AutoBlogger_Logger::info('Plugin deactivated');
        }
    }
    
    /**
     * Check minimum requirements
     */
    private static function check_requirements() {
        global $wp_version;
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(AUTOBLOGGER_BASENAME);
            wp_die(
                esc_html__('AutoBlogger requires PHP 7.4 or higher.', 'autoblogger'),
                esc_html__('Plugin Activation Error', 'autoblogger'),
                ['back_link' => true]
            );
        }
        
        // Check WordPress version
        if (version_compare($wp_version, '6.0', '<')) {
            deactivate_plugins(AUTOBLOGGER_BASENAME);
            wp_die(
                esc_html__('AutoBlogger requires WordPress 6.0 or higher.', 'autoblogger'),
                esc_html__('Plugin Activation Error', 'autoblogger'),
                ['back_link' => true]
            );
        }
        
        // Check OpenSSL extension
        if (!extension_loaded('openssl')) {
            deactivate_plugins(AUTOBLOGGER_BASENAME);
            wp_die(
                esc_html__('AutoBlogger requires the OpenSSL PHP extension.', 'autoblogger'),
                esc_html__('Plugin Activation Error', 'autoblogger'),
                ['back_link' => true]
            );
        }
    }
    
    /**
     * Run database migrations
     */
    private static function run_migrations($from_version) {
        // Run migrations in order
        if (version_compare($from_version, '1.0.0', '<')) {
            self::migrate_to_1_0_0();
        }
        
        // Future migrations can be added here
        // if (version_compare($from_version, '1.1.0', '<')) {
        //     self::migrate_to_1_1_0();
        // }
    }
    
    /**
     * Migrate to version 1.0.0
     */
    private static function migrate_to_1_0_0() {
        self::create_tables();
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Knowledge base table
        $knowledge_table = $wpdb->prefix . 'autoblogger_knowledge';
        $knowledge_sql = "CREATE TABLE IF NOT EXISTS $knowledge_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            keyword VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            metadata JSON,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FULLTEXT KEY keyword_content (keyword, content),
            INDEX idx_keyword_created (keyword(191), created_at DESC)
        ) $charset_collate;";
        
        // Usage tracking table
        $usage_table = $wpdb->prefix . 'autoblogger_usage';
        $usage_sql = "CREATE TABLE IF NOT EXISTS $usage_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            operation VARCHAR(50) NOT NULL,
            tokens_input INT UNSIGNED NOT NULL,
            tokens_output INT UNSIGNED NOT NULL,
            cost DECIMAL(10, 6) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_user_date (user_id, created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($knowledge_sql);
        dbDelta($usage_sql);
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        // Only set if not already set
        add_option('autoblogger_max_iterations', 2);
        add_option('autoblogger_score_threshold', 80);
        add_option('autoblogger_daily_budget', 5.00);
        add_option('autoblogger_disclaimer_text', AutoBlogger_Config::SAFETY_DEFAULT_DISCLAIMER);
        
        // Default personas
        $default_personas = [
            [
                'name' => 'Academic',
                'prompt' => 'Write in a formal, scholarly tone with proper citations and technical terminology'
            ],
            [
                'name' => 'Simple',
                'prompt' => 'Write in a friendly, conversational tone that\'s easy to understand for general readers'
            ]
        ];
        add_option('autoblogger_personas', wp_json_encode($default_personas));
        
        // Default negative keywords
        $default_negative_keywords = [
            'chắc chắn chết',
            'bỏ thuốc',
            'đừng tin bác sĩ',
            'không cần bác sĩ',
            '100% chính xác',
            'chữa khỏi ung thư',
            'bỏ điều trị'
        ];
        add_option('autoblogger_negative_keywords', wp_json_encode($default_negative_keywords));
        
        // Default expert name
        add_option('autoblogger_expert_name', 'Expert');
    }
}

