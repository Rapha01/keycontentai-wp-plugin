<?php
/**
 * Prompt Builder Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class KeyContentAI_Prompt_Builder {
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
        
        // 4. Company/Client Information
        $prompt_parts[] = $this->build_client_context($cpt_settings);
        
        // 5. Target Audience
        $prompt_parts[] = $this->build_target_audience_section($cpt_settings);
        
        // 6. Post Type Specific Context
        $prompt_parts[] = $this->build_post_type_context($cpt_settings);
        
        // 7. Post-Specific Additional Context
        $prompt_parts[] = $this->build_post_specific_context($post_settings);
        
        // 8. Custom Fields Instructions
        $prompt_parts[] = $this->build_custom_fields_instructions($custom_fields);
        
        // 9. Output Format Instructions
        $prompt_parts[] = $this->build_output_format_instructions($custom_fields);
        
        // 10. Final Instructions
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
        $language_name = keycontentai_get_language_name($language);
        
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
     * Build client/company context
     */
    private function build_client_context($settings) {
        $context_parts = array();
        
        $context_parts[] = "CLIENT INFORMATION:";
        
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
        
        if (!empty($settings['client_additional_context'])) {
            $context_parts[] = "- Additional Context: {$settings['client_additional_context']}";
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
            
            if (!empty($field['description'])) {
                $instructions[] = "   Description: {$field['description']}";
            }
            
            if (!empty($field['word_count']) && $field['word_count'] > 0) {
                $instructions[] = "   Target Word Count: approximately {$field['word_count']} words";
            }
            
            $instructions[] = "   Field Type: {$field['type']}";
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
        $instructions[] = "- For multi-paragraph content, use \\n for line breaks";
        $instructions[] = "- Do not include markdown formatting unless specifically requested";
        $instructions[] = "- Each field value should be a string containing the generated content";
        
        return implode("\n", $instructions);
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
     * Build image generation prompts for DALL-E (filters image fields and builds prompts)
     * 
     * @param array $cpt_settings CPT-level settings (including custom_fields)
     * @param array $post_settings Post-specific settings
     * @return array Array of prompts keyed by field name
     */
    public function build_image_prompts($cpt_settings, $post_settings) {
        $this->add_debug('build_image_prompts', 'Building image prompts for DALL-E');
        
        $image_prompts = array();
        
        // Filter only image fields
        $image_fields = array_filter($cpt_settings['custom_fields'], function($field) {
            return $field['type'] === 'image';
        });
        
        if (empty($image_fields)) {
            $this->add_debug('build_image_prompts', 'No image fields found');
            return array();
        }
        
        // Build a prompt for each image field
        foreach ($image_fields as $field) {
            $image_prompts[$field['key']] = $this->build_image_prompt($cpt_settings, $post_settings, $field);
        }
        
        $this->add_debug('build_image_prompts', array(
            'image_fields_count' => count($image_fields),
            'prompts' => $image_prompts,
            'prompt' => !empty($image_prompts) ? implode("\n\n--- Next Image ---\n\n", $image_prompts) : ''
        ));
        
        return $image_prompts;
    }
    
    /**
     * Build image generation prompt for DALL-E (single field)
     * 
     * @param array $cpt_settings CPT-level settings
     * @param array $post_settings Post-specific settings (including keyword)
     * @param array $field Image field configuration
     * @return string The image generation prompt
     */
    public function build_image_prompt($cpt_settings, $post_settings, $field) {
        $prompt_parts = array();
        
        $keyword = isset($post_settings['keyword']) ? $post_settings['keyword'] : '';
        
        // 1. Base description from field configuration
        if (!empty($field['description'])) {
            $prompt_parts[] = $field['description'];
        } else {
            $prompt_parts[] = "Create a professional image related to: {$keyword}";
        }
        
        // 2. Add context from company/industry if available
        if (!empty($cpt_settings['industry'])) {
            $prompt_parts[] = "Industry context: {$cpt_settings['industry']}";
        }
        
        // 3. Add style and quality requirements
        $prompt_parts[] = "Style: Professional, high-quality, modern, suitable for commercial use";
        
        // 4. Technical requirements
        $prompt_parts[] = "Format: Clear composition, good lighting, appropriate for {$field['label']}";
        
        return implode('. ', $prompt_parts);
    }
}
