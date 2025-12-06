<?php
/**
 * REST API endpoints
 * Handles all API routes for AI operations, knowledge base, and settings
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_REST_API {
    
    private $namespace = 'autoblogger/v1';
    private $ai_service;
    private $database;
    private $rag_engine;
    private $cost_tracker;
    private $settings;
    private $post_interceptor;
    private $content_filter;
    
    public function __construct() {
        // Initialize only the dependencies that are always needed
        // Others will be lazy-loaded when needed
        $this->settings = new AutoBlogger_Settings();
        $this->database = new AutoBlogger_Database();
        
        // Lazy-load heavy dependencies only when actually used
        // This prevents initialization failures from blocking REST API registration
    }
    
    /**
     * Lazy-load AI Service (only when needed for AI operations)
     */
    private function get_ai_service() {
        if (!$this->ai_service) {
            $this->ai_service = new AutoBlogger_AI_Service();
        }
        return $this->ai_service;
    }
    
    /**
     * Lazy-load RAG Engine
     */
    private function get_rag_engine() {
        if (!$this->rag_engine) {
            $this->rag_engine = new AutoBlogger_RAG_Engine();
        }
        return $this->rag_engine;
    }
    
    /**
     * Lazy-load Cost Tracker
     */
    private function get_cost_tracker() {
        if (!$this->cost_tracker) {
            $this->cost_tracker = new AutoBlogger_Cost_Tracker();
        }
        return $this->cost_tracker;
    }
    
    /**
     * Lazy-load Post Interceptor
     */
    private function get_post_interceptor() {
        if (!$this->post_interceptor) {
            $this->post_interceptor = new AutoBlogger_Post_Interceptor();
        }
        return $this->post_interceptor;
    }
    
    /**
     * Lazy-load Content Filter
     */
    private function get_content_filter() {
        if (!$this->content_filter) {
            $this->content_filter = new AutoBlogger_Content_Filter();
        }
        return $this->content_filter;
    }
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        // AI Operations (HIGH COST - Strict permissions)
        register_rest_route($this->namespace, '/generate/draft', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_draft'],
            'permission_callback' => [$this, 'check_ai_permissions'],
            'args' => [
                'post_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => [$this, 'validate_post_id']
                ],
                'keyword' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        register_rest_route($this->namespace, '/generate/outline', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_outline'],
            'permission_callback' => [$this, 'check_ai_permissions'],
            'args' => [
                'keyword' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        register_rest_route($this->namespace, '/generate/section', [
            'methods' => 'POST',
            'callback' => [$this, 'generate_section'],
            'permission_callback' => [$this, 'check_ai_permissions'],
            'args' => [
                'heading' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'keyword' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        register_rest_route($this->namespace, '/optimize', [
            'methods' => 'POST',
            'callback' => [$this, 'optimize_content'],
            'permission_callback' => [$this, 'check_ai_permissions'],
            'args' => [
                'content' => [
                    'required' => true,
                    'type' => 'string'
                ],
                'keyword' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ]
            ]
        ]);
        
        register_rest_route($this->namespace, '/expand', [
            'methods' => 'POST',
            'callback' => [$this, 'expand_text'],
            'permission_callback' => [$this, 'check_ai_permissions'],
            'args' => [
                'text' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ]
            ]
        ]);
        
        // Knowledge Base (Admin only)
        register_rest_route($this->namespace, '/knowledge', [
            'methods' => 'GET',
            'callback' => [$this, 'get_knowledge'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);
        
        register_rest_route($this->namespace, '/knowledge', [
            'methods' => 'POST',
            'callback' => [$this, 'create_knowledge'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'keyword' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ],
                'content' => [
                    'required' => true
                ]
            ]
        ]);
        
        register_rest_route($this->namespace, '/knowledge/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_knowledge'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);
        
        register_rest_route($this->namespace, '/knowledge/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_knowledge'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);
        
        register_rest_route($this->namespace, '/knowledge/import', [
            'methods' => 'POST',
            'callback' => [$this, 'import_knowledge'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'entries' => [
                    'required' => true,
                    'type' => 'array'
                ]
            ]
        ]);
        
        // Settings (Admin only)
        register_rest_route($this->namespace, '/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);
        
        register_rest_route($this->namespace, '/settings', [
            'methods' => 'POST',
            'callback' => [$this, 'save_settings'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);
        
        // Usage & Costs (Read-only for editors, write for admin)
        register_rest_route($this->namespace, '/usage/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_usage_stats'],
            'permission_callback' => [$this, 'check_editor_permissions']
        ]);
        
        register_rest_route($this->namespace, '/usage/budget', [
            'methods' => 'GET',
            'callback' => [$this, 'get_budget_status'],
            'permission_callback' => [$this, 'check_editor_permissions']
        ]);
        
        register_rest_route($this->namespace, '/cost/estimate', [
            'methods' => 'POST',
            'callback' => [$this, 'estimate_cost'],
            'permission_callback' => [$this, 'check_editor_permissions'],
            'args' => [
                'prompt' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ]
            ]
        ]);
        
        // Post Management
        register_rest_route($this->namespace, '/post/(?P<id>\d+)/versions', [
            'methods' => 'GET',
            'callback' => [$this, 'get_versions'],
            'permission_callback' => [$this, 'check_post_permissions']
        ]);
        
        register_rest_route($this->namespace, '/post/(?P<id>\d+)/restore', [
            'methods' => 'POST',
            'callback' => [$this, 'restore_version'],
            'permission_callback' => [$this, 'check_post_permissions'],
            'args' => [
                'version_index' => [
                    'required' => true,
                    'type' => 'integer'
                ]
            ]
        ]);
        
        register_rest_route($this->namespace, '/post/(?P<id>\d+)/approve', [
            'methods' => 'POST',
            'callback' => [$this, 'approve_post'],
            'permission_callback' => [$this, 'check_post_permissions']
        ]);
        
        // Prompts (Admin only)
        register_rest_route($this->namespace, '/prompts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_prompts'],
            'permission_callback' => [$this, 'check_admin_permissions']
        ]);
        
        register_rest_route($this->namespace, '/prompts/(?P<name>[a-z-]+)', [
            'methods' => 'POST',
            'callback' => [$this, 'save_prompt'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'content' => [
                    'required' => true,
                    'type' => 'string'
                ]
            ]
        ]);
    }
    
    /**
     * Check AI operation permissions (Editor or higher + nonce)
     * These operations cost money, so we're strict
     */
    public function check_ai_permissions($request) {
        // Check nonce
        if (!$this->verify_nonce($request)) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed. Please refresh the page and try again.', 'autoblogger'),
                ['status' => 403]
            );
        }
        
        // Check capability
        if (!current_user_can('edit_posts')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to use AI features.', 'autoblogger'),
                ['status' => 403]
            );
        }
        
        // Rate limiting check
        if (!$this->check_rate_limit()) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Too many requests. Please wait a moment and try again.', 'autoblogger'),
                ['status' => 429]
            );
        }
        
        return true;
    }
    
    /**
     * Check admin permissions (Admin only + nonce)
     */
    public function check_admin_permissions($request) {
        // Check nonce
        if (!$this->verify_nonce($request)) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed. Please refresh the page and try again.', 'autoblogger'),
                ['status' => 403]
            );
        }
        
        // Check capability
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You must be an administrator to perform this action.', 'autoblogger'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Check editor permissions (Editor or higher + nonce)
     */
    public function check_editor_permissions($request) {
        // Check nonce
        if (!$this->verify_nonce($request)) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed. Please refresh the page and try again.', 'autoblogger'),
                ['status' => 403]
            );
        }
        
        // Check capability
        if (!current_user_can('edit_posts')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to access this resource.', 'autoblogger'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Check post-specific permissions (Can edit this specific post + nonce)
     */
    public function check_post_permissions($request) {
        // Check nonce
        if (!$this->verify_nonce($request)) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed. Please refresh the page and try again.', 'autoblogger'),
                ['status' => 403]
            );
        }
        
        $post_id = (int) $request['id'];
        
        // Check if user can edit this specific post
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to edit this post.', 'autoblogger'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Verify nonce from request header
     */
    private function verify_nonce($request) {
        $nonce = $request->get_header('X-WP-Nonce');
        
        if (empty($nonce)) {
            AutoBlogger_Logger::warning('Missing nonce in API request', [
                'endpoint' => $request->get_route(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return false;
        }
        
        $verified = wp_verify_nonce($nonce, 'wp_rest');
        
        if (!$verified) {
            AutoBlogger_Logger::warning('Invalid nonce in API request', [
                'endpoint' => $request->get_route(),
                'user_id' => get_current_user_id(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        
        return $verified;
    }
    
    /**
     * Rate limiting to prevent abuse
     */
    private function check_rate_limit() {
        $user_id = get_current_user_id();
        $transient_key = "autoblogger_rate_limit_{$user_id}";
        
        $request_count = get_transient($transient_key);
        
        if ($request_count === false) {
            // First request in this minute
            set_transient($transient_key, 1, 60); // 1 minute
            return true;
        }
        
        // Allow max 30 requests per minute per user
        if ($request_count >= 30) {
            AutoBlogger_Logger::warning('Rate limit exceeded', [
                'user_id' => $user_id,
                'requests' => $request_count,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return false;
        }
        
        // Increment counter
        set_transient($transient_key, $request_count + 1, 60);
        return true;
    }
    
    /**
     * Validate post ID exists and is editable
     */
    public function validate_post_id($post_id, $request, $key) {
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error(
                'invalid_post',
                __('Post not found.', 'autoblogger'),
                ['status' => 404]
            );
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return new WP_Error(
                'insufficient_permissions',
                __('You cannot edit this post.', 'autoblogger'),
                ['status' => 403]
            );
        }
        
        return true;
    }
    
    /**
     * Generate draft
     */
    public function generate_draft($request) {
        try {
            // CRITICAL: Increase PHP timeout - AI generation can take 60-120 seconds
            @set_time_limit(180); // 3 minutes for full draft
            @ini_set('max_execution_time', 180);
            
            $post_id = $request['post_id'];
            $keyword = sanitize_text_field($request['keyword']);
            $persona = sanitize_text_field($request['persona'] ?? 'Academic');
            $human_story = sanitize_textarea_field($request['human_story'] ?? '');
            $outline = sanitize_textarea_field($request['outline'] ?? '');
            
            // Acquire lock
            $this->get_post_interceptor()->acquire_lock($post_id, get_current_user_id());
            
            // Check budget
            $this->get_cost_tracker()->check_daily_budget(get_current_user_id(), 0.5);
            
            // Fire before generation event
            do_action('autoblogger_before_generate', $post_id, $keyword, $request->get_params());
            
            // Retrieve RAG context
            $rag_result = $this->get_rag_engine()->retrieve_context($keyword);
            
            // Build data for prompt
            $data = [
                'keyword' => $keyword,
                'knowledge_context' => $rag_result['context'],
                'sources' => implode("\n", $rag_result['sources']),
                'outline' => $outline,
                'human_story' => $human_story,
                'persona' => $persona,
                'topic_domain' => 'general'
            ];
            
            // Generate content
            $content = $this->get_ai_service()->generate_draft($data);
            
            // Filter for safety
            $filter_result = $this->get_content_filter()->filter_content($content, $post_id);
            $content = $filter_result['content'];
            
            // Log usage (estimate for now, actual tokens would come from API response)
            $cost_estimate = $this->get_cost_tracker()->estimate_cost($content);
            $this->get_cost_tracker()->log_usage(
                get_current_user_id(),
                'generate_draft',
                $cost_estimate['input_tokens'],
                $cost_estimate['output_tokens']
            );
            
            // Save version
            $current_content = get_post_field('post_content', $post_id);
            $this->get_post_interceptor()->save_version($post_id, $current_content, 'before_generate');
            
            // Mark as AI-generated
            $this->get_post_interceptor()->mark_as_generated($post_id, [
                'keyword' => $keyword,
                'persona' => $persona,
                'generated_at' => current_time('mysql')
            ]);
            
            // Release lock
            $this->get_post_interceptor()->release_lock($post_id, get_current_user_id());
            
            // Fire after generation event
            do_action('autoblogger_after_generate', $post_id, $content, [
                'cost' => $cost_estimate['total_cost'],
                'tokens' => $cost_estimate['total_tokens']
            ]);
            
            return new WP_REST_Response([
                'success' => true,
                'content' => $content,
                'safety_issues' => $filter_result['issues'],
                'cost' => $cost_estimate
            ], 200);
            
        } catch (AutoBlogger_Budget_Exception $e) {
            return AutoBlogger_Error_Handler::handle('budget_exceeded', [
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            AutoBlogger_Logger::error('Generate draft failed', [
                'error' => $e->getMessage()
            ]);
            
            // Release lock on error
            if (isset($post_id)) {
                $this->get_post_interceptor()->release_lock($post_id, get_current_user_id());
            }
            
            return new WP_Error('generation_failed', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Generate outline
     */
    public function generate_outline($request) {
        try {
            // Increase PHP timeout - outline generation usually fast but can take 30s
            @set_time_limit(60); // 1 minute
            @ini_set('max_execution_time', 60);
            
            $keyword = sanitize_text_field($request['keyword']);
            
            // Check budget
            $this->get_cost_tracker()->check_daily_budget(get_current_user_id(), 0.1);
            
            // Retrieve context
            $rag_result = $this->get_rag_engine()->retrieve_context($keyword);
            
            // Generate outline
            $outline = $this->get_ai_service()->generate_outline([
                'keyword' => $keyword,
                'knowledge_context' => $rag_result['context']
            ]);
            
            // Log usage
            $cost_estimate = $this->get_cost_tracker()->estimate_cost($outline, 2000);
            $this->get_cost_tracker()->log_usage(
                get_current_user_id(),
                'generate_outline',
                $cost_estimate['input_tokens'],
                $cost_estimate['output_tokens']
            );
            
            return new WP_REST_Response([
                'success' => true,
                'outline' => $outline,
                'cost' => $cost_estimate
            ], 200);
            
        } catch (Exception $e) {
            return new WP_Error('generation_failed', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Generate section
     */
    public function generate_section($request) {
        try {
            $heading = sanitize_text_field($request['heading']);
            $keyword = sanitize_text_field($request['keyword']);
            $context = sanitize_textarea_field($request['context'] ?? '');
            $persona = sanitize_text_field($request['persona'] ?? 'Academic');
            $is_intro = (bool) ($request['is_intro'] ?? false);
            $is_conclusion = (bool) ($request['is_conclusion'] ?? false);
            $target_length = (int) ($request['target_length'] ?? 300);
            
            // CRITICAL: Increase PHP timeout - section generation can take 60-90 seconds
            @set_time_limit(120); // 2 minutes per section
            @ini_set('max_execution_time', 120);
            
            // Check budget
            $this->get_cost_tracker()->check_daily_budget(get_current_user_id(), 0.2);
            
            // Retrieve knowledge (with caching)
            $rag_result = $this->get_rag_engine()->retrieve_context($keyword, ['limit' => 3]);
            
            // Build prompt data
            $prompt_data = [
                'heading' => $heading,
                'context' => $context,
                'knowledge_context' => $rag_result['context'],
                'persona' => $persona,
                'target_length' => $target_length
            ];
            
            // Add special instructions for intro/conclusion
            if ($is_intro) {
                $prompt_data['section_type'] = 'introduction';
            } elseif ($is_conclusion) {
                $prompt_data['section_type'] = 'conclusion';
            }
            
            // Generate section
            $section = $this->get_ai_service()->generate_section($prompt_data);
            
            // Log usage
            $cost_estimate = $this->get_cost_tracker()->estimate_cost($section, 1500);
            $this->get_cost_tracker()->log_usage(
                get_current_user_id(),
                'generate_section',
                $cost_estimate['input_tokens'],
                $cost_estimate['output_tokens']
            );
            
            AutoBlogger_Logger::info('Section generated', [
                'heading' => $heading,
                'length' => strlen($section),
                'cost' => $cost_estimate['total_cost']
            ]);
            
            return new WP_REST_Response([
                'success' => true,
                'content' => $section,
                'cost' => $cost_estimate
            ], 200);
            
        } catch (Exception $e) {
            AutoBlogger_Logger::error('Section generation failed', [
                'heading' => $request['heading'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return new WP_Error('generation_failed', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Optimize content
     */
    public function optimize_content($request) {
        try {
            // Increase PHP timeout - optimization can take 60 seconds
            @set_time_limit(120); // 2 minutes
            @ini_set('max_execution_time', 120);
            
            $content = wp_kses_post($request['content']);
            $keyword = sanitize_text_field($request['keyword']);
            $seo_issues = $request['seo_issues'] ?? [];
            $persona = sanitize_text_field($request['persona'] ?? 'Academic');
            
            // Check budget
            $this->get_cost_tracker()->check_daily_budget(get_current_user_id(), 0.3);
            
            // Format SEO issues
            $issues_text = '';
            foreach ($seo_issues as $issue) {
                $issues_text .= "- " . $issue . "\n";
            }
            
            // Optimize
            $optimized = $this->get_ai_service()->optimize_content([
                'content' => $content,
                'keyword' => $keyword,
                'seo_issues' => $issues_text,
                'persona' => $persona
            ]);
            
            // Log usage
            $cost_estimate = $this->get_cost_tracker()->estimate_cost($optimized);
            $this->get_cost_tracker()->log_usage(
                get_current_user_id(),
                'optimize_content',
                $cost_estimate['input_tokens'],
                $cost_estimate['output_tokens']
            );
            
            return new WP_REST_Response([
                'success' => true,
                'content' => $optimized,
                'cost' => $cost_estimate
            ], 200);
            
        } catch (Exception $e) {
            return new WP_Error('optimization_failed', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Expand text
     */
    public function expand_text($request) {
        try {
            // Increase PHP timeout - text expansion can take 30-60 seconds
            @set_time_limit(90); // 1.5 minutes
            @ini_set('max_execution_time', 90);
            
            $text = sanitize_textarea_field($request['text']);
            $target_length = (int) ($request['target_length'] ?? 300);
            $persona = sanitize_text_field($request['persona'] ?? 'Academic');
            
            // Check budget
            $this->get_cost_tracker()->check_daily_budget(get_current_user_id(), 0.2);
            
            // Expand
            $expanded = $this->get_ai_service()->expand_text([
                'text' => $text,
                'target_length' => $target_length,
                'persona' => $persona
            ]);
            
            // Log usage
            $cost_estimate = $this->get_cost_tracker()->estimate_cost($expanded, 2000);
            $this->get_cost_tracker()->log_usage(
                get_current_user_id(),
                'expand_text',
                $cost_estimate['input_tokens'],
                $cost_estimate['output_tokens']
            );
            
            return new WP_REST_Response([
                'success' => true,
                'content' => $expanded,
                'cost' => $cost_estimate
            ], 200);
            
        } catch (Exception $e) {
            return new WP_Error('expansion_failed', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Get knowledge entries
     */
    public function get_knowledge($request) {
        $page = (int) ($request['page'] ?? 1);
        $per_page = (int) ($request['per_page'] ?? 20);
        
        $result = $this->database->get_all_knowledge($page, $per_page);
        
        return new WP_REST_Response($result, 200);
    }
    
    /**
     * Create knowledge entry
     */
    public function create_knowledge($request) {
        $keyword = sanitize_text_field($request['keyword']);
        $content = $request['content']; // Already validated as JSON
        $metadata = $request['metadata'] ?? [];
        
        $id = $this->database->insert_knowledge($keyword, $content, $metadata);
        
        if ($id) {
            return new WP_REST_Response([
                'success' => true,
                'id' => $id
            ], 201);
        }
        
        return new WP_Error('creation_failed', 'Failed to create knowledge entry', ['status' => 500]);
    }
    
    /**
     * Update knowledge entry
     */
    public function update_knowledge($request) {
        $id = (int) $request['id'];
        $data = [];
        
        if (isset($request['keyword'])) {
            $data['keyword'] = sanitize_text_field($request['keyword']);
        }
        
        if (isset($request['content'])) {
            $data['content'] = $request['content'];
        }
        
        if (isset($request['metadata'])) {
            $data['metadata'] = $request['metadata'];
        }
        
        $result = $this->database->update_knowledge($id, $data);
        
        if ($result) {
            return new WP_REST_Response(['success' => true], 200);
        }
        
        return new WP_Error('update_failed', 'Failed to update knowledge entry', ['status' => 500]);
    }
    
    /**
     * Delete knowledge entry
     */
    public function delete_knowledge($request) {
        $id = (int) $request['id'];
        
        $result = $this->database->delete_knowledge($id);
        
        if ($result) {
            return new WP_REST_Response(['success' => true], 200);
        }
        
        return new WP_Error('deletion_failed', 'Failed to delete knowledge entry', ['status' => 500]);
    }
    
    /**
     * Import knowledge entries
     */
    public function import_knowledge($request) {
        $entries = $request['entries'] ?? [];
        
        if (empty($entries)) {
            return new WP_Error('invalid_data', 'No entries provided', ['status' => 400]);
        }
        
        $result = $this->database->bulk_import_knowledge($entries);
        
        return new WP_REST_Response($result, 200);
    }
    
    /**
     * Get settings
     */
    public function get_settings($request) {
        $settings = $this->settings->get_all_settings();
        return new WP_REST_Response([
            'success' => true,
            'data' => $settings
        ], 200);
    }
    
    /**
     * Save settings
     */
    public function save_settings($request) {
        $settings = $request->get_params();
        
        $result = $this->settings->save_settings($settings);
        
        if ($result) {
            return new WP_REST_Response(['success' => true], 200);
        }
        
        return new WP_Error('save_failed', 'Failed to save settings', ['status' => 500]);
    }
    
    /**
     * Get usage stats
     */
    public function get_usage_stats($request) {
        $days = (int) ($request['days'] ?? 30);
        $user_id = get_current_user_id();
        
        $stats = $this->get_cost_tracker()->get_usage_stats($user_id, $days);
        $monthly = $this->get_cost_tracker()->get_monthly_summary($user_id);
        
        return new WP_REST_Response([
            'daily_stats' => $stats,
            'monthly_summary' => $monthly
        ], 200);
    }
    
    /**
     * Get budget status
     */
    public function get_budget_status($request) {
        $user_id = get_current_user_id();
        $status = $this->get_cost_tracker()->get_budget_status($user_id);
        
        return new WP_REST_Response($status, 200);
    }
    
    /**
     * Estimate cost
     */
    public function estimate_cost($request) {
        $prompt = sanitize_textarea_field($request['prompt'] ?? '');
        $max_output = (int) ($request['max_output'] ?? 4000);
        
        $estimate = $this->get_cost_tracker()->estimate_cost($prompt, $max_output);
        
        return new WP_REST_Response($estimate, 200);
    }
    
    /**
     * Get post versions
     */
    public function get_versions($request) {
        $post_id = (int) $request['id'];
        $versions = $this->get_post_interceptor()->get_versions($post_id);
        
        return new WP_REST_Response($versions, 200);
    }
    
    /**
     * Restore version
     */
    public function restore_version($request) {
        $post_id = (int) $request['id'];
        $version_index = (int) $request['version_index'];
        
        $result = $this->get_post_interceptor()->restore_version($post_id, $version_index);
        
        if ($result) {
            return new WP_REST_Response(['success' => true], 200);
        }
        
        return new WP_Error('restore_failed', 'Failed to restore version', ['status' => 500]);
    }
    
    /**
     * Approve post
     */
    public function approve_post($request) {
        $post_id = (int) $request['id'];
        
        $result = $this->get_post_interceptor()->approve_post($post_id);
        
        if ($result) {
            return new WP_REST_Response(['success' => true], 200);
        }
        
        return new WP_Error('approval_failed', 'Failed to approve post', ['status' => 500]);
    }
    
    /**
     * Get prompts
     */
    public function get_prompts($request) {
        $prompt_manager = new AutoBlogger_Prompt_Manager();
        $templates = $prompt_manager->get_all_templates();
        
        // Extract just the content for each prompt (either custom or default)
        $prompts = [];
        foreach ($templates as $name => $template) {
            $prompts[$name] = $prompt_manager->get_template($name);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => $prompts
        ], 200);
    }
    
    /**
     * Save prompt
     */
    public function save_prompt($request) {
        $name = sanitize_text_field($request['name']);
        $content = wp_kses_post($request['content']);
        
        $prompt_manager = new AutoBlogger_Prompt_Manager();
        $result = $prompt_manager->save_custom_prompt($name, $content);
        
        if ($result) {
            return new WP_REST_Response(['success' => true], 200);
        }
        
        return new WP_Error('save_failed', 'Failed to save prompt', ['status' => 500]);
    }
}

