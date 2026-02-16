<?php
/**
 * Load Keywords Page
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
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (!$is_configured) : ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('Configuration Required:', 'sparkwp'); ?></strong>
                <?php esc_html_e('Please configure the plugin settings before creating content.', 'sparkwp'); ?>
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
        <div class="notice notice-info" style="margin-bottom: 20px;">
            <p>
                <strong><?php esc_html_e('Ready to Create!', 'sparkwp'); ?></strong>
                <?php 
                $post_type_object = get_post_type_object($selected_post_type);
                printf(
                    /* translators: %s: post type singular name */
                    esc_html__('Content will be created as: %s', 'sparkwp'),
                    '<strong>' . esc_html($post_type_object->labels->singular_name) . '</strong>'
                );
                ?>
            </p>
        </div>
        
        <div class="sparkwp-loadkeywords-layout">
            <!-- Left Column: Keywords Input -->
            <div class="sparkwp-loadkeywords-left">
                <div class="card">
                    <h2><?php esc_html_e('Keywords', 'sparkwp'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Enter one keyword per line. Each keyword will generate a separate post.', 'sparkwp'); ?>
                    </p>
                    
                    <form id="sparkwp-load-keywords-form" method="post">
                        <div class="sparkwp-textarea-container">
                            <div class="sparkwp-textarea-wrapper" id="sparkwp-keywords-wrapper">
                                <label for="sparkwp-keywords" class="sparkwp-textarea-label">
                                    <?php esc_html_e('Keywords', 'sparkwp'); ?>
                                </label>
                                <textarea 
                                    id="sparkwp-keywords" 
                                    name="keywords" 
                                    rows="20" 
                                    class="sparkwp-loadkeywords-keywords-textarea"
                                    placeholder="<?php esc_attr_e('WordPress Plugins&#10;SEO Best Practices&#10;Content Marketing Tips&#10;...', 'sparkwp'); ?>"
                                ></textarea>
                            </div>
                            
                            <div class="sparkwp-textarea-wrapper" id="sparkwp-context-wrapper" style="display: none;">
                                <label for="sparkwp-additional-context" class="sparkwp-textarea-label">
                                    <?php esc_html_e('Additional Context', 'sparkwp'); ?>
                                </label>
                                <textarea 
                                    id="sparkwp-additional-context" 
                                    name="additional_context" 
                                    rows="20" 
                                    class="sparkwp-loadkeywords-keywords-textarea"
                                    placeholder="<?php esc_attr_e('Context for line 1&#10;Context for line 2&#10;Context for line 3&#10;...', 'sparkwp'); ?>"
                                ></textarea>
                            </div>
                        </div>
                        
                        <div class="sparkwp-context-option" style="margin-top: 10px; padding: 6px 10px; background: #f6f7f7; border-left: 3px solid #2271b1; border-radius: 3px;">
                            <label style="display: flex; align-items: center; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="sparkwp-enable-context" name="enable_context" value="1" style="margin: 0 8px 0 0;">
                                <span style="font-weight: 500; font-size: 13px;">
                                    <?php esc_html_e('Use additional context per keyword', 'sparkwp'); ?>
                                </span>
                            </label>
                            <p class="description" style="margin: 3px 0 0 24px; font-size: 11px; line-height: 1.3;">
                                <?php esc_html_e('When checked, you can provide specific context for each keyword. Each line in the additional context textarea corresponds to the same line number in the keywords textarea.', 'sparkwp'); ?>
                            </p>
                        </div>
                        
                        <div class="sparkwp-publish-option" style="margin-top: 10px; padding: 6px 10px; background: #f6f7f7; border-left: 3px solid #2271b1; border-radius: 3px;">
                            <label style="display: flex; align-items: center; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="sparkwp-auto-publish" name="auto_publish" value="1" style="margin: 0 8px 0 0;">
                                <span style="font-weight: 500; font-size: 13px;">
                                    <?php esc_html_e('Publish posts immediately', 'sparkwp'); ?>
                                </span>
                            </label>
                            <p class="description" style="margin: 3px 0 0 24px; font-size: 11px; line-height: 1.3;">
                                <?php esc_html_e('When checked, new posts will be published instead of saved as drafts. Existing draft posts will also be published.', 'sparkwp'); ?>
                            </p>
                        </div>
                        
                        <div class="sparkwp-loadkeywords-actions">
                            <button type="submit" id="sparkwp-start-btn" class="button button-primary button-hero">
                                <span class="dashicons dashicons-admin-post" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Generate Posts', 'sparkwp'); ?>
                            </button>
                            
                            <button type="button" id="sparkwp-stop-btn" class="button button-secondary button-hero" style="display: none;">
                                <span class="dashicons dashicons-no" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Stop', 'sparkwp'); ?>
                            </button>
                            
                            <button type="button" id="sparkwp-clear-btn" class="button button-link">
                                <?php esc_html_e('Clear All', 'sparkwp'); ?>
                            </button>
                        </div>
                        
                        <div class="sparkwp-loadkeywords-stats">
                            <span id="sparkwp-keyword-count">0</span> <?php esc_html_e('keywords', 'sparkwp'); ?>
                            <span id="sparkwp-context-count-wrapper" style="display: none;"> | 
                                <span id="sparkwp-context-count">0</span> <?php esc_html_e('context lines', 'sparkwp'); ?>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Debug Toggle -->
        <div class="sparkwp-loadkeywords-debug-toggle" style="margin-top: 20px; margin-bottom: 10px;">
            <button type="button" id="sparkwp-toggle-debug-btn" class="button button-secondary">
                <span class="dashicons dashicons-admin-tools" style="margin-top: 4px; margin-right: 5px;"></span>
                <span class="button-text"><?php esc_html_e('Show Debug Mode', 'sparkwp'); ?></span>
            </button>
            <p class="description" style="margin-top: 8px;">
                <?php esc_html_e('Show detailed information about the content generation process', 'sparkwp'); ?>
            </p>
        </div>
        
        <!-- Debug Output Box (hidden by default) -->
        <div id="sparkwp-debug-container" class="sparkwp-loadkeywords-debug-container" style="display: none;">
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
                
                <!-- Debug Output -->
                <div id="sparkwp-debug-output" class="sparkwp-loadkeywords-debug-output">
                    <div class="sparkwp-loadkeywords-debug-empty">
                        <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                        <p><?php esc_html_e('Debug information will appear here when generation starts.', 'sparkwp'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
})(); // End IIFE
