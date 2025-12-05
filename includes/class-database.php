<?php
/**
 * Database operations with transient caching
 * Handles CRUD for knowledge base and usage tracking
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent class name collision with other plugins
if (class_exists('AutoBlogger_Database')) {
    return;
}

class AutoBlogger_Database {
    
    private $wpdb;
    private $knowledge_table;
    private $usage_table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->knowledge_table = $wpdb->prefix . 'autoblogger_knowledge';
        $this->usage_table = $wpdb->prefix . 'autoblogger_usage';
    }
    
    /**
     * Search knowledge base with transient caching
     *
     * @param string $keyword Search keyword
     * @param int $limit Maximum results
     * @return array Search results
     */
    public function search_by_keyword($keyword, $limit = 5) {
        // Simple transient caching (no complex cache setup needed)
        $cache_key = 'autoblogger_search_' . md5($keyword . $limit);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            AutoBlogger_Logger::debug('Cache hit', ['keyword' => $keyword]);
            return $cached;
        }
        
        // Query database with FULLTEXT search
        $sql = $this->wpdb->prepare(
            "SELECT *, MATCH(keyword, content) AGAINST(%s) as relevance
             FROM {$this->knowledge_table}
             WHERE MATCH(keyword, content) AGAINST(%s)
             ORDER BY relevance DESC
             LIMIT %d",
            $keyword, $keyword, min($limit, 100)
        );
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        // Decode JSON content
        foreach ($results as &$result) {
            $result['content'] = json_decode($result['content'], true);
            if (isset($result['metadata'])) {
                $result['metadata'] = json_decode($result['metadata'], true);
            }
        }
        
        // Cache for 1 hour
        set_transient($cache_key, $results, AutoBlogger_Config::get('cache_ttl'));
        
        AutoBlogger_Logger::debug('Cache miss', [
            'keyword' => $keyword,
            'results' => count($results)
        ]);
        
        return $results;
    }
    
    /**
     * Insert knowledge entry
     *
     * @param string $keyword Keyword
     * @param mixed $content Content (will be JSON encoded)
     * @param array $metadata Optional metadata
     * @return int|false Insert ID or false on failure
     */
    public function insert_knowledge($keyword, $content, $metadata = []) {
        $result = $this->wpdb->insert(
            $this->knowledge_table,
            [
                'keyword' => sanitize_text_field($keyword),
                'content' => wp_json_encode($content),
                'metadata' => wp_json_encode($metadata),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result) {
            // Clear related caches
            $this->clear_keyword_cache($keyword);
            
            AutoBlogger_Logger::info('Knowledge added', ['keyword' => $keyword]);
            
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update knowledge entry
     *
     * @param int $id Entry ID
     * @param array $data Data to update
     * @return bool True on success
     */
    public function update_knowledge($id, $data) {
        $update_data = [];
        $formats = [];
        
        if (isset($data['keyword'])) {
            $update_data['keyword'] = sanitize_text_field($data['keyword']);
            $formats[] = '%s';
        }
        
        if (isset($data['content'])) {
            $update_data['content'] = wp_json_encode($data['content']);
            $formats[] = '%s';
        }
        
        if (isset($data['metadata'])) {
            $update_data['metadata'] = wp_json_encode($data['metadata']);
            $formats[] = '%s';
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $formats[] = '%s';
        
        $result = $this->wpdb->update(
            $this->knowledge_table,
            $update_data,
            ['id' => $id],
            $formats,
            ['%d']
        );
        
        if ($result !== false) {
            // Clear caches
            if (isset($data['keyword'])) {
                $this->clear_keyword_cache($data['keyword']);
            }
            $this->clear_all_search_cache();
            
            AutoBlogger_Logger::info('Knowledge updated', ['id' => $id]);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete knowledge entry
     *
     * @param int $id Entry ID
     * @return bool True on success
     */
    public function delete_knowledge($id) {
        $result = $this->wpdb->delete(
            $this->knowledge_table,
            ['id' => $id],
            ['%d']
        );
        
        if ($result) {
            $this->clear_all_search_cache();
            AutoBlogger_Logger::info('Knowledge deleted', ['id' => $id]);
        }
        
        return $result !== false;
    }
    
    /**
     * Get knowledge entry by ID
     *
     * @param int $id Entry ID
     * @return array|null Entry data or null
     */
    public function get_knowledge($id) {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->knowledge_table} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        if ($result) {
            $result['content'] = json_decode($result['content'], true);
            if (isset($result['metadata'])) {
                $result['metadata'] = json_decode($result['metadata'], true);
            }
        }
        
        return $result;
    }
    
    /**
     * Get all knowledge entries with pagination
     *
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array Results with pagination info
     */
    public function get_all_knowledge($page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->knowledge_table}
                 ORDER BY created_at DESC
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
        
        // Decode JSON
        foreach ($results as &$result) {
            $result['content'] = json_decode($result['content'], true);
            if (isset($result['metadata'])) {
                $result['metadata'] = json_decode($result['metadata'], true);
            }
        }
        
        // Get total count
        $total = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->knowledge_table}"
        );
        
        return [
            'items' => $results,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }
    
    /**
     * Log API usage
     *
     * @param int $user_id User ID
     * @param string $operation Operation type
     * @param int $tokens_input Input tokens
     * @param int $tokens_output Output tokens
     * @param float $cost Cost in USD
     * @return int|false Insert ID or false
     */
    public function log_usage($user_id, $operation, $tokens_input, $tokens_output, $cost) {
        $result = $this->wpdb->insert(
            $this->usage_table,
            [
                'user_id' => $user_id,
                'operation' => sanitize_text_field($operation),
                'tokens_input' => $tokens_input,
                'tokens_output' => $tokens_output,
                'cost' => $cost,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%d', '%d', '%f', '%s']
        );
        
        if ($result) {
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get daily usage for user
     *
     * @param int $user_id User ID
     * @param string $date Date (Y-m-d format, defaults to today)
     * @return float Total cost for the day
     */
    public function get_daily_usage($user_id, $date = null) {
        if ($date === null) {
            $date = current_time('Y-m-d');
        }
        
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(cost) FROM {$this->usage_table}
                 WHERE user_id = %d
                 AND DATE(created_at) = %s",
                $user_id,
                $date
            )
        );
        
        return $result ? (float) $result : 0.0;
    }
    
    /**
     * Get usage statistics
     *
     * @param int $user_id User ID
     * @param int $days Number of days to look back
     * @return array Usage statistics
     */
    public function get_usage_stats($user_id, $days = 30) {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT 
                    DATE(created_at) as date,
                    operation,
                    SUM(tokens_input) as total_input,
                    SUM(tokens_output) as total_output,
                    SUM(cost) as total_cost,
                    COUNT(*) as count
                 FROM {$this->usage_table}
                 WHERE user_id = %d
                 AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                 GROUP BY DATE(created_at), operation
                 ORDER BY date DESC",
                $user_id,
                $days
            ),
            ARRAY_A
        );
        
        return $results;
    }
    
    /**
     * Bulk import knowledge entries
     *
     * @param array $entries Array of entries
     * @return array Result with success/failure counts
     */
    public function bulk_import_knowledge($entries) {
        $success = 0;
        $failed = 0;
        
        foreach ($entries as $entry) {
            if (!isset($entry['keyword']) || !isset($entry['content'])) {
                $failed++;
                continue;
            }
            
            $metadata = $entry['metadata'] ?? [];
            
            if ($this->insert_knowledge($entry['keyword'], $entry['content'], $metadata)) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        $this->clear_all_search_cache();
        
        AutoBlogger_Logger::info('Bulk import completed', [
            'success' => $success,
            'failed' => $failed
        ]);
        
        return [
            'success' => $success,
            'failed' => $failed,
            'total' => count($entries)
        ];
    }
    
    /**
     * Clear keyword-specific cache
     *
     * @param string $keyword Keyword
     */
    private function clear_keyword_cache($keyword) {
        // Clear specific keyword cache (common limits)
        foreach ([5, 10, 20] as $limit) {
            $cache_key = 'autoblogger_search_' . md5($keyword . $limit);
            delete_transient($cache_key);
        }
    }
    
    /**
     * Clear all search caches
     */
    private function clear_all_search_cache() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_autoblogger_search_%' 
             OR option_name LIKE '_transient_timeout_autoblogger_search_%'"
        );
    }
}

