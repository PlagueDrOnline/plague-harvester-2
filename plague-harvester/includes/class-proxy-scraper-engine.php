<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PlagueDr_Proxy_Scraper_Engine {

    private static $instance = null;
    private $default_sources = array(
        'https://api.proxyscrape.com/v2/?request=getproxies&protocol=http&timeout=10000&country=all&ssl=all&anonymity=all',
        'https://www.proxy-list.download/api/v1/get?type=http&anon=elite',
        'https://raw.githubusercontent.com/TheSpeedX/PROXY-List/master/http.txt'
    );

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_active_doctrine() {
        return get_option( 'plaguedr_crawler_doctrine', 'sentinel' );
    }

    public function get_proxy_pool() {
        $pool = get_transient( 'pd_elite_proxy_pool' );
        if ( ! is_array( $pool ) ) {
            return array();
        }
        return $pool;
    }

    public function store_proxy_pool( array $pool ) {
        set_transient( 'pd_elite_proxy_pool', $pool, 12 * HOUR_IN_SECONDS );
    }

    public function execute_proxy_request( $url ) {
        $doctrine = $this->get_active_doctrine();

        if ( $doctrine === 'sentinel' && strpos( $url, home_url() ) === false ) {
            return array(
                'success' => false,
                'message' => 'Sentinel Blocked: External scraping is disabled in Sentinel mode.'
            );
        }

        $pool = $this->get_proxy_pool();
        if ( empty( $pool ) ) {
            return $this->fetch_url( $url );
        }

        shuffle( $pool );
        foreach ( $pool as $node ) {
            if ( empty( $node['proxy'] ) ) {
                continue;
            }

            $result = $this->fetch_url( $url, $node['proxy'] );
            if ( $result['success'] ) {
                return $result;
            }
        }

        return $this->fetch_url( $url );
    }

    private function fetch_url( $url, $proxy = '' ) {
        if ( ! function_exists( 'curl_init' ) ) {
            $response = wp_remote_get( $url, array( 'timeout' => 15, 'redirection' => 5, 'httpversion' => '1.1' ) );
            if ( is_wp_error( $response ) ) {
                return array( 'success' => false, 'message' => $response->get_error_message() );
            }
            return array( 'success' => true, 'body' => wp_remote_retrieve_body( $response ) );
        }

        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 8 );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'PlagueDr Crawler/1.0' );

        if ( ! empty( $proxy ) ) {
            curl_setopt( $ch, CURLOPT_PROXY, $proxy );
        }

        $body = curl_exec( $ch );
        $error = curl_error( $ch );
        $errno = curl_errno( $ch );
        curl_close( $ch );

        if ( $errno || $body === false ) {
            return array( 'success' => false, 'message' => $error ?: 'Remote fetch failed' );
        }

        return array( 'success' => true, 'body' => $body );
    }

    public function perform_harvest( $sources = array() ) {
        if ( empty( $sources ) ) {
            $sources = $this->default_sources;
        }

        $collected = array();
        $logs = array();
        foreach ( $sources as $source ) {
            $source = trim( $source );
            if ( empty( $source ) ) {
                continue;
            }

            $response = wp_remote_get( $source, array( 'timeout' => 20, 'redirection' => 5 ) );
            if ( is_wp_error( $response ) ) {
                $logs[] = sprintf( 'Harvest source failed: %s (%s)', $response->get_error_message(), esc_url( $source ) );
                continue;
            }

            $body = wp_remote_retrieve_body( $response );
            preg_match_all( '/(\d{1,3}(?:\.\d{1,3}){3}:\d{2,5})/', $body, $matches );
            $count = 0;
            if ( ! empty( $matches[1] ) ) {
                foreach ( $matches[1] as $proxy ) {
                    $collected[ trim( $proxy ) ] = array(
                        'proxy'    => trim( $proxy ),
                        'speed'    => 'n/a',
                        'location' => 'discovered'
                    );
                }
                $count = count( $matches[1] );
            }
            $logs[] = sprintf( 'Harvest source %s returned %d proxies.', esc_url( $source ), $count );
        }

        $pool = array_values( $collected );
        if ( ! empty( $pool ) ) {
            $this->store_proxy_pool( $pool );
            $logs[] = sprintf( 'Saved %d unique proxies to the pool.', count( $pool ) );
            return array( 'success' => true, 'count' => count( $pool ), 'message' => 'Proxy pool refreshed successfully.', 'logs' => $logs );
        }

        $logs[] = 'No valid proxy nodes discovered from harvest sources.';
        return array( 'success' => false, 'count' => 0, 'message' => 'No valid proxy nodes discovered from harvest sources.', 'logs' => $logs );
    }

    public function test_proxy_pool( $limit = 5 ) {
        $pool = $this->get_proxy_pool();
        if ( empty( $pool ) ) {
            return array(
                'success' => false,
                'message' => 'Proxy pool is empty. Harvest some proxies first.',
                'tested'  => 0,
                'live'    => 0,
                'logs'    => array( 'Proxy pool is empty.' )
            );
        }

        $tested = 0;
        $live = 0;
        $logs = array();
        $sample = array_slice( $pool, 0, max( 1, min( $limit, count( $pool ) ) ) );

        foreach ( $sample as $node ) {
            $tested++;
            $result = $this->fetch_url( 'http://example.com', $node['proxy'] );
            if ( $result['success'] ) {
                $live++;
                $logs[] = sprintf( 'Proxy %s responded successfully.', esc_html( $node['proxy'] ) );
            } else {
                $logs[] = sprintf( 'Proxy %s failed: %s', esc_html( $node['proxy'] ), $result['message'] );
            }
        }

        if ( $live > 0 ) {
            $logs[] = sprintf( 'Proxy test complete. %d of %d proxies responded successfully.', $live, $tested );
            return array(
                'success' => true,
                'message' => sprintf( 'Proxy test complete. %d of %d proxies responded successfully.', $live, $tested ),
                'tested'  => $tested,
                'live'    => $live,
                'logs'    => $logs
            );
        }

        $logs[] = 'Proxy test complete. No proxies responded successfully. Your harvested pool may be invalid or the source endpoints are unreachable.';
        return array(
            'success' => false,
            'message' => 'Proxy test complete. No proxies responded successfully. Your harvested pool may be invalid or the source endpoints are unreachable.',
            'tested'  => $tested,
            'live'    => $live,
            'logs'    => $logs
        );
    }

    public function crawl_url( $start_url, $max_depth = 1, $max_pages = 10 ) {
        $start_url = esc_url_raw( $start_url );
        if ( empty( $start_url ) || ! filter_var( $start_url, FILTER_VALIDATE_URL ) ) {
            return array( 'success' => false, 'message' => 'Invalid crawl target URL.', 'logs' => array( 'Invalid URL provided.' ) );
        }

        $domain = parse_url( $start_url, PHP_URL_HOST );
        $visited = array();
        $queue   = array( array( 'url' => $start_url, 'depth' => 0 ) );
        $results = array();
        $logs = array();

        $logs[] = sprintf( 'Starting crawl on %s with depth %d and max pages %d.', esc_url( $start_url ), $max_depth, $max_pages );

        while ( ! empty( $queue ) && count( $visited ) < $max_pages ) {
            $item = array_shift( $queue );
            $url  = $item['url'];

            if ( isset( $visited[ $url ] ) || $item['depth'] > $max_depth ) {
                continue;
            }

            $response = $this->execute_proxy_request( $url );
            $status = $response['success'] ? 'Fetched' : $response['message'];
            $logs[] = sprintf( 'Crawled %s at depth %d: %s', esc_url( $url ), $item['depth'], $status );

            $page = array(
                'url'   => $url,
                'depth' => $item['depth'],
                'links' => 0,
                'success' => $response['success'],
                'message' => $status
            );

            if ( $response['success'] ) {
                $links = $this->extract_links( $response['body'], $url, $domain );
                $page['links'] = count( $links );

                foreach ( $links as $link ) {
                    if ( ! isset( $visited[ $link ] ) && count( $visited ) + count( $queue ) < $max_pages ) {
                        $queue[] = array( 'url' => $link, 'depth' => $item['depth'] + 1 );
                    }
                }
            }

            $visited[ $url ] = true;
            $results[] = $page;
        }

        $logs[] = sprintf( 'Crawl finished with %d pages processed.', count( $results ) );

        return array(
            'success' => true,
            'count'   => count( $results ),
            'pages'   => $results,
            'domain'  => $domain,
            'logs'    => $logs
        );
    }

    private function extract_links( $html, $base_url, $domain ) {
        $links = array();
        if ( empty( $html ) ) {
            return $links;
        }

        if ( preg_match_all( '/<a[^>]+href=["\']?([^"\' >]+)["\']?/i', $html, $matches ) ) {
            foreach ( $matches[1] as $href ) {
                $href = trim( $href );
                if ( empty( $href ) || strpos( $href, 'javascript:' ) === 0 || strpos( $href, 'mailto:' ) === 0 || strpos( $href, '#' ) === 0 ) {
                    continue;
                }

                if ( strpos( $href, '//' ) === 0 ) {
                    $href = parse_url( $base_url, PHP_URL_SCHEME ) . ':' . $href;
                } elseif ( strpos( $href, '/' ) === 0 ) {
                    $href = parse_url( $base_url, PHP_URL_SCHEME ) . '://' . parse_url( $base_url, PHP_URL_HOST ) . $href;
                } elseif ( ! preg_match( '#^https?://#i', $href ) ) {
                    $base_path = trailingslashit( dirname( parse_url( $base_url, PHP_URL_PATH ) ) );
                    $href = parse_url( $base_url, PHP_URL_SCHEME ) . '://' . parse_url( $base_url, PHP_URL_HOST ) . $base_path . $href;
                }

                if ( filter_var( $href, FILTER_VALIDATE_URL ) && parse_url( $href, PHP_URL_HOST ) === $domain ) {
                    $links[ $href ] = $href;
                }
            }
        }

        return array_values( $links );
    }
}
