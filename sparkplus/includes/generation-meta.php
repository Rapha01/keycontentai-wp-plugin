<?php
/**
 * Generation Meta Class
 *
 * Data-provider layer: gathers all settings, field configs, existing content,
 * and linking pool data that the browser needs to orchestrate AI generation.
 * No prompts are built and no AI API calls are made here — everything is
 * handled entirely on the client side.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SparkPlus_Generation_Meta {
    use SparkPlus_Generation_Helpers;

    private $debug_log = array();

    /**
     * Return all data the browser needs to orchestrate generation for a post.
     * The server only supplies raw settings, field configs, existing content,
     * and linking pool data — no prompts are built and no API params are
     * assembled here. All prompt building and provider orchestration happen
     * entirely on the client (see sparkplus-prompt-builder.js / sparkplus-providers.js).
     *
     * @param int $post_id Post ID.
     * @return array { success, post_settings, cpt_settings, api_keys,
     *                 text_provider, image_provider, text_fields[], image_fields[],
     *                 has_clear_fields, existing_content[], linking, wysiwyg_formatting,
     *                 debug_log }
     */
    public function get_generation_meta( $post_id ) {
        $this->debug_log = array();

        try {
            $post = get_post( $post_id );
            if ( ! $post ) {
                throw new Exception( __( 'Post not found', 'sparkplus' ) );
            }

            $cpt_settings  = $this->get_cpt_settings( $post->post_type );
            $post_settings = $this->get_post_settings( $post_id );

            // --- Split enabled fields (from get_custom_fields_config) into text and image lists ---
            // Each entry already carries the full user-configured settings (description, word_count, etc.).
            $text_fields  = array();
            $image_fields = array();
            $image_index  = 0;

            foreach ( $cpt_settings['custom_fields'] as $field ) {
                if ( $field['type'] === 'image' ) {
                    // Pre-fetch the reference image server-side to avoid browser CORS issues.
                    $ref_url = isset( $field['reference_image_url'] ) ? trim( $field['reference_image_url'] ) : '';
                    $field['reference_image'] = $this->get_reference_image_data( $ref_url );
                    unset( $field['reference_image_url'] );
                    $field['index'] = $image_index++;

                    // Resolve the linked text field's label + current value. The client
                    // refreshes the value with freshly-generated text (which is saved
                    // before images) before building the image prompt.
                    $related_key = isset( $field['related_field'] ) ? trim( $field['related_field'] ) : '';
                    if ( $related_key !== '' ) {
                        $related = $this->get_related_field_data( $post_id, $post->post_type, $related_key );
                        if ( $related ) {
                            $field['related_label'] = $related['label'];
                            $field['related_value'] = $related['value'];
                        }
                    }

                    $image_fields[] = $field;
                } else {
                    $text_fields[] = $field;
                }
            }

            $clear_fields = $this->get_clear_fields_config( $post->post_type );

            // --- Provider slugs (JS uses these to select the right provider class) ---
            $text_provider  = sparkplus_get_model_provider( $cpt_settings['text_model'],  'text'  );
            $image_provider = sparkplus_get_model_provider( $cpt_settings['image_model'], 'image' );

            // --- API keys for all providers ---
            $api_keys = array(
                'openai'    => $this->get_provider_api_key( 'openai' ),
                'anthropic' => $this->get_provider_api_key( 'anthropic' ),
                'gemini'    => $this->get_provider_api_key( 'gemini' ),
            );

            // --- Existing content (DB-dependent — built here, passed as data to JS) ---
            $existing_content = array();
            if ( ! empty( $cpt_settings['include_existing_content'] ) ) {
                $existing_content = $this->build_existing_content_data( $post_id, $post->post_type );
            }

            // --- Linking pool (DB-dependent — built here, passed as data to JS) ---
            $linking = $this->build_linking_data( $post_id );

            // --- WYSIWYG formatting options ---
            $wysiwyg_formatting = get_option( 'sparkplus_wysiwyg_formatting', array(
                'paragraphs' => true,
                'bold'       => true,
                'italic'     => true,
                'headings'   => false,
                'lists'      => true,
            ) );

            return array(
                'success'            => true,
                'post_settings'      => $post_settings,
                'cpt_settings'       => array(
                    'text_model'                               => $cpt_settings['text_model'],
                    'image_model'                              => $cpt_settings['image_model'],
                    'post_type'                                => $cpt_settings['post_type'],
                    'language'                                 => $cpt_settings['language'],
                    'addressing'                               => $cpt_settings['addressing'],
                    'company_name'                             => $cpt_settings['company_name'],
                    'industry'                                 => $cpt_settings['industry'],
                    'target_group'                             => $cpt_settings['target_group'],
                    'usp'                                      => $cpt_settings['usp'],
                    'advantages'                               => $cpt_settings['advantages'],
                    'buying_reasons'                           => $cpt_settings['buying_reasons'],
                    'general_context_additional_context_text'  => $cpt_settings['general_context_additional_context_text'],
                    'general_context_additional_context_image' => $cpt_settings['general_context_additional_context_image'],
                    'cpt_additional_context_text'              => $cpt_settings['cpt_additional_context_text'],
                    'cpt_additional_context_image'             => $cpt_settings['cpt_additional_context_image'],
                    'include_existing_content'                 => $cpt_settings['include_existing_content'],
                    'include_acf_instructions'                 => $cpt_settings['include_acf_instructions'],
                ),
                'text_provider'      => $text_provider,
                'image_provider'     => $image_provider,
                'api_keys'           => $api_keys,
                'text_fields'        => $text_fields,
                'image_fields'       => $image_fields,
                'has_clear_fields'   => ! empty( $clear_fields ),
                'existing_content'   => $existing_content,
                'linking'            => $linking,
                'wysiwyg_formatting' => $wysiwyg_formatting,
                'debug_log'          => $this->debug_log,
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

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build a flat array of existing post field values for prompt context.
     * Returns [{label, value}] for all text-based fields that have content.
     * Called server-side because ACF get_field() requires PHP.
     *
     * @param int    $post_id
     * @param string $post_type
     * @return array
     */
    private function build_existing_content_data( $post_id, $post_type ) {
        $entries = array();
        $post    = get_post( $post_id );
        if ( ! $post ) {
            return $entries;
        }

        // WordPress baseline fields
        if ( ! empty( $post->post_title ) ) {
            $entries[] = array( 'label' => 'Title',   'value' => $post->post_title );
        }
        if ( ! empty( $post->post_excerpt ) ) {
            $entries[] = array( 'label' => 'Excerpt', 'value' => $post->post_excerpt );
        }
        if ( ! empty( $post->post_content ) ) {
            $entries[] = array( 'label' => 'Content', 'value' => wp_strip_all_tags( $post->post_content ) );
        }

        // ACF fields
        if ( function_exists( 'acf_get_field_groups' ) ) {
            $text_based_types = array( 'text', 'textarea', 'wysiwyg', 'url', 'email' );
            $field_groups     = acf_get_field_groups( array( 'post_type' => $post_type ) );

            foreach ( $field_groups as $group ) {
                $fields = acf_get_fields( $group['key'] );
                if ( ! $fields ) {
                    continue;
                }
                foreach ( $fields as $field ) {
                    if ( $field['type'] === 'group' && ! empty( $field['sub_fields'] ) ) {
                        $group_value = get_field( $field['name'], $post_id );
                        if ( ! is_array( $group_value ) ) {
                            continue;
                        }
                        foreach ( $field['sub_fields'] as $sub_field ) {
                            if ( ! in_array( $sub_field['type'], $text_based_types, true ) ) {
                                continue;
                            }
                            if ( empty( $group_value[ $sub_field['name'] ] ) ) {
                                continue;
                            }
                            $clean = wp_strip_all_tags( (string) $group_value[ $sub_field['name'] ] );
                            if ( ! empty( $clean ) ) {
                                $entries[] = array(
                                    'label' => $field['label'] . ' › ' . $sub_field['label'],
                                    'value' => $clean,
                                );
                            }
                        }
                    } elseif ( in_array( $field['type'], $text_based_types, true ) ) {
                        $value = get_field( $field['name'], $post_id );
                        if ( ! empty( $value ) ) {
                            $clean = wp_strip_all_tags( (string) $value );
                            if ( ! empty( $clean ) ) {
                                $entries[] = array( 'label' => $field['label'], 'value' => $clean );
                            }
                        }
                    }
                }
            }
        }

        return $entries;
    }

    /**
     * Build the linking pool data structure for the client.
     * Returns null when linking is disabled or the pool is empty.
     * Called server-side because it needs to query the WordPress database.
     *
     * @param int $post_id  Current post ID (excluded from pool).
     * @return array|null { enabled, wysiwyg, pool[] } or null.
     */
    private function build_linking_data( $post_id ) {
        if ( ! get_option( 'sparkplus_linking_enable', false ) ) {
            return null;
        }

        $pool_json = get_option( 'sparkplus_linking_pool', '' );
        if ( empty( $pool_json ) ) {
            return null;
        }
        $raw_pool = json_decode( $pool_json, true );
        if ( ! is_array( $raw_pool ) ) {
            return null;
        }

        $pool = array();

        if ( ! empty( $raw_pool['post_types'] ) ) {
            foreach ( $raw_pool['post_types'] as $post_type ) {
                $posts = get_posts( array( 'post_type' => $post_type, 'posts_per_page' => -1, 'post_status' => 'publish', 'exclude' => array( $post_id ) ) );
                foreach ( $posts as $p ) {
                    $pool[] = array( 'id' => $p->ID, 'keyword' => (string) get_post_meta( $p->ID, 'sparkplus_keyword', true ), 'title' => $p->post_title, 'url' => get_permalink( $p->ID ) );
                }
            }
        }

        if ( ! empty( $raw_pool['single_items'] ) ) {
            foreach ( $raw_pool['single_items'] as $item ) {
                if ( (int) $item['id'] === (int) $post_id ) { continue; }
                $p = get_post( $item['id'] );
                if ( $p && $p->post_status === 'publish' ) {
                    $pool[] = array( 'id' => $p->ID, 'keyword' => (string) get_post_meta( $p->ID, 'sparkplus_keyword', true ), 'title' => $p->post_title, 'url' => get_permalink( $p->ID ) );
                }
            }
        }

        if ( ! empty( $raw_pool['custom_links'] ) ) {
            foreach ( $raw_pool['custom_links'] as $link ) {
                $keywords = ( ! empty( $link['keywords'] ) && is_array( $link['keywords'] ) ) ? implode( ', ', $link['keywords'] ) : '';
                $pool[]   = array( 'id' => 0, 'keyword' => $keywords, 'title' => $link['title'], 'url' => $link['url'] );
            }
        }

        return array(
            'enabled' => true,
            'wysiwyg' => (bool) get_option( 'sparkplus_linking_wysiwyg', false ),
            'pool'    => $pool,
        );
    }

    /**
     * Resolve a linked text field's label and current saved value for a post.
     * Handles WordPress core fields, ACF fields, ACF group sub-fields ("group::sub"),
     * RankMath meta, and the URL slug. Returns null for an empty key.
     *
     * The returned value is what is currently stored in the database; the client
     * overrides it with freshly-generated text when the linked field is generated
     * in the same run (text is generated and saved before any image).
     *
     * @param int    $post_id
     * @param string $post_type
     * @param string $related_key Field key, or "group_key::sub_key" for group sub-fields.
     * @return array|null { label, value } or null.
     */
    private function get_related_field_data( $post_id, $post_type, $related_key ) {
        if ( empty( $related_key ) ) {
            return null;
        }

        $include_rankmath = (bool) get_option( 'sparkplus_seo_rankmath_enable', false );
        $include_slug     = (bool) get_option( 'sparkplus_seo_slug_enable', false );
        $all_fields       = $this->build_all_fields_map( $post_type, $include_rankmath, $include_slug );
        $post             = get_post( $post_id );

        // Group sub-field: "group_key::sub_key"
        if ( strpos( $related_key, '::' ) !== false ) {
            list( $group_key, $sub_key ) = array_pad( explode( '::', $related_key, 2 ), 2, '' );

            $label = $sub_key;
            if ( isset( $all_fields[ $group_key ]['sub_fields'][ $sub_key ] ) ) {
                $label = $all_fields[ $group_key ]['label'] . ' › ' . $all_fields[ $group_key ]['sub_fields'][ $sub_key ]['label'];
            }

            $value = '';
            if ( function_exists( 'get_field' ) ) {
                $group_value = get_field( $group_key, $post_id );
                if ( is_array( $group_value ) && isset( $group_value[ $sub_key ] ) ) {
                    $value = wp_strip_all_tags( (string) $group_value[ $sub_key ] );
                }
            }

            return array( 'label' => $label, 'value' => $value );
        }

        // Top-level field
        $label = isset( $all_fields[ $related_key ]['label'] ) ? $all_fields[ $related_key ]['label'] : $related_key;
        $value = '';

        switch ( $related_key ) {
            case 'post_title':
                $value = $post ? $post->post_title : '';
                break;
            case 'post_excerpt':
                $value = $post ? $post->post_excerpt : '';
                break;
            case 'post_content':
                $value = $post ? wp_strip_all_tags( $post->post_content ) : '';
                break;
            case 'post_slug':
                $value = $post ? $post->post_name : '';
                break;
            case 'rank_math_title':
            case 'rank_math_description':
                $value = (string) get_post_meta( $post_id, $related_key, true );
                break;
            default:
                if ( function_exists( 'get_field' ) ) {
                    $acf_value = get_field( $related_key, $post_id );
                    $value     = is_scalar( $acf_value ) ? wp_strip_all_tags( (string) $acf_value ) : '';
                }
                break;
        }

        return array( 'label' => $label, 'value' => (string) $value );
    }

    /**
     * Fetch a reference image URL and return it as inline base64 data.
     * Returns null when the URL is empty or the fetch fails.
     * Called server-side to avoid browser CORS restrictions on arbitrary image URLs.
     *
     * @param string $url
     * @return array|null { mime_type, b64_data } or null.
     */
    private function get_reference_image_data( $url ) {
        if ( empty( $url ) ) {
            return null;
        }
        $mime_map = array( 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp' );
        $ext      = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
        $response = wp_remote_get( $url, array( 'timeout' => 30 ) );
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return null;
        }
        return array(
            'mime_type' => isset( $mime_map[ $ext ] ) ? $mime_map[ $ext ] : 'image/jpeg',
            'b64_data'  => base64_encode( wp_remote_retrieve_body( $response ) ),
        );
    }

    /**
     * Return the API key for an AI provider.
     *
     * @param string $provider  'openai' | 'anthropic' | 'gemini'
     * @return string
     */
    private function get_provider_api_key( $provider ) {
        $key_map = array(
            'openai'    => 'sparkplus_openai_api_key',
            'anthropic' => 'sparkplus_anthropic_api_key',
            'gemini'    => 'sparkplus_gemini_api_key',
        );
        $option = isset( $key_map[ $provider ] ) ? $key_map[ $provider ] : '';
        return $option ? get_option( $option, '' ) : '';
    }
}
