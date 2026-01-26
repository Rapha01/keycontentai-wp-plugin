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
$image_model = get_option('keycontentai_image_model', 'gpt-image-1.5');
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
                            <option value="gpt-5.2" <?php selected($text_model, 'gpt-5.2'); ?>>gpt-5.2</option>
                            <option value="gpt-5.2-pro" <?php selected($text_model, 'gpt-5.2-pro'); ?>>gpt-5.2-pro</option>
                            <option value="gpt-5.1" <?php selected($text_model, 'gpt-5.1'); ?>>gpt-5.1</option>
                            <option value="gpt-5" <?php selected($text_model, 'gpt-5'); ?>>gpt-5</option>
                            <option value="gpt-4.1" <?php selected($text_model, 'gpt-4.1'); ?>>gpt-4.1</option>
                            <option value="gpt-5-mini" <?php selected($text_model, 'gpt-5-mini'); ?>>gpt-5-mini</option>
                            <option value="gpt-5-nano" <?php selected($text_model, 'gpt-5-nano'); ?>>gpt-5-nano</option>
                            <option value="gpt-3.5-turbo" <?php selected($text_model, 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo</option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the GPT model to use for text content generation.', 'keycontentai'); ?>
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
                            <option value="gpt-image-1.5" <?php selected($image_model, 'gpt-image-1.5'); ?>>gpt-image-1.5</option>
                            <option value="gpt-image-1" <?php selected($image_model, 'gpt-image-1'); ?>>gpt-image-1</option>
                            <option value="gpt-image-1-mini" <?php selected($image_model, 'gpt-image-1-mini'); ?>>gpt-image-1-mini</option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the model to use for image generation.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
