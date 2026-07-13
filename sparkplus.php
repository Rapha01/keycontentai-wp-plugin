<?php
/**
 * Plugin Name: SparkPlus
 * Description: Creates and populates custom post types with AI-generated content based on user keywords.
 * Version: 1.1.6
 * Author: olympagency
 * Author URI: https://olympagency.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sparkplus
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SPARKPLUS_VERSION', '1.1.6');
define('SPARKPLUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPARKPLUS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPARKPLUS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/*
 * This file is a thin bootstrap. It loads two independent products, each living
 * in its own folder and unaware of the other:
 *
 *   sparkplus/    — the SparkPlus AI content generator   (see sparkplus-core.php)
 *   olymp-tools/  — standalone side-features with their own top-level menu
 */

// ── SparkPlus core (AI content generation) ──
require_once SPARKPLUS_PLUGIN_DIR . 'sparkplus/sparkplus-core.php';
add_action('plugins_loaded', 'sparkplus_init');

// ── Olymp Tools (independent side-features) ──
require_once SPARKPLUS_PLUGIN_DIR . 'olymp-tools/olymp-tools-core.php';
add_action('plugins_loaded', 'olymp_tools_init');
