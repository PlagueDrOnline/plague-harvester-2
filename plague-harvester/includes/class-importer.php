<?php
class Plague_Harvester_Importer {
    public function import($data, $args = []) {
        // $data = ['title', 'content', 'url', 'timestamp']
        $post_id = wp_insert_post([
            'post_title'   => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['content']),
            'post_status'  => isset($args['status']) ? $args['status'] : 'draft',
            'post_date'    => isset($data['timestamp']) ? $data['timestamp'] : current_time('mysql'),
            'post_type'    => 'post',
        ]);

        if (is_wp_error($post_id)) {
            return false;
        }

        // Set featured image if URL exists
        if (!empty($data['featured_image_url'])) {
            $this->set_featured_image($post_id, $data['featured_image_url']);
        }

        // Set categories and tags
        if (!empty($args['categories'])) {
            wp_set_post_terms($post_id, $args['categories'], 'category');
        }
        if (!empty($args['tags'])) {
            wp_set_post_terms($post_id, $args['tags'], 'post_tag');
        }

        return $post_id;
    }

    private function set_featured_image($post_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) return false;

        $file_array = [
            'name'     => basename($image_url),
            'tmp_name' => $tmp
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
        @unlink($tmp);
    }
}