<?php
/**
 * Settings Page with Tabs
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get the active tab from the URL parameter
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'client-settings';
?>

<div class="wrap keycontentai-settings-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <h2 class="nav-tab-wrapper keycontentai-nav-tab-wrapper">
        <a href="?page=keycontentai-settings&tab=client-settings" class="nav-tab <?php echo $active_tab === 'client-settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Client', 'keycontentai'); ?>
        </a>
        <a href="?page=keycontentai-settings&tab=cpt" class="nav-tab <?php echo $active_tab === 'cpt' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('CPT', 'keycontentai'); ?>
        </a>
        <a href="?page=keycontentai-settings&tab=internal-linking" class="nav-tab <?php echo $active_tab === 'internal-linking' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Internal Linking', 'keycontentai'); ?>
        </a>
        <a href="?page=keycontentai-settings&tab=api-settings" class="nav-tab <?php echo $active_tab === 'api-settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('API', 'keycontentai'); ?>
        </a>
    </h2>
    
    <div class="tab-content keycontentai-tab-content">
        <?php
        // Include the appropriate tab file
        $tab_file = '';
        switch ($active_tab) {
            case 'client-settings':
                $tab_file = KEYCONTENTAI_PLUGIN_DIR . 'admin/settings/tabs/tab-client-settings.php';
                break;
            case 'cpt':
                $tab_file = KEYCONTENTAI_PLUGIN_DIR . 'admin/settings/tabs/tab-cpt.php';
                break;
            case 'internal-linking':
                $tab_file = KEYCONTENTAI_PLUGIN_DIR . 'admin/settings/tabs/tab-internal-linking.php';
                break;
            case 'api-settings':
                $tab_file = KEYCONTENTAI_PLUGIN_DIR . 'admin/settings/tabs/tab-api-settings.php';
                break;
            default:
                $tab_file = KEYCONTENTAI_PLUGIN_DIR . 'admin/settings/tabs/tab-client-settings.php';
                break;
        }
        
        if (file_exists($tab_file)) {
            include $tab_file;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Tab file not found.', 'keycontentai') . '</p></div>';
        }
        ?>
    </div>
</div>
