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

class KeyContentAI_Content_Generator {
    private $debug_log = array();
    private $api_caller = null;
    private $prompt_builder = null;

    public function __construct() {
        // Initialize API caller with debug callback
        $this->api_caller = new KeyContentAI_OpenAI_API_Caller(array($this, 'add_debug'));
        
        // Initialize prompt builder with debug callback
        $this->prompt_builder = new KeyContentAI_Prompt_Builder(array($this, 'add_debug'));
    }
    
    public function generate_content($post_id) {
        $this->debug_log = array();
        
        try {
            // 1. Validate post exists
            $post = get_post($post_id);
            if (!$post) {
                throw new Exception(__('Post not found', 'keycontentai'));
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
            $text_generated = $this->generate_text_content($post_id, $cpt_settings, $post_settings);
            
            // 5. Generate image content
            $images_generated = $this->generate_image_content($post_id, $cpt_settings, $post_settings);
            //throw new Exception("Test error");
            $this->add_debug('Generation complete', array(
                'status' => 'success',
                'post_id' => $post_id,
                'text_generated' => $text_generated,
                'images_generated' => $images_generated
            ));
            
            // Update last generation timestamp
            update_post_meta($post_id, 'keycontentai_last_generation', current_time('mysql'));
            
            // Return success response
            return array(
                'success' => true,
                'post_id' => $post_id,
                'keyword' => $post_settings['keyword'],
                'message' => sprintf(__('Successfully updated post (ID: %d) for keyword: %s', 'keycontentai'), $post_id, $post_settings['keyword']),
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
        global $keycontentai;
        if ($keycontentai && method_exists($keycontentai, 'get_cpt_configs')) {
            $cpt_configs = $keycontentai->get_cpt_configs();
            if (isset($cpt_configs[$post_type]['additional_context'])) {
                $cpt_additional_context = $cpt_configs[$post_type]['additional_context'];
            }
        }
        
        $settings = array(
            // API Settings
            'api_key' => get_option('keycontentai_openai_api_key', ''),
            'text_model' => get_option('keycontentai_text_model', 'gpt-5.2'),
            'image_model' => get_option('keycontentai_image_model', 'dall-e-3'),
            
            // Post Type
            'post_type' => $post_type,
            
            // WordPress Site Language
            'language' => $this->get_site_language(),
            
            // Client Information
            'addressing' => get_option('keycontentai_addressing', 'formal'),
            'company_name' => get_option('keycontentai_company_name', ''),
            'industry' => get_option('keycontentai_industry', ''),
            'target_group' => get_option('keycontentai_target_group', ''),
            'usp' => get_option('keycontentai_usp', ''),
            'advantages' => get_option('keycontentai_advantages', ''),
            'buying_reasons' => get_option('keycontentai_buying_reasons', ''),
            
            // Custom Fields for this post type
            'custom_fields' => $this->get_custom_fields_config($post_type),
            
            // Two levels of additional context (Client + CPT)
            'client_additional_context' => get_option('keycontentai_additional_context', ''),
            'cpt_additional_context' => $cpt_additional_context
        );
        
        $this->add_debug('get_cpt_settings', array(
            'post_type' => $settings['post_type'],
            'has_api_key' => !empty($settings['api_key']),
            'language' => $settings['language'],
            'company_name' => $settings['company_name'],
            'industry' => $settings['industry'],
            'custom_fields_count' => count($settings['custom_fields']),
            'has_client_context' => !empty($settings['client_additional_context']),
            'has_cpt_context' => !empty($settings['cpt_additional_context'])
        ));
        
        // Validate required settings
        if (empty($settings['api_key'])) {
            throw new Exception(__('OpenAI API key is not configured. Please add it in the settings.', 'keycontentai'));
        }
        
        if (empty($settings['post_type'])) {
            throw new Exception(__('No post type selected. Please configure in the settings.', 'keycontentai'));
        }
        
        return $settings;
    }
    
    private function get_post_settings($post_id) {
        $settings = array(
            'keyword' => get_post_meta($post_id, 'keycontentai_keyword', true),
            'post_additional_context' => get_post_meta($post_id, 'keycontentai_additional_context', true)
        );
        
        $this->add_debug('get_post_settings', array(
            'post_id' => $post_id,
            'keyword' => $settings['keyword'],
            'has_post_context' => !empty($settings['post_additional_context'])
        ));
        
        // Validate keyword exists
        if (empty($settings['keyword'])) {
            throw new Exception(__('No keyword found for this post', 'keycontentai'));
        }
        
        return $settings;
    }
    
    private function get_custom_fields_config($post_type) {
        $this->add_debug('get_custom_fields_config', "Retrieving field configuration for post type: {$post_type}");
        
        // Get field configs from consolidated CPT configs
        global $keycontentai;
        $cpt_configs = $keycontentai->get_cpt_configs();
        
        if (!isset($cpt_configs[$post_type]['fields'])) {
            $this->add_debug('get_custom_fields_config', "No field configuration found for post type: {$post_type}");
            throw new Exception("No field configuration found for post type: {$post_type}");
        }
        
        $post_type_configs = $cpt_configs[$post_type]['fields'];
        $all_fields = array();
        
        // 1. Add WordPress baseline fields if configured
        $baseline_fields = array('post_title', 'post_content', 'post_excerpt', '_thumbnail_id');
        
        foreach ($baseline_fields as $field_key) {
            if (isset($post_type_configs[$field_key]) && !empty($post_type_configs[$field_key]['enabled'])) {
                $field_labels = array(
                    'post_title' => 'Title',
                    'post_content' => 'Content',
                    'post_excerpt' => 'Excerpt',
                    '_thumbnail_id' => 'Featured Image'
                );
                
                $field_type = 'text';
                if ($field_key === 'post_content') {
                    $field_type = 'wysiwyg';
                } elseif ($field_key === '_thumbnail_id') {
                    $field_type = 'image';
                }
                
                $all_fields[] = array(
                    'key' => $field_key,
                    'label' => $field_labels[$field_key],
                    'type' => $field_type,
                    'source' => 'wordpress',
                    'description' => isset($post_type_configs[$field_key]['description']) ? $post_type_configs[$field_key]['description'] : '',
                    'word_count' => isset($post_type_configs[$field_key]['word_count']) ? intval($post_type_configs[$field_key]['word_count']) : 0,
                    'enabled' => true
                );
            }
        }
        
        // 2. Add ACF custom fields if ACF is active
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups(array('post_type' => $post_type));
            
            if (!empty($field_groups)) {
                foreach ($field_groups as $group) {
                    $fields = acf_get_fields($group['key']);
                    
                    if ($fields) {
                        foreach ($fields as $field) {
                            $field_key = $field['name'];
                            
                            // Check if this field is configured and enabled
                            if (isset($post_type_configs[$field_key]) && !empty($post_type_configs[$field_key]['enabled'])) {
                                $all_fields[] = array(
                                    'key' => $field_key,
                                    'label' => $field['label'],
                                    'type' => $field['type'],
                                    'source' => 'acf',
                                    'description' => isset($post_type_configs[$field_key]['description']) ? $post_type_configs[$field_key]['description'] : '',
                                    'word_count' => isset($post_type_configs[$field_key]['word_count']) ? intval($post_type_configs[$field_key]['word_count']) : 0,
                                    'enabled' => true
                                );
                            }
                        }
                    }
                }
            }
        }
        
        // 3. Add image-specific options to all image fields (regardless of source)
        foreach ($all_fields as &$field) {
            if (in_array($field['type'], array('image', 'file', 'gallery'))) {
                $field_key = $field['key'];
                $field['width'] = isset($post_type_configs[$field_key]['width']) ? intval($post_type_configs[$field_key]['width']) : 1024;
                $field['height'] = isset($post_type_configs[$field_key]['height']) ? intval($post_type_configs[$field_key]['height']) : 1024;
                $field['quality'] = isset($post_type_configs[$field_key]['quality']) ? $post_type_configs[$field_key]['quality'] : 'auto';
            }
        }
        unset($field); // Break reference
        
        if (empty($all_fields)) {
            $this->add_debug('get_custom_fields_config', "No enabled fields found for post type: {$post_type}");
            throw new Exception("No enabled fields found for post type: {$post_type}");
        }
        
        $this->add_debug('get_custom_fields_config', array(
            'post_type' => $post_type,
            'total_fields' => count($all_fields),
            'wordpress_fields' => count(array_filter($all_fields, function($f) { return $f['source'] === 'wordpress'; })),
            'acf_fields' => count(array_filter($all_fields, function($f) { return $f['source'] === 'acf'; })),
            'fields' => $all_fields
        ));
        
        return $all_fields;
    }
    
    private function generate_text_content($post_id, $cpt_settings, $post_settings) {
        // Build the text generation prompt
        $text_prompt = $this->prompt_builder->build_text_prompt($cpt_settings, $post_settings);
        
        // Generate text content if we have text fields
        if (empty($text_prompt)) {
            return false;
        }
        
        $text_response = $this->api_caller->generate_text($text_prompt, $cpt_settings['api_key'], array(
            'model' => $cpt_settings['text_model']
        ));
        
        // Parse JSON response
        $parsed_content = json_decode($text_response['content'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse GPT response: ' . json_last_error_msg());
        }
        
        // Update post with generated text content
        $this->update_post_with_texts($post_id, $parsed_content, $cpt_settings['custom_fields']);
        
        return true;
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
            // 1. Build prompt for this specific image
            $image_prompt = $this->prompt_builder->build_image_prompt($cpt_settings, $post_settings, $field);
            
            // 2. Prepare image options from field config
            $image_options = array(
                'model' => $cpt_settings['image_model'],
                'size' => isset($field['size']) ? $field['size'] : 'auto',
                'quality' => isset($field['quality']) ? $field['quality'] : 'auto',
                'response_format' => 'url'
            );
            
            // 3. Make API call to generate the image
            $image_response = $this->api_caller->generate_image($image_prompt, $cpt_settings['api_key'], $image_options);
            
            // 4. Process and save the image immediately
            if ($image_response && isset($image_response['data'][0]['url'])) {
                $attachment_id = $this->save_image_from_url($image_response['data'][0]['url'], $post_id, $field);
                
                if ($attachment_id) {
                    // 5. Update the post with the generated image
                    $this->update_post_with_image($post_id, $field, $attachment_id);
                    $images_generated_count++;
                }
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
            return;
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
        
        $this->add_debug('update_post_with_texts', array(
            'status' => 'completed',
            'total_fields_updated' => count($wp_fields) + count($acf_fields)
        ));
    }
    
}
