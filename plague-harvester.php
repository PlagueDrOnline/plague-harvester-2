<?php
/**
 * Plugin Name: Plague Harvester
 * Plugin URI: https://plaguedr.online
 * Description: Steal the web. Cure your content hunger. Crawl, import, export, and rewrite content into WordPress.
 * Version: 1.0.0
 * Author: Plague Doctor Labs
 * Author URI: https://plaguedr.online
 * License: GPL-2.0+
 * Text Domain: plague-harvester
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Define constants
define('PLAGUE_HARVESTER_VERSION', '1.0.0');
define('PLAGUE_HARVESTER_PATH', plugin_dir_path(__FILE__));
define('PLAGUE_HARVESTER_URL', plugin_dir_url(__FILE__));

// Load classes
require_once PLAGUE_HARVESTER_PATH . 'includes/class-helper.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-crawler.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-importer.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-exporter.php';

// Admin menu
add_action('admin_menu', 'plague_harvester_admin_menu');
function plague_harvester_admin_menu() {
    add_menu_page(
        'Plague Harvester',
        'Plague Harvester',
        'manage_options',
        'plague-harvester',
        'plague_harvester_admin_page',
        'dashicons-admin-site',
        80
    );
}

// Admin page
function plague_harvester_admin_page() {
    include PLAGUE_HARVESTER_PATH . 'templates/admin-page.php';
}

// Enqueue assets
add_action('admin_enqueue_scripts', 'plague_harvester_enqueue_assets');
function plague_harvester_enqueue_assets($hook) {
    if ($hook != 'toplevel_page_plague-harvester') return;

    wp_enqueue_style('plague-harvester-admin', PLAGUE_HARVESTER_URL . 'assets/css/admin.css', [], PLAGUE_HARVESTER_VERSION);
    wp_enqueue_script('plague-harvester-admin', PLAGUE_HARVESTER_URL . 'assets/js/admin.js', ['jquery'], PLAGUE_HARVESTER_VERSION, true);
}