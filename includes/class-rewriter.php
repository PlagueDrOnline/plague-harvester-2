<?php
class Plague_Harvester_Rewriter {
    public function generate_unique_content($video_id) {
        // Retrieve key from your WordPress settings (ensure you have a settings field)
        $api_key = get_option('plague_openai_api_key'); 

        if (empty($api_key)) return ['title' => 'Video ' . $video_id, 'description' => 'Video content.'];

        $prompt = "Create a unique, catchy YouTube-style title and a 150-word SEO description for a video with the internal ID {$video_id}. Return ONLY a JSON object with keys 'title' and 'description'.";

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 45,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'gpt-4o',
                'messages' => [['role' => 'user', 'content' => $prompt]]
            ])
        ]);

        if (is_wp_error($response)) return ['title' => 'Video ' . $video_id, 'description' => 'Check video content.'];

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return json_decode($body['choices'][0]['message']['content'], true);
    }
}