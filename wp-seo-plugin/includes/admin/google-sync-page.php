<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_render_google_sync_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['myseo_google_sync_nonce']) && wp_verify_nonce($_POST['myseo_google_sync_nonce'], 'myseo_google_sync')) {
        $provider = isset($_POST['myseo_sync_provider']) ? myseo_sanitize_text($_POST['myseo_sync_provider']) : 'google';
        $type = isset($_POST['myseo_sync_type']) ? myseo_sanitize_text($_POST['myseo_sync_type']) : 'manual_fetch';
        $country = isset($_POST['myseo_sync_country']) ? strtoupper(myseo_sanitize_text($_POST['myseo_sync_country'])) : myseo_get_option('default_country_code', 'US');
        $access_token = myseo_get_google_access_token();
        if (!$access_token) {
            myseo_log_sync_event($provider, $type, $country, 'failed', 'No valid Google access token available.');
            echo '<div class="notice notice-error"><p>No valid Google access token available.</p></div>';
        } else {
            if ($provider === 'gsc' && in_array($type, array('manual_fetch', 'country_import', 'page_metrics'), true)) {
                myseo_fetch_gsc_page_metrics($access_token, myseo_get_option('gsc_property', ''), $country);
            }
            if ($provider === 'gsc' && in_array($type, array('manual_fetch', 'winning_keywords', 'keyword_metrics'), true)) {
                myseo_fetch_gsc_keyword_metrics($access_token, myseo_get_option('gsc_property', ''), $country);
            }
            if ($provider === 'ga4' && in_array($type, array('manual_fetch', 'country_import', 'page_metrics'), true)) {
                myseo_fetch_ga4_page_metrics($access_token, myseo_get_option('ga4_property_id', ''), $country);
            }
            if ($provider === 'google' && $type === 'manual_fetch') {
                myseo_run_scheduled_google_sync();
            }
            echo '<div class="notice notice-success"><p>Manual sync executed.</p></div>';
        }
    }

    global $wpdb;
    $table = myseo_get_table_name('sync_logs');
    $logs = $wpdb->get_results("SELECT * FROM {$table} ORDER BY synced_at DESC, id DESC LIMIT 100");

    echo '<div class="wrap"><h1>Google Data Sync</h1>';
    echo '<div class="myseo-card"><h2>Manual Sync</h2><form method="post">';
    wp_nonce_field('myseo_google_sync', 'myseo_google_sync_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>Provider</th><td><select name="myseo_sync_provider"><option value="google">Google</option><option value="ga4">GA4</option><option value="gsc">Search Console</option><option value="pagespeed">PageSpeed</option><option value="adsense">AdSense</option></select></td></tr>';
    echo '<tr><th>Sync Type</th><td><select name="myseo_sync_type"><option value="manual_fetch">Manual Fetch</option><option value="country_import">Country Import</option><option value="page_metrics">Page Metrics</option><option value="keyword_metrics">Keyword Metrics</option><option value="index_status">Index Status Refresh</option><option value="winning_keywords">Winning Keywords Refresh</option></select></td></tr>';
    echo '<tr><th>Country</th><td><input type="text" name="myseo_sync_country" value="' . esc_attr(myseo_get_option('default_country_code', 'US')) . '" class="small-text" /></td></tr>';
    echo '</tbody></table>';
    submit_button('Run Sync');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Sync Logs</h2><table class="widefat striped"><thead><tr><th>Provider</th><th>Type</th><th>Country</th><th>Status</th><th>Message</th><th>Time</th></tr></thead><tbody>';
    if ($logs) {
        foreach ($logs as $log) {
            echo '<tr><td>' . esc_html($log->provider) . '</td><td>' . esc_html($log->sync_type) . '</td><td>' . esc_html($log->country_code) . '</td><td>' . esc_html($log->status) . '</td><td>' . esc_html($log->message) . '</td><td>' . esc_html($log->synced_at) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="6">No sync logs yet.</td></tr>';
    }
    echo '</tbody></table></div></div>';
}
