<?php
class Plague_Harvester_Proxy {
    public function fetch($url, $args = []) {
        $args = wp_parse_args($args, [
            'timeout'    => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ]);

        $response = wp_remote_get(esc_url_raw($url), $args);
        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return false;
        }

        return wp_remote_retrieve_body($response);
    }
}
