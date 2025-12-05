<?php
/**
 * RAG (Retrieval Augmented Generation) Engine
 * Handles knowledge base retrieval with JSON slicing for cost optimization
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_RAG_Engine {
    
    private $database;
    
    public function __construct() {
        $this->database = new AutoBlogger_Database();
    }
    
    /**
     * Retrieve context for keyword
     *
     * @param string $keyword Main keyword
     * @param array $options Retrieval options
     * @return array Context data
     */
    public function retrieve_context($keyword, $options = []) {
        $defaults = [
            'limit' => 5,
            'slice_path' => null, // e.g., 'meanings.money' for JSON slicing
            'include_sources' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        // Search knowledge base
        $results = $this->database->search_by_keyword($keyword, $options['limit']);
        
        if (empty($results)) {
            AutoBlogger_Logger::info('No knowledge base results', ['keyword' => $keyword]);
            return [
                'context' => '',
                'sources' => []
            ];
        }
        
        $context_parts = [];
        $sources = [];
        
        foreach ($results as $result) {
            // Apply JSON slicing if path specified
            if ($options['slice_path']) {
                $sliced_content = $this->get_knowledge_slice($result['content'], $options['slice_path']);
                if ($sliced_content) {
                    $context_parts[] = $this->format_context($result['keyword'], $sliced_content);
                }
            } else {
                $context_parts[] = $this->format_context($result['keyword'], $result['content']);
            }
            
            // Collect sources
            if ($options['include_sources'] && isset($result['metadata']['source'])) {
                $sources[] = $result['metadata']['source'];
            }
        }
        
        $context = implode("\n\n---\n\n", $context_parts);
        
        AutoBlogger_Logger::debug('Context retrieved', [
            'keyword' => $keyword,
            'results' => count($results),
            'context_length' => strlen($context)
        ]);
        
        return [
            'context' => $context,
            'sources' => array_unique($sources)
        ];
    }
    
    /**
     * Get specific slice of JSON knowledge
     * Example: get_knowledge_slice($data, 'meanings.money') returns only money-related content
     *
     * @param mixed $content Content (array or object)
     * @param string $path Dot-notation path (e.g., 'meanings.money')
     * @return mixed Sliced content or null
     */
    private function get_knowledge_slice($content, $path) {
        if (empty($path)) {
            return $content;
        }
        
        $keys = explode('.', $path);
        $current = $content;
        
        foreach ($keys as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } elseif (is_object($current) && isset($current->$key)) {
                $current = $current->$key;
            } else {
                AutoBlogger_Logger::debug('Slice path not found', [
                    'path' => $path,
                    'key' => $key
                ]);
                return null;
            }
        }
        
        return $current;
    }
    
    /**
     * Format context for prompt
     *
     * @param string $keyword Keyword
     * @param mixed $content Content
     * @return string Formatted context
     */
    private function format_context($keyword, $content) {
        $formatted = "**{$keyword}**\n";
        
        if (is_array($content)) {
            $formatted .= $this->format_array_recursive($content);
        } elseif (is_string($content)) {
            $formatted .= $content;
        } else {
            $formatted .= wp_json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        return $formatted;
    }
    
    /**
     * Format array recursively for readable output
     *
     * @param array $array Array to format
     * @param int $depth Current depth
     * @return string Formatted string
     */
    private function format_array_recursive($array, $depth = 0) {
        $output = '';
        $indent = str_repeat('  ', $depth);
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $output .= "{$indent}- {$key}:\n";
                $output .= $this->format_array_recursive($value, $depth + 1);
            } else {
                $output .= "{$indent}- {$key}: {$value}\n";
            }
        }
        
        return $output;
    }
    
    /**
     * Smart context retrieval with automatic slicing
     * Analyzes keyword to determine relevant slice
     *
     * @param string $keyword Main keyword
     * @param string $context_hint Optional hint about context (e.g., 'money', 'health')
     * @return array Context data
     */
    public function smart_retrieve($keyword, $context_hint = null) {
        // Map common hints to JSON paths
        $path_mapping = [
            'money' => 'meanings.money',
            'finance' => 'meanings.money',
            'health' => 'meanings.health',
            'career' => 'meanings.career',
            'relationship' => 'meanings.relationship',
            'love' => 'meanings.relationship'
        ];
        
        $slice_path = null;
        
        if ($context_hint && isset($path_mapping[$context_hint])) {
            $slice_path = $path_mapping[$context_hint];
            
            AutoBlogger_Logger::debug('Using smart slicing', [
                'hint' => $context_hint,
                'path' => $slice_path
            ]);
        }
        
        return $this->retrieve_context($keyword, [
            'slice_path' => $slice_path,
            'limit' => 5
        ]);
    }
    
    /**
     * Retrieve multiple keywords and merge contexts
     *
     * @param array $keywords Array of keywords
     * @param array $options Retrieval options
     * @return array Merged context data
     */
    public function retrieve_multiple($keywords, $options = []) {
        $all_contexts = [];
        $all_sources = [];
        
        foreach ($keywords as $keyword) {
            $result = $this->retrieve_context($keyword, $options);
            
            if (!empty($result['context'])) {
                $all_contexts[] = $result['context'];
            }
            
            if (!empty($result['sources'])) {
                $all_sources = array_merge($all_sources, $result['sources']);
            }
        }
        
        return [
            'context' => implode("\n\n---\n\n", $all_contexts),
            'sources' => array_unique($all_sources)
        ];
    }
    
    /**
     * Get context statistics
     *
     * @param string $context Context string
     * @return array Statistics
     */
    public function get_context_stats($context) {
        $cost_tracker = new AutoBlogger_Cost_Tracker();
        
        return [
            'character_count' => mb_strlen($context, 'UTF-8'),
            'word_count' => str_word_count($context),
            'estimated_tokens' => $cost_tracker->estimate_tokens($context),
            'line_count' => substr_count($context, "\n") + 1
        ];
    }
}

