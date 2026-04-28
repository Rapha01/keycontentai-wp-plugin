<?php
/**
 * Google Gemini Provider
 *
 * Handles communication with the Google Generative Language API
 * (Gemini models via generateContent endpoint).
 *
 * Supported text models: gemini-2.5-pro, gemini-2.5-flash
 * Image generation: not supported by this provider — throws Exception.
 *
 * @package SparkPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SparkPlus_Gemini_Provider
 */
class SparkPlus_Gemini_Provider extends SparkPlus_Provider_Base {

    /**
     * Generate text using the Gemini generateContent API.
     *
     * @param string $prompt  The full prompt to send.
     * @param array  $options Optional: model, max_output_tokens, temperature, step.
     * @return array { content, usage, model }
     * @throws Exception On API / network failure.
     */
    public function generate_text( $prompt, $options = array() ) {
        $step = isset( $options['step'] ) ? $options['step'] : 'generate_text';
        $this->add_debug( $step, 'Preparing text generation request to Google Gemini' );

        if ( empty( $this->api_key ) ) {
            throw new Exception( __( 'Google Gemini API key is not configured. Please add it in Settings → API.', 'sparkplus' ) );
        }

        $defaults = array(
            'model'             => 'gemini-2.5-flash',
            'max_output_tokens' => 16000,
            'temperature'       => 0.7,
        );
        $options = wp_parse_args( $options, $defaults );

        $model        = $options['model'];
        $api_endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode( $model ),
            $this->api_key
        );

        $request_body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'maxOutputTokens'  => intval( $options['max_output_tokens'] ),
                'temperature'      => floatval( $options['temperature'] ),
                'responseMimeType' => 'application/json',
            ),
        );

        $this->add_debug( $step, array(
            'endpoint'      => preg_replace( '/key=[^&]+/', 'key=***', $api_endpoint ),
            'model'         => $model,
            'prompt_length' => strlen( $prompt ),
        ) );

        $response = wp_remote_post( $api_endpoint, array(
            'headers'   => array(
                'Content-Type' => 'application/json',
            ),
            'body'      => json_encode( $request_body ),
            'timeout'   => 120,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            $msg = $response->get_error_message();
            $this->add_debug( $step, array( 'error' => 'WordPress HTTP Error', 'message' => $msg ) );
            throw new Exception( esc_html__( 'API request failed: ', 'sparkplus' ) . esc_html( $msg ) );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        $this->add_debug( $step, array(
            'response_code'   => $response_code,
            'response_length' => strlen( $response_body ),
        ) );

        if ( $response_code !== 200 ) {
            $error_data    = json_decode( $response_body, true );
            $error_message = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : 'Unknown API error';
            $this->add_debug( $step, array(
                'error'         => 'Gemini API Error',
                'code'          => $response_code,
                'message'       => $error_message,
                'full_response' => $error_data,
            ) );
            throw new Exception( sprintf(
                /* translators: %1$d: HTTP response code, %2$s: error message */
                esc_html__( 'Gemini API error (%1$d): %2$s', 'sparkplus' ),
                intval( $response_code ),
                esc_html( $error_message )
            ) );
        }

        $response_data = json_decode( $response_body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            throw new Exception( esc_html__( 'Failed to parse Gemini API response: ', 'sparkplus' ) . esc_html( json_last_error_msg() ) );
        }

        // Response structure: { "candidates": [ { "content": { "parts": [ { "text": "..." } ] } } ], "usageMetadata": {...} }
        if ( ! isset( $response_data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            $this->add_debug( $step, array(
                'error'             => 'Invalid Response Structure',
                'full_api_response' => wp_json_encode( $response_data, JSON_PRETTY_PRINT ),
            ) );
            throw new Exception( 'Invalid Gemini API response structure' );
        }

        $content = $response_data['candidates'][0]['content']['parts'][0]['text'];

        // Normalize usage statistics.
        $usage = null;
        if ( isset( $response_data['usageMetadata'] ) ) {
            $meta  = $response_data['usageMetadata'];
            $usage = array(
                'prompt_tokens'     => isset( $meta['promptTokenCount'] )     ? $meta['promptTokenCount']     : 0,
                'completion_tokens' => isset( $meta['candidatesTokenCount'] ) ? $meta['candidatesTokenCount'] : 0,
                'total_tokens'      => isset( $meta['totalTokenCount'] )       ? $meta['totalTokenCount']      : 0,
            );
        }

        $this->add_debug( $step, array(
            'status'           => 'success',
            'content_length'   => strlen( $content ),
            'model'            => $model,
            'finish_reason'    => isset( $response_data['candidates'][0]['finishReason'] ) ? $response_data['candidates'][0]['finishReason'] : null,
            'usage'            => $usage,
            'full_api_response' => $content,
        ) );

        return array(
            'content' => $content,
            'usage'   => $usage,
            'model'   => $model,
        );
    }

    /**
     * Image generation via Gemini's text API is not supported.
     *
     * @throws Exception Always.
     */
    public function generate_image( $prompt, $options = array() ) {
        throw new Exception( __( 'Image generation is not supported for Gemini text models. Please select an OpenAI image model in Settings → API.', 'sparkplus' ) );
    }
}
