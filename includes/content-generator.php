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
    private $debug_mode = false;
    private $debug_log = array();
    private $api_caller = null;
    private $prompt_builder = null;

    public function __construct() {
        // Initialize API caller with debug callback
        $this->api_caller = new KeyContentAI_OpenAI_API_Caller(array($this, 'add_debug'));
        
        // Initialize prompt builder
        $this->prompt_builder = new KeyContentAI_Prompt_Builder();
    }
    
    /**
     * Generate content for an existing post
     * 
     * @param int $post_id The post ID to generate content for
     * @return array Success/error response with debug_log
     */
    public function generate_content($post_id) {
        $this->debug_mode = true; // Always enable debug mode for generation page
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
            
            // 2. Gather all settings (includes validation)
            $settings = $this->gather_settings();
            
            // 3. Get custom fields for the post type
            $custom_fields = $this->get_custom_fields_config($post->post_type);
            
            // 4. Build the text generation prompt (will retrieve keyword and merge context)
            $text_prompt = $this->build_text_prompt($settings, $custom_fields, $post_id);
            /*
            // 8. Generate text content via GPT API
            $text_response = $this->api_caller->generate_text($text_prompt, $settings['api_key']);
            
            // 9. Build the image generation prompt(s)
            $image_prompts = $this->build_image_prompts($keyword, $settings, $custom_fields);

            // 10. Parse the text response
            $parsed_content = $this->parse_text_response($text_response['content'], $custom_fields);
            
            // 11. Generate images
            $image_responses = array();
            if (!empty($image_prompts)) {
                foreach ($image_prompts as $field_key => $image_prompt) {
                    $image_responses[$field_key] = $this->api_caller->generate_image($image_prompt, $settings['api_key']);
                }
            }

            // 12. Process image responses
            $image_ids = $this->process_image_responses($image_responses);
            
            // 13. Update post with generated content
            $this->update_post_with_content($post_id, $parsed_content, $image_ids, $custom_fields);
            
            $this->add_debug('Generation complete', array(
                'status' => 'success',
                'post_id' => $post_id,
                'text_generated' => true,
                'images_generated' => count($image_responses)
            ));
            */
            
            // Update last generation timestamp
            update_post_meta($post_id, 'keycontentai_last_generation', current_time('mysql'));
            
            // Get keyword for response message
            $keyword = get_post_meta($post_id, 'keycontentai_keyword', true);
            
            // Return success response
            return array(
                'success' => true,
                'post_id' => $post_id,
                'keyword' => $keyword,
                'message' => sprintf(__('Successfully updated post (ID: %d) for keyword: %s', 'keycontentai'), $post_id, $keyword),
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
    
    /**
     * Add debug entry
     * 
     * @param string $step The step name/description
     * @param mixed $data The data to log
     */
    public function add_debug($step, $data) {
        if ($this->debug_mode) {
            $this->debug_log[] = array(
                'step' => $step,
                'data' => $data,
                'timestamp' => current_time('mysql')
            );
        }
    }
    
    /**
     * Get WordPress site language
     * 
     * @return string Language code (e.g., 'en', 'de', 'fr')
     */
    private function get_site_language() {
        // Get WordPress locale (e.g., 'en_US', 'de_DE', 'fr_FR')
        $locale = get_locale();
        
        // Extract language code (first 2 characters)
        $language = substr($locale, 0, 2);
        
        return $language;
    }
    
    /**
     * Gather all plugin settings and validate required fields
     * 
     * @return array All plugin settings
     * @throws Exception If required settings are missing
     */
    private function gather_settings() {
        $settings = array(
            // API Settings
            'api_key' => get_option('keycontentai_openai_api_key', ''),
            
            // Post Type
            'post_type' => get_option('keycontentai_selected_post_type', 'post'),
            
            // WordPress Site Language
            'language' => $this->get_site_language(),
            
            // Client Information
            'addressing' => get_option('keycontentai_addressing', ''),
            'company_name' => get_option('keycontentai_company_name', ''),
            'industry' => get_option('keycontentai_industry', ''),
            'target_group' => get_option('keycontentai_target_group', ''),
            'usp' => get_option('keycontentai_usp', ''),
            'advantages' => get_option('keycontentai_advantages', ''),
            'buying_reasons' => get_option('keycontentai_buying_reasons', ''),
            'additional_context' => get_option('keycontentai_additional_context', ''),
            
            // Field Configurations (JSON format)
            'field_configs' => $this->get_field_configs_from_json(),
            
            // CPT Additional Context
            'cpt_additional_context' => get_option('keycontentai_cpt_additional_context', array())
        );
        
        $this->add_debug('gather_settings', array(
            'post_type' => $settings['post_type'],
            'has_api_key' => !empty($settings['api_key']),
            'language' => $settings['language'],
            'company_name' => $settings['company_name'],
            'industry' => $settings['industry']
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
    
    /**
     * Get field configs from the consolidated CPT configs
     */
    private function get_field_configs_from_json() {
        global $keycontentai;
        if ($keycontentai && method_exists($keycontentai, 'get_cpt_configs')) {
            $cpt_configs = $keycontentai->get_cpt_configs();
            
            // Convert to old format: array(post_type => fields)
            $field_configs = array();
            foreach ($cpt_configs as $post_type => $config) {
                if (isset($config['fields'])) {
                    $field_configs[$post_type] = $config['fields'];
                }
            }
            return $field_configs;
        }
        
        // Fallback: read directly from option
        $json = get_option('keycontentai_cpt_configs', '');
        
        if (empty($json)) {
            return array();
        }
        
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return array();
        }
        
        // Convert to old format
        $field_configs = array();
        foreach ($data as $post_type => $config) {
            if (isset($config['fields'])) {
                $field_configs[$post_type] = $config['fields'];
            }
        }
        return $field_configs;
    }
    
    /**
     * Get custom fields configuration for a post type (ACF only)
     * 
     * @param string $post_type The post type to get fields for
     * @return array Custom fields configuration
     * @throws Exception If ACF is not active or no fields found
     */
    private function get_custom_fields_config($post_type) {
        $this->add_debug('get_custom_fields_config', "Retrieving custom field configuration for post type: {$post_type}");
        
        // Check if ACF is active
        if (!function_exists('acf_get_field_groups')) {
            $this->add_debug('get_custom_fields_config', "ACF is not active");
            throw new Exception("Advanced Custom Fields plugin is not active");
        }
        
        $field_configs = $this->get_field_configs_from_json();
        
        if (!isset($field_configs[$post_type])) {
            $this->add_debug('get_custom_fields_config', "No field configuration found for post type: {$post_type}");
            throw new Exception("No field configuration found for post type: {$post_type}");
        }
        
        $post_type_configs = $field_configs[$post_type];
        $custom_fields = array();
        
        // Get ACF field groups for this post type
        $field_groups = acf_get_field_groups(array('post_type' => $post_type));
        
        if (empty($field_groups)) {
            $this->add_debug('get_custom_fields_config', "No ACF field groups found for post type: {$post_type}");
            throw new Exception("No ACF field groups found for post type: {$post_type}");
        }
        
        // Loop through each field group and get fields
        foreach ($field_groups as $group) {
            $fields = acf_get_fields($group['key']);
            
            if ($fields) {
                foreach ($fields as $field) {
                    $field_key = $field['name'];
                    
                    // Check if this field is configured and enabled
                    if (isset($post_type_configs[$field_key]) && !empty($post_type_configs[$field_key]['enabled'])) {
                        $custom_fields[] = array(
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
        
        if (empty($custom_fields)) {
            $this->add_debug('get_custom_fields_config', "No enabled custom fields found for post type: {$post_type}");
            throw new Exception("No enabled custom fields found for post type: {$post_type}");
        }
        
        $this->add_debug('get_custom_fields_config', array(
            'post_type' => $post_type,
            'field_count' => count($custom_fields),
            'fields' => $custom_fields
        ));
        
        return $custom_fields;
    }
    
    /**
     * Build the text generation prompt for OpenAI GPT
     * 
     * @param array $settings All settings
     * @param array $custom_fields Custom fields configuration
     * @param int $post_id Post ID to retrieve keyword and context from
     * @return string The complete text prompt
     * @throws Exception If keyword is missing
     */
    private function build_text_prompt($settings, $custom_fields, $post_id) {
        $this->add_debug('build_text_prompt', 'Building text prompt using KeyContentAI_Prompt_Builder');
        
        // Get keyword from post meta
        $keyword = get_post_meta($post_id, 'keycontentai_keyword', true);
        if (empty($keyword)) {
            throw new Exception(__('No keyword found for this post', 'keycontentai'));
        }
        
        // Get post-specific additional context
        $post_additional_context = get_post_meta($post_id, 'keycontentai_additional_context', true);
        
        // Merge post-specific additional context with CPT-level context
        if (!empty($post_additional_context)) {
            $cpt_context = isset($settings['additional_context']) ? $settings['additional_context'] : '';
            if (!empty($cpt_context)) {
                $settings['additional_context'] = $cpt_context . "\n\n" . $post_additional_context;
            } else {
                $settings['additional_context'] = $post_additional_context;
            }
            
            $this->add_debug('Merged additional context', array(
                'post_id' => $post_id,
                'keyword' => $keyword,
                'cpt_context_length' => strlen($cpt_context),
                'post_context_length' => strlen($post_additional_context),
                'merged_context_length' => strlen($settings['additional_context'])
            ));
        }
        
        // Filter only text fields (exclude image fields)
        $text_fields = array_filter($custom_fields, function($field) {
            return $field['type'] !== 'image';
        });
        
        // Build the complete prompt using the instance
        $prompt = $this->prompt_builder->build_prompt($keyword, $settings, $text_fields);
        
        // Log prompt details (including full prompt for debug tab)
        $this->add_debug('build_text_prompt', array(
            'prompt_length' => strlen($prompt),
            'prompt_preview' => $this->prompt_builder->get_prompt_preview($prompt, 300),
            'prompt' => $prompt,  // Use 'prompt' key for debug.js to extract
            'keyword' => $keyword,
            'language' => $settings['language'],
            'text_fields_count' => count($text_fields)
        ));
        
        return $prompt;
    }
    
    /**
     * Build image generation prompts for DALL-E
     * 
     * @param string $keyword The keyword
     * @param array $settings All settings
     * @param array $custom_fields Custom fields configuration
     * @return array Array of prompts keyed by field name
     */
    private function build_image_prompts($keyword, $settings, $custom_fields) {
        $this->add_debug('build_image_prompts', 'Building image prompts for DALL-E');
        
        $image_prompts = array();
        
        // Filter only image fields
        $image_fields = array_filter($custom_fields, function($field) {
            return $field['type'] === 'image';
        });
        
        if (empty($image_fields)) {
            $this->add_debug('build_image_prompts', 'No image fields found');
            return array();
        }
        
        // Build a prompt for each image field
        foreach ($image_fields as $field) {
            $prompt = $this->prompt_builder->build_image_prompt($keyword, $settings, $field);
            $image_prompts[$field['key']] = $prompt;
        }
        
        $this->add_debug('build_image_prompts', array(
            'image_fields_count' => count($image_fields),
            'prompts' => $image_prompts,
            'full_prompt' => !empty($image_prompts) ? implode("\n\n--- Next Image ---\n\n", $image_prompts) : ''  // Include all image prompts for debug display
        ));
        
        return $image_prompts;
    }
    
    /**
     * Parse text API response
     * 
     * @param string $response Raw JSON response from GPT
     * @param array $custom_fields Custom fields configuration
     * @return array Parsed content keyed by field name
     * @throws Exception If parsing fails
     */
    private function parse_text_response($response, $custom_fields) {
        $this->add_debug('parse_text_response', 'Parsing text response from GPT');
        
        // TODO: Implement JSON parsing and field extraction
        $this->add_debug('parse_text_response', 'TODO: Not yet implemented');
        
        return array();
    }
    
    /**
     * Process image responses from DALL-E
     * 
     * @param array $image_responses Array of image responses keyed by field name
     * @return array Array of WordPress attachment IDs keyed by field name
     * @throws Exception If processing fails
     */
    private function process_image_responses($image_responses) {
        $this->add_debug('process_image_responses', 'Processing DALL-E image responses');
        
        if (empty($image_responses)) {
            return array();
        }
        
        // TODO: Download images and upload to WordPress media library
        $this->add_debug('process_image_responses', 'TODO: Not yet implemented');
        
        return array();
    }
    
    /**
     * Create post with generated content and images
     * 
     * @param string $keyword The keyword (post title)
     * @param array $content Parsed text content
     * @param array $image_ids WordPress attachment IDs
     * @param string $post_type Post type
     * @param array $custom_fields Custom fields configuration
     * @return int Post ID
     * @throws Exception If post creation fails
     */
    private function create_post_with_content($keyword, $content, $image_ids, $post_type, $custom_fields) {
        $this->add_debug('create_post_with_content', 'Creating WordPress post');
        
        // TODO: Implement post creation with wp_insert_post() and ACF update_field()
        $this->add_debug('create_post_with_content', 'TODO: Not yet implemented');
        
        return 0;
    }
}
