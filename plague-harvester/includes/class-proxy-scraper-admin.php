<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'plaguedr_crawler_menu' );
add_action( 'admin_init', 'plaguedr_handle_crawler_post_actions' );
add_action( 'admin_enqueue_scripts', 'plaguedr_crawler_enqueue_assets' );

function plaguedr_crawler_menu() {
    add_menu_page(
        'PlagueDr Crawler Lab',
        'Crawler Lab',
        'manage_options',
        'plaguedr-crawler',
        'plaguedr_render_crawler_lab',
        'dashicons-networking',
        110
    );
}

function plaguedr_render_crawler_lab() {
    $current_doctrine = get_option( 'plaguedr_crawler_doctrine', 'sentinel' );
    $saved_spots = get_option( 'plaguedr_crawler_spots', array() );
    $pool = get_transient( 'pd_elite_proxy_pool' );
    $harvest_report = get_transient( 'plaguedr_crawler_harvest_report' );
    $crawl_report = get_transient( 'plaguedr_crawler_last_report' );
    $test_report = get_transient( 'plaguedr_crawler_test_report' );

    if ( $harvest_report ) {
        delete_transient( 'plaguedr_crawler_harvest_report' );
    }

    if ( $crawl_report ) {
        delete_transient( 'plaguedr_crawler_last_report' );
    }

    if ( $test_report ) {
        delete_transient( 'plaguedr_crawler_test_report' );
    }
    ?>
    <div class="wrap">
        <h1>Crawler Lab // Operations Dashboard</h1>

        <?php if ( $harvest_report ) : ?>
            <div class="notice <?php echo $harvest_report['success'] ? 'notice-success' : 'notice-error'; ?>">
                <p><?php echo esc_html( $harvest_report['message'] ); ?></p>
                <?php if ( isset( $harvest_report['count'] ) ) : ?>
                    <p><?php echo esc_html( $harvest_report['count'] ); ?> proxy nodes imported.</p>
                <?php endif; ?>
                <?php if ( ! empty( $harvest_report['logs'] ) ) : ?>
                    <p><strong>Harvest progress:</strong></p>
                    <ul>
                        <?php foreach ( $harvest_report['logs'] as $log_line ) : ?>
                            <li><?php echo esc_html( $log_line ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="notice <?php echo empty( $pool ) ? 'notice-warning' : 'notice-success'; ?>">
            <?php if ( empty( $pool ) ) : ?>
                <p><strong>Proxy Pool Status:</strong> No active proxies available.</p>
                <p>If harvesting returned zero proxies or the pool expired, use <strong>Refresh Proxy Pool</strong> and then <strong>Test Proxy Pool</strong> to verify functionality.</p>
            <?php else : ?>
                <p><strong>Proxy Pool Status:</strong> <?php echo esc_html( count( $pool ) ); ?> active node<?php echo count( $pool ) === 1 ? '' : 's'; ?> in the pool.</p>
                <p>Use <strong>Test Proxy Pool</strong> to verify node reachability and identify failing proxies.</p>
            <?php endif; ?>
        </div>

        <?php if ( $test_report ) : ?>
            <div class="notice <?php echo $test_report['success'] ? 'notice-success' : 'notice-warning'; ?>">
                <p><?php echo esc_html( $test_report['message'] ); ?></p>
                <?php if ( isset( $test_report['tested'] ) ) : ?>
                    <p><?php echo esc_html( $test_report['tested'] ); ?> proxies tested, <?php echo esc_html( $test_report['live'] ); ?> responsive.</p>
                <?php endif; ?>
                <?php if ( ! empty( $test_report['logs'] ) ) : ?>
                    <p><strong>Test progress:</strong></p>
                    <ul>
                        <?php foreach ( $test_report['logs'] as $log_line ) : ?>
                            <li><?php echo esc_html( $log_line ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ( $crawl_report ) : ?>
            <div class="notice notice-info">
                <p>Crawl completed: <?php echo esc_html( $crawl_report['count'] ); ?> pages scanned.</p>
            </div>
            <table class="widefat" style="margin-bottom:20px;">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Depth</th>
                        <th>Links</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $crawl_report['pages'] as $page ) : ?>
                        <tr>
                            <td><?php echo esc_url( $page['url'] ); ?></td>
                            <td><?php echo esc_html( $page['depth'] ); ?></td>
                            <td><?php echo esc_html( $page['links'] ); ?></td>
                            <td><?php echo esc_html( $page['message'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <form method="POST">
            <?php wp_nonce_field( 'pd_save_doctrine_action' ); ?>
            <h3>Select Operational Doctrine</h3>
            <select name="pd_crawler_doctrine">
                <option value="sentinel" <?php selected( $current_doctrine, 'sentinel' ); ?>>Sentinel Health Check (Default)</option>
                <option value="aggregator" <?php selected( $current_doctrine, 'aggregator' ); ?>>Content Aggregator Mode</option>
                <option value="intelligence" <?php selected( $current_doctrine, 'intelligence' ); ?>>Intelligence Gathering</option>
            </select>
            <input type="submit" name="pd_save_doctrine" class="button" value="Update Doctrine">
        </form>

        <hr />

        <form method="POST">
            <?php wp_nonce_field( 'pd_save_spots_action' ); ?>
            <h3>Saved Scrape Spots</h3>
            <p>Add custom scrape spots for the crawler. One URL per line.</p>
            <textarea name="pd_spots_list" placeholder="https://example.com/page1\nhttps://example.com/page2" style="width:100%; height:120px;"><?php echo esc_textarea( implode( "\n", $saved_spots ) ); ?></textarea>
            <p><input type="submit" name="pd_save_spots" class="button button-primary" value="Save Scrape Spots" /></p>
        </form>

        <?php if ( ! empty( $saved_spots ) ) : ?>
            <div class="notice notice-info">
                <p><strong>Saved Scrape Spots:</strong></p>
                <ul>
                    <?php foreach ( $saved_spots as $spot ) : ?>
                        <li><?php echo esc_url( $spot ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <hr />

        <form method="POST">
            <?php wp_nonce_field( 'pd_harvest_action', 'pd_nonce' ); ?>
            <h3>Harvest Proxy Pool</h3>
            <p>Use built-in open proxy sources or add your own list of provider URLs below.</p>
            <textarea name="pd_custom_sources" placeholder="One source URL per line..." style="width:100%; height:100px;"></textarea>
            <p>
                <input type="submit" name="pd_manual_harvest" class="button button-primary" value="Refresh Proxy Pool" />
                <input type="submit" name="pd_test_pool" class="button button-secondary" value="Test Proxy Pool" />
            </p>
        </form>

        <hr />

        <form method="POST">
            <?php wp_nonce_field( 'pd_crawl_action', 'pd_crawl_nonce' ); ?>
            <h3>Execute Premium Crawl</h3>
            <p>Scan a target URL using the current doctrine and proxy pool. Internal links only are followed up to the depth limit.</p>
            <input type="text" name="pd_crawl_target" value="" placeholder="https://example.com" style="width:100%; max-width:500px;" />
            <p style="margin:10px 0 0 0;">
                Depth: <input type="number" name="pd_crawl_depth" value="2" min="1" max="4" style="width:72px;" />
                Maximum pages: <input type="number" name="pd_crawl_limit" value="10" min="1" max="25" style="width:72px; margin-left:10px;" />
            </p>
            <input type="submit" name="pd_manual_crawl" class="button button-secondary" value="Run Crawl" />
        </form>

        <hr />

        <table class="widefat" style="margin-top:20px; background:#1a1a1a; color:#fff;">
            <thead>
                <tr>
                    <th style="padding:12px 10px;">PROXY</th>
                    <th style="padding:12px 10px;">SPEED LATENCY</th>
                    <th style="padding:12px 10px;">LOCATION STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $pool ) && is_array( $pool ) ) : foreach ( $pool as $node ) : ?>
                    <tr>
                        <td style="padding:12px 10px;"><?php echo esc_html( $node['proxy'] ); ?></td>
                        <td style="padding:12px 10px;"><?php echo esc_html( $node['speed'] ); ?></td>
                        <td style="padding:12px 10px;"><?php echo esc_html( $node['location'] ); ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="3" style="padding:20px; text-align:center;">No active nodes found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ( empty( $pool ) ) : ?>
            <div class="notice notice-warning" style="margin-top:20px;">
                <p>No active proxies are currently stored in the pool.</p>
                <p>This can mean one of three things:</p>
                <ul>
                    <li>The harvest returned zero proxies.</li>
                    <li>The proxy pool expired or was cleared.</li>
                    <li>The proxy harvester cannot reach the source endpoints.</li>
                </ul>
                <p>Use <strong>Refresh Proxy Pool</strong> and then <strong>Test Proxy Pool</strong> to diagnose the failure path.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function plaguedr_handle_crawler_post_actions() {
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'plaguedr-crawler' ) {
        return;
    }

    if ( isset( $_POST['pd_save_doctrine'] ) ) {
        check_admin_referer( 'pd_save_doctrine_action' );
        update_option( 'plaguedr_crawler_doctrine', sanitize_text_field( $_POST['pd_crawler_doctrine'] ) );
        wp_safe_redirect( admin_url( 'admin.php?page=plaguedr-crawler&updated=1' ) );
        exit;
    }

    if ( isset( $_POST['pd_manual_harvest'] ) || isset( $_POST['pd_test_pool'] ) ) {
        check_admin_referer( 'pd_harvest_action', 'pd_nonce' );
        if ( function_exists( 'set_time_limit' ) ) {
            set_time_limit( 300 );
        }

        $engine = PlagueDr_Proxy_Scraper_Engine::instance();
        $sources = array();
        if ( ! empty( $_POST['pd_custom_sources'] ) ) {
            $sources = array_filter( array_map( 'trim', explode( "\n", sanitize_textarea_field( $_POST['pd_custom_sources'] ) ) ) );
        }

        if ( isset( $_POST['pd_manual_harvest'] ) ) {
            $report = $engine->perform_harvest( $sources );
            set_transient( 'plaguedr_crawler_harvest_report', $report, 300 );
        } else {
            $report = $engine->test_proxy_pool( 5 );
            set_transient( 'plaguedr_crawler_test_report', $report, 300 );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=plaguedr-crawler' ) );
        exit;
    }

    if ( isset( $_POST['pd_save_spots'] ) ) {
        check_admin_referer( 'pd_save_spots_action' );
        $spots = array();
        if ( ! empty( $_POST['pd_spots_list'] ) ) {
            $lines = explode( "\n", sanitize_textarea_field( $_POST['pd_spots_list'] ) );
            foreach ( $lines as $line ) {
                $line = trim( $line );
                if ( filter_var( $line, FILTER_VALIDATE_URL ) ) {
                    $spots[] = esc_url_raw( $line );
                }
            }
        }
        update_option( 'plaguedr_crawler_spots', $spots );
        wp_safe_redirect( admin_url( 'admin.php?page=plaguedr-crawler&updated=1' ) );
        exit;
    }

    if ( isset( $_POST['pd_manual_crawl'] ) ) {
        check_admin_referer( 'pd_crawl_action', 'pd_crawl_nonce' );
        if ( function_exists( 'set_time_limit' ) ) {
            set_time_limit( 300 );
        }

        $target = '';
        if ( ! empty( $_POST['pd_saved_spot'] ) && filter_var( $_POST['pd_saved_spot'], FILTER_VALIDATE_URL ) ) {
            $target = esc_url_raw( $_POST['pd_saved_spot'] );
        }
        if ( empty( $target ) ) {
            $target = isset( $_POST['pd_crawl_target'] ) ? esc_url_raw( $_POST['pd_crawl_target'] ) : '';
        }

        $depth = isset( $_POST['pd_crawl_depth'] ) ? max( 1, min( 4, intval( $_POST['pd_crawl_depth'] ) ) ) : 2;
        $limit = isset( $_POST['pd_crawl_limit'] ) ? max( 1, min( 25, intval( $_POST['pd_crawl_limit'] ) ) ) : 10;

        $engine = PlagueDr_Proxy_Scraper_Engine::instance();
        $report = $engine->crawl_url( $target, $depth, $limit );
        set_transient( 'plaguedr_crawler_last_report', $report, 300 );

        wp_safe_redirect( admin_url( 'admin.php?page=plaguedr-crawler' ) );
        exit;
    }
}

function plaguedr_crawler_enqueue_assets( $hook ) {
    if ( $hook !== 'toplevel_page_plaguedr-crawler' ) {
        return;
    }

    wp_enqueue_style( 'plaguedr-crawler-admin', PLAGUE_HARVESTER_URL . 'assets/css/admin.css', array(), PLAGUE_HARVESTER_VERSION );
}
