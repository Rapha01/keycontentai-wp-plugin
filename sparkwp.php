<?php
/**
 * Plugin Name: SparkWP
 * Plugin URI: https://sparkwp.io
 * Description: Creates and populates custom post types with AI-generated content based on user keywords.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://sparkwp.io
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sparkwp
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SPARKWP_VERSION', '1.0.0');
define('SPARKWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPARKWP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPARKWP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main SparkWP Class
 */
class SparkWP {
    
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
            add_action('wp_ajax_sparkwp_generate_content', array($this, 'ajax_generate_content'));
            add_action('wp_ajax_sparkwp_load_keyword', array($this, 'ajax_load_keyword'));
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load utility functions
        require_once SPARKWP_PLUGIN_DIR . 'includes/util.php';
        
        // Load prompt builder class
        require_once SPARKWP_PLUGIN_DIR . 'includes/prompt-builder.php';
        
        // Load OpenAI API caller class
        require_once SPARKWP_PLUGIN_DIR . 'includes/openai-api-caller.php';
        
        // Load content generator class
        require_once SPARKWP_PLUGIN_DIR . 'includes/content-generator.php';
        
        // Load keyword loader class
        require_once SPARKWP_PLUGIN_DIR . 'includes/keyword-loader.php';
        
        // Load admin meta box (only in admin)
        if (is_admin()) {
            require_once SPARKWP_PLUGIN_DIR . 'admin/edit-meta-box.php';
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add top-level menu
        add_menu_page(
            __('SparkWP', 'sparkwp'),           // Page title
            __('SparkWP', 'sparkwp'),           // Menu title
            'manage_options',                              // Capability
            'sparkwp-load-keywords',                  // Menu slug
            array($this, 'render_load_keywords_page'),    // Callback
            'dashicons-admin-post',                        // Icon
            30                                             // Position
        );
        
        // Add submenu items
        add_submenu_page(
            'sparkwp-load-keywords',                  // Parent slug
            __('Load Keywords', 'sparkwp'),          // Page title
            __('Load Keywords', 'sparkwp'),          // Menu title
            'manage_options',                              // Capability
            'sparkwp-load-keywords',                  // Menu slug (same as parent for first item)
            array($this, 'render_load_keywords_page')     // Callback
        );

        add_submenu_page(
            'sparkwp-load-keywords',                  // Parent slug
            __('Generation', 'sparkwp'),             // Page title
            __('Generation', 'sparkwp'),             // Menu title
            'manage_options',                              // Capability
            'sparkwp-generation',                     // Menu slug
            array($this, 'render_generation_page')        // Callback
        );
        
        add_submenu_page(
            'sparkwp-load-keywords',                  // Parent slug
            __('Internal Linking', 'sparkwp'),       // Page title
            __('Internal Linking', 'sparkwp'),       // Menu title
            'manage_options',                              // Capability
            'sparkwp-internal-linking',               // Menu slug
            array($this, 'render_internal_linking_page')  // Callback
        );
        
        add_submenu_page(
            'sparkwp-load-keywords',                  // Parent slug
            __('Settings', 'sparkwp'),               // Page title
            __('Settings', 'sparkwp'),               // Menu title
            'manage_options',                              // Capability
            'sparkwp-settings',                       // Menu slug
            array($this, 'render_settings_page')          // Callback
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // API Settings
        register_setting('sparkwp_api_settings', 'sparkwp_openai_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_api_settings', 'sparkwp_text_model', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'gpt-5.2'
        ));
        
        register_setting('sparkwp_api_settings', 'sparkwp_image_model', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'gpt-image-1.5'
        ));
        
