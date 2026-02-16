<?php
/**
 * General Context Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// IIFE pattern to avoid globally-scoped variables (WordPress coding standards requirement).
(function() {

// Get current settings
$addressing = get_option('sparkplus_addressing', 'formal');
$company_name = get_option('sparkplus_company_name', '');
$industry = get_option('sparkplus_industry', '');
$target_group = get_option('sparkplus_target_group', '');
$usp = get_option('sparkplus_usp', '');
$advantages = get_option('sparkplus_advantages', '');
$buying_reasons = get_option('sparkplus_buying_reasons', '');
$additional_context = get_option('sparkplus_additional_context', '');

// Get WYSIWYG formatting options
$wysiwyg_formatting = get_option('sparkplus_wysiwyg_formatting', array(
    'bold' => true,
    'italic' => true,
    'headings' => false,
    'lists' => true,
    'links' => false,
    'paragraphs' => true
));
?>

<div class="sparkplus-tab-panel">
    
    <p class="description" style="margin-bottom: 20px;">
        <?php esc_html_e('Configure your general context and business information. This data will be used to personalize AI-generated content.', 'sparkplus'); ?>
    </p>
    
    <form method="post" class="sparkplus-settings-form" data-tab="general-context">
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_addressing">
                            <?php esc_html_e('Addressing', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <select 
                            id="sparkplus_addressing" 
                            name="sparkplus_addressing" 
                            class="regular-text"
                        >
                            <option value="formal" <?php selected($addressing, 'formal'); ?>>
                                <?php esc_html_e('Formal (Sie)', 'sparkplus'); ?>
                            </option>
                            <option value="informal" <?php selected($addressing, 'informal'); ?>>
                                <?php esc_html_e('Informal (Du)', 'sparkplus'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Choose how the AI should address your target audience in the generated content.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkplus_company_name">
                            <?php esc_html_e('Company Name', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="sparkplus_company_name" 
                            name="sparkplus_company_name" 
                            value="<?php echo esc_attr($company_name); ?>" 
                            class="regular-text"
                            placeholder="<?php esc_attr_e('e.g., Acme Corporation', 'sparkplus'); ?>"
                        />
                        <p class="description">
                            <?php esc_html_e('Your company or brand name.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkplus_industry">
                            <?php esc_html_e('Industry', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="sparkplus_industry" 
                            name="sparkplus_industry" 
                            value="<?php echo esc_attr($industry); ?>" 
                            class="regular-text"
                            placeholder="<?php esc_attr_e('e.g., Software Development, E-commerce, Healthcare', 'sparkplus'); ?>"
                        />
                        <p class="description">
                            <?php esc_html_e('The industry or sector your business operates in.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkplus_target_group">
                            <?php esc_html_e('Target Group', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="sparkplus_target_group" 
                            name="sparkplus_target_group" 
                            value="<?php echo esc_attr($target_group); ?>" 
                            class="regular-text"
                            placeholder="<?php esc_attr_e('e.g., Small business owners, Tech professionals', 'sparkplus'); ?>"
                        />
                        <p class="description">
                            <?php esc_html_e('Describe your primary target audience or customer persona.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkplus_usp">
                            <?php esc_html_e('USP (Unique Selling Proposition)', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkplus_usp" 
                            name="sparkplus_usp" 
                            rows="4"
                            class="large-text"
                            placeholder="<?php esc_attr_e('What makes your product or service unique? What sets you apart from competitors?', 'sparkplus'); ?>"
                        ><?php echo esc_textarea($usp); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Your unique value proposition that differentiates you from competitors.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkplus_advantages">
                            <?php esc_html_e('Advantages of the Product', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkplus_advantages" 
                            name="sparkplus_advantages" 
                            rows="4"
                            class="large-text"
                            placeholder="<?php esc_attr_e('List the key advantages and benefits of your product or service...', 'sparkplus'); ?>"
                        ><?php echo esc_textarea($advantages); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Key benefits and advantages your customers gain from your product or service.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkplus_buying_reasons">
                            <?php esc_html_e('Reasons for Buying', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkplus_buying_reasons" 
                            name="sparkplus_buying_reasons" 
                            rows="4"
                            class="large-text"
                            placeholder="<?php esc_attr_e('Why should customers choose your product? What problems does it solve?', 'sparkplus'); ?>"
                        ><?php echo esc_textarea($buying_reasons); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Compelling reasons why customers should purchase your product or service.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sparkplus_additional_context">
                            <?php esc_html_e('Additional Context', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkplus_additional_context" 
                            name="sparkplus_additional_context" 
                            rows="5"
                            class="large-text"
                            placeholder="<?php esc_attr_e('Any additional information, brand voice guidelines, or context that should influence the content generation...', 'sparkplus'); ?>"
                        ><?php echo esc_textarea($additional_context); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Any other relevant information about your brand, tone of voice, or specific requirements.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php esc_html_e('WYSIWYG Formatting', 'sparkplus'); ?></h2>
        <p class="description" style="margin-bottom: 20px;">
            <?php esc_html_e('Select which HTML formatting elements the AI can use when generating content for WYSIWYG fields (like post content and rich text fields).', 'sparkplus'); ?>
        </p>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <?php esc_html_e('Allowed HTML Elements', 'sparkplus'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php esc_html_e('Allowed HTML Elements', 'sparkplus'); ?></span>
                            </legend>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkplus_wysiwyg_formatting[paragraphs]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['paragraphs']) ? $wysiwyg_formatting['paragraphs'] : true, true); ?>
                                />
                                <?php esc_html_e('Paragraphs', 'sparkplus'); ?>
                                <code>&lt;p&gt;</code>
                            </label>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkplus_wysiwyg_formatting[bold]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['bold']) ? $wysiwyg_formatting['bold'] : true, true); ?>
                                />
                                <?php esc_html_e('Bold', 'sparkplus'); ?>
                                <code>&lt;strong&gt;</code>
                            </label>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkplus_wysiwyg_formatting[italic]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['italic']) ? $wysiwyg_formatting['italic'] : true, true); ?>
                                />
                                <?php esc_html_e('Italic', 'sparkplus'); ?>
                                <code>&lt;em&gt;</code>
                            </label>
                            
                            <br>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkplus_wysiwyg_formatting[headings]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['headings']) ? $wysiwyg_formatting['headings'] : true, true); ?>
                                />
                                <?php esc_html_e('Headings', 'sparkplus'); ?>
                                <code>&lt;h2&gt; &lt;h3&gt; &lt;h4&gt;</code>
                            </label>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkplus_wysiwyg_formatting[lists]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['lists']) ? $wysiwyg_formatting['lists'] : true, true); ?>
                                />
                                <?php esc_html_e('Lists', 'sparkplus'); ?>
                                <code>&lt;ul&gt; &lt;ol&gt; &lt;li&gt;</code>
                            </label>
                            
                            <label style="display: inline-block; margin-right: 20px; margin-bottom: 10px;">
                                <input 
                                    type="checkbox" 
                                    name="sparkplus_wysiwyg_formatting[links]" 
                                    value="1"
                                    <?php checked(isset($wysiwyg_formatting['links']) ? $wysiwyg_formatting['links'] : true, true); ?>
                                />
                                <?php esc_html_e('Links', 'sparkplus'); ?>
                                <code>&lt;a&gt;</code>
                            </label>
                            
                            <p class="description" style="margin-top: 10px;">
                                <?php esc_html_e('The AI will only use the selected HTML elements when generating content for WYSIWYG fields. Uncheck elements you don\'t want the AI to use.', 'sparkplus'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
            <input type="submit" class="button button-primary sparkplus-save-button" value="<?php esc_attr_e('Save Changes', 'sparkplus'); ?>" />
            <span class="sparkplus-save-status"></span>
        </p>
    </form>
</div>

<?php
})(); // End IIFE
