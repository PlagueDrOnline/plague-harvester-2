<?php
class Plague_Harvester_Crawler {
    public function crawl($url) {
        // Use Simple HTML DOM or Symfony DomCrawler
        // For now, we'll use file_get_contents with timeout
        $html = $this->fetch_url($url);
        if (!$html) return false;

        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $title = $dom->getElementsByTagName('title')->item(0)->nodeValue;
        $content = $dom->getElementById('content') ?: $dom->getElementsByTagName('body')->item(0);

        // Clean content
        $content = $this->clean_content($content->nodeValue);

        return [
            'title' => $title,
            'content' => $content,
            'url' => $url,
            'timestamp' => current_time('mysql')
        ];
    }

    private function fetch_url($url) {
        $args = [
            'timeout' => 30,
            'sslverify' => false, // For testing only
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ];
        $response = wp_remote_get($url, $args);
        return wp_remote_retrieve_body($response);
    }

    private function clean_content($content) {
        // Remove ads, scripts, styles
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
        $content = strip_tags($content, '<p><a><img><h1><h2><h3><ul><ol><li>');
        return trim($content);
    }
}