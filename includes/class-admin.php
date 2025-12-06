<?php
/**
 * Admin menu and pages
 * Handles WordPress admin interface
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Admin {
    
    /**
     * Register admin menu
     */
    public function register_menu() {
        // Main menu
        add_menu_page(
            __('AutoBlogger', 'autoblogger'),
            __('AutoBlogger', 'autoblogger'),
            'manage_options',
            'autoblogger',
            [$this, 'render_settings_page'],
            'dashicons-edit-large',
            30
        );
        
        // Settings submenu (default)
        add_submenu_page(
            'autoblogger',
            __('Settings', 'autoblogger'),
            __('Settings', 'autoblogger'),
            'manage_options',
            'autoblogger',
            [$this, 'render_settings_page']
        );
        
        // Knowledge Base submenu
        add_submenu_page(
            'autoblogger',
            __('Knowledge Base', 'autoblogger'),
            __('Knowledge Base', 'autoblogger'),
            'manage_options',
            'autoblogger-knowledge',
            [$this, 'render_knowledge_page']
        );
        
        // Usage Dashboard submenu
        add_submenu_page(
            'autoblogger',
            __('Usage & Costs', 'autoblogger'),
            __('Usage & Costs', 'autoblogger'),
            'manage_options',
            'autoblogger-usage',
            [$this, 'render_usage_page']
        );
    }
    
    /**
     * Enqueue admin assets
     * ONLY loads on AutoBlogger admin pages to avoid slowing down other admin pages
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        // CRITICAL SAFETY CHECK: Never load on frontend
        if (!is_admin()) {
            return; // Exit immediately if not in admin
        }
        
        // CRITICAL: Only load on our specific admin pages
        // This prevents loading heavy JS on Comments, Settings, etc.
        // Check if we're on any AutoBlogger page
        if (strpos($hook, 'autoblogger') === false) {
            return; // Exit early - not on our pages
        }
        
        // Enqueue admin React app
        $asset_file = AUTOBLOGGER_PATH . 'assets/js/admin/build/index.asset.php';
        
        if (file_exists($asset_file)) {
            $asset = require $asset_file;
            
            $script_handle = 'autoblogger-admin';
            wp_enqueue_script(
                $script_handle,
                AUTOBLOGGER_URL . 'assets/js/admin/build/index.js',
                $asset['dependencies'],
                $asset['version'],
                true // Load in footer
            );
            
            // Add defer attribute for better performance
            // Heavy React bundle won't block admin page rendering
            add_filter('script_loader_tag', function($tag, $handle) use ($script_handle) {
                if ($handle === $script_handle) {
                    return str_replace(' src', ' defer src', $tag);
                }
                return $tag;
            }, 10, 2);
            
            wp_enqueue_style(
                'autoblogger-admin',
                AUTOBLOGGER_URL . 'assets/js/admin/build/style-index.css',
                ['wp-components'],
                $asset['version']
            );
            
            // Get custom locale for AutoBlogger
            $settings = new AutoBlogger_Settings();
            $custom_locale = $settings->get_effective_locale();
            
            // Set up JavaScript translations
            // WordPress will look for: autoblogger-{locale}-{hash}.json
            // We need to ensure it uses our custom locale, not the site locale
            $translation_file = sprintf(
                '%slanguages/autoblogger-%s-%s.json',
                AUTOBLOGGER_PATH,
                $custom_locale,
                substr($asset['version'], 0, 32)
            );
            
            error_log('AutoBlogger: Looking for translation file: ' . $translation_file);
            error_log('AutoBlogger: File exists: ' . (file_exists($translation_file) ? 'YES' : 'NO'));
            error_log('AutoBlogger: Custom locale: ' . $custom_locale);
            error_log('AutoBlogger: Site locale: ' . get_locale());
            
            // Try setting script translations
            $result = wp_set_script_translations(
                'autoblogger-admin',
                'autoblogger',
                AUTOBLOGGER_PATH . 'languages'
            );
            
            error_log('AutoBlogger: wp_set_script_translations result: ' . ($result ? 'true' : 'false'));
            
            // Localize script
            wp_localize_script('autoblogger-admin', 'autobloggerAdmin', [
                'apiUrl' => rest_url('autoblogger/v1'),
                'nonce' => wp_create_nonce('wp_rest'),
                'currentPage' => $_GET['page'] ?? 'autoblogger',
                'assetsUrl' => AUTOBLOGGER_URL . 'assets/',
                'locale' => $custom_locale
            ]);
        }
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AutoBlogger Settings', 'autoblogger'); ?></h1>
            <div id="autoblogger-settings-root"></div>
        </div>
        <?php
    }
    
    /**
     * Render knowledge base page
     */
    public function render_knowledge_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Knowledge Base', 'autoblogger'); ?></h1>
            <div id="autoblogger-knowledge-root"></div>
        </div>
        <?php
    }
    
    /**
     * Render usage page
     */
    public function render_usage_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Usage & Costs', 'autoblogger'); ?></h1>
            <div id="autoblogger-usage-root"></div>
        </div>
        <?php
    }
}

