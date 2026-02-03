<?php
/**
 * Client Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$addressing = get_option('sparkwp_addressing', 'formal');
$company_name = get_option('sparkwp_company_name', '');
$industry = get_option('sparkwp_industry', '');
$target_group = get_option('sparkwp_target_group', '');
$usp = get_option('sparkwp_usp', '');
$advantages = get_option('sparkwp_advantages', '');
$buying_reasons = get_option('sparkwp_buying_reasons', '');
$additional_context = get_option('sparkwp_additional_context', '');
?>

<div class="sparkwp-tab-panel">
    <?php settings_errors('sparkwp_client_settings'); ?>
    
    <p class="description" style="margin-bottom: 20px;">
        <?php esc_html_e('Configure your client and business information. This data will be used to personalize AI-generated content.', 'sparkwp'); ?>
    </p>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('sparkwp_client_settings');
        do_settings_sections('sparkwp_client_settings');
        ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="sparkwp_addressing">
                            <?php esc_html_e('Addressing', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <select 
                            id="sparkwp_addressing" 
                            name="sparkwp_addressing" 
                            class="regular-text"
                        >
                            <option value="formal" <?php selected($addressing, 'formal'); ?>>
                                <?php esc_html_e('Formal (Sie)', 'sparkwp'); ?>
                            </option>
                            <option value="informal" <?php selected($addressing, 'informal'); ?>>
                                <?php esc_html_e('Informal (Du)', 'sparkwp'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Choose how the AI should address your target audience in the generated content.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkwp_company_name">
                            <?php esc_html_e('Company Name', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="sparkwp_company_name" 
                            name="sparkwp_company_name" 
                            value="<?php echo esc_attr($company_name); ?>" 
                            class="regular-text"
                            placeholder="<?php esc_attr_e('e.g., Acme Corporation', 'sparkwp'); ?>"
                        />
                        <p class="description">
                            <?php esc_html_e('Your company or brand name.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkwp_industry">
                            <?php esc_html_e('Industry', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="sparkwp_industry" 
                            name="sparkwp_industry" 
                            value="<?php echo esc_attr($industry); ?>" 
                            class="regular-text"
                            placeholder="<?php esc_attr_e('e.g., Software Development, E-commerce, Healthcare', 'sparkwp'); ?>"
                        />
                        <p class="description">
                            <?php esc_html_e('The industry or sector your business operates in.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkwp_target_group">
                            <?php esc_html_e('Target Group', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="sparkwp_target_group" 
                            name="sparkwp_target_group" 
                            value="<?php echo esc_attr($target_group); ?>" 
                            class="regular-text"
                            placeholder="<?php esc_attr_e('e.g., Small business owners, Tech professionals', 'sparkwp'); ?>"
                        />
                        <p class="description">
                            <?php esc_html_e('Describe your primary target audience or customer persona.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkwp_usp">
                            <?php esc_html_e('USP (Unique Selling Proposition)', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkwp_usp" 
                            name="sparkwp_usp" 
                            rows="4"
                            class="large-text"
                            placeholder="<?php esc_attr_e('What makes your product or service unique? What sets you apart from competitors?', 'sparkwp'); ?>"
                        ><?php echo esc_textarea($usp); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Your unique value proposition that differentiates you from competitors.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkwp_advantages">
                            <?php esc_html_e('Advantages of the Product', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkwp_advantages" 
                            name="sparkwp_advantages" 
                            rows="4"
                            class="large-text"
                            placeholder="<?php esc_attr_e('List the key advantages and benefits of your product or service...', 'sparkwp'); ?>"
                        ><?php echo esc_textarea($advantages); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Key benefits and advantages your customers gain from your product or service.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkwp_buying_reasons">
                            <?php esc_html_e('Reasons for Buying', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkwp_buying_reasons" 
                            name="sparkwp_buying_reasons" 
                            rows="4"
                            class="large-text"
                            placeholder="<?php esc_attr_e('Why should customers choose your product? What problems does it solve?', 'sparkwp'); ?>"
                        ><?php echo esc_textarea($buying_reasons); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Compelling reasons why customers should purchase your product or service.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkwp_additional_context">
                            <?php esc_html_e('Additional Context', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkwp_additional_context" 
                            name="sparkwp_additional_context" 
                            rows="5"
                            class="large-text"
                            placeholder="<?php esc_attr_e('Any additional information, brand voice guidelines, or context that should influence the content generation...', 'sparkwp'); ?>"
                        ><?php echo esc_textarea($additional_context); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Any other relevant information about your brand, tone of voice, or specific requirements.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
