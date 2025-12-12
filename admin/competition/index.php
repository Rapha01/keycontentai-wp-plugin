<?php
/**
 * Competition Page - Main file with tab navigation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'urls';

// Available tabs
$tabs = array(
    'urls' => __('URLs', 'keycontentai'),
    'scraping' => __('Scraping', 'keycontentai')
);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <p class="description">
        <?php esc_html_e('Manage competitor URLs and configure content scraping settings.', 'keycontentai'); ?>
    </p>
    
    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_key => $tab_label) : ?>
            <a 
                href="<?php echo esc_url(admin_url('admin.php?page=keycontentai-competition&tab=' . $tab_key)); ?>" 
                class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>"
            >
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Tab Content -->
    <?php
    $tab_file = KEYCONTENTAI_PLUGIN_DIR . 'admin/competition/tabs/tab-' . $current_tab . '.php';
    
    if (file_exists($tab_file)) {
        include $tab_file;
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html__('Tab content not found.', 'keycontentai') . '</p></div>';
    }
    ?>
</div>
