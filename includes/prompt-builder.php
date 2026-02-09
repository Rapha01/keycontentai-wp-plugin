<?php
/**
 * Prompt Builder Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SparkWP_Prompt_Builder {
    private $debug_callback = null;
    
    /**
     * Constructor
     * 
     * @param callable $debug_callback Optional debug callback function
     */
    public function __construct($debug_callback = null) {
        $this->debug_callback = $debug_callback;
    }
    
    /**
     * Add debug entry via callback
     * 
     * @param string $step The step name/description
     * @param mixed $data The data to log
     */
    private function add_debug($step, $data) {
        if (is_callable($this->debug_callback)) {
            call_user_func($this->debug_callback, $step, $data);
        }
    }
    
    /**
     * Build the complete prompt for OpenAI
     * 
     * @param array $cpt_settings CPT-level settings (general + CPT-specific)
     * @param array $post_settings Post-specific settings (keyword, post context)
     * @param array $custom_fields Custom fields configuration
     * @return string The complete prompt
     */
    public function build_prompt($cpt_settings, $post_settings, $custom_fields) {
        $prompt_parts = array();
        
        // 1. System Role and Context
        $prompt_parts[] = $this->build_system_context($cpt_settings);
        
        // 2. Language Instruction
        $prompt_parts[] = $this->build_language_instruction($cpt_settings);
        
        // 3. Topic/Keyword
        $prompt_parts[] = $this->build_topic_section($post_settings);
        
        // 4. Company/General Context Information
        $prompt_parts[] = $this->build_general_context($cpt_settings);
        
        // 5. Target Audience
        $prompt_parts[] = $this->build_target_audience_section($cpt_settings);
        
        // 6. Post Type Specific Context
        $prompt_parts[] = $this->build_post_type_context($cpt_settings);
        
        // 7. Post-Specific Additional Context
        $prompt_parts[] = $this->build_post_specific_context($post_settings);
        
        // 8. WYSIWYG Formatting Rules (if applicable)
        $prompt_parts[] = $this->build_wysiwyg_formatting_rules($custom_fields);
        
        // 9. Custom Fields Instructions
        $prompt_parts[] = $this->build_custom_fields_instructions($custom_fields);
        
        // 10. Output Format Instructions
        $prompt_parts[] = $this->build_output_format_instructions($custom_fields);
        
        // 11. Final Instructions
        $prompt_parts[] = $this->build_final_instructions($cpt_settings);
        
        // Combine all parts with double line breaks
        return implode("\n\n\n", array_filter($prompt_parts));
    }
    
    /**
     * Build system context and role
     */
    private function build_system_context($settings) {
        return "You are an expert content writer and SEO specialist. Your task is to create high-quality, engaging, and SEO-optimized content for a professional website.";
    }
    
    /**
     * Build language instruction
     */
    private function build_language_instruction($settings) {
        if (empty($settings['language'])) {
            return '';
        }
        
        $language = $settings['language'];
        
        // Get language name from utility function
        $language_name = sparkwp_get_language_name($language);
        
        $instruction = "IMPORTANT: Write all content in {$language_name}.";
        // Add addressing style only for German
        if ($language === 'de' && !empty($settings['addressing'])) {
            if ($settings['addressing'] === 'formal') {
                $instruction .= " Use formal addressing (Sie) when speaking to the reader.";
            } else {
                $instruction .= " Use informal addressing (Du) when speaking to the reader.";
            }
        }
        
        return $instruction;
    }
    
    /**
     * Build topic/keyword section
     */
    private function build_topic_section($post_settings) {
        if (empty($post_settings['keyword'])) {
            return '';
        }
        
        return "TOPIC/KEYWORD: {$post_settings['keyword']}\nCreate comprehensive content about this topic.";
    }
    
    /**
     * Build general context/company context
     */
    private function build_general_context($settings) {
        $context_parts = array();
        
        $context_parts[] = "GENERAL CONTEXT:";
        
        if (!empty($settings['company_name'])) {
            $context_parts[] = "- Company: {$settings['company_name']}";
        }
        
        if (!empty($settings['industry'])) {
            $context_parts[] = "- Industry: {$settings['industry']}";
        }
        
        if (!empty($settings['usp'])) {
            $context_parts[] = "- Unique Selling Proposition: {$settings['usp']}";
        }
        
        if (!empty($settings['advantages'])) {
            $context_parts[] = "- Key Advantages: {$settings['advantages']}";
        }
        
        if (!empty($settings['buying_reasons'])) {
            $context_parts[] = "- Why Customers Choose Us: {$settings['buying_reasons']}";
        }
        
        if (!empty($settings['general_context_additional_context'])) {
            $context_parts[] = "- Additional Context: {$settings['general_context_additional_context']}";
        }
        
        return implode("\n", $context_parts);
    }
    
    /**
     * Build target audience section
     */
    private function build_target_audience_section($settings) {
        if (empty($settings['target_group'])) {
            return '';
        }
        
        return "TARGET AUDIENCE: {$settings['target_group']}\nTailor the content to resonate with this specific audience. Use language, examples, and references that appeal to them.";
    }
    
    /**
     * Build post type specific context
     */
    private function build_post_type_context($settings) {
        $context_parts = array();
        
        $post_type = $settings['post_type'];
        $context_parts[] = "POST TYPE: {$post_type}";
        
        // Check if there's post-type-specific additional context
        if (!empty($settings['cpt_additional_context'])) {
            $context_parts[] = "SPECIFIC INSTRUCTIONS FOR THIS POST TYPE:";
            $context_parts[] = $settings['cpt_additional_context'];
        }
        
        return implode("\n", $context_parts);
    }
    
    /**
     * Build post-specific additional context
     */
    private function build_post_specific_context($post_settings) {
        if (empty($post_settings['post_additional_context'])) {
            return '';
        }
        
        $context_parts = array();
        
        $context_parts[] = "POST-SPECIFIC INSTRUCTIONS:";
        $context_parts[] = $post_settings['post_additional_context'];
        $context_parts[] = "\nThese instructions are specific to this individual post and should take priority over general instructions.";
        
        return implode("\n", $context_parts);
    }
    
    /**
     * Build WYSIWYG formatting rules (if there are WYSIWYG fields)
     */
    private function build_wysiwyg_formatting_rules($custom_fields) {
        // Check if any WYSIWYG fields exist
        $has_wysiwyg = false;
        foreach ($custom_fields as $field) {
            if ($field['type'] === 'wysiwyg') {
                $has_wysiwyg = true;
                break;
            }
        }
        
        // Return empty if no WYSIWYG fields
        if (!$has_wysiwyg) {
            return '';
        }
        
        // Get WYSIWYG formatting settings
        $wysiwyg_formatting = get_option('sparkwp_wysiwyg_formatting', array(
            'paragraphs' => true,
            'bold' => true,
            'italic' => true,
            'headings' => true,
            'lists' => true,
            'links' => true
        ));
        
        $allowed_html = $this->build_allowed_html_tags($wysiwyg_formatting);
        
        // Return empty if no HTML tags are allowed
        if (empty($allowed_html)) {
            return '';
        }
        
        $instructions = array();
        $instructions[] = "WYSIWYG FIELD FORMATTING:";
        $instructions[] = "For fields marked as type 'wysiwyg', you may use HTML formatting when it enhances readability and structure.";
        $instructions[] = "Available HTML tags: {$allowed_html}";
        $instructions[] = "";
        $instructions[] = "Use HTML formatting when appropriate:";
        
        if (!empty($wysiwyg_formatting['paragraphs'])) {
            $instructions[] = "- <p> tags for paragraph breaks when content has multiple paragraphs";
        }
        
        if (!empty($wysiwyg_formatting['headings'])) {
            $instructions[] = "- <h2>, <h3>, <h4> for section headings when content benefits from clear structure";
        }
        
        if (!empty($wysiwyg_formatting['bold'])) {
            $instructions[] = "- <strong> to emphasize important points or key terms when needed";
        }
        
        if (!empty($wysiwyg_formatting['italic'])) {
            $instructions[] = "- <em> for subtle emphasis or technical terms when appropriate";
        }
        
        if (!empty($wysiwyg_formatting['lists'])) {
            $instructions[] = "- <ul>/<ol> with <li> for lists when presenting multiple items or steps";
        }
        
        if (!empty($wysiwyg_formatting['links'])) {
            $instructions[] = "- <a href=\"URL\"> for hyperlinks when relevant (use placeholder URLs like https://example.com)";
        }
        
        $instructions[] = "";
        $instructions[] = "Plain text is perfectly acceptable if HTML formatting doesn't add value. Use your judgment to create well-structured, readable content.";
        
        return implode("\n", $instructions);
    }
    
    /**
     * Build custom fields instructions
     */
    private function build_custom_fields_instructions($custom_fields) {
        $instructions = array();
        $instructions[] = "CONTENT FIELDS TO GENERATE:";
        $instructions[] = "You must generate content for the following custom fields:";
        $instructions[] = "";
        
        foreach ($custom_fields as $index => $field) {
            $field_num = $index + 1;
            $instructions[] = "{$field_num}. Field: {$field['label']} ({$field['key']})";
            $instructions[] = "   Field Type: {$field['type']}";
            
            if (!empty($field['description'])) {
                $instructions[] = "   Description: {$field['description']}";
            }
            
            if (!empty($field['word_count']) && $field['word_count'] > 0) {
                $instructions[] = "   Target Word Count: approximately {$field['word_count']} words";
            }
            
            $instructions[] = "";
        }
        
        return implode("\n", $instructions);
    }
    
    /**
     * Build output format instructions
     */
    private function build_output_format_instructions($custom_fields) {
        $instructions = array();
        $instructions[] = "OUTPUT FORMAT REQUIREMENTS:";
        $instructions[] = "You MUST return the content as a valid JSON object with the following structure:";
        $instructions[] = "";
        $instructions[] = "{";
        
        foreach ($custom_fields as $index => $field) {
            $comma = ($index < count($custom_fields) - 1) ? ',' : '';
            $instructions[] = "  \"{$field['key']}\": \"content for this field\"{$comma}";
        }
        
        $instructions[] = "}";
        $instructions[] = "";
        $instructions[] = "CRITICAL JSON FORMATTING RULES:";
        $instructions[] = "- Return ONLY valid JSON, no additional text before or after";
        $instructions[] = "- Use double quotes for all keys and string values";
        $instructions[] = "- Properly escape special characters (quotes, newlines, etc.)";
        $instructions[] = "- For WYSIWYG fields: include HTML tags as specified in the field instructions";
        $instructions[] = "- For non-WYSIWYG fields: use plain text with \\n for line breaks, no HTML";
        $instructions[] = "- Each field value should be a string containing the generated content";
        
        return implode("\n", $instructions);
    }
    
    /**
     * Build allowed HTML tags string based on formatting options
     * 
     * @param array $wysiwyg_formatting Formatting options
     * @return string Comma-separated list of allowed HTML tags
     */
    private function build_allowed_html_tags($wysiwyg_formatting) {
        $allowed_tags = array();
        
        if (!empty($wysiwyg_formatting['paragraphs'])) {
            $allowed_tags[] = '<p>';
        }
        
        if (!empty($wysiwyg_formatting['bold'])) {
            $allowed_tags[] = '<strong>';
        }
        
        if (!empty($wysiwyg_formatting['italic'])) {
            $allowed_tags[] = '<em>';
        }
        
        if (!empty($wysiwyg_formatting['headings'])) {
            $allowed_tags[] = '<h2>';
            $allowed_tags[] = '<h3>';
            $allowed_tags[] = '<h4>';
        }
        
        if (!empty($wysiwyg_formatting['lists'])) {
            $allowed_tags[] = '<ul>';
            $allowed_tags[] = '<ol>';
            $allowed_tags[] = '<li>';
        }
        
        if (!empty($wysiwyg_formatting['links'])) {
            $allowed_tags[] = '<a>';
        }
        
        return implode(', ', $allowed_tags);
    }
    
    /**
     * Build final instructions
     */
    private function build_final_instructions($settings) {
        $instructions = array();
        $instructions[] = "QUALITY REQUIREMENTS:";
        $instructions[] = "- Write in a professional, engaging tone";
        $instructions[] = "- Ensure content is SEO-optimized with natural keyword usage";
        $instructions[] = "- Use clear, concise language appropriate for the target audience";
        $instructions[] = "- Include relevant examples and details where appropriate";
        $instructions[] = "- Maintain factual accuracy and credibility";
        $instructions[] = "- Follow best practices for web content writing";
        
        if (!empty($settings['company_name'])) {
            $instructions[] = "- Naturally incorporate the company name where relevant";
        }
        
        $instructions[] = "";
        $instructions[] = "Remember: Return ONLY the JSON object, no explanations or additional text.";
        
        return implode("\n", $instructions);
    }
    
    /**
     * Get a preview of the prompt (for debugging)
     * 
     * @param string $prompt The full prompt
     * @param int $max_length Maximum length to show
     * @return string Truncated prompt preview
     */
    public function get_prompt_preview($prompt, $max_length = 500) {
        if (strlen($prompt) <= $max_length) {
            return $prompt;
        }
        
        return substr($prompt, 0, $max_length) . "... [truncated, total length: " . strlen($prompt) . " characters]";
    }
    
    /**
     * Build text generation prompt (filters text fields and builds prompt)
     * 
     * @param array $cpt_settings CPT-level settings (including custom_fields)
     * @param array $post_settings Post-specific settings
     * @return string The complete text prompt (empty string if no text fields)
     */
    public function build_text_prompt($cpt_settings, $post_settings) {
        $this->add_debug('build_text_prompt', 'Building text prompt');
        
        // Filter only text fields (exclude image fields)
        $text_fields = array_filter($cpt_settings['custom_fields'], function($field) {
            return $field['type'] !== 'image';
        });
        
        if (empty($text_fields)) {
            $this->add_debug('build_text_prompt', 'No text fields found - skipping text generation');
            return '';
        }
        
        // Build the complete prompt
        $prompt = $this->build_prompt($cpt_settings, $post_settings, $text_fields);
        
        // Log prompt details (including full prompt for debug tab)
        $this->add_debug('build_text_prompt', array(
            'prompt_length' => strlen($prompt),
            'prompt_preview' => $this->get_prompt_preview($prompt, 300),
            'prompt' => $prompt,  // Use 'prompt' key for debug.js to extract
            'keyword' => $post_settings['keyword'],
            'language' => $cpt_settings['language'],
            'text_fields_count' => count($text_fields)
        ));
        
        return $prompt;
    }
    
    /**
     * Build image generation prompt (single field)
     * 
     * @param array $cpt_settings CPT-level settings
     * @param array $post_settings Post-specific settings (including keyword)
     * @param array $field Image field configuration
     * @param int $post_id The post ID to retrieve existing content from
     * @return string The image generation prompt
     */
    public function build_image_prompt($cpt_settings, $post_settings, $field, $post_id) {
        $this->add_debug('build_image_prompt', array(
            'field_key' => $field['key'],
            'field_label' => $field['label'],
            'post_id' => $post_id
        ));
        
        $prompt_parts = array();
        
        $keyword = isset($post_settings['keyword']) ? $post_settings['keyword'] : '';
        
        // 1. Image field purpose (most important)
        $prompt_parts[] = "IMAGE FIELD: {$field['label']}";
        $prompt_parts[] = "FIELD KEY: {$field['key']}";
        
        // 2. Primary topic/keyword
        if (!empty($keyword)) {
            $prompt_parts[] = "PRIMARY TOPIC: {$keyword}";
        }
        
        // 3. Field-specific description or default instruction
        if (!empty($field['description'])) {
            $prompt_parts[] = "SPECIFIC INSTRUCTIONS:\n{$field['description']}";
        } else {
            $prompt_parts[] = "INSTRUCTIONS:\nCreate a professional, high-quality image that represents the field '{$field['label']}' in the context of: {$keyword}";
        }
        
        // 4. Add context from company/industry if available
        if (!empty($cpt_settings['industry'])) {
            $prompt_parts[] = "INDUSTRY CONTEXT: {$cpt_settings['industry']}";
        }
        
        // 5. Style requirements
        $prompt_parts[] = "STYLE REQUIREMENTS:\n- Professional and modern\n- High-quality composition\n- Clear and well-lit\n- Suitable for commercial use\n- Match the tone and context of the post content";
        
        // 6. Retrieve existing post content for context (at the end for reference)
        $post_content = $this->get_post_content($post_id);
        if (!empty($post_content)) {
            $prompt_parts[] = "EXISTING POST CONTENT (REFERENCE ONLY):\nThe following content is provided as additional context and may help you better understand the topic and tone of the image to generate. You may reference this content if the instructions above mention specific fields or require context from the post. However, this is optional reference material - focus primarily on the instructions and requirements specified above.\n\n" . $post_content;
        }
        
        $full_prompt = implode("\n\n", $prompt_parts);
        
        $this->add_debug('build_image_prompt', array(
            'field_key' => $field['key'],
            'prompt' => $full_prompt
        ));
        
        return $full_prompt;
    }
    
    /**
     * Get existing post content (both WordPress and ACF fields)
     * 
     * @param int $post_id The post ID
     * @return string Formatted post content
     */
    private function get_post_content($post_id) {
        $content_parts = array();
        $wp_fields = array();
        $acf_fields = array();
        
        // Get WordPress post object
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
        
        // Get WordPress baseline fields
        if (!empty($post->post_title)) {
            $wp_fields[] = "[Title]\n{$post->post_title}";
        }
        
        if (!empty($post->post_excerpt)) {
            $wp_fields[] = "[Excerpt]\n{$post->post_excerpt}";
        }
        
        if (!empty($post->post_content)) {
            // Strip HTML tags (no length limit)
            $clean_content = wp_strip_all_tags($post->post_content);
            $wp_fields[] = "[Content]\n{$clean_content}";
        }
        
        // Get ALL ACF fields for this post (not just enabled ones)
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups(array('post_type' => $post->post_type));
            
            if (!empty($field_groups)) {
                foreach ($field_groups as $group) {
                    $fields = acf_get_fields($group['key']);
                    
                    if ($fields) {
                        foreach ($fields as $field) {
                            // Only include text-based field types
                            $text_based_types = array('text', 'textarea', 'wysiwyg');
                            if (!in_array($field['type'], $text_based_types)) {
                                continue;
                            }
                            
                            // Get field value
                            $field_value = get_field($field['name'], $post_id);
                            
                            // Add to content if not empty
                            if (!empty($field_value)) {
                                // Clean HTML tags (no length limit)
                                $clean_value = is_string($field_value) ? wp_strip_all_tags($field_value) : $field_value;
                                $acf_fields[] = "[{$field['label']}]\n{$clean_value}";
                            }
                        }
                    }
                }
            }
        }
        
        // Build structured output
        if (!empty($wp_fields)) {
            $content_parts[] = "--- WordPress Fields ---";
            $content_parts[] = implode("\n\n", $wp_fields);
        }
        
        if (!empty($acf_fields)) {
            $content_parts[] = "--- Custom Fields ---";
            $content_parts[] = implode("\n\n", $acf_fields);
        }
        
        $this->add_debug('get_post_content_result', array(
            'post_id' => $post_id,
            'wp_fields_count' => count($wp_fields),
            'acf_fields_count' => count($acf_fields),
            'total_parts' => count($content_parts)
        ));
        
        return implode("\n\n", $content_parts);
    }
}
