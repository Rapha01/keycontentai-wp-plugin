<?php
/**
 * Utility Functions
 * 
 * Common utility functions and constants used throughout the plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get language names mapping
 * 
 * Maps language codes (ISO 639-1) to their English names
 * 
 * @return array Language code => Language name
 */
function sparkplus_get_language_names() {
    return array(
        // Germanic languages
        'de' => 'German',
        'en' => 'English',
        'nl' => 'Dutch',
        'sv' => 'Swedish',
        'da' => 'Danish',
        'no' => 'Norwegian',
        'is' => 'Icelandic',
        'lb' => 'Luxembourgish',
        
        // Romance languages
        'fr' => 'French',
        'es' => 'Spanish',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'ro' => 'Romanian',
        'ca' => 'Catalan',
        'gl' => 'Galician',
        
        // Slavic languages
        'pl' => 'Polish',
        'cs' => 'Czech',
        'sk' => 'Slovak',
        'ru' => 'Russian',
        'uk' => 'Ukrainian',
        'bg' => 'Bulgarian',
        'sr' => 'Serbian',
        'hr' => 'Croatian',
        'sl' => 'Slovenian',
        'mk' => 'Macedonian',
        'bs' => 'Bosnian',
        'be' => 'Belarusian',
        
        // Baltic languages
        'lt' => 'Lithuanian',
        'lv' => 'Latvian',
        
        // Celtic languages
        'ga' => 'Irish',
        'cy' => 'Welsh',
        'gd' => 'Scottish Gaelic',
        'br' => 'Breton',
        
        // Finno-Ugric languages
        'fi' => 'Finnish',
        'et' => 'Estonian',
        'hu' => 'Hungarian',
        
        // Other European languages
        'el' => 'Greek',
        'sq' => 'Albanian',
        'hy' => 'Armenian',
        'eu' => 'Basque',
        'mt' => 'Maltese',
        
        // Major non-European languages (for completeness)
        'zh' => 'Chinese',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'ar' => 'Arabic',
        'tr' => 'Turkish',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'th' => 'Thai',
        'vi' => 'Vietnamese'
    );
}

/**
 * Get language name from language code
 * 
 * @param string $code Language code (ISO 639-1)
 * @return string Language name or capitalized code if not found
 */
function sparkplus_get_language_name($code) {
    $language_names = sparkplus_get_language_names();
    return isset($language_names[$code]) ? $language_names[$code] : ucfirst($code);
}

/**
 * Convert image to WebP format
 * 
 * Supports conversion from JPEG, PNG, GIF, and WebP (passthrough)
 * Uses PHP GD library for conversion
 * 
 * @param string $source_data Base64 encoded image data or raw image binary data
 * @param int $quality WebP quality (0-100), default 90
 * @return string|WP_Error WebP image data as binary string, or WP_Error on failure
 */
function sparkplus_convert_image_to_webp($source_data, $quality = 90) {
    // Decode base64 if needed
    $image_data = $source_data;
    if (base64_decode($source_data, true) !== false) {
        $decoded = base64_decode($source_data);
        if ($decoded !== false) {
            $image_data = $decoded;
        }
    }
    
    // Create image resource from string
    $image = @imagecreatefromstring($image_data);
    
    if ($image === false) {
        return new WP_Error('invalid_image', __('Failed to create image from data. The image data may be corrupted.', 'sparkplus'));
    }
    
    // Enable alpha blending and save alpha channel for transparency
    imagealphablending($image, true);
    imagesavealpha($image, true);
    
    // Convert to WebP
    ob_start();
    $result = imagewebp($image, null, $quality);
    $webp_data = ob_get_clean();
    
    // Free memory
    imagedestroy($image);
    
    if (!$result || empty($webp_data)) {
        return new WP_Error('conversion_failed', __('Failed to convert image to WebP format. Please check if GD library supports WebP.', 'sparkplus'));
    }
    
    return $webp_data;
}

/**
 * Save WebP image to WordPress Media Library
 * 
 * @param string $webp_data Binary WebP image data
 * @param int $post_id Post ID to attach the image to
 * @param string $filename Desired filename (without extension)
 * @param string $title Image title/alt text
 * @return int|WP_Error Attachment ID on success, WP_Error on failure
 */
function sparkplus_save_webp_to_media_library($webp_data, $post_id, $filename, $title = '') {
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // Get WordPress upload directory
    $upload_dir = wp_upload_dir();
    
    if (!empty($upload_dir['error'])) {
        return new WP_Error('upload_dir_error', $upload_dir['error']);
    }
    
    // Sanitize filename and add .webp extension
    $safe_filename = sanitize_file_name($filename) . '.webp';
    $filepath = $upload_dir['path'] . '/' . $safe_filename;
    
    // Save the WebP data to file using WP_Filesystem
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }
    
    $file_saved = $wp_filesystem->put_contents($filepath, $webp_data, FS_CHMOD_FILE);
    
    if ($file_saved === false) {
        return new WP_Error('file_save_error', __('Failed to save WebP file to uploads directory.', 'sparkplus'));
    }
    
    // Prepare attachment data
    $attachment = array(
        'guid' => $upload_dir['url'] . '/' . $safe_filename,
        'post_mime_type' => 'image/webp',
        'post_title' => !empty($title) ? $title : $filename,
        'post_content' => '',
        'post_status' => 'inherit'
    );
    
    // Insert the attachment into the database
    $attachment_id = wp_insert_attachment($attachment, $filepath, $post_id);
    
    if (is_wp_error($attachment_id)) {
        $wp_filesystem->delete($filepath);
        return $attachment_id;
    }
    
    // Generate attachment metadata and thumbnails
    $attachment_data = wp_generate_attachment_metadata($attachment_id, $filepath);
    wp_update_attachment_metadata($attachment_id, $attachment_data);
    
    return $attachment_id;
}

