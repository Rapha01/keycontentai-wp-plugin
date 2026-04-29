<?php
/**
 * Anthropic Claude Provider
 *
 * Handles communication with the Anthropic Messages API.
 *
 * Supported text models: claude-opus-4-5, claude-sonnet-4-5, claude-haiku-3-5
 * Image generation: not supported — throws Exception.
 *
 * @package SparkPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SparkPlus_Anthropic_Provider
 */
class SparkPlus_Anthropic_Provider extends SparkPlus_Provider_Base {

    /**
     * Anthropic API version header value.
     */
    const API_VERSION = '2023-06-01';

    /**
     * Generate text using the Anthropic Messages API.
     *
     * @param string $prompt  The full prompt to send.
     * @param array  $options Optional: model, max_tokens, step.
     * @return array { content, usage, model }
     * @throws Exception On API / network failure.
     */
    public function generate_text( $prompt, $options = array() ) {
        $step = isset( $options['step'] ) ? $options['step'] : 'generate_text';
        $this->add_debug( $step, 'Preparing text generation request to Anthropic' );

        if ( empty( $this->api_key ) ) {
            throw new Exception( __( 'Anthropic API key is not configured. Please add it in Settings → API.', 'sparkplus' ) );
        }

        $defaults = array(
            'model'      => 'claude-sonnet-4-5',
            'max_tokens' => 16000,
        );
        $options = wp_parse_args( $options, $defaults );

        $api_endpoint = 'https://api.anthropic.com/v1/messages';

        $request_body = array(
            'model'      => $options['model'],
            'max_tokens' => intval( $options['max_tokens'] ),
            'messages'   => array(
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
        );

        $this->add_debug( $step, array(
            'endpoint'      => $api_endpoint,
            'model'         => $request_body['model'],
            'prompt_length' => strlen( $prompt ),
        ) );

        $response = wp_remote_post( $api_endpoint, array(
            'headers'   => array(
                'x-api-key'         => $this->api_key,
                'anthropic-version' => self::API_VERSION,
                'Content-Type'      => 'application/json',
            ),
            'body'      => json_encode( $request_body ),
            'timeout'   => 300,
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
                'error'         => 'Anthropic API Error',
                'code'          => $response_code,
                'message'       => $error_message,
                'full_response' => $error_data,
            ) );
            throw new Exception( sprintf(
                /* translators: %1$d: HTTP response code, %2$s: error message */
                esc_html__( 'Anthropic API error (%1$d): %2$s', 'sparkplus' ),
                intval( $response_code ),
                esc_html( $error_message )
            ) );
        }

        $response_data = json_decode( $response_body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            throw new Exception( esc_html__( 'Failed to parse Anthropic API response: ', 'sparkplus' ) . esc_html( json_last_error_msg() ) );
        }

        // Response structure: { "content": [ { "type": "text", "text": "..." } ], "usage": {...}, "model": "..." }
        if ( ! isset( $response_data['content'][0]['text'] ) ) {
            $this->add_debug( $step, array(
                'error'             => 'Invalid Response Structure',
                'full_api_response' => wp_json_encode( $response_data, JSON_PRETTY_PRINT ),
            ) );
            throw new Exception( 'Invalid Anthropic API response structure' );
        }

        $content = $response_data['content'][0]['text'];

        // Normalize usage to match OpenAI shape expected by debug output.
        $usage = null;
        if ( isset( $response_data['usage'] ) ) {
            $usage = array(
                'prompt_tokens'     => isset( $response_data['usage']['input_tokens'] )  ? $response_data['usage']['input_tokens']  : 0,
                'completion_tokens' => isset( $response_data['usage']['output_tokens'] ) ? $response_data['usage']['output_tokens'] : 0,
                'total_tokens'      => ( isset( $response_data['usage']['input_tokens'] ) ? $response_data['usage']['input_tokens'] : 0 )
                                     + ( isset( $response_data['usage']['output_tokens'] ) ? $response_data['usage']['output_tokens'] : 0 ),
            );
        }

        $this->add_debug( $step, array(
            'status'           => 'success',
            'content_length'   => strlen( $content ),
            'model'            => isset( $response_data['model'] ) ? $response_data['model'] : null,
            'stop_reason'      => isset( $response_data['stop_reason'] ) ? $response_data['stop_reason'] : null,
            'usage'            => $usage,
            'full_api_response' => $content,
        ) );

        return array(
            'content' => $content,
            'usage'   => $usage,
            'model'   => isset( $response_data['model'] ) ? $response_data['model'] : null,
        );
    }

    /**
     * Image generation is not supported by Anthropic Claude.
     *
     * @throws Exception Always.
     */
    public function generate_image( $prompt, $options = array() ) {
        throw new Exception( __( 'Image generation is not supported by Anthropic Claude. Please select an OpenAI image model in Settings → API.', 'sparkplus' ) );
    }
}
