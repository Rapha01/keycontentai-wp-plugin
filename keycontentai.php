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
        
        // Initialize admin
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            
            // AJAX handlers
            add_action('wp_ajax_keycontentai_generate_content', array($this, 'ajax_generate_content'));
            add_action('wp_ajax_keycontentai_fetch_models', array($this, 'ajax_fetch_models'));
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load prompt builder class
        require_once KEYCONTENTAI_PLUGIN_DIR . 'includes/prompt-builder.php';
        
        // Load OpenAI API caller class
        require_once KEYCONTENTAI_PLUGIN_DIR . 'includes/openai-api-caller.php';
        
        // Load content generator class
        require_once KEYCONTENTAI_PLUGIN_DIR . 'includes/content-generator.php';
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
            'keycontentai-create',                         // Menu slug
            array($this, 'render_create_page'),           // Callback
            'dashicons-admin-post',                        // Icon
            30                                             // Position
        );
        
        // Add submenu items
        add_submenu_page(
            'keycontentai-create',                         // Parent slug
            __('Create', 'keycontentai'),                 // Page title
            __('Create', 'keycontentai'),                 // Menu title
            'manage_options',                              // Capability
            'keycontentai-create',                         // Menu slug (same as parent for first item)
            array($this, 'render_create_page')            // Callback
        );

        add_submenu_page(
            'keycontentai-create',                         // Parent slug
            __('Edit', 'keycontentai'),                   // Page title
            __('Edit', 'keycontentai'),                   // Menu title
            'manage_options',                              // Capability
            'keycontentai-edit',                           // Menu slug
            array($this, 'render_edit_page')              // Callback
        );

        add_submenu_page(
            'keycontentai-create',                         // Parent slug
            __('Competition', 'keycontentai'),            // Page title
            __('Competition', 'keycontentai'),            // Menu title
            'manage_options',                              // Capability
            'keycontentai-competition',                    // Menu slug
            array($this, 'render_competition_page')       // Callback
        );
        
        add_submenu_page(
            'keycontentai-create',                         // Parent slug
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
        register_setting('keycontentai_client_settings', 'keycontentai_language', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'de'
        ));
        
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
        
        // Competition Settings
        register_setting('keycontentai_competition_settings', 'keycontentai_competition_urls', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_competition_urls'),
            'default' => array()
        ));
        
        // CPT Settings
        register_setting('keycontentai_cpt_settings', 'keycontentai_selected_post_type', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'post'
        ));
        
        // Custom Field Configurations (per post type)
        // Structure: array('post_type' => array('field_key' => array('description' => '', 'word_count' => 0, 'enabled' => true)))
        register_setting('keycontentai_cpt_settings', 'keycontentai_field_configs', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_field_configs'),
            'default' => array()
        ));
        
        // Additional Context per Post Type
        // Structure: array('post_type' => 'context text')
        register_setting('keycontentai_cpt_settings', 'keycontentai_cpt_additional_context', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_cpt_additional_context'),
            'default' => array()
        ));
    }
    
    /**
     * Sanitize field configurations
     */
    public function sanitize_field_configs($input) {
        if (!is_array($input)) {
            return array();
        }
        
        // Get existing field configs to preserve data for other post types
        $existing_configs = get_option('keycontentai_field_configs', array());
        
        // Start with existing data
        $sanitized = is_array($existing_configs) ? $existing_configs : array();
        
        // Update only the post types that are in the input
        foreach ($input as $post_type => $fields) {
            $post_type_clean = sanitize_key($post_type);
            $sanitized[$post_type_clean] = array();
            
            if (is_array($fields)) {
                foreach ($fields as $field_key => $field_data) {
                    $field_key_clean = sanitize_key($field_key);
                    $sanitized[$post_type_clean][$field_key_clean] = array(
                        'description' => isset($field_data['description']) ? sanitize_textarea_field($field_data['description']) : '',
                        'word_count' => isset($field_data['word_count']) ? absint($field_data['word_count']) : 0,
                        'enabled' => isset($field_data['enabled']) ? (bool) $field_data['enabled'] : false,
                        'width' => isset($field_data['width']) ? absint($field_data['width']) : 0,
                        'height' => isset($field_data['height']) ? absint($field_data['height']) : 0
                    );
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize CPT additional context
     */
    public function sanitize_cpt_additional_context($input) {
        if (!is_array($input)) {
            return array();
        }
        
        // Get existing context to preserve data for other post types
        $existing_context = get_option('keycontentai_cpt_additional_context', array());
        
        // Start with existing data
        $sanitized = is_array($existing_context) ? $existing_context : array();
        
        // Update only the post types that are in the input
        foreach ($input as $post_type => $context) {
            $post_type_clean = sanitize_key($post_type);
            $sanitized[$post_type_clean] = sanitize_textarea_field($context);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize competition URLs
     */
    public function sanitize_competition_urls($input) {
        if (!is_array($input)) {
            return array();
        }
        
        $sanitized = array();
        foreach ($input as $url) {
            $url = trim($url);
            if (!empty($url)) {
                $sanitized[] = esc_url_raw($url);
            }
        }
        
        return array_filter($sanitized);
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
     * Render Competition page with tabs
     */
    public function render_competition_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include KEYCONTENTAI_PLUGIN_DIR . 'admin/competition/index.php';
    }
    
    /**
     * Render Edit page
     */
    public function render_edit_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include KEYCONTENTAI_PLUGIN_DIR . 'admin/edit/index.php';
    }
    
    /**
     * Render Create page
     */
    public function render_create_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include KEYCONTENTAI_PLUGIN_DIR . 'admin/create/index.php';
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if ($hook !== 'toplevel_page_keycontentai-create' 
            && $hook !== 'keycontentai_page_keycontentai-edit'
            && $hook !== 'keycontentai_page_keycontentai-settings' 
            && $hook !== 'keycontentai_page_keycontentai-competition') {
            return;
        }
        
        // Settings page assets
        if ($hook === 'keycontentai_page_keycontentai-settings' || $hook === 'keycontentai_page_keycontentai-competition') {
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
        
        // Create page assets
        if ($hook === 'toplevel_page_keycontentai-create') {
            wp_enqueue_style(
                'keycontentai-create',
                KEYCONTENTAI_PLUGIN_URL . 'admin/create/assets/create.css',
                array(),
                KEYCONTENTAI_VERSION
            );
            
            wp_enqueue_script(
                'keycontentai-create',
                KEYCONTENTAI_PLUGIN_URL . 'admin/create/assets/create.js',
                array('jquery'),
                KEYCONTENTAI_VERSION,
                true
            );
            
            // Localize script for translations and AJAX
            wp_localize_script('keycontentai-create', 'keycontentaiCreate', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('keycontentai_generate'),
                'confirmClear' => __('Are you sure you want to clear all keywords?', 'keycontentai'),
                'confirmStop' => __('Are you sure you want to stop the generation process?', 'keycontentai'),
                'noKeywords' => __('Please enter at least one keyword.', 'keycontentai'),
                'starting' => __('Starting content generation...', 'keycontentai'),
                'found' => __('Found', 'keycontentai'),
                'keywordsToProcess' => __('keyword(s) to process', 'keycontentai'),
                'processing' => __('Processing keyword', 'keycontentai'),
                'placeholder' => __('Content generation placeholder - ready for implementation', 'keycontentai'),
                'error' => __('Error:', 'keycontentai'),
                'allProcessed' => __('All keywords processed!', 'keycontentai'),
                'generated' => __('Generated', 'keycontentai'),
                'postsSuccess' => __('post(s) successfully', 'keycontentai'),
                'stoppedByUser' => __('Process stopped by user', 'keycontentai'),
                'stopping' => __('Stopping process...', 'keycontentai'),
                'logEmpty' => __('Activity log is empty. Enter keywords and click "Generate Content" to start.', 'keycontentai')
            ));
        }
    }
    
    /**
     * AJAX handler for content generation
     */
    public function ajax_generate_content() {
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
        
        // Generate content using the content generator class
        $generator = new KeyContentAI_Content_Generator();
        $result = $generator->generate_content($keyword, $debug_mode);
        
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
}

/**
 * Initialize the plugin
 */
function keycontentai_init() {
    return KeyContentAI::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'keycontentai_init');
