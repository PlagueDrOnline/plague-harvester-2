<?php
class Plague_Harvester_Scheduler {
    public function schedule_harvest() {
        if (!wp_next_scheduled('plague_harvester_cron')) {
            wp_schedule_event(time(), 'hourly', 'plague_harvester_cron');
        }
    }

    public function clear_schedule() {
        $timestamp = wp_next_scheduled('plague_harvester_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'plague_harvester_cron');
        }
    }

    public function run_harvest_cycle($target_url) {
        $crawler = new Plague_Harvester_Crawler();
        $importer = new Plague_Harvester_Importer();

        $data = $crawler->crawl($target_url);
        if (!$data) {
            return false;
        }

        if (post_exists_by_meta_key('_plague_harvester_source_url', $target_url)) {
            return false;
        }

        $post_id = $importer->import($data, ['status' => 'draft']);
        if ($post_id) {
            update_post_meta($post_id, '_plague_harvester_source_url', $target_url);
            update_post_meta($post_id, '_plague_harvester_imported', 1);
        }

        return $post_id;
    }
}
