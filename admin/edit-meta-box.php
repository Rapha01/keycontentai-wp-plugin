<?php
/**
 * Post Edit Meta Box
 * 
 * Handles the SparkPlus meta box on post edit screens
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta boxes for SparkPlus fields
 */
function sparkplus_add_meta_boxes() {
    global $sparkplus;
    
    // Get all configured post types
    $cpt_configs = $sparkplus->get_cpt_configs();
    $post_types_to_add = array();
    
    if (!empty($cpt_configs)) {
        $post_types_to_add = array_keys($cpt_configs);
    }
    
    // Always include currently selected post type
    $selected_post_type = get_option('sparkplus_selected_post_type', 'post');
    if (!in_array($selected_post_type, $post_types_to_add)) {
        $post_types_to_add[] = $selected_post_type;
    }
    
    // Add meta box for each post type
    foreach ($post_types_to_add as $post_type) {
        add_meta_box(
            'sparkplus_meta_box',
            __('SparkPlus - Content Generation', 'sparkplus'),
            'sparkplus_render_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}

/**
 * Render the meta box content
 */
function sparkplus_render_meta_box($post) {
    // Add nonce for security
    wp_nonce_field('sparkplus_meta_box', 'sparkplus_meta_box_nonce');
    
    // Get current values
    $keyword = get_post_meta($post->ID, 'sparkplus_keyword', true);
    $additional_context = get_post_meta($post->ID, 'sparkplus_additional_context', true);
    $last_generation = get_post_meta($post->ID, 'sparkplus_last_generation', true);
    
    ?>
    <div class="sparkplus-meta-box">
        <p>
            <label for="sparkplus_keyword"><strong><?php esc_html_e('Keyword:', 'sparkplus'); ?></strong></label>
            <input 
                type="text" 
                id="sparkplus_keyword" 
                name="sparkplus_keyword" 
                value="<?php echo esc_attr($keyword); ?>" 
                class="widefat"
                placeholder="<?php esc_attr_e('e.g., best coffee machine', 'sparkplus'); ?>"
            />
            <span class="description"><?php esc_html_e('The keyword used for AI content generation', 'sparkplus'); ?></span>
        </p>
        
        <p>
            <label for="sparkplus_additional_context"><strong><?php esc_html_e('Additional Context:', 'sparkplus'); ?></strong></label>
            <textarea 
                id="sparkplus_additional_context" 
                name="sparkplus_additional_context" 
                rows="4" 
                class="widefat"
                placeholder="<?php esc_attr_e('Post-specific context or instructions...', 'sparkplus'); ?>"
            ><?php echo esc_textarea($additional_context); ?></textarea>
            <span class="description"><?php esc_html_e('Specific context for this post only', 'sparkplus'); ?></span>
        </p>
        
        <?php if (!empty($last_generation)) : ?>
            <p>
                <strong><?php esc_html_e('Last Generated:', 'sparkplus'); ?></strong><br>
                <span class="description"><?php echo esc_html($last_generation); ?></span>
            </p>
        <?php else : ?>
            <p>
                <span class="description" style="font-style: italic;"><?php esc_html_e('Not yet generated', 'sparkplus'); ?></span>
            </p>
        <?php endif; ?>
    </div>
    <style>
        .sparkplus-meta-box p {
            margin-bottom: 15px;
        }
        .sparkplus-meta-box label {
            display: block;
            margin-bottom: 5px;
        }
        .sparkplus-meta-box .description {
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
function sparkplus_save_meta_box_data($post_id) {
    // Check nonce
    if (!isset($_POST['sparkplus_meta_box_nonce']) || 
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sparkplus_meta_box_nonce'])), 'sparkplus_meta_box')) {
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
    if (isset($_POST['sparkplus_keyword'])) {
        update_post_meta($post_id, 'sparkplus_keyword', sanitize_text_field(wp_unslash($_POST['sparkplus_keyword'])));
    }
    
    // Save additional context
    if (isset($_POST['sparkplus_additional_context'])) {
        update_post_meta($post_id, 'sparkplus_additional_context', sanitize_textarea_field(wp_unslash($_POST['sparkplus_additional_context'])));
    }
    
    // Note: last_generation is not editable by user, only set by the plugin during generation
}

// Register hooks
add_action('add_meta_boxes', 'sparkplus_add_meta_boxes');
add_action('save_post', 'sparkplus_save_meta_box_data');
