<?php
/**
 * Prompt Builder Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SparkPlus_Prompt_Builder {
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
    
    // ─── Public API ────────────────────────────────────────────────────────

    /**
     * Build the complete text generation prompt.
     *
     * Assembles shared context sections (topic, company, audience, etc.)
     * together with text-specific sections (field instructions, output format,
     * internal linking, WYSIWYG rules).
     *
     * @param array $cpt_settings  CPT-level settings (including custom_fields).
     * @param array $post_settings Post-specific settings (keyword, post context).
     * @param int   $post_id       Current post ID.
     * @return string The complete text prompt (empty if no text fields).
     */
    public function build_text_prompt( $cpt_settings, $post_settings, $post_id = 0 ) {
        $this->add_debug( 'build_text_prompt', 'Building text prompt' );

        // Filter to text-only fields (exclude image fields)
        $text_fields = array_values( array_filter( $cpt_settings['custom_fields'], function ( $f ) {
            return $f['type'] !== 'image';
        } ) );

        if ( empty( $text_fields ) ) {
            $this->add_debug( 'build_text_prompt', 'No text fields found — skipping text generation' );
            return '';
        }

        $parts = array();

        // --- Shared sections ---
        $parts[] = $this->build_system_context();
        $parts[] = $this->build_language_instruction( $cpt_settings );
        $parts[] = $this->build_topic_section( $post_settings );
        $parts[] = $this->build_general_context( $cpt_settings, 'text' );
        $parts[] = $this->build_target_audience_section( $cpt_settings );
        $parts[] = $this->build_post_type_context( $cpt_settings, 'text' );
        $parts[] = $this->build_post_specific_context( $post_settings );
        $parts[] = $this->build_existing_content_section( $cpt_settings, $text_fields, $post_id );

        // --- Text-specific sections ---
        $parts[] = $this->build_internal_linking_section( $text_fields, $post_id );
        $parts[] = $this->build_wysiwyg_formatting_rules( $text_fields );
        $parts[] = $this->build_custom_fields_instructions( $text_fields, ! empty( $cpt_settings['include_acf_instructions'] ) );
        $parts[] = $this->build_output_format_instructions( $text_fields );
        $parts[] = $this->build_final_instructions( $cpt_settings );

        $prompt = implode( "\n\n\n", array_filter( $parts ) );

        $this->add_debug( 'build_text_prompt', array(
            'prompt_length'     => strlen( $prompt ),
            'prompt_preview'    => $this->get_prompt_preview( $prompt, 300 ),
            'prompt'            => $prompt,
            'keyword'           => $post_settings['keyword'],
            'language'          => $cpt_settings['language'],
            'text_fields_count' => count( $text_fields ),
        ) );

        return $prompt;
    }

    /**
     * Build the complete image generation prompt for a single field.
     *
     * Assembles the same shared context sections as text (topic, company,
     * audience, etc.) together with image-specific sections (field header,
     * image instructions, style requirements).
     *
     * @param array $cpt_settings  CPT-level settings.
     * @param array $post_settings Post-specific settings (keyword, etc.).
     * @param array $field         Image field configuration.
     * @param int   $post_id       Current post ID.
     * @return string The image generation prompt.
     */
    public function build_image_prompt( $cpt_settings, $post_settings, $field, $post_id ) {
        $this->add_debug( 'build_image_prompt', array(
            'field_key'   => $field['key'],
            'field_label' => $field['label'],
            'post_id'     => $post_id,
        ) );

        $keyword = isset( $post_settings['keyword'] ) ? $post_settings['keyword'] : '';

        $parts = array();

        // --- Image field header + instructions ---
        $parts[] = $this->build_image_field_section( $field, $cpt_settings, $keyword );

        // --- Shared sections ---
        $parts[] = $this->build_topic_section( $post_settings );
        $parts[] = $this->build_general_context( $cpt_settings, 'image' );
        $parts[] = $this->build_target_audience_section( $cpt_settings );
        $parts[] = $this->build_post_type_context( $cpt_settings, 'image' );
        $parts[] = $this->build_post_specific_context( $post_settings );

        // --- Image-specific sections ---
        $parts[] = $this->build_image_style_requirements();
        $parts[] = $this->build_existing_content_for_image( $post_id );

        $prompt = implode( "\n\n", array_filter( $parts ) );

        $this->add_debug( 'build_image_prompt', array(
            'field_key' => $field['key'],
            'prompt'    => $prompt,
        ) );

        return $prompt;
    }

    // ─── Shared Sections ──────────────────────────────────────────────────

    /**
     * Build system context and role (text prompts only).
     */
    private function build_system_context() {
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
        $language_name = sparkplus_get_language_name($language);
        
        $instruction = "**IMPORTANT:** Write all content in {$language_name}.";
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
        
        return "# Topic\n**Keyword:** {$post_settings['keyword']}";
    }
    
    /**
     * Build general context / company context.
     *
     * @param array  $settings CPT-level settings.
     * @param string $type     'text' or 'image' — selects the matching additional-context field.
     */
    private function build_general_context( $settings, $type = 'text' ) {
        $context_parts = array();

        if ( ! empty( $settings['company_name'] ) ) {
            $context_parts[] = "- Company: {$settings['company_name']}";
        }
        if ( ! empty( $settings['industry'] ) ) {
            $context_parts[] = "- Industry: {$settings['industry']}";
        }
        if ( ! empty( $settings['usp'] ) ) {
            $context_parts[] = "- Unique Selling Proposition: {$settings['usp']}";
        }
        if ( ! empty( $settings['advantages'] ) ) {
            $context_parts[] = "- Key Advantages: {$settings['advantages']}";
        }
        if ( ! empty( $settings['buying_reasons'] ) ) {
            $context_parts[] = "- Why Customers Choose Us: {$settings['buying_reasons']}";
        }

        $key = ( $type === 'image' )
            ? 'general_context_additional_context_image'
            : 'general_context_additional_context_text';

        if ( ! empty( $settings[ $key ] ) ) {
            $context_parts[] = "- Additional Context: {$settings[ $key ]}";
        }

        if ( empty( $context_parts ) ) {
            return '';
        }

        array_unshift( $context_parts, '# General Context' );

        return implode( "\n", $context_parts );
    }
    
    /**
     * Build target audience section
     */
    private function build_target_audience_section($settings) {
        if (empty($settings['target_group'])) {
            return '';
        }
        
        return "# Target Audience\n{$settings['target_group']}\nTailor the content to resonate with this specific audience. Use language, examples, and references that appeal to them.";
    }
    
    /**
     * Build post-type-specific context.
     *
     * @param array  $settings CPT-level settings.
     * @param string $type     'text' or 'image' — selects the matching additional-context field.
     */
    private function build_post_type_context( $settings, $type = 'text' ) {
        $context_parts = array();

        $context_parts[] = "# Post Type: {$settings['post_type']}";

        $key = ( $type === 'image' )
            ? 'cpt_additional_context_image'
            : 'cpt_additional_context_text';

        if ( ! empty( $settings[ $key ] ) ) {
            $context_parts[] = '## Specific Instructions for This Post Type';
            $context_parts[] = $settings[ $key ];
        }

        return implode( "\n", $context_parts );
    }
    
    /**
     * Build post-specific additional context
     */
    private function build_post_specific_context($post_settings) {
        if (empty($post_settings['post_additional_context'])) {
            return '';
        }
        
        $context_parts = array();
        
        $context_parts[] = "# Post-Specific Instructions";
        $context_parts[] = $post_settings['post_additional_context'];
        $context_parts[] = "\n**These instructions are specific to this individual post and should take priority over general instructions.**";
        
        return implode("\n", $context_parts);
    }
    
    /**
     * Build existing content section
     */
    private function build_existing_content_section($cpt_settings, $custom_fields, $post_id) {
        // Check if feature is enabled
        if (empty($cpt_settings['include_existing_content'])) {
            return '';
        }
        
        // Only show existing content if we have a valid post ID
        if (empty($post_id) || $post_id <= 0) {
            return '';
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
        
        // Build list of all available fields with their types
        $all_fields = array();
        
        // 1. Add WordPress baseline fields
        $baseline_fields = array(
            'post_title' => array('label' => 'Title', 'type' => 'text'),
            'post_content' => array('label' => 'Content', 'type' => 'wysiwyg'),
            'post_excerpt' => array('label' => 'Excerpt', 'type' => 'textarea')
        );
        
        foreach ($baseline_fields as $field_key => $field_info) {
            $all_fields[$field_key] = array(
                'key' => $field_key,
                'label' => $field_info['label'],
                'type' => $field_info['type'],
                'source' => 'wordpress'
            );
        }
        
        // 2. Add ACF fields (expand groups into dotted sub-field entries)
        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups(array('post_type' => $post->post_type));
            
            if (!empty($field_groups)) {
                foreach ($field_groups as $group) {
                    $fields = acf_get_fields($group['key']);
                    
                    if ($fields) {
                        foreach ($fields as $field) {
                            if ($field['type'] === 'group' && !empty($field['sub_fields'])) {
                                foreach ($field['sub_fields'] as $sub_field) {
                                    $all_fields[$field['name'] . '.' . $sub_field['name']] = array(
                                        'key'       => $sub_field['name'],
                                        'label'     => $field['label'] . ' › ' . $sub_field['label'],
                                        'type'      => $sub_field['type'],
                                        'source'    => 'acf',
                                        'group_key' => $field['name'],
                                    );
                                }
                            } else {
                                $all_fields[$field['name']] = array(
                                    'key'    => $field['name'],
                                    'label'  => $field['label'],
                                    'type'   => $field['type'],
                                    'source' => 'acf',
                                );
                            }
                        }
                    }
                }
            }
        }
        
        $existing_content = array();
        $text_field_types = array('text', 'textarea', 'wysiwyg', 'url', 'email');
        
        // Check each field that has existing content
        foreach ($all_fields as $field_name => $field_data) {
            // Skip if not a text field type
            if (!in_array($field_data['type'], $text_field_types)) {
                continue;
            }
            
            // Get the content
            $content = '';
            $field_label = $field_data['label'];
            
            // Check WordPress fields
            if ($field_name === 'post_title') {
                $content = $post->post_title;
            } elseif ($field_name === 'post_content') {
                $content = $post->post_content;
            } elseif ($field_name === 'post_excerpt') {
                $content = $post->post_excerpt;
            } else {
                // Check ACF fields
                if (function_exists('get_field')) {
                    if (!empty($field_data['group_key'])) {
                        // Sub-field: read from parent group array
                        $group_value = get_field($field_data['group_key'], $post_id);
                        $content = (is_array($group_value) && isset($group_value[$field_data['key']]))
                            ? $group_value[$field_data['key']] : '';
                    } else {
                        $content = get_field($field_name, $post_id);
                    }
                }
            }
            
            // If content exists, add it to context
            if (!empty($content)) {
                // Strip HTML tags and collapse newlines so the value stays on a single
                // line within the Markdown list item (a blank line would break the list).
                $content_clean = wp_strip_all_tags($content);
                $content_clean = preg_replace('/\s*\n\s*/u', ' ', $content_clean);
                $content_clean = preg_replace('/ {2,}/u', ' ', trim($content_clean));
                $existing_content[] = "- {$field_label}: {$content_clean}";
            }
        }
        
        // Build context section
        if (empty($existing_content)) {
            return '';
        }
        
        $section_parts = array();
        $section_parts[] = "# Existing Content";
        $section_parts[] = "This post already has content in the following fields:";
        $section_parts[] = "";
        $section_parts = array_merge($section_parts, $existing_content);
        $section_parts[] = "";
        $section_parts[] = "## Purpose";
        $section_parts[] = "Use this existing content to:";
        $section_parts[] = "1. **Avoid duplication:** Don't repeat information already covered in other fields";
        $section_parts[] = "2. **Reference between fields:** You can reference information from other fields when relevant (e.g., 'As mentioned in the description...')";
        $section_parts[] = "3. **Reference from field context:** If the additional context/description of a field being generated instructs you to reference other fields, you may use the content above";
        
        // Add link management instruction only if linking is enabled
        $linking_enabled = get_option('sparkplus_linking_enable', false);
        if ($linking_enabled) {
            $section_parts[] = "4. **Link management:** Check what links already exist in other fields to avoid overusing links to the same page, but do add new relevant links from the linking pool that haven't been used yet";
        }
        
        return implode("\n", $section_parts);
    }
    
    // ─── Text-Specific Sections ──────────────────────────────────────────

    /**
     * Build internal linking section
     */
    private function build_internal_linking_section($custom_fields, $post_id) {
        // Check if linking is enabled
        $linking_enabled = get_option('sparkplus_linking_enable', false);
        
        if (!$linking_enabled) {
            return '';
        }
        
        // Get linking pool
        $linking_pool_json = get_option('sparkplus_linking_pool', '');
        
        if (empty($linking_pool_json)) {
            return '';
        }
        
        $linking_pool = json_decode($linking_pool_json, true);
        
        if (!is_array($linking_pool)) {
            return '';
        }
        
        // Build list of available links
        $available_links = array();
        
        // Add posts from selected post types
        if (!empty($linking_pool['post_types']) && is_array($linking_pool['post_types'])) {
            foreach ($linking_pool['post_types'] as $post_type) {
                $posts = get_posts(array(
                    'post_type' => $post_type,
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'exclude' => $post_id // Exclude current post
                ));
                
                foreach ($posts as $post) {
                    $keyword = get_post_meta($post->ID, 'sparkplus_keyword', true);
                    
                    $available_links[] = array(
                        'id' => $post->ID,
                        'keyword' => !empty($keyword) ? $keyword : '',
                        'title' => $post->post_title,
                        'url' => get_permalink($post->ID)
                    );
                }
            }
        }
        
        // Add individual selected posts
        if (!empty($linking_pool['single_items']) && is_array($linking_pool['single_items'])) {
            foreach ($linking_pool['single_items'] as $item) {
                // Skip if this is the current post
                if ($item['id'] == $post_id) {
                    continue;
                }
                
                $post = get_post($item['id']);
                
                if ($post && $post->post_status === 'publish') {
                    $keyword = get_post_meta($post->ID, 'sparkplus_keyword', true);
                    
                    $available_links[] = array(
                        'id' => $post->ID,
                        'keyword' => !empty($keyword) ? $keyword : '',
                        'title' => $post->post_title,
                        'url' => get_permalink($post->ID)
                    );
                }
            }
        }
        
        // Add custom links
        if (!empty($linking_pool['custom_links']) && is_array($linking_pool['custom_links'])) {
            foreach ($linking_pool['custom_links'] as $link) {
                $keywords = '';
                if (!empty($link['keywords']) && is_array($link['keywords'])) {
                    $keywords = implode(', ', $link['keywords']);
                }
                
                $available_links[] = array(
                    'id' => 0,
                    'keyword' => $keywords,
                    'title' => $link['title'],
                    'url' => $link['url']
                );
            }
        }
        
        // If no links available, return empty
        if (empty($available_links)) {
            return '';
        }
        
        // Build the section
        $section_parts = array();
        $section_parts[] = "# Internal Linking";
        $section_parts[] = "You have access to the following links that you should use when appropriate. **ONLY use links from this list** - do not create or use any other links:";
        $section_parts[] = "";
        
        foreach ($available_links as $index => $link) {
            $link_entry = ($index + 1) . ". ";
            
            if (!empty($link['keyword'])) {
                $link_entry .= "Keyword: " . $link['keyword'] . " | ";
            }
            
            $link_entry .= "Title: " . $link['title'] . " | URL: " . $link['url'];
            
            if (!empty($link['id'])) {
                $link_entry .= " | ID: " . $link['id'];
            }
            
            $section_parts[] = $link_entry;
        }
        
        $section_parts[] = "";
        
        // Add usage instructions
        $linking_wysiwyg = get_option('sparkplus_linking_wysiwyg', false);
        $has_wysiwyg_fields = false;
        $has_url_fields = false;
        
        foreach ($custom_fields as $field) {
            if ($field['type'] === 'wysiwyg') {
                $has_wysiwyg_fields = true;
            }
            if ($field['type'] === 'url') {
                $has_url_fields = true;
            }
        }
        
        $section_parts[] = "## How to Use These Links";
        $section_parts[] = "**IMPORTANT:** You must **ONLY** use URLs from the list above. Never use or create links that are not explicitly provided in this list.";
        $section_parts[] = "Actively look for opportunities to include relevant links from this list. Link to related topics, broader concepts, or complementary information that would benefit the reader.";
        $section_parts[] = "";
        
        if ($linking_wysiwyg && $has_wysiwyg_fields) {
            $section_parts[] = "- For WYSIWYG fields: Insert relevant HTML links (<a href=\"URL\">anchor text</a>) throughout the content. Link to related pages when:";
            $section_parts[] = "  • Mentioning a topic that has a dedicated page in the list";
            $section_parts[] = "  • Discussing a specific aspect of a broader topic (e.g., link from 'Vogelmilben bekämpfen' to general 'Vogelmilben' page)";
            $section_parts[] = "  • Referencing related products, services, or information covered by other pages";
            $section_parts[] = "  • Providing context or background that another page explains in detail";
            $section_parts[] = "  Use contextually appropriate anchor text and aim to include multiple relevant links where it makes sense. Use ONLY the exact URLs provided in the list.";
        }
        
        if ($has_url_fields) {
            $section_parts[] = "- For URL fields: Select the most relevant link from the list above based on the field's purpose and the content you're generating. Look for strong topical relationships and choose links that provide valuable additional context or information. Use ONLY URLs from the provided list.";
        }
        
        if (!$linking_wysiwyg && !$has_url_fields) {
            $section_parts[] = "- Use these links as reference material to understand the available content, but do not insert them into the generated content.";
        }
        
        return implode("\n", $section_parts);
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
        $wysiwyg_formatting = get_option('sparkplus_wysiwyg_formatting', array(
            'paragraphs' => true,
            'bold' => true,
            'italic' => true,
            'headings' => false,
            'lists' => true
        ));
        
        $allowed_html = $this->build_allowed_html_tags($wysiwyg_formatting);
        
        // Return empty if no HTML tags are allowed
        if (empty($allowed_html)) {
            return '';
        }
        
        $instructions = array();
        $instructions[] = "# WYSIWYG Field Formatting";
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
        
        $instructions[] = "";
        $instructions[] = "Plain text is perfectly acceptable if HTML formatting doesn't add value. Use your judgment to create well-structured, readable content.";
        
        return implode("\n", $instructions);
    }
    
    /**
     * Build custom fields instructions
     */
    /**
     * Return special SEO copy-writing instructions for RankMath meta fields.
     *
     * @param string $key  Field key (rank_math_title | rank_math_description).
     * @return string      Multi-line instruction string, or empty string for unknown keys.
     */
    private function get_rankmath_field_instructions( $key ) {
        if ( $key === 'rank_math_title' ) {
            return implode( "\n", array(
                '   ⚑ SEO Meta Title — follow these rules strictly:',
                '     • Maximum 60 characters (including spaces). Character counts close to the maximum are better. Count carefully.',
                '     • Include the primary keyword naturally — it does NOT have to be the very first word but earlier mentions of the keyword are preferrable.',
                '     • Internally write 3 candidate titles using clearly different structures. Do NOT output these candidates — output only the third one as the final value.',
                '     • Avoid generic, formulaic phrasing. The title should stand out and make the reader want to click.',
                '     • Do NOT append the site/brand name — RankMath adds that automatically.',
                '     • Do NOT use quotation marks, pipes, or special characters.',
            ) );
        }
        if ( $key === 'rank_math_description' ) {
            return implode( "\n", array(
                '   ⚑ SEO Meta Description — follow these rules strictly:',
                '     • Between 145 and 150 characters (including spaces). Count carefully.',
                '     • Include the primary keyword naturally in the first half of the sentence.',
                '     • Write a single clear, action-oriented sentence that summarises the page and encourages click-through.',
                '     • Do NOT use quotation marks or markdown formatting.',
                '     • Do NOT truncate — the sentence must feel complete.',
            ) );
        }
        return '';
    }

    private function build_custom_fields_instructions($custom_fields, $include_acf_instructions = false) {
        $instructions = array();
        $instructions[] = "# Content Fields to Generate";
        $instructions[] = "You must generate content for the following custom fields:";
        $instructions[] = "";

        // Build field type guide showing only types that are actually present.
        $present_types = array();
        foreach ( $custom_fields as $field ) {
            $present_types[ $field['type'] ] = true;
        }

        $type_guide = array(
            'text'        => '**text** — Short plain text. Output a plain text string; no HTML or markdown.',
            'textarea'    => '**textarea** — Multi-line plain text. Use \\n for line breaks; no HTML.',
            'wysiwyg'     => '**wysiwyg** — Rich text editor. HTML formatting is allowed (see WYSIWYG Field Formatting rules above).',
            'true_false'  => '**true_false** — Boolean. You MUST output the integer `1` (true/yes) or `0` (false/no). No other value is accepted.',
            'post_object' => '**post_object** — Related post(s). Output a JSON array of integer post IDs chosen from the Internal Linking pool. Example: `[12, 34]`. Only use IDs listed in the linking section.',
        );

        $guide_lines = array();
        foreach ( $type_guide as $type => $desc ) {
            if ( isset( $present_types[ $type ] ) ) {
                $guide_lines[] = "- {$desc}";
            }
        }

        if ( ! empty( $guide_lines ) ) {
            $instructions[] = "## Field Type Guide";
            foreach ( $guide_lines as $line ) {
                $instructions[] = $line;
            }
            $instructions[] = "";
        }

        $field_num     = 1;
        $current_group = null;
        $sub_num       = 1;
        
        foreach ($custom_fields as $field) {
            $this_group = isset($field['group_key']) ? $field['group_key'] : null;
            
            // Emit group header when entering a new group
            if ($this_group !== null && $this_group !== $current_group) {
                $group_label = isset($field['group_label']) ? $field['group_label'] : $this_group;
                $instructions[] = "{$field_num}. **Group: {$group_label} ({$this_group})**";
                $field_num++;
                $sub_num       = 1;
                $current_group = $this_group;
            } elseif ($this_group === null) {
                $current_group = null;
            }
            
            if ($this_group !== null) {
                $instructions[] = "   {$sub_num}. Field: {$field['label']} ({$field['key']})";
                $instructions[] = "      Field Type: {$field['type']}";
                if ($include_acf_instructions && !empty($field['acf_instructions'])) {
                    $instructions[] = "      ACF Instructions: {$field['acf_instructions']}";
                }
                if (!empty($field['description'])) {
                    $instructions[] = "      Description: {$field['description']}";
                }
                $rm_hint = isset( $field['key'] ) ? $this->get_rankmath_field_instructions( $field['key'] ) : '';
                if ( $rm_hint !== '' ) {
                    $instructions[] = $rm_hint;
                }
                if (!empty($field['word_count']) && $field['word_count'] > 0) {
                    if ( $field['type'] === 'post_object' ) {
                        $instructions[] = "      Select approximately {$field['word_count']} posts from the Internal Linking pool";
                    } else {
                        $instructions[] = "      Target Word Count: approximately {$field['word_count']} words";
                    }
                }
                $instructions[] = "";
                $sub_num++;
            } else {
                $instructions[] = "{$field_num}. Field: {$field['label']} ({$field['key']})";
                $instructions[] = "   Field Type: {$field['type']}";
                if ($include_acf_instructions && !empty($field['acf_instructions'])) {
                    $instructions[] = "   ACF Instructions: {$field['acf_instructions']}";
                }
                if (!empty($field['description'])) {
                    $instructions[] = "   Description: {$field['description']}";
                }
                $rm_hint = isset( $field['key'] ) ? $this->get_rankmath_field_instructions( $field['key'] ) : '';
                if ( $rm_hint !== '' ) {
                    $instructions[] = $rm_hint;
                }
                if (!empty($field['word_count']) && $field['word_count'] > 0) {
                    if ( $field['type'] === 'post_object' ) {
                        $instructions[] = "   Select approximately {$field['word_count']} posts from the Internal Linking pool";
                    } else {
                        $instructions[] = "   Target Word Count: approximately {$field['word_count']} words";
                    }
                }
                $instructions[] = "";
                $field_num++;
            }
        }

        return implode("\n", $instructions);
    }

    /**
     * Build output format instructions
     */
    private function build_output_format_instructions($custom_fields) {
        $instructions = array();
        $instructions[] = "# Output Format Requirements";
        $instructions[] = "You **MUST** return the content as a valid JSON object with the following structure:";    
        $instructions[] = "";
        $instructions[] = "{";
        
        // Build ordered output structure, nesting group sub-fields as objects
        $output_items = array();
        $seen_groups  = array();
        foreach ($custom_fields as $field) {
            if (!empty($field['group_key'])) {
                $gk = $field['group_key'];
                if (!isset($seen_groups[$gk])) {
                    $seen_groups[$gk]  = count($output_items);
                    $output_items[]    = array('type' => 'group', 'key' => $gk, 'sub_keys' => array());
                }
                $output_items[$seen_groups[$gk]]['sub_keys'][] = array( 'key' => $field['key'], 'field_type' => $field['type'] );
            } else {
                $output_items[] = array( 'type' => 'field', 'key' => $field['key'], 'field_type' => $field['type'] );
            }
        }
        
        $total = count($output_items);
        foreach ($output_items as $i => $item) {
            $comma = ($i < $total - 1) ? ',' : '';
            if ($item['type'] === 'group') {
                $instructions[] = "  \"{$item['key']}\": {";
                $sub_total = count($item['sub_keys']);
                foreach ($item['sub_keys'] as $j => $sub_field) {
                    $sub_comma      = ($j < $sub_total - 1) ? ',' : '';
                    $placeholder    = $this->get_json_placeholder( $sub_field['field_type'] );
                    $instructions[] = "    \"{$sub_field['key']}\": {$placeholder}{$sub_comma}";
                }
                $instructions[] = "  }{$comma}";
            } else {
                $placeholder    = $this->get_json_placeholder( $item['field_type'] );
                $instructions[] = "  \"{$item['key']}\": {$placeholder}{$comma}";
            }
        }
        
        $instructions[] = "}";
        $instructions[] = "";
        $instructions[] = "## Critical JSON Formatting Rules";
        $instructions[] = "- Return ONLY valid JSON, no additional text before or after";
        $instructions[] = "- Use double quotes for all keys and string values";
        $instructions[] = "- Properly escape special characters (quotes, newlines, etc.)";
        $instructions[] = "- For WYSIWYG fields: include HTML tags as specified in the field instructions";
        $instructions[] = "- For non-WYSIWYG text fields: use plain text with \\n for line breaks, no HTML";
        $instructions[] = "- For true_false fields: output the integer 1 (true/yes) or 0 (false/no) — no quotes, no other values";
        $instructions[] = "- For post_object fields: output a JSON array of integer post IDs (e.g. [12, 34]) — only use IDs from the Internal Linking pool";
        $instructions[] = "- All field values must be strings, except true_false fields (integers 1 or 0) and post_object fields (arrays of integers)";
        
        return implode("\n", $instructions);
    }

    /**
     * Get JSON placeholder value for the output format template based on field type.
     *
     * @param string $field_type ACF field type.
     * @return string Placeholder for JSON template.
     */
    private function get_json_placeholder( $field_type ) {
        switch ( $field_type ) {
            case 'true_false':
                return '1';
            case 'post_object':
                return '[12, 34]';
            default:
                return '"content for this field"';
        }
    }
    
    // ─── Utilities ───────────────────────────────────────────────────────

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
        
        return implode(', ', $allowed_tags);
    }
    
    /**
     * Build final instructions
     */
    private function build_final_instructions($settings) {
        $instructions = array();
        $instructions[] = "# Quality Requirements";
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
    
    // ─── Image-Specific Sections ──────────────────────────────────────────

    /**
     * Build the image field header and field-specific instructions.
     */
    private function build_image_field_section( $field, $cpt_settings, $keyword ) {
        $parts = array();

        $parts[] = "# Image Field: {$field['label']}";
        $parts[] = "**Field key:** `{$field['key']}`";

        // Field-specific description / ACF instructions
        $use_acf = ! empty( $cpt_settings['include_acf_instructions'] ) && ! empty( $field['acf_instructions'] );
        $has_custom = ! empty( $field['description'] ) || $use_acf;

        if ( $has_custom ) {
            $lines = array();
            if ( $use_acf ) {
                $lines[] = $field['acf_instructions'];
            }
            if ( ! empty( $field['description'] ) ) {
                $lines[] = $field['description'];
            }
            $parts[] = "# Specific Instructions\n" . implode( "\n", $lines );
        } else {
            $parts[] = "# Instructions\nCreate a professional, high-quality image that represents the field '{$field['label']}' in the context of: {$keyword}";
        }

        return implode( "\n", $parts );
    }

    /**
     * Build generic image style requirements.
     */
    private function build_image_style_requirements() {
        return "# Style Requirements\n"
            . "- Professional and modern\n"
            . "- High-quality composition\n"
            . "- Clear and well-lit\n"
            . "- Suitable for commercial use\n"
            . "- Match the tone and context of the post content";
    }

    /**
     * Build existing-content reference for image prompts (lighter framing).
     */
    private function build_existing_content_for_image( $post_id ) {
        $content = $this->get_post_content( $post_id );
        if ( empty( $content ) ) {
            return '';
        }

        return "# Existing Post Content (Reference Only)\n"
            . "The following content is provided as additional context and may help you better understand "
            . "the topic and tone of the image to generate. You may reference this content if the instructions "
            . "above mention specific fields or require context from the post. However, this is optional "
            . "reference material — focus primarily on the instructions and requirements specified above.\n\n"
            . $content;
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
                            $text_based_types = array('text', 'textarea', 'wysiwyg');
                            
                            if ($field['type'] === 'group' && !empty($field['sub_fields'])) {
                                // Read group value (associative array)
                                $group_value = get_field($field['name'], $post_id);
                                if (is_array($group_value)) {
                                    foreach ($field['sub_fields'] as $sub_field) {
                                        if (!in_array($sub_field['type'], $text_based_types)) continue;
                                        if (empty($group_value[$sub_field['name']])) continue;
                                        $clean_value = is_string($group_value[$sub_field['name']])
                                            ? wp_strip_all_tags($group_value[$sub_field['name']]) : '';
                                        if (!empty($clean_value)) {
                                            $acf_fields[] = "[{$field['label']} › {$sub_field['label']}]\n{$clean_value}";
                                        }
                                    }
                                }
                            } elseif (in_array($field['type'], $text_based_types)) {
                                $field_value = get_field($field['name'], $post_id);
                                if (!empty($field_value)) {
                                    $clean_value = is_string($field_value) ? wp_strip_all_tags($field_value) : '';
                                    if (!empty($clean_value)) {
                                        $acf_fields[] = "[{$field['label']}]\n{$clean_value}";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Build structured output
        if (!empty($wp_fields)) {
            $content_parts[] = "## WordPress Fields";
            $content_parts[] = implode("\n\n", $wp_fields);
        }
        
        if (!empty($acf_fields)) {
            $content_parts[] = "## Custom Fields";
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
