<?php
/**
 * API Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('keycontentai_openai_api_key', '');
$text_model = get_option('keycontentai_text_model', 'gpt-4o-mini');
$image_model = get_option('keycontentai_image_model', 'dall-e-3');
?>

<div class="keycontentai-tab-panel">
    <?php settings_errors('keycontentai_api_settings'); ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('keycontentai_api_settings');
        do_settings_sections('keycontentai_api_settings');
        ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="keycontentai_openai_api_key">
                            <?php esc_html_e('OpenAI API Key', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="keycontentai_openai_api_key" 
                            name="keycontentai_openai_api_key" 
                            value="<?php echo esc_attr($api_key); ?>" 
                            class="regular-text"
                            placeholder="sk-..."
                        />
                        <p class="description">
                            <?php esc_html_e('Enter your OpenAI API key. You can get one from', 'keycontentai'); ?>
                            <a href="https://platform.openai.com/api-keys" target="_blank">
                                <?php esc_html_e('OpenAI Platform', 'keycontentai'); ?>
                            </a>
                        </p>
                        <?php if (!empty($api_key)) : ?>
                            <p>
                                <button type="button" id="keycontentai-fetch-models-btn" class="button button-secondary">
                                    <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                                    <?php esc_html_e('Fetch Available Models', 'keycontentai'); ?>
                                </button>
                                <span id="keycontentai-fetch-models-status" style="margin-left: 10px;"></span>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keycontentai_text_model">
                            <?php esc_html_e('Text Generation Model', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="keycontentai_text_model" name="keycontentai_text_model" class="regular-text">
                            <option value="<?php echo esc_attr($text_model); ?>">
                                <?php echo esc_html($text_model); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the GPT model to use for text content generation. Click "Fetch Available Models" to populate this dropdown with models from your API key.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keycontentai_image_model">
                            <?php esc_html_e('Image Generation Model', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="keycontentai_image_model" name="keycontentai_image_model" class="regular-text">
                            <option value="<?php echo esc_attr($image_model); ?>">
                                <?php echo esc_html($image_model); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the DALL-E model to use for image generation. Click "Fetch Available Models" to populate this dropdown with models from your API key.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
