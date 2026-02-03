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
}
