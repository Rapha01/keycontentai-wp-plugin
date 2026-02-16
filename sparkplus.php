<?php
/**
 * Plugin Name: SparkPlus
 * Description: Creates and populates custom post types with AI-generated content based on user keywords.
 * Version: 1.0.0
 * Author: olympagency
 * Author URI: https://olympagency.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sparkplus
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SPARKPLUS_VERSION', '1.0.0');
define('SPARKPLUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPARKPLUS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPARKPLUS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/*
 * ─── Plugin Settings Reference ─────────────────────────────────────
 *
 * All settings are saved via AJAX (see SparkPlus_Admin_Ajax_Handler::save_settings()).
 * Sanitization is handled in that method and in SparkPlus_Sanitizer.
 *
 * API Settings (tab: api-settings)
 *   sparkplus_openai_api_key    string   OpenAI API key
 *   sparkplus_text_model        string   Text generation model (default: 'gpt-5.2')
 *   sparkplus_image_model       string   Image generation model (default: 'gpt-image-1.5')
 *
 * General Context (tab: general-context)
 *   sparkplus_addressing        string   Formal/informal tone (default: 'formal')
 *   sparkplus_company_name      string   Company or brand name
 *   sparkplus_industry          string   Business industry/sector
 *   sparkplus_target_group      string   Target audience description
 *   sparkplus_usp               text     Unique selling proposition
 *   sparkplus_advantages        text     Product advantages
 *   sparkplus_buying_reasons    text     Reasons for buying
 *   sparkplus_additional_context text    Additional brand context
 *   sparkplus_wysiwyg_formatting array   Allowed HTML elements (paragraphs, bold, italic, headings, lists, links)
 *
 * CPT Settings (tab: cpt)
 *   sparkplus_selected_post_type string  Currently selected post type (default: 'post')
 *   sparkplus_cpt_configs        json    Per-post-type field configs and additional context
 *
 * Reset (tab: reset)
 *   Deletes all options matching sparkplus_% and all post meta matching sparkplus_%
 *   See SparkPlus_Admin_Ajax_Handler::reset_settings()
 * ───────────────────────────────────────────────────────────────────
 */

/**
 * Main SparkPlus Class
 */
class SparkPlus {
    
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
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load utility functions
        require_once SPARKPLUS_PLUGIN_DIR . 'includes/util.php';
        
        // Load sanitization functions
        require_once SPARKPLUS_PLUGIN_DIR . 'includes/sanitizer.php';
        
        // Load prompt builder class
        require_once SPARKPLUS_PLUGIN_DIR . 'includes/prompt-builder.php';
        
        // Load OpenAI API caller class
        require_once SPARKPLUS_PLUGIN_DIR . 'includes/openai-api-caller.php';
        
        // Load content generator class
        require_once SPARKPLUS_PLUGIN_DIR . 'includes/content-generator.php';
        
        // Load keyword loader class
        require_once SPARKPLUS_PLUGIN_DIR . 'includes/keyword-loader.php';
        
        // Load admin components (only in admin)
        if (is_admin()) {
            require_once SPARKPLUS_PLUGIN_DIR . 'admin/edit-meta-box.php';
            require_once SPARKPLUS_PLUGIN_DIR . 'includes/admin-ajax-handler.php';
            
            // Initialize AJAX handler
            new SparkPlus_Admin_Ajax_Handler();
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add top-level menu
        add_menu_page(
            __('SparkPlus', 'sparkplus'),           // Page title
            __('SparkPlus', 'sparkplus'),           // Menu title
            'manage_options',                              // Capability
            'sparkplus-load-keywords',                  // Menu slug
            array($this, 'render_load_keywords_page'),    // Callback
            'dashicons-admin-post',                        // Icon
            30                                             // Position
        );
        
        // Add submenu items
        add_submenu_page(
            'sparkplus-load-keywords',                  // Parent slug
            __('Load Keywords', 'sparkplus'),          // Page title
            __('Load Keywords', 'sparkplus'),          // Menu title
            'manage_options',                              // Capability
            'sparkplus-load-keywords',                  // Menu slug (same as parent for first item)
            array($this, 'render_load_keywords_page')     // Callback
        );

        add_submenu_page(
            'sparkplus-load-keywords',                  // Parent slug
            __('Generation', 'sparkplus'),             // Page title
            __('Generation', 'sparkplus'),             // Menu title
            'manage_options',                              // Capability
            'sparkplus-generation',                     // Menu slug
            array($this, 'render_generation_page')        // Callback
        );
        
        add_submenu_page(
            'sparkplus-load-keywords',                  // Parent slug
            __('Internal Linking', 'sparkplus'),       // Page title
            __('Internal Linking', 'sparkplus'),       // Menu title
            'manage_options',                              // Capability
            'sparkplus-internal-linking',               // Menu slug
            array($this, 'render_internal_linking_page')  // Callback
        );
        
        add_submenu_page(
            'sparkplus-load-keywords',                  // Parent slug
            __('Settings', 'sparkplus'),               // Page title
            __('Settings', 'sparkplus'),               // Menu title
            'manage_options',                              // Capability
            'sparkplus-settings',                       // Menu slug
            array($this, 'render_settings_page')          // Callback
        );
    }
    
