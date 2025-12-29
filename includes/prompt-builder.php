<?php
/**
 * Prompt Builder Class
 * 
 * Handles the construction of AI prompts for content generation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class KeyContentAI_Prompt_Builder {
    
    /**
     * Build the complete prompt for OpenAI
     * 
     * @param string $keyword The keyword/topic for content generation
     * @param array $settings All plugin settings
     * @param array $custom_fields Custom fields configuration
     * @return string The complete prompt
     */
    public function build_prompt($keyword, $settings, $custom_fields) {
        $prompt_parts = array();
        
        // 1. System Role and Context
        $prompt_parts[] = $this->build_system_context($settings);
        
        // 2. Language Instruction
        $prompt_parts[] = $this->build_language_instruction($settings);
        
        // 3. Topic/Keyword
        $prompt_parts[] = $this->build_topic_section($keyword);
        
        // 4. Company/Client Information
        $prompt_parts[] = $this->build_client_context($settings);
        
        // 5. Target Audience
        if (!empty($settings['target_group'])) {
            $prompt_parts[] = $this->build_target_audience_section($settings);
        }
        
        // 6. Competition Context (if available)
        if (!empty($settings['competition_urls'])) {
            $prompt_parts[] = $this->build_competition_section($settings);
        }
        
        // 7. Post Type Specific Context
        $prompt_parts[] = $this->build_post_type_context($settings);
        
        // 8. Custom Fields Instructions
        $prompt_parts[] = $this->build_custom_fields_instructions($custom_fields);
        
        // 9. Output Format Instructions
        $prompt_parts[] = $this->build_output_format_instructions($custom_fields);
        
        // 10. Final Instructions
        $prompt_parts[] = $this->build_final_instructions($settings);
        
        // Combine all parts with double line breaks
        return implode("\n\n", array_filter($prompt_parts));
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
        $language = !empty($settings['language']) ? $settings['language'] : 'de';
        
        $language_names = array(
            'de' => 'German',
            'en' => 'English',
            'fr' => 'French',
            'es' => 'Spanish',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'ru' => 'Russian',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean'
        );
        
        $language_name = isset($language_names[$language]) ? $language_names[$language] : 'German';
        
        $instruction = "IMPORTANT: Write all content in {$language_name}.";
        
        // Add addressing style for German
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
    private function build_topic_section($keyword) {
        return "TOPIC/KEYWORD: {$keyword}\n\nCreate comprehensive content about this topic.";
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
        
        if (!empty($settings['additional_context'])) {
            $context_parts[] = "- Additional Context: {$settings['additional_context']}";
        }
        
        return implode("\n", $context_parts);
    }
    
    /**
     * Build target audience section
     */
    private function build_target_audience_section($settings) {
        return "TARGET AUDIENCE: {$settings['target_group']}\n\nTailor the content to resonate with this specific audience. Use language, examples, and references that appeal to them.";
    }
    
    /**
     * Build competition section
     */
    private function build_competition_section($settings) {
        $competition_parts = array();
        $competition_parts[] = "COMPETITIVE CONTEXT:";
        $competition_parts[] = "The following competitor websites exist in this space:";
        
        foreach ($settings['competition_urls'] as $url) {
            if (!empty($url)) {
                $competition_parts[] = "- {$url}";
            }
        }
        
        $competition_parts[] = "Create content that is more comprehensive, engaging, and valuable than what competitors offer.";
        
        return implode("\n", $competition_parts);
    }
    
    /**
     * Build post type specific context
     */
    private function build_post_type_context($settings) {
        $context_parts = array();
        
        $post_type = $settings['post_type'];
        $context_parts[] = "POST TYPE: {$post_type}";
        
        // Check if there's post-type-specific additional context
        if (!empty($settings['cpt_additional_context'][$post_type])) {
            $context_parts[] = "SPECIFIC INSTRUCTIONS FOR THIS POST TYPE:";
            $context_parts[] = $settings['cpt_additional_context'][$post_type];
        }
        
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
     * Build image generation prompt for DALL-E
     * 
     * @param string $keyword The keyword/topic
     * @param array $settings All plugin settings
     * @param array $field Image field configuration
     * @return string The image generation prompt
     */
    public function build_image_prompt($keyword, $settings, $field) {
        $prompt_parts = array();
        
        // 1. Base description from field configuration
        if (!empty($field['description'])) {
            $prompt_parts[] = $field['description'];
        } else {
            $prompt_parts[] = "Create a professional image related to: {$keyword}";
        }
        
        // 2. Add context from company/industry if available
        if (!empty($settings['industry'])) {
            $prompt_parts[] = "Industry context: {$settings['industry']}";
        }
        
        // 3. Add style and quality requirements
        $prompt_parts[] = "Style: Professional, high-quality, modern, suitable for commercial use";
        
        // 4. Technical requirements
        $prompt_parts[] = "Format: Clear composition, good lighting, appropriate for {$field['label']}";
        
        return implode('. ', $prompt_parts);
    }
}
