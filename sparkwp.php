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
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load utility functions
        require_once SPARKWP_PLUGIN_DIR . 'includes/util.php';
        
        // Load sanitization functions
        require_once SPARKWP_PLUGIN_DIR . 'includes/sanitizer.php';
        
        // Load prompt builder class
        require_once SPARKWP_PLUGIN_DIR . 'includes/prompt-builder.php';
        
        // Load OpenAI API caller class
        require_once SPARKWP_PLUGIN_DIR . 'includes/openai-api-caller.php';
        
        // Load content generator class
        require_once SPARKWP_PLUGIN_DIR . 'includes/content-generator.php';
        
        // Load keyword loader class
        require_once SPARKWP_PLUGIN_DIR . 'includes/keyword-loader.php';
        
        // Load admin components (only in admin)
        if (is_admin()) {
            require_once SPARKWP_PLUGIN_DIR . 'admin/edit-meta-box.php';
            require_once SPARKWP_PLUGIN_DIR . 'includes/admin-ajax-handler.php';
            
            // Initialize AJAX handler
            new SparkWP_Admin_Ajax_Handler();
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
        
        // General Context Settings
        register_setting('sparkwp_general_context_settings', 'sparkwp_addressing', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'formal'
        ));
        
        register_setting('sparkwp_general_context_settings', 'sparkwp_company_name', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_general_context_settings', 'sparkwp_industry', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_general_context_settings', 'sparkwp_target_group', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_general_context_settings', 'sparkwp_usp', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_general_context_settings', 'sparkwp_advantages', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_general_context_settings', 'sparkwp_buying_reasons', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_general_context_settings', 'sparkwp_additional_context', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('sparkwp_general_context_settings', 'sparkwp_wysiwyg_formatting', array(
            'type' => 'array',
            'sanitize_callback' => 'SparkWP_Sanitizer::wysiwyg_formatting',
            'default' => array(
                'paragraphs' => true,
                'bold' => true,
                'italic' => true,
                'headings' => true,
                'lists' => true,
                'links' => true
            )
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
            'sanitize_callback' => 'SparkWP_Sanitizer::cpt_configs',
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
