<?php
/**
 * Cost tracking and budget enforcement
 * Manages API usage tracking and daily budget limits
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Cost_Tracker {
    
    private $database;
    
    public function __construct() {
        $this->database = new AutoBlogger_Database();
    }
    
    /**
     * Estimate cost for a given prompt
     *
     * @param string $prompt Prompt text
     * @param int $max_output_tokens Expected output tokens
     * @return array Cost estimate with breakdown
     */
    public function estimate_cost($prompt, $max_output_tokens = 4000) {
        $input_tokens = $this->estimate_tokens($prompt);
        $output_tokens = $max_output_tokens;
        
        $input_cost = ($input_tokens / 1000000) * AutoBlogger_Config::get('cost_input_per_million');
        $output_cost = ($output_tokens / 1000000) * AutoBlogger_Config::get('cost_output_per_million');
        $total_cost = $input_cost + $output_cost;
        
        return [
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
            'total_tokens' => $input_tokens + $output_tokens,
            'input_cost' => round($input_cost, 6),
            'output_cost' => round($output_cost, 6),
            'total_cost' => round($total_cost, 6)
        ];
    }
    
    /**
     * Estimate tokens from text
     * Uses character-to-token ratio (1 token ≈ 3 chars for Vietnamese)
     *
     * @param string $text Text to estimate
     * @return int Estimated tokens
     */
    public function estimate_tokens($text) {
        $char_count = mb_strlen($text, 'UTF-8');
        $ratio = AutoBlogger_Config::get('cost_token_char_ratio');
        return (int) ceil($char_count / $ratio);
    }
    
    /**
     * Log actual usage
     *
     * @param int $user_id User ID
     * @param string $operation Operation type
     * @param int $tokens_input Actual input tokens
     * @param int $tokens_output Actual output tokens
     * @return bool True on success
     */
    public function log_usage($user_id, $operation, $tokens_input, $tokens_output) {
        $input_cost = ($tokens_input / 1000000) * AutoBlogger_Config::get('cost_input_per_million');
        $output_cost = ($tokens_output / 1000000) * AutoBlogger_Config::get('cost_output_per_million');
        $total_cost = $input_cost + $output_cost;
        
        $result = $this->database->log_usage(
            $user_id,
            $operation,
            $tokens_input,
            $tokens_output,
            $total_cost
        );
        
        if ($result) {
            AutoBlogger_Logger::info('Usage logged', [
                'user_id' => $user_id,
                'operation' => $operation,
                'tokens' => $tokens_input + $tokens_output,
                'cost' => $total_cost
            ]);
        }
        
        return $result !== false;
    }
    
    /**
     * Get daily usage for user
     *
     * @param int $user_id User ID
     * @param string $date Date (Y-m-d format)
     * @return float Total cost
     */
    public function get_daily_usage($user_id, $date = null) {
        return $this->database->get_daily_usage($user_id, $date);
    }
    
    /**
     * Check if user is within daily budget
     *
     * @param int $user_id User ID
     * @param float $estimated_cost Estimated cost for next operation
     * @return bool True if within budget
     * @throws AutoBlogger_Budget_Exception If budget exceeded
     */
    public function check_daily_budget($user_id, $estimated_cost = 0) {
        $daily_limit = get_option('autoblogger_daily_budget', AutoBlogger_Config::get('cost_default_daily_budget'));
        $today_usage = $this->get_daily_usage($user_id);
        $projected_usage = $today_usage + $estimated_cost;
        
        if ($projected_usage >= $daily_limit) {
            // Fire event when budget exceeded
            do_action('autoblogger_budget_exceeded', $user_id, $today_usage, $daily_limit);
            
            AutoBlogger_Logger::warning('Budget exceeded', [
                'user_id' => $user_id,
                'current_usage' => $today_usage,
                'limit' => $daily_limit,
                'estimated_cost' => $estimated_cost
            ]);
            
            throw new AutoBlogger_Budget_Exception(
                sprintf(
                    'Daily budget limit of $%.2f reached (current: $%.2f, estimated: $%.2f)',
                    $daily_limit,
                    $today_usage,
                    $estimated_cost
                ),
                'budget_exceeded',
                [
                    'current_usage' => $today_usage,
                    'limit' => $daily_limit,
                    'estimated_cost' => $estimated_cost
                ]
            );
        }
        
        return true;
    }
    
    /**
     * Get usage statistics
     *
     * @param int $user_id User ID
     * @param int $days Number of days
     * @return array Usage stats
     */
    public function get_usage_stats($user_id, $days = 30) {
        return $this->database->get_usage_stats($user_id, $days);
    }
    
    /**
     * Get budget status for user
     *
     * @param int $user_id User ID
     * @return array Budget status
     */
    public function get_budget_status($user_id) {
        $daily_limit = get_option('autoblogger_daily_budget', AutoBlogger_Config::get('cost_default_daily_budget'));
        $today_usage = $this->get_daily_usage($user_id);
        $remaining = max(0, $daily_limit - $today_usage);
        $percentage_used = $daily_limit > 0 ? ($today_usage / $daily_limit) * 100 : 0;
        
        return [
            'daily_limit' => $daily_limit,
            'today_usage' => round($today_usage, 2),
            'remaining' => round($remaining, 2),
            'percentage_used' => round($percentage_used, 1),
            'is_exceeded' => $today_usage >= $daily_limit
        ];
    }
    
    /**
     * Get monthly summary
     *
     * @param int $user_id User ID
     * @param string $month Month (Y-m format, defaults to current)
     * @return array Monthly summary
     */
    public function get_monthly_summary($user_id, $month = null) {
        global $wpdb;
        
        if ($month === null) {
            $month = current_time('Y-m');
        }
        
        $usage_table = $wpdb->prefix . 'autoblogger_usage';
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_operations,
                    SUM(tokens_input) as total_input_tokens,
                    SUM(tokens_output) as total_output_tokens,
                    SUM(cost) as total_cost,
                    AVG(cost) as avg_cost_per_operation
                 FROM {$usage_table}
                 WHERE user_id = %d
                 AND DATE_FORMAT(created_at, '%%Y-%%m') = %s",
                $user_id,
                $month
            ),
            ARRAY_A
        );
        
        return [
            'month' => $month,
            'total_operations' => (int) $result['total_operations'],
            'total_input_tokens' => (int) $result['total_input_tokens'],
            'total_output_tokens' => (int) $result['total_output_tokens'],
            'total_tokens' => (int) $result['total_input_tokens'] + (int) $result['total_output_tokens'],
            'total_cost' => round((float) $result['total_cost'], 2),
            'avg_cost_per_operation' => round((float) $result['avg_cost_per_operation'], 4)
        ];
    }
}

