<?php
/**
 * OpenAI Provider
 *
 * Handles all communication with the OpenAI Chat Completions and Images APIs.
 *
 * @package SparkPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SparkPlus_OpenAI_Provider
 */
class SparkPlus_OpenAI_Provider extends SparkPlus_Provider_Base {

    /**
     * Generate text using the OpenAI Chat Completions API.
     *
     * @param string $prompt  The prompt to send.
     * @param array  $options Optional parameters (model, temperature, max_completion_tokens, step, etc.)
     * @return array { content, usage, model }
     * @throws Exception On API / network failure.
     */
    public function generate_text( $prompt, $options = array() ) {
        $step = isset( $options['step'] ) ? $options['step'] : 'generate_text';
        $this->add_debug( $step, 'Preparing text generation request to OpenAI' );

        if ( empty( $this->api_key ) ) {
            throw new Exception( __( 'OpenAI API key is not configured. Please add it in Settings → API.', 'sparkplus' ) );
        }

        $api_endpoint = 'https://api.openai.com/v1/chat/completions';
        $request_body = $this->prepare_text_request_body( $prompt, $options );

        $this->add_debug( $step, array(
            'endpoint'     => $api_endpoint,
            'request_body' => $request_body,
        ) );

        $response = wp_remote_post( $api_endpoint, array(
            'headers'   => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
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
                'error'         => 'OpenAI API Error',
                'code'          => $response_code,
                'message'       => $error_message,
                'full_response' => $error_data,
            ) );
            throw new Exception( sprintf(
                /* translators: %1$d: HTTP response code, %2$s: error message */
                esc_html__( 'OpenAI API error (%1$d): %2$s', 'sparkplus' ),
                intval( $response_code ),
                esc_html( $error_message )
            ) );
        }

        $response_data = json_decode( $response_body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->add_debug( $step, array(
                'error'        => 'JSON Parse Error',
                'message'      => json_last_error_msg(),
                'raw_response' => substr( $response_body, 0, 500 ),
            ) );
            throw new Exception( esc_html__( 'Failed to parse API response: ', 'sparkplus' ) . esc_html( json_last_error_msg() ) );
        }

        if ( ! isset( $response_data['choices'][0]['message']['content'] ) ) {
            $this->add_debug( $step, array(
                'error'             => 'Invalid Response Structure',
                'full_api_response' => wp_json_encode( $response_data, JSON_PRETTY_PRINT ),
            ) );
            throw new Exception( 'Invalid API response structure' );
        }

        $content = $response_data['choices'][0]['message']['content'];

        $debug_data = array(
            'status'           => 'success',
            'content_length'   => strlen( $content ),
            'model'            => isset( $response_data['model'] ) ? $response_data['model'] : null,
            'finish_reason'    => isset( $response_data['choices'][0]['finish_reason'] ) ? $response_data['choices'][0]['finish_reason'] : null,
            'full_api_response' => $content,
        );
        if ( isset( $response_data['usage'] ) ) {
            $debug_data['usage'] = array(
                'prompt_tokens'     => $response_data['usage']['prompt_tokens'],
                'completion_tokens' => $response_data['usage']['completion_tokens'],
                'total_tokens'      => $response_data['usage']['total_tokens'],
            );
        }
        $this->add_debug( $step, $debug_data );

