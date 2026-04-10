<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_render_history_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $keyword_id = isset($_GET['keyword_id']) ? (int) $_GET['keyword_id'] : 0;
    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;

    $keywords = $wpdb->get_results("SELECT id, keyword FROM " . myseo_get_table_name('keywords') . " ORDER BY keyword ASC LIMIT 200");
    $keyword_history = array();
    if ($keyword_id > 0) {
        $keyword_history = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . myseo_get_table_name('keyword_history') . " WHERE keyword_id = %d ORDER BY tracked_on DESC LIMIT 90", $keyword_id));
    }

    $post_history = array();
    if ($post_id > 0) {
        $post_history = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . myseo_get_table_name('post_metrics') . " WHERE post_id = %d ORDER BY metric_date DESC LIMIT 90", $post_id));
    }

    echo '<div class="wrap"><h1>Position History</h1>';
    echo '<form method="get" style="margin-bottom:16px;">';
    echo '<input type="hidden" name="page" value="myseo-history" />';
    echo '<select name="keyword_id"><option value="0">Select Keyword</option>';
    foreach ($keywords as $keyword) {
        echo '<option value="' . esc_attr($keyword->id) . '"' . selected($keyword_id, $keyword->id, false) . '>' . esc_html($keyword->keyword) . '</option>';
    }
    echo '</select> ';
    echo '<input type="number" min="0" name="post_id" value="' . esc_attr($post_id) . '" class="small-text" placeholder="Post ID" /> ';
    submit_button('Load History', 'secondary', '', false);
    echo '</form>';

    echo '<div class="myseo-card-grid">';
    echo '<div class="myseo-card"><h2>Keyword History</h2><table class="widefat striped"><thead><tr><th>Date</th><th>Position</th><th>Clicks</th><th>Impressions</th></tr></thead><tbody>';
    if ($keyword_history) {
        foreach ($keyword_history as $row) {
            echo '<tr><td>' . esc_html($row->tracked_on) . '</td><td>' . esc_html($row->rank_position) . '</td><td>' . esc_html($row->clicks) . '</td><td>' . esc_html($row->impressions) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="4">No keyword history loaded.</td></tr>';
    }
    echo '</tbody></table></div>';

    echo '<div class="myseo-card"><h2>Post History</h2><table class="widefat striped"><thead><tr><th>Date</th><th>Position</th><th>Clicks</th><th>PageSpeed</th></tr></thead><tbody>';
    if ($post_history) {
        foreach ($post_history as $row) {
            echo '<tr><td>' . esc_html($row->metric_date) . '</td><td>' . esc_html($row->average_position) . '</td><td>' . esc_html($row->clicks) . '</td><td>' . esc_html($row->page_speed_mobile . '/' . $row->page_speed_desktop) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="4">No post history loaded.</td></tr>';
    }
    echo '</tbody></table></div>';
    echo '</div></div>';
}
