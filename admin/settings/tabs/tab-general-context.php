<?php
/**
 * General Context Settings Tab Content
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

// Get WYSIWYG formatting options
$wysiwyg_formatting = get_option('sparkwp_wysiwyg_formatting', array(
    'bold' => true,
    'italic' => true,
    'headings' => true,
    'lists' => true,
    'links' => true,
    'paragraphs' => true
));
?>

<div class="sparkwp-tab-panel">
    
    <p class="description" style="margin-bottom: 20px;">
        <?php esc_html_e('Configure your general context and business information. This data will be used to personalize AI-generated content.', 'sparkwp'); ?>
    </p>
    
    <form method="post" class="sparkwp-settings-form" data-tab="general-context">
        
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
        
        <h2><?php esc_html_e('WYSIWYG Formatting', 'sparkwp'); ?></h2>
        <p class="description" style="margin-bottom: 20px;">
            <?php esc_html_e('Select which HTML formatting elements the AI can use when generating content for WYSIWYG fields (like post content and rich text fields).', 'sparkwp'); ?>
        </p>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <?php esc_html_e('Allowed HTML Elements', 'sparkwp'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php esc_html_e('Allowed HTML Elements', 'sparkwp'); ?></span>
                            </legend>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkwp_wysiwyg_formatting[paragraphs]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['paragraphs']) ? $wysiwyg_formatting['paragraphs'] : true, true); ?>
                                />
                                <?php esc_html_e('Paragraphs', 'sparkwp'); ?>
                                <code>&lt;p&gt;</code>
                            </label>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkwp_wysiwyg_formatting[bold]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['bold']) ? $wysiwyg_formatting['bold'] : true, true); ?>
                                />
                                <?php esc_html_e('Bold', 'sparkwp'); ?>
                                <code>&lt;strong&gt;</code>
                            </label>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkwp_wysiwyg_formatting[italic]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['italic']) ? $wysiwyg_formatting['italic'] : true, true); ?>
                                />
                                <?php esc_html_e('Italic', 'sparkwp'); ?>
                                <code>&lt;em&gt;</code>
                            </label>
                            
                            <br>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkwp_wysiwyg_formatting[headings]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['headings']) ? $wysiwyg_formatting['headings'] : true, true); ?>
                                />
                                <?php esc_html_e('Headings', 'sparkwp'); ?>
                                <code>&lt;h2&gt; &lt;h3&gt; &lt;h4&gt;</code>
                            </label>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkwp_wysiwyg_formatting[lists]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['lists']) ? $wysiwyg_formatting['lists'] : true, true); ?>
                                />
                                <?php esc_html_e('Lists', 'sparkwp'); ?>
                                <code>&lt;ul&gt; &lt;ol&gt; &lt;li&gt;</code>
                            </label>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkwp_wysiwyg_formatting[links]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['links']) ? $wysiwyg_formatting['links'] : true, true); ?>
                                />
                                <?php esc_html_e('Links', 'sparkwp'); ?>
                                <code>&lt;a&gt;</code>
                            </label>
                            
                            <p class="description" style="margin-top: 10px;">
                                <?php esc_html_e('The AI will only use the selected HTML elements when generating content for WYSIWYG fields. Uncheck elements you don\'t want the AI to use.', 'sparkwp'); ?>
                            </p>
                        </fieldset>
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
