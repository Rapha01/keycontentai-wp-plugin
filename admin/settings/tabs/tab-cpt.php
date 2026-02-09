<?php
/**
 * CPT Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get all post types including custom post types
$post_types = get_post_types(array('public' => true), 'objects');

// Filter out attachments and other unwanted post types
$available_post_types = array();
foreach ($post_types as $post_type) {
    if ($post_type->name !== 'attachment') {
        $available_post_types[$post_type->name] = $post_type;
    }
}

// Get current settings
// Check if post type is being changed via URL parameter
$selected_post_type = isset($_GET['cpt']) ? sanitize_key($_GET['cpt']) : get_option('sparkwp_selected_post_type', 'post');

// Get CPT configs from consolidated JSON structure
global $sparkwp;
$cpt_configs = $sparkwp->get_cpt_configs();

// Get field configurations for the selected post type
$current_field_configs = isset($cpt_configs[$selected_post_type]['fields']) ? $cpt_configs[$selected_post_type]['fields'] : array();

// Get additional context for the selected post type
$current_additional_context = isset($cpt_configs[$selected_post_type]['additional_context']) ? $cpt_configs[$selected_post_type]['additional_context'] : '';

// Get custom fields for the selected post type
$custom_fields = array();
if (!empty($selected_post_type)) {
    // Add WordPress core fields first
    $custom_fields[] = array(
        'key' => 'post_title',
        'label' => __('Post Title', 'sparkwp'),
        'type' => 'text',
        'source' => 'WP'
    );
    
    $custom_fields[] = array(
        'key' => 'post_content',
        'label' => __('Post Content', 'sparkwp'),
        'type' => 'wysiwyg',
        'source' => 'WP'
    );
    
    $custom_fields[] = array(
        'key' => 'post_excerpt',
        'label' => __('Post Excerpt', 'sparkwp'),
        'type' => 'textarea',
        'source' => 'WP'
    );
    
    $custom_fields[] = array(
        'key' => '_thumbnail_id',
        'label' => __('Featured Image', 'sparkwp'),
        'type' => 'image',
        'source' => 'WP'
    );
    
    // Get ACF fields
    if (function_exists('acf_get_field_groups')) {
        $field_groups = acf_get_field_groups(array('post_type' => $selected_post_type));
        foreach ($field_groups as $field_group) {
            $fields = acf_get_fields($field_group['key']);
            if ($fields) {
                foreach ($fields as $field) {
                    $custom_fields[] = array(
                        'key' => $field['name'],
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'source' => 'ACF'
                    );
                }
            }
        }
    }
}
?>

<div class="sparkwp-tab-panel">
    <?php settings_errors('sparkwp_cpt_settings'); ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('sparkwp_cpt_settings');
        do_settings_sections('sparkwp_cpt_settings');
        ?>
        
        <!-- Hidden field to save the currently selected post type -->
        <input type="hidden" name="sparkwp_selected_post_type" value="<?php echo esc_attr($selected_post_type); ?>" />
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="sparkwp_selected_post_type">
                            <?php esc_html_e('Select Post Type', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <select 
                            id="sparkwp_selected_post_type" 
                            name="sparkwp_selected_post_type_display" 
                            class="regular-text"
                        >
                            <?php foreach ($available_post_types as $post_type_name => $post_type_object) : ?>
                                <option 
                                    value="<?php echo esc_attr($post_type_name); ?>"
                                    <?php selected($selected_post_type, $post_type_name); ?>
                                >
                                    <?php echo esc_html($post_type_object->labels->singular_name); ?>
                                    <?php if ($post_type_name !== $post_type_object->labels->singular_name) : ?>
                                        (<?php echo esc_html($post_type_name); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select the post type where AI-generated content will be created. This includes all registered post types and custom post types from ACF.', 'sparkwp'); ?>
                        </p>
                        
                        <?php if (empty($available_post_types)) : ?>
                            <p class="description" style="color: #d63638;">
                                <?php esc_html_e('No post types found. Please make sure you have at least one post type registered.', 'sparkwp'); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php if (!empty($custom_fields)) : ?>
            <h2><?php esc_html_e('Custom Fields', 'sparkwp'); ?></h2>
            <p class="description">
                <?php esc_html_e('Add descriptions or prompts for each custom field. These will help the AI understand what content to generate for each field.', 'sparkwp'); ?>
            </p>
            
            <table class="form-table sparkwp-fields-table" role="presentation">
                <thead>
                    <tr>
                        <th style="width: 8%; text-align: center;"><?php esc_html_e('Generate', 'sparkwp'); ?></th>
                        <th style="width: 20%;"><?php esc_html_e('Field Name', 'sparkwp'); ?></th>
                        <th style="width: 10%;"><?php esc_html_e('Type', 'sparkwp'); ?></th>
                        <th style="width: 8%;"><?php esc_html_e('Source', 'sparkwp'); ?></th>
                        <th style="width: 45%;"><?php esc_html_e('Description / Prompt', 'sparkwp'); ?></th>
                        <th style="width: 9%;"><?php esc_html_e('WordCount/Dimensions', 'sparkwp'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_fields as $field) : ?>
                        <?php 
                        // Determine if this is an image field
                        $is_image_field = in_array($field['type'], array('image', 'file', 'gallery'));
                        $current_size = isset($current_field_configs[$field['key']]['size']) ? $current_field_configs[$field['key']]['size'] : 'auto';
                        $current_quality = isset($current_field_configs[$field['key']]['quality']) ? $current_field_configs[$field['key']]['quality'] : 'auto';
                        ?>
                        <tr>
                            <td data-label="<?php esc_attr_e('Generate', 'sparkwp'); ?>" style="text-align: center; vertical-align: middle;">
                                <label style="display: inline-block; margin: 0;">
                                    <input 
                                        type="checkbox" 
                                        name="sparkwp_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][enabled]"
                                        value="1"
                                        class="sparkwp-field-enable-checkbox"
                                        <?php 
                                        // Check if this field has been saved before
                                        if (isset($current_field_configs[$field['key']])) {
                                            // Use saved value
                                            checked($current_field_configs[$field['key']]['enabled'], true);
                                        } else {
                                            // Default to checked for new fields
                                            checked(true, true);
                                        }
                                        ?>
                                    />
                                    <span class="screen-reader-text">
                                        <?php echo esc_html(sprintf(__('Enable generation for %s', 'sparkwp'), $field['label'])); ?>
                                    </span>
                                </label>
                            </td>
                            <td data-label="<?php esc_attr_e('Field Name', 'sparkwp'); ?>">
                                <strong><?php echo esc_html($field['label']); ?></strong>
                                <br>
                                <code style="font-size: 11px; color: #666;"><?php echo esc_html($field['key']); ?></code>
                            </td>
                            <td data-label="<?php esc_attr_e('Type', 'sparkwp'); ?>">
                                <span class="sparkwp-field-type">
                                    <?php echo esc_html($field['type']); ?>
                                </span>
                            </td>
                            <td data-label="<?php esc_attr_e('Source', 'sparkwp'); ?>">
                                <span class="sparkwp-field-source sparkwp-source-<?php echo esc_attr(strtolower($field['source'])); ?>">
                                    <?php echo esc_html($field['source']); ?>
                                </span>
                            </td>
                            <td data-label="<?php esc_attr_e('Description / Prompt', 'sparkwp'); ?>">
                                <textarea 
                                    name="sparkwp_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][description]"
                                    rows="3"
                                    class="large-text"
                                    placeholder="<?php esc_attr_e('E.g., A brief summary of the content, maximum 150 characters', 'sparkwp'); ?>"
                                ><?php echo isset($current_field_configs[$field['key']]['description']) ? esc_textarea($current_field_configs[$field['key']]['description']) : ''; ?></textarea>
                            </td>
                            <td data-label="<?php esc_attr_e('WordCount/Dimensions', 'sparkwp'); ?>">
                                <?php if ($is_image_field) : ?>
                                    <!-- Image Size Dropdown -->
                                    <select 
                                        name="sparkwp_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][size]"
                                        style="width: 100%;"
                                    >
                                        <option value="auto" <?php selected($current_size, 'auto'); ?>><?php esc_html_e('Auto (Recommended)', 'sparkwp'); ?></option>
                                        <option value="1024x1024" <?php selected($current_size, '1024x1024'); ?>>1024 x 1024 (Square)</option>
                                        <option value="1024x1536" <?php selected($current_size, '1024x1536'); ?>>1024 x 1536 (Portrait)</option>
                                        <option value="1536x1024" <?php selected($current_size, '1536x1024'); ?>>1536 x 1024 (Landscape)</option>
                                    </select>
                                    
                                    <!-- Image Quality Dropdown -->
                                    <div style="margin-top: 8px;">
                                        <select 
                                            name="sparkwp_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][quality]"
                                            class="sparkwp-quality-select"
                                            data-field-key="<?php echo esc_attr($field['key']); ?>"
                                            style="width: 100%;"
                                        >
                                            <option value="auto" <?php selected($current_quality, 'auto'); ?>><?php esc_html_e('Auto (Recommended)', 'sparkwp'); ?></option>
                                            <option value="low" <?php selected($current_quality, 'low'); ?>><?php esc_html_e('Low', 'sparkwp'); ?></option>
                                            <option value="medium" <?php selected($current_quality, 'medium'); ?>><?php esc_html_e('Medium', 'sparkwp'); ?></option>
                                            <option value="high" <?php selected($current_quality, 'high'); ?>><?php esc_html_e('High', 'sparkwp'); ?></option>
                                        </select>
                                    </div>
                                <?php else : ?>
                                    <!-- Word Count Input for Text Fields -->
                                    <input 
                                        type="number" 
                                        name="sparkwp_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][word_count]"
                                        value="<?php echo isset($current_field_configs[$field['key']]['word_count']) ? esc_attr($current_field_configs[$field['key']]['word_count']) : ''; ?>"
                                        class="small-text"
                                        min="0"
                                        step="1"
                                        placeholder="<?php esc_attr_e('Words', 'sparkwp'); ?>"
                                    />
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="notice notice-info inline" style="margin: 20px 0;">
                <p>
                    <strong><?php esc_html_e('No custom fields found', 'sparkwp'); ?></strong><br>
                    <?php esc_html_e('This post type doesn\'t have any custom fields yet. Custom fields will appear here once they are added via ACF or other methods.', 'sparkwp'); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <h2><?php esc_html_e('Options', 'sparkwp'); ?></h2>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="sparkwp_cpt_additional_context_<?php echo esc_attr($selected_post_type); ?>">
                            <?php esc_html_e('Additional Context', 'sparkwp'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkwp_cpt_additional_context_<?php echo esc_attr($selected_post_type); ?>"
                            name="sparkwp_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][additional_context]"
                            rows="5"
                            class="large-text"
                            placeholder="<?php esc_attr_e('E.g., This post type is used for case studies. Focus on results and data. Always include a call-to-action at the end.', 'sparkwp'); ?>"
                        ><?php echo esc_textarea($current_additional_context); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Provide specific instructions or context for this post type. This will be used by the AI when generating content.', 'sparkwp'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle post type change
    $('#sparkwp_selected_post_type').on('change', function() {
        var selectedPostType = $(this).val();
        
        // Update hidden field
        $('input[name="sparkwp_selected_post_type"]').val(selectedPostType);
        
        // Build URL with current page parameters
        var currentUrl = window.location.href;
        var baseUrl = currentUrl.split('?')[0];
        var params = new URLSearchParams(window.location.search);
        params.set('cpt', selectedPostType);
        
        // Reload page with new post type parameter
        window.location.href = baseUrl + '?' + params.toString();
    });
    
    // Handle dimension dropdown change - show/hide custom dimension fields and update hidden inputs
    $('.sparkwp-dimension-select').on('change', function() {
        var fieldKey = $(this).data('field-key');
        var selectedValue = $(this).val();
        var customDimensionsDiv = $('.sparkwp-custom-dimensions[data-field-key="' + fieldKey + '"]');
        var widthInput = $('.sparkwp-dimension-width[data-field-key="' + fieldKey + '"]');
        var heightInput = $('.sparkwp-dimension-height[data-field-key="' + fieldKey + '"]');
        
        if (selectedValue === 'custom') {
            customDimensionsDiv.slideDown();
        } else {
            customDimensionsDiv.slideUp();
            
            // Parse the preset dimension and update hidden inputs
            var dimensions = selectedValue.split('x');
            if (dimensions.length === 2) {
                widthInput.val(dimensions[0]);
                heightInput.val(dimensions[1]);
            }
        }
    });
    
    // Handle custom dimension input changes - update hidden inputs
    $('.sparkwp-custom-width, .sparkwp-custom-height').on('input', function() {
        var fieldKey = $(this).data('field-key');
        var customWidth = $('.sparkwp-custom-width[data-field-key="' + fieldKey + '"]').val();
        var customHeight = $('.sparkwp-custom-height[data-field-key="' + fieldKey + '"]').val();
        var widthInput = $('.sparkwp-dimension-width[data-field-key="' + fieldKey + '"]');
        var heightInput = $('.sparkwp-dimension-height[data-field-key="' + fieldKey + '"]');
        
        widthInput.val(customWidth);
        heightInput.val(customHeight);
    });
});
</script>
