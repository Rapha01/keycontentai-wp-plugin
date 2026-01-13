<?php
/**
 * Generation Page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get the selected post type from settings
$selected_post_type = get_option('keycontentai_selected_post_type', 'post');
$api_key = get_option('keycontentai_openai_api_key', '');

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
                <strong><?php esc_html_e('Configuration Required:', 'keycontentai'); ?></strong>
                <?php esc_html_e('Please configure the plugin settings before generating content.', 'keycontentai'); ?>
            </p>
            <p>
                <?php if (empty($api_key)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=keycontentai-settings&tab=api-settings')); ?>" class="button button-primary">
                        <?php esc_html_e('Set OpenAI API Key', 'keycontentai'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if (empty($selected_post_type)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=keycontentai-settings&tab=cpt')); ?>" class="button button-primary">
                        <?php esc_html_e('Select Post Type', 'keycontentai'); ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
    <?php else : ?>
        <div class="keycontentai-generation-container">
            <div class="keycontentai-generation-header">
                <div class="keycontentai-header-info">
                    <p>
                        <strong><?php esc_html_e('Post Type:', 'keycontentai'); ?></strong> 
                        <?php echo esc_html($selected_post_type); ?>
                        <span class="separator">|</span>
                        <strong><?php esc_html_e('Total Posts:', 'keycontentai'); ?></strong> 
                        <?php echo count($posts); ?>
                    </p>
                </div>
                
                <div class="keycontentai-header-actions">
                    <button type="button" id="keycontentai-queue-all" class="button">
                        <?php esc_html_e('Queue All', 'keycontentai'); ?>
                    </button>
                    <button type="button" id="keycontentai-unqueue-all" class="button">
                        <?php esc_html_e('Unqueue All', 'keycontentai'); ?>
                    </button>
                    <button type="button" id="keycontentai-start-generation" class="button button-primary" disabled>
                        <?php esc_html_e('Start Generation', 'keycontentai'); ?>
                    </button>
                </div>
            </div>
            
            <?php if (empty($posts)) : ?>
                <div class="notice notice-info inline">
                    <p>
                        <?php esc_html_e('No posts found for this post type.', 'keycontentai'); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=keycontentai-load-keywords')); ?>">
                            <?php esc_html_e('Load keywords to create posts.', 'keycontentai'); ?>
                        </a>
                    </p>
                </div>
            <?php else : ?>
                <div class="keycontentai-posts-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php esc_html_e('Status', 'keycontentai'); ?></th>
                                <th style="width: 60px;"><?php esc_html_e('ID', 'keycontentai'); ?></th>
                                <th><?php esc_html_e('Keyword', 'keycontentai'); ?></th>
                                <th><?php esc_html_e('Title', 'keycontentai'); ?></th>
                                <th style="width: 150px;"><?php esc_html_e('Last Generated', 'keycontentai'); ?></th>
                                <th style="width: 120px;"><?php esc_html_e('Actions', 'keycontentai'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="keycontentai-posts-body">
                            <?php foreach ($posts as $post) : 
                                $keyword = get_post_meta($post->ID, 'keycontentai_keyword', true);
                                $last_generation = get_post_meta($post->ID, 'keycontentai_last_generation', true);
                            ?>
                                <tr class="keycontentai-post-row" data-post-id="<?php echo esc_attr($post->ID); ?>" data-status="unqueued">
                                    <td class="status-cell" data-label="<?php esc_attr_e('Status', 'keycontentai'); ?>">
                                        <span class="status-indicator status-unqueued" title="<?php esc_attr_e('Unqueued', 'keycontentai'); ?>">
                                            <span class="dashicons dashicons-minus"></span>
                                        </span>
                                    </td>
                                    <td data-label="<?php esc_attr_e('ID', 'keycontentai'); ?>"><?php echo esc_html($post->ID); ?></td>
                                    <td data-label="<?php esc_attr_e('Keyword', 'keycontentai'); ?>">
                                        <strong><?php echo esc_html($keyword ?: __('(no keyword)', 'keycontentai')); ?></strong>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Title', 'keycontentai'); ?>">
                                        <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" target="_blank">
                                            <?php echo esc_html($post->post_title ?: __('(no title)', 'keycontentai')); ?>
                                        </a>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Last Generated', 'keycontentai'); ?>">
                                        <?php echo $last_generation ? esc_html($last_generation) : '<em>' . esc_html__('Never', 'keycontentai') . '</em>'; ?>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Actions', 'keycontentai'); ?>">
                                        <button type="button" class="button button-small keycontentai-toggle-queue" data-post-id="<?php echo esc_attr($post->ID); ?>">
                                            <?php esc_html_e('Queue', 'keycontentai'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Debug Toggle -->
            <div class="keycontentai-generation-debug-toggle" style="margin-top: 30px; margin-bottom: 10px;">
                <button type="button" id="keycontentai-toggle-debug-btn" class="button button-secondary">
                    <span class="dashicons dashicons-admin-tools" style="margin-top: 4px; margin-right: 5px;"></span>
                    <span class="button-text"><?php esc_html_e('Show Debug Mode', 'keycontentai'); ?></span>
                </button>
                <p class="description" style="margin-top: 8px;">
                    <?php esc_html_e('Show detailed information about the content generation process', 'keycontentai'); ?>
                </p>
            </div>
            
            <!-- Debug Output Box (hidden by default) -->
            <div id="keycontentai-debug-container" class="keycontentai-generation-debug-container" style="display: none;">
                <div class="card">
                    <h2 style="display: flex; justify-content: space-between; align-items: center;">
                        <span>
                            <span class="dashicons dashicons-admin-tools" style="margin-right: 5px;"></span>
                            <?php esc_html_e('Debug Information', 'keycontentai'); ?>
                        </span>
                        <button type="button" id="keycontentai-clear-debug-btn" class="button button-small">
                            <?php esc_html_e('Clear', 'keycontentai'); ?>
                        </button>
                    </h2>
                    
                    <!-- Debug Tabs -->
                    <div class="keycontentai-debug-tabs">
                        <button class="keycontentai-debug-tab active" data-tab="all">
                            <?php esc_html_e('All Debug Data', 'keycontentai'); ?>
                        </button>
                        <button class="keycontentai-debug-tab" data-tab="prompt">
                            <?php esc_html_e('Last Prompt', 'keycontentai'); ?>
                        </button>
                        <button class="keycontentai-debug-tab" data-tab="response">
                            <?php esc_html_e('Last API Response', 'keycontentai'); ?>
                        </button>
                    </div>
                    
                    <!-- Debug Tab Content -->
                    <div class="keycontentai-debug-content">
                        <!-- All Debug Data Tab -->
                        <div id="keycontentai-debug-tab-all" class="keycontentai-debug-tab-content active">
                            <div class="keycontentai-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Debug information will appear here when generation starts.', 'keycontentai'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Last Prompt Tab -->
                        <div id="keycontentai-debug-tab-prompt" class="keycontentai-debug-tab-content">
                            <div class="keycontentai-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Last prompt will appear here after generation.', 'keycontentai'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Last API Response Tab -->
                        <div id="keycontentai-debug-tab-response" class="keycontentai-debug-tab-content">
                            <div class="keycontentai-generation-debug-empty">
                                <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('Last API response will appear here after generation.', 'keycontentai'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
