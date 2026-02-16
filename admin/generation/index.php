<?php
/**
 * Generation Page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// IIFE pattern to avoid globally-scoped variables (WordPress coding standards requirement).
(function() {

// Get the selected post type from settings
$selected_post_type = get_option('sparkplus_selected_post_type', 'post');
$api_key = get_option('sparkplus_openai_api_key', '');

// Check if settings are configured
$is_configured = !empty($api_key) && !empty($selected_post_type);

// Get all posts of the selected post type
$posts = array();
if ($is_configured) {
    $posts = get_posts(array(
        'post_type' => $selected_post_type,
        'posts_per_page' => -1,
        'post_status' => 'any',
        'orderby' => 'date',
        'order' => 'DESC'
    ));
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (!$is_configured) : ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('Configuration Required:', 'sparkplus'); ?></strong>
                <?php esc_html_e('Please configure the plugin settings before generating content.', 'sparkplus'); ?>
            </p>
            <p>
                <?php if (empty($api_key)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sparkplus-settings&tab=api-settings')); ?>" class="button button-primary">
                        <?php esc_html_e('Set OpenAI API Key', 'sparkplus'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if (empty($selected_post_type)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sparkplus-settings&tab=cpt')); ?>" class="button button-primary">
                        <?php esc_html_e('Select Post Type', 'sparkplus'); ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
    <?php else : ?>
        <div class="sparkplus-generation-container">
            <div class="sparkplus-generation-header">
                <div class="sparkplus-header-info">
                    <p>
                        <strong><?php esc_html_e('Post Type:', 'sparkplus'); ?></strong> 
                        <?php echo esc_html($selected_post_type); ?>
                        <span class="sparkplus-separator">|</span>
                        <strong><?php esc_html_e('Total Posts:', 'sparkplus'); ?></strong> 
                        <?php echo count($posts); ?>
                    </p>
                </div>
                
                <div class="sparkplus-header-actions">
                    <button type="button" id="sparkplus-queue-all" class="button">
                        <?php esc_html_e('Queue All', 'sparkplus'); ?>
                    </button>
                    <button type="button" id="sparkplus-unqueue-all" class="button">
                        <?php esc_html_e('Unqueue All', 'sparkplus'); ?>
                    </button>
                    <button type="button" id="sparkplus-start-generation" class="button button-primary" disabled>
                        <?php esc_html_e('Start Generation', 'sparkplus'); ?>
                    </button>
                </div>
            </div>
            
            <?php if (empty($posts)) : ?>
                <div class="notice notice-info inline">
                    <p>
                        <?php esc_html_e('No posts found for this post type.', 'sparkplus'); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=sparkplus-load-keywords')); ?>">
                            <?php esc_html_e('Load keywords to create posts.', 'sparkplus'); ?>
                        </a>
                    </p>
                </div>
            <?php else : ?>
                <div class="sparkplus-posts-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php esc_html_e('Status', 'sparkplus'); ?></th>
                                <th style="width: 60px;"><?php esc_html_e('ID', 'sparkplus'); ?></th>
                                <th><?php esc_html_e('Keyword', 'sparkplus'); ?></th>
                                <th><?php esc_html_e('Title', 'sparkplus'); ?></th>
                                <th style="width: 150px;"><?php esc_html_e('Last Generated', 'sparkplus'); ?></th>
                                <th style="width: 120px;"><?php esc_html_e('Actions', 'sparkplus'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="sparkplus-posts-body">
                            <?php foreach ($posts as $post) : 
                                $keyword = get_post_meta($post->ID, 'sparkplus_keyword', true);
                                $additional_context = get_post_meta($post->ID, 'sparkplus_additional_context', true);
                                $last_generation = get_post_meta($post->ID, 'sparkplus_last_generation', true);
                            ?>
                                <tr class="sparkplus-post-row" data-post-id="<?php echo esc_attr($post->ID); ?>" data-status="unqueued">
                                    <td class="sparkplus-status-cell">
                                        <span class="sparkplus-status-indicator sparkplus-status-unqueued" title="<?php esc_attr_e('Unqueued', 'sparkplus'); ?>">
                                            <span class="dashicons dashicons-minus"></span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="sparkplus-mobile-label"><?php esc_html_e('ID:', 'sparkplus'); ?></span>
                                        <?php echo esc_html($post->ID); ?>
                                    </td>
                                    <td>
                                        <span class="sparkplus-mobile-label"><?php esc_html_e('Keyword:', 'sparkplus'); ?></span>
                                        <strong><?php echo esc_html($keyword ?: __('(no keyword)', 'sparkplus')); ?></strong>
                                    </td>
                                    <td>
                                        <span class="sparkplus-mobile-label"><?php esc_html_e('Title:', 'sparkplus'); ?></span>
                                        <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" target="_blank">
                                            <?php echo esc_html($post->post_title ?: __('(no title)', 'sparkplus')); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="sparkplus-mobile-label"><?php esc_html_e('Last Generated:', 'sparkplus'); ?></span>
                                        <?php echo $last_generation ? esc_html($last_generation) : '<em>' . esc_html__('Never', 'sparkplus') . '</em>'; ?>
                                    </td>
                                    <td class="sparkplus-actions-cell">
                                        <span class="sparkplus-mobile-label"><?php esc_html_e('Actions:', 'sparkplus'); ?></span>
                                        <button type="button" class="button button-small sparkplus-toggle-queue" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                            <?php esc_html_e('Queue', 'sparkplus'); ?>
                                        </button>
                                        <button type="button" class="button button-small sparkplus-toggle-details" title="<?php esc_attr_e('Toggle details', 'sparkplus'); ?>">
                                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="sparkplus-post-details-row" style="display: none;">
                                    <td colspan="6">
                                        <div class="sparkplus-post-details">
                                            <div class="sparkplus-detail-field">
                                                <label for="sparkplus-keyword-<?php echo esc_attr($post->ID); ?>">
                                                    <strong><?php esc_html_e('Keyword:', 'sparkplus'); ?></strong>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    id="sparkplus-keyword-<?php echo esc_attr($post->ID); ?>" 
                                                    class="regular-text sparkplus-keyword-input" 
                                                    value="<?php echo esc_attr($keyword); ?>"
                                                    placeholder="<?php esc_attr_e('Enter keyword...', 'sparkplus'); ?>"
                                                />
                                            </div>
                                            <div class="sparkplus-detail-field">
                                                <label for="sparkplus-context-<?php echo esc_attr($post->ID); ?>">
                                                    <strong><?php esc_html_e('Additional Context:', 'sparkplus'); ?></strong>
                                                </label>
                                                <textarea 
                                                    id="sparkplus-context-<?php echo esc_attr($post->ID); ?>" 
                                                    class="large-text sparkplus-context-input" 
                                                    rows="3"
                                                    placeholder="<?php esc_attr_e('Enter additional context for this post...', 'sparkplus'); ?>"
                                                ><?php echo esc_textarea($additional_context); ?></textarea>
                                            </div>
                                            <div class="sparkplus-detail-actions">
                                                <button type="button" class="button button-primary sparkplus-save-meta" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                                    <?php esc_html_e('Save', 'sparkplus'); ?>
                                                </button>
                                                <span class="sparkplus-save-status"></span>
                                                <button type="button" class="button sparkplus-delete-post" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                    <?php esc_html_e('Delete', 'sparkplus'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Debug Toggle -->
            <div class="sparkplus-generation-debug-toggle" style="margin-top: 30px; margin-bottom: 10px;">
                <button type="button" id="sparkplus-toggle-debug-btn" class="button button-secondary">
                    <span class="dashicons dashicons-admin-tools" style="margin-top: 4px; margin-right: 5px;"></span>
                    <span class="button-text"><?php esc_html_e('Show Debug Mode', 'sparkplus'); ?></span>
                </button>
                <p class="description" style="margin-top: 8px;">
                    <?php esc_html_e('Show detailed information about the content generation process', 'sparkplus'); ?>
                </p>
            </div>
            
            <!-- Debug Output Box (hidden by default) -->
            <div id="sparkplus-debug-container" class="sparkplus-generation-debug-container" style="display: none;">
                <div class="card">
                    <h2 style="display: flex; justify-content: space-between; align-items: center;">
                        <span>
                            <span class="dashicons dashicons-admin-tools" style="margin-right: 5px;"></span>
                            <?php esc_html_e('Debug Information', 'sparkplus'); ?>
                        </span>
                        <button type="button" id="sparkplus-clear-debug-btn" class="button button-small">
                            <?php esc_html_e('Clear', 'sparkplus'); ?>
                        </button>
                    </h2>
                    
                    <!-- Debug Tabs -->
                    <div class="sparkplus-debug-tabs">
                        <button class="sparkplus-debug-tab active" data-tab="all">
                            <?php esc_html_e('All Debug Data', 'sparkplus'); ?>
                        </button>
                        <button class="sparkplus-debug-tab" data-tab="text-prompt">
                            <?php esc_html_e('Last Text Prompt', 'sparkplus'); ?>
                        </button>
                        <button class="sparkplus-debug-tab" data-tab="text-response">
                            <?php esc_html_e('Last Text API Response', 'sparkplus'); ?>
                        </button>
                        <button class="sparkplus-debug-tab" data-tab="image-prompt">
                            <?php esc_html_e('Last Image Prompt', 'sparkplus'); ?>
                        </button>
                        <button class="sparkplus-debug-tab" data-tab="image-response">
                            <?php esc_html_e('Last Image API Response', 'sparkplus'); ?>
                        </button>
                    </div>
                    
                    <!-- Debug Tab Content -->
                    <div class="sparkplus-debug-content">
                        <!-- All Debug Data Tab -->
                        <div id="sparkplus-debug-tab-all" class="sparkplus-debug-tab-content active">
                            <div class="sparkplus-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Debug information will appear here when generation starts.', 'sparkplus'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Last Text Prompt Tab -->
                        <div id="sparkplus-debug-tab-text-prompt" class="sparkplus-debug-tab-content">
                            <div class="sparkplus-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Last text prompt will appear here after generation.', 'sparkplus'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Last Text API Response Tab -->
                        <div id="sparkplus-debug-tab-text-response" class="sparkplus-debug-tab-content">
                            <div class="sparkplus-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Last text API response will appear here after generation.', 'sparkplus'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Last Image Prompt Tab -->
                        <div id="sparkplus-debug-tab-image-prompt" class="sparkplus-debug-tab-content">
                            <div class="sparkplus-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Last image prompt will appear here after generation.', 'sparkplus'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Last Image API Response Tab -->
                        <div id="sparkplus-debug-tab-image-response" class="sparkplus-debug-tab-content">
                            <div class="sparkplus-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Last image API response will appear here after generation.', 'sparkplus'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="sparkplus-delete-modal" class="sparkplus-delete-modal">
    <div class="sparkplus-delete-modal-overlay"></div>
    <div class="sparkplus-delete-modal-dialog">
        <div class="sparkplus-delete-modal-header">
            <h2><?php esc_html_e('Delete Post', 'sparkplus'); ?></h2>
        </div>
        <div class="sparkplus-delete-modal-body">
            <p><?php esc_html_e('Are you sure you want to permanently delete the following post?', 'sparkplus'); ?></p>
            <p>&ldquo;<span class="sparkplus-delete-modal-post-title"></span>&rdquo;</p>
            <p><?php esc_html_e('This action cannot be undone.', 'sparkplus'); ?></p>
        </div>
        <div class="sparkplus-delete-modal-footer">
            <button type="button" class="button sparkplus-delete-modal-cancel"><?php esc_html_e('Cancel', 'sparkplus'); ?></button>
            <button type="button" class="button sparkplus-delete-modal-confirm"><?php esc_html_e('Delete', 'sparkplus'); ?></button>
        </div>
    </div>
</div>
<?php
})(); // End IIFE