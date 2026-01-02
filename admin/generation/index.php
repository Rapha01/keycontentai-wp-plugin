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
            <div class="notice notice-info">
                <p>
                    <strong><?php esc_html_e('Generation Page', 'keycontentai'); ?></strong>
                </p>
                <p><?php esc_html_e('This page will be used to generate content from loaded keywords.', 'keycontentai'); ?></p>
            </div>
            
            <!-- TODO: Add generation interface here -->
            <div class="keycontentai-generation-content">
                <p><?php esc_html_e('Generation interface coming soon...', 'keycontentai'); ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>
