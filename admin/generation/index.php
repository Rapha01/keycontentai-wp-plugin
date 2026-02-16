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
$selected_post_type = get_option('sparkwp_selected_post_type', 'post');
$api_key = get_option('sparkwp_openai_api_key', '');

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
                <strong><?php esc_html_e('Configuration Required:', 'sparkwp'); ?></strong>
                <?php esc_html_e('Please configure the plugin settings before generating content.', 'sparkwp'); ?>
            </p>
            <p>
                <?php if (empty($api_key)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sparkwp-settings&tab=api-settings')); ?>" class="button button-primary">
                        <?php esc_html_e('Set OpenAI API Key', 'sparkwp'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if (empty($selected_post_type)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sparkwp-settings&tab=cpt')); ?>" class="button button-primary">
                        <?php esc_html_e('Select Post Type', 'sparkwp'); ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
    <?php else : ?>
        <div class="sparkwp-generation-container">
            <div class="sparkwp-generation-header">
                <div class="sparkwp-header-info">
                    <p>
                        <strong><?php esc_html_e('Post Type:', 'sparkwp'); ?></strong> 
                        <?php echo esc_html($selected_post_type); ?>
                        <span class="sparkwp-separator">|</span>
                        <strong><?php esc_html_e('Total Posts:', 'sparkwp'); ?></strong> 
                        <?php echo count($posts); ?>
                    </p>
                </div>
                
                <div class="sparkwp-header-actions">
                    <button type="button" id="sparkwp-queue-all" class="button">
                        <?php esc_html_e('Queue All', 'sparkwp'); ?>
                    </button>
                    <button type="button" id="sparkwp-unqueue-all" class="button">
                        <?php esc_html_e('Unqueue All', 'sparkwp'); ?>
                    </button>
                    <button type="button" id="sparkwp-start-generation" class="button button-primary" disabled>
                        <?php esc_html_e('Start Generation', 'sparkwp'); ?>
                    </button>
                </div>
            </div>
            
            <?php if (empty($posts)) : ?>
                <div class="notice notice-info inline">
                    <p>
                        <?php esc_html_e('No posts found for this post type.', 'sparkwp'); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=sparkwp-load-keywords')); ?>">
                            <?php esc_html_e('Load keywords to create posts.', 'sparkwp'); ?>
                        </a>
                    </p>
                </div>
            <?php else : ?>
                <div class="sparkwp-posts-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php esc_html_e('Status', 'sparkwp'); ?></th>
                                <th style="width: 60px;"><?php esc_html_e('ID', 'sparkwp'); ?></th>
                                <th><?php esc_html_e('Keyword', 'sparkwp'); ?></th>
                                <th><?php esc_html_e('Title', 'sparkwp'); ?></th>
                                <th style="width: 150px;"><?php esc_html_e('Last Generated', 'sparkwp'); ?></th>
                                <th style="width: 120px;"><?php esc_html_e('Actions', 'sparkwp'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="sparkwp-posts-body">
                            <?php foreach ($posts as $post) : 
                                $keyword = get_post_meta($post->ID, 'sparkwp_keyword', true);
                                $additional_context = get_post_meta($post->ID, 'sparkwp_additional_context', true);
                                $last_generation = get_post_meta($post->ID, 'sparkwp_last_generation', true);
                            ?>
                                <tr class="sparkwp-post-row" data-post-id="<?php echo esc_attr($post->ID); ?>" data-status="unqueued">
                                    <td class="sparkwp-status-cell">
                                        <span class="sparkwp-status-indicator sparkwp-status-unqueued" title="<?php esc_attr_e('Unqueued', 'sparkwp'); ?>">
                                            <span class="dashicons dashicons-minus"></span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="sparkwp-mobile-label"><?php esc_html_e('ID:', 'sparkwp'); ?></span>
                                        <?php echo esc_html($post->ID); ?>
                                    </td>
                                    <td>
                                        <span class="sparkwp-mobile-label"><?php esc_html_e('Keyword:', 'sparkwp'); ?></span>
                                        <strong><?php echo esc_html($keyword ?: __('(no keyword)', 'sparkwp')); ?></strong>
                                    </td>
                                    <td>
                                        <span class="sparkwp-mobile-label"><?php esc_html_e('Title:', 'sparkwp'); ?></span>
                                        <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" target="_blank">
                                            <?php echo esc_html($post->post_title ?: __('(no title)', 'sparkwp')); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="sparkwp-mobile-label"><?php esc_html_e('Last Generated:', 'sparkwp'); ?></span>
                                        <?php echo $last_generation ? esc_html($last_generation) : '<em>' . esc_html__('Never', 'sparkwp') . '</em>'; ?>
                                    </td>
                                    <td class="sparkwp-actions-cell">
                                        <span class="sparkwp-mobile-label"><?php esc_html_e('Actions:', 'sparkwp'); ?></span>
                                        <button type="button" class="button button-small sparkwp-toggle-queue" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                            <?php esc_html_e('Queue', 'sparkwp'); ?>
                                        </button>
                                        <button type="button" class="button button-small sparkwp-toggle-details" title="<?php esc_attr_e('Toggle details', 'sparkwp'); ?>">
                                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="sparkwp-post-details-row" style="display: none;">
                                    <td colspan="6">
                                        <div class="sparkwp-post-details">
                                            <div class="sparkwp-detail-field">
                                                <label for="sparkwp-keyword-<?php echo esc_attr($post->ID); ?>">
                                                    <strong><?php esc_html_e('Keyword:', 'sparkwp'); ?></strong>
                                                </label>
                                                <input 
                                                    type="text" 
                                                    id="sparkwp-keyword-<?php echo esc_attr($post->ID); ?>" 
                                                    class="regular-text sparkwp-keyword-input" 
                                                    value="<?php echo esc_attr($keyword); ?>"
                                                    placeholder="<?php esc_attr_e('Enter keyword...', 'sparkwp'); ?>"
                                                />
                                            </div>
                                            <div class="sparkwp-detail-field">
                                                <label for="sparkwp-context-<?php echo esc_attr($post->ID); ?>">
                                                    <strong><?php esc_html_e('Additional Context:', 'sparkwp'); ?></strong>
                                                </label>
                                                <textarea 
                                                    id="sparkwp-context-<?php echo esc_attr($post->ID); ?>" 
                                                    class="large-text sparkwp-context-input" 
                                                    rows="3"
                                                    placeholder="<?php esc_attr_e('Enter additional context for this post...', 'sparkwp'); ?>"
                                                ><?php echo esc_textarea($additional_context); ?></textarea>
                                            </div>
                                            <div class="sparkwp-detail-actions">
                                                <button type="button" class="button button-primary sparkwp-save-meta" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                                    <?php esc_html_e('Save', 'sparkwp'); ?>
                                                </button>
                                                <span class="sparkwp-save-status"></span>
                                                <button type="button" class="button sparkwp-delete-post" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                    <?php esc_html_e('Delete', 'sparkwp'); ?>
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
            <div class="sparkwp-generation-debug-toggle" style="margin-top: 30px; margin-bottom: 10px;">
                <button type="button" id="sparkwp-toggle-debug-btn" class="button button-secondary">
                    <span class="dashicons dashicons-admin-tools" style="margin-top: 4px; margin-right: 5px;"></span>
                    <span class="button-text"><?php esc_html_e('Show Debug Mode', 'sparkwp'); ?></span>
                </button>
                <p class="description" style="margin-top: 8px;">
                    <?php esc_html_e('Show detailed information about the content generation process', 'sparkwp'); ?>
                </p>
            </div>
            
            <!-- Debug Output Box (hidden by default) -->
            <div id="sparkwp-debug-container" class="sparkwp-generation-debug-container" style="display: none;">
                <div class="card">
                    <h2 style="display: flex; justify-content: space-between; align-items: center;">
                        <span>
                            <span class="dashicons dashicons-admin-tools" style="margin-right: 5px;"></span>
                            <?php esc_html_e('Debug Information', 'sparkwp'); ?>
                        </span>
                        <button type="button" id="sparkwp-clear-debug-btn" class="button button-small">
                            <?php esc_html_e('Clear', 'sparkwp'); ?>
                        </button>
                    </h2>
                    
                    <!-- Debug Tabs -->
                    <div class="sparkwp-debug-tabs">
                        <button class="sparkwp-debug-tab active" data-tab="all">
                            <?php esc_html_e('All Debug Data', 'sparkwp'); ?>
                        </button>
                        <button class="sparkwp-debug-tab" data-tab="text-prompt">
                            <?php esc_html_e('Last Text Prompt', 'sparkwp'); ?>
                        </button>
                        <button class="sparkwp-debug-tab" data-tab="text-response">
                            <?php esc_html_e('Last Text API Response', 'sparkwp'); ?>
                        </button>
                        <button class="sparkwp-debug-tab" data-tab="image-prompt">
                            <?php esc_html_e('Last Image Prompt', 'sparkwp'); ?>
                        </button>
                        <button class="sparkwp-debug-tab" data-tab="image-response">
                            <?php esc_html_e('Last Image API Response', 'sparkwp'); ?>
                        </button>
                    </div>
                    
                    <!-- Debug Tab Content -->
                    <div class="sparkwp-debug-content">
                        <!-- All Debug Data Tab -->
                        <div id="sparkwp-debug-tab-all" class="sparkwp-debug-tab-content active">
                            <div class="sparkwp-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Debug information will appear here when generation starts.', 'sparkwp'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Last Text Prompt Tab -->
                        <div id="sparkwp-debug-tab-text-prompt" class="sparkwp-debug-tab-content">
                            <div class="sparkwp-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Last text prompt will appear here after generation.', 'sparkwp'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Last Text API Response Tab -->
                        <div id="sparkwp-debug-tab-text-response" class="sparkwp-debug-tab-content">
                            <div class="sparkwp-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Last text API response will appear here after generation.', 'sparkwp'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Last Image Prompt Tab -->
                        <div id="sparkwp-debug-tab-image-prompt" class="sparkwp-debug-tab-content">
                            <div class="sparkwp-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Last image prompt will appear here after generation.', 'sparkwp'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Last Image API Response Tab -->
                        <div id="sparkwp-debug-tab-image-response" class="sparkwp-debug-tab-content">
                            <div class="sparkwp-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Last image API response will appear here after generation.', 'sparkwp'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="sparkwp-delete-modal" class="sparkwp-delete-modal">
    <div class="sparkwp-delete-modal-overlay"></div>
    <div class="sparkwp-delete-modal-dialog">
        <div class="sparkwp-delete-modal-header">
            <h2><?php esc_html_e('Delete Post', 'sparkwp'); ?></h2>
        </div>
        <div class="sparkwp-delete-modal-body">
            <p><?php esc_html_e('Are you sure you want to permanently delete the following post?', 'sparkwp'); ?></p>
            <p>&ldquo;<span class="sparkwp-delete-modal-post-title"></span>&rdquo;</p>
            <p><?php esc_html_e('This action cannot be undone.', 'sparkwp'); ?></p>
        </div>
        <div class="sparkwp-delete-modal-footer">
            <button type="button" class="button sparkwp-delete-modal-cancel"><?php esc_html_e('Cancel', 'sparkwp'); ?></button>
            <button type="button" class="button sparkwp-delete-modal-confirm"><?php esc_html_e('Delete', 'sparkwp'); ?></button>
        </div>
    </div>
</div>
<?php
})(); // End IIFE