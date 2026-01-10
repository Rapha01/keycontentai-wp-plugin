<?php
/**
 * Client Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$addressing = get_option('keycontentai_addressing', 'formal');
$company_name = get_option('keycontentai_company_name', '');
$industry = get_option('keycontentai_industry', '');
$target_group = get_option('keycontentai_target_group', '');
$usp = get_option('keycontentai_usp', '');
$advantages = get_option('keycontentai_advantages', '');
$buying_reasons = get_option('keycontentai_buying_reasons', '');
$additional_context = get_option('keycontentai_additional_context', '');
?>

<div class="keycontentai-tab-panel">
    <?php settings_errors('keycontentai_client_settings'); ?>
    
    <p class="description" style="margin-bottom: 20px;">
        <?php esc_html_e('Configure your client and business information. This data will be used to personalize AI-generated content.', 'keycontentai'); ?>
    </p>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('keycontentai_client_settings');
        do_settings_sections('keycontentai_client_settings');
        ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="keycontentai_addressing">
                            <?php esc_html_e('Addressing', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <select 
                            id="keycontentai_addressing" 
                            name="keycontentai_addressing" 
                            class="regular-text"
                        >
                            <option value="formal" <?php selected($addressing, 'formal'); ?>>
                                <?php esc_html_e('Formal (Sie)', 'keycontentai'); ?>
                            </option>
                            <option value="informal" <?php selected($addressing, 'informal'); ?>>
                                <?php esc_html_e('Informal (Du)', 'keycontentai'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Choose how the AI should address your target audience in the generated content.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keycontentai_company_name">
                            <?php esc_html_e('Company Name', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="keycontentai_company_name" 
                            name="keycontentai_company_name" 
                            value="<?php echo esc_attr($company_name); ?>" 
                            class="regular-text"
                            placeholder="<?php esc_attr_e('e.g., Acme Corporation', 'keycontentai'); ?>"
                        />
                        <p class="description">
                            <?php esc_html_e('Your company or brand name.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keycontentai_industry">
                            <?php esc_html_e('Industry', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="keycontentai_industry" 
                            name="keycontentai_industry" 
                            value="<?php echo esc_attr($industry); ?>" 
                            class="regular-text"
                            placeholder="<?php esc_attr_e('e.g., Software Development, E-commerce, Healthcare', 'keycontentai'); ?>"
                        />
                        <p class="description">
                            <?php esc_html_e('The industry or sector your business operates in.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keycontentai_target_group">
                            <?php esc_html_e('Target Group', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="keycontentai_target_group" 
                            name="keycontentai_target_group" 
                            value="<?php echo esc_attr($target_group); ?>" 
                            class="regular-text"
                            placeholder="<?php esc_attr_e('e.g., Small business owners, Tech professionals', 'keycontentai'); ?>"
                        />
                        <p class="description">
                            <?php esc_html_e('Describe your primary target audience or customer persona.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keycontentai_usp">
                            <?php esc_html_e('USP (Unique Selling Proposition)', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="keycontentai_usp" 
                            name="keycontentai_usp" 
                            rows="4"
                            class="large-text"
                            placeholder="<?php esc_attr_e('What makes your product or service unique? What sets you apart from competitors?', 'keycontentai'); ?>"
                        ><?php echo esc_textarea($usp); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Your unique value proposition that differentiates you from competitors.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keycontentai_advantages">
                            <?php esc_html_e('Advantages of the Product', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="keycontentai_advantages" 
                            name="keycontentai_advantages" 
                            rows="4"
                            class="large-text"
                            placeholder="<?php esc_attr_e('List the key advantages and benefits of your product or service...', 'keycontentai'); ?>"
                        ><?php echo esc_textarea($advantages); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Key benefits and advantages your customers gain from your product or service.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keycontentai_buying_reasons">
                            <?php esc_html_e('Reasons for Buying', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="keycontentai_buying_reasons" 
                            name="keycontentai_buying_reasons" 
                            rows="4"
                            class="large-text"
                            placeholder="<?php esc_attr_e('Why should customers choose your product? What problems does it solve?', 'keycontentai'); ?>"
                        ><?php echo esc_textarea($buying_reasons); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Compelling reasons why customers should purchase your product or service.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="keycontentai_additional_context">
                            <?php esc_html_e('Additional Context', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="keycontentai_additional_context" 
                            name="keycontentai_additional_context" 
                            rows="5"
                            class="large-text"
                            placeholder="<?php esc_attr_e('Any additional information, brand voice guidelines, or context that should influence the content generation...', 'keycontentai'); ?>"
                        ><?php echo esc_textarea($additional_context); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Any other relevant information about your brand, tone of voice, or specific requirements.', 'keycontentai'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
