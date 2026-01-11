<?php
/**
 * Post Edit Meta Box
 * 
 * Handles the KeyContentAI meta box on post edit screens
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta boxes for KeyContentAI fields
 */
function keycontentai_add_meta_boxes() {
    global $keycontentai;
    
    // Get all configured post types
    $cpt_configs = $keycontentai->get_cpt_configs();
    $post_types_to_add = array();
    
    if (!empty($cpt_configs)) {
        $post_types_to_add = array_keys($cpt_configs);
    }
    
    // Always include currently selected post type
    $selected_post_type = get_option('keycontentai_selected_post_type', 'post');
    if (!in_array($selected_post_type, $post_types_to_add)) {
        $post_types_to_add[] = $selected_post_type;
    }
    
    // Add meta box for each post type
    foreach ($post_types_to_add as $post_type) {
        add_meta_box(
            'keycontentai_meta_box',
            __('KeyContentAI - Content Generation', 'keycontentai'),
            'keycontentai_render_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}

/**
 * Render the meta box content
 */
function keycontentai_render_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('keycontentai_meta_box', 'keycontentai_meta_box_nonce');
    
    // Get current values
    $keyword = get_post_meta($post->ID, 'keycontentai_keyword', true);
    $additional_context = get_post_meta($post->ID, 'keycontentai_additional_context', true);
    $last_generation = get_post_meta($post->ID, 'keycontentai_last_generation', true);
    
    ?>
    <div class="keycontentai-meta-box">
        <p>
            <label for="keycontentai_keyword"><strong><?php esc_html_e('Keyword:', 'keycontentai'); ?></strong></label>
            <input 
                type="text" 
                id="keycontentai_keyword" 
                name="keycontentai_keyword" 
                value="<?php echo esc_attr($keyword); ?>" 
                class="widefat"
                placeholder="<?php esc_attr_e('e.g., best coffee machine', 'keycontentai'); ?>"
            />
            <span class="description"><?php esc_html_e('The keyword used for AI content generation', 'keycontentai'); ?></span>
        </p>
        
        <p>
            <label for="keycontentai_additional_context"><strong><?php esc_html_e('Additional Context:', 'keycontentai'); ?></strong></label>
            <textarea 
                id="keycontentai_additional_context" 
                name="keycontentai_additional_context" 
                rows="4" 
                class="widefat"
                placeholder="<?php esc_attr_e('Post-specific context or instructions...', 'keycontentai'); ?>"
            ><?php echo esc_textarea($additional_context); ?></textarea>
            <span class="description"><?php esc_html_e('Specific context for this post only', 'keycontentai'); ?></span>
        </p>
        
        <?php if (!empty($last_generation)) : ?>
            <p>
                <strong><?php esc_html_e('Last Generated:', 'keycontentai'); ?></strong><br>
                <span class="description"><?php echo esc_html($last_generation); ?></span>
            </p>
        <?php else : ?>
            <p>
                <span class="description" style="font-style: italic;"><?php esc_html_e('Not yet generated', 'keycontentai'); ?></span>
            </p>
        <?php endif; ?>
    </div>
    <style>
        .keycontentai-meta-box p {
            margin-bottom: 15px;
        }
        .keycontentai-meta-box label {
            display: block;
            margin-bottom: 5px;
        }
        .keycontentai-meta-box .description {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
    </style>
    <?php
}

/**
 * Save meta box data
 */
function keycontentai_save_meta_box_data($post_id) {
    // Check nonce
    if (!isset($_POST['keycontentai_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['keycontentai_meta_box_nonce'], 'keycontentai_meta_box')) {
        return;
    }
    
    // Check if autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save keyword
    if (isset($_POST['keycontentai_keyword'])) {
        update_post_meta($post_id, 'keycontentai_keyword', sanitize_text_field($_POST['keycontentai_keyword']));
    }
    
    // Save additional context
    if (isset($_POST['keycontentai_additional_context'])) {
        update_post_meta($post_id, 'keycontentai_additional_context', sanitize_textarea_field($_POST['keycontentai_additional_context']));
    }
    
    // Note: last_generation is not editable by user, only set by the plugin during generation
}

// Register hooks
add_action('add_meta_boxes', 'keycontentai_add_meta_boxes');
add_action('save_post', 'keycontentai_save_meta_box_data');
