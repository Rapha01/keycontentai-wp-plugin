<?php
/**
 * Internal Linking Settings Tab
 * 
 * Manage the pool of content available for internal linking
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// IIFE pattern to avoid globally-scoped variables (WordPress coding standards requirement).
(function() {

// Get all public post types
$post_types = get_post_types(array('public' => true), 'objects');
$available_post_types = array();
foreach ($post_types as $post_type) {
    if ($post_type->name !== 'attachment') {
        $available_post_types[$post_type->name] = $post_type;
    }
}

// Load linking enable settings
$linking_enable = get_option('sparkplus_linking_enable', false);
$linking_wysiwyg = get_option('sparkplus_linking_wysiwyg', false);

// Load saved linking pool settings
$linking_pool_json = get_option('sparkplus_linking_pool', '');
$linking_pool = array(
    'post_types' => array(),
    'single_items' => array(),
    'custom_links' => array()
);

if (!empty($linking_pool_json)) {
    $saved_pool = json_decode($linking_pool_json, true);
    if (is_array($saved_pool)) {
        $linking_pool = array_merge($linking_pool, $saved_pool);
    }
}

?>

<div class="sparkplus-interlinking-container">
    
    <!-- Header -->
    <div class="sparkplus-interlinking-header">
        <h2><?php esc_html_e('Internal Linking Pool', 'sparkplus'); ?></h2>
        <p class="description">
            <?php esc_html_e('Select content that should be available for automatic internal linking. Check entire post types or expand to select specific items.', 'sparkplus'); ?>
        </p>
    </div>

    <!-- Linking Options -->
    <div class="sparkplus-linking-options">
        <div class="sparkplus-option-group">
            <label class="sparkplus-checkbox-label">
                <input 
                    type="checkbox" 
                    id="sparkplus-enable-linking" 
                    class="sparkplus-linking-checkbox"
                    <?php checked($linking_enable, true); ?>
                />
                <span class="sparkplus-option-title">
                    <?php esc_html_e('Add linking pool to the prompt (selected posts/CPT/links).', 'sparkplus'); ?>
                </span>
            </label>
            <p class="description">
                <?php esc_html_e('When enabled, the selected links will be included in the AI prompt for context. This makes sense if you want the AI to generate URL fields, or if you want it to weave selected links into a wysiwyg fields text.', 'sparkplus'); ?>
            </p>
        </div>
        
        <div class="sparkplus-option-group">
            <label class="sparkplus-checkbox-label">
                <input 
                    type="checkbox" 
                    id="sparkplus-enable-wysiwyg-linking" 
                    class="sparkplus-linking-checkbox"
                    <?php checked($linking_wysiwyg, true); ?>
                    <?php disabled($linking_enable, false); ?>
                />
                <span class="sparkplus-option-title">
                    <?php esc_html_e('Enable linking in WYSIWYG fields (creates meaningful links automatically in WYSIWYG content)', 'sparkplus'); ?>
                </span>
            </label>
            <p class="description">
                <?php esc_html_e('When enabled, the AI will automatically insert hyperlinks to relevant content within generated WYSIWYG content.', 'sparkplus'); ?>
            </p>
        </div>
    </div>

    <div class="sparkplus-interlinking-content">
        
        <!-- Post Types Tree -->
        <div class="sparkplus-interlinking-section">
            <div class="sparkplus-interlinking-section-header">
                <h3>
                    <span class="dashicons dashicons-category"></span>
                    <?php esc_html_e('Content Types', 'sparkplus'); ?>
                </h3>
                <p class="description">
                    <?php esc_html_e('Expand folders to select specific items, or check the folder to include all items of that type.', 'sparkplus'); ?>
                </p>
            </div>
            
            <div class="sparkplus-tree-container">
                <?php foreach ($available_post_types as $slug => $post_type): ?>
                    <div class="sparkplus-tree-item sparkplus-tree-folder" data-post-type="<?php echo esc_attr($slug); ?>">
                        <div class="sparkplus-tree-row">
                            <button type="button" class="sparkplus-tree-toggle" aria-label="<?php esc_attr_e('Toggle', 'sparkplus'); ?>">
                                <span class="dashicons dashicons-arrow-right"></span>
                            </button>
                            <label class="sparkplus-tree-label">
                                <input 
                                    type="checkbox" 
                                    class="sparkplus-tree-checkbox sparkplus-post-type-checkbox" 
                                    data-post-type="<?php echo esc_attr($slug); ?>"
                                    value="<?php echo esc_attr($slug); ?>"
                                />
                                <span class="sparkplus-tree-icon">
                                    <span class="dashicons dashicons-category"></span>
                                </span>
                                <span class="sparkplus-tree-text">
                                    <?php echo esc_html($post_type->labels->name); ?>
                                    <span class="sparkplus-tree-count">(<?php echo esc_html($slug); ?>)</span>
                                </span>
                            </label>
                            <span class="sparkplus-tree-badge sparkplus-tree-loading" style="display: none;">
                                <span class="spinner is-active"></span>
                            </span>
                        </div>
                        <div class="sparkplus-tree-children" data-post-type="<?php echo esc_attr($slug); ?>" style="display: none;">
                            <!-- Items will be loaded via AJAX when expanded -->
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Custom Links Section -->
        <div class="sparkplus-interlinking-section sparkplus-custom-links-section">
            <div class="sparkplus-interlinking-section-header sparkplus-custom-links-header">
                <div class="sparkplus-custom-links-title">
                    <h3>
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php esc_html_e('Custom Links', 'sparkplus'); ?>
                    </h3>
                    <p class="description">
                        <?php esc_html_e('Add external URLs or specific internal URLs that should be available for linking.', 'sparkplus'); ?>
                    </p>
                </div>
                <button type="button" class="button sparkplus-toggle-custom-links" aria-label="<?php esc_attr_e('Toggle custom links', 'sparkplus'); ?>">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                    <span class="sparkplus-toggle-text"><?php esc_html_e('Add Links', 'sparkplus'); ?></span>
                </button>
            </div>

            <div class="sparkplus-custom-links-container" style="display: none;">
                
                <!-- Add Custom Link Form -->
                <div class="sparkplus-custom-link-form">
                    <div class="sparkplus-form-grid">
                        <div class="sparkplus-form-field">
                            <label for="sparkplus-custom-url"><?php esc_html_e('URL', 'sparkplus'); ?></label>
                            <input 
                                type="url" 
                                id="sparkplus-custom-url" 
                                class="sparkplus-input" 
                                placeholder="https://example.com/page"
                            />
                        </div>
                        
                        <div class="sparkplus-form-field">
                            <label for="sparkplus-custom-title"><?php esc_html_e('Link Title', 'sparkplus'); ?></label>
                            <input 
                                type="text" 
                                id="sparkplus-custom-title" 
                                class="sparkplus-input" 
                                placeholder="<?php esc_attr_e('Descriptive title', 'sparkplus'); ?>"
                            />
                        </div>
                        
                        <div class="sparkplus-form-field sparkplus-form-field-full">
                            <label for="sparkplus-custom-keywords">
                                <?php esc_html_e('Keywords', 'sparkplus'); ?>
                                <span class="description"><?php esc_html_e('(optional, comma-separated)', 'sparkplus'); ?></span>
                            </label>
                            <input 
                                type="text" 
                                id="sparkplus-custom-keywords" 
                                class="sparkplus-input" 
                                placeholder="<?php esc_attr_e('keyword1, keyword2, keyword3', 'sparkplus'); ?>"
                            />
                        </div>
                        
                        <div class="sparkplus-form-field sparkplus-form-field-button">
                            <button type="button" class="button button-secondary" id="sparkplus-add-custom-link">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php esc_html_e('Add Link', 'sparkplus'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Custom Links List -->
                <div class="sparkplus-custom-links-list" id="sparkplus-custom-links-list">
                    <div class="sparkplus-empty-state">
                        <span class="dashicons dashicons-info"></span>
                        <p><?php esc_html_e('No custom links added yet.', 'sparkplus'); ?></p>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Save Button -->
    <div class="sparkplus-interlinking-footer">
        <button type="button" class="button button-primary button-hero" id="sparkplus-save-linking-pool">
            <span class="dashicons dashicons-saved"></span>
            <?php esc_html_e('Save Linking Pool', 'sparkplus'); ?>
        </button>
        <span class="sparkplus-save-status" id="sparkplus-save-status"></span>
    </div>

</div>

<?php
})(); // End IIFE
