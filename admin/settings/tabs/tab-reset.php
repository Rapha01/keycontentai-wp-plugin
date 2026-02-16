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

<div class="sparkwp-tab-panel">
    <h2><?php esc_html_e('Reset Settings', 'sparkwp'); ?></h2>
    <p class="description" style="margin-bottom: 15px; max-width: 600px;">
        <?php esc_html_e('Delete all SparkWP options from the database (API keys, CPT configurations, general context, WYSIWYG formatting). The plugin will behave as if freshly installed.', 'sparkwp'); ?>
    </p>
    <p>
        <button type="button" class="button button-secondary" id="sparkwp-reset-settings-trigger">
            <?php esc_html_e('Reset Settings', 'sparkwp'); ?>
        </button>
        <span class="sparkwp-save-status" id="sparkwp-reset-settings-status"></span>
    </p>
    
    <!-- Settings Reset Modal -->
    <div class="sparkwp-modal-overlay" id="sparkwp-reset-settings-modal" style="display: none;">
        <div class="sparkwp-modal">
            <h3><?php esc_html_e('Are you sure?', 'sparkwp'); ?></h3>
            <p>
                <?php esc_html_e('This will permanently delete all SparkWP settings, including API keys, CPT configurations, and general context.', 'sparkwp'); ?>
            </p>
            <p style="color: #d63638; font-weight: 600;">
                <?php esc_html_e('This action cannot be undone.', 'sparkwp'); ?>
            </p>
            <div class="sparkwp-modal-actions">
                <button type="button" class="button button-secondary sparkwp-modal-cancel">
                    <?php esc_html_e('Cancel', 'sparkwp'); ?>
                </button>
                <button type="button" class="button button-primary sparkwp-button-danger sparkwp-modal-confirm" data-target="settings">
                    <?php esc_html_e('Yes, Delete Settings', 'sparkwp'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <hr style="margin: 30px 0;" />
    
    <?php
    $selected_post_type = get_option('sparkwp_selected_post_type', 'post');
    $post_type_object = get_post_type_object($selected_post_type);
    $post_type_label = $post_type_object ? $post_type_object->labels->name : $selected_post_type;
    ?>
    
    <h2><?php esc_html_e('Reset Post Meta', 'sparkwp'); ?></h2>
    <p class="description" style="margin-bottom: 15px; max-width: 600px;">
        <?php
        echo esc_html(sprintf(
            /* translators: %s: post type label */
            __('Delete all SparkWP post meta from all "%s" posts (keywords, additional context, last generation timestamps). The posts themselves will not be deleted.', 'sparkwp'),
            $post_type_label
        ));
        ?>
    </p>
    <p>
        <button type="button" class="button button-secondary" id="sparkwp-reset-meta-trigger">
            <?php
            echo esc_html(sprintf(
                /* translators: %s: post type label */
                __('Reset Post Meta for "%s"', 'sparkwp'), $post_type_label));
            ?>
        </button>
        <span class="sparkwp-save-status" id="sparkwp-reset-meta-status"></span>
    </p>
    
    <!-- Post Meta Reset Modal -->
    <div class="sparkwp-modal-overlay" id="sparkwp-reset-meta-modal" style="display: none;">
        <div class="sparkwp-modal">
            <h3><?php esc_html_e('Are you sure?', 'sparkwp'); ?></h3>
            <p>
                <?php
                echo esc_html(sprintf(
                    /* translators: %s: post type label */
                    __('This will permanently delete all SparkWP post meta from all "%s" posts (keywords, additional context, generation timestamps). The posts themselves will not be deleted.', 'sparkwp'),
                    $post_type_label
                ));
                ?>
            </p>
            <p style="color: #d63638; font-weight: 600;">
                <?php esc_html_e('This action cannot be undone.', 'sparkwp'); ?>
            </p>
            <div class="sparkwp-modal-actions">
                <button type="button" class="button button-secondary sparkwp-modal-cancel">
                    <?php esc_html_e('Cancel', 'sparkwp'); ?>
                </button>
                <button type="button" class="button button-primary sparkwp-button-danger sparkwp-modal-confirm" data-target="meta" data-post-type="<?php echo esc_attr($selected_post_type); ?>">
                    <?php
                    /* translators: %s: post type label */
                    echo esc_html(sprintf(__('Yes, Delete Post Meta for "%s"', 'sparkwp'), $post_type_label));
                    ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
})(); // End IIFE
