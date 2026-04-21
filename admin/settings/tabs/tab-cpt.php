<?php
/**
 * CPT Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// IIFE pattern to avoid globally-scoped variables (WordPress coding standards requirement).
(function() {
global $sparkplus;

// Get all post types including custom post types
$post_types = get_post_types(array('public' => true), 'objects');

// Filter out attachments and other unwanted post types
$available_post_types = array();
foreach ($post_types as $post_type) {
    if ($post_type->name !== 'attachment') {
        $available_post_types[$post_type->name] = $post_type;
    }
}

// Check if post type is being changed via URL parameter
$selected_post_type = isset($_GET['cpt']) ? sanitize_key(wp_unslash($_GET['cpt'])) : get_option('sparkplus_selected_post_type', 'post');

// Get CPT configs from consolidated JSON structure
$cpt_configs = $sparkplus->get_cpt_configs();

// Get field configurations for the selected post type
$current_field_configs = isset($cpt_configs[$selected_post_type]['fields']) ? $cpt_configs[$selected_post_type]['fields'] : array();

// Get additional context for the selected post type
$current_additional_context_text = isset($cpt_configs[$selected_post_type]['additional_context_text']) ? $cpt_configs[$selected_post_type]['additional_context_text'] : '';
$current_additional_context_image = isset($cpt_configs[$selected_post_type]['additional_context_image']) ? $cpt_configs[$selected_post_type]['additional_context_image'] : '';

// Get include existing content setting (defaults to true)
$include_existing_content = isset($cpt_configs[$selected_post_type]['include_existing_content']) ? $cpt_configs[$selected_post_type]['include_existing_content'] : true;

// Get include ACF instruction fields setting (defaults to false)
$include_acf_instructions = isset($cpt_configs[$selected_post_type]['include_acf_instructions']) ? $cpt_configs[$selected_post_type]['include_acf_instructions'] : false;

// Check if linking pool is enabled (used for post_object warnings)
$linking_pool_enabled = (bool) get_option('sparkplus_linking_enable', false);

// RankMath availability and per-post-type toggle
$rankmath_active  = defined('RANK_MATH_VERSION');
$include_rankmath = (bool) get_option('sparkplus_seo_rankmath_enable', false);

// Get custom fields for the selected post type
$custom_fields = array();
if (!empty($selected_post_type)) {
    // Add WordPress core fields first
    $custom_fields[] = array(
        'key' => 'post_title',
        'label' => __('Post Title', 'sparkplus'),
        'type' => 'text',
        'source' => 'WP'
    );
    
    $custom_fields[] = array(
        'key' => 'post_content',
        'label' => __('Post Content', 'sparkplus'),
        'type' => 'wysiwyg',
        'source' => 'WP'
    );
    
    $custom_fields[] = array(
        'key' => 'post_excerpt',
        'label' => __('Post Excerpt', 'sparkplus'),
        'type' => 'textarea',
        'source' => 'WP'
    );
    
    $custom_fields[] = array(
        'key' => '_thumbnail_id',
        'label' => __('Featured Image', 'sparkplus'),
        'type' => 'image',
        'source' => 'WP'
    );
    
    // Get ACF fields (with group support)
    if (function_exists('acf_get_field_groups')) {
        $field_groups = acf_get_field_groups(array('post_type' => $selected_post_type));
        foreach ($field_groups as $field_group) {
            $fields = acf_get_fields($field_group['key']);
            if ($fields) {
                foreach ($fields as $field) {
                    if ($field['type'] === 'group') {
                        $sub_fields = array();
                        if (!empty($field['sub_fields'])) {
                            foreach ($field['sub_fields'] as $sub_field) {
                                $sub_fields[] = array(
                                    'key'          => $sub_field['name'],
                                    'label'        => $sub_field['label'],
                                    'type'         => $sub_field['type'],
                                    'source'       => 'ACF',
                                    'group_key'    => $field['name'],
                                    'instructions' => isset($sub_field['instructions']) ? $sub_field['instructions'] : '',
                                );
                            }
                        }
                        $custom_fields[] = array(
                            'key'          => $field['name'],
                            'label'        => $field['label'],
                            'type'         => 'group',
                            'source'       => 'ACF',
                            'sub_fields'   => $sub_fields,
                            'instructions' => isset($field['instructions']) ? $field['instructions'] : '',
                        );
                    } else {
                        $custom_fields[] = array(
                            'key'          => $field['name'],
                            'label'        => $field['label'],
                            'type'         => $field['type'],
                            'source'       => 'ACF',
                            'instructions' => isset($field['instructions']) ? $field['instructions'] : '',
                        );
                    }
                }
            }
        }
    }

    // RankMath SEO fields (appended when RankMath is active and toggle is on)
    if ($rankmath_active && $include_rankmath) {
        $custom_fields[] = array(
            'key'    => 'rank_math_title',
            'label'  => __('SEO Title', 'sparkplus'),
            'type'   => 'text',
            'source' => 'RM',
        );
        $custom_fields[] = array(
            'key'    => 'rank_math_description',
            'label'  => __('SEO Description', 'sparkplus'),
            'type'   => 'textarea',
            'source' => 'RM',
        );
    }
}
?>

<div class="sparkplus-tab-panel">
    
    <form method="post" class="sparkplus-settings-form" data-tab="cpt">
        
        <!-- Hidden field to save the currently selected post type -->
        <input type="hidden" name="sparkplus_selected_post_type" value="<?php echo esc_attr($selected_post_type); ?>" />
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_selected_post_type">
                            <?php esc_html_e('Select Post Type', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <select 
                            id="sparkplus_selected_post_type" 
                            name="sparkplus_selected_post_type_display" 
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
                            <?php esc_html_e('Select the post type where AI-generated content will be created. This includes all registered post types and custom post types from ACF.', 'sparkplus'); ?>
                        </p>
                        
                        <?php if (empty($available_post_types)) : ?>
                            <p class="description" style="color: #d63638;">
                                <?php esc_html_e('No post types found. Please make sure you have at least one post type registered.', 'sparkplus'); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php if (!empty($custom_fields)) : ?>
            <h2><?php esc_html_e('Custom Fields', 'sparkplus'); ?></h2>
            <p class="description">
                <?php esc_html_e('Add descriptions or prompts for each custom field. These will help the AI understand what content to generate for each field.', 'sparkplus'); ?>
            </p>
            
            <table class="form-table sparkplus-fields-table" role="presentation">
                <thead>
                    <tr>
                        <th style="width: 6%; text-align: center;"><?php esc_html_e('Generate', 'sparkplus'); ?></th>
                        <th style="width: 6%; text-align: center;"><?php esc_html_e('Clear', 'sparkplus'); ?></th>
                        <th style="width: 18%;"><?php esc_html_e('Field Name', 'sparkplus'); ?></th>
                        <th style="width: 12%;"><?php esc_html_e('Type / Source', 'sparkplus'); ?></th>
                        <th style="width: 38%;"><?php esc_html_e('Description / Prompt', 'sparkplus'); ?></th>
                        <th style="width: 15%;"><?php esc_html_e('Text/Image Options', 'sparkplus'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_fields as $field) : ?>
                        <?php if ($field['type'] === 'group') : ?>
                            <?php
                            $group_cfg         = isset($current_field_configs[$field['key']]) ? $current_field_configs[$field['key']] : array();
                            $sub_count_total   = count($field['sub_fields']);
                            $sub_count_enabled = 0;
                            $sub_count_clear   = 0;
                            foreach ($field['sub_fields'] as $sf) {
                                $sf_on    = isset($group_cfg['sub_fields'][$sf['key']]['enabled']) ? $group_cfg['sub_fields'][$sf['key']]['enabled'] : false;
                                $sf_clear = isset($group_cfg['sub_fields'][$sf['key']]['clear']) ? $group_cfg['sub_fields'][$sf['key']]['clear'] : false;
                                if ($sf_on) { $sub_count_enabled++; }
                                if ($sf_clear) { $sub_count_clear++; }
                            }
                            ?>
                            <input type="hidden" name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][type]" value="group" />
                            <tr class="sparkplus-group-header-row" data-group="<?php echo esc_attr($field['key']); ?>">
                                <td data-label="<?php esc_attr_e('Generate', 'sparkplus'); ?>" style="text-align: center; vertical-align: middle;">
                                    <input
                                        type="checkbox"
                                        class="sparkplus-group-master-checkbox sparkplus-generate-checkbox"
                                        data-group="<?php echo esc_attr($field['key']); ?>"
                                        title="<?php esc_attr_e('Toggle generation for all fields in this group', 'sparkplus'); ?>"
                                        <?php if ($sub_count_total > 0 && $sub_count_enabled === $sub_count_total) { echo 'checked'; } ?>
                                    />
                                </td>
                                <td data-label="<?php esc_attr_e('Clear', 'sparkplus'); ?>" style="text-align: center; vertical-align: middle;">
                                    <input
                                        type="checkbox"
                                        class="sparkplus-group-master-clear-checkbox sparkplus-clear-checkbox"
                                        data-group="<?php echo esc_attr($field['key']); ?>"
                                        title="<?php esc_attr_e('Toggle clearing for all fields in this group', 'sparkplus'); ?>"
                                        <?php if ($sub_count_total > 0 && $sub_count_clear === $sub_count_total) { echo 'checked'; } ?>
                                    />
                                </td>
                                <td colspan="4" class="sparkplus-group-toggle" style="background: #f6f7f7; padding: 8px 12px; border-left: 3px solid #2271b1; cursor: pointer; user-select: none;">
                                    <span class="dashicons dashicons-arrow-right-alt2 sparkplus-group-chevron" style="vertical-align: middle; margin-right: 4px; transition: transform 0.2s ease;"></span>
                                    <strong><?php echo esc_html($field['label']); ?></strong>
                                    <code style="margin-left: 6px; font-size: 11px; color: #666;"><?php echo esc_html($field['key']); ?></code>
                                    <span style="margin-left: 8px; font-size: 11px; color: #777; background: #e0e0e0; padding: 1px 5px; border-radius: 3px;">Group &bull; ACF</span>
                                    <span class="sparkplus-group-count" style="margin-left: 6px; font-size: 11px; color: #888;">(<?php echo intval($sub_count_total); ?> <?php echo intval($sub_count_total) === 1 ? esc_html__('field', 'sparkplus') : esc_html__('fields', 'sparkplus'); ?>)</span>
                                </td>
                            </tr>
                            <?php foreach ($field['sub_fields'] as $sub_field) : ?>
                                <?php
                                $sub_is_image       = in_array($sub_field['type'], array('image', 'file', 'gallery'));
                                $sub_is_post_object = $sub_field['type'] === 'post_object';
                                $sub_hide_options   = $sub_field['type'] === 'true_false';
                                $sub_cfg      = isset($group_cfg['sub_fields'][$sub_field['key']]) ? $group_cfg['sub_fields'][$sub_field['key']] : array();
                                $sub_size     = isset($sub_cfg['size']) ? $sub_cfg['size'] : 'auto';
                                $sub_quality  = isset($sub_cfg['quality']) ? $sub_cfg['quality'] : 'auto';
                                $sub_webp     = isset($sub_cfg['webp_quality']) ? $sub_cfg['webp_quality'] : 80;
                                ?>
                                <tr class="sparkplus-sub-field-row" data-group="<?php echo esc_attr($field['key']); ?>" style="display: none;">
                                    <td data-label="<?php esc_attr_e('Generate', 'sparkplus'); ?>" style="text-align: center; vertical-align: middle;">
                                        <input
                                            type="checkbox"
                                            name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][sub_fields][<?php echo esc_attr($sub_field['key']); ?>][enabled]"
                                            value="1"
                                            class="sparkplus-field-enable-checkbox sparkplus-sub-field-checkbox sparkplus-generate-checkbox"
                                            data-group="<?php echo esc_attr($field['key']); ?>"
                                            <?php
                                            if (isset($sub_cfg['enabled'])) {
                                                checked($sub_cfg['enabled'], true);
                                            }
                                            ?>
                                        />
                                    </td>
                                    <td data-label="<?php esc_attr_e('Clear', 'sparkplus'); ?>" style="text-align: center; vertical-align: middle;">
                                        <input
                                            type="checkbox"
                                            name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][sub_fields][<?php echo esc_attr($sub_field['key']); ?>][clear]"
                                            value="1"
                                            class="sparkplus-field-clear-checkbox sparkplus-sub-field-clear-checkbox sparkplus-clear-checkbox"
                                            data-group="<?php echo esc_attr($field['key']); ?>"
                                            <?php checked(isset($sub_cfg['clear']) && $sub_cfg['clear'], true); ?>
                                        />
                                    </td>
                                    <td data-label="<?php esc_attr_e('Field Name', 'sparkplus'); ?>" style="padding-left: 24px;">
                                        <span style="color: #666; margin-right: 5px; font-family: monospace; font-size: 14px; vertical-align: middle;">&#x2514;&#x2500;</span>
                                        <strong><?php echo esc_html($sub_field['label']); ?></strong>
                                        <br>
                                        <code style="font-size: 11px; color: #666; margin-left: 22px;"><?php echo esc_html($sub_field['key']); ?></code>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Type / Source', 'sparkplus'); ?>">
                                        <span class="sparkplus-field-source sparkplus-source-acf"><?php echo esc_html($sub_field['source']); ?></span>
                                        <span class="sparkplus-field-type" style="margin-left: 4px;"><?php echo esc_html($sub_field['type']); ?></span>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Description / Prompt', 'sparkplus'); ?>">
                                        <?php if ($include_acf_instructions && !empty($sub_field['instructions'])) : ?>
                                            <p class="description" style="margin: 0 0 6px; padding: 5px 8px; background: #f0f4ff; border-left: 3px solid #7b9cde; border-radius: 2px; font-size: 12px; color: #444;">
                                                <strong style="color: #3a5ea8;"><?php esc_html_e('ACF Instructions:', 'sparkplus'); ?></strong>
                                                <?php echo esc_html($sub_field['instructions']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <textarea
                                            name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][sub_fields][<?php echo esc_attr($sub_field['key']); ?>][description]"
                                            rows="2"
                                            class="large-text"
                                            placeholder="<?php esc_attr_e('E.g., A brief summary of the content, maximum 150 characters', 'sparkplus'); ?>"
                                        ><?php echo isset($sub_cfg['description']) ? esc_textarea($sub_cfg['description']) : ''; ?></textarea>
                                        <?php if ($sub_is_post_object && !$linking_pool_enabled) : ?>
                                            <p class="sparkplus-post-object-warning" style="display: <?php echo (!empty($sub_cfg['enabled'])) ? 'block' : 'none'; ?>;">
                                                <span class="dashicons dashicons-warning"></span>
                                                <?php esc_html_e('The linking pool is not enabled. Enable it in the Internal Linking tab for this field to work. Make sure the linking pool contains the posts that are allowed for this post_object field.', 'sparkplus'); ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Text/Image Options', 'sparkplus'); ?>">
                                        <?php if ($sub_hide_options) : ?>
                                            <!-- No options for this field type -->
                                        <?php elseif ($sub_is_post_object) : ?>
                                            <label style="display: block; font-size: 10px; margin-bottom: 2px; color: #666;"><?php esc_html_e('Nr. of Posts', 'sparkplus'); ?></label>
                                            <input type="number" name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][sub_fields][<?php echo esc_attr($sub_field['key']); ?>][word_count]" value="<?php echo isset($sub_cfg['word_count']) ? esc_attr($sub_cfg['word_count']) : ''; ?>" class="small-text" min="1" step="1" placeholder="<?php esc_attr_e('Posts', 'sparkplus'); ?>" />
                                        <?php elseif ($sub_is_image) : ?>
                                            <label style="display: block; font-size: 10px; margin-bottom: 2px; color: #666;"><?php esc_html_e('Dimensions', 'sparkplus'); ?></label>
                                            <select name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][sub_fields][<?php echo esc_attr($sub_field['key']); ?>][size]" style="width: 100%;">
                                                <option value="auto" <?php selected($sub_size, 'auto'); ?>><?php esc_html_e('Auto (Recommended)', 'sparkplus'); ?></option>
                                                <option value="1024x1024" <?php selected($sub_size, '1024x1024'); ?>>1024 x 1024 (Square)</option>
                                                <option value="1024x1536" <?php selected($sub_size, '1024x1536'); ?>>1024 x 1536 (Portrait)</option>
                                                <option value="1536x1024" <?php selected($sub_size, '1536x1024'); ?>>1536 x 1024 (Landscape)</option>
                                            </select>
                                            <div style="margin-top: 8px; display: flex; gap: 8px;">
                                                <div style="flex: 1;">
                                                    <label style="display: block; font-size: 10px; margin-bottom: 2px; color: #666;"><?php esc_html_e('AI Quality', 'sparkplus'); ?></label>
                                                    <select name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][sub_fields][<?php echo esc_attr($sub_field['key']); ?>][quality]" style="width: 100%;">
                                                        <option value="auto" <?php selected($sub_quality, 'auto'); ?>><?php esc_html_e('Auto', 'sparkplus'); ?></option>
                                                        <option value="low" <?php selected($sub_quality, 'low'); ?>><?php esc_html_e('Low', 'sparkplus'); ?></option>
                                                        <option value="medium" <?php selected($sub_quality, 'medium'); ?>><?php esc_html_e('Medium', 'sparkplus'); ?></option>
                                                        <option value="high" <?php selected($sub_quality, 'high'); ?>><?php esc_html_e('High', 'sparkplus'); ?></option>
                                                    </select>
                                                </div>
                                                <div style="flex: 1;">
                                                    <label style="display: block; font-size: 10px; margin-bottom: 2px; color: #666;"><?php esc_html_e('WebP %', 'sparkplus'); ?></label>
                                                    <input type="number" name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][sub_fields][<?php echo esc_attr($sub_field['key']); ?>][webp_quality]" value="<?php echo esc_attr($sub_webp); ?>" class="small-text" min="1" max="100" step="1" style="width: 100%;" placeholder="80" />
                                                </div>
                                            </div>
                                        <?php else : ?>
                                            <label style="display: block; font-size: 10px; margin-bottom: 2px; color: #666;"><?php esc_html_e('Word Count', 'sparkplus'); ?></label>
                                            <input type="number" name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][sub_fields][<?php echo esc_attr($sub_field['key']); ?>][word_count]" value="<?php echo isset($sub_cfg['word_count']) ? esc_attr($sub_cfg['word_count']) : ''; ?>" class="small-text" min="0" step="1" placeholder="<?php esc_attr_e('Words', 'sparkplus'); ?>" />
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                        <?php
                        // Determine if this is an image field
                        $is_image_field  = in_array($field['type'], array('image', 'file', 'gallery'));
                        $is_post_object  = $field['type'] === 'post_object';
                        $hide_options    = $field['type'] === 'true_false' || ( isset( $field['source'] ) && $field['source'] === 'RM' );
                        $current_size = isset($current_field_configs[$field['key']]['size']) ? $current_field_configs[$field['key']]['size'] : 'auto';
                        $current_quality = isset($current_field_configs[$field['key']]['quality']) ? $current_field_configs[$field['key']]['quality'] : 'auto';
                        $current_webp_quality = isset($current_field_configs[$field['key']]['webp_quality']) ? $current_field_configs[$field['key']]['webp_quality'] : 80;
                        ?>
                        <tr>
                            <td data-label="<?php esc_attr_e('Generate', 'sparkplus'); ?>" style="text-align: center; vertical-align: middle;">
                                <label style="display: inline-block; margin: 0;">
                                    <input 
                                        type="checkbox" 
                                        name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][enabled]"
                                        value="1"
                                        class="sparkplus-field-enable-checkbox sparkplus-generate-checkbox"
                                        <?php 
                                        if (isset($current_field_configs[$field['key']])) {
                                            checked($current_field_configs[$field['key']]['enabled'], true);
                                        }
                                        ?>
                                    />
                                    <span class="screen-reader-text">
                                        <?php
                                        /* translators: %s: field label */
                                        echo esc_html(sprintf(__('Enable generation for %s', 'sparkplus'), $field['label']));
                                        ?>
                                    </span>
                                </label>
                            </td>
                            <td data-label="<?php esc_attr_e('Clear', 'sparkplus'); ?>" style="text-align: center; vertical-align: middle;">
                                <label style="display: inline-block; margin: 0;">
                                    <input 
                                        type="checkbox" 
                                        name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][clear]"
                                        value="1"
                                        class="sparkplus-field-clear-checkbox sparkplus-clear-checkbox"
                                        <?php checked(isset($current_field_configs[$field['key']]['clear']) && $current_field_configs[$field['key']]['clear'], true); ?>
                                    />
                                    <span class="screen-reader-text">
                                        <?php
                                        /* translators: %s: field label */
                                        echo esc_html(sprintf(__('Clear content for %s', 'sparkplus'), $field['label']));
                                        ?>
                                    </span>
                                </label>
                            </td>
                            <td data-label="<?php esc_attr_e('Field Name', 'sparkplus'); ?>">
                                <strong><?php echo esc_html($field['label']); ?></strong>
                                <br>
                                <code style="font-size: 11px; color: #666;"><?php echo esc_html($field['key']); ?></code>
                            </td>
                            <td data-label="<?php esc_attr_e('Type / Source', 'sparkplus'); ?>">
                                <span class="sparkplus-field-source sparkplus-source-<?php echo esc_attr(strtolower($field['source'])); ?>"><?php echo esc_html($field['source']); ?></span>
                                <span class="sparkplus-field-type" style="margin-left: 4px;"><?php echo esc_html($field['type']); ?></span>
                            </td>
                            <td data-label="<?php esc_attr_e('Description / Prompt', 'sparkplus'); ?>">
                                <?php if ($include_acf_instructions && !empty($field['instructions'])) : ?>
                                    <p class="description" style="margin: 0 0 6px; padding: 5px 8px; background: #f0f4ff; border-left: 3px solid #7b9cde; border-radius: 2px; font-size: 12px; color: #444;">
                                        <strong style="color: #3a5ea8;"><?php esc_html_e('ACF Instructions:', 'sparkplus'); ?></strong>
                                        <?php echo esc_html($field['instructions']); ?>
                                    </p>
                                <?php endif; ?>
                                <textarea 
                                    name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][description]"
                                    rows="2"
                                    class="large-text"
                                    placeholder="<?php esc_attr_e('E.g., A brief summary of the content, maximum 150 characters', 'sparkplus'); ?>"
                                ><?php echo isset($current_field_configs[$field['key']]['description']) ? esc_textarea($current_field_configs[$field['key']]['description']) : ''; ?></textarea>
                                <?php if ($is_post_object && !$linking_pool_enabled) : ?>
                                    <p class="sparkplus-post-object-warning" style="display: <?php echo (!empty($current_field_configs[$field['key']]['enabled'])) ? 'block' : 'none'; ?>;">
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php esc_html_e('The linking pool is not enabled. Enable it in the Internal Linking tab for this field to work. Make sure the linking pool contains the posts that are allowed for this post_object field.', 'sparkplus'); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?php esc_attr_e('WordCount/Dimensions', 'sparkplus'); ?>">
                                <?php if ($hide_options) : ?>
                                    <!-- No options for this field type -->
                                <?php elseif ($is_post_object) : ?>
                                    <!-- Post Count Input -->
                                    <label style="display: block; font-size: 10px; margin-bottom: 2px; color: #666;"><?php esc_html_e('Nr. of Posts', 'sparkplus'); ?></label>
                                    <input 
                                        type="number" 
                                        name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][word_count]"
                                        value="<?php echo isset($current_field_configs[$field['key']]['word_count']) ? esc_attr($current_field_configs[$field['key']]['word_count']) : ''; ?>"
                                        class="small-text"
                                        min="1"
                                        step="1"
                                        placeholder="<?php esc_attr_e('Posts', 'sparkplus'); ?>"
                                    />
                                <?php elseif ($is_image_field) : ?>
                                    <!-- Image Size Dropdown -->
                                    <label style="display: block; font-size: 10px; margin-bottom: 2px; color: #666;"><?php esc_html_e('Dimensions', 'sparkplus'); ?></label>
                                    <select 
                                        name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][size]"
                                        style="width: 100%;"
                                    >
                                        <option value="auto" <?php selected($current_size, 'auto'); ?>><?php esc_html_e('Auto (Recommended)', 'sparkplus'); ?></option>
                                        <option value="1024x1024" <?php selected($current_size, '1024x1024'); ?>>1024 x 1024 (Square)</option>
                                        <option value="1024x1536" <?php selected($current_size, '1024x1536'); ?>>1024 x 1536 (Portrait)</option>
                                        <option value="1536x1024" <?php selected($current_size, '1536x1024'); ?>>1536 x 1024 (Landscape)</option>
                                    </select>
                                    
                                    <!-- AI Quality and WebP Quality Row -->
                                    <div style="margin-top: 8px; display: flex; gap: 8px;">
                                        <!-- AI Quality Dropdown -->
                                        <div style="flex: 1;">
                                            <label style="display: block; font-size: 10px; margin-bottom: 2px; color: #666;"><?php esc_html_e('AI Quality', 'sparkplus'); ?></label>
                                            <select 
                                                name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][quality]"
                                                class="sparkplus-quality-select"
                                                data-field-key="<?php echo esc_attr($field['key']); ?>"
                                                style="width: 100%;"
                                            >
                                                <option value="auto" <?php selected($current_quality, 'auto'); ?>><?php esc_html_e('Auto', 'sparkplus'); ?></option>
                                                <option value="low" <?php selected($current_quality, 'low'); ?>><?php esc_html_e('Low', 'sparkplus'); ?></option>
                                                <option value="medium" <?php selected($current_quality, 'medium'); ?>><?php esc_html_e('Medium', 'sparkplus'); ?></option>
                                                <option value="high" <?php selected($current_quality, 'high'); ?>><?php esc_html_e('High', 'sparkplus'); ?></option>
                                            </select>
                                        </div>
                                        
                                        <!-- WebP Quality Input -->
                                        <div style="flex: 1;">
                                            <label style="display: block; font-size: 10px; margin-bottom: 2px; color: #666;"><?php esc_html_e('WebP %', 'sparkplus'); ?></label>
                                            <input 
                                                type="number" 
                                                name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][webp_quality]"
                                                value="<?php echo esc_attr($current_webp_quality); ?>"
                                                class="small-text"
                                                min="1"
                                                max="100"
                                                step="1"
                                                style="width: 100%;"
                                                placeholder="80"
                                            />
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <!-- Word Count Input for Text Fields -->
                                    <label style="display: block; font-size: 10px; margin-bottom: 2px; color: #666;"><?php esc_html_e('Word Count', 'sparkplus'); ?></label>
                                    <input 
                                        type="number" 
                                        name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][fields][<?php echo esc_attr($field['key']); ?>][word_count]"
                                        value="<?php echo isset($current_field_configs[$field['key']]['word_count']) ? esc_attr($current_field_configs[$field['key']]['word_count']) : ''; ?>"
                                        class="small-text"
                                        min="0"
                                        step="1"
                                        placeholder="<?php esc_attr_e('Words', 'sparkplus'); ?>"
                                    />
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="notice notice-info inline" style="margin: 20px 0;">
                <p>
                    <strong><?php esc_html_e('No custom fields found', 'sparkplus'); ?></strong><br>
                    <?php esc_html_e('This post type doesn\'t have any custom fields yet. Custom fields will appear here once they are added via ACF or other methods.', 'sparkplus'); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <h2><?php esc_html_e('Options', 'sparkplus'); ?></h2>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_cpt_include_existing_content_<?php echo esc_attr($selected_post_type); ?>">
                            <?php esc_html_e('Include Existing Content', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input 
                                type="checkbox" 
                                id="sparkplus_cpt_include_existing_content_<?php echo esc_attr($selected_post_type); ?>"
                                name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][include_existing_content]"
                                value="1"
                                <?php checked($include_existing_content, true); ?>
                            />
                            <?php esc_html_e('Add existing post content to the AI prompt when regenerating fields', 'sparkplus'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, existing content from the post will be included in the prompt. This helps the AI avoid duplicate content and links, maintain consistency, and reference information from other fields.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_cpt_include_acf_instructions_<?php echo esc_attr($selected_post_type); ?>">
                            <?php esc_html_e('Include ACF Instruction Fields', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input 
                                type="checkbox" 
                                id="sparkplus_cpt_include_acf_instructions_<?php echo esc_attr($selected_post_type); ?>"
                                name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][include_acf_instructions]"
                                value="1"
                                <?php checked($include_acf_instructions, true); ?>
                            />
                            <?php esc_html_e('Add ACF field instruction text to the AI prompt', 'sparkplus'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, the "Instructions" text you enter on each ACF field will be included in the AI prompt, giving the AI additional context about what to write for that field.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="sparkplus_cpt_additional_context_text_<?php echo esc_attr($selected_post_type); ?>">
                            <?php esc_html_e('Additional Context (Text)', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkplus_cpt_additional_context_text_<?php echo esc_attr($selected_post_type); ?>"
                            name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][additional_context_text]"
                            rows="5"
                            class="large-text"
                            placeholder="<?php esc_attr_e('E.g., This post type is used for case studies. Focus on results and data. Always include a call-to-action at the end.', 'sparkplus'); ?>"
                        ><?php echo esc_textarea($current_additional_context_text); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Specific instructions for text generation on this post type.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sparkplus_cpt_additional_context_image_<?php echo esc_attr($selected_post_type); ?>">
                            <?php esc_html_e('Additional Context (Image)', 'sparkplus'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea 
                            id="sparkplus_cpt_additional_context_image_<?php echo esc_attr($selected_post_type); ?>"
                            name="sparkplus_cpt_configs[<?php echo esc_attr($selected_post_type); ?>][additional_context_image]"
                            rows="5"
                            class="large-text"
                            placeholder="<?php esc_attr_e('E.g., Use minimalist style, bright colors, no text overlays on images.', 'sparkplus'); ?>"
                        ><?php echo esc_textarea($current_additional_context_image); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Specific instructions for image generation on this post type.', 'sparkplus'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="submit">
            <input type="submit" class="button button-primary sparkplus-save-button" value="<?php esc_attr_e('Save Changes', 'sparkplus'); ?> " />
            <span class="sparkplus-save-status"></span>
        </p>
    </form>
</div>

<?php
})(); // End IIFE
