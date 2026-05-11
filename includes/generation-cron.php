<?php
/**
 * Generation Handler
 *
 * Contains the generation work methods (text and image) called directly
 * by the AJAX handlers after flushing the HTTP response early via
 * fastcgi_finish_request().
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SparkPlus_Generation_Cron {

    /**
     * Run text generation for a post and write the result to a transient.
     *
     * @param int    $post_id Post ID.
     * @param string $job_id  Transient key.
     */
    public function handle_generate_text( $post_id, $job_id ) {
        @set_time_limit( 300 );

        $generator = new SparkPlus_Content_Generator();
        $generator->set_streaming_job_id( $job_id );
        $result = $generator->generate_text_only( $post_id );

        if ( $result['success'] ) {
            set_transient( $job_id, array(
                'status'    => 'complete',
                'success'   => true,
                'debug_log' => $result['debug_log'],
            ), 600 );
        } else {
            set_transient( $job_id, array(
                'status'    => 'error',
                'success'   => false,
                'message'   => $result['message'],
                'debug_log' => $result['debug_log'] ?? array(),
            ), 600 );
        }
    }

    /**
     * Run image generation for a single image field and write the result to a transient.
     *
     * @param int    $post_id     Post ID.
     * @param int    $field_index Image field index (0-based).
     * @param string $job_id      Transient key.
     */
    public function handle_generate_image( $post_id, $field_index, $job_id ) {
        @set_time_limit( 300 );

        $generator = new SparkPlus_Content_Generator();
        $generator->set_streaming_job_id( $job_id );
        $result = $generator->generate_single_image( $post_id, $field_index );

        if ( $result['success'] ) {
            set_transient( $job_id, array(
                'status'    => 'complete',
                'success'   => true,
                'debug_log' => $result['debug_log'],
            ), 600 );
        } else {
            set_transient( $job_id, array(
                'status'    => 'error',
                'success'   => false,
                'message'   => $result['message'],
                'debug_log' => $result['debug_log'] ?? array(),
            ), 600 );
        }
    }
}
