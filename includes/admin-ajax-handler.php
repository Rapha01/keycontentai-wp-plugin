<?php
/**
 * Admin AJAX Handler
 * 
 * Handles all AJAX requests from the admin interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SparkWP_Admin_Ajax_Handler {
    
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_sparkwp_generate_content', array($this, 'generate_content'));
        add_action('wp_ajax_sparkwp_load_keyword', array($this, 'load_keyword'));
        add_action('wp_ajax_sparkwp_save_post_meta', array($this, 'save_post_meta'));
        add_action('wp_ajax_sparkwp_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_sparkwp_reset_settings', array($this, 'reset_settings'));
    }
    
    /**
     * AJAX handler to generate content for a post
     */
    public function generate_content() {
        // Security check
        check_ajax_referer('sparkwp_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'sparkwp')
            ));
        }
        
        // Get post ID from request
        if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
            wp_send_json_error(array(
                'message' => __('No post ID provided', 'sparkwp')
            ));
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Generate content using the content generator class
        $generator = new SparkWP_Content_Generator();
        $result = $generator->generate_content($post_id);
        
        // Return response
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Content generated successfully', 'sparkwp'),
                'debug_log' => $result['debug_log']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message'],
                'debug_log' => $result['debug_log']
            ));
        }
    }
    
    /**
     * AJAX handler to load a keyword and create a post
     */
    public function load_keyword() {
        // Security check
        check_ajax_referer('sparkwp_nonce', 'nonce');
        
        // Get keyword from request
        if (!isset($_POST['keyword']) || empty($_POST['keyword'])) {
            wp_send_json_error(array(
                'message' => __('No keyword provided', 'sparkwp')
            ));
        }
        
        $keyword = sanitize_text_field($_POST['keyword']);
        $debug_mode = isset($_POST['debug']) && $_POST['debug'] === '1';
        $auto_publish = isset($_POST['auto_publish']) && $_POST['auto_publish'] === '1';
        $additional_context = isset($_POST['additional_context']) ? sanitize_textarea_field($_POST['additional_context']) : '';
        
        // Load keyword using the keyword loader class
        $loader = new SparkWP_Keyword_Loader();
        $result = $loader->load_keyword($keyword, $debug_mode, $auto_publish, $additional_context);
        
        // Return response
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error(array(
                'message' => $result['message'],
                'debug' => isset($result['debug']) ? $result['debug'] : array()
            ));
        }
    }
    
    /**
     * AJAX handler to save post meta (keyword and additional context)
     */
    public function save_post_meta() {
        // Security check
        check_ajax_referer('sparkwp_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'sparkwp'));
        }
        
        // Get post ID
        if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
            wp_send_json_error(__('No post ID provided', 'sparkwp'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Verify post exists
        if (!get_post($post_id)) {
            wp_send_json_error(__('Post not found', 'sparkwp'));
        }
        
        // Get and sanitize data
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $additional_context = isset($_POST['additional_context']) ? sanitize_textarea_field($_POST['additional_context']) : '';
        
        // Update post meta
        update_post_meta($post_id, 'sparkwp_keyword', $keyword);
        update_post_meta($post_id, 'sparkwp_additional_context', $additional_context);
        
        wp_send_json_success(array(
            'message' => __('Post meta updated successfully', 'sparkwp')
        ));
    }
    
    /**
     * AJAX handler to save settings from any tab
     */
    public function save_settings() {
        // Security check
        check_ajax_referer('sparkwp_settings_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'sparkwp')
            ));
        }
        
        // Get the tab being saved
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : '';
        
        if (empty($tab)) {
            wp_send_json_error(array(
                'message' => __('No tab specified', 'sparkwp')
            ));
        }
        
        $updated = 0;
        
        switch ($tab) {
            case 'api-settings':
                // API Key
                if (isset($_POST['sparkwp_openai_api_key'])) {
                    update_option('sparkwp_openai_api_key', sanitize_text_field($_POST['sparkwp_openai_api_key']));
                    $updated++;
                }
                // Text Model
                if (isset($_POST['sparkwp_text_model'])) {
                    update_option('sparkwp_text_model', sanitize_text_field($_POST['sparkwp_text_model']));
                    $updated++;
                }
                // Image Model
                if (isset($_POST['sparkwp_image_model'])) {
                    update_option('sparkwp_image_model', sanitize_text_field($_POST['sparkwp_image_model']));
                    $updated++;
                }
                break;
                
            case 'general-context':
                // Simple text fields
                $text_fields = array('sparkwp_addressing', 'sparkwp_company_name', 'sparkwp_industry', 'sparkwp_target_group');
                foreach ($text_fields as $field) {
                    if (isset($_POST[$field])) {
                        update_option($field, sanitize_text_field($_POST[$field]));
                        $updated++;
                    }
                }
                
                // Textarea fields
                $textarea_fields = array('sparkwp_usp', 'sparkwp_advantages', 'sparkwp_buying_reasons', 'sparkwp_additional_context');
                foreach ($textarea_fields as $field) {
                    if (isset($_POST[$field])) {
                        update_option($field, sanitize_textarea_field($_POST[$field]));
                        $updated++;
                    }
                }
                
                // WYSIWYG formatting (checkbox array)
                $wysiwyg_input = isset($_POST['sparkwp_wysiwyg_formatting']) ? $_POST['sparkwp_wysiwyg_formatting'] : array();
                update_option('sparkwp_wysiwyg_formatting', SparkWP_Sanitizer::wysiwyg_formatting($wysiwyg_input));
                $updated++;
                break;
                
            case 'cpt':
                // Selected post type
                if (isset($_POST['sparkwp_selected_post_type'])) {
                    update_option('sparkwp_selected_post_type', sanitize_text_field($_POST['sparkwp_selected_post_type']));
                    $updated++;
                }
                
                // CPT configs (complex nested array → JSON)
                if (isset($_POST['sparkwp_cpt_configs'])) {
                    $sanitized = SparkWP_Sanitizer::cpt_configs($_POST['sparkwp_cpt_configs']);
                    update_option('sparkwp_cpt_configs', $sanitized);
                    $updated++;
                }
                break;
                
            default:
                wp_send_json_error(array(
                    'message' => __('Unknown settings tab', 'sparkwp')
                ));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Settings saved successfully.', 'sparkwp'),
                $updated
            )
        ));
    }
    
    /**
     * AJAX handler to reset plugin settings or post meta
     */
    public function reset_settings() {
        // Security check
        check_ajax_referer('sparkwp_settings_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'sparkwp')
            ));
        }
        
        $target = isset($_POST['target']) ? sanitize_text_field($_POST['target']) : '';
        
        global $wpdb;
        
        switch ($target) {
            case 'settings':
                $deleted = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                        'sparkwp_%'
                    )
                );
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Removed %d options.', 'sparkwp'),
                        $deleted
                    )
                ));
                break;
                
            case 'meta':
                $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : '';
                
                if (empty($post_type)) {
                    wp_send_json_error(array(
                        'message' => __('No post type specified.', 'sparkwp')
                    ));
                }
                
                $post_type_object = get_post_type_object($post_type);
                $post_type_label = $post_type_object ? $post_type_object->labels->name : $post_type;
                
                $deleted = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE pm FROM {$wpdb->postmeta} pm
                         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                         WHERE pm.meta_key LIKE %s AND p.post_type = %s",
                        'sparkwp_%',
                        $post_type
                    )
                );
                wp_send_json_success(array(
                    'message' => sprintf(
                        __('Removed %d post meta entries from "%s".', 'sparkwp'),
                        $deleted,
                        $post_type_label
                    )
                ));
                break;
                
            default:
                wp_send_json_error(array(
                    'message' => __('Invalid reset target.', 'sparkwp')
                ));
        }
    }
}
