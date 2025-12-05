<?php
/**
 * Gutenberg Editor Integration
 * Handles sidebar panel and editor assets
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Gutenberg {
    
    /**
     * Enqueue editor assets
     * ONLY loads in Gutenberg editor, not on other admin pages or frontend
     */
    public function enqueue_assets() {
        // CRITICAL SAFETY CHECK: Never load on frontend
        if (!is_admin()) {
            return; // Exit immediately if not in admin
        }
        
        // CRITICAL: Only load in block editor
        // Check if we're in the post editor screen
        $screen = get_current_screen();
        
        if (!$screen || !$screen->is_block_editor()) {
            return; // Exit early - not in Gutenberg editor
        }
        
        // Only load for post types that support editor
        $supported_post_types = ['post', 'page'];
        $supported_post_types = apply_filters('autoblogger_supported_post_types', $supported_post_types);
        
        if (!in_array($screen->post_type, $supported_post_types)) {
            return; // Exit early - not a supported post type
        }
        
        $asset_file = AUTOBLOGGER_PATH . 'assets/js/editor/build/editor.asset.php';
        
        if (!file_exists($asset_file)) {
            return;
        }
        
        $asset = require $asset_file;
        
        // Enqueue editor script
        $script_handle = 'autoblogger-editor';
        wp_enqueue_script(
            $script_handle,
            AUTOBLOGGER_URL . 'assets/js/editor/build/editor.js',
            $asset['dependencies'],
            $asset['version'],
            true // Load in footer
        );
        
        // Add defer attribute for better performance
        // Heavy React bundle won't block page rendering
        add_filter('script_loader_tag', function($tag, $handle) use ($script_handle) {
            if ($handle === $script_handle) {
                return str_replace(' src', ' defer src', $tag);
            }
            return $tag;
        }, 10, 2);
        
        // Enqueue editor styles
        wp_enqueue_style(
            'autoblogger-editor',
            AUTOBLOGGER_URL . 'assets/js/editor/build/style-editor.css',
            ['wp-edit-post'],
            $asset['version']
        );
        
        // Localize script
        wp_localize_script('autoblogger-editor', 'autobloggerEditor', [
            'apiUrl' => rest_url('autoblogger/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'postId' => get_the_ID(),
            'assetsUrl' => AUTOBLOGGER_URL . 'assets/',
            'config' => [
                'maxIterations' => AutoBlogger_Config::get('opt_max_iterations'),
                'scoreThreshold' => AutoBlogger_Config::get('opt_score_threshold'),
                'autosaveInterval' => AutoBlogger_Config::get('autosave_interval')
            ]
        ]);
    }
}

