<?php
class Plague_Harvester_Crawler {
    public function crawl($url) {
        $url = esc_url_raw($url);
        if (empty($url)) {
            return false;
        }

        $html = $this->fetch_url($url);
        if (!$html) {
            return false;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $title_element = $dom->getElementsByTagName('title')->item(0);
        $title = $title_element ? trim($title_element->nodeValue) : '';

        $content_node = $dom->getElementById('content');
        if (!$content_node) {
            $content_node = $dom->getElementsByTagName('body')->item(0);
        }

        $content_html = $content_node ? $dom->saveHTML($content_node) : '';
        $content = $this->clean_content($content_html);

        if (empty($content) && empty($title)) {
            return false;
        }

        return [
            'title'     => $title,
            'content'   => $content,
            'url'       => $url,
            'timestamp' => current_time('mysql'),
        ];
    }

    private function fetch_url($url) {
        $args = [
            'timeout'    => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ];

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return false;
        }

        return wp_remote_retrieve_body($response);
    }

    private function clean_content($content) {
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);

        $allowed_tags = [
            'p' => [],
            'a' => ['href' => [], 'title' => [], 'target' => [], 'rel' => []],
            'img' => ['src' => [], 'alt' => [], 'title' => [], 'width' => [], 'height' => []],
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'br' => [],
            'strong' => [],
            'em' => [],
            'blockquote' => [],
            'div' => [],
            'span' => [],
        ];

        return trim(wp_kses($content, $allowed_tags));
    }
}
