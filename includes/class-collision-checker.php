<?php
/**
 * Class Collision Checker
 * Detects and prevents class name conflicts with other plugins
 *
 * @package AutoBlogger
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoBlogger_Collision_Checker {
    
    /**
     * Check for class name collisions
     * Run this on plugin activation
     *
     * @return array Array of conflicts found
     */
    public static function check_collisions() {
        $conflicts = [];
        
        // List of our classes
        $our_classes = [
            'AutoBlogger_Database',
            'AutoBlogger_Settings',
            'AutoBlogger_AI_Service',
            'AutoBlogger_Config',
            'AutoBlogger_Hooks',
            'AutoBlogger_Logger',
            'AutoBlogger_Error_Handler',
            'AutoBlogger_Activator',
            'AutoBlogger_Admin',
            'AutoBlogger_Gutenberg',
            'AutoBlogger_REST_API',
            'AutoBlogger_Cost_Tracker',
            'AutoBlogger_RAG_Engine',
            'AutoBlogger_Content_Filter',
            'AutoBlogger_Post_Interceptor',
            'AutoBlogger_Prompt_Manager',
            'AutoBlogger_Claude_Provider',
            'AutoBlogger_Gemini_Provider'
        ];
        
        foreach ($our_classes as $class_name) {
            if (class_exists($class_name)) {
                // Class exists - check if it's ours or another plugin's
                try {
                    $reflection = new ReflectionClass($class_name);
                    $file = $reflection->getFileName();
                    
                    // Check if file is in our plugin directory (case-insensitive)
                    $normalized_file = str_replace('\\', '/', strtolower($file));
                    $normalized_plugin_path = str_replace('\\', '/', strtolower(AUTOBLOGGER_PATH));
                    
                    if (strpos($normalized_file, $normalized_plugin_path) === false) {
                        $conflicts[] = [
                            'class' => $class_name,
                            'file' => $file,
                            'message' => "Class {$class_name} already exists in another plugin"
                        ];
                    }
                } catch (ReflectionException $e) {
                    // Could not reflect - assume it's a conflict
                    $conflicts[] = [
                        'class' => $class_name,
                        'file' => 'unknown',
                        'message' => "Class {$class_name} exists but cannot be inspected"
                    ];
                }
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Check for function name collisions
     *
     * @return array Array of conflicts found
     */
    public static function check_function_collisions() {
        $conflicts = [];
        
        // List of our functions (if any)
        $our_functions = [
            'autoblogger_activate',
            'autoblogger_deactivate'
        ];
        
        foreach ($our_functions as $function_name) {
            if (function_exists($function_name)) {
                $reflection = new ReflectionFunction($function_name);
                $file = $reflection->getFileName();
                
                if (strpos($file, 'autoblogger') === false) {
                    $conflicts[] = [
                        'function' => $function_name,
                        'file' => $file,
                        'message' => "Function {$function_name} already exists in another plugin"
                    ];
                }
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Display collision warning in admin
     *
     * @param array $conflicts Array of conflicts
     */
    public static function display_collision_warning($conflicts) {
        if (empty($conflicts)) {
            return;
        }
        
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e('AutoBlogger: Class Name Conflict Detected!', 'autoblogger'); ?></strong>
            </p>
            <p>
                <?php esc_html_e('AutoBlogger cannot run because another plugin is using the same class names:', 'autoblogger'); ?>
            </p>
            <ul>
                <?php foreach ($conflicts as $conflict): ?>
                    <li>
                        <code><?php echo esc_html($conflict['class']); ?></code>
                        <?php if (!empty($conflict['file'])): ?>
                            <br><small><?php echo esc_html($conflict['file']); ?></small>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p>
                <?php esc_html_e('Please deactivate the conflicting plugin or contact support.', 'autoblogger'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Log conflicts for debugging
     *
     * @param array $conflicts Array of conflicts
     */
    public static function log_conflicts($conflicts) {
        if (empty($conflicts)) {
            return;
        }
        
        AutoBlogger_Logger::error('Class name conflicts detected', [
            'conflicts' => $conflicts,
            'total' => count($conflicts)
        ]);
    }
}

