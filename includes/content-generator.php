<?php
/**
 * Content Generator Class
 * 
 * Handles all content generation logic including:
 * - Gathering settings and custom fields
 * - Building prompts
 * - Calling OpenAI API
 * - Creating posts
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SparkPlus_Content_Generator {
    private $debug_log    = array();
    private $prompt_builder = null;

    public function __construct() {
        // Initialize prompt builder with debug callback.
        $this->prompt_builder = new SparkPlus_Prompt_Builder( array( $this, 'add_debug' ) );
    }
    
    public function add_debug($step, $data) {
        $this->debug_log[] = array(
            'step' => $step,
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
    }

    /**
     * Generate only text fields for a post.
     *
     * @param int $post_id Post ID.
     * @return array Result with success, debug_log.
     */
    public function generate_text_only( $post_id ) {
        $this->debug_log = array();

        try {
            $post = get_post( $post_id );
            if ( ! $post ) {
                throw new Exception( __( 'Post not found', 'sparkplus' ) );
            }

            $cpt_settings  = $this->get_cpt_settings( $post->post_type );
            $post_settings = $this->get_post_settings( $post_id );

            $texts_generated = $this->generate_text_content( $post_id, $cpt_settings, $post_settings );

            return array(
                'success'         => true,
                'post_id'         => $post_id,
                'texts_generated' => $texts_generated,
                'debug_log'       => $this->debug_log,
            );

        } catch ( Exception $e ) {
            $this->add_debug( 'Error in generate_text_only', array(
                'error_message' => $e->getMessage(),
            ) );
            return array(
                'success'   => false,
                'message'   => $e->getMessage(),
                'debug_log' => $this->debug_log,
            );
        }
    }

    /**
     * Generate a single image field for a post, identified by zero-based index.
     *
     * @param int  $post_id     Post ID.
     * @param int  $field_index Zero-based index into the image-field list.
     * @return array Result with success, debug_log.
     */
    public function generate_single_image( $post_id, $field_index ) {
        $this->debug_log = array();

        try {
            $post = get_post( $post_id );
            if ( ! $post ) {
                throw new Exception( __( 'Post not found', 'sparkplus' ) );
            }

            $cpt_settings  = $this->get_cpt_settings( $post->post_type );
            $post_settings = $this->get_post_settings( $post_id );

            // Build a stable, ordered list of image fields.
            $image_fields = array_values( array_filter( $cpt_settings['custom_fields'], function ( $f ) {
                return $f['type'] === 'image';
            } ) );

            if ( ! isset( $image_fields[ $field_index ] ) ) {
                throw new Exception( sprintf( 'Image field index %d not found', $field_index ) );
            }

            $field = $image_fields[ $field_index ];

            // 1. Build prompt for this specific image.
            $image_prompt = $this->prompt_builder->build_image_prompt( $cpt_settings, $post_settings, $field, $post_id );

            // 2. Prepare image options from field config.
            $image_options = array(
                'model'               => $cpt_settings['image_model'],
                'aspect_ratio'        => isset( $field['aspect_ratio'] )        ? $field['aspect_ratio']        : 'square',
                'gen_quality'         => isset( $field['gen_quality'] )         ? $field['gen_quality']         : 'medium',
                'output_resolution'   => isset( $field['output_resolution'] )   ? $field['output_resolution']   : 'medium',
                'reference_image_url' => isset( $field['reference_image_url'] ) ? $field['reference_image_url'] : '',
            );

            // 3. Make API call to generate the image.
            $image_provider = SparkPlus_API_Manager::make_image_provider( $cpt_settings['image_model'], array( $this, 'add_debug' ) );
            $image_response  = $image_provider->generate_image( $image_prompt, $image_options );

            if ( $image_response && isset( $image_response['data'][0]['b64_json'] ) ) {
                $webp_quality = isset( $field['webp_quality'] ) ? $field['webp_quality'] : 80;
                $webp_data    = sparkplus_convert_image_to_webp( $image_response['data'][0]['b64_json'], $webp_quality );

                if ( is_wp_error( $webp_data ) ) {
                    throw new Exception( sprintf(
                        esc_html__( 'Failed to convert image to WebP for field "%1$s": %2$s', 'sparkplus' ),
                        esc_html( $field['label'] ),
                        esc_html( $webp_data->get_error_message() )
                    ) );
                }

                $keyword       = $post_settings['keyword'];
                $filename      = sanitize_file_name( $keyword ) . '-' . time();
                $attachment_id = sparkplus_save_webp_to_media_library( $webp_data, $post_id, $filename, $keyword );

                if ( is_wp_error( $attachment_id ) ) {
                    throw new Exception( sprintf(
                        esc_html__( 'Failed to save WebP image for field "%1$s": %2$s', 'sparkplus' ),
                        esc_html( $field['label'] ),
                        esc_html( $attachment_id->get_error_message() )
                    ) );
                }

                $alt_text = $this->generate_and_set_alt_text( $attachment_id, $image_prompt, $cpt_settings['text_model'] );
                $this->update_post_with_image( $post_id, $field, $attachment_id );

                $this->add_debug( 'generate_single_image', array(
                    'field_index'   => $field_index,
                    'field_key'     => $field['key'],
                    'attachment_id' => $attachment_id,
                    'format'        => 'webp',
                    'webp_quality'  => $webp_quality,
                    'alt_text'      => $alt_text,
                    'success'       => true,
                ) );
            }

            return array(
                'success'   => true,
                'post_id'   => $post_id,
                'debug_log' => $this->debug_log,
            );

        } catch ( Exception $e ) {
            $this->add_debug( 'Error in generate_single_image', array(
                'error_message' => $e->getMessage(),
            ) );
            return array(
                'success'   => false,
                'message'   => $e->getMessage(),
                'debug_log' => $this->debug_log,
            );
        }
    }

    /**
     * Return metadata describing the generation work needed for a post.
     *
     * The client uses this to orchestrate the individual generation calls
     * (one text call + N image calls) without the server dictating workflow.
     *
     * @param int $post_id Post ID.
     * @return array { success, text_fields[], image_fields[], debug_log }
     */
    public function get_generation_meta( $post_id ) {
        $this->debug_log = array();

        try {
            $post = get_post( $post_id );
            if ( ! $post ) {
                throw new Exception( __( 'Post not found', 'sparkplus' ) );
            }

            // Build field lists directly — no validation, just data.
            global $sparkplus;
            $cpt_configs   = $sparkplus->get_cpt_configs();
            $user_settings    = isset( $cpt_configs[ $post->post_type ]['fields'] ) ? $cpt_configs[ $post->post_type ]['fields'] : array();
            $include_rankmath = (bool) get_option( 'sparkplus_seo_rankmath_enable', false );
            $include_slug     = (bool) get_option( 'sparkplus_seo_slug_enable', false );
            $all_fields       = $this->build_all_fields_map( $post->post_type, $include_rankmath, $include_slug );

            $text_fields  = array();
            $image_fields = array();
            $image_index  = 0;

            foreach ( $all_fields as $field_key => $field_data ) {
                if ( $field_data['type'] === 'group' ) {
                    $group_user = isset( $user_settings[ $field_key ] ) ? $user_settings[ $field_key ] : array();
                    foreach ( $field_data['sub_fields'] as $sub_key => $sub_data ) {
                        $sub_user = isset( $group_user['sub_fields'][ $sub_key ] ) ? $group_user['sub_fields'][ $sub_key ] : array();
                        if ( ! empty( $sub_user['enabled'] ) ) {
                            if ( $sub_data['type'] === 'image' ) {
                                $image_fields[] = array( 'index' => $image_index++, 'key' => $sub_key, 'label' => $sub_data['label'] );
                            } else {
                                $text_fields[] = array( 'key' => $sub_key, 'label' => $sub_data['label'] );
                            }
                        }
                    }
                } else {
                    $field_user = isset( $user_settings[ $field_key ] ) ? $user_settings[ $field_key ] : array();
                    if ( ! empty( $field_user['enabled'] ) ) {
                        if ( $field_data['type'] === 'image' ) {
                            $image_fields[] = array( 'index' => $image_index++, 'key' => $field_key, 'label' => $field_data['label'] );
                        } else {
                            $text_fields[] = array( 'key' => $field_key, 'label' => $field_data['label'] );
                        }
                    }
                }
            }

            $clear_fields = $this->get_clear_fields_config( $post->post_type );

            return array(
                'success'          => true,
                'text_fields'      => $text_fields,
                'image_fields'     => $image_fields,
                'has_clear_fields' => ! empty( $clear_fields ),
                'debug_log'        => $this->debug_log,
            );

        } catch ( Exception $e ) {
            $this->add_debug( 'Error in get_generation_meta', array(
                'error_message' => $e->getMessage(),
            ) );
            return array(
                'success'   => false,
                'message'   => $e->getMessage(),
                'debug_log' => $this->debug_log,
            );
        }
    }

    /**
     * Clear all fields marked with clear=true for a given post.
     *
     * @param int $post_id Post ID.
     * @return array Result with success, cleared_count, debug_log.
     */
    public function clear_fields( $post_id ) {
        $this->debug_log = array();

        try {
            $post = get_post( $post_id );
            if ( ! $post ) {
                throw new Exception( __( 'Post not found', 'sparkplus' ) );
            }

            $clear_fields = $this->get_clear_fields_config( $post->post_type );

            if ( empty( $clear_fields ) ) {
                $this->add_debug( 'clear_fields', 'No fields marked for clearing' );
                return array(
                    'success'       => true,
                    'cleared_count' => 0,
                    'debug_log'     => $this->debug_log,
                );
            }

            $wp_update  = array( 'ID' => $post_id );
            $cleared    = 0;
            $group_clears = array(); // group_key => [ sub_key, … ]

            foreach ( $clear_fields as $field ) {
                // Group sub-field
                if ( ! empty( $field['group_key'] ) ) {
                    if ( ! isset( $group_clears[ $field['group_key'] ] ) ) {
                        $group_clears[ $field['group_key'] ] = array();
                    }
                    $group_clears[ $field['group_key'] ][] = $field['key'];
                    $cleared++;
                    continue;
                }

                // WordPress core fields
                if ( $field['source'] === 'wordpress' ) {
                    switch ( $field['key'] ) {
                        case 'post_title':
                            $wp_update['post_title'] = '';
                            break;
                        case 'post_content':
                            $wp_update['post_content'] = '';
                            break;
                        case 'post_excerpt':
                            $wp_update['post_excerpt'] = '';
                            break;
                        case 'post_slug':
                            $wp_update['post_name'] = '';
                            break;
                        case '_thumbnail_id':
                            delete_post_thumbnail( $post_id );
                            break;
                    }
                    $cleared++;
                    continue;
                }

                // RankMath field — clear via post meta
                if ( $field['source'] === 'rankmath' ) {
                    update_post_meta( $post_id, $field['key'], '' );
                    $cleared++;
                    continue;
                }

                // ACF image field — set to empty
                if ( in_array( $field['type'], array( 'image', 'file', 'gallery' ), true ) ) {
                    if ( function_exists( 'update_field' ) ) {
                        update_field( $field['key'], '', $post_id );
                    }
                    $cleared++;
                    continue;
                }

                // ACF text field — set to empty string
                if ( function_exists( 'update_field' ) ) {
                    update_field( $field['key'], '', $post_id );
                }
                $cleared++;
            }

            // Batch-update WordPress core fields
            if ( count( $wp_update ) > 1 ) {
                $result = wp_update_post( $wp_update, true );
                if ( is_wp_error( $result ) ) {
                    throw new Exception( __( 'Failed to clear WordPress fields: ', 'sparkplus' ) . $result->get_error_message() );
                }
            }

            // Clear group sub-fields via read-merge-write
            if ( ! empty( $group_clears ) && function_exists( 'update_field' ) ) {
                foreach ( $group_clears as $group_key => $sub_keys ) {
                    $existing = get_field( $group_key, $post_id );
                    if ( ! is_array( $existing ) ) {
                        $existing = array();
                    }
                    foreach ( $sub_keys as $sub_key ) {
                        $existing[ $sub_key ] = '';
                    }
                    update_field( $group_key, $existing, $post_id );
                }
            }

            $this->add_debug( 'clear_fields', array(
                'post_id'       => $post_id,
                'cleared_count' => $cleared,
                'fields'        => array_map( function( $f ) { return $f['key']; }, $clear_fields ),
            ) );

            return array(
                'success'       => true,
                'cleared_count' => $cleared,
                'debug_log'     => $this->debug_log,
            );

        } catch ( Exception $e ) {
            $this->add_debug( 'Error in clear_fields', array(
                'error_message' => $e->getMessage(),
            ) );
            return array(
                'success'   => false,
                'message'   => $e->getMessage(),
                'debug_log' => $this->debug_log,
            );
        }
    }

    /**
     * Build the list of fields marked clear=true for a post type.
     * Backend safety: if both enabled and clear are true, ignore clear.
     */
    private function get_clear_fields_config( $post_type ) {
        global $sparkplus;
        $cpt_configs   = $sparkplus->get_cpt_configs();
        $user_settings    = isset( $cpt_configs[ $post_type ]['fields'] ) ? $cpt_configs[ $post_type ]['fields'] : array();
        $include_rankmath = (bool) get_option( 'sparkplus_seo_rankmath_enable', false );
        $include_slug     = (bool) get_option( 'sparkplus_seo_slug_enable', false );

        $all_fields = $this->build_all_fields_map( $post_type, $include_rankmath, $include_slug );
        $clear_list = array();

        foreach ( $all_fields as $field_key => $field_data ) {
            if ( $field_data['type'] === 'group' ) {
                $group_user = isset( $user_settings[ $field_key ] ) ? $user_settings[ $field_key ] : array();
                foreach ( $field_data['sub_fields'] as $sub_key => $sub_data ) {
                    $sub_user  = isset( $group_user['sub_fields'][ $sub_key ] ) ? $group_user['sub_fields'][ $sub_key ] : array();
                    $enabled   = ! empty( $sub_user['enabled'] );
                    $clear     = ! empty( $sub_user['clear'] );
                    // Generate takes precedence
                    if ( $clear && ! $enabled ) {
                        $clear_list[] = array_merge( $sub_data, array(
                            'group_key' => $field_key,
                        ) );
                    }
                }
            } else {
                $field_user = isset( $user_settings[ $field_key ] ) ? $user_settings[ $field_key ] : array();
                $enabled    = ! empty( $field_user['enabled'] );
                $clear      = ! empty( $field_user['clear'] );
                if ( $clear && ! $enabled ) {
                    $clear_list[] = $field_data;
                }
            }
        }

        return $clear_list;
    }

    /**
     * Build the full map of all available fields (WP + ACF + RankMath) for a post type.
     * Shared by get_custom_fields_config and get_clear_fields_config.
     *
     * @param string $post_type       Post type slug.
     * @param bool   $include_rankmath Whether to append RankMath SEO fields.
     */
    private function build_all_fields_map( $post_type, $include_rankmath = false, $include_slug = false ) {
        $all_fields = array();

        // WordPress baseline fields
        $baseline_fields = array(
            'post_title'   => array( 'label' => 'Title',          'type' => 'text' ),
            'post_content' => array( 'label' => 'Content',        'type' => 'wysiwyg' ),
            'post_excerpt' => array( 'label' => 'Excerpt',        'type' => 'text' ),
            '_thumbnail_id' => array( 'label' => 'Featured Image', 'type' => 'image' ),
        );

        foreach ( $baseline_fields as $field_key => $field_info ) {
            $all_fields[ $field_key ] = array(
                'key'    => $field_key,
                'label'  => $field_info['label'],
                'type'   => $field_info['type'],
                'source' => 'wordpress',
            );
        }

        // ACF fields
        if ( function_exists( 'acf_get_field_groups' ) ) {
            $field_groups = acf_get_field_groups( array( 'post_type' => $post_type ) );
            if ( ! empty( $field_groups ) ) {
                foreach ( $field_groups as $group ) {
                    $fields = acf_get_fields( $group['key'] );
                    if ( $fields ) {
                        foreach ( $fields as $field ) {
                            if ( $field['type'] === 'group' ) {
                                $sub_fields = array();
                                if ( ! empty( $field['sub_fields'] ) ) {
                                    foreach ( $field['sub_fields'] as $sub_field ) {
                                        $sub_fields[ $sub_field['name'] ] = array(
                                            'key'              => $sub_field['name'],
                                            'label'            => $sub_field['label'],
                                            'type'             => $sub_field['type'],
                                            'source'           => 'acf',
                                            'group_key'        => $field['name'],
                                            'group_label'      => $field['label'],
                                            'acf_instructions' => isset( $sub_field['instructions'] ) ? $sub_field['instructions'] : '',
                                        );
                                    }
                                }
                                $all_fields[ $field['name'] ] = array(
                                    'key'        => $field['name'],
                                    'label'      => $field['label'],
                                    'type'       => 'group',
                                    'source'     => 'acf',
                                    'sub_fields' => $sub_fields,
                                );
                            } else {
                                $all_fields[ $field['name'] ] = array(
                                    'key'              => $field['name'],
                                    'label'            => $field['label'],
                                    'type'             => $field['type'],
                                    'source'           => 'acf',
                                    'acf_instructions' => isset( $field['instructions'] ) ? $field['instructions'] : '',
                                );
                            }
                        }
                    }
                }
            }
        }

        // URL Slug field
        if ( $include_slug ) {
            $all_fields['post_slug'] = array(
                'key'    => 'post_slug',
                'label'  => 'URL Slug',
                'type'   => 'text',
                'source' => 'wordpress',
            );
        }

        // RankMath SEO fields
        if ( $include_rankmath && defined( 'RANK_MATH_VERSION' ) ) {
            $rankmath_field_defs = array(
                'rank_math_title'       => array( 'label' => 'SEO Title',       'type' => 'text' ),
                'rank_math_description' => array( 'label' => 'SEO Description', 'type' => 'textarea' ),
            );
            foreach ( $rankmath_field_defs as $rm_key => $rm_info ) {
                $all_fields[ $rm_key ] = array(
                    'key'    => $rm_key,
                    'label'  => $rm_info['label'],
                    'type'   => $rm_info['type'],
                    'source' => 'rankmath',
                );
            }
        }

        return $all_fields;
    }

    private function get_cpt_settings($post_type) {
        // Get CPT-specific additional context and settings from consolidated configs
        $cpt_additional_context_text = '';
        $cpt_additional_context_image = '';
        $include_existing_content = true; // Default to true
        $include_acf_instructions = false; // Default to false
        global $sparkplus;
        if ($sparkplus && method_exists($sparkplus, 'get_cpt_configs')) {
            $cpt_configs = $sparkplus->get_cpt_configs();
            if (isset($cpt_configs[$post_type]['additional_context_text'])) {
                $cpt_additional_context_text = $cpt_configs[$post_type]['additional_context_text'];
            }
            if (isset($cpt_configs[$post_type]['additional_context_image'])) {
                $cpt_additional_context_image = $cpt_configs[$post_type]['additional_context_image'];
            }
            if (isset($cpt_configs[$post_type]['include_existing_content'])) {
                $include_existing_content = (bool) $cpt_configs[$post_type]['include_existing_content'];
            }
            if (isset($cpt_configs[$post_type]['include_acf_instructions'])) {
                $include_acf_instructions = (bool) $cpt_configs[$post_type]['include_acf_instructions'];
            }
        }
        
        $settings = array(
            // API Settings
            'text_model'  => get_option( 'sparkplus_text_model',  sparkplus_get_supported_models()['text']['default'] ),
            'image_model' => get_option( 'sparkplus_image_model', sparkplus_get_supported_models()['image']['default'] ),
            
            // Post Type
            'post_type' => $post_type,
            
            // WordPress Site Language
            'language' => substr(get_locale(), 0, 2),
            
            // General Context Information
            'addressing' => get_option('sparkplus_addressing', 'formal'),
            'company_name' => get_option('sparkplus_company_name', ''),
            'industry' => get_option('sparkplus_industry', ''),
            'target_group' => get_option('sparkplus_target_group', ''),
            'usp' => get_option('sparkplus_usp', ''),
            'advantages' => get_option('sparkplus_advantages', ''),
            'buying_reasons' => get_option('sparkplus_buying_reasons', ''),
            
            // Custom Fields for this post type
            'custom_fields' => $this->get_custom_fields_config($post_type),
            
            // Two levels of additional context (General Context + CPT), split by text/image
            'general_context_additional_context_text' => get_option('sparkplus_additional_context_text', ''),
            'general_context_additional_context_image' => get_option('sparkplus_additional_context_image', ''),
            'cpt_additional_context_text' => $cpt_additional_context_text,
            'cpt_additional_context_image' => $cpt_additional_context_image,
            
            // Include existing content setting
            'include_existing_content' => $include_existing_content,

            // Include ACF instruction fields in prompt
            'include_acf_instructions' => $include_acf_instructions
        );
        
        $this->add_debug('get_cpt_settings', array(
            'post_type' => $settings['post_type'],
            'language' => $settings['language'],
            'company_name' => $settings['company_name'],
            'industry' => $settings['industry'],
            'custom_fields_count' => count($settings['custom_fields']),
            'has_general_context_text' => !empty($settings['general_context_additional_context_text']),
            'has_general_context_image' => !empty($settings['general_context_additional_context_image']),
            'has_cpt_context_text' => !empty($settings['cpt_additional_context_text']),
            'has_cpt_context_image' => !empty($settings['cpt_additional_context_image'])
        ));
        
        // Validate required settings
        if (empty($settings['post_type'])) {
            throw new Exception(esc_html__('No post type selected. Please configure in the settings.', 'sparkplus'));
        }

        // Validate that the saved models are still supported
        if ( sparkplus_get_model_provider( $settings['text_model'], 'text' ) === null ) {
            throw new Exception( sprintf(
                /* translators: %s: deprecated model name */
                __( 'The text generation model "%s" is no longer supported. Please go to Settings → API and select a current model, then save.', 'sparkplus' ),
                $settings['text_model']
            ) );
        }
        if ( sparkplus_get_model_provider( $settings['image_model'], 'image' ) === null ) {
            throw new Exception( sprintf(
                /* translators: %s: deprecated model name */
                __( 'The image generation model "%s" is no longer supported. Please go to Settings → API and select a current model, then save.', 'sparkplus' ),
                $settings['image_model']
            ) );
        }

        return $settings;
    }
    
    private function get_post_settings($post_id) {
        $settings = array(
            'keyword' => get_post_meta($post_id, 'sparkplus_keyword', true),
            'post_additional_context' => get_post_meta($post_id, 'sparkplus_additional_context', true)
        );
        
        $this->add_debug('get_post_settings', array(
            'post_id' => $post_id,
            'keyword' => $settings['keyword'],
            'has_post_context' => !empty($settings['post_additional_context'])
        ));
        
        // Validate keyword exists
        if (empty($settings['keyword'])) {
            throw new Exception(esc_html__('No keyword found for this post', 'sparkplus'));
        }
        
        return $settings;
    }
    
    private function get_custom_fields_config($post_type) {
        $this->add_debug('get_custom_fields_config', "Retrieving field configuration for post type: {$post_type}");
        
        global $sparkplus;
        $cpt_configs = $sparkplus->get_cpt_configs();
        $user_settings    = isset($cpt_configs[$post_type]['fields']) ? $cpt_configs[$post_type]['fields'] : array();
        $include_rankmath = (bool) get_option( 'sparkplus_seo_rankmath_enable', false );
        $include_slug     = (bool) get_option( 'sparkplus_seo_slug_enable', false );
        
        $all_fields = $this->build_all_fields_map( $post_type, $include_rankmath, $include_slug );
        
        // Overlay user settings onto existing fields (left join)
        $enabled_fields = array();
        
        foreach ($all_fields as $field_key => $field_data) {
            if ($field_data['type'] === 'group') {
                // For groups, check each sub-field individually
                $group_user = isset($user_settings[$field_key]) ? $user_settings[$field_key] : array();
                foreach ($field_data['sub_fields'] as $sub_key => $sub_data) {
                    $sub_user = isset($group_user['sub_fields'][$sub_key]) ? $group_user['sub_fields'][$sub_key] : array();
                    if (!empty($sub_user['enabled'])) {
                        $enabled_fields[] = array_merge($sub_data, array(
                            'description'         => isset($sub_user['description'])         ? $sub_user['description']               : '',
                            'word_count'          => isset($sub_user['word_count'])          ? intval($sub_user['word_count'])         : 0,
                            'aspect_ratio'        => isset($sub_user['aspect_ratio'])        ? $sub_user['aspect_ratio']              : 'square',
                            'gen_quality'         => isset($sub_user['gen_quality'])         ? $sub_user['gen_quality']               : 'medium',
                            'output_resolution'   => isset($sub_user['output_resolution'])   ? $sub_user['output_resolution']         : 'medium',
                            'webp_quality'        => isset($sub_user['webp_quality'])        ? intval($sub_user['webp_quality'])      : 80,
                            'reference_image_url' => isset($sub_user['reference_image_url']) ? $sub_user['reference_image_url']       : '',
                            'enabled'             => true,
                        ));
                    }
                }
            } else {
                // Regular field
                if (isset($user_settings[$field_key]) && !empty($user_settings[$field_key]['enabled'])) {
                    $enabled_fields[] = array_merge($field_data, array(
                        'description'         => isset($user_settings[$field_key]['description'])         ? $user_settings[$field_key]['description']               : '',
                        'word_count'          => isset($user_settings[$field_key]['word_count'])          ? intval($user_settings[$field_key]['word_count'])         : 0,
                        'aspect_ratio'        => isset($user_settings[$field_key]['aspect_ratio'])        ? $user_settings[$field_key]['aspect_ratio']              : 'square',
                        'gen_quality'         => isset($user_settings[$field_key]['gen_quality'])         ? $user_settings[$field_key]['gen_quality']               : 'medium',
                        'output_resolution'   => isset($user_settings[$field_key]['output_resolution'])   ? $user_settings[$field_key]['output_resolution']         : 'medium',
                        'webp_quality'        => isset($user_settings[$field_key]['webp_quality'])        ? intval($user_settings[$field_key]['webp_quality'])      : 80,
                        'reference_image_url' => isset($user_settings[$field_key]['reference_image_url']) ? $user_settings[$field_key]['reference_image_url']       : '',
                        'enabled'             => true,
                    ));
                }
            }
        }
        
        if (empty($enabled_fields)) {
            $this->add_debug('get_custom_fields_config', "No enabled fields found for post type: {$post_type}");
            throw new Exception(
                sprintf(
                    /* translators: %s: post type name */
                    esc_html__('No enabled fields found for post type: %s', 'sparkplus'),
                    esc_html($post_type)
                )
            );
        }
        
        $this->add_debug('get_custom_fields_config', array(
            'post_type' => $post_type,
            'total_existing_fields' => count($all_fields),
            'enabled_fields' => count($enabled_fields),
            'wordpress_fields' => count(array_filter($enabled_fields, function($f) { return $f['source'] === 'wordpress'; })),
            'acf_fields' => count(array_filter($enabled_fields, function($f) { return $f['source'] === 'acf'; }))
        ));
        
        return $enabled_fields;
    }
    
    private function generate_text_content($post_id, $cpt_settings, $post_settings) {
        // Build the text generation prompt
        $text_prompt = $this->prompt_builder->build_text_prompt($cpt_settings, $post_settings, $post_id);
        
        // Generate text content if we have text fields
        if (empty($text_prompt)) {
            return 0;
        }
        
        $text_provider = SparkPlus_API_Manager::make_text_provider( $cpt_settings['text_model'], array( $this, 'add_debug' ) );
        $text_response = $text_provider->generate_text( $text_prompt, array(
            'model' => $cpt_settings['text_model'],
            'step'  => 'generate_text',
        ) );
        
        // Parse JSON response
        $parsed_content = json_decode($text_response['content'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log the raw response for debugging
            $this->add_debug('JSON Parse Error', array(
                'error' => json_last_error_msg(),
                'raw_response' => $text_response['content'],
                'response_length' => strlen($text_response['content']),
                'first_100_chars' => substr($text_response['content'], 0, 100),
                'last_100_chars' => substr($text_response['content'], -100)
            ));
            
            throw new Exception(esc_html__('Failed to parse GPT response: ', 'sparkplus') . esc_html(json_last_error_msg()));
        }
        
        // Update post with generated text content and return count
        return $this->update_post_with_texts($post_id, $parsed_content, $cpt_settings['custom_fields']);
    }
    
    /**
     * Update post with generated image
     * 
     * @param int $post_id The post ID
     * @param array $field The field configuration
     * @param int $attachment_id The attachment ID
     */
    private function update_post_with_image($post_id, $field, $attachment_id) {
        // Handle featured image (_thumbnail_id)
        if ($field['key'] === '_thumbnail_id') {
            set_post_thumbnail($post_id, $attachment_id);
            
            $this->add_debug('update_post_with_image', array(
                'action' => 'set_featured_image',
                'post_id' => $post_id,
                'attachment_id' => $attachment_id
            ));
            return;
        }
        
        // Handle ACF image fields
        if ($field['source'] === 'acf' && function_exists('update_field')) {
            // Group sub-field: read-merge-write to avoid wiping sibling sub-fields
            if (!empty($field['group_key'])) {
                $group_key      = $field['group_key'];
                $existing_value = get_field($group_key, $post_id);
                if (!is_array($existing_value)) {
                    $existing_value = array();
                }
                $existing_value[$field['key']] = $attachment_id;
                $updated = update_field($group_key, $existing_value, $post_id);

                $this->add_debug('update_post_with_image', array(
                    'action'        => 'update_acf_group_image_field',
                    'group_key'     => $group_key,
                    'field_key'     => $field['key'],
                    'post_id'       => $post_id,
                    'attachment_id' => $attachment_id,
                    'success'       => $updated
                ));
            } else {
                $updated = update_field($field['key'], $attachment_id, $post_id);

                $this->add_debug('update_post_with_image', array(
                    'action'        => 'update_acf_field',
                    'field_key'     => $field['key'],
                    'post_id'       => $post_id,
                    'attachment_id' => $attachment_id,
                    'success'       => $updated
                ));
            }
        }
    }
    
    /**
     * Generate and set alt text for an image attachment
     * 
     * @param int    $attachment_id The attachment ID
     * @param string $image_prompt  The original image generation prompt
     * @param string $model         The text model to use
     * @return string|null The generated alt text, or null if failed
     */
    private function generate_and_set_alt_text( $attachment_id, $image_prompt, $model ) {
        // Build a prompt to generate concise alt text with clear length requirement
        $alt_text_prompt = "Based on this image description, generate a concise and descriptive alt text for web accessibility and SEO.\n\n";
        $alt_text_prompt .= "Image description:\n" . $image_prompt . "\n\n";
        $alt_text_prompt .= "IMPORTANT: The alt text must be 125 characters or less. Be specific, descriptive, and concise.\n\n";
        $alt_text_prompt .= "Respond with a JSON object containing a single 'alt_text' key with the value.";
        
        try {
            // Make API call to generate alt text (always use JSON format)
            $alt_provider = SparkPlus_API_Manager::make_text_provider( $model, array( $this, 'add_debug' ) );
            $response     = $alt_provider->generate_text( $alt_text_prompt, array(
                'model' => $model,
                'step'  => 'generate_alt_text',
            ) );
            
            // Parse JSON response
            $parsed = json_decode($response['content'], true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !isset($parsed['alt_text'])) {
                throw new Exception('Failed to parse alt text JSON response: ' . json_last_error_msg());
            }
            
            $alt_text = trim($parsed['alt_text']);
            
            // Remove any quotes that the AI might have added
            $alt_text = trim($alt_text, '"\'');
            
            // Limit to 125 characters for best practice
            if (strlen($alt_text) > 125) {
                $alt_text = substr($alt_text, 0, 122) . '...';
            }
            
            // Update the attachment's alt text
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
            
            $this->add_debug('generate_and_set_alt_text', array(
                'attachment_id' => $attachment_id,
                'alt_text' => $alt_text,
                'success' => true
            ));
            
            return $alt_text;
            
        } catch (Exception $e) {
            // Log the error but don't fail the entire image generation
            $this->add_debug('generate_and_set_alt_text', array(
                'attachment_id' => $attachment_id,
                'error' => $e->getMessage(),
                'success' => false
            ));
            
            return null;
        }
    }
    
    private function update_post_with_texts($post_id, $parsed_content, $custom_fields) {
        $this->add_debug('update_post_with_texts', 'Updating post with generated text content');
        
        if (empty($parsed_content)) {
            $this->add_debug('update_post_with_texts', 'No content to update');
            return 0;
        }
        
        // Separate WordPress baseline fields from ACF fields
        $wp_fields    = array();
        $acf_fields   = array();
        $group_fields = array(); // [ group_key => [ sub_key => value ] ]
        $rm_fields    = array();
        
        foreach ($custom_fields as $field) {
            // Skip image fields
            if (in_array($field['type'], array('image', 'file', 'gallery'))) {
                continue;
            }
            
            $field_key = $field['key'];
            
            // Group sub-field: value lives at parsed_content[group_key][sub_key]
            if (!empty($field['group_key'])) {
                $group_key = $field['group_key'];
                if (isset($parsed_content[$group_key][$field_key])) {
                    if (!isset($group_fields[$group_key])) {
                        $group_fields[$group_key] = array();
                    }
                    $group_fields[$group_key][$field_key] = $parsed_content[$group_key][$field_key];
                }
                continue;
            }
            
            // Check if we have content for this field
            if (!isset($parsed_content[$field_key])) {
                continue;
            }
            
            if ($field['source'] === 'wordpress') {
                $wp_fields[$field_key] = $parsed_content[$field_key];
            } elseif ($field['source'] === 'acf') {
                $acf_fields[$field_key] = $parsed_content[$field_key];
            } elseif ($field['source'] === 'rankmath') {
                $rm_fields[$field_key] = $parsed_content[$field_key];
            }
        }
        
        // Update WordPress baseline fields
        if (!empty($wp_fields)) {
            $post_data = array('ID' => $post_id);
            
            if (isset($wp_fields['post_title'])) {
                $post_data['post_title'] = $wp_fields['post_title'];
            }
            
            if (isset($wp_fields['post_content'])) {
                $post_data['post_content'] = $wp_fields['post_content'];
            }
            
            if (isset($wp_fields['post_excerpt'])) {
                $post_data['post_excerpt'] = $wp_fields['post_excerpt'];
            }

            if (isset($wp_fields['post_slug'])) {
                $post_data['post_name'] = sanitize_title( $wp_fields['post_slug'] );
            }
            
            $result = wp_update_post($post_data, true);
            
            if (is_wp_error($result)) {
                throw new Exception(esc_html__('Failed to update WordPress fields: ', 'sparkplus') . esc_html($result->get_error_message()));
            }
            
            $this->add_debug('update_post_with_texts', array(
                'wordpress_fields_updated' => array_keys($wp_fields)
            ));
        }
        
        // Update ACF fields
        if (!empty($acf_fields) && function_exists('update_field')) {
            foreach ($acf_fields as $field_key => $field_value) {
                $updated = update_field($field_key, $field_value, $post_id);
                
                if (!$updated) {
                    $this->add_debug('update_post_with_texts', array(
                        'warning' => "Failed to update ACF field: {$field_key}"
                    ));
                }
            }
            
            $this->add_debug('update_post_with_texts', array(
                'acf_fields_updated' => array_keys($acf_fields)
            ));
        }
        
        // Update ACF group fields (read-merge-write preserves non-enabled sub-fields)
        if (!empty($group_fields) && function_exists('update_field')) {
            foreach ($group_fields as $group_key => $new_sub_values) {
                $existing = get_field($group_key, $post_id);
                $merged   = is_array($existing) ? array_merge($existing, $new_sub_values) : $new_sub_values;
                update_field($group_key, $merged, $post_id);
            }
            $this->add_debug('update_post_with_texts', array(
                'group_fields_updated' => array_keys($group_fields)
            ));
        }
        
        // Update RankMath meta fields
        if (!empty($rm_fields)) {
            foreach ($rm_fields as $field_key => $field_value) {
                update_post_meta($post_id, $field_key, $field_value);
            }
            $this->add_debug('update_post_with_texts', array(
                'rankmath_fields_updated' => array_keys($rm_fields)
            ));
        }

        $total_updated = count($wp_fields) + count($acf_fields) + count($group_fields) + count($rm_fields);
        
        $this->add_debug('update_post_with_texts', array(
            'status' => 'completed',
            'total_fields_updated' => $total_updated
        ));
        
        return $total_updated;
    }
    
}
