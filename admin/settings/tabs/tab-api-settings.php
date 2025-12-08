<?php
/**
 * API Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('keycontentai_openai_api_key', '');
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
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
