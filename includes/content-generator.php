<?php
/**
 * Content Generator Class
 * 
 * Handles all content generation logic including:
 * - Gathering settings and custom fields
 * - Building prompts
 * - Calling OpenAI API
 * - Creating posts
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SparkWP_Content_Generator {
    private $debug_log = array();
    private $api_caller = null;
    private $prompt_builder = null;

    public function __construct() {
        // Initialize API caller with debug callback
        $this->api_caller = new SparkWP_OpenAI_API_Caller(array($this, 'add_debug'));
        
        // Initialize prompt builder with debug callback
        $this->prompt_builder = new SparkWP_Prompt_Builder(array($this, 'add_debug'));
    }
    
    public function generate_content($post_id) {
        $this->debug_log = array();
        
        try {
            // 1. Validate post exists
            $post = get_post($post_id);
            if (!$post) {
                throw new Exception(__('Post not found', 'sparkwp'));
            }
            
            $this->add_debug('Starting content generation for existing post', array(
                'post_id' => $post_id,
                'post_type' => $post->post_type
            ));
            
            // 2. Gather CPT-level settings (includes validation and custom fields)
            $cpt_settings = $this->get_cpt_settings($post->post_type);
            
            // 3. Get post-specific settings
            $post_settings = $this->get_post_settings($post_id);
            
            // 4. Generate text content
            $texts_generated = $this->generate_text_content($post_id, $cpt_settings, $post_settings);
            
            // 5. Generate image content
            $images_generated = $this->generate_image_content($post_id, $cpt_settings, $post_settings);
       
            $this->add_debug('Generation complete', array(
                'status' => 'success',
                'post_id' => $post_id,
                'texts_generated' => $texts_generated,
                'images_generated' => $images_generated
            ));
            
            // Update last generation timestamp
            update_post_meta($post_id, 'sparkwp_last_generation', current_time('mysql'));
            
            // Return success response
            return array(
                'success' => true,
                'post_id' => $post_id,
                'keyword' => $post_settings['keyword'],
                'message' => sprintf(__('Successfully updated post (ID: %d) for keyword: %s', 'sparkwp'), $post_id, $post_settings['keyword']),
                'debug_log' => $this->debug_log
            );
            
        } catch (Exception $e) {
            // Log the error
            $this->add_debug('Error occurred', array(
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_line' => $e->getLine()
            ));
            
            // Return error response
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'debug_log' => $this->debug_log
            );
        }
    }
    
    public function add_debug($step, $data) {
        $this->debug_log[] = array(
            'step' => $step,
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
    }
    
    private function get_site_language() {
        // Get WordPress locale (e.g., 'en_US', 'de_DE', 'fr_FR')
        $locale = get_locale();
        
        // Extract language code (first 2 characters)
        $language = substr($locale, 0, 2);
        
        return $language;
    }
    
    private function get_cpt_settings($post_type) {
        // Get CPT-specific additional context from consolidated configs
        $cpt_additional_context = '';
        global $sparkwp;
        if ($sparkwp && method_exists($sparkwp, 'get_cpt_configs')) {
            $cpt_configs = $sparkwp->get_cpt_configs();
            if (isset($cpt_configs[$post_type]['additional_context'])) {
                $cpt_additional_context = $cpt_configs[$post_type]['additional_context'];
            }
        }
        
        $settings = array(
            // API Settings
            'api_key' => get_option('sparkwp_openai_api_key', ''),
            'text_model' => get_option('sparkwp_text_model', 'gpt-5.2'),
            'image_model' => get_option('sparkwp_image_model', 'gpt-image-1.5'),
            
            // Post Type
            'post_type' => $post_type,
            
            // WordPress Site Language
            'language' => $this->get_site_language(),
            
            // General Context Information
            'addressing' => get_option('sparkwp_addressing', 'formal'),
            'company_name' => get_option('sparkwp_company_name', ''),
            'industry' => get_option('sparkwp_industry', ''),
            'target_group' => get_option('sparkwp_target_group', ''),
            'usp' => get_option('sparkwp_usp', ''),
            'advantages' => get_option('sparkwp_advantages', ''),
            'buying_reasons' => get_option('sparkwp_buying_reasons', ''),
            
            // Custom Fields for this post type
            'custom_fields' => $this->get_custom_fields_config($post_type),
            
            // Two levels of additional context (General Context + CPT)
            'general_context_additional_context' => get_option('sparkwp_additional_context', ''),
            'cpt_additional_context' => $cpt_additional_context
        );
        
        $this->add_debug('get_cpt_settings', array(
            'post_type' => $settings['post_type'],
            'has_api_key' => !empty($settings['api_key']),
            'language' => $settings['language'],
            'company_name' => $settings['company_name'],
            'industry' => $settings['industry'],
            'custom_fields_count' => count($settings['custom_fields']),
            'has_general_context' => !empty($settings['general_context_additional_context']),
            'has_cpt_context' => !empty($settings['cpt_additional_context'])
        ));
        
        // Validate required settings
        if (empty($settings['api_key'])) {
            throw new Exception(__('OpenAI API key is not configured. Please add it in the settings.', 'sparkwp'));
        }
        
        if (empty($settings['post_type'])) {
            throw new Exception(__('No post type selected. Please configure in the settings.', 'sparkwp'));
        }
        
        return $settings;
    }
    
    private function get_post_settings($post_id) {
        $settings = array(
            'keyword' => get_post_meta($post_id, 'sparkwp_keyword', true),
            'post_additional_context' => get_post_meta($post_id, 'sparkwp_additional_context', true)
        );
        
        $this->add_debug('get_post_settings', array(
            'post_id' => $post_id,
            'keyword' => $settings['keyword'],
            'has_post_context' => !empty($settings['post_additional_context'])
        ));
        
        // Validate keyword exists
        if (empty($settings['keyword'])) {
            throw new Exception(__('No keyword found for this post', 'sparkwp'));
        }
        
        return $settings;
    }
    
    private function get_custom_fields_config($post_type) {
        $this->add_debug('get_custom_fields_config', "Retrieving field configuration for post type: {$post_type}");
        
        // Get user's field settings
        global $sparkwp;
        $cpt_configs = $sparkwp->get_cpt_configs();
        $user_settings = isset($cpt_configs[$post_type]['fields']) ? $cpt_configs[$post_type]['fields'] : array();
        
        $all_fields = array();
        
        // 1. Collect all WordPress baseline fields that exist
        $baseline_fields = array(
            'post_title' => array('label' => 'Title', 'type' => 'text'),
            'post_content' => array('label' => 'Content', 'type' => 'wysiwyg'),
            'post_excerpt' => array('label' => 'Excerpt', 'type' => 'text'),
            '_thumbnail_id' => array('label' => 'Featured Image', 'type' => 'image')
        );
        
        foreach ($baseline_fields as $field_key => $field_info) {
            $all_fields[$field_key] = array(
                'key' => $field_key,
                'label' => $field_info['label'],
                'type' => $field_info['type'],
                'source' => 'wordpress'
            );
        }
        
        // 2. Collect all ACF fields that exist
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups(array('post_type' => $post_type));
            
            if (!empty($field_groups)) {
                foreach ($field_groups as $group) {
                    $fields = acf_get_fields($group['key']);
                    
                    if ($fields) {
                        foreach ($fields as $field) {
                            $all_fields[$field['name']] = array(
                                'key' => $field['name'],
                                'label' => $field['label'],
                                'type' => $field['type'],
                                'source' => 'acf'
                            );
                        }
                    }
                }
            }
        }
        
        // 3. Overlay user settings onto existing fields (left join)
        $enabled_fields = array();
        
        foreach ($all_fields as $field_key => $field_data) {
            // Check if user has enabled this field
            if (isset($user_settings[$field_key]) && !empty($user_settings[$field_key]['enabled'])) {
                // Merge field data with user settings
                $enabled_fields[] = array_merge($field_data, array(
                    'description' => isset($user_settings[$field_key]['description']) ? $user_settings[$field_key]['description'] : '',
                    'word_count' => isset($user_settings[$field_key]['word_count']) ? intval($user_settings[$field_key]['word_count']) : 0,
                    'size' => isset($user_settings[$field_key]['size']) ? $user_settings[$field_key]['size'] : 'auto',
                    'quality' => isset($user_settings[$field_key]['quality']) ? $user_settings[$field_key]['quality'] : 'auto',
                    'enabled' => true
                ));
            }
        }
        
        if (empty($enabled_fields)) {
            $this->add_debug('get_custom_fields_config', "No enabled fields found for post type: {$post_type}");
            throw new Exception("No enabled fields found for post type: {$post_type}");
        }
        
        $this->add_debug('get_custom_fields_config', array(
            'post_type' => $post_type,
            'total_existing_fields' => count($all_fields),
            'enabled_fields' => count($enabled_fields),
            'wordpress_fields' => count(array_filter($enabled_fields, function($f) { return $f['source'] === 'wordpress'; })),
            'acf_fields' => count(array_filter($enabled_fields, function($f) { return $f['source'] === 'acf'; }))
        ));
        
        return $enabled_fields;
    }
    
    private function generate_text_content($post_id, $cpt_settings, $post_settings) {
        // Build the text generation prompt
        $text_prompt = $this->prompt_builder->build_text_prompt($cpt_settings, $post_settings);
        
        // Generate text content if we have text fields
        if (empty($text_prompt)) {
            return 0;
        }
        
        $text_response = $this->api_caller->generate_text($text_prompt, $cpt_settings['api_key'], array(
            'model' => $cpt_settings['text_model']
        ));
        
        // Parse JSON response
        $parsed_content = json_decode($text_response['content'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse GPT response: ' . json_last_error_msg());
        }
        
        // Update post with generated text content and return count
        return $this->update_post_with_texts($post_id, $parsed_content, $cpt_settings['custom_fields']);
    }
    
    private function generate_image_content($post_id, $cpt_settings, $post_settings) {
        // Filter only image fields
        $image_fields = array_filter($cpt_settings['custom_fields'], function($field) {
            return $field['type'] === 'image';
        });
        
        if (empty($image_fields)) {
            return 0;
        }
        
        $images_generated_count = 0;
        
        // Process each image field individually
        foreach ($image_fields as $field) {
            // 1. Build prompt for this specific image (passing post_id to retrieve existing content)
            $image_prompt = $this->prompt_builder->build_image_prompt($cpt_settings, $post_settings, $field, $post_id);
            
            // 2. Prepare image options from field config
            $image_options = array(
                'model' => $cpt_settings['image_model'],
                'size' => isset($field['size']) ? $field['size'] : 'auto',
                'quality' => isset($field['quality']) ? $field['quality'] : 'auto'
            );
            
            // 3. Make API call to generate the image
            $image_response = $this->api_caller->generate_image($image_prompt, $cpt_settings['api_key'], $image_options);
            
            // 4. Process and save the image as WebP (gpt-image returns b64_json)
            if ($image_response && isset($image_response['data'][0]['b64_json'])) {
                // Convert to WebP format
                $webp_data = sparkwp_convert_image_to_webp($image_response['data'][0]['b64_json'], 90);
                
                if (is_wp_error($webp_data)) {
                    throw new Exception(sprintf(
                        __('Failed to convert image to WebP for field "%s": %s', 'sparkwp'),
                        $field['label'],
                        $webp_data->get_error_message()
                    ));
                }
                
                // Save WebP to media library
                $filename = sanitize_file_name($field['label']) . '-' . time();
                $attachment_id = sparkwp_save_webp_to_media_library($webp_data, $post_id, $filename, $field['label']);
                
                if (is_wp_error($attachment_id)) {
                    throw new Exception(sprintf(
                        __('Failed to save WebP image for field "%s": %s', 'sparkwp'),
                        $field['label'],
                        $attachment_id->get_error_message()
                    ));
                }
                
                // 5. Update the post with the generated image
                $this->update_post_with_image($post_id, $field, $attachment_id);
                
                $this->add_debug('generate_image_content', array(
                    'field_key' => $field['key'],
                    'attachment_id' => $attachment_id,
                    'format' => 'webp',
                    'success' => true
                ));
                
                $images_generated_count++;
            }
        }
        
        return $images_generated_count;
    }
    
    /**
     * Save an image from URL to WordPress Media Library
     * 
     * @param string $image_url The URL of the image to download
     * @param int $post_id The post ID to attach the image to
     * @param array $field The field configuration
     * @return int|false The attachment ID on success, false on failure
     */
    private function save_image_from_url($image_url, $post_id, $field) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Generate a unique filename based on the field
        $filename = sanitize_file_name($field['label']) . '-' . time() . '.png';
        
        // Download the image
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            $this->add_debug('save_image_from_url', array(
                'error' => 'Failed to download image',
                'message' => $tmp->get_error_message(),
                'url' => $image_url
            ));
            return false;
        }
        
        // Prepare file array
        $file_array = array(
            'name' => $filename,
            'tmp_name' => $tmp
        );
        
        // Upload the file to WordPress Media Library
        $attachment_id = media_handle_sideload($file_array, $post_id, $field['label']);
        
        // Clean up temporary file
        if (file_exists($tmp)) {
            @unlink($tmp);
        }
        
        if (is_wp_error($attachment_id)) {
            $this->add_debug('save_image_from_url', array(
                'error' => 'Failed to create attachment',
                'message' => $attachment_id->get_error_message()
            ));
            return false;
        }
        
        $this->add_debug('save_image_from_url', array(
            'attachment_id' => $attachment_id,
            'field_key' => $field['key'],
            'filename' => $filename
        ));
        
        return $attachment_id;
    }
    
    /**
     * Save an image from base64 data to WordPress Media Library
     * 
     * @param string $base64_data The base64 encoded image data
     * @param int $post_id The post ID to attach the image to
     * @param array $field The field configuration
     * @return int|false The attachment ID on success, false on failure
     */
    private function save_image_from_base64($base64_data, $post_id, $field) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php'); 
        
        // Decode base64 data
        $image_data = base64_decode($base64_data);
        
        if ($image_data === false) {
            $this->add_debug('save_image_from_base64', array(
                'error' => 'Failed to decode base64 data',
                'field_key' => $field['key']
            ));
            return false;
        }
        
        // Generate a unique filename
        $filename = sanitize_file_name($field['label']) . '-' . time() . '.png';
        
        // Get WordPress upload directory
        $upload_dir = wp_upload_dir();
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        // Save the decoded image to a file
        $file_saved = file_put_contents($filepath, $image_data);
        
        if ($file_saved === false) {
            $this->add_debug('save_image_from_base64', array(
                'error' => 'Failed to save image file',
                'filepath' => $filepath
            ));
            return false;
        }
        
        // Prepare attachment data
        $attachment = array(
            'guid' => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => 'image/png',
            'post_title' => $field['label'],
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        // Insert the attachment into the database
        $attachment_id = wp_insert_attachment($attachment, $filepath, $post_id);
        
        if (is_wp_error($attachment_id)) {
            $this->add_debug('save_image_from_base64', array(
                'error' => 'Failed to create attachment',
                'message' => $attachment_id->get_error_message()
            ));
            return false;
        }
        
        // Generate attachment metadata and update
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $filepath);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        $this->add_debug('save_image_from_base64', array(
            'attachment_id' => $attachment_id,
            'field_key' => $field['key'],
            'filename' => $filename,
            'filesize' => strlen($image_data)
        ));
        
        return $attachment_id;
    }
    
    /**
     * Update post with generated image
     * 
     * @param int $post_id The post ID
     * @param array $field The field configuration
     * @param int $attachment_id The attachment ID
     */
    private function update_post_with_image($post_id, $field, $attachment_id) {
        // Handle featured image (_thumbnail_id)
        if ($field['key'] === '_thumbnail_id') {
            set_post_thumbnail($post_id, $attachment_id);
            
            $this->add_debug('update_post_with_image', array(
                'action' => 'set_featured_image',
                'post_id' => $post_id,
                'attachment_id' => $attachment_id
            ));
            return;
        }
        
        // Handle ACF image fields
        if ($field['source'] === 'acf' && function_exists('update_field')) {
            $updated = update_field($field['key'], $attachment_id, $post_id);
            
            $this->add_debug('update_post_with_image', array(
                'action' => 'update_acf_field',
                'field_key' => $field['key'],
                'post_id' => $post_id,
                'attachment_id' => $attachment_id,
                'success' => $updated
            ));
        }
    }
    
    private function update_post_with_texts($post_id, $parsed_content, $custom_fields) {
        $this->add_debug('update_post_with_texts', 'Updating post with generated text content');
        
        if (empty($parsed_content)) {
            $this->add_debug('update_post_with_texts', 'No content to update');
            return 0;
        }
        
        // Separate WordPress baseline fields from ACF fields
        $wp_fields = array();
        $acf_fields = array();
        
        foreach ($custom_fields as $field) {
            // Skip image fields - only process text fields
            if ($field['type'] === 'image') {
                continue;
            }
            
            $field_key = $field['key'];
            
            // Check if we have content for this field
            if (!isset($parsed_content[$field_key])) {
                continue;
            }
            
            if ($field['source'] === 'wordpress') {
                $wp_fields[$field_key] = $parsed_content[$field_key];
            } elseif ($field['source'] === 'acf') {
                $acf_fields[$field_key] = $parsed_content[$field_key];
            }
        }
        
        // Update WordPress baseline fields
        if (!empty($wp_fields)) {
            $post_data = array('ID' => $post_id);
            
            if (isset($wp_fields['post_title'])) {
                $post_data['post_title'] = $wp_fields['post_title'];
            }
            
            if (isset($wp_fields['post_content'])) {
                $post_data['post_content'] = $wp_fields['post_content'];
            }
            
            if (isset($wp_fields['post_excerpt'])) {
                $post_data['post_excerpt'] = $wp_fields['post_excerpt'];
            }
            
            $result = wp_update_post($post_data, true);
            
            if (is_wp_error($result)) {
                throw new Exception('Failed to update WordPress fields: ' . $result->get_error_message());
            }
            
            $this->add_debug('update_post_with_texts', array(
                'wordpress_fields_updated' => array_keys($wp_fields)
            ));
        }
        
        // Update ACF fields
        if (!empty($acf_fields) && function_exists('update_field')) {
            foreach ($acf_fields as $field_key => $field_value) {
                $updated = update_field($field_key, $field_value, $post_id);
                
                if (!$updated) {
                    $this->add_debug('update_post_with_texts', array(
                        'warning' => "Failed to update ACF field: {$field_key}"
                    ));
                }
            }
            
            $this->add_debug('update_post_with_texts', array(
                'acf_fields_updated' => array_keys($acf_fields)
            ));
        }
        
        $total_updated = count($wp_fields) + count($acf_fields);
        
        $this->add_debug('update_post_with_texts', array(
            'status' => 'completed',
            'total_fields_updated' => $total_updated
        ));
        
        return $total_updated;
    }
    
}
