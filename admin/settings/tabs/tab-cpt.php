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
$selected_post_type = isset($_GET['cpt']) ? sanitize_key($_GET['cpt']) : get_option('keycontentai_selected_post_type', 'post');

// Get CPT configs from consolidated JSON structure
global $keycontentai;
$cpt_configs = $keycontentai->get_cpt_configs();

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
        'label' => __('Post Title', 'keycontentai'),
        'type' => 'text',
        'source' => 'WP'
    );
    
    $custom_fields[] = array(
        'key' => 'post_content',
        'label' => __('Post Content', 'keycontentai'),
        'type' => 'wysiwyg',
        'source' => 'WP'
    );
    
    $custom_fields[] = array(
        'key' => 'post_excerpt',
        'label' => __('Post Excerpt', 'keycontentai'),
        'type' => 'textarea',
        'source' => 'WP'
    );
    
    $custom_fields[] = array(
        'key' => '_thumbnail_id',
        'label' => __('Featured Image', 'keycontentai'),
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

<div class="keycontentai-tab-panel">
    <?php settings_errors('keycontentai_cpt_settings'); ?>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('keycontentai_cpt_settings');
        do_settings_sections('keycontentai_cpt_settings');
        ?>
        
        <!-- Hidden field to save the currently selected post type -->
        <input type="hidden" name="keycontentai_selected_post_type" value="<?php echo esc_attr($selected_post_type); ?>" />
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="keycontentai_selected_post_type">
                            <?php esc_html_e('Select Post Type', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <select 
                            id="keycontentai_selected_post_type" 
                            name="keycontentai_selected_post_type_display" 
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
                            <?php esc_html_e('Select the post type where AI-generated content will be created. This includes all registered post types and custom post types from ACF.', 'keycontentai'); ?>
                        </p>
                        
                        <?php if (empty($available_post_types)) : ?>
                            <p class="description" style="color: #d63638;">
                                <?php esc_html_e('No post types found. Please make sure you have at least one post type registered.', 'keycontentai'); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php if (!empty($custom_fields)) : ?>
            <h2><?php esc_html_e('Custom Fields', 'keycontentai'); ?></h2>
            <p class="description">
                <?php esc_html_e('Add descriptions or prompts for each custom field. These will help the AI understand what content to generate for each field.', 'keycontentai'); ?>
            </p>
            
            <table class="form-table keycontentai-fields-table" role="presentation">
                <thead>
                    <tr>
                        <th style="width: 18%;"><?php esc_html_e('Field Name', 'keycontentai'); ?></th>
                        <th style="width: 10%;"><?php esc_html_e('Type', 'keycontentai'); ?></th>
                        <th style="width: 8%;"><?php esc_html_e('Source', 'keycontentai'); ?></th>
                        <th style="width: 40%;"><?php esc_html_e('Description / Prompt', 'keycontentai'); ?></th>
                        <th style="width: 12%;"><?php esc_html_e('WordCount/Dimensions', 'keycontentai'); ?></th>
                        <th style="width: 12%; text-align: center;"><?php esc_html_e('Generate', 'keycontentai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_fields as $field) : ?>
                        <?php 
                        // Determine if this is an image field
                        $is_image_field = in_array($field['type'], array('image', 'file', 'gallery'));
                        $current_width = isset($current_field_configs[$field['key']]['width']) ? $current_field_configs[$field['key']]['width'] : 1024;
                        $current_height = isset($current_field_configs[$field['key']]['height']) ? $current_field_configs[$field['key']]['height'] : 1024;
                        
                        // Determine which preset matches current dimensions (if any)
                        $current_dimension = 'custom';
                        $presets = array(
                            '1024x1024' => array(1024, 1024),
                            '1792x1024' => array(1792, 1024),
                            '1024x1792' => array(1024, 1792),
                            '512x512' => array(512, 512),
                            '1920x1080' => array(1920, 1080),
                            '1280x720' => array(1280, 720)
                        );
                        foreach ($presets as $preset_key => $preset_dims) {
                            if ($current_width == $preset_dims[0] && $current_height == $preset_dims[1]) {
                                $current_dimension = $preset_key;
                                break;
                            }
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($field['label']); ?></strong>
                                <br>
                                <code style="font-size: 11px; color: #666;"><?php echo esc_html($field['key']); ?></code>
                            </td>
                            <td>
                                <span class="keycontentai-field-type">
                                    <?php echo esc_html($field['type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="keycontentai-field-source keycontentai-source-<?php echo esc_attr(strtolower($field['source'])); ?>">
                                    <?php echo esc_html($field['source']); ?>
                                </span>
                            </td>
                            <td>
                                <textarea 
                                    name="keycontentai_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][description]"
                                    rows="3"
                                    class="large-text"
                                    placeholder="<?php esc_attr_e('E.g., A brief summary of the content, maximum 150 characters', 'keycontentai'); ?>"
                                ><?php echo isset($current_field_configs[$field['key']]['description']) ? esc_textarea($current_field_configs[$field['key']]['description']) : ''; ?></textarea>
                            </td>
                            <td>
                                <?php if ($is_image_field) : ?>
                                    <!-- Hidden inputs for actual width/height values -->
                                    <input 
                                        type="hidden" 
                                        name="keycontentai_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][width]"
                                        class="keycontentai-dimension-width"
                                        data-field-key="<?php echo esc_attr($field['key']); ?>"
                                        value="<?php echo esc_attr($current_width); ?>"
                                    />
                                    <input 
                                        type="hidden" 
                                        name="keycontentai_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][height]"
                                        class="keycontentai-dimension-height"
                                        data-field-key="<?php echo esc_attr($field['key']); ?>"
                                        value="<?php echo esc_attr($current_height); ?>"
                                    />
                                    
                                    <!-- Image Dimension Dropdown (UI only) -->
                                    <select 
                                        class="regular-text keycontentai-dimension-select"
                                        data-field-key="<?php echo esc_attr($field['key']); ?>"
                                    >
                                        <option value="1024x1024" <?php selected($current_dimension, '1024x1024'); ?>>1024 x 1024 (Square)</option>
                                        <option value="1792x1024" <?php selected($current_dimension, '1792x1024'); ?>>1792 x 1024 (Landscape)</option>
                                        <option value="1024x1792" <?php selected($current_dimension, '1024x1792'); ?>>1024 x 1792 (Portrait)</option>
                                        <option value="512x512" <?php selected($current_dimension, '512x512'); ?>>512 x 512 (Small Square)</option>
                                        <option value="1920x1080" <?php selected($current_dimension, '1920x1080'); ?>>1920 x 1080 (Full HD)</option>
                                        <option value="1280x720" <?php selected($current_dimension, '1280x720'); ?>>1280 x 720 (HD)</option>
                                        <option value="custom" <?php selected($current_dimension, 'custom'); ?>>Custom Dimensions</option>
                                    </select>
                                    
                                    <!-- Custom Dimension Fields -->
                                    <div class="keycontentai-custom-dimensions" data-field-key="<?php echo esc_attr($field['key']); ?>" style="margin-top: 8px; <?php echo $current_dimension !== 'custom' ? 'display: none;' : ''; ?>">
                                        <input 
                                            type="number" 
                                            class="small-text keycontentai-custom-width"
                                            data-field-key="<?php echo esc_attr($field['key']); ?>"
                                            value="<?php echo $current_dimension === 'custom' ? esc_attr($current_width) : ''; ?>"
                                            min="64"
                                            max="4096"
                                            step="1"
                                            placeholder="Width"
                                        />
                                        <span style="margin: 0 5px;">×</span>
                                        <input 
                                            type="number" 
                                            class="small-text keycontentai-custom-height"
                                            data-field-key="<?php echo esc_attr($field['key']); ?>"
                                            value="<?php echo $current_dimension === 'custom' ? esc_attr($current_height) : ''; ?>"
                                            min="64"
                                            max="4096"
                                            step="1"
                                            placeholder="Height"
                                        />
                                        <p class="description">
                                            <?php esc_html_e('Width × Height in pixels', 'keycontentai'); ?>
                                        </p>
                                    </div>
                                <?php else : ?>
                                    <!-- Word Count Input for Text Fields -->
                                    <input 
                                        type="number" 
                                        name="keycontentai_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][word_count]"
                                        value="<?php echo isset($current_field_configs[$field['key']]['word_count']) ? esc_attr($current_field_configs[$field['key']]['word_count']) : ''; ?>"
                                        class="small-text"
                                        min="0"
                                        step="1"
                                        placeholder="<?php esc_attr_e('Words', 'keycontentai'); ?>"
                                    />
                                    <p class="description">
                                        <?php esc_html_e('Target word count', 'keycontentai'); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                <label style="display: inline-block; margin: 0;">
                                    <input 
                                        type="checkbox" 
                                        name="keycontentai_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][enabled]"
                                        value="1"
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
                                        <?php echo esc_html(sprintf(__('Enable generation for %s', 'keycontentai'), $field['label'])); ?>
                                    </span>
                                </label>
                                <p class="description" style="margin-top: 5px;">
                                    <?php esc_html_e('Enable AI generation', 'keycontentai'); ?>
                                </p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="notice notice-info inline" style="margin: 20px 0;">
                <p>
                    <strong><?php esc_html_e('No custom fields found', 'keycontentai'); ?></strong><br>
                    <?php esc_html_e('This post type doesn\'t have any custom fields yet. Custom fields will appear here once they are added via ACF or other methods.', 'keycontentai'); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <h2><?php esc_html_e('Options', 'keycontentai'); ?></h2>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="keycontentai_cpt_additional_context_<?php echo esc_attr($selected_post_type); ?>">
                            <?php esc_html_e('Additional Context', 'keycontentai'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="keycontentai_cpt_additional_context_<?php echo esc_attr($selected_post_type); ?>"
                            name="keycontentai_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][additional_context]"
                            rows="5"
                            class="large-text"
                            placeholder="<?php esc_attr_e('E.g., This post type is used for case studies. Focus on results and data. Always include a call-to-action at the end.', 'keycontentai'); ?>"
                        ><?php echo esc_textarea($current_additional_context); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Provide specific instructions or context for this post type. This will be used by the AI when generating content.', 'keycontentai'); ?>
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
    $('#keycontentai_selected_post_type').on('change', function() {
        var selectedPostType = $(this).val();
        
        // Update hidden field
        $('input[name="keycontentai_selected_post_type"]').val(selectedPostType);
        
        // Build URL with current page parameters
        var currentUrl = window.location.href;
        var baseUrl = currentUrl.split('?')[0];
        var params = new URLSearchParams(window.location.search);
        params.set('cpt', selectedPostType);
        
        // Reload page with new post type parameter
        window.location.href = baseUrl + '?' + params.toString();
    });
    
    // Handle dimension dropdown change - show/hide custom dimension fields and update hidden inputs
    $('.keycontentai-dimension-select').on('change', function() {
        var fieldKey = $(this).data('field-key');
        var selectedValue = $(this).val();
        var customDimensionsDiv = $('.keycontentai-custom-dimensions[data-field-key="' + fieldKey + '"]');
        var widthInput = $('.keycontentai-dimension-width[data-field-key="' + fieldKey + '"]');
        var heightInput = $('.keycontentai-dimension-height[data-field-key="' + fieldKey + '"]');
        
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
    $('.keycontentai-custom-width, .keycontentai-custom-height').on('input', function() {
        var fieldKey = $(this).data('field-key');
        var customWidth = $('.keycontentai-custom-width[data-field-key="' + fieldKey + '"]').val();
        var customHeight = $('.keycontentai-custom-height[data-field-key="' + fieldKey + '"]').val();
        var widthInput = $('.keycontentai-dimension-width[data-field-key="' + fieldKey + '"]');
        var heightInput = $('.keycontentai-dimension-height[data-field-key="' + fieldKey + '"]');
        
        widthInput.val(customWidth);
        heightInput.val(customHeight);
    });
});
</script>
