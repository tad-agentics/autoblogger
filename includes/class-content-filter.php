<?php
/**
 * Content Safety Filter
 * Filters dangerous keywords and validates E-E-A-T compliance
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Content_Filter {
    
    private $settings;
    
    public function __construct() {
        $this->settings = new AutoBlogger_Settings();
    }
    
    /**
     * Filter content for safety issues
     *
     * @param string $content Content to filter
     * @param int $post_id Post ID
     * @return array Result with filtered content and issues
     */
    public function filter_content($content, $post_id = 0) {
        $issues = [];
        $filtered_content = $content;
        
        // Check for negative keywords
        $negative_issues = $this->check_negative_keywords($content);
        if (!empty($negative_issues)) {
            $issues = array_merge($issues, $negative_issues);
            
            // Remove or replace negative keywords
            $filtered_content = $this->remove_negative_keywords($content);
        }
        
        // Check for medical/financial advice without disclaimer
        $advice_issues = $this->check_sensitive_advice($content);
        if (!empty($advice_issues)) {
            $issues = array_merge($issues, $advice_issues);
        }
        
        // Fire event if issues detected
        if (!empty($issues)) {
            do_action('autoblogger_safety_issues_detected', $content, $issues, $post_id);
            
            AutoBlogger_Logger::warning('Safety issues detected', [
                'post_id' => $post_id,
                'issues' => $issues
            ]);
            
            // Notify admin for critical issues
            if ($this->has_critical_issues($issues)) {
                $this->notify_admin($post_id, $issues);
            }
        }
        
        return [
            'content' => $filtered_content,
            'issues' => $issues,
            'is_safe' => empty($issues)
        ];
    }
    
    /**
     * Check for negative keywords
     *
     * @param string $content Content to check
     * @return array Issues found
     */
    private function check_negative_keywords($content) {
        $issues = [];
        $negative_keywords = $this->settings->get_negative_keywords();
        
        foreach ($negative_keywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $issues[] = [
                    'type' => 'negative_keyword',
                    'severity' => 'critical',
                    'keyword' => $keyword,
                    'message' => sprintf('Dangerous phrase detected: "%s"', $keyword)
                ];
            }
        }
        
        return $issues;
    }
    
    /**
     * Remove negative keywords from content
     *
     * @param string $content Content
     * @return string Filtered content
     */
    private function remove_negative_keywords($content) {
        $negative_keywords = $this->settings->get_negative_keywords();
        
        foreach ($negative_keywords as $keyword) {
            // Replace with safe alternative
            $content = str_ireplace($keyword, '[REMOVED FOR SAFETY]', $content);
        }
        
        return $content;
    }
    
    /**
     * Check for sensitive medical/financial advice
     *
     * @param string $content Content to check
     * @return array Issues found
     */
    private function check_sensitive_advice($content) {
        $issues = [];
        
        $sensitive_patterns = [
            '/(?:chữa|điều trị|khỏi).{0,30}(?:ung thư|bệnh nan y)/ui' => 'Medical advice without qualification',
            '/(?:đầu tư|mua).{0,30}(?:chắc chắn|100%|không rủi ro)/ui' => 'Financial advice with absolute claims',
            '/(?:bỏ|ngừng|không cần).{0,30}(?:thuốc|điều trị|bác sĩ)/ui' => 'Dangerous medical advice'
        ];
        
        foreach ($sensitive_patterns as $pattern => $description) {
            if (preg_match($pattern, $content)) {
                $issues[] = [
                    'type' => 'sensitive_advice',
                    'severity' => 'high',
                    'pattern' => $pattern,
                    'message' => $description
                ];
            }
        }
        
        return $issues;
    }
    
    /**
     * Check if issues contain critical severity
     *
     * @param array $issues Issues array
     * @return bool True if critical
     */
    private function has_critical_issues($issues) {
        foreach ($issues as $issue) {
            if ($issue['severity'] === 'critical') {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Notify admin of safety issues
     *
     * @param int $post_id Post ID
     * @param array $issues Issues
     */
    private function notify_admin($post_id, $issues) {
        $admin_email = get_option('admin_email');
        
        if (empty($admin_email)) {
            return;
        }
        
        $post_title = get_the_title($post_id);
        $edit_link = get_edit_post_link($post_id);
        
        $subject = 'AutoBlogger Safety Alert: Critical Issues Detected';
        
        $body = "Critical safety issues detected in post:\n\n";
        $body .= "Post: {$post_title}\n";
        $body .= "Edit: {$edit_link}\n\n";
        $body .= "Issues:\n";
        
        foreach ($issues as $issue) {
            $body .= "- [{$issue['severity']}] {$issue['message']}\n";
        }
        
        $body .= "\nPlease review and edit the content before publishing.";
        
        wp_mail($admin_email, $subject, $body);
    }
    
    /**
     * Validate E-E-A-T compliance
     *
     * @param string $content Content to validate
     * @return array Validation result
     */
    public function validate_eeat($content) {
        $checks = [
            'has_citations' => $this->has_citations($content),
            'has_personal_experience' => $this->has_personal_markers($content),
            'has_expert_attribution' => $this->has_expert_attribution($content),
            'has_disclaimer' => $this->has_disclaimer($content)
        ];
        
        $passed = array_filter($checks);
        $score = (count($passed) / count($checks)) * 100;
        
        return [
            'score' => round($score),
            'checks' => $checks,
            'recommendations' => $this->get_eeat_recommendations($checks)
        ];
    }
    
    /**
     * Check if content has citations
     *
     * @param string $content Content
     * @return bool
     */
    private function has_citations($content) {
        // Look for citation patterns: (According to...), (Source:...), etc.
        return preg_match('/\((?:According to|Theo|Source:|Nguồn:).+?\)/ui', $content) > 0;
    }
    
    /**
     * Check if content has personal experience markers
     *
     * @param string $content Content
     * @return bool
     */
    private function has_personal_markers($content) {
        // Look for first-person narrative
        $markers = ['tôi', 'mình', 'I observed', 'trong trường hợp'];
        
        foreach ($markers as $marker) {
            if (stripos($content, $marker) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if content has expert attribution
     *
     * @param string $content Content
     * @return bool
     */
    private function has_expert_attribution($content) {
        // Look for expert note blocks or attributions
        return strpos($content, 'wp:autoblogger/expert-note') !== false ||
               preg_match('/(?:chuyên gia|expert|theo ý kiến)/ui', $content) > 0;
    }
    
    /**
     * Check if content has disclaimer
     *
     * @param string $content Content
     * @return bool
     */
    private function has_disclaimer($content) {
        return strpos($content, 'wp:autoblogger/disclaimer') !== false ||
               stripos($content, 'tham khảo') !== false;
    }
    
    /**
     * Get E-E-A-T recommendations
     *
     * @param array $checks Checks result
     * @return array Recommendations
     */
    private function get_eeat_recommendations($checks) {
        $recommendations = [];
        
        if (!$checks['has_citations']) {
            $recommendations[] = 'Add source citations to support factual claims';
        }
        
        if (!$checks['has_personal_experience']) {
            $recommendations[] = 'Include personal observations or real-world examples';
        }
        
        if (!$checks['has_expert_attribution']) {
            $recommendations[] = 'Add expert notes or professional attributions';
        }
        
        if (!$checks['has_disclaimer']) {
            $recommendations[] = 'Add disclaimer block for sensitive topics';
        }
        
        return $recommendations;
    }
}

