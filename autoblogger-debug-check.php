<?php
/**
 * AutoBlogger Debug Check
 * Drop this file in your WordPress root and visit it to check REST API status
 * 
 * Usage: http://yoursite.com/autoblogger-debug-check.php
 */

// Load WordPress
require_once('wp-load.php');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>AutoBlogger Debug Check</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
        h1 { color: #2271b1; }
        .section { background: #f0f0f1; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .success { background: #d7ffd9; }
        .error { background: #ffd7d7; }
        .warning { background: #fff4d7; }
        pre { background: #23282d; color: #f0f0f1; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .status { font-weight: bold; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .status.ok { background: #46b450; color: white; }
        .status.fail { background: #dc3232; color: white; }
    </style>
</head>
<body>
    <h1>🔍 AutoBlogger Debug Check</h1>
    
    <div class="section">
        <h2>1. Plugin Status</h2>
        <?php
        // Method 1: Check using is_plugin_active (requires admin functions)
        if (!function_exists('is_plugin_active')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_file = 'autoblogger/autoblogger.php';
        $is_active = is_plugin_active($plugin_file);
        
        // Method 2: Check if AutoBlogger constant is defined (more reliable)
        $constant_defined = defined('AUTOBLOGGER_VERSION');
        
        // Method 3: Check active plugins option directly
        $active_plugins = get_option('active_plugins', []);
        $in_active_list = in_array($plugin_file, $active_plugins);
        ?>
        <p>Plugin Active (Method 1 - is_plugin_active): <span class="status <?php echo $is_active ? 'ok' : 'fail'; ?>"><?php echo $is_active ? 'YES' : 'NO'; ?></span></p>
        <p>Plugin Active (Method 2 - AUTOBLOGGER_VERSION defined): <span class="status <?php echo $constant_defined ? 'ok' : 'fail'; ?>"><?php echo $constant_defined ? 'YES' : 'NO'; ?></span></p>
        <?php if ($constant_defined): ?>
            <p>Plugin Version: <strong><?php echo AUTOBLOGGER_VERSION; ?></strong></p>
        <?php endif; ?>
        <p>Plugin Active (Method 3 - in active_plugins option): <span class="status <?php echo $in_active_list ? 'ok' : 'fail'; ?>"><?php echo $in_active_list ? 'YES' : 'NO'; ?></span></p>
        
        <?php if (!$constant_defined): ?>
            <p class="error">⚠️ Plugin is not loaded! The autoblogger_init() function didn't run.</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>2. Class Loading</h2>
        <?php
        $classes = [
            'AutoBlogger_Hooks',
            'AutoBlogger_REST_API',
            'AutoBlogger_Settings',
            'AutoBlogger_Database',
            'AutoBlogger_AI_Service',
        ];
        
        foreach ($classes as $class) {
            $exists = class_exists($class);
            echo '<p>' . $class . ': <span class="status ' . ($exists ? 'ok' : 'fail') . '">' . ($exists ? 'LOADED' : 'NOT FOUND') . '</span></p>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. REST API Routes</h2>
        <?php
        $rest_server = rest_get_server();
        $namespaces = $rest_server->get_namespaces();
        $has_autoblogger = in_array('autoblogger/v1', $namespaces);
        ?>
        <p>Namespace registered: <span class="status <?php echo $has_autoblogger ? 'ok' : 'fail'; ?>"><?php echo $has_autoblogger ? 'YES' : 'NO'; ?></span></p>
        
        <p><strong>All Registered Namespaces:</strong></p>
        <pre><?php echo implode("\n", $namespaces); ?></pre>
        
        <?php if ($has_autoblogger): ?>
            <p><strong>Registered AutoBlogger Routes:</strong></p>
            <pre><?php
                $routes = $rest_server->get_routes();
                $autoblogger_routes = array_filter(array_keys($routes), function($route) {
                    return strpos($route, '/autoblogger/v1') === 0;
                });
                echo implode("\n", $autoblogger_routes);
            ?></pre>
        <?php else: ?>
            <p class="error">⚠️ AutoBlogger REST API routes are NOT registered!</p>
            
            <p><strong>Checking why routes aren't registered:</strong></p>
            <?php
            // Check if hooks were registered
            global $wp_filter;
            $rest_api_init_hooks = isset($wp_filter['rest_api_init']) ? count($wp_filter['rest_api_init']->callbacks) : 0;
            echo '<p>Hooks registered for rest_api_init: <strong>' . $rest_api_init_hooks . '</strong></p>';
            
            // Try to manually trigger registration to see if it fails
            if (class_exists('AutoBlogger_Hooks')) {
                echo '<p>AutoBlogger_Hooks class: <span class="status ok">EXISTS</span></p>';
                
                try {
                    $hooks = new AutoBlogger_Hooks();
                    echo '<p>AutoBlogger_Hooks instantiation: <span class="status ok">SUCCESS</span></p>';
                    
                    if (method_exists($hooks, 'register_rest_routes')) {
                        echo '<p>register_rest_routes method: <span class="status ok">EXISTS</span></p>';
                        
                        // Try to call it
                        ob_start();
                        $hooks->register_rest_routes();
                        $output = ob_get_clean();
                        
                        echo '<p>register_rest_routes() called: <span class="status ok">SUCCESS</span></p>';
                        
                        if ($output) {
                            echo '<p><strong>Output from register_rest_routes():</strong></p>';
                            echo '<pre>' . esc_html($output) . '</pre>';
                        }
                        
                        // Check again if routes are now registered
                        $rest_server = rest_get_server();
                        $namespaces = $rest_server->get_namespaces();
                        $has_autoblogger_now = in_array('autoblogger/v1', $namespaces);
                        
                        if ($has_autoblogger_now) {
                            echo '<p class="success">✅ Routes registered successfully after manual call!</p>';
                        } else {
                            echo '<p class="error">❌ Routes still not registered even after manual call!</p>';
                        }
                    } else {
                        echo '<p>register_rest_routes method: <span class="status fail">NOT FOUND</span></p>';
                    }
                } catch (Exception $e) {
                    echo '<p class="error">Error: ' . esc_html($e->getMessage()) . '</p>';
                    echo '<pre>' . esc_html($e->getTraceAsString()) . '</pre>';
                }
            } else {
                echo '<p>AutoBlogger_Hooks class: <span class="status fail">NOT FOUND</span></p>';
            }
            ?>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>4. Test REST API Request</h2>
        <?php
        $test_url = rest_url('autoblogger/v1/settings');
        echo "<p><strong>Testing:</strong> <code>$test_url</code></p>";
        
        // Make internal request
        $request = new WP_REST_Request('GET', '/autoblogger/v1/settings');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        
        $response = rest_do_request($request);
        $data = $response->get_data();
        $status = $response->get_status();
        
        echo '<p>Status Code: <span class="status ' . ($status === 200 ? 'ok' : 'fail') . '">' . $status . '</span></p>';
        
        if ($status === 200) {
            echo '<p class="success">✅ REST API is working!</p>';
            echo '<pre>' . print_r($data, true) . '</pre>';
        } else {
            echo '<p class="error">❌ REST API failed!</p>';
            echo '<pre>' . print_r($data, true) . '</pre>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>5. Debug Log</h2>
        <?php
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<p>WP_DEBUG: <span class="status ok">ENABLED</span></p>';
            
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                echo '<p>WP_DEBUG_LOG: <span class="status ok">ENABLED</span></p>';
                
                $log_file = WP_CONTENT_DIR . '/debug.log';
                if (file_exists($log_file)) {
                    echo '<p>Log file: <code>' . $log_file . '</code></p>';
                    
                    // Get last 50 lines of AutoBlogger-related logs
                    $logs = file($log_file);
                    $autoblogger_logs = array_filter($logs, function($line) {
                        return stripos($line, 'autoblogger') !== false;
                    });
                    
                    if (!empty($autoblogger_logs)) {
                        $recent_logs = array_slice($autoblogger_logs, -50);
                        echo '<p><strong>Recent AutoBlogger Logs:</strong></p>';
                        echo '<pre>' . implode('', $recent_logs) . '</pre>';
                    } else {
                        echo '<p class="warning">No AutoBlogger logs found.</p>';
                    }
                } else {
                    echo '<p class="warning">Debug log file not found at: ' . $log_file . '</p>';
                }
            } else {
                echo '<p>WP_DEBUG_LOG: <span class="status fail">DISABLED</span></p>';
                echo '<p class="warning">Enable WP_DEBUG_LOG in wp-config.php to see errors</p>';
            }
        } else {
            echo '<p>WP_DEBUG: <span class="status fail">DISABLED</span></p>';
            echo '<p class="warning">Enable WP_DEBUG in wp-config.php to see errors</p>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>6. Recommendations</h2>
        <?php if (!$has_autoblogger): ?>
            <p>❌ <strong>REST API routes are not registered.</strong> Check the debug log above for errors.</p>
            <p>Possible causes:</p>
            <ul>
                <li>Plugin initialization failed</li>
                <li>Class autoloader not working</li>
                <li>PHP fatal error during instantiation</li>
                <li>Missing dependencies</li>
            </ul>
        <?php else: ?>
            <p>✅ REST API routes are registered correctly!</p>
        <?php endif; ?>
    </div>
    
    <hr>
    <p><small>After checking, <strong>delete this file</strong> for security. It contains sensitive information.</small></p>
</body>
</html>

