<?php
/**
 * Internal Linking Page
 * 
 * Placeholder for internal linking functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="sparkwp-interlinking-container">
        <div class="card">
            <h2><?php esc_html_e('Internal Linking', 'sparkwp'); ?></h2>
            <p class="description">
                <?php esc_html_e('This feature will help you create and manage internal links within your content.', 'sparkwp'); ?>
            </p>
            
            <div class="sparkwp-interlinking-placeholder">
                <p><?php esc_html_e('Internal linking functionality coming soon...', 'sparkwp'); ?></p>
            </div>
        </div>
    </div>
</div>
