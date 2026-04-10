<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_analytics_overview_page() {
    add_submenu_page('myseo-dashboard', 'Analytics Overview', 'Analytics Overview', 'manage_options', 'myseo-analytics-overview', 'myseo_render_analytics_overview_page');
}

function myseo_render_analytics_overview_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    myseo_handle_post_metrics_actions();

    global $wpdb;
    $table = myseo_get_table_name('post_metrics');
    $winning_posts = $wpdb->get_results("SELECT post_id, clicks, average_position FROM {$table} ORDER BY clicks DESC, average_position ASC LIMIT 5");
    $losing_posts = $wpdb->get_results("SELECT post_id, clicks, average_position FROM {$table} WHERE clicks > 0 ORDER BY clicks ASC, average_position DESC LIMIT 5");
    $recent = $wpdb->get_results("SELECT * FROM {$table} ORDER BY metric_date DESC, id DESC LIMIT 50");

    echo '<div class="wrap"><h1>Analytics Overview</h1>';
    echo '<div class="myseo-card"><h2>Add / Update Post Metrics</h2><form method="post">';
    wp_nonce_field('myseo_save_post_metrics', 'myseo_save_post_metrics_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>Post ID</th><td><input type="number" min="1" name="myseo_metric_post_id" class="small-text" required /></td></tr>';
    echo '<tr><th>Date</th><td><input type="date" name="myseo_metric_date" value="' . esc_attr(gmdate('Y-m-d')) . '" required /></td></tr>';
    echo '<tr><th>Clicks</th><td><input type="number" min="0" name="myseo_metric_clicks" class="small-text" /></td></tr>';
    echo '<tr><th>Impressions</th><td><input type="number" min="0" name="myseo_metric_impressions" class="small-text" /></td></tr>';
    echo '<tr><th>Average Position</th><td><input type="number" step="0.01" min="0" name="myseo_metric_position" class="small-text" /></td></tr>';
    echo '<tr><th>PageSpeed Mobile</th><td><input type="number" min="0" max="100" name="myseo_metric_mobile" class="small-text" /></td></tr>';
    echo '<tr><th>PageSpeed Desktop</th><td><input type="number" min="0" max="100" name="myseo_metric_desktop" class="small-text" /></td></tr>';
    echo '<tr><th>AdSense Earnings</th><td><input type="number" step="0.01" min="0" name="myseo_metric_adsense" class="small-text" /></td></tr>';
    echo '<tr><th>AI Search Clicks</th><td><input type="number" min="0" name="myseo_metric_ai_clicks" class="small-text" /></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Metrics');
    echo '</form></div>';

    echo '<div class="myseo-card-grid" style="margin-top:16px;">';
    echo myseo_render_post_trend_card('Top 5 Winning Posts', $winning_posts);
    echo myseo_render_post_trend_card('Top 5 Losing Posts', $losing_posts);
    echo '</div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Recent Metrics</h2><table class="widefat striped"><thead><tr><th>Post</th><th>Date</th><th>Clicks</th><th>Position</th><th>PageSpeed</th><th>AI Clicks</th></tr></thead><tbody>';
    if ($recent) {
        foreach ($recent as $row) {
            echo '<tr><td>' . esc_html(get_the_title((int) $row->post_id)) . '</td><td>' . esc_html($row->metric_date) . '</td><td>' . esc_html($row->clicks) . '</td><td>' . esc_html($row->average_position) . '</td><td>' . esc_html($row->page_speed_mobile . '/' . $row->page_speed_desktop) . '</td><td>' . esc_html($row->ai_search_clicks) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="6">No metrics yet.</td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function myseo_handle_post_metrics_actions() {
    if (!isset($_POST['myseo_save_post_metrics_nonce']) || !wp_verify_nonce($_POST['myseo_save_post_metrics_nonce'], 'myseo_save_post_metrics')) {
        return;
    }

    global $wpdb;
    $table = myseo_get_table_name('post_metrics');
    $post_id = isset($_POST['myseo_metric_post_id']) ? (int) $_POST['myseo_metric_post_id'] : 0;
    if ($post_id < 1) {
        return;
    }

    $date = isset($_POST['myseo_metric_date']) ? myseo_sanitize_text($_POST['myseo_metric_date']) : gmdate('Y-m-d');
    $clicks = isset($_POST['myseo_metric_clicks']) ? (int) $_POST['myseo_metric_clicks'] : 0;
    $impressions = isset($_POST['myseo_metric_impressions']) ? (int) $_POST['myseo_metric_impressions'] : 0;
    $ctr = $impressions > 0 ? round($clicks / $impressions, 4) : 0;
    $average_position = isset($_POST['myseo_metric_position']) ? (float) $_POST['myseo_metric_position'] : 0;

    $wpdb->replace($table, array(
        'post_id' => $post_id,
        'metric_date' => $date,
        'clicks' => $clicks,
        'impressions' => $impressions,
        'ctr' => $ctr,
        'average_position' => $average_position,
        'page_speed_mobile' => isset($_POST['myseo_metric_mobile']) ? (int) $_POST['myseo_metric_mobile'] : null,
        'page_speed_desktop' => isset($_POST['myseo_metric_desktop']) ? (int) $_POST['myseo_metric_desktop'] : null,
        'adsense_earnings' => isset($_POST['myseo_metric_adsense']) ? (float) $_POST['myseo_metric_adsense'] : 0,
        'ai_search_clicks' => isset($_POST['myseo_metric_ai_clicks']) ? (int) $_POST['myseo_metric_ai_clicks'] : 0,
    ));
}

function myseo_render_post_trend_card($title, $rows) {
    $html = '<div class="myseo-card"><h2>' . esc_html($title) . '</h2><ul style="margin-left:18px;list-style:disc;">';
    if ($rows) {
        foreach ($rows as $row) {
            $html .= '<li>' . esc_html(get_the_title((int) $row->post_id)) . ' (' . esc_html($row->clicks) . ' clicks, pos ' . esc_html($row->average_position) . ')</li>';
        }
    } else {
        $html .= '<li>No post performance data yet.</li>';
    }
    $html .= '</ul></div>';
    return $html;
}
