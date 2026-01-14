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
    
    /**
     * Generate content for an existing post
     * 
     * @param int $post_id The post ID to generate content for
     * @return array Success/error response with debug_log
     */
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
            
            // 4. Generate text and image content
            $generation_result = $this->generate_texts_and_images($post_id, $cpt_settings, $post_settings);
            
            $this->add_debug('Generation complete', array(
                'status' => 'success',
                'post_id' => $post_id,
                'text_generated' => $generation_result['text_generated'],
                'images_generated' => $generation_result['images_generated']
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
    
    /**
     * Add debug entry
     * 
     * @param string $step The step name/description
     * @param mixed $data The data to log
     */
    public function add_debug($step, $data) {
        $this->debug_log[] = array(
            'step' => $step,
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
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
     * Get CPT-level settings (general + CPT-specific)
     * 
     * @param string $post_type The post type to get settings for
     * @return array CPT-level settings (including custom fields)
     * @throws Exception If required settings are missing
     */
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
    
    /**
     * Get post-specific settings
     * 
     * @param int $post_id The post ID
     * @return array Post-specific settings
     * @throws Exception If required settings are missing
     */
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
    
    /**
     * Get all fields configuration for a post type (WordPress baseline + ACF)
     * 
     * @param string $post_type The post type to get fields for
     * @return array All fields configuration
     * @throws Exception If no fields found
     */
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
        $baseline_fields = array('post_title', 'post_content', 'post_excerpt');
        
        foreach ($baseline_fields as $field_key) {
            if (isset($post_type_configs[$field_key]) && !empty($post_type_configs[$field_key]['enabled'])) {
                $field_labels = array(
                    'post_title' => 'Title',
                    'post_content' => 'Content',
                    'post_excerpt' => 'Excerpt'
                );
                
                $all_fields[] = array(
                    'key' => $field_key,
                    'label' => $field_labels[$field_key],
                    'type' => ($field_key === 'post_content') ? 'wysiwyg' : 'text',
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
    
    /**
     * Generate text and image content via API
     * 
     * @param int $post_id The post ID
     * @param array $cpt_settings CPT-level settings
     * @param array $post_settings Post-specific settings
     * @return array Result with text_generated and images_generated counts
     */
    private function generate_texts_and_images($post_id, $cpt_settings, $post_settings) {
        $result = array(
            'text_generated' => false,
            'images_generated' => 0
        );
        
        // 1. Build the text generation prompt
        $text_prompt = $this->prompt_builder->build_text_prompt($cpt_settings, $post_settings);
        
        // 2. Generate text content if we have text fields
        if (!empty($text_prompt)) {
            $text_response = $this->api_caller->generate_text($text_prompt, $cpt_settings['api_key']);
            
            // Parse the text response
            $parsed_content = $this->parse_text_response($text_response['content'], $cpt_settings['custom_fields']);
            
            // TODO: Update post with generated text content
            // $this->update_post_with_content($post_id, $parsed_content, $cpt_settings['custom_fields']);
            
            $result['text_generated'] = true;
        }
        
        // 3. Build and generate image content if we have image fields
        /*$image_prompts = $this->prompt_builder->build_image_prompts($cpt_settings, $post_settings);
        
        if (!empty($image_prompts)) {
            $image_responses = array();
            foreach ($image_prompts as $field_key => $image_prompt) {
                $image_responses[$field_key] = $this->api_caller->generate_image($image_prompt, $cpt_settings['api_key']);
            }
            
            // Process image responses and get attachment IDs
            $image_ids = $this->process_image_responses($image_responses);
            
            // TODO: Update post with generated images
            // $this->update_post_with_images($post_id, $image_ids, $cpt_settings['custom_fields']);
            
            $result['images_generated'] = count($image_prompts);
        }*/
        
        return $result;
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
