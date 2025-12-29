<?php
/**
 * Edit Page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('keycontentai_openai_api_key', '');
$is_configured = !empty($api_key);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (!$is_configured) : ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('Configuration Required:', 'keycontentai'); ?></strong>
                <?php esc_html_e('Please configure the plugin settings before editing content.', 'keycontentai'); ?>
            </p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=keycontentai-settings&tab=api-settings')); ?>" class="button button-primary">
                    <?php esc_html_e('Set OpenAI API Key', 'keycontentai'); ?>
                </a>
            </p>
        </div>
    <?php else : ?>
        <div class="keycontentai-edit-container">
            <p><?php esc_html_e('Edit page content coming soon...', 'keycontentai'); ?></p>
        </div>
    <?php endif; ?>
</div>
