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

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=keycontentai-settings&tab=client-settings" class="nav-tab <?php echo $active_tab === 'client-settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Client', 'keycontentai'); ?>
        </a>
        <a href="?page=keycontentai-settings&tab=cpt" class="nav-tab <?php echo $active_tab === 'cpt' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('CPT', 'keycontentai'); ?>
        </a>
        <a href="?page=keycontentai-settings&tab=api-settings" class="nav-tab <?php echo $active_tab === 'api-settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('API', 'keycontentai'); ?>
        </a>
    </h2>
    
    <div class="tab-content">
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

<style>
    .wrap h1 {
        margin-bottom: 20px;
    }
    
    .nav-tab-wrapper {
        margin-bottom: 0;
    }
    
    .tab-content {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-top: none;
        padding: 20px;
        margin-top: 0;
    }
    
    .keycontentai-tab-panel {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .form-table {
        margin-top: 20px;
    }
    
    .keycontentai-fields-table {
        margin-top: 15px;
        background: #fff;
    }
    
    .keycontentai-fields-table thead th {
        background: #f6f7f7;
        padding: 10px 15px;
        font-weight: 600;
        border-bottom: 2px solid #c3c4c7;
    }
    
    .keycontentai-fields-table tbody td {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
        vertical-align: top;
    }
    
    .keycontentai-fields-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .keycontentai-fields-table tbody tr:hover {
        background: #f9f9f9;
    }
    
    .keycontentai-field-type {
        display: inline-block;
        padding: 3px 8px;
        background: #f0f0f1;
        border-radius: 3px;
        font-size: 12px;
        color: #2c3338;
    }
    
    .keycontentai-field-source {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .keycontentai-source-acf {
        background: #d4edda;
        color: #155724;
    }
    
    .keycontentai-source-meta {
        background: #cce5ff;
        color: #004085;
    }
    
    .keycontentai-fields-table textarea {
        width: 100%;
        font-size: 13px;
        resize: vertical;
    }
    
    .keycontentai-fields-table code {
        background: #f0f0f1;
        padding: 2px 6px;
        border-radius: 3px;
    }
</style>
