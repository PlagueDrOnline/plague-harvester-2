public function run_harvest_cycle($target_url) {
    $crawler   = new Plague_Harvester_Crawler();
    $rewriter  = new Plague_Harvester_Rewriter();
    $importer  = new Plague_Harvester_Importer();

    $video_ids = $crawler->crawl($target_url);

    foreach ($video_ids as $id) {
        // Check if exists to prevent duplicates
        if (post_exists_by_meta_key('_video_id', $id)) continue;

        $content = $rewriter->generate_unique_content($id);

        $data = [
            'title'   => $content['title'],
            'content' => $content['description'] . '<br><iframe src="https://embed.site/' . $id . '"></iframe>',
            'timestamp' => current_time('mysql')
        ];

        $post_id = $importer->import($data, ['status' => 'publish']);
        
        // Save the ID so we don't harvest it again
        if ($post_id) update_post_meta($post_id, '_video_id', $id);
    }
}