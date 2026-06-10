<?php
/**
 * Reset Settings Tab Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// IIFE pattern to avoid globally-scoped variables (WordPress coding standards requirement).
(function() {
?>

<div class="sparkplus-tab-panel">
    <h2><?php esc_html_e('Reset Settings', 'sparkplus'); ?></h2>
    <p class="description" style="margin-bottom: 15px; max-width: 600px;">
        <?php esc_html_e('Delete all SparkPlus options from the database (API keys, CPT configurations, general context, WYSIWYG formatting). The plugin will behave as if freshly installed.', 'sparkplus'); ?>
    </p>
    <p>
        <button type="button" class="button button-secondary" id="sparkplus-reset-settings-trigger">
            <?php esc_html_e('Reset Settings', 'sparkplus'); ?>
        </button>
        <span class="sparkplus-save-status" id="sparkplus-reset-settings-status"></span>
    </p>
    
    <!-- Settings Reset Modal -->
    <div class="sparkplus-modal-overlay" id="sparkplus-reset-settings-modal" style="display: none;">
        <div class="sparkplus-modal">
            <h3><?php esc_html_e('Are you sure?', 'sparkplus'); ?></h3>
            <p>
                <?php esc_html_e('This will permanently delete all SparkPlus settings, including API keys, CPT configurations, and general context.', 'sparkplus'); ?>
            </p>
            <p style="color: #d63638; font-weight: 600;">
                <?php esc_html_e('This action cannot be undone.', 'sparkplus'); ?>
            </p>
            <div class="sparkplus-modal-actions">
                <button type="button" class="button button-secondary sparkplus-modal-cancel">
                    <?php esc_html_e('Cancel', 'sparkplus'); ?>
                </button>
                <button type="button" class="button button-primary sparkplus-button-danger sparkplus-modal-confirm" data-target="settings">
                    <?php esc_html_e('Yes, Delete Settings', 'sparkplus'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <hr style="margin: 30px 0;" />
    
    <?php
    $selected_post_type = get_option('sparkplus_selected_post_type', 'post');
    $post_type_object = get_post_type_object($selected_post_type);
    $post_type_label = $post_type_object ? $post_type_object->labels->name : $selected_post_type;
    ?>
    
    <h2><?php esc_html_e('Reset Post Meta', 'sparkplus'); ?></h2>
    <p class="description" style="margin-bottom: 15px; max-width: 600px;">
        <?php
        echo esc_html(sprintf(
            /* translators: %s: post type label */
            __('Delete all SparkPlus post meta from all "%s" posts (keywords, additional context, last generation timestamps). The posts themselves will not be deleted.', 'sparkplus'),
            $post_type_label
        ));
        ?>
    </p>
    <p>
        <button type="button" class="button button-secondary" id="sparkplus-reset-meta-trigger">
            <?php
            echo esc_html(sprintf(
                /* translators: %s: post type label */
                __('Reset Post Meta for "%s"', 'sparkplus'), $post_type_label));
            ?>
        </button>
        <span class="sparkplus-save-status" id="sparkplus-reset-meta-status"></span>
    </p>
    
    <!-- Post Meta Reset Modal -->
    <div class="sparkplus-modal-overlay" id="sparkplus-reset-meta-modal" style="display: none;">
        <div class="sparkplus-modal">
            <h3><?php esc_html_e('Are you sure?', 'sparkplus'); ?></h3>
            <p>
                <?php
                echo esc_html(sprintf(
                    /* translators: %s: post type label */
                    __('This will permanently delete all SparkPlus post meta from all "%s" posts (keywords, additional context, generation timestamps). The posts themselves will not be deleted.', 'sparkplus'),
                    $post_type_label
                ));
                ?>
            </p>
            <p style="color: #d63638; font-weight: 600;">
                <?php esc_html_e('This action cannot be undone.', 'sparkplus'); ?>
            </p>
            <div class="sparkplus-modal-actions">
                <button type="button" class="button button-secondary sparkplus-modal-cancel">
                    <?php esc_html_e('Cancel', 'sparkplus'); ?>
                </button>
                <button type="button" class="button button-primary sparkplus-button-danger sparkplus-modal-confirm" data-target="meta" data-post-type="<?php echo esc_attr($selected_post_type); ?>">
                    <?php
                    /* translators: %s: post type label */
                    echo esc_html(sprintf(__('Yes, Delete Post Meta for "%s"', 'sparkplus'), $post_type_label));
                    ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
})(); // End IIFE
