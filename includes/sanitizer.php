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
                    'include_existing_content' => true,
                    'include_acf_instructions' => false,
                    'fields' => array()
                );
            }
            
            // Handle include_existing_content (defaults to true)
            if (isset($data['include_existing_content'])) {
                $sanitized[$post_type_clean]['include_existing_content'] = (bool) $data['include_existing_content'];
            } else {
                // If checkbox is not present in POST data, it means it's unchecked
                $sanitized[$post_type_clean]['include_existing_content'] = false;
            }

            // Handle include_acf_instructions (defaults to false)
            if (isset($data['include_acf_instructions'])) {
                $sanitized[$post_type_clean]['include_acf_instructions'] = (bool) $data['include_acf_instructions'];
            } else {
                $sanitized[$post_type_clean]['include_acf_instructions'] = false;
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
                    // Group field: detected by type=group or presence of sub_fields key
                    if ((isset($field_data['type']) && $field_data['type'] === 'group') || isset($field_data['sub_fields'])) {
                        $sanitized_group = array(
                            'type'       => 'group',
                            'enabled'    => isset($field_data['enabled']) ? (bool) $field_data['enabled'] : false,
                            'sub_fields' => array(),
                        );
                        if (isset($field_data['sub_fields']) && is_array($field_data['sub_fields'])) {
                            foreach ($field_data['sub_fields'] as $sub_key => $sub_data) {
                                $sub_key_clean = sanitize_key($sub_key);
                                $sanitized_group['sub_fields'][$sub_key_clean] = array(
                                    'description'  => isset($sub_data['description']) ? sanitize_textarea_field($sub_data['description']) : '',
                                    'word_count'   => isset($sub_data['word_count']) ? absint($sub_data['word_count']) : 0,
                                    'enabled'      => isset($sub_data['enabled']) ? (bool) $sub_data['enabled'] : false,
                                    'clear'        => isset($sub_data['clear']) ? (bool) $sub_data['clear'] : false,
                                    'size'         => isset($sub_data['size']) ? sanitize_text_field($sub_data['size']) : 'auto',
                                    'quality'      => isset($sub_data['quality']) ? sanitize_text_field($sub_data['quality']) : 'auto',
                                    'webp_quality' => isset($sub_data['webp_quality']) ? max(1, min(100, absint($sub_data['webp_quality']))) : 80,
                                );
                            }
                        }
                        $sanitized[$post_type_clean]['fields'][$field_key_clean] = $sanitized_group;
                    } else {
                        $sanitized[$post_type_clean]['fields'][$field_key_clean] = array(
                            'description'  => isset($field_data['description']) ? sanitize_textarea_field($field_data['description']) : '',
                            'word_count'   => isset($field_data['word_count']) ? absint($field_data['word_count']) : 0,
                            'enabled'      => isset($field_data['enabled']) ? (bool) $field_data['enabled'] : false,
                            'clear'        => isset($field_data['clear']) ? (bool) $field_data['clear'] : false,
                            'size'         => isset($field_data['size']) ? sanitize_text_field($field_data['size']) : 'auto',
                            'quality'      => isset($field_data['quality']) ? sanitize_text_field($field_data['quality']) : 'auto',
                            'webp_quality' => isset($field_data['webp_quality']) ? max(1, min(100, absint($field_data['webp_quality']))) : 80,
                        );
                    }
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
        $valid_options = array('paragraphs', 'bold', 'italic', 'headings', 'lists');
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