        return array(
            'content' => $content,
            'usage'   => isset( $response_data['usage'] ) ? $response_data['usage'] : null,
            'model'   => isset( $response_data['model'] ) ? $response_data['model'] : null,
        );
    }

    /**
     * Generate an image using the OpenAI Images API.
     *
     * @param string $prompt  Image description prompt.
     * @param array  $options Optional parameters (model, size, quality, n).
     * @return array { 'data' => [ [ 'b64_json' => string ] ] }
     * @throws Exception On API / network failure.
     */
    public function generate_image( $prompt, $options = array() ) {
        $this->add_debug( 'generate_image', 'Preparing image generation request to OpenAI.' );

        if ( empty( $this->api_key ) ) {
            throw new Exception( __( 'OpenAI API key is not configured. Please add it in Settings → API.', 'sparkplus' ) );
        }

        $api_endpoint = 'https://api.openai.com/v1/images/generations';

        $defaults = array(
            'model'   => 'gpt-image-1.5',
            'size'    => 'auto',
            'quality' => 'auto',
            'n'       => 1,
        );
        $options = wp_parse_args( $options, $defaults );

        $request_body = array(
            'model'   => $options['model'],
            'prompt'  => $prompt,
            'size'    => $options['size'],
            'quality' => $options['quality'],
            'n'       => $options['n'],
        );

        $this->add_debug( 'generate_image', array(
            'endpoint'      => $api_endpoint,
            'model'         => $request_body['model'],
            'prompt_length' => strlen( $prompt ),
            'size'          => $request_body['size'],
            'quality'       => $request_body['quality'],
        ) );

        $response = wp_remote_post( $api_endpoint, array(
            'headers'   => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'      => json_encode( $request_body ),
            'timeout'   => 120,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            $msg = $response->get_error_message();
            $this->add_debug( 'generate_image', array( 'error' => 'WordPress HTTP Error', 'message' => $msg ) );
            throw new Exception( esc_html__( 'Image generation request failed: ', 'sparkplus' ) . esc_html( $msg ) );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        $this->add_debug( 'generate_image', array(
            'response_code'   => $response_code,
            'response_length' => strlen( $response_body ),
        ) );

        if ( $response_code !== 200 ) {
            $error_data    = json_decode( $response_body, true );
            $error_message = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : 'Unknown API error';
            $this->add_debug( 'generate_image', array(
                'error'         => 'Image API Error',
                'code'          => $response_code,
                'message'       => $error_message,
                'full_response' => $error_data,
            ) );
            throw new Exception( sprintf(
                /* translators: %1$d: HTTP response code, %2$s: error message */
                esc_html__( 'Image API error (%1$d): %2$s', 'sparkplus' ),
                intval( $response_code ),
                esc_html( $error_message )
            ) );
        }

        $response_data = json_decode( $response_body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->add_debug( 'generate_image', array(
                'error'        => 'JSON Parse Error',
                'message'      => json_last_error_msg(),
                'raw_response' => substr( $response_body, 0, 500 ),
            ) );
            throw new Exception( esc_html__( 'Failed to parse image API response: ', 'sparkplus' ) . esc_html( json_last_error_msg() ) );
        }

        if ( ! isset( $response_data['data'] ) || ! is_array( $response_data['data'] ) || empty( $response_data['data'] ) ) {
            $this->add_debug( 'generate_image', array(
                'error'             => 'Invalid Response Structure',
                'full_api_response' => wp_json_encode( $response_data, JSON_PRETTY_PRINT ),
            ) );
            throw new Exception( 'Invalid image API response structure' );
        }

        $this->add_debug( 'generate_image', array(
            'status'        => 'success',
            'model'         => $options['model'],
            'response_keys' => array_keys( $response_data['data'][0] ),
        ) );

        return $response_data;
    }

    /**
     * Build the request body for text generation, applying model-specific tweaks.
     *
     * @param string $prompt  The prompt.
     * @param array  $options Options.
     * @return array Request body.
     */
    private function prepare_text_request_body( $prompt, $options ) {
        $defaults = array(
            'model'                 => 'gpt-5.4',
            'max_completion_tokens' => 16000,
            'response_format'       => array( 'type' => 'json_object' ),
        );

        $options = wp_parse_args( $options, $defaults );

        $request_body = array(
            'model'                 => $options['model'],
            'messages'              => array(
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
            'max_completion_tokens' => $options['max_completion_tokens'],
            'response_format'       => $options['response_format'],
        );

        return $request_body;
    }
}
