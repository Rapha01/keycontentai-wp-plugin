<?php
/**
 * Keyword Loader
 * 
 * Handles loading keywords and creating posts
 * 
 * @package SparkWP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SparkWP_Keyword_Loader
 * 
 * Manages keyword loading and post creation
 */
class SparkWP_Keyword_Loader {
    
    /**
     * Load a keyword and create a post if it doesn't exist
     * 
     * @param string $keyword The keyword to process
     * @param bool $debug_mode Whether to include debug information
     * @param bool $auto_publish Whether to publish the post immediately
     * @param string $additional_context Optional additional context for the post
     * @return array Result array with success status, message, and data
     */
    public function load_keyword($keyword, $debug_mode = false, $auto_publish = false, $additional_context = '') {
        // Get selected post type from settings
        $post_type = get_option('sparkwp_selected_post_type', 'post');
        
        // Trim keyword but preserve case
        $keyword = trim($keyword);
        
        // Initialize result
        $result = array(
            'success' => false,
            'message' => '',
            'post_id' => 0,
            'post_title' => $keyword,
            'exists' => false,
            'published' => false,
            'debug' => array()
        );
        
        // Add debug info
        if ($debug_mode) {
            $result['debug'][] = array(
                'step' => 'start',
                'data' => array(
                    'keyword' => $keyword,
                    'post_type' => $post_type,
                    'auto_publish' => $auto_publish,
                    'timestamp' => current_time('mysql')
                )
            );
        }
        
        // Check if a post with this keyword already exists
        $existing_posts = get_posts(array(
            'post_type' => $post_type,
            'meta_key' => 'sparkwp_keyword',
            'meta_value' => $keyword,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ));
        
        $existing_post = !empty($existing_posts) ? $existing_posts[0] : null;
        
        if ($existing_post) {
            if ($debug_mode) {
                $result['debug'][] = array(
                    'step' => 'check_existing',
                    'data' => array(
                        'exists' => true,
                        'post_id' => $existing_post->ID,
                        'post_title' => $existing_post->post_title,
                        'post_status' => $existing_post->post_status,
                        'keyword_meta' => get_post_meta($existing_post->ID, 'sparkwp_keyword', true)
                    )
                );
            }
            
            // If auto-publish is enabled and the post is a draft, publish it
            if ($auto_publish && $existing_post->post_status === 'draft') {
                $update_result = wp_update_post(array(
                    'ID' => $existing_post->ID,
                    'post_status' => 'publish'
                ));
                
                if ($debug_mode) {
                    $result['debug'][] = array(
                        'step' => 'publish_existing',
                        'data' => array(
                            'post_id' => $existing_post->ID,
                            'previous_status' => 'draft',
                            'new_status' => 'publish',
                            'update_result' => !is_wp_error($update_result)
                        )
                    );
                }
                
                if (!is_wp_error($update_result)) {
                    $result['success'] = true;
                    /* translators: 1: keyword, 2: post title, 3: post ID */
                    $result['message'] = sprintf(__('Post with keyword "%1$s" already exists and has been published: "%2$s" (ID: %3$d)', 'sparkwp'), $keyword, $existing_post->post_title, $existing_post->ID);
                    $result['post_id'] = $existing_post->ID;
                    $result['exists'] = true;
                    $result['published'] = true;
                    
                    return $result;
                }
            }
            
            $result['success'] = true;
            /* translators: 1: keyword, 2: post title, 3: post ID */
            $result['message'] = sprintf(__('Post with keyword "%1$s" already exists: "%2$s" (ID: %3$d)', 'sparkwp'), $keyword, $existing_post->post_title, $existing_post->ID);
            $result['post_id'] = $existing_post->ID;
            $result['exists'] = true;
            
            return $result;
        }
        
        if ($debug_mode) {
            $result['debug'][] = array(
                'step' => 'check_existing',
                'data' => array(
                    'exists' => false
                )
            );
        }
        
        // Create the post
        $post_data = array(
            'post_title'   => $keyword,
            'post_type'    => $post_type,
            'post_status'  => $auto_publish ? 'publish' : 'draft',
            'post_author'  => get_current_user_id()
        );
        
        if ($debug_mode) {
            $result['debug'][] = array(
                'step' => 'create_post',
                'data' => array(
                    'post_data' => $post_data
                )
            );
        }
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            if ($debug_mode) {
                $result['debug'][] = array(
                    'step' => 'create_post_error',
                    'data' => array(
                        'error_message' => $post_id->get_error_message()
                    )
                );
            }
            
            $result['success'] = false;
            /* translators: %s: error message */
            $result['message'] = sprintf(__('Failed to create post: %s', 'sparkwp'), $post_id->get_error_message());
            
            return $result;
        }
        
        if ($debug_mode) {
            $result['debug'][] = array(
                'step' => 'create_post_success',
                'data' => array(
                    'post_id' => $post_id,
                    'post_title' => $keyword,
                    'post_type' => $post_type,
                    'post_status' => $auto_publish ? 'publish' : 'draft'
                )
            );
        }
        
        // Save the keyword to the custom field (preserve original case)
        update_post_meta($post_id, 'sparkwp_keyword', $keyword);
        
        // Save the additional context if provided
        if (!empty($additional_context)) {
            update_post_meta($post_id, 'sparkwp_additional_context', $additional_context);
        }
        
        if ($debug_mode) {
            $result['debug'][] = array(
                'step' => 'save_keyword_meta',
                'data' => array(
                    'post_id' => $post_id,
                    'keyword' => $keyword,
                    'additional_context' => $additional_context
                )
            );
        }
        
        // Success
        $result['success'] = true;
        $result['message'] = $auto_publish 
            /* translators: %s: keyword/post title */
            ? sprintf(__('Post created and published successfully: "%s"', 'sparkwp'), $keyword)
            /* translators: %s: keyword/post title */
            : sprintf(__('Post created successfully: "%s"', 'sparkwp'), $keyword);
        $result['post_id'] = $post_id;
        $result['exists'] = false;
        $result['published'] = $auto_publish;
        
        return $result;
    }
}
