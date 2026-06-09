<?php
class Plague_Harvester_Rewriter {
    public function generate_unique_content($source_data) {
        if (is_array($source_data)) {
            $title = isset($source_data['title']) ? $source_data['title'] : 'Untitled';
            $description = isset($source_data['description']) ? $source_data['description'] : '';
        } else {
            $title = 'Untitled';
            $description = '';
        }

        return [
            'title'       => wp_strip_all_tags($title),
            'description' => wp_kses_post($description),
        ];
    }

    public function rewrite_text($text) {
        return trim(str_replace(["\r\n", "\r"], "\n", $text));
    }
}
