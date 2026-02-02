<?php
/**
 * Load Keywords Page
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
        
        <div class="keycontentai-loadkeywords-layout">
            <!-- Left Column: Keywords Input -->
            <div class="keycontentai-loadkeywords-left">
                <div class="card">
                    <h2><?php esc_html_e('Keywords', 'keycontentai'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Enter one keyword per line. Each keyword will generate a separate post.', 'keycontentai'); ?>
                    </p>
                    
                    <form id="keycontentai-load-keywords-form" method="post">
                        <div class="keycontentai-textarea-container">
                            <div class="keycontentai-textarea-wrapper" id="keycontentai-keywords-wrapper">
                                <label for="keycontentai-keywords" class="keycontentai-textarea-label">
                                    <?php esc_html_e('Keywords', 'keycontentai'); ?>
                                </label>
                                <textarea 
                                    id="keycontentai-keywords" 
                                    name="keywords" 
                                    rows="20" 
                                    class="keycontentai-loadkeywords-keywords-textarea"
                                    placeholder="<?php esc_attr_e('WordPress Plugins&#10;SEO Best Practices&#10;Content Marketing Tips&#10;...', 'keycontentai'); ?>"
                                ></textarea>
                            </div>
                            
                            <div class="keycontentai-textarea-wrapper" id="keycontentai-context-wrapper" style="display: none;">
                                <label for="keycontentai-additional-context" class="keycontentai-textarea-label">
                                    <?php esc_html_e('Additional Context', 'keycontentai'); ?>
                                </label>
                                <textarea 
                                    id="keycontentai-additional-context" 
                                    name="additional_context" 
                                    rows="20" 
                                    class="keycontentai-loadkeywords-keywords-textarea"
                                    placeholder="<?php esc_attr_e('Context for line 1&#10;Context for line 2&#10;Context for line 3&#10;...', 'keycontentai'); ?>"
                                ></textarea>
                            </div>
                        </div>
                        
                        <div class="keycontentai-context-option" style="margin-top: 10px; padding: 6px 10px; background: #f6f7f7; border-left: 3px solid #2271b1; border-radius: 3px;">
                            <label style="display: flex; align-items: center; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="keycontentai-enable-context" name="enable_context" value="1" style="margin: 0 8px 0 0;">
                                <span style="font-weight: 500; font-size: 13px;">
                                    <?php esc_html_e('Use additional context per keyword', 'keycontentai'); ?>
                                </span>
                            </label>
                            <p class="description" style="margin: 3px 0 0 24px; font-size: 11px; line-height: 1.3;">
                                <?php esc_html_e('When checked, you can provide specific context for each keyword. Each line in the additional context textarea corresponds to the same line number in the keywords textarea.', 'keycontentai'); ?>
                            </p>
                        </div>
                        
                        <div class="keycontentai-publish-option" style="margin-top: 10px; padding: 6px 10px; background: #f6f7f7; border-left: 3px solid #2271b1; border-radius: 3px;">
                            <label style="display: flex; align-items: center; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="keycontentai-auto-publish" name="auto_publish" value="1" style="margin: 0 8px 0 0;">
                                <span style="font-weight: 500; font-size: 13px;">
                                    <?php esc_html_e('Publish posts immediately', 'keycontentai'); ?>
                                </span>
                            </label>
                            <p class="description" style="margin: 3px 0 0 24px; font-size: 11px; line-height: 1.3;">
                                <?php esc_html_e('When checked, new posts will be published instead of saved as drafts. Existing draft posts will also be published.', 'keycontentai'); ?>
                            </p>
                        </div>
                        
                        <div class="keycontentai-loadkeywords-actions">
                            <button type="submit" id="keycontentai-start-btn" class="button button-primary button-hero">
                                <span class="dashicons dashicons-admin-post" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Generate Posts', 'keycontentai'); ?>
                            </button>
                            
                            <button type="button" id="keycontentai-stop-btn" class="button button-secondary button-hero" style="display: none;">
                                <span class="dashicons dashicons-no" style="margin-top: 4px;"></span>
                                <?php esc_html_e('Stop', 'keycontentai'); ?>
                            </button>
                            
                            <button type="button" id="keycontentai-clear-btn" class="button button-link">
                                <?php esc_html_e('Clear All', 'keycontentai'); ?>
                            </button>
                        </div>
                        
                        <div class="keycontentai-loadkeywords-stats">
                            <span id="keycontentai-keyword-count">0</span> <?php esc_html_e('keywords', 'keycontentai'); ?>
                            <span id="keycontentai-context-count-wrapper" style="display: none;"> | 
                                <span id="keycontentai-context-count">0</span> <?php esc_html_e('context lines', 'keycontentai'); ?>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Debug Toggle -->
        <div class="keycontentai-loadkeywords-debug-toggle" style="margin-top: 20px; margin-bottom: 10px;">
            <button type="button" id="keycontentai-toggle-debug-btn" class="button button-secondary">
                <span class="dashicons dashicons-admin-tools" style="margin-top: 4px; margin-right: 5px;"></span>
                <span class="button-text"><?php esc_html_e('Show Debug Mode', 'keycontentai'); ?></span>
            </button>
            <p class="description" style="margin-top: 8px;">
                <?php esc_html_e('Show detailed information about the content generation process', 'keycontentai'); ?>
            </p>
        </div>
        
        <!-- Debug Output Box (hidden by default) -->
        <div id="keycontentai-debug-container" class="keycontentai-loadkeywords-debug-container" style="display: none;">
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
                
                <!-- Debug Output -->
                <div id="keycontentai-debug-output" class="keycontentai-loadkeywords-debug-output">
                    <div class="keycontentai-loadkeywords-debug-empty">
                        <span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
                        <p><?php esc_html_e('Debug information will appear here when generation starts.', 'keycontentai'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