/**
 * Single source of truth for supported AI models.
 *
 * Only 'default' and 'by_provider' are stored here.
 * Use the helper functions below to derive flat model lists,
 * provider lookups, or API-key option names.
 *
 * To add a model: add it to the appropriate provider's 'models' array.
 * To add a provider: add a new key under 'by_provider' and register its
 * API-key option name in sparkplus_provider_api_key_option().
 *
 * @return array {
 *   'text'  => [ 'default' => string, 'by_provider' => [ slug => [ 'label' => string, 'models' => [ id => label ] ] ] ]
 *   'image' => [ 'default' => string, 'by_provider' => [ ... ] ]
 * }
 */
function sparkplus_get_supported_models() {
    return array(
        'text' => array(
            'default'     => 'gpt-5.5',
            'by_provider' => array(
                'openai' => array(
                    'label'  => 'OpenAI',
                    'models' => array(
                        'gpt-5.5'     => 'GPT-5.5',
                        'gpt-5.5-pro' => 'GPT-5.5 Pro',
                        'gpt-5.4'     => 'GPT-5.4',
                        'gpt-5.2'     => 'GPT-5.2',
                        'gpt-5.1'     => 'GPT-5.1',
                        'gpt-5-mini'  => 'GPT-5 Mini',
                        'gpt-5-nano'  => 'GPT-5 Nano',
                    ),
                ),
                'anthropic' => array(
                    'label'  => 'Anthropic Claude',
                    'models' => array(
                        'claude-opus-4-5'   => 'Claude Opus 4.5',
                        'claude-sonnet-4-5' => 'Claude Sonnet 4.5',
                        'claude-haiku-3-5'  => 'Claude Haiku 3.5',
                    ),
                ),
                'gemini' => array(
                    'label'  => 'Google Gemini',
                    'models' => array(
                        'gemini-2.5-pro'   => 'Gemini 2.5 Pro',
                        'gemini-2.5-flash' => 'Gemini 2.5 Flash',
                    ),
                ),
            ),
        ),
        'image' => array(
            'default'     => 'gpt-image-1.5',
            'by_provider' => array(
                'openai' => array(
                    'label'  => 'OpenAI',
                    'models' => array(
                        'gpt-image-2'      => 'gpt-image-2',
                        'gpt-image-1.5'    => 'gpt-image-1.5',
                        'gpt-image-1'      => 'gpt-image-1',
                        'gpt-image-1-mini' => 'gpt-image-1-mini',
                    ),
                ),
            ),
        ),
    );
}

/**
 * Return the WP option name that stores the API key for a given provider slug.
 *
 * @param string $provider_slug  e.g. 'openai', 'anthropic', 'gemini'.
 * @return string|null  Option name, or null for unknown providers.
 */
function sparkplus_provider_api_key_option( $provider_slug ) {
    $map = array(
        'openai'    => 'sparkplus_openai_api_key',
        'anthropic' => 'sparkplus_anthropic_api_key',
        'gemini'    => 'sparkplus_gemini_api_key',
    );
    return isset( $map[ $provider_slug ] ) ? $map[ $provider_slug ] : null;
}

/**
 * Return a flat id→label map of all models for a given type ('text' or 'image').
 * Derived from by_provider, so there is no duplication.
 *
 * @param string $type 'text' or 'image'.
 * @return array  [ model_id => label ]
 */
function sparkplus_get_flat_models( $type ) {
    $supported = sparkplus_get_supported_models();
    $flat      = array();
    foreach ( $supported[ $type ]['by_provider'] as $provider_data ) {
        foreach ( $provider_data['models'] as $id => $label ) {
            $flat[ $id ] = $label;
        }
    }
    return $flat;
}

/**
 * Return the provider slug for a given model id and type.
 *
 * @param string $model_id  Model identifier.
 * @param string $type      'text' or 'image'.
 * @return string|null  Provider slug, or null if not found.
 */
function sparkplus_get_model_provider( $model_id, $type ) {
    $supported = sparkplus_get_supported_models();
    foreach ( $supported[ $type ]['by_provider'] as $slug => $provider_data ) {
        if ( isset( $provider_data['models'][ $model_id ] ) ) {
            return $slug;
        }
    }
    return null;
}

/**
 * Check whether the currently selected text model's provider API key is configured.
 * Used by admin UI pages to decide whether to show a "configuration required" warning.
 *
 * @return bool
 */
function sparkplus_is_text_provider_configured() {
    $supported   = sparkplus_get_supported_models();
    $text_model  = get_option( 'sparkplus_text_model', $supported['text']['default'] );
    $provider    = sparkplus_get_model_provider( $text_model, 'text' ) ?? 'openai';
    $option_name = sparkplus_provider_api_key_option( $provider ) ?? 'sparkplus_openai_api_key';
    return ! empty( get_option( $option_name, '' ) );
}
