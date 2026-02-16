<?php
/**
 * Settings Page with Tabs
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// IIFE pattern to avoid globally-scoped variables (WordPress coding standards requirement).
(function() {

// Get the active tab from the URL parameter
$active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'cpt';
?>

<div class="wrap sparkwp-settings-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <h2 class="nav-tab-wrapper sparkwp-nav-tab-wrapper">
        <a href="?page=sparkwp-settings&tab=cpt" class="nav-tab <?php echo $active_tab === 'cpt' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('CPT', 'sparkwp'); ?>
        </a>
        <a href="?page=sparkwp-settings&tab=general-context" class="nav-tab <?php echo $active_tab === 'general-context' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('General Context', 'sparkwp'); ?>
        </a>
        <a href="?page=sparkwp-settings&tab=api-settings" class="nav-tab <?php echo $active_tab === 'api-settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('API', 'sparkwp'); ?>
        </a>
        <a href="?page=sparkwp-settings&tab=reset" class="nav-tab <?php echo $active_tab === 'reset' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Reset', 'sparkwp'); ?>
        </a>
    </h2>
    
    <div class="tab-content sparkwp-tab-content">
        <?php
        // Include the appropriate tab file
        $tab_file = '';
        switch ($active_tab) {
            case 'cpt':
                $tab_file = SPARKWP_PLUGIN_DIR . 'admin/settings/tabs/tab-cpt.php';
                break;
            case 'general-context':
                $tab_file = SPARKWP_PLUGIN_DIR . 'admin/settings/tabs/tab-general-context.php';
                break;
            case 'api-settings':
                $tab_file = SPARKWP_PLUGIN_DIR . 'admin/settings/tabs/tab-api-settings.php';
                break;
            case 'reset':
                $tab_file = SPARKWP_PLUGIN_DIR . 'admin/settings/tabs/tab-reset.php';
                break;
            default:
                $tab_file = SPARKWP_PLUGIN_DIR . 'admin/settings/tabs/tab-cpt.php';
                break;
        }
        
        if (file_exists($tab_file)) {
            include $tab_file;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Tab file not found.', 'sparkwp') . '</p></div>';
        }
        ?>
    </div>
</div>

<?php
})(); // End IIFE
