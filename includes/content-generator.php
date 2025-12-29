<?php
/**
 * Content Generator Class
 * 
 * Handles all content generation logic including:
 * - Gathering settings
 * - Getting custom fields
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
     * Generate content for a keyword
     * 
     * @param string $keyword The keyword to generate content for
     * @param bool $debug_mode Whether to enable debug mode
     * @return array Success/error response
     */
    public function generate_content($keyword, $debug_mode = false) {
        $this->debug_mode = $debug_mode;
        $this->debug_log = array();
        
        try {
            $this->add_debug('Starting content generation', array(
                'keyword' => $keyword,
                'debug_mode' => $debug_mode
            ));
            
            // 1. Gather all settings (includes validation)
            $settings = $this->gather_settings();
            
            // 2. Get custom fields for selected post type
            $custom_fields = $this->get_custom_fields_config($settings['post_type']);
            
            // 3. Build the text generation prompt
            $text_prompt = $this->build_text_prompt($keyword, $settings, $custom_fields);
            
            // 4. Generate text content via GPT API
            $text_response = $this->api_caller->generate_text($text_prompt, $settings['api_key']);
            
            // 5. Build the image generation prompt(s)
            $image_prompts = $this->build_image_prompts($keyword, $settings, $custom_fields);

            // 6. Parse the text response
            $parsed_content = $this->parse_text_response($text_response['content'], $custom_fields);
            /*
            // 7. Generate images
            $image_responses = array();
            if (!empty($image_prompts)) {
                foreach ($image_prompts as $field_key => $image_prompt) {
                    $image_responses[$field_key] = 'url';//$this->api_caller->generate_image($image_prompt, $settings['api_key']);
                }
            }

            
            // 8. Process image responses
            $image_ids = $this->process_image_responses($image_responses);
            
            // 9. Create/update post with text content and images
            $post_id = $this->create_post_with_content($keyword, $parsed_content, $image_ids, $settings['post_type'], $custom_fields);
            
            $this->add_debug('Generation complete', array(
                'status' => 'success',
                'post_id' => $post_id,
                'text_generated' => true,
                'images_generated' => count($image_responses)
            ));
            */
            // Return success response
            return array(
                'success' => true,
                'post_id' => $post_id,
                'keyword' => $keyword,
                'post_type' => $settings['post_type'],
                'message' => sprintf(__('Successfully created post (ID: %d) for keyword: %s', 'keycontentai'), $post_id, $keyword),
                'debug' => $this->debug_log
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
                'debug' => $this->debug_log
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
            
            // Client Information
            'language' => get_option('keycontentai_language', 'de'),
            'addressing' => get_option('keycontentai_addressing', ''),
            'company_name' => get_option('keycontentai_company_name', ''),
            'industry' => get_option('keycontentai_industry', ''),
            'target_group' => get_option('keycontentai_target_group', ''),
            'usp' => get_option('keycontentai_usp', ''),
            'advantages' => get_option('keycontentai_advantages', ''),
            'buying_reasons' => get_option('keycontentai_buying_reasons', ''),
            'additional_context' => get_option('keycontentai_additional_context', ''),
            
            // Competition
            'competition_urls' => get_option('keycontentai_competition_urls', array()),
            
            // Field Configurations (new structure)
            'field_configs' => get_option('keycontentai_field_configs', array()),
            
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
        
        $field_configs = get_option('keycontentai_field_configs', array());
        
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
     * @param string $keyword The keyword
     * @param array $settings All settings
     * @param array $custom_fields Custom fields configuration
     * @return string The complete text prompt
     */
    private function build_text_prompt($keyword, $settings, $custom_fields) {
        $this->add_debug('build_text_prompt', 'Building text prompt using KeyContentAI_Prompt_Builder');
        
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
            'full_prompt' => $prompt,  // Include full text prompt for debug display
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
