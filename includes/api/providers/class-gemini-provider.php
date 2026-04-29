<?php
/**
 * Google Gemini Provider
 *
 * Handles communication with the Google Generative Language API
 * (Gemini models via generateContent endpoint).
 *
 * Supported text models: gemini-2.5-pro, gemini-2.5-flash, gemini-3.x flavours
 * Supported image models: gemini-3.1-flash-image-preview, gemini-3-pro-image-preview, gemini-2.5-flash-image
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
     * Generate an image using the Gemini generateContent API with IMAGE response modality.
     *
     * @param string $prompt  The image prompt.
     * @param array  $options Optional: model, aspect_ratio, output_resolution, step.
     * @return array { data: [ [ 'b64_json' => string ] ] }
     * @throws Exception On API / network failure.
     */
    public function generate_image( $prompt, $options = array() ) {
        $step = isset( $options['step'] ) ? $options['step'] : 'generate_image';
        $this->add_debug( $step, 'Preparing image generation request to Google Gemini' );

        if ( empty( $this->api_key ) ) {
            throw new Exception( __( 'Google Gemini API key is not configured. Please add it in Settings → API.', 'sparkplus' ) );
        }

        $defaults = array(
            'model'             => 'gemini-3.1-flash-image-preview',
            'aspect_ratio'      => 'square',
            'output_resolution' => 'medium',
        );
        $options = wp_parse_args( $options, $defaults );

        $model        = $options['model'];
        $api_endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode( $model ),
            $this->api_key
        );

        // Map generic aspect_ratio to Gemini aspect_ratio strings.
        $aspect_map = array(
            'square'    => '1:1',
            'landscape' => '16:9',
            'portrait'  => '9:16',
        );
        $aspect_ratio = isset( $aspect_map[ $options['aspect_ratio'] ] ) ? $aspect_map[ $options['aspect_ratio'] ] : '1:1';

        // Map generic output_resolution to Gemini image_size.
        // gemini-2.5-flash-image does not support image_size, so skip for that model.
        // Gemini 3 Pro Image supports up to 4K; 3.1 Flash Image also supports 4K.
        $resolution_map = array(
            'low'    => '1K',
            'medium' => '2K',
            'high'   => '4K',
        );
        $image_size = isset( $resolution_map[ $options['output_resolution'] ] ) ? $resolution_map[ $options['output_resolution'] ] : '2K';
        // gemini-2.5-flash-image doesn't support image_size parameter at all.
        $supports_image_size = ( $model !== 'gemini-2.5-flash-image' );

        $generation_config = array(
            'responseModalities' => array( 'IMAGE' ),
            'imageConfig'        => array(
                'aspectRatio' => $aspect_ratio,
            ),
        );
        if ( $supports_image_size ) {
            $generation_config['imageConfig']['imageSize'] = $image_size;
        }

        $request_body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt ),
                    ),
                ),
            ),
            'generationConfig' => $generation_config,
        );

        // Inject reference image as inlineData (base64) if provided.
        // Gemini image-generation models require inlineData — fileData (URL) is not reliably processed.
        $reference_image_url = isset( $options['reference_image_url'] ) ? trim( $options['reference_image_url'] ) : '';
        if ( ! empty( $reference_image_url ) ) {
            // Detect MIME type from file extension.
            $ext      = strtolower( pathinfo( parse_url( $reference_image_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
            $mime_map = array(
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
                'bmp'  => 'image/bmp',
            );
            $mime_type = isset( $mime_map[ $ext ] ) ? $mime_map[ $ext ] : 'image/jpeg';

            // Fetch the image server-side and base64-encode it.
            $img_response = wp_remote_get( $reference_image_url, array( 'timeout' => 30, 'sslverify' => true ) );

            if ( ! is_wp_error( $img_response ) && wp_remote_retrieve_response_code( $img_response ) === 200 ) {
                $img_b64 = base64_encode( wp_remote_retrieve_body( $img_response ) );

                // Prepend the reference image part before the text prompt.
                array_unshift(
                    $request_body['contents'][0]['parts'],
                    array(
                        'inlineData' => array(
                            'mimeType' => $mime_type,
                            'data'     => $img_b64,
                        ),
                    )
                );
                $this->add_debug( $step, array(
                    'reference_image' => $reference_image_url,
                    'mime_type'       => $mime_type,
                    'ref_image_bytes' => strlen( wp_remote_retrieve_body( $img_response ) ),
                ) );
            } else {
                $fetch_error = is_wp_error( $img_response ) ? $img_response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $img_response );
                $this->add_debug( $step, array( 'reference_image_error' => 'Failed to fetch reference image: ' . $fetch_error ) );
            }
        }

        $this->add_debug( $step, array(
            'endpoint'      => preg_replace( '/key=[^&]+/', 'key=***', $api_endpoint ),
            'model'         => $model,
            'aspect_ratio'  => $aspect_ratio,
            'image_size'    => $supports_image_size ? $image_size : 'n/a (gemini-2.5-flash-image)',
            'prompt_length' => strlen( $prompt ),
        ) );

        $response = wp_remote_post( $api_endpoint, array(
            'headers'   => array( 'Content-Type' => 'application/json' ),
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

        if ( $response_code !== 200 ) {
            $error_data    = json_decode( $response_body, true );
            $error_message = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : 'Unknown API error';
            $this->add_debug( $step, array( 'error' => 'Gemini API Error', 'code' => $response_code, 'message' => $error_message ) );
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

        // Find the first non-thought inline_data (image) part.
        $b64_json = null;
        $parts    = isset( $response_data['candidates'][0]['content']['parts'] ) ? $response_data['candidates'][0]['content']['parts'] : array();
        foreach ( $parts as $part ) {
            if ( ! empty( $part['thought'] ) ) {
                continue; // skip thinking parts
            }
            if ( isset( $part['inlineData']['data'] ) ) {
                $b64_json = $part['inlineData']['data'];
                break;
            }
        }

        if ( $b64_json === null ) {
            $this->add_debug( $step, array(
                'error'             => 'No image in response',
                'full_api_response' => wp_json_encode( $response_data, JSON_PRETTY_PRINT ),
            ) );
            throw new Exception( esc_html__( 'Gemini API did not return an image. The prompt may have been blocked or the model did not generate an image.', 'sparkplus' ) );
        }

        $this->add_debug( $step, array(
            'status' => 'success',
            'model'  => $model,
            'bytes'  => strlen( base64_decode( $b64_json ) ),
        ) );

        return array(
            'data' => array(
                array( 'b64_json' => $b64_json ),
            ),
        );
    }
}
