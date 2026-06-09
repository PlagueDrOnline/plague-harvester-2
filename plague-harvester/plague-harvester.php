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

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('PLAGUE_HARVESTER_VERSION', '1.0.0');
define('PLAGUE_HARVESTER_PATH', plugin_dir_path(__FILE__));
define('PLAGUE_HARVESTER_URL', plugin_dir_url(__FILE__));

// Load classes
require_once PLAGUE_HARVESTER_PATH . 'includes/class-helper.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-crawler.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-importer.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-exporter.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-proxy.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-proxy-scraper-engine.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-proxy-scraper-admin.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-rewriter.php';
require_once PLAGUE_HARVESTER_PATH . 'includes/class-scheduler.php';

add_action('admin_menu', 'plague_harvester_admin_menu');
add_action('admin_enqueue_scripts', 'plague_harvester_enqueue_assets');
add_action('wp_ajax_plague_harvester_crawl', 'plague_harvester_ajax_crawl');
add_action('wp_ajax_plague_harvester_import', 'plague_harvester_ajax_import');
add_action('wp_ajax_plague_harvester_export', 'plague_harvester_ajax_export');
add_action('wp_ajax_plague_harvester_fetch_import_history', 'plague_harvester_fetch_import_history');
add_action('wp_ajax_plague_harvester_save_banner', 'plague_harvester_ajax_save_banner');
add_action('wp_ajax_plague_harvester_fetch_banners', 'plague_harvester_ajax_fetch_banners');
add_action('wp_ajax_plague_harvester_save_affiliate', 'plague_harvester_ajax_save_affiliate');
add_action('wp_ajax_plague_harvester_fetch_affiliate', 'plague_harvester_ajax_fetch_affiliate');
add_action('plague_harvester_cron', 'plague_harvester_hourly_tasks');
register_activation_hook(__FILE__, 'plague_harvester_activate');
register_deactivation_hook(__FILE__, 'plague_harvester_deactivate');

function plague_harvester_activate() {
    $scheduler = new Plague_Harvester_Scheduler();
    $scheduler->schedule_harvest();
}

function plague_harvester_deactivate() {
    $scheduler = new Plague_Harvester_Scheduler();
    $scheduler->clear_schedule();
}

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

function plague_harvester_admin_page() {
    include PLAGUE_HARVESTER_PATH . 'templates/admin-page.php';
}

function plague_harvester_enqueue_assets($hook) {
    if ($hook !== 'toplevel_page_plague-harvester') {
        return;
    }

    wp_enqueue_style('plague-harvester-admin', PLAGUE_HARVESTER_URL . 'assets/css/admin.css', [], PLAGUE_HARVESTER_VERSION);
    wp_enqueue_script('plague-harvester-admin', PLAGUE_HARVESTER_URL . 'assets/js/admin.js', ['jquery'], PLAGUE_HARVESTER_VERSION, true);
    wp_localize_script('plague-harvester-admin', 'plagueHarvester', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('plague_harvester_nonce'),
    ]);
}

