<?php
/**
 * OpenAI API Caller
 * 
 * Handles all communication with the OpenAI API
 * 
 * @package SparkWP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SparkWP_OpenAI_API_Caller
 * 
 * Manages API calls to OpenAI's chat completions endpoint
 */
class SparkWP_OpenAI_API_Caller {
    
    /**
     * Debug callback function
     * 
     * @var callable|null
     */
    private $debug_callback = null;
    
    /**
     * Constructor
     * 
     * @param callable|null $debug_callback Optional callback for debug logging
     */
    public function __construct($debug_callback = null) {
        $this->debug_callback = $debug_callback;
    }
    
    /**
     * Generate text using GPT API
     * 
     * @param string $prompt The prompt to send
     * @param string $api_key OpenAI API key
     * @param array $options Optional parameters (model, temperature, max_tokens, etc.)
     * @return array API response data with keys: content, usage, model
     * @throws Exception If API call fails
     */
    public function generate_text($prompt, $api_key, $options = array()) {
        $this->add_debug('generate_text', 'Preparing text generation request to GPT');
        
        // Validate API key
        if (empty($api_key)) {
            throw new Exception('OpenAI API key is missing');
        }
        
        // Prepare the API endpoint
        $api_endpoint = 'https://api.openai.com/v1/chat/completions';
        
        // Default options
        $defaults = array(
            'model' => 'gpt-5.2',
            'temperature' => 0.7,
            'max_completion_tokens' => 4000,
            'response_format' => array('type' => 'json_object')
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Prepare the request body
        $request_body = array(
            'model' => $options['model'],
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => $options['temperature'],
            'max_completion_tokens' => $options['max_completion_tokens'],
            'response_format' => $options['response_format']
        );
        
        $this->add_debug('generate_text', array(
            'endpoint' => $api_endpoint,
            'model' => $request_body['model'],
            'prompt_length' => strlen($prompt),
            'temperature' => $request_body['temperature'],
            'max_completion_tokens' => $request_body['max_completion_tokens']
        ));
        
        // Prepare request headers
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        );
        
        // Make the API request using WordPress HTTP API
        $response = wp_remote_post($api_endpoint, array(
            'headers' => $headers,
            'body' => json_encode($request_body),
            'timeout' => 120, // 2 minutes timeout for longer content generation
            'sslverify' => true
        ));
        
        // Check for WordPress HTTP errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->add_debug('generate_text', array(
                'error' => 'WordPress HTTP Error',
                'message' => $error_message
            ));
            throw new Exception('API request failed: ' . $error_message);
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->add_debug('generate_text', array(
            'response_code' => $response_code,
            'response_length' => strlen($response_body)
        ));
        
        // Check for HTTP errors
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : 'Unknown API error';
            
            $this->add_debug('generate_text', array(
                'error' => 'OpenAI API Error',
                'code' => $response_code,
                'message' => $error_message,
                'full_response' => $error_data
            ));
            
            throw new Exception('OpenAI API error (' . $response_code . '): ' . $error_message);
        }
        
        // Parse the JSON response
        $response_data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->add_debug('generate_text', array(
                'error' => 'JSON Parse Error',
                'message' => json_last_error_msg(),
                'raw_response' => substr($response_body, 0, 500)
            ));
            throw new Exception('Failed to parse API response: ' . json_last_error_msg());
        }
        
        // Extract the content
        if (!isset($response_data['choices'][0]['message']['content'])) {
            $this->add_debug('generate_text', array(
                'error' => 'Invalid Response Structure',
                'full_api_response' => wp_json_encode($response_data, JSON_PRETTY_PRINT)
            ));
            throw new Exception('Invalid API response structure');
        }
        
        $content = $response_data['choices'][0]['message']['content'];
        
        // Log usage statistics and full API response
        $debug_data = array(
            'status' => 'success',
            'content_length' => strlen($content),
            'model' => isset($response_data['model']) ? $response_data['model'] : null,
            'finish_reason' => isset($response_data['choices'][0]['finish_reason']) ? $response_data['choices'][0]['finish_reason'] : null,
            'full_api_response' => $content  // Store the actual content for display
        );
        
        // Add usage statistics if available
        if (isset($response_data['usage'])) {
            $debug_data['usage'] = array(
                'prompt_tokens' => $response_data['usage']['prompt_tokens'],
                'completion_tokens' => $response_data['usage']['completion_tokens'],
                'total_tokens' => $response_data['usage']['total_tokens']
            );
        }
        
        $this->add_debug('generate_text', $debug_data);
        
        return array(
            'content' => $content,
            'usage' => isset($response_data['usage']) ? $response_data['usage'] : null,
            'model' => isset($response_data['model']) ? $response_data['model'] : null
        );
    }
    
    /**
     * Generate image using OpenAI Image Generation API
     * 
     * @param string $prompt The image description prompt
     * @param string $api_key OpenAI API key
     * @param array $options Optional parameters (model, size, quality, etc.)
     * @return array API response data
     * @throws Exception If API call fails
     */
    public function generate_image($prompt, $api_key, $options = array()) {
        $this->add_debug('generate_image', 'Preparing image generation request.');
        
        // Validate API key
        if (empty($api_key)) {
            throw new Exception('OpenAI API key is missing');
        }
        
        // Prepare the API endpoint
        $api_endpoint = 'https://api.openai.com/v1/images/generations';
        
        // Default options
        $defaults = array(
            'model' => 'gpt-image-1.5',
            'size' => 'auto',  // Supported: 'auto', '1024x1024', '1024x1536', '1536x1024'
            'quality' => 'auto',  // 'low', 'medium', 'high', or 'auto'
            'n' => 1  // Number of images to generate
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Prepare the request body (gpt-image returns b64_json by default)
        $request_body = array(
            'model' => $options['model'],
            'prompt' => $prompt,
            'size' => $options['size'],
            'quality' => $options['quality'],
            'n' => $options['n']
        );
        
        $this->add_debug('generate_image', array(
            'endpoint' => $api_endpoint,
            'model' => $request_body['model'],
            'prompt_length' => strlen($prompt),
            'size' => $request_body['size'],
            'quality' => $request_body['quality']
        ));
        
        // Prepare request headers
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        );
        
        // Make the API request using WordPress HTTP API
        $response = wp_remote_post($api_endpoint, array(
            'headers' => $headers,
            'body' => json_encode($request_body),
            'timeout' => 120, // 2 minutes timeout
            'sslverify' => true
        ));
        
        // Check for WordPress HTTP errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->add_debug('generate_image', array(
                'error' => 'WordPress HTTP Error',
                'message' => $error_message
            ));
            throw new Exception('Image generation request failed: ' . $error_message);
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->add_debug('generate_image', array(
            'response_code' => $response_code,
            'response_length' => strlen($response_body)
        ));
        
        // Check for HTTP errors
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : 'Unknown API error';
            
            $this->add_debug('generate_image', array(
                'error' => 'Image API Error',
                'code' => $response_code,
                'message' => $error_message,
                'full_response' => $error_data
            ));
            
            throw new Exception('Image API error (' . $response_code . '): ' . $error_message);
        }
        
        // Parse the JSON response
        $response_data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->add_debug('generate_image', array(
                'error' => 'JSON Parse Error',
                'message' => json_last_error_msg(),
                'raw_response' => substr($response_body, 0, 500)
            ));
            throw new Exception('Failed to parse image API response: ' . json_last_error_msg());
        }
        
        // Validate response structure (gpt-image returns b64_json)
        if (!isset($response_data['data']) || !is_array($response_data['data']) || empty($response_data['data'])) {
            $this->add_debug('generate_image', array(
                'error' => 'Invalid Response Structure',
                'full_api_response' => wp_json_encode($response_data, JSON_PRETTY_PRINT)
            ));
            throw new Exception('Invalid image API response structure');
        }
        
        // Log success
        $this->add_debug('generate_image', array(
            'status' => 'success',
            'model' => $options['model'],
            'response_keys' => array_keys($response_data['data'][0])
        ));
        
        return $response_data;
    }
    
    /**
     * Add debug entry
     * 
     * @param string $step Step name
     * @param mixed $data Debug data
     */
    private function add_debug($step, $data) {
        if (is_callable($this->debug_callback)) {
            call_user_func($this->debug_callback, $step, $data);
        }
    }
}
