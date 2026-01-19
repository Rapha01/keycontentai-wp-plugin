<?php
/**
 * Plugin Name: KeyContentAI
 * Plugin URI: https://keycontentai.com
 * Description: Creates and populates custom post types with AI-generated content based on user keywords.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://keycontentai.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: keycontentai
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KEYCONTENTAI_VERSION', '1.0.0');
define('KEYCONTENTAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KEYCONTENTAI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KEYCONTENTAI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main KeyContentAI Class
 */
class KeyContentAI {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the plugin
     */
    private function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Register post meta fields
        add_action('init', array($this, 'register_post_meta'));
        
        // Initialize admin
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            
            // AJAX handlers
            add_action('wp_ajax_keycontentai_generate_content', array($this, 'ajax_generate_content'));
            add_action('wp_ajax_keycontentai_fetch_models', array($this, 'ajax_fetch_models'));
            add_action('wp_ajax_keycontentai_load_keyword', array($this, 'ajax_load_keyword'));
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load utility functions
        require_once KEYCONTENTAI_PLUGIN_DIR . 'includes/util.php';
        
        // Load prompt builder class
        require_once KEYCONTENTAI_PLUGIN_DIR . 'includes/prompt-builder.php';
        
        // Load OpenAI API caller class
        require_once KEYCONTENTAI_PLUGIN_DIR . 'includes/openai-api-caller.php';
        
        // Load content generator class
        require_once KEYCONTENTAI_PLUGIN_DIR . 'includes/content-generator.php';
        
        // Load keyword loader class
        require_once KEYCONTENTAI_PLUGIN_DIR . 'includes/keyword-loader.php';
        
        // Load admin meta box (only in admin)
        if (is_admin()) {
            require_once KEYCONTENTAI_PLUGIN_DIR . 'admin/edit-meta-box.php';
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add top-level menu
        add_menu_page(
            __('KeyContentAI', 'keycontentai'),           // Page title
            __('KeyContentAI', 'keycontentai'),           // Menu title
            'manage_options',                              // Capability
            'keycontentai-load-keywords',                  // Menu slug
            array($this, 'render_load_keywords_page'),    // Callback
            'dashicons-admin-post',                        // Icon
            30                                             // Position
        );
        
        // Add submenu items
        add_submenu_page(
            'keycontentai-load-keywords',                  // Parent slug
            __('Load Keywords', 'keycontentai'),          // Page title
            __('Load Keywords', 'keycontentai'),          // Menu title
            'manage_options',                              // Capability
            'keycontentai-load-keywords',                  // Menu slug (same as parent for first item)
            array($this, 'render_load_keywords_page')     // Callback
        );

        add_submenu_page(
            'keycontentai-load-keywords',                  // Parent slug
            __('Generation', 'keycontentai'),             // Page title
            __('Generation', 'keycontentai'),             // Menu title
            'manage_options',                              // Capability
            'keycontentai-generation',                     // Menu slug
            array($this, 'render_generation_page')        // Callback
        );
        
        add_submenu_page(
            'keycontentai-load-keywords',                  // Parent slug
            __('Internal Linking', 'keycontentai'),       // Page title
            __('Internal Linking', 'keycontentai'),       // Menu title
            'manage_options',                              // Capability
            'keycontentai-internal-linking',               // Menu slug
            array($this, 'render_internal_linking_page')  // Callback
        );
        
        add_submenu_page(
            'keycontentai-load-keywords',                  // Parent slug
            __('Settings', 'keycontentai'),               // Page title
            __('Settings', 'keycontentai'),               // Menu title
            'manage_options',                              // Capability
            'keycontentai-settings',                       // Menu slug
            array($this, 'render_settings_page')          // Callback
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // API Settings
        register_setting('keycontentai_api_settings', 'keycontentai_openai_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('keycontentai_api_settings', 'keycontentai_text_model', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'gpt-4o-mini'
        ));
        
        register_setting('keycontentai_api_settings', 'keycontentai_image_model', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'dall-e-3'
        ));
        
