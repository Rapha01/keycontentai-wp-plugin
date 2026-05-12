<?php
/**
 * Generation Saver Class
 *
 * Result-saver layer: receives AI-generated content returned by the browser's
 * direct API calls and persists it to the WordPress database (post fields,
 * ACF fields, RankMath meta, and the media library for images).
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SparkPlus_Generation_Saver {
    use SparkPlus_Generation_Helpers;

    private $debug_log = array();

    /**
     * Save the raw JSON text returned by the browser's AI API call to the post fields.
     *
     * @param int    $post_id      Post ID.
     * @param string $content_json Raw JSON string from the LLM (e.g. {"post_title":"..."}).
     * @return array { success, fields_updated, debug_log }
     */
    public function save_text_to_post( $post_id, $content_json ) {
        $this->debug_log = array();

        try {
            $post = get_post( $post_id );
            if ( ! $post ) {
                throw new Exception( __( 'Post not found', 'sparkplus' ) );
            }

            $cpt_settings   = $this->get_cpt_settings( $post->post_type );
            $parsed_content = json_decode( $content_json, true );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                throw new Exception( 'Failed to parse generated text: ' . json_last_error_msg() );
            }

            $updated = $this->update_post_with_texts( $post_id, $parsed_content, $cpt_settings['custom_fields'] );

            return array(
                'success'        => true,
                'fields_updated' => $updated,
                'debug_log'      => $this->debug_log,
            );

        } catch ( Exception $e ) {
            $this->add_debug( 'Error in save_text_to_post', array( 'error_message' => $e->getMessage() ) );
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
     * @return array { success, cleared_count, debug_log }
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

            $wp_update    = array( 'ID' => $post_id );
            $cleared      = 0;
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
     * Save a base64-encoded image (returned by an external API) to the WordPress
     * media library, convert it to WebP, and attach it to the correct post field.
     *
     * Used by the client-side image generation flow where the browser calls the
     * AI API directly and then sends the b64_json back to the server for saving.
     *
     * @param int    $post_id     Post ID.
     * @param int    $field_index Zero-based index into the image-field list.
     * @param string $b64_json    Raw base64-encoded image data (no data-URI prefix).
     * @param string $alt_text    Optional alt text generated browser-side.
     * @return array { success, attachment_id, debug_log }
     */
    public function save_image_to_post( $post_id, $field_index, $b64_json, $alt_text = null ) {
        $this->debug_log = array();

        try {
            $post = get_post( $post_id );
            if ( ! $post ) {
                throw new Exception( __( 'Post not found', 'sparkplus' ) );
            }

            $cpt_settings  = $this->get_cpt_settings( $post->post_type );
            $post_settings = $this->get_post_settings( $post_id );

            $image_fields = array_values( array_filter( $cpt_settings['custom_fields'], function ( $f ) {
                return $f['type'] === 'image';
            } ) );

            if ( ! isset( $image_fields[ $field_index ] ) ) {
                throw new Exception( sprintf( 'Image field index %d not found', $field_index ) );
            }

            $field = $image_fields[ $field_index ];

            $webp_quality = isset( $field['webp_quality'] ) ? $field['webp_quality'] : 80;
            $webp_data    = sparkplus_convert_image_to_webp( $b64_json, $webp_quality );

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

            // Set alt text provided by the client (generated browser-side).
            if ( $alt_text !== null && $alt_text !== '' ) {
                $alt_text = trim( $alt_text );
                $alt_text = trim( $alt_text, '"\'' );
                if ( strlen( $alt_text ) > 125 ) {
                    $alt_text = substr( $alt_text, 0, 122 ) . '...';
                }
                update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt_text ) );
            }

            $this->update_post_with_image( $post_id, $field, $attachment_id );

            $this->add_debug( 'save_image_to_post', array(
                'field_index'   => $field_index,
                'field_key'     => $field['key'],
                'attachment_id' => $attachment_id,
                'format'        => 'webp',
                'webp_quality'  => $webp_quality,
                'alt_text'      => $alt_text,
                'success'       => true,
            ) );

            return array(
                'success'       => true,
                'attachment_id' => $attachment_id,
                'debug_log'     => $this->debug_log,
            );

        } catch ( Exception $e ) {
            $this->add_debug( 'Error in save_image_to_post', array( 'error_message' => $e->getMessage() ) );
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
     * Update post with generated image.
     *
     * @param int   $post_id       The post ID.
     * @param array $field         The field configuration.
     * @param int   $attachment_id The attachment ID.
     */
    private function update_post_with_image( $post_id, $field, $attachment_id ) {
        // Handle featured image (_thumbnail_id)
        if ( $field['key'] === '_thumbnail_id' ) {
            set_post_thumbnail( $post_id, $attachment_id );

            $this->add_debug( 'update_post_with_image', array(
                'action'        => 'set_featured_image',
                'post_id'       => $post_id,
                'attachment_id' => $attachment_id,
            ) );
            return;
        }

        // Handle ACF image fields
        if ( $field['source'] === 'acf' && function_exists( 'update_field' ) ) {
            // Group sub-field: read-merge-write to avoid wiping sibling sub-fields
            if ( ! empty( $field['group_key'] ) ) {
                $group_key      = $field['group_key'];
                $existing_value = get_field( $group_key, $post_id );
                if ( ! is_array( $existing_value ) ) {
                    $existing_value = array();
                }
                $existing_value[ $field['key'] ] = $attachment_id;
                $updated = update_field( $group_key, $existing_value, $post_id );

                $this->add_debug( 'update_post_with_image', array(
                    'action'        => 'update_acf_group_image_field',
                    'group_key'     => $group_key,
                    'field_key'     => $field['key'],
                    'post_id'       => $post_id,
                    'attachment_id' => $attachment_id,
                    'success'       => $updated,
                ) );
            } else {
                $updated = update_field( $field['key'], $attachment_id, $post_id );

                $this->add_debug( 'update_post_with_image', array(
                    'action'        => 'update_acf_field',
                    'field_key'     => $field['key'],
                    'post_id'       => $post_id,
                    'attachment_id' => $attachment_id,
                    'success'       => $updated,
                ) );
            }
        }
    }

    /**
     * Write parsed text content to the post's fields.
     *
     * @param int   $post_id        Post ID.
     * @param array $parsed_content Decoded JSON from the LLM.
     * @param array $custom_fields  Enabled field configs from get_cpt_settings.
     * @return int  Number of fields updated.
     */
    private function update_post_with_texts( $post_id, $parsed_content, $custom_fields ) {
        $this->add_debug( 'update_post_with_texts', 'Updating post with generated text content' );

        if ( empty( $parsed_content ) ) {
            $this->add_debug( 'update_post_with_texts', 'No content to update' );
            return 0;
        }

        // Separate WordPress baseline fields from ACF fields
        $wp_fields    = array();
        $acf_fields   = array();
        $group_fields = array(); // [ group_key => [ sub_key => value ] ]
        $rm_fields    = array();

        foreach ( $custom_fields as $field ) {
            // Skip image fields
            if ( in_array( $field['type'], array( 'image', 'file', 'gallery' ) ) ) {
                continue;
            }

            $field_key = $field['key'];

            // Group sub-field: value lives at parsed_content[group_key][sub_key]
            if ( ! empty( $field['group_key'] ) ) {
                $group_key = $field['group_key'];
                if ( isset( $parsed_content[ $group_key ][ $field_key ] ) ) {
                    if ( ! isset( $group_fields[ $group_key ] ) ) {
                        $group_fields[ $group_key ] = array();
                    }
                    $group_fields[ $group_key ][ $field_key ] = $parsed_content[ $group_key ][ $field_key ];
                }
                continue;
            }

            if ( ! isset( $parsed_content[ $field_key ] ) ) {
                continue;
            }

            if ( $field['source'] === 'wordpress' ) {
                $wp_fields[ $field_key ] = $parsed_content[ $field_key ];
            } elseif ( $field['source'] === 'acf' ) {
                $acf_fields[ $field_key ] = $parsed_content[ $field_key ];
            } elseif ( $field['source'] === 'rankmath' ) {
                $rm_fields[ $field_key ] = $parsed_content[ $field_key ];
            }
        }

        // Update WordPress baseline fields
        if ( ! empty( $wp_fields ) ) {
            $post_data = array( 'ID' => $post_id );

            if ( isset( $wp_fields['post_title'] ) ) {
                $post_data['post_title'] = $wp_fields['post_title'];
            }
            if ( isset( $wp_fields['post_content'] ) ) {
                $post_data['post_content'] = $wp_fields['post_content'];
            }
            if ( isset( $wp_fields['post_excerpt'] ) ) {
                $post_data['post_excerpt'] = $wp_fields['post_excerpt'];
            }
            if ( isset( $wp_fields['post_slug'] ) ) {
                $post_data['post_name'] = sanitize_title( $wp_fields['post_slug'] );
            }

            $result = wp_update_post( $post_data, true );

            if ( is_wp_error( $result ) ) {
                throw new Exception( esc_html__( 'Failed to update WordPress fields: ', 'sparkplus' ) . esc_html( $result->get_error_message() ) );
            }

            $this->add_debug( 'update_post_with_texts', array(
                'wordpress_fields_updated' => array_keys( $wp_fields ),
            ) );
        }

        // Update ACF fields
        if ( ! empty( $acf_fields ) && function_exists( 'update_field' ) ) {
            foreach ( $acf_fields as $field_key => $field_value ) {
                $updated = update_field( $field_key, $field_value, $post_id );

                if ( ! $updated ) {
                    $this->add_debug( 'update_post_with_texts', array(
                        'warning' => "Failed to update ACF field: {$field_key}",
                    ) );
                }
            }

            $this->add_debug( 'update_post_with_texts', array(
                'acf_fields_updated' => array_keys( $acf_fields ),
            ) );
        }

        // Update ACF group fields (read-merge-write preserves non-enabled sub-fields)
        if ( ! empty( $group_fields ) && function_exists( 'update_field' ) ) {
            foreach ( $group_fields as $group_key => $new_sub_values ) {
                $existing = get_field( $group_key, $post_id );
                $merged   = is_array( $existing ) ? array_merge( $existing, $new_sub_values ) : $new_sub_values;
                update_field( $group_key, $merged, $post_id );
            }
            $this->add_debug( 'update_post_with_texts', array(
                'group_fields_updated' => array_keys( $group_fields ),
            ) );
        }

        // Update RankMath meta fields
        if ( ! empty( $rm_fields ) ) {
            foreach ( $rm_fields as $field_key => $field_value ) {
                update_post_meta( $post_id, $field_key, $field_value );
            }
            $this->add_debug( 'update_post_with_texts', array(
                'rankmath_fields_updated' => array_keys( $rm_fields ),
            ) );
        }

        $total_updated = count( $wp_fields ) + count( $acf_fields ) + count( $group_fields ) + count( $rm_fields );

        $this->add_debug( 'update_post_with_texts', array(
            'status'               => 'completed',
            'total_fields_updated' => $total_updated,
        ) );

        return $total_updated;
    }
}
