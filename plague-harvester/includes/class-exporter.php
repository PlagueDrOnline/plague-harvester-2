<?php
class Plague_Harvester_Exporter {
    public function export_posts($format = 'json') {
        $posts = get_posts([
            'post_type'      => 'post',
            'meta_key'       => '_plague_harvester_source_url',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => -1,
        ]);

        $data = [];
        foreach ($posts as $post) {
            $data[] = [
                'ID'          => $post->ID,
                'title'       => get_the_title($post),
                'content'     => apply_filters('the_content', $post->post_content),
                'source_url'  => esc_url(get_post_meta($post->ID, '_plague_harvester_source_url', true)),
                'imported_at' => get_the_date('c', $post),
            ];
        }

        if ($format === 'csv') {
            return $this->export_to_csv($data);
        }

        return wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function export_to_csv(array $data) {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys(reset($data)));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
