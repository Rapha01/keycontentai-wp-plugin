<?php
/**
 * Abstract Provider Base
 *
 * Defines the contract that all AI provider classes must fulfill.
 *
 * @package SparkPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SparkPlus_Provider_Base
 *
 * All AI provider implementations extend this class and implement
 * generate_text() and generate_image().
 */
abstract class SparkPlus_Provider_Base {

    /**
     * API key for the provider.
     *
     * @var string
     */
    protected $api_key = '';

    /**
     * Debug callback.
     *
     * @var callable|null
     */
    protected $debug_callback = null;

    /**
     * Constructor.
     *
     * @param string        $api_key   Provider API key.
     * @param callable|null $debug_cb  Debug callback ( $step, $data ).
     */
    public function __construct( $api_key, $debug_cb = null ) {
        $this->api_key        = $api_key;
        $this->debug_callback = $debug_cb;
    }

    /**
     * Generate text content.
     *
     * @param string $prompt  The full prompt to send.
     * @param array  $options Provider/model options (e.g. 'model', 'step').
     * @return array {
     *     @type string     $content  The raw text (typically JSON) returned by the model.
     *     @type array|null $usage    Token usage statistics, if provided by the API.
     *     @type string|null $model   Model identifier echoed back by the API.
     * }
     * @throws Exception On API / network error.
     */
    abstract public function generate_text( $prompt, $options = array() );

    /**
     * Generate an image.
     *
     * Returns data in the same structure as the OpenAI images API so that
     * content-generator.php can remain unchanged:
     *   $response['data'][0]['b64_json']  →  base-64-encoded image data.
     *
     * Providers that do not support image generation should throw an Exception.
     *
     * @param string $prompt  Image description prompt.
     * @param array  $options Provider/model options.
     * @return array  { 'data' => [ [ 'b64_json' => string ] ] }
     * @throws Exception On unsupported operation or API / network error.
     */
    abstract public function generate_image( $prompt, $options = array() );

    /**
     * Log a debug entry via the registered callback.
     *
     * @param string $step  Step / label name.
     * @param mixed  $data  Data to log.
     */
    protected function add_debug( $step, $data ) {
        if ( is_callable( $this->debug_callback ) ) {
            call_user_func( $this->debug_callback, $step, $data );
        }
    }
}
