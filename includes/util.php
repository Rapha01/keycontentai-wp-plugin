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
