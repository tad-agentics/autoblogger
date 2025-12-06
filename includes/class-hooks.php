<?php
/**
 * Centralized hook registration
 * All WordPress hooks in one place for easy overview
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Hooks {
    
    private $post_interceptor;
    private $admin;
    private $gutenberg;
    private $rest_api;
    
    public function __construct() {
        // Dependencies will be initialized when classes are available
    }
    
    /**
     * Register all hooks
     * OPTIMIZED: Only registers hooks needed for current context
     */
    public function register() {
        // Initialization (lightweight, always needed)
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Custom locale filter for AutoBlogger (independent from WordPress language)
        add_filter('locale', [$this, 'override_locale']);
        add_filter('plugin_locale', [$this, 'override_plugin_locale'], 10, 2);
        
        // OPTIMIZATION: Only register blocks on admin or when needed
        // Blocks are only used in editor, not on frontend
        if (is_admin() || wp_doing_ajax() || defined('REST_REQUEST')) {
            add_action('init', [$this, 'register_blocks']);
        }
        
        // OPTIMIZATION: Only initialize heavy dependencies when needed
        // Admin hooks - only in admin
        if (is_admin()) {
            add_action('init', [$this, 'init_admin_dependencies']);
            add_action('admin_menu', [$this, 'register_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('admin_notices', [$this, 'show_admin_notices']);
            add_filter('wp_insert_post_data', [$this, 'intercept_post_data'], 10, 2);
        }
        
        // Editor hooks - only in admin (Gutenberg is admin-only)
        if (is_admin()) {
            add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
        }
        
        // REST API hooks - always register (needed for AJAX/REST)
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // ⭐ Extensibility Hooks (for third-party developers)
        $this->register_extensibility_hooks();
    }
    
    /**
     * Initialize admin dependencies (ONLY in admin context)
     * OPTIMIZATION: Heavy classes only loaded when actually needed
     */
    public function init_admin_dependencies() {
        // Only initialize these heavy classes in admin
        if (class_exists('AutoBlogger_Post_Interceptor')) {
            $this->post_interceptor = new AutoBlogger_Post_Interceptor();
        }
        
        if (class_exists('AutoBlogger_Admin')) {
            $this->admin = new AutoBlogger_Admin();
        }
        
        if (class_exists('AutoBlogger_Gutenberg')) {
            $this->gutenberg = new AutoBlogger_Gutenberg();
        }
    }
    
    /**
     * Register extensibility hooks
     * These allow other plugins to extend AutoBlogger
     */
    private function register_extensibility_hooks() {
        // These are documentation - actual firing happens in respective classes
        // Listed here for discoverability
        
        /**
         * Fires before content generation starts
         * @param int $post_id Post ID
         * @param string $keyword Main keyword
         * @param array $params Generation parameters
         */
        // do_action('autoblogger_before_generate', $post_id, $keyword, $params);
        
        /**
         * Fires after content generation completes
         * @param int $post_id Post ID
         * @param string $content Generated content
         * @param array $meta Generation metadata
         */
        // do_action('autoblogger_after_generate', $post_id, $content, $meta);
        
        /**
         * Filter generated content before insertion
         * @param string $content Generated content
         * @param int $post_id Post ID
         * @param array $context Generation context
         */
        // $content = apply_filters('autoblogger_generated_content', $content, $post_id, $context);
        
        /**
         * Filter AI prompt before sending to API
         * @param string $prompt The prompt
         * @param string $keyword Main keyword
         * @param array $rag_context RAG context data
         */
        // $prompt = apply_filters('autoblogger_prompt', $prompt, $keyword, $rag_context);
        
        /**
         * Fires when budget is exceeded
         * @param int $user_id User ID
         * @param float $current_usage Current usage amount
         * @param float $limit Budget limit
         */
        // do_action('autoblogger_budget_exceeded', $user_id, $current_usage, $limit);
        
        /**
         * Fires when safety filter detects issues
         * @param string $content Content with issues
         * @param array $issues Array of detected issues
         * @param int $post_id Post ID
         */
        // do_action('autoblogger_safety_issues_detected', $content, $issues, $post_id);
        
        /**
         * Fires before API call
         * @param string $prompt The prompt
         * @param int $max_tokens Maximum tokens
         */
        // do_action('autoblogger_before_api_call', $prompt, $max_tokens);
        
        /**
         * Fires after successful API call
         * @param string $content Generated content
         * @param string $prompt The prompt used
         */
        // do_action('autoblogger_after_api_call', $content, $prompt);
        
        /**
         * Fires when API call fails
         * @param string $error Error message
         * @param string $prompt The prompt used
         */
        // do_action('autoblogger_api_call_failed', $error, $prompt);
        
        /**
         * Fires when prompt is updated
         * @param string $template_name Template name
         * @param string $content New prompt content
         */
        // do_action('autoblogger_prompt_updated', $template_name, $content);
        
        /**
         * Filter rendered prompt
         * @param string $template Rendered template
         * @param string $template_name Template name
         * @param array $data Placeholder data
         */
        // $template = apply_filters('autoblogger_prompt_rendered', $template, $template_name, $data);
        
        /**
         * Fires on any log entry
         * @param int $level Log level
         * @param string $message Log message
         * @param array $context Log context
         */
        // do_action('autoblogger_log', $level, $message, $context);
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'autoblogger',
            false,
            dirname(plugin_basename(AUTOBLOGGER_PATH)) . '/languages/'
        );
    }
    
    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        if (function_exists('register_block_type')) {
            $disclaimer_path = AUTOBLOGGER_PATH . 'blocks/disclaimer-block';
            $expert_note_path = AUTOBLOGGER_PATH . 'blocks/expert-note-block';
            
            if (file_exists($disclaimer_path)) {
                register_block_type($disclaimer_path);
            }
            
            if (file_exists($expert_note_path)) {
                register_block_type($expert_note_path);
            }
        }
    }
    
    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        if ($this->admin && method_exists($this->admin, 'register_menu')) {
            $this->admin->register_menu();
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($this->admin && method_exists($this->admin, 'enqueue_assets')) {
            $this->admin->enqueue_assets($hook);
        }
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        if ($this->post_interceptor && method_exists($this->post_interceptor, 'show_ai_notice')) {
            $this->post_interceptor->show_ai_notice();
        }
    }
    
    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        // Initialize Gutenberg if not already done
        if (!$this->gutenberg && class_exists('AutoBlogger_Gutenberg')) {
            $this->gutenberg = new AutoBlogger_Gutenberg();
        }
        
        if ($this->gutenberg && method_exists($this->gutenberg, 'enqueue_assets')) {
            $this->gutenberg->enqueue_assets();
        }
    }
    
    /**
     * Register REST routes
     * OPTIMIZATION: REST API class only initialized when actually registering routes
     */
    public function register_rest_routes() {
        // Debug: Log that this method is being called
        error_log('AutoBlogger: register_rest_routes() called');
        
        // Check if class exists
        if (!class_exists('AutoBlogger_REST_API')) {
            error_log('AutoBlogger: AutoBlogger_REST_API class not found!');
            return;
        }
        
        error_log('AutoBlogger: AutoBlogger_REST_API class exists');
        
        // Lazy load REST API class only when needed
        if (!$this->rest_api) {
            try {
                error_log('AutoBlogger: Attempting to instantiate AutoBlogger_REST_API');
                $this->rest_api = new AutoBlogger_REST_API();
                error_log('AutoBlogger: AutoBlogger_REST_API instantiated successfully');
            } catch (Exception $e) {
                // Log error but don't break WordPress
                error_log('AutoBlogger REST API initialization failed: ' . $e->getMessage());
                error_log('AutoBlogger REST API trace: ' . $e->getTraceAsString());
                
                if (class_exists('AutoBlogger_Logger')) {
                    AutoBlogger_Logger::error('Failed to initialize REST API', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                return; // Exit early if REST API can't be initialized
            }
        }
        
        if ($this->rest_api && method_exists($this->rest_api, 'register_routes')) {
            try {
                error_log('AutoBlogger: Calling register_routes()');
                $this->rest_api->register_routes();
                error_log('AutoBlogger: Routes registered successfully');
            } catch (Exception $e) {
                // Log error but don't break WordPress
                error_log('AutoBlogger REST route registration failed: ' . $e->getMessage());
                error_log('AutoBlogger REST route trace: ' . $e->getTraceAsString());
                
                if (class_exists('AutoBlogger_Logger')) {
                    AutoBlogger_Logger::error('Failed to register REST routes', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        } else {
            error_log('AutoBlogger: REST API object is null or register_routes method not found');
        }
    }
    
    /**
     * Intercept post data
     */
    public function intercept_post_data($data, $postarr) {
        if ($this->post_interceptor && method_exists($this->post_interceptor, 'force_pending_review')) {
            return $this->post_interceptor->force_pending_review($data, $postarr);
        }
        return $data;
    }
    
    /**
     * Override locale for AutoBlogger only
     * This allows AutoBlogger to have its own language independent of WordPress
     *
     * @param string $locale Current locale
     * @return string Modified locale
     */
    public function override_locale($locale) {
        // Only override in AutoBlogger admin pages
        if (!$this->is_autoblogger_page()) {
            return $locale;
        }
        
        $language = get_option('autoblogger_language', 'auto');
        
        if ($language === 'auto') {
            return $locale;
        }
        
        return $language;
    }
    
    /**
     * Override plugin locale specifically for AutoBlogger text domain
     *
     * @param string $locale Current locale
     * @param string $domain Text domain
     * @return string Modified locale
     */
    public function override_plugin_locale($locale, $domain) {
        // Only override for autoblogger text domain
        if ($domain !== 'autoblogger') {
            return $locale;
        }
        
        $language = get_option('autoblogger_language', 'auto');
        
        if ($language === 'auto') {
            return $locale;
        }
        
        return $language;
    }
    
    /**
     * Check if current page is an AutoBlogger page
     *
     * @return bool True if AutoBlogger page
     */
    private function is_autoblogger_page() {
        // Check $_GET for our admin pages (safer than get_current_screen)
        if (is_admin()) {
            $page = $_GET['page'] ?? '';
            if (strpos($page, 'autoblogger') !== false) {
                return true;
            }
            
            // Check AJAX requests
            if (wp_doing_ajax()) {
                $action = $_REQUEST['action'] ?? '';
                if (strpos($action, 'autoblogger') !== false) {
                    return true;
                }
            }
        }
        
        // Check REST API requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($request_uri, '/autoblogger/') !== false) {
                return true;
            }
        }
        
        return false;
    }
}

