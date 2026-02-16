<?php
/**
 * Sanitization Class
 * 
 * Handles all sanitization callbacks for plugin settings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SparkPlus_Sanitizer {
    
    /**
     * Sanitize CPT configurations
     * 
     * @param array|string $input Input data from the form
     * @return string JSON-encoded sanitized configurations
     */
    public static function cpt_configs($input) {
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
        global $sparkplus;
        $existing_configs = $sparkplus->get_cpt_configs();
        
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
     * Sanitize WYSIWYG formatting options
     * 
     * @param array|string $input Input data from the form
     * @return array Sanitized formatting options
     */
    public static function wysiwyg_formatting($input) {
        // Valid formatting options
        $valid_options = array('paragraphs', 'bold', 'italic', 'headings', 'lists', 'links');
        $sanitized = array();
        
        // If input is not an array, return default (only bold, italic, lists, paragraphs enabled)
        if (!is_array($input)) {
            $defaults = array('bold', 'italic', 'lists', 'paragraphs');
            foreach ($valid_options as $option) {
                $sanitized[$option] = in_array($option, $defaults);
            }
            return $sanitized;
        }
        
        // Process each valid option
        foreach ($valid_options as $option) {
            // Checkbox values: if checked, value is '1', if unchecked, key doesn't exist
            $sanitized[$option] = isset($input[$option]) && $input[$option] == '1';
        }
        
        return $sanitized;
    }
}