        // Client Settings
        register_setting('keycontentai_client_settings', 'keycontentai_addressing', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'formal'
        ));
        
        register_setting('keycontentai_client_settings', 'keycontentai_company_name', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('keycontentai_client_settings', 'keycontentai_industry', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('keycontentai_client_settings', 'keycontentai_target_group', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('keycontentai_client_settings', 'keycontentai_usp', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('keycontentai_client_settings', 'keycontentai_advantages', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('keycontentai_client_settings', 'keycontentai_buying_reasons', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('keycontentai_client_settings', 'keycontentai_additional_context', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        // CPT Settings
        register_setting('keycontentai_cpt_settings', 'keycontentai_selected_post_type', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'post'
        ));
        
        // CPT Configurations (consolidated) - Stored as JSON
        // Structure: array('post_type' => array('additional_context' => '', 'fields' => array('field_key' => array(...))))
        register_setting('keycontentai_cpt_settings', 'keycontentai_cpt_configs', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_cpt_configs'),
            'default' => ''
        ));
    }
    
    /**
     * Get CPT configs (reads from JSON format)
     */
    public function get_cpt_configs() {
        $json = get_option('keycontentai_cpt_configs', '');
        
        if (empty($json)) {
            return array();
        }
        
        $data = json_decode($json, true);
        return is_array($data) ? $data : array();
    }
    
    /**
     * Sanitize CPT configurations and save as JSON
     */
    public function sanitize_cpt_configs($input) {
        // Input comes as array from the form
        if (!is_array($input)) {
            // If it's already JSON string, decode it first
            if (is_string($input)) {
                $input = json_decode($input, true);
            }
            if (!is_array($input)) {
                return '';
            }
        }
        
        // Get existing configs to preserve data for other post types
        $existing_configs = $this->get_cpt_configs();
        
        // Start with existing data
        $sanitized = is_array($existing_configs) ? $existing_configs : array();
        
        // Update only the post types that are in the input
        foreach ($input as $post_type => $data) {
            $post_type_clean = sanitize_key($post_type);
            
            if (!isset($sanitized[$post_type_clean])) {
                $sanitized[$post_type_clean] = array(
                    'additional_context' => '',
                    'fields' => array()
                );
            }
            
            // Handle additional_context
            if (isset($data['additional_context'])) {
                $sanitized[$post_type_clean]['additional_context'] = sanitize_textarea_field($data['additional_context']);
            }
            
            // Handle fields
            if (isset($data['fields']) && is_array($data['fields'])) {
                $sanitized[$post_type_clean]['fields'] = array();
                foreach ($data['fields'] as $field_key => $field_data) {
                    $field_key_clean = sanitize_key($field_key);
                    $sanitized[$post_type_clean]['fields'][$field_key_clean] = array(
                        'description' => isset($field_data['description']) ? sanitize_textarea_field($field_data['description']) : '',
                        'word_count' => isset($field_data['word_count']) ? absint($field_data['word_count']) : 0,
                        'enabled' => isset($field_data['enabled']) ? (bool) $field_data['enabled'] : false,
                        'width' => isset($field_data['width']) ? absint($field_data['width']) : 0,
                        'height' => isset($field_data['height']) ? absint($field_data['height']) : 0
                    );
                }
            }
        }
        
        // Return as JSON string
        return wp_json_encode($sanitized);
    }
    
    /**
     * Register post meta fields for content generation
     * Registers for all post types that have been configured in the plugin
     */
    public function register_post_meta() {
        // Get all configured post types from CPT configs
        $cpt_configs = $this->get_cpt_configs();
        $post_types_to_register = array();
        
        // Add all post types that have configs
        if (!empty($cpt_configs)) {
            $post_types_to_register = array_keys($cpt_configs);
        }
        
        // Always include the currently selected post type
        $selected_post_type = get_option('keycontentai_selected_post_type', 'post');
        if (!in_array($selected_post_type, $post_types_to_register)) {
            $post_types_to_register[] = $selected_post_type;
        }
        
        // If no post types yet, at least register for 'post'
        if (empty($post_types_to_register)) {
            $post_types_to_register = array('post');
        }
        
        // Register meta fields for all relevant post types
        foreach ($post_types_to_register as $post_type) {
            // Register additional context field (per-post specific context)
            register_post_meta($post_type, 'keycontentai_additional_context', array(
                'type' => 'string',
                'description' => 'Post-specific additional context for AI content generation',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_textarea_field',
                'auth_callback' => function() {
                    return current_user_can('edit_posts');
                }
            ));
            
            // Register last generation timestamp field
            register_post_meta($post_type, 'keycontentai_last_generation', array(
                'type' => 'string',
                'description' => 'Timestamp of last AI content generation',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => function() {
                    return current_user_can('edit_posts');
                }
            ));
            
            // Register keyword field (the keyword used for generation)
            register_post_meta($post_type, 'keycontentai_keyword', array(
                'type' => 'string',
                'description' => 'Keyword used for AI content generation',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => function() {
                    return current_user_can('edit_posts');
                }
            ));
        }
    }
    
    /**
     * Render Settings page with tabs
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include KEYCONTENTAI_PLUGIN_DIR . 'admin/settings/index.php';
    }
    
    /**
     * Render Generation page
     */
    public function render_generation_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include KEYCONTENTAI_PLUGIN_DIR . 'admin/generation/index.php';
    }
    
    /**
     * Render Internal Linking page
     */
    public function render_internal_linking_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include KEYCONTENTAI_PLUGIN_DIR . 'admin/internal-linking/index.php';
    }
    
    /**
     * Render Load Keywords page
     */
    public function render_load_keywords_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include KEYCONTENTAI_PLUGIN_DIR . 'admin/load-keywords/index.php';
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if ($hook !== 'toplevel_page_keycontentai-load-keywords' 
            && $hook !== 'keycontentai_page_keycontentai-generation'
            && $hook !== 'keycontentai_page_keycontentai-internal-linking'
            && $hook !== 'keycontentai_page_keycontentai-load-keywords'
            && $hook !== 'keycontentai_page_keycontentai-settings') {
            return;
        }
        
        // Settings page assets
        if ($hook === 'keycontentai_page_keycontentai-settings') {
            wp_enqueue_style(
                'keycontentai-settings',
                KEYCONTENTAI_PLUGIN_URL . 'admin/settings/assets/settings.css',
                array(),
                KEYCONTENTAI_VERSION
            );
            
            wp_enqueue_script(
                'keycontentai-settings',
                KEYCONTENTAI_PLUGIN_URL . 'admin/settings/assets/settings.js',
                array('jquery'),
                KEYCONTENTAI_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('keycontentai-settings', 'keycontentaiSettings', array(
                'nonce' => wp_create_nonce('keycontentai_settings_nonce')
            ));
        }
        
        // Generation page assets
        if ($hook === 'keycontentai_page_keycontentai-generation') {
            wp_enqueue_style(
                'keycontentai-generation',
                KEYCONTENTAI_PLUGIN_URL . 'admin/generation/assets/generation.css',
                array(),
                KEYCONTENTAI_VERSION
            );
            
            wp_enqueue_style(
                'keycontentai-generation-debug',
                KEYCONTENTAI_PLUGIN_URL . 'admin/generation/assets/debug.css',
                array('keycontentai-generation'),
                KEYCONTENTAI_VERSION
            );
            
            wp_enqueue_script(
                'keycontentai-generation-debug',
                KEYCONTENTAI_PLUGIN_URL . 'admin/generation/assets/debug.js',
                array('jquery'),
                KEYCONTENTAI_VERSION,
                true
            );
            
            wp_enqueue_script(
                'keycontentai-generation',
                KEYCONTENTAI_PLUGIN_URL . 'admin/generation/assets/generation.js',
                array('jquery', 'keycontentai-generation-debug'),
                KEYCONTENTAI_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('keycontentai-generation', 'keycontentaiGeneration', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('keycontentai_generation_nonce')
            ));
        }
        
        // Internal Linking page assets
        if ($hook === 'keycontentai_page_keycontentai-internal-linking') {
            wp_enqueue_style(
                'keycontentai-internal-linking',
                KEYCONTENTAI_PLUGIN_URL . 'admin/internal-linking/assets/internal-linking.css',
                array(),
                KEYCONTENTAI_VERSION
            );
            
            wp_enqueue_script(
                'keycontentai-internal-linking',
                KEYCONTENTAI_PLUGIN_URL . 'admin/internal-linking/assets/internal-linking.js',
                array('jquery'),
                KEYCONTENTAI_VERSION,
                true
            );
        }
        
        // Load Keywords page assets
        if ($hook === 'toplevel_page_keycontentai-load-keywords' || $hook === 'keycontentai_page_keycontentai-load-keywords') {
            wp_enqueue_style(
                'keycontentai-load-keywords',
                KEYCONTENTAI_PLUGIN_URL . 'admin/load-keywords/assets/load-keywords.css',
                array(),
                KEYCONTENTAI_VERSION
            );
            
            wp_enqueue_script(
                'keycontentai-load-keywords',
                KEYCONTENTAI_PLUGIN_URL . 'admin/load-keywords/assets/load-keywords.js',
                array('jquery'),
                KEYCONTENTAI_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('keycontentai-load-keywords', 'keycontentaiLoadKeywords', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('keycontentai_generate'),
                'confirmClear' => __('Are you sure you want to clear all keywords?', 'keycontentai'),
                'confirmStop' => __('Are you sure you want to stop the process?', 'keycontentai'),
                'noKeywords' => __('Please enter at least one keyword.', 'keycontentai'),
                'starting' => __('Starting post creation...', 'keycontentai'),
                'found' => __('Found', 'keycontentai'),
                'keywordsToProcess' => __('keyword(s) to process', 'keycontentai'),
                'processing' => __('Processing keyword', 'keycontentai'),
                'error' => __('Error:', 'keycontentai'),
                'allProcessed' => __('All keywords processed!', 'keycontentai'),
                'stoppedByUser' => __('Process stopped by user', 'keycontentai'),
                'stopping' => __('Stopping process...', 'keycontentai'),
                'logEmpty' => __('Activity log is empty. Enter keywords and click "Generate Posts" to start.', 'keycontentai')
            ));
        }
    }
    
    /**
     * AJAX handler for content generation
     */
    public function ajax_generate_content() {
        // Security check
        check_ajax_referer('keycontentai_generation_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'keycontentai')
            ));
            return;
        }
        
        // Get post_id from request
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (empty($post_id)) {
            wp_send_json_error(array(
                'message' => __('No post ID provided', 'keycontentai')
            ));
            return;
        }
        
        // Generate content for existing post
        $generator = new KeyContentAI_Content_Generator();
        $result = $generator->generate_content($post_id);
        
        // Return response with debug log
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Content generated successfully', 'keycontentai'),
                'debug_log' => isset($result['debug_log']) ? $result['debug_log'] : array()
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message'],
                'debug_log' => isset($result['debug_log']) ? $result['debug_log'] : array()
            ));
        }
    }
    
    /**
     * AJAX handler to fetch available OpenAI models
     */
    public function ajax_fetch_models() {
        // Check nonce
        check_ajax_referer('keycontentai_settings_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        // Get API key from POST or settings
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : get_option('keycontentai_openai_api_key', '');
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API key is required'));
            return;
        }
        
        try {
            // Fetch models
            $models = KeyContentAI_OpenAI_API_Caller::get_available_models($api_key);
            
            // Format all models for both dropdowns
            $all_models = array();
            
            foreach ($models as $model) {
                $model_id = $model['id'];
                $all_models[] = array(
                    'id' => $model_id,
                    'name' => $model_id
                );
            }
            
            wp_send_json_success(array(
                'text_models' => $all_models,
                'image_models' => $all_models
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler to load a keyword and create a post
     */
    public function ajax_load_keyword() {
        // Security check
        check_ajax_referer('keycontentai_generate', 'nonce');
        
        // Get keyword from request
        if (!isset($_POST['keyword']) || empty($_POST['keyword'])) {
            wp_send_json_error(array(
                'message' => __('No keyword provided', 'keycontentai')
            ));
        }
        
        $keyword = sanitize_text_field($_POST['keyword']);
        $debug_mode = isset($_POST['debug']) && $_POST['debug'] === '1';
        $auto_publish = isset($_POST['auto_publish']) && $_POST['auto_publish'] === '1';
        
        // Load keyword using the keyword loader class
        $loader = new KeyContentAI_Keyword_Loader();
        $result = $loader->load_keyword($keyword, $debug_mode, $auto_publish);
        
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
}

/**
 * Initialize the plugin
 */
function keycontentai_init() {
    global $keycontentai;
    $keycontentai = KeyContentAI::get_instance();
    return $keycontentai;
}

// Start the plugin
add_action('plugins_loaded', 'keycontentai_init');
