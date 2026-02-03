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
function sparkwp_get_language_names() {
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
function sparkwp_get_language_name($code) {
    $language_names = sparkwp_get_language_names();
    return isset($language_names[$code]) ? $language_names[$code] : ucfirst($code);
}
