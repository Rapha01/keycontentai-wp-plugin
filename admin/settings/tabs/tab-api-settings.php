<?php
/**
 * API Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// IIFE pattern to avoid globally-scoped variables (WordPress coding standards requirement).
(function() {

$api_key = get_option('sparkwp_openai_api_key', '');
$text_model = get_option('sparkwp_text_model', 'gpt-5.2');
$image_model = get_option('sparkwp_image_model', 'gpt-image-1.5');
?>

<div class="sparkwp-tab-panel">
    
    <form method="post" class="sparkwp-settings-form" data-tab="api-settings">
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="sparkwp_openai_api_key">
                            <?php esc_html_e('OpenAI API Key', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="sparkwp_openai_api_key" 
                            name="sparkwp_openai_api_key" 
                            value="<?php echo esc_attr($api_key); ?>" 
                            class="regular-text"
                            placeholder="sk-..."
                        />
                        <p class="description">
                            <?php esc_html_e('Enter your OpenAI API key. You can get one from', 'sparkwp'); ?>
                            <a href="https://platform.openai.com/api-keys" target="_blank">
                                <?php esc_html_e('OpenAI Platform', 'sparkwp'); ?>
                            </a>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkwp_text_model">
                            <?php esc_html_e('Text Generation Model', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="sparkwp_text_model" name="sparkwp_text_model" class="regular-text">
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
                            <?php esc_html_e('Select the GPT model to use for text content generation.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkwp_image_model">
                            <?php esc_html_e('Image Generation Model', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="sparkwp_image_model" name="sparkwp_image_model" class="regular-text">
                            <option value="gpt-image-1.5" <?php selected($image_model, 'gpt-image-1.5'); ?>>gpt-image-1.5</option>
                            <option value="gpt-image-1" <?php selected($image_model, 'gpt-image-1'); ?>>gpt-image-1</option>
                            <option value="gpt-image-1-mini" <?php selected($image_model, 'gpt-image-1-mini'); ?>>gpt-image-1-mini</option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the model to use for image generation.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
            <input type="submit" class="button button-primary sparkwp-save-button" value="<?php esc_attr_e('Save Changes', 'sparkwp'); ?>" />
            <span class="sparkwp-save-status"></span>
        </p>
    </form>
</div>

<?php
})(); // End IIFE
