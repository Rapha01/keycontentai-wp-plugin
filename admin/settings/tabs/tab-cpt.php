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
$selected_post_type = get_option('keycontentai_selected_post_type', 'post');
$field_descriptions = get_option('keycontentai_field_descriptions', array());
$field_word_counts = get_option('keycontentai_field_word_counts', array());
$field_enabled = get_option('keycontentai_field_enabled', array());

// Get custom fields for the selected post type
$custom_fields = array();
if (!empty($selected_post_type)) {
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
    
    // Get standard custom fields (post meta)
    global $wpdb;
    $meta_keys = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT meta_key 
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE p.post_type = %s 
        AND meta_key NOT LIKE '\\_%%'
        ORDER BY meta_key
    ", $selected_post_type));
    
    if ($meta_keys) {
        foreach ($meta_keys as $meta_key) {
            // Check if this key is already in custom_fields from ACF
            $exists = false;
            foreach ($custom_fields as $cf) {
                if ($cf['key'] === $meta_key) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $custom_fields[] = array(
                    'key' => $meta_key,
                    'label' => ucwords(str_replace(array('_', '-'), ' ', $meta_key)),
                    'type' => 'custom',
                    'source' => 'Meta'
                );
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
                            name="keycontentai_selected_post_type" 
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
                        <th style="width: 12%;"><?php esc_html_e('Word Count', 'keycontentai'); ?></th>
                        <th style="width: 12%; text-align: center;"><?php esc_html_e('Generate', 'keycontentai'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_fields as $field) : ?>
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
                                    name="keycontentai_field_descriptions[<?php echo esc_attr($field['key']); ?>]"
                                    rows="3"
                                    class="large-text"
                                    placeholder="<?php esc_attr_e('E.g., A brief summary of the content, maximum 150 characters', 'keycontentai'); ?>"
                                ><?php echo isset($field_descriptions[$field['key']]) ? esc_textarea($field_descriptions[$field['key']]) : ''; ?></textarea>
                            </td>
                            <td>
                                <input 
                                    type="number" 
                                    name="keycontentai_field_word_counts[<?php echo esc_attr($field['key']); ?>]"
                                    value="<?php echo isset($field_word_counts[$field['key']]) ? esc_attr($field_word_counts[$field['key']]) : ''; ?>"
                                    class="small-text"
                                    min="1"
                                    step="1"
                                    placeholder="<?php esc_attr_e('Words', 'keycontentai'); ?>"
                                />
                                <p class="description">
                                    <?php esc_html_e('Target word count for AI generation', 'keycontentai'); ?>
                                </p>
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                <label style="display: inline-block; margin: 0;">
                                    <input 
                                        type="checkbox" 
                                        name="keycontentai_field_enabled[<?php echo esc_attr($field['key']); ?>]"
                                        value="1"
                                        <?php checked(isset($field_enabled[$field['key']]) ? $field_enabled[$field['key']] : true, true); ?>
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
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr style="display: none;">
                    <td></td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
