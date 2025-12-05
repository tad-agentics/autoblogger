<?php
/**
 * Post Interceptor
 * Handles review workflow, content versioning, and concurrent user locks
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Post_Interceptor {
    
    /**
     * Force pending review status for AI-generated content
     *
     * @param array $data Post data
     * @param array $postarr Post array
     * @return array Modified post data
     */
    public function force_pending_review($data, $postarr) {
        // Check if this is AI-generated content
        $is_ai_generated = isset($postarr['ID']) && 
                          get_post_meta($postarr['ID'], '_autoblogger_generated', true);
        
        // Only intercept if AI-generated and trying to publish
        if ($is_ai_generated && $data['post_status'] === 'publish') {
            // Check if user has explicitly approved
            $approved = get_post_meta($postarr['ID'], '_autoblogger_approved', true);
            
            if (!$approved) {
                $data['post_status'] = 'pending';
                
                AutoBlogger_Logger::info('Post status changed to pending review', [
                    'post_id' => $postarr['ID']
                ]);
                
                // Set flag to show notice
                set_transient('autoblogger_review_notice_' . $postarr['ID'], true, 60);
            }
        }
        
        return $data;
    }
    
    /**
     * Show admin notice for pending review
     */
    public function show_ai_notice() {
        global $post;
        
        if (!$post) {
            return;
        }
        
        $notice_key = 'autoblogger_review_notice_' . $post->ID;
        
        if (get_transient($notice_key)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php esc_html_e('AutoBlogger:', 'autoblogger'); ?></strong>
                    <?php esc_html_e('This AI-generated content requires human review before publishing. Please review, edit as needed, and click "Approve & Publish" when ready.', 'autoblogger'); ?>
                </p>
            </div>
            <?php
            delete_transient($notice_key);
        }
    }
    
    /**
     * Save content version before AI modification
     *
     * @param int $post_id Post ID
     * @param string $content Content
     * @param string $operation Operation type
     * @return bool True on success
     */
    public function save_version($post_id, $content, $operation) {
        $versions = get_post_meta($post_id, '_autoblogger_versions', true) ?: [];
        
        $versions[] = [
            'content' => $content,
            'operation' => $operation,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ];
        
        // Keep only last N versions
        $max_versions = AutoBlogger_Config::get('version_max_keep');
        if (count($versions) > $max_versions) {
            $versions = array_slice($versions, -$max_versions);
        }
        
        $result = update_post_meta($post_id, '_autoblogger_versions', $versions);
        
        AutoBlogger_Logger::debug('Version saved', [
            'post_id' => $post_id,
            'operation' => $operation,
            'version_count' => count($versions)
        ]);
        
        return $result;
    }
    
    /**
     * Get version history
     *
     * @param int $post_id Post ID
     * @return array Version history
     */
    public function get_versions($post_id) {
        return get_post_meta($post_id, '_autoblogger_versions', true) ?: [];
    }
    
    /**
     * Restore specific version
     *
     * @param int $post_id Post ID
     * @param int $version_index Version index
     * @return bool True on success
     */
    public function restore_version($post_id, $version_index) {
        $versions = $this->get_versions($post_id);
        
        if (!isset($versions[$version_index])) {
            return false;
        }
        
        // Save current content before restoring
        $current = get_post_field('post_content', $post_id);
        $this->save_version($post_id, $current, 'pre_restore');
        
        // Restore
        $result = wp_update_post([
            'ID' => $post_id,
            'post_content' => $versions[$version_index]['content']
        ]);
        
        if (!is_wp_error($result)) {
            AutoBlogger_Logger::info('Version restored', [
                'post_id' => $post_id,
                'version_index' => $version_index
            ]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Acquire lock for post (prevent concurrent editing)
     *
     * @param int $post_id Post ID
     * @param int $user_id User ID
     * @return bool True if lock acquired
     * @throws Exception If post is locked by another user
     */
    public function acquire_lock($post_id, $user_id) {
        $lock_key = "autoblogger_lock_{$post_id}";
        $existing_lock = get_transient($lock_key);
        
        if ($existing_lock && $existing_lock !== $user_id) {
            $lock_user = get_userdata($existing_lock);
            $lock_user_name = $lock_user ? $lock_user->display_name : 'Another user';
            
            AutoBlogger_Logger::warning('Post locked by another user', [
                'post_id' => $post_id,
                'locked_by' => $existing_lock,
                'attempted_by' => $user_id
            ]);
            
            throw new Exception(
                sprintf('%s is currently generating content for this post. Please wait.', $lock_user_name)
            );
        }
        
        // Set lock
        $timeout = AutoBlogger_Config::get('lock_timeout');
        set_transient($lock_key, $user_id, $timeout);
        
        AutoBlogger_Logger::debug('Lock acquired', [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'timeout' => $timeout
        ]);
        
        return true;
    }
    
    /**
     * Release lock for post
     *
     * @param int $post_id Post ID
     * @param int $user_id User ID
     * @return bool True on success
     */
    public function release_lock($post_id, $user_id) {
        $lock_key = "autoblogger_lock_{$post_id}";
        $existing_lock = get_transient($lock_key);
        
        // Only release if this user owns the lock
        if ($existing_lock === $user_id) {
            delete_transient($lock_key);
            
            AutoBlogger_Logger::debug('Lock released', [
                'post_id' => $post_id,
                'user_id' => $user_id
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if post is locked
     *
     * @param int $post_id Post ID
     * @return array Lock status
     */
    public function check_lock($post_id) {
        $lock_key = "autoblogger_lock_{$post_id}";
        $locked_by = get_transient($lock_key);
        
        if ($locked_by) {
            $user = get_userdata($locked_by);
            return [
                'is_locked' => true,
                'locked_by' => $locked_by,
                'locked_by_name' => $user ? $user->display_name : 'Unknown',
                'expires_in' => $this->get_transient_expiration($lock_key)
            ];
        }
        
        return [
            'is_locked' => false
        ];
    }
    
    /**
     * Get transient expiration time
     *
     * @param string $transient Transient key
     * @return int Seconds until expiration
     */
    private function get_transient_expiration($transient) {
        global $wpdb;
        
        $timeout = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                '_transient_timeout_' . $transient
            )
        );
        
        if ($timeout) {
            return max(0, $timeout - time());
        }
        
        return 0;
    }
    
    /**
     * Mark post as AI-generated
     *
     * @param int $post_id Post ID
     * @param array $metadata Generation metadata
     * @return bool True on success
     */
    public function mark_as_generated($post_id, $metadata = []) {
        update_post_meta($post_id, '_autoblogger_generated', true);
        update_post_meta($post_id, '_autoblogger_metadata', $metadata);
        
        AutoBlogger_Logger::info('Post marked as AI-generated', [
            'post_id' => $post_id
        ]);
        
        return true;
    }
    
    /**
     * Mark post as approved for publishing
     *
     * @param int $post_id Post ID
     * @return bool True on success
     */
    public function approve_post($post_id) {
        update_post_meta($post_id, '_autoblogger_approved', true);
        
        AutoBlogger_Logger::info('Post approved for publishing', [
            'post_id' => $post_id,
            'user_id' => get_current_user_id()
        ]);
        
        return true;
    }
    
    /**
     * Check if post is AI-generated
     *
     * @param int $post_id Post ID
     * @return bool True if AI-generated
     */
    public function is_ai_generated($post_id) {
        return (bool) get_post_meta($post_id, '_autoblogger_generated', true);
    }
    
    /**
     * Get generation metadata
     *
     * @param int $post_id Post ID
     * @return array Metadata
     */
    public function get_metadata($post_id) {
        return get_post_meta($post_id, '_autoblogger_metadata', true) ?: [];
    }
}

