<?php
/**
 * Create Page
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
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (!$is_configured) : ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('Configuration Required:', 'keycontentai'); ?></strong>
                <?php esc_html_e('Please configure the plugin settings before creating content.', 'keycontentai'); ?>
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
        <div class="notice notice-info" style="margin-bottom: 20px;">
            <p>
                <strong><?php esc_html_e('Ready to Create!', 'keycontentai'); ?></strong>
                <?php 
                $post_type_object = get_post_type_object($selected_post_type);
                printf(
                    esc_html__('Content will be created as: %s', 'keycontentai'),
                    '<strong>' . esc_html($post_type_object->labels->singular_name) . '</strong>'
                );
                ?>
            </p>
        </div>
        
        <div class="keycontentai-create-layout">
            <!-- Left Column: Keywords Input -->
            <div class="keycontentai-create-left">
                <div class="card">
                    <h2><?php esc_html_e('Keywords', 'keycontentai'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Enter one keyword per line. Each keyword will generate a separate post.', 'keycontentai'); ?>
                    </p>
                    
                    <form id="keycontentai-create-form" method="post">
                        <textarea 
                            id="keycontentai-keywords" 
                            name="keywords" 
                            rows="20" 
                            class="keycontentai-keywords-textarea"
                            placeholder="<?php esc_attr_e('WordPress Plugins&#10;SEO Best Practices&#10;Content Marketing Tips&#10;...', 'keycontentai'); ?>"
                        ></textarea>
                        
                        <div class="keycontentai-create-actions">
                            <button type="submit" id="keycontentai-start-btn" class="button button-primary button-hero">
                                <span class="dashicons dashicons-admin-post" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Generate Content', 'keycontentai'); ?>
                            </button>
                            
                            <button type="button" id="keycontentai-stop-btn" class="button button-secondary button-hero" style="display: none;">
                                <span class="dashicons dashicons-no" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Stop', 'keycontentai'); ?>
                            </button>
                            
                            <button type="button" id="keycontentai-clear-btn" class="button button-link">
                                <?php esc_html_e('Clear All', 'keycontentai'); ?>
                            </button>
                        </div>
                        
                        <div class="keycontentai-stats">
                            <span id="keycontentai-keyword-count">0</span> <?php esc_html_e('keywords', 'keycontentai'); ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Right Column: Activity Log -->
            <div class="keycontentai-create-right">
                <div class="card">
                    <h2>
                        <?php esc_html_e('Activity Log', 'keycontentai'); ?>
                        <button type="button" id="keycontentai-clear-log-btn" class="button button-small" style="float: right;">
                            <?php esc_html_e('Clear Log', 'keycontentai'); ?>
                        </button>
                    </h2>
                    
                    <div id="keycontentai-log" class="keycontentai-log">
                        <div class="keycontentai-log-empty">
                            <span class="dashicons dashicons-info" style="font-size: 48px; opacity: 0.3;"></span>
                            <p><?php esc_html_e('Activity log is empty. Enter keywords and click "Generate Content" to start.', 'keycontentai'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
