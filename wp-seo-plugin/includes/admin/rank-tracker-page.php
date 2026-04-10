<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_rank_tracker_page() {
    add_submenu_page('myseo-dashboard', 'Rank Tracker', 'Rank Tracker', 'manage_options', 'myseo-rank-tracker', 'myseo_render_rank_tracker_page');
}

function myseo_render_rank_tracker_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    myseo_handle_rank_tracker_actions();

    global $wpdb;
    $keywords_table = myseo_get_table_name('keywords');
    $history_table = myseo_get_table_name('keyword_history');

    $keywords = $wpdb->get_results("SELECT * FROM {$keywords_table} ORDER BY updated_at DESC, id DESC LIMIT 200");
    $winners = $wpdb->get_results("SELECT k.keyword, h.rank_position, h.previous_position FROM {$keywords_table} k INNER JOIN {$history_table} h ON h.keyword_id = k.id WHERE h.previous_position IS NOT NULL AND h.rank_position < h.previous_position ORDER BY (h.previous_position - h.rank_position) DESC LIMIT 5");
    $losers = $wpdb->get_results("SELECT k.keyword, h.rank_position, h.previous_position FROM {$keywords_table} k INNER JOIN {$history_table} h ON h.keyword_id = k.id WHERE h.previous_position IS NOT NULL AND h.rank_position > h.previous_position ORDER BY (h.rank_position - h.previous_position) DESC LIMIT 5");

    echo '<div class="wrap"><h1>Keyword Rank Tracker</h1>';

    echo '<div class="myseo-card">';
    echo '<h2>Add Tracked Keyword</h2>';
    echo '<form method="post">';
    wp_nonce_field('myseo_save_keyword', 'myseo_save_keyword_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th><label for="myseo_keyword">Keyword</label></th><td><input type="text" id="myseo_keyword" name="myseo_keyword" class="regular-text" required /></td></tr>';
    echo '<tr><th><label for="myseo_keyword_url">Target URL</label></th><td><input type="url" id="myseo_keyword_url" name="myseo_keyword_url" class="regular-text" /></td></tr>';
    echo '<tr><th><label for="myseo_keyword_country">Country</label></th><td><input type="text" id="myseo_keyword_country" name="myseo_keyword_country" value="' . esc_attr(myseo_get_option('default_country_code', 'US')) . '" class="small-text" /></td></tr>';
    echo '<tr><th><label for="myseo_keyword_group">Group</label></th><td><input type="text" id="myseo_keyword_group" name="myseo_keyword_group" class="regular-text" /></td></tr>';
    echo '<tr><th><label for="myseo_keyword_position">Current Position</label></th><td><input type="number" min="1" id="myseo_keyword_position" name="myseo_keyword_position" class="small-text" /></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Keyword');
    echo '</form>';
    echo '</div>';

    echo '<div class="myseo-card-grid" style="margin-top:16px;">';
    echo myseo_render_keyword_delta_card('Top 5 Winning Keywords', $winners);
    echo myseo_render_keyword_delta_card('Top 5 Losing Keywords', $losers);
    echo '</div>';

    echo '<div class="myseo-card" style="margin-top:16px;">';
    echo '<h2>Tracked Keywords</h2>';
    echo '<table class="widefat striped"><thead><tr><th>Keyword</th><th>URL</th><th>Country</th><th>Group</th><th>Status</th></tr></thead><tbody>';
    if ($keywords) {
        foreach ($keywords as $keyword) {
            echo '<tr>';
            echo '<td>' . esc_html($keyword->keyword) . '</td>';
            echo '<td>' . esc_html($keyword->target_url) . '</td>';
            echo '<td>' . esc_html($keyword->country_code) . '</td>';
            echo '<td>' . esc_html($keyword->group_name) . '</td>';
            echo '<td>' . ($keyword->is_active ? 'Active' : 'Paused') . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">No tracked keywords yet.</td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function myseo_handle_rank_tracker_actions() {
    if (!isset($_POST['myseo_save_keyword_nonce']) || !wp_verify_nonce($_POST['myseo_save_keyword_nonce'], 'myseo_save_keyword')) {
        return;
    }

    global $wpdb;
    $keywords_table = myseo_get_table_name('keywords');
    $history_table = myseo_get_table_name('keyword_history');
    $now = current_time('mysql');

    $keyword = isset($_POST['myseo_keyword']) ? myseo_sanitize_text($_POST['myseo_keyword']) : '';
    if ($keyword === '') {
        return;
    }

    $target_url = isset($_POST['myseo_keyword_url']) ? esc_url_raw(wp_unslash($_POST['myseo_keyword_url'])) : '';
    $country = isset($_POST['myseo_keyword_country']) ? strtoupper(myseo_sanitize_text($_POST['myseo_keyword_country'])) : myseo_get_option('default_country_code', 'US');
    $group = isset($_POST['myseo_keyword_group']) ? myseo_sanitize_text($_POST['myseo_keyword_group']) : '';
    $position = isset($_POST['myseo_keyword_position']) && $_POST['myseo_keyword_position'] !== '' ? (int) $_POST['myseo_keyword_position'] : null;

    $wpdb->insert($keywords_table, array(
        'keyword' => $keyword,
        'target_url' => $target_url,
        'country_code' => $country,
        'device_type' => 'desktop',
        'group_name' => $group,
        'notes' => '',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ));

    $keyword_id = (int) $wpdb->insert_id;
    if ($keyword_id && $position) {
        $wpdb->insert($history_table, array(
            'keyword_id' => $keyword_id,
            'tracked_on' => gmdate('Y-m-d', current_time('timestamp')),
            'rank_position' => $position,
            'previous_position' => null,
            'clicks' => 0,
            'impressions' => 0,
            'ctr' => 0,
            'average_position' => $position,
            'source' => 'manual',
        ));
    }
}

function myseo_render_keyword_delta_card($title, $rows) {
    $html = '<div class="myseo-card"><h2>' . esc_html($title) . '</h2><ul style="margin-left:18px;list-style:disc;">';
    if ($rows) {
        foreach ($rows as $row) {
            $html .= '<li>' . esc_html($row->keyword) . ' (' . esc_html($row->previous_position) . ' to ' . esc_html($row->rank_position) . ')</li>';
        }
    } else {
        $html .= '<li>No trend data yet.</li>';
    }
    $html .= '</ul></div>';
    return $html;
}
