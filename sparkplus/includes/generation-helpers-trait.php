<?php
/**
 * Generation Helpers Trait
 *
 * Shared helper methods used by both SparkPlus_Generation_Meta (data provider)
 * and SparkPlus_Generation_Saver (result saver).
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait SparkPlus_Generation_Helpers {

    public function add_debug( $step, $data ) {
        $this->debug_log[] = array(
            'step'      => $step,
            'data'      => $data,
            'timestamp' => current_time( 'mysql' ),
        );
    }

    /**
     * Build the full map of all available fields (WP + ACF + RankMath) for a post type.
     * Shared by get_custom_fields_config and get_clear_fields_config.
     *
     * @param string $post_type       Post type slug.
     * @param bool   $include_rankmath Whether to append RankMath SEO fields.
     * @param bool   $include_slug     Whether to append the URL Slug field.
     */
    private function build_all_fields_map( $post_type, $include_rankmath = false, $include_slug = false ) {
        $all_fields = array();

        // WordPress baseline fields
        $baseline_fields = array(
            'post_title'    => array( 'label' => 'Title',          'type' => 'text' ),
            'post_content'  => array( 'label' => 'Content',        'type' => 'wysiwyg' ),
            'post_excerpt'  => array( 'label' => 'Excerpt',        'type' => 'text' ),
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

    /**
     * Build the list of fields marked clear=true for a post type.
     * Backend safety: if both enabled and clear are true, ignore clear.
     */
    private function get_clear_fields_config( $post_type ) {
        global $sparkplus;
        $cpt_configs      = $sparkplus->get_cpt_configs();
        $user_settings    = isset( $cpt_configs[ $post_type ]['fields'] ) ? $cpt_configs[ $post_type ]['fields'] : array();
        $include_rankmath = (bool) get_option( 'sparkplus_seo_rankmath_enable', false );
        $include_slug     = (bool) get_option( 'sparkplus_seo_slug_enable', false );

        $all_fields = $this->build_all_fields_map( $post_type, $include_rankmath, $include_slug );
        $clear_list = array();

        foreach ( $all_fields as $field_key => $field_data ) {
            if ( $field_data['type'] === 'group' ) {
                $group_user = isset( $user_settings[ $field_key ] ) ? $user_settings[ $field_key ] : array();
                foreach ( $field_data['sub_fields'] as $sub_key => $sub_data ) {
                    $sub_user = isset( $group_user['sub_fields'][ $sub_key ] ) ? $group_user['sub_fields'][ $sub_key ] : array();
                    $enabled  = ! empty( $sub_user['enabled'] );
                    $clear    = ! empty( $sub_user['clear'] );
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
     * Overlay user-configured field settings onto the full fields map.
     * Returns only enabled fields with their generation configuration.
     * Shared by both SparkPlus_Generation_Meta and SparkPlus_Generation_Saver
     * because get_cpt_settings (used by both) depends on this.
     *
     * @param string $post_type
     * @return array
     */
    private function get_custom_fields_config( $post_type ) {
        $this->add_debug( 'get_custom_fields_config', "Retrieving field configuration for post type: {$post_type}" );

        global $sparkplus;
        $cpt_configs      = $sparkplus->get_cpt_configs();
        $user_settings    = isset( $cpt_configs[ $post_type ]['fields'] ) ? $cpt_configs[ $post_type ]['fields'] : array();
        $include_rankmath = (bool) get_option( 'sparkplus_seo_rankmath_enable', false );
        $include_slug     = (bool) get_option( 'sparkplus_seo_slug_enable', false );

        $all_fields = $this->build_all_fields_map( $post_type, $include_rankmath, $include_slug );

        // Overlay user settings onto existing fields (left join)
        $enabled_fields = array();

        foreach ( $all_fields as $field_key => $field_data ) {
            if ( $field_data['type'] === 'group' ) {
                $group_user = isset( $user_settings[ $field_key ] ) ? $user_settings[ $field_key ] : array();
                foreach ( $field_data['sub_fields'] as $sub_key => $sub_data ) {
                    $sub_user = isset( $group_user['sub_fields'][ $sub_key ] ) ? $group_user['sub_fields'][ $sub_key ] : array();
                    if ( ! empty( $sub_user['enabled'] ) ) {
                        $enabled_fields[] = array_merge( $sub_data, array(
                            'description'         => isset( $sub_user['description'] )         ? $sub_user['description']               : '',
                            'word_count'          => isset( $sub_user['word_count'] )          ? intval( $sub_user['word_count'] )       : 0,
                            'aspect_ratio'        => isset( $sub_user['aspect_ratio'] )        ? $sub_user['aspect_ratio']              : 'square',
                            'gen_quality'         => isset( $sub_user['gen_quality'] )         ? $sub_user['gen_quality']               : 'medium',
                            'output_resolution'   => isset( $sub_user['output_resolution'] )   ? $sub_user['output_resolution']         : 'medium',
                            'webp_quality'        => isset( $sub_user['webp_quality'] )        ? intval( $sub_user['webp_quality'] )    : 80,
                            'reference_image_url' => isset( $sub_user['reference_image_url'] ) ? $sub_user['reference_image_url']       : '',
                            'related_field'       => isset( $sub_user['related_field'] )       ? $sub_user['related_field']             : '',
                            'enabled'             => true,
                        ) );
                    }
                }
            } else {
                if ( isset( $user_settings[ $field_key ] ) && ! empty( $user_settings[ $field_key ]['enabled'] ) ) {
                    $enabled_fields[] = array_merge( $field_data, array(
                        'description'         => isset( $user_settings[ $field_key ]['description'] )         ? $user_settings[ $field_key ]['description']           : '',
                        'word_count'          => isset( $user_settings[ $field_key ]['word_count'] )          ? intval( $user_settings[ $field_key ]['word_count'] )   : 0,
                        'aspect_ratio'        => isset( $user_settings[ $field_key ]['aspect_ratio'] )        ? $user_settings[ $field_key ]['aspect_ratio']          : 'square',
                        'gen_quality'         => isset( $user_settings[ $field_key ]['gen_quality'] )         ? $user_settings[ $field_key ]['gen_quality']           : 'medium',
                        'output_resolution'   => isset( $user_settings[ $field_key ]['output_resolution'] )   ? $user_settings[ $field_key ]['output_resolution']     : 'medium',
                        'webp_quality'        => isset( $user_settings[ $field_key ]['webp_quality'] )        ? intval( $user_settings[ $field_key ]['webp_quality'] ) : 80,
                        'reference_image_url' => isset( $user_settings[ $field_key ]['reference_image_url'] ) ? $user_settings[ $field_key ]['reference_image_url']   : '',
                        'related_field'       => isset( $user_settings[ $field_key ]['related_field'] )       ? $user_settings[ $field_key ]['related_field']         : '',
                        'enabled'             => true,
                    ) );
                }
            }
        }

        if ( empty( $enabled_fields ) ) {
            $this->add_debug( 'get_custom_fields_config', "No enabled fields found for post type: {$post_type}" );
            throw new Exception(
                sprintf(
                    /* translators: %s: post type name */
                    esc_html__( 'No enabled fields found for post type: %s', 'sparkplus' ),
                    esc_html( $post_type )
                )
            );
        }

        $this->add_debug( 'get_custom_fields_config', array(
            'post_type'             => $post_type,
            'total_existing_fields' => count( $all_fields ),
            'enabled_fields'        => count( $enabled_fields ),
            'wordpress_fields'      => count( array_filter( $enabled_fields, function( $f ) { return $f['source'] === 'wordpress'; } ) ),
            'acf_fields'            => count( array_filter( $enabled_fields, function( $f ) { return $f['source'] === 'acf'; } ) ),
        ) );

        return $enabled_fields;
    }

    /**
     * Gather CPT-level and site-level settings for a given post type.
     *
     * @param string $post_type
     * @return array
     */
    private function get_cpt_settings( $post_type ) {
        $cpt_additional_context_text  = '';
        $cpt_additional_context_image = '';
        $include_existing_content     = true;
        $include_acf_instructions     = false;

        global $sparkplus;
        if ( $sparkplus && method_exists( $sparkplus, 'get_cpt_configs' ) ) {
            $cpt_configs = $sparkplus->get_cpt_configs();
            if ( isset( $cpt_configs[ $post_type ]['additional_context_text'] ) ) {
                $cpt_additional_context_text = $cpt_configs[ $post_type ]['additional_context_text'];
            }
            if ( isset( $cpt_configs[ $post_type ]['additional_context_image'] ) ) {
                $cpt_additional_context_image = $cpt_configs[ $post_type ]['additional_context_image'];
            }
            if ( isset( $cpt_configs[ $post_type ]['include_existing_content'] ) ) {
                $include_existing_content = (bool) $cpt_configs[ $post_type ]['include_existing_content'];
            }
            if ( isset( $cpt_configs[ $post_type ]['include_acf_instructions'] ) ) {
                $include_acf_instructions = (bool) $cpt_configs[ $post_type ]['include_acf_instructions'];
            }
        }

        $settings = array(
            // API Settings
            'text_model'  => get_option( 'sparkplus_text_model',  sparkplus_get_supported_models()['text']['default'] ),
            'image_model' => get_option( 'sparkplus_image_model', sparkplus_get_supported_models()['image']['default'] ),

            // Post Type
            'post_type' => $post_type,

            // WordPress Site Language
            'language' => substr( get_locale(), 0, 2 ),

            // General Context Information
            'addressing'    => get_option( 'sparkplus_addressing', 'formal' ),
            'company_name'  => get_option( 'sparkplus_company_name', '' ),
            'industry'      => get_option( 'sparkplus_industry', '' ),
            'target_group'  => get_option( 'sparkplus_target_group', '' ),
            'usp'           => get_option( 'sparkplus_usp', '' ),
            'advantages'    => get_option( 'sparkplus_advantages', '' ),
            'buying_reasons' => get_option( 'sparkplus_buying_reasons', '' ),

            // Custom Fields for this post type
            'custom_fields' => $this->get_custom_fields_config( $post_type ),

            // Two levels of additional context (General Context + CPT), split by text/image
            'general_context_additional_context_text'  => get_option( 'sparkplus_additional_context_text', '' ),
            'general_context_additional_context_image' => get_option( 'sparkplus_additional_context_image', '' ),
            'cpt_additional_context_text'              => $cpt_additional_context_text,
            'cpt_additional_context_image'             => $cpt_additional_context_image,

            // Include existing content setting
            'include_existing_content' => $include_existing_content,

            // Include ACF instruction fields in prompt
            'include_acf_instructions' => $include_acf_instructions,
        );

        $this->add_debug( 'get_cpt_settings', array(
            'post_type'                    => $settings['post_type'],
            'language'                     => $settings['language'],
            'company_name'                 => $settings['company_name'],
            'industry'                     => $settings['industry'],
            'custom_fields_count'          => count( $settings['custom_fields'] ),
            'has_general_context_text'     => ! empty( $settings['general_context_additional_context_text'] ),
            'has_general_context_image'    => ! empty( $settings['general_context_additional_context_image'] ),
            'has_cpt_context_text'         => ! empty( $settings['cpt_additional_context_text'] ),
            'has_cpt_context_image'        => ! empty( $settings['cpt_additional_context_image'] ),
        ) );

        // Validate required settings
        if ( empty( $settings['post_type'] ) ) {
            throw new Exception( esc_html__( 'No post type selected. Please configure in the settings.', 'sparkplus' ) );
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

    /**
     * Gather per-post settings (keyword, additional context).
     *
     * @param int $post_id
     * @return array
     */
    private function get_post_settings( $post_id ) {
        $settings = array(
            'keyword'              => get_post_meta( $post_id, 'sparkplus_keyword', true ),
            'post_additional_context' => get_post_meta( $post_id, 'sparkplus_additional_context', true ),
        );

        $this->add_debug( 'get_post_settings', array(
            'post_id'          => $post_id,
            'keyword'          => $settings['keyword'],
            'has_post_context' => ! empty( $settings['post_additional_context'] ),
        ) );

        if ( empty( $settings['keyword'] ) ) {
            throw new Exception( esc_html__( 'No keyword found for this post', 'sparkplus' ) );
        }

        return $settings;
    }
}