    /**
     * Get CPT configs (reads from JSON format)
     */
    public function get_cpt_configs() {
        $json = get_option('sparkplus_cpt_configs', '');
        
        if (empty($json)) {
            return array();
        }
        
        $data = json_decode($json, true);
        return is_array($data) ? $data : array();
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
        $selected_post_type = get_option('sparkplus_selected_post_type', 'post');
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
            register_post_meta($post_type, 'sparkplus_additional_context', array(
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
            register_post_meta($post_type, 'sparkplus_last_generation', array(
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
            register_post_meta($post_type, 'sparkplus_keyword', array(
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
        
        include SPARKPLUS_PLUGIN_DIR . 'admin/settings/index.php';
    }
    
    /**
     * Render Generation page
     */
    public function render_generation_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include SPARKPLUS_PLUGIN_DIR . 'admin/generation/index.php';
    }
    
    /**
     * Render Internal Linking page
     */
    public function render_internal_linking_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include SPARKPLUS_PLUGIN_DIR . 'admin/internal-linking/index.php';
    }
    
    /**
     * Render Load Keywords page
     */
    public function render_load_keywords_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        include SPARKPLUS_PLUGIN_DIR . 'admin/load-keywords/index.php';
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if ($hook !== 'toplevel_page_sparkplus-load-keywords' 
            && $hook !== 'sparkplus_page_sparkplus-generation'
            && $hook !== 'sparkplus_page_sparkplus-internal-linking'
            && $hook !== 'sparkplus_page_sparkplus-load-keywords'
            && $hook !== 'sparkplus_page_sparkplus-settings') {
            return;
        }
        
        // Settings page assets
        if ($hook === 'sparkplus_page_sparkplus-settings') {
            wp_enqueue_style(
                'sparkplus-settings',
                SPARKPLUS_PLUGIN_URL . 'admin/settings/assets/settings.css',
                array(),
                SPARKPLUS_VERSION
            );
            
            wp_enqueue_script(
                'sparkplus-settings',
                SPARKPLUS_PLUGIN_URL . 'admin/settings/assets/settings.js',
                array('jquery'),
                SPARKPLUS_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('sparkplus-settings', 'sparkplusSettings', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('sparkplus_settings_nonce'),
                'saving'  => __('Saving...', 'sparkplus'),
                'saved'   => __('Saved!', 'sparkplus'),
                'error'   => __('Error saving settings', 'sparkplus'),
            ));
        }
        
        // Generation page assets
        if ($hook === 'sparkplus_page_sparkplus-generation') {
            wp_enqueue_style(
                'sparkplus-generation',
                SPARKPLUS_PLUGIN_URL . 'admin/generation/assets/generation.css',
                array(),
                SPARKPLUS_VERSION
            );
            
            wp_enqueue_style(
                'sparkplus-generation-debug',
                SPARKPLUS_PLUGIN_URL . 'admin/generation/assets/debug.css',
                array('sparkplus-generation'),
                SPARKPLUS_VERSION
            );
            
            wp_enqueue_script(
                'sparkplus-generation-debug',
                SPARKPLUS_PLUGIN_URL . 'admin/generation/assets/debug.js',
                array('jquery'),
                SPARKPLUS_VERSION,
                true
            );
            
            wp_enqueue_script(
                'sparkplus-generation',
                SPARKPLUS_PLUGIN_URL . 'admin/generation/assets/generation.js',
                array('jquery', 'sparkplus-generation-debug'),
                SPARKPLUS_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('sparkplus-generation', 'sparkplusGeneration', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sparkplus_nonce'),
                'deleting' => __('Deleting...', 'sparkplus'),
                'deleteConfirm' => __('Delete', 'sparkplus')
            ));
        }
        
        // Internal Linking page assets
        if ($hook === 'sparkplus_page_sparkplus-internal-linking') {
            wp_enqueue_style(
                'sparkplus-internal-linking',
                SPARKPLUS_PLUGIN_URL . 'admin/internal-linking/assets/internal-linking.css',
                array(),
                SPARKPLUS_VERSION
            );
            
            wp_enqueue_script(
                'sparkplus-internal-linking',
                SPARKPLUS_PLUGIN_URL . 'admin/internal-linking/assets/internal-linking.js',
                array('jquery'),
                SPARKPLUS_VERSION,
                true
            );
        }
        
        // Load Keywords page assets
        if ($hook === 'toplevel_page_sparkplus-load-keywords' || $hook === 'sparkplus_page_sparkplus-load-keywords') {
            wp_enqueue_style(
                'sparkplus-load-keywords',
                SPARKPLUS_PLUGIN_URL . 'admin/load-keywords/assets/load-keywords.css',
                array(),
                SPARKPLUS_VERSION
            );
            
            wp_enqueue_script(
                'sparkplus-load-keywords',
                SPARKPLUS_PLUGIN_URL . 'admin/load-keywords/assets/load-keywords.js',
                array('jquery'),
                SPARKPLUS_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('sparkplus-load-keywords', 'sparkplusLoadKeywords', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sparkplus_nonce'),
                'confirmClear' => __('Are you sure you want to clear all keywords?', 'sparkplus'),
                'confirmStop' => __('Are you sure you want to stop the process?', 'sparkplus'),
                'noKeywords' => __('Please enter at least one keyword.', 'sparkplus')
            ));
        }
    }
}

/**
 * Initialize the plugin
 */
function sparkplus_init() {
    global $sparkplus;
    $sparkplus = SparkPlus::get_instance();
    return $sparkplus;
}

// Start the plugin
add_action('plugins_loaded', 'sparkplus_init');
