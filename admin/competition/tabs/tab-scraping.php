<?php
/**
 * Competition Scraping Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="keycontentai-tab-panel">
    <div class="notice notice-info inline" style="margin: 20px 0;">
        <p>
            <span class="dashicons dashicons-info" style="vertical-align: middle; margin-right: 5px;"></span>
            <strong><?php esc_html_e('Feature In Development', 'keycontentai'); ?></strong>
        </p>
        <p style="margin-left: 30px;">
            <?php esc_html_e('The content scraping feature is currently under development. This feature will allow you to automatically extract and analyze content from competitor URLs to improve AI-generated content.', 'keycontentai'); ?>
        </p>
    </div>
    
    <h2><?php esc_html_e('Upcoming Features', 'keycontentai'); ?></h2>
    
    <div class="card" style="max-width: 800px; padding: 20px;">
        <ul style="list-style: disc; margin-left: 20px; line-height: 2;">
            <li><?php esc_html_e('Automatic content extraction from competitor URLs', 'keycontentai'); ?></li>
            <li><?php esc_html_e('Smart content analysis and keyword detection', 'keycontentai'); ?></li>
            <li><?php esc_html_e('Competitor content comparison and insights', 'keycontentai'); ?></li>
            <li><?php esc_html_e('Customizable scraping rules and filters', 'keycontentai'); ?></li>
            <li><?php esc_html_e('Content caching for faster processing', 'keycontentai'); ?></li>
            <li><?php esc_html_e('Scheduled automatic scraping updates', 'keycontentai'); ?></li>
        </ul>
    </div>
    
    <p style="margin-top: 20px; color: #666;">
        <em>
            <?php esc_html_e('Stay tuned for updates! This feature will be available in a future release.', 'keycontentai'); ?>
        </em>
    </p>
</div>