function plague_harvester_ajax_crawl() {
    check_ajax_referer('plague_harvester_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }

    $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
    if (empty($url)) {
        wp_send_json_error(['message' => 'Please provide a valid URL.'], 400);
    }

    $crawler = new Plague_Harvester_Crawler();
    $result = $crawler->crawl($url);
    if (!$result) {
        wp_send_json_error(['message' => 'Unable to crawl this URL. Please verify the address and try again.'], 500);
    }

    wp_send_json_success($result);
}

function plague_harvester_ajax_import() {
    check_ajax_referer('plague_harvester_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }

    $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
    if (empty($url)) {
        wp_send_json_error(['message' => 'Please provide a valid URL to import.'], 400);
    }

    if (post_exists_by_meta_key('_plague_harvester_source_url', $url)) {
        wp_send_json_error(['message' => 'This URL has already been imported.'], 409);
    }

    $crawler = new Plague_Harvester_Crawler();
    $data = $crawler->crawl($url);
    if (!$data) {
        wp_send_json_error(['message' => 'Unable to crawl content for import.'], 500);
    }

    $importer = new Plague_Harvester_Importer();
    $post_id = $importer->import($data, ['status' => 'draft']);
    if (!$post_id) {
        wp_send_json_error(['message' => 'Import failed. Please check your server logs and try again.'], 500);
    }

    update_post_meta($post_id, '_plague_harvester_source_url', $url);
    update_post_meta($post_id, '_plague_harvester_imported', 1);

    wp_send_json_success(['post_id' => $post_id, 'edit_link' => get_edit_post_link($post_id, '')]);
}

function plague_harvester_ajax_export() {
    check_ajax_referer('plague_harvester_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_die('Permission denied.');
    }

    $format = isset($_REQUEST['format']) ? sanitize_text_field(wp_unslash($_REQUEST['format'])) : 'json';
    $exporter = new Plague_Harvester_Exporter();

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=plague-harvester-export-' . date('Y-m-d') . '.csv');
        echo $exporter->export_posts('csv');
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=plague-harvester-export-' . date('Y-m-d') . '.json');
    echo $exporter->export_posts('json');
    exit;
}

function plague_harvester_fetch_import_history() {
    check_ajax_referer('plague_harvester_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }

    $posts = get_posts([
        'post_type'      => 'post',
        'meta_key'       => '_plague_harvester_source_url',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'posts_per_page' => 20,
    ]);

    $history = [];
    foreach ($posts as $post) {
        $history[] = [
            'id'    => $post->ID,
            'title' => get_the_title($post),
            'url'   => esc_url(get_post_meta($post->ID, '_plague_harvester_source_url', true)),
            'edit'  => get_edit_post_link($post->ID, ''),
            'date'  => get_the_date('', $post),
        ];
    }

    wp_send_json_success($history);
}

function plague_harvester_get_banners() {
    return get_option('plague_harvester_banners', []);
}

function plague_harvester_save_banner($banner) {
    $banners = plague_harvester_get_banners();
    $banners[] = $banner;
    update_option('plague_harvester_banners', $banners);
    return $banners;
}

function plague_harvester_ajax_save_banner() {
    check_ajax_referer('plague_harvester_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }

    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $image = isset($_POST['image']) ? esc_url_raw(wp_unslash($_POST['image'])) : '';
    $link = isset($_POST['link']) ? esc_url_raw(wp_unslash($_POST['link'])) : '';
    $active = isset($_POST['active']) ? boolval($_POST['active']) : false;

    if (empty($title) || empty($link)) {
        wp_send_json_error(['message' => 'Banner title and destination URL are required.'], 400);
    }

    $banner = [
        'id'      => uniqid('banner_', true),
        'title'   => $title,
        'image'   => $image,
        'link'    => $link,
        'active'  => $active,
        'created' => current_time('mysql'),
    ];

    $banners = plague_harvester_save_banner($banner);
    wp_send_json_success($banners);
}

function plague_harvester_ajax_fetch_banners() {
    check_ajax_referer('plague_harvester_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }

    wp_send_json_success(plague_harvester_get_banners());
}

function plague_harvester_get_affiliate_settings() {
    return wp_parse_args(get_option('plague_harvester_affiliate_settings', []), [
        'code'        => '',
        'partner_url' => '',
        'notes'       => '',
        'updated'     => '',
    ]);
}

function plague_harvester_ajax_save_affiliate() {
    check_ajax_referer('plague_harvester_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }

    $code = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';
    $partner_url = isset($_POST['partner_url']) ? esc_url_raw(wp_unslash($_POST['partner_url'])) : '';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';

    $settings = [
        'code'        => $code,
        'partner_url' => $partner_url,
        'notes'       => $notes,
        'updated'     => current_time('mysql'),
    ];

    update_option('plague_harvester_affiliate_settings', $settings);
    wp_send_json_success($settings);
}

function plague_harvester_ajax_fetch_affiliate() {
    check_ajax_referer('plague_harvester_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }

    $settings = plague_harvester_get_affiliate_settings();
    $posts = get_posts([
        'post_type'      => 'post',
        'meta_key'       => '_plague_harvester_source_url',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'posts_per_page' => -1,
    ]);

    $data = [
        'settings'      => $settings,
        'total_imported' => count($posts),
        'total_banners'  => count(plague_harvester_get_banners()),
    ];

    wp_send_json_success($data);
}

function plague_harvester_hourly_tasks() {
    // Placeholder for scheduled actions. Add recurring harvesting or cleanup logic here.
}
