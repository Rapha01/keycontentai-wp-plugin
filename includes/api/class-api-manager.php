<?php
/**
 * API Manager
 *
 * Factory that maps a model ID to the correct provider class, reads
 * the appropriate API key from WordPress options, and returns a ready-
 * to-use provider instance.
 *
 * @package SparkPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SparkPlus_API_Manager
 */
class SparkPlus_API_Manager {

    /**
     * Map provider slugs to the WP option name that stores their API key.
     *
     * @var array
     */
    private static $api_key_options = array(
        'openai'    => 'sparkplus_openai_api_key',
        'anthropic' => 'sparkplus_anthropic_api_key',
        'gemini'    => 'sparkplus_gemini_api_key',
    );

    /**
     * Map provider slugs to their class names.
     *
     * @var array
     */
    private static $provider_classes = array(
        'openai'    => 'SparkPlus_OpenAI_Provider',
        'anthropic' => 'SparkPlus_Anthropic_Provider',
        'gemini'    => 'SparkPlus_Gemini_Provider',
    );

    /**
     * Create a provider instance for the given text model.
     *
     * @param string        $model    Text model ID (e.g. 'gpt-5.4', 'claude-sonnet-4-5').
     * @param callable|null $debug_cb Debug callback.
     * @return SparkPlus_Provider_Base
     * @throws Exception If the model is unknown or the API key is not set.
     */
    public static function make_text_provider( $model, $debug_cb = null ) {
        return self::make_provider( $model, 'text', $debug_cb );
    }

    /**
     * Create a provider instance for the given image model.
     *
     * @param string        $model    Image model ID (e.g. 'gpt-image-1.5').
     * @param callable|null $debug_cb Debug callback.
     * @return SparkPlus_Provider_Base
     * @throws Exception If the model is unknown or the API key is not set.
     */
    public static function make_image_provider( $model, $debug_cb = null ) {
        return self::make_provider( $model, 'image', $debug_cb );
    }

    /**
     * Internal factory.
     *
     * @param string        $model   Model ID.
     * @param string        $type    'text' or 'image'.
     * @param callable|null $debug_cb Debug callback.
     * @return SparkPlus_Provider_Base
     * @throws Exception
     */
    private static function make_provider( $model, $type, $debug_cb ) {
        $provider_slug = sparkplus_get_model_provider( $model, $type );
        if ( $provider_slug === null ) {
            throw new Exception( sprintf(
                /* translators: %s: model ID */
                __( 'Unknown AI model "%s". Please select a supported model in Settings → API.', 'sparkplus' ),
                $model
            ) );
        }

        if ( ! isset( self::$provider_classes[ $provider_slug ] ) ) {
            throw new Exception( sprintf(
                /* translators: %s: provider slug */
                __( 'Unknown AI provider "%s".', 'sparkplus' ),
                $provider_slug
            ) );
        }

        $api_key = '';
        if ( isset( self::$api_key_options[ $provider_slug ] ) ) {
            $api_key = get_option( self::$api_key_options[ $provider_slug ], '' );
        }

        if ( empty( $api_key ) ) {
            $provider_labels = array(
                'openai'    => 'OpenAI',
                'anthropic' => 'Anthropic',
                'gemini'    => 'Google Gemini',
            );
            $label = isset( $provider_labels[ $provider_slug ] ) ? $provider_labels[ $provider_slug ] : $provider_slug;
            throw new Exception( sprintf(
                /* translators: %s: provider name */
                __( '%s API key is not configured. Please add it in Settings → API.', 'sparkplus' ),
                $label
            ) );
        }

        $class_name = self::$provider_classes[ $provider_slug ];

        return new $class_name( $api_key, $debug_cb );
    }

    /**
     * Return the WP option name for a given provider slug's API key.
     *
     * @param string $provider_slug  e.g. 'openai', 'anthropic', 'gemini'.
     * @return string|null
     */
    public static function get_api_key_option( $provider_slug ) {
        return isset( self::$api_key_options[ $provider_slug ] )
            ? self::$api_key_options[ $provider_slug ]
            : null;
    }
}
