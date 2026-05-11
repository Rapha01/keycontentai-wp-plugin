<?php
/**
 * Generation Cron Handler
 *
 * Registers and handles WP-Cron callbacks for background AI generation jobs.
 * Loaded on every request (including wp-cron.php) so cron hooks are always
 * registered — no is_admin() guard here.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SparkPlus_Generation_Cron {

    public function __construct() {
        add_action( 'sparkplus_cron_generate_text',  array( $this, 'handle_generate_text' ),  10, 2 );
        add_action( 'sparkplus_cron_generate_image', array( $this, 'handle_generate_image' ), 10, 3 );
    }

    /**
     * Run text generation for a post and write the result to a transient.
     * Called by WP-Cron (fresh PHP process) or inline as a fallback.
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
     * Called by WP-Cron (fresh PHP process) or inline as a fallback.
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