        // Client Settings
        register_setting('sparkwp_client_settings', 'sparkwp_addressing', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'formal'
        ));
        
        register_setting('sparkwp_client_settings', 'sparkwp_company_name', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_client_settings', 'sparkwp_industry', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_client_settings', 'sparkwp_target_group', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_client_settings', 'sparkwp_usp', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_client_settings', 'sparkwp_advantages', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_client_settings', 'sparkwp_buying_reasons', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_client_settings', 'sparkwp_additional_context', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        // CPT Settings
        register_setting('sparkwp_cpt_settings', 'sparkwp_selected_post_type', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'post'
        ));
        
        // CPT Configurations (consolidated) - Stored as JSON
        // Structure: array('post_type' => array('additional_context' => '', 'fields' => array('field_key' => array(...))))
        register_setting('sparkwp_cpt_settings', 'sparkwp_cpt_configs', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_cpt_configs'),
            'default' => ''
        ));
    }
    
    /**
     * Get CPT configs (reads from JSON format)
     */
    public function get_cpt_configs() {
        $json = get_option('sparkwp_cpt_configs', '');
        
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
                        'size' => isset($field_data['size']) ? sanitize_text_field($field_data['size']) : 'auto',
                        'quality' => isset($field_data['quality']) ? sanitize_text_field($field_data['quality']) : 'auto'
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
        $selected_post_type = get_option('sparkwp_selected_post_type', 'post');
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
            register_post_meta($post_type, 'sparkwp_additional_context', array(
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
            register_post_meta($post_type, 'sparkwp_last_generation', array(
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
            register_post_meta($post_type, 'sparkwp_keyword', array(
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
        
        include SPARKWP_PLUGIN_DIR . 'admin/settings/index.php';
    }
    
    /**
     * Render Generation page
     */
    public function render_generation_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include SPARKWP_PLUGIN_DIR . 'admin/generation/index.php';
    }
    
    /**
     * Render Internal Linking page
     */
    public function render_internal_linking_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include SPARKWP_PLUGIN_DIR . 'admin/internal-linking/index.php';
    }
    
    /**
     * Render Load Keywords page
     */
    public function render_load_keywords_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include SPARKWP_PLUGIN_DIR . 'admin/load-keywords/index.php';
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if ($hook !== 'toplevel_page_sparkwp-load-keywords' 
            && $hook !== 'sparkwp_page_sparkwp-generation'
            && $hook !== 'sparkwp_page_sparkwp-internal-linking'
            && $hook !== 'sparkwp_page_sparkwp-load-keywords'
            && $hook !== 'sparkwp_page_sparkwp-settings') {
            return;
        }
        
        // Settings page assets
        if ($hook === 'sparkwp_page_sparkwp-settings') {
            wp_enqueue_style(
                'sparkwp-settings',
                SPARKWP_PLUGIN_URL . 'admin/settings/assets/settings.css',
                array(),
                SPARKWP_VERSION
            );
            
            wp_enqueue_script(
                'sparkwp-settings',
                SPARKWP_PLUGIN_URL . 'admin/settings/assets/settings.js',
                array('jquery'),
                SPARKWP_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('sparkwp-settings', 'sparkwpSettings', array(
                'nonce' => wp_create_nonce('sparkwp_settings_nonce')
            ));
        }
        
        // Generation page assets
        if ($hook === 'sparkwp_page_sparkwp-generation') {
            wp_enqueue_style(
                'sparkwp-generation',
                SPARKWP_PLUGIN_URL . 'admin/generation/assets/generation.css',
                array(),
                SPARKWP_VERSION
            );
            
            wp_enqueue_style(
                'sparkwp-generation-debug',
                SPARKWP_PLUGIN_URL . 'admin/generation/assets/debug.css',
                array('sparkwp-generation'),
                SPARKWP_VERSION
            );
            
            wp_enqueue_script(
                'sparkwp-generation-debug',
                SPARKWP_PLUGIN_URL . 'admin/generation/assets/debug.js',
                array('jquery'),
                SPARKWP_VERSION,
                true
            );
            
            wp_enqueue_script(
                'sparkwp-generation',
                SPARKWP_PLUGIN_URL . 'admin/generation/assets/generation.js',
                array('jquery', 'sparkwp-generation-debug'),
                SPARKWP_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('sparkwp-generation', 'sparkwpGeneration', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sparkwp_nonce')
            ));
        }
        
        // Internal Linking page assets
        if ($hook === 'sparkwp_page_sparkwp-internal-linking') {
            wp_enqueue_style(
                'sparkwp-internal-linking',
                SPARKWP_PLUGIN_URL . 'admin/internal-linking/assets/internal-linking.css',
                array(),
                SPARKWP_VERSION
            );
            
            wp_enqueue_script(
                'sparkwp-internal-linking',
                SPARKWP_PLUGIN_URL . 'admin/internal-linking/assets/internal-linking.js',
                array('jquery'),
                SPARKWP_VERSION,
                true
            );
        }
        
        // Load Keywords page assets
        if ($hook === 'toplevel_page_sparkwp-load-keywords' || $hook === 'sparkwp_page_sparkwp-load-keywords') {
            wp_enqueue_style(
                'sparkwp-load-keywords',
                SPARKWP_PLUGIN_URL . 'admin/load-keywords/assets/load-keywords.css',
                array(),
                SPARKWP_VERSION
            );
            
            wp_enqueue_script(
                'sparkwp-load-keywords',
                SPARKWP_PLUGIN_URL . 'admin/load-keywords/assets/load-keywords.js',
                array('jquery'),
                SPARKWP_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('sparkwp-load-keywords', 'sparkwpLoadKeywords', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sparkwp_nonce'),
                'confirmClear' => __('Are you sure you want to clear all keywords?', 'sparkwp'),
                'confirmStop' => __('Are you sure you want to stop the process?', 'sparkwp'),
                'noKeywords' => __('Please enter at least one keyword.', 'sparkwp')
            ));
        }
    }
    
    /**
     * AJAX handler for content generation
     */
    public function ajax_generate_content() {
        // Security check
        check_ajax_referer('sparkwp_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized', 'sparkwp')
            ));
            return;
        }
        
        // Get post_id from request
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (empty($post_id)) {
            wp_send_json_error(array(
                'message' => __('No post ID provided', 'sparkwp')
            ));
            return;
        }
        
        // Generate content for existing post
        $generator = new SparkWP_Content_Generator();
        $result = $generator->generate_content($post_id);
        
        // Return response with debug log
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Content generated successfully', 'sparkwp'),
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
     * AJAX handler to load a keyword and create a post
     */
    public function ajax_load_keyword() {
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
}

/**
 * Initialize the plugin
 */
function sparkwp_init() {
    global $sparkwp;
    $sparkwp = SparkWP::get_instance();
    return $sparkwp;
}

// Start the plugin
add_action('plugins_loaded', 'sparkwp_init');
