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

class SparkPlus_Admin_Ajax_Handler {
    
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_sparkplus_get_generation_meta',     array($this, 'get_generation_meta'));
        add_action('wp_ajax_sparkplus_generate_text',           array($this, 'generate_text'));
        add_action('wp_ajax_sparkplus_generate_image',          array($this, 'generate_image'));
        add_action('wp_ajax_sparkplus_stamp_generation',        array($this, 'stamp_generation'));
        add_action('wp_ajax_sparkplus_clear_fields',            array($this, 'clear_fields'));
        add_action('wp_ajax_sparkplus_load_keyword', array($this, 'load_keyword'));
        add_action('wp_ajax_sparkplus_save_post_meta', array($this, 'save_post_meta'));
        add_action('wp_ajax_sparkplus_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_sparkplus_reset_settings', array($this, 'reset_settings'));
        add_action('wp_ajax_sparkplus_delete_post', array($this, 'delete_post'));
        add_action('wp_ajax_sparkplus_get_post_type_items', array($this, 'get_post_type_items'));
        add_action('wp_ajax_sparkplus_save_linking_pool', array($this, 'save_linking_pool'));
    }
    
    /**
     * AJAX handler: return field metadata for a post so the client
     * can plan its generation calls without the server dictating workflow.
     */
    public function get_generation_meta() {
        check_ajax_referer( 'sparkplus_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'sparkplus' ) ) );
        }
        if ( empty( $_POST['post_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No post ID provided', 'sparkplus' ) ) );
        }

        $post_id   = absint( wp_unslash( $_POST['post_id'] ) );
        $generator = new SparkPlus_Content_Generator();
        $result    = $generator->get_generation_meta( $post_id );

        if ( $result['success'] ) {
            wp_send_json_success( array(
                'text_fields'      => $result['text_fields'],
                'image_fields'     => $result['image_fields'],
                'has_clear_fields' => $result['has_clear_fields'],
                'debug_log'        => $result['debug_log'],
            ) );
        } else {
            wp_send_json_error( array(
                'message'   => $result['message'],
                'debug_log' => $result['debug_log'],
            ) );
        }
    }

    /**
     * AJAX handler: generate only text fields for a post.
     */
    public function generate_text() {
        check_ajax_referer( 'sparkplus_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'sparkplus' ) ) );
        }
        if ( empty( $_POST['post_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No post ID provided', 'sparkplus' ) ) );
        }

        @set_time_limit( 300 );
        ignore_user_abort( true );

        $post_id   = absint( wp_unslash( $_POST['post_id'] ) );
        $generator = new SparkPlus_Content_Generator();
        $result    = $generator->generate_text_only( $post_id );

        if ( $result['success'] ) {
            wp_send_json_success( array(
                'debug_log' => $result['debug_log'],
            ) );
        } else {
            wp_send_json_error( array(
                'message'   => $result['message'],
                'debug_log' => $result['debug_log'],
            ) );
        }
    }

    /**
     * AJAX handler: generate one image field for a post.
     * Expects post_id, field_index (0-based).
     */
    public function generate_image() {
        check_ajax_referer( 'sparkplus_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'sparkplus' ) ) );
        }
        if ( empty( $_POST['post_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No post ID provided', 'sparkplus' ) ) );
        }

        @set_time_limit( 300 );
        ignore_user_abort( true );

        $post_id      = absint( wp_unslash( $_POST['post_id'] ) );
        $field_index  = isset( $_POST['field_index'] ) ? absint( wp_unslash( $_POST['field_index'] ) ) : 0;

        $generator = new SparkPlus_Content_Generator();
        $result    = $generator->generate_single_image( $post_id, $field_index );

        if ( $result['success'] ) {
            wp_send_json_success( array(
                'debug_log' => $result['debug_log'],
            ) );
        } else {
            wp_send_json_error( array(
                'message'   => $result['message'],
                'debug_log' => $result['debug_log'],
            ) );
        }
    }

    /**
     * AJAX handler: stamp the last-generation timestamp on a post.
     */
    public function stamp_generation() {
        check_ajax_referer( 'sparkplus_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'sparkplus' ) ) );
        }
        if ( empty( $_POST['post_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No post ID provided', 'sparkplus' ) ) );
        }

        $post_id = absint( wp_unslash( $_POST['post_id'] ) );
        update_post_meta( $post_id, 'sparkplus_last_generation', current_time( 'mysql' ) );
        wp_send_json_success();
    }

    /**
     * AJAX handler: clear fields marked with clear=true for a post.
     */
    public function clear_fields() {
        check_ajax_referer( 'sparkplus_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'sparkplus' ) ) );
        }
        if ( empty( $_POST['post_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No post ID provided', 'sparkplus' ) ) );
        }

        $post_id   = absint( wp_unslash( $_POST['post_id'] ) );
        $generator = new SparkPlus_Content_Generator();
        $result    = $generator->clear_fields( $post_id );

        if ( $result['success'] ) {
            wp_send_json_success( array(
                'cleared_count' => $result['cleared_count'],
                'debug_log'     => $result['debug_log'],
            ) );
        } else {
            wp_send_json_error( array(
                'message'   => $result['message'],
                'debug_log' => $result['debug_log'],
            ) );
        }
    }

    /**
     * AJAX handler to load a keyword and create a post
     */
    public function load_keyword() {
        // Security check
        check_ajax_referer('sparkplus_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'sparkplus')
            ));
        }
        
        // Get keyword from request
        if (!isset($_POST['keyword']) || empty($_POST['keyword'])) {
            wp_send_json_error(array(
                'message' => __('No keyword provided', 'sparkplus')
            ));
        }
        
        $keyword = sanitize_text_field(wp_unslash($_POST['keyword']));
        $debug_mode = isset($_POST['debug']) && sanitize_text_field(wp_unslash($_POST['debug'])) === '1';
        $auto_publish = isset($_POST['auto_publish']) && sanitize_text_field(wp_unslash($_POST['auto_publish'])) === '1';
        $additional_context = isset($_POST['additional_context']) ? sanitize_textarea_field(wp_unslash($_POST['additional_context'])) : '';
        
        // Load keyword using the keyword loader class
        $loader = new SparkPlus_Keyword_Loader();
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
        check_ajax_referer('sparkplus_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'sparkplus'));
        }
        
        // Get post ID
        if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
            wp_send_json_error(__('No post ID provided', 'sparkplus'));
        }
        
        $post_id = absint(wp_unslash($_POST['post_id']));
        
        // Verify post exists
        if (!get_post($post_id)) {
            wp_send_json_error(__('Post not found', 'sparkplus'));
        }
        
        // Get and sanitize data
        $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
        $additional_context = isset($_POST['additional_context']) ? sanitize_textarea_field(wp_unslash($_POST['additional_context'])) : '';
        
        // Update post meta
        update_post_meta($post_id, 'sparkplus_keyword', $keyword);
        update_post_meta($post_id, 'sparkplus_additional_context', $additional_context);
        
        wp_send_json_success(array(
            'message' => __('Post meta updated successfully', 'sparkplus')
        ));
    }
    
    /**
     * AJAX handler to delete a post
     */
    public function delete_post() {
        // Security check
        check_ajax_referer('sparkplus_nonce', 'nonce');

        // Check if user has permission
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'sparkplus')
            ));
        }

        // Get post ID from request
        if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
            wp_send_json_error(array(
                'message' => __('No post ID provided', 'sparkplus')
            ));
        }

        $post_id = absint(wp_unslash($_POST['post_id']));
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error(array(
                'message' => __('Post not found', 'sparkplus')
            ));
        }

        // Check the user can delete this specific post
        if (!current_user_can('delete_post', $post_id)) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to delete this post.', 'sparkplus')
            ));
        }

        $title = $post->post_title;
        $result = wp_delete_post($post_id, true); // force delete (bypass trash)

        if ($result) {
            wp_send_json_success(array(
                /* translators: 1: post title, 2: post ID */
                'message' => sprintf(__('Post "%1$s" (ID: %2$d) deleted successfully.', 'sparkplus'), $title, $post_id)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete post.', 'sparkplus')
            ));
        }
    }

    /**
     * AJAX handler to save settings from any tab
     */
    public function save_settings() {
        // Security check
        check_ajax_referer('sparkplus_settings_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'sparkplus')
            ));
        }
        
        // Get the tab being saved
        $tab = isset($_POST['tab']) ? sanitize_text_field(wp_unslash($_POST['tab'])) : '';
        
        if (empty($tab)) {
            wp_send_json_error(array(
                'message' => __('No tab specified', 'sparkplus')
            ));
        }
        
        $updated = 0;
        
        switch ($tab) {
            case 'api-settings':
                // API Key
                if (isset($_POST['sparkplus_openai_api_key'])) {
                    update_option('sparkplus_openai_api_key', sanitize_text_field(wp_unslash($_POST['sparkplus_openai_api_key'])));
                    $updated++;
                }
                // Text Model
                if (isset($_POST['sparkplus_text_model'])) {
                    update_option('sparkplus_text_model', sanitize_text_field(wp_unslash($_POST['sparkplus_text_model'])));
                    $updated++;
                }
                // Image Model
                if (isset($_POST['sparkplus_image_model'])) {
                    update_option('sparkplus_image_model', sanitize_text_field(wp_unslash($_POST['sparkplus_image_model'])));
                    $updated++;
                }
                break;
                
            case 'general-context':
                // Simple text fields
                $text_fields = array('sparkplus_addressing', 'sparkplus_company_name', 'sparkplus_industry', 'sparkplus_target_group');
                foreach ($text_fields as $field) {
                    if (isset($_POST[$field])) {
                        update_option($field, sanitize_text_field(wp_unslash($_POST[$field])));
                        $updated++;
                    }
                }
                
                // Textarea fields
                $textarea_fields = array('sparkplus_usp', 'sparkplus_advantages', 'sparkplus_buying_reasons', 'sparkplus_additional_context_text', 'sparkplus_additional_context_image');
                foreach ($textarea_fields as $field) {
                    if (isset($_POST[$field])) {
                        update_option($field, sanitize_textarea_field(wp_unslash($_POST[$field])));
                        $updated++;
                    }
                }
                
                // WYSIWYG formatting (checkbox array)
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by SparkPlus_Sanitizer::wysiwyg_formatting()
                $wysiwyg_input = isset($_POST['sparkplus_wysiwyg_formatting']) ? wp_unslash($_POST['sparkplus_wysiwyg_formatting']) : array();
                update_option('sparkplus_wysiwyg_formatting', SparkPlus_Sanitizer::wysiwyg_formatting($wysiwyg_input));
                $updated++;
                break;
                
            case 'cpt':
                // Selected post type
                if (isset($_POST['sparkplus_selected_post_type'])) {
                    update_option('sparkplus_selected_post_type', sanitize_text_field(wp_unslash($_POST['sparkplus_selected_post_type'])));
                    $updated++;
                }
                
                // CPT configs (complex nested array → JSON)
                if (isset($_POST['sparkplus_cpt_configs'])) {
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by SparkPlus_Sanitizer::cpt_configs()
                    $sanitized = SparkPlus_Sanitizer::cpt_configs(wp_unslash($_POST['sparkplus_cpt_configs']));
                    update_option('sparkplus_cpt_configs', $sanitized);
                    $updated++;
                }
                break;
                
            default:
                wp_send_json_error(array(
                    'message' => __('Unknown settings tab', 'sparkplus')
                ));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Settings saved successfully.', 'sparkplus'),
                $updated
            )
        ));
    }
    
    /**
     * AJAX handler to reset plugin settings or post meta
     */
    public function reset_settings() {
        // Security check
        check_ajax_referer('sparkplus_settings_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'sparkplus')
            ));
        }
        
        $target = isset($_POST['target']) ? sanitize_text_field(wp_unslash($_POST['target'])) : '';
        
        global $wpdb;
        
        switch ($target) {
            case 'settings':
                $deleted = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                        'sparkplus_%'
                    )
                );
                wp_send_json_success(array(
                    'message' => sprintf(
                        /* translators: %d: number of deleted options */
                        __('Removed %d options.', 'sparkplus'),
                        $deleted
                    )
                ));
                break;
                
            case 'meta':
                $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
                
                if (empty($post_type)) {
                    wp_send_json_error(array(
                        'message' => __('No post type specified.', 'sparkplus')
                    ));
                }
                
                $post_type_object = get_post_type_object($post_type);
                $post_type_label = $post_type_object ? $post_type_object->labels->name : $post_type;
                
                $deleted = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE pm FROM {$wpdb->postmeta} pm
                         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                         WHERE pm.meta_key LIKE %s AND p.post_type = %s",
                        'sparkplus_%',
                        $post_type
                    )
                );
                wp_send_json_success(array(
                    'message' => sprintf(
                        /* translators: 1: number of deleted meta entries, 2: post type label */
                        __('Removed %1$d post meta entries from "%2$s".', 'sparkplus'),
                        $deleted,
                        $post_type_label
                    )
                ));
                break;
                
            default:
                wp_send_json_error(array(
                    'message' => __('Invalid reset target.', 'sparkplus')
                ));
        }
    }
    
    /**
     * AJAX handler to get post type items for internal linking
     */
    public function get_post_type_items() {
        // Security check
        check_ajax_referer('sparkplus_settings_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'sparkplus')
            ));
        }
        
        // Get post type from request
        if (!isset($_POST['post_type']) || empty($_POST['post_type'])) {
            wp_send_json_error(array(
                'message' => __('No post type provided', 'sparkplus')
            ));
        }
        
        $post_type = sanitize_text_field(wp_unslash($_POST['post_type']));
        
        // Verify post type exists
        if (!post_type_exists($post_type)) {
            wp_send_json_error(array(
                'message' => __('Invalid post type', 'sparkplus')
            ));
        }
        
        // Get all published posts of this type
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Format the response
        $items = array();
        foreach ($posts as $post) {
            $items[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_permalink($post->ID)
            );
        }
        
        wp_send_json_success(array(
            'items' => $items
        ));
    }
    
    /**
     * AJAX handler to save linking pool settings
     */
    public function save_linking_pool() {
        // Security check
        check_ajax_referer('sparkplus_settings_nonce', 'nonce');
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'sparkplus')
            ));
        }
        
        // Save enable flags as separate options
        $linking_enable = isset($_POST['linking_enable']) && $_POST['linking_enable'] === '1';
        $linking_wysiwyg = isset($_POST['linking_wysiwyg']) && $_POST['linking_wysiwyg'] === '1';
        
        update_option('sparkplus_linking_enable', $linking_enable);
        update_option('sparkplus_linking_wysiwyg', $linking_wysiwyg);
        
        // Get linking pool data from request
        if (!isset($_POST['linking_pool'])) {
            wp_send_json_error(array(
                'message' => __('No linking pool data provided', 'sparkplus')
            ));
        }
        
        $linking_pool_raw = wp_unslash($_POST['linking_pool']);
        $linking_pool = json_decode($linking_pool_raw, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array(
                'message' => __('Invalid JSON data', 'sparkplus')
            ));
        }
        
        // Sanitize the data
        $sanitized = array(
            'post_types' => array(),
            'single_items' => array(),
            'custom_links' => array()
        );
        
        // Sanitize post types
        if (!empty($linking_pool['post_types']) && is_array($linking_pool['post_types'])) {
            foreach ($linking_pool['post_types'] as $post_type) {
                $sanitized_type = sanitize_key($post_type);
                if (post_type_exists($sanitized_type)) {
                    $sanitized['post_types'][] = $sanitized_type;
                }
            }
        }
        
        // Sanitize single items
        if (!empty($linking_pool['single_items']) && is_array($linking_pool['single_items'])) {
            foreach ($linking_pool['single_items'] as $item) {
                if (!empty($item['id']) && !empty($item['type'])) {
                    $sanitized['single_items'][] = array(
                        'id' => absint($item['id']),
                        'type' => sanitize_key($item['type']),
                        'title' => sanitize_text_field($item['title']),
                        'url' => esc_url_raw($item['url'])
                    );
                }
            }
        }
        
        // Sanitize custom links
        if (!empty($linking_pool['custom_links']) && is_array($linking_pool['custom_links'])) {
            foreach ($linking_pool['custom_links'] as $link) {
                if (!empty($link['url']) && !empty($link['title'])) {
                    $keywords = array();
                    if (!empty($link['keywords']) && is_array($link['keywords'])) {
                        foreach ($link['keywords'] as $keyword) {
                            $keywords[] = sanitize_text_field($keyword);
                        }
                    }
                    
                    $sanitized['custom_links'][] = array(
                        'url' => esc_url_raw($link['url']),
                        'title' => sanitize_text_field($link['title']),
                        'keywords' => $keywords
                    );
                }
            }
        }
        
        // Save to database
        update_option('sparkplus_linking_pool', wp_json_encode($sanitized));
        
        wp_send_json_success(array(
            'message' => __('Linking pool saved successfully', 'sparkplus')
        ));
    }
}
