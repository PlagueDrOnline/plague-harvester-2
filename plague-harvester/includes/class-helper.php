<?php
if (!function_exists('post_exists_by_meta_key')) {
    function post_exists_by_meta_key($key, $value) {
        $posts = get_posts([
            'meta_key'       => $key,
            'meta_value'     => $value,
            'post_type'      => 'post',
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);
        return !empty($posts);
    }
}
