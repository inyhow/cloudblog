<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_404_monitor() {
    add_action('template_redirect', 'myseo_log_404', 1);
}

function myseo_register_monitor_page() {
    add_options_page(
        'MySEO 404 Monitor',
        'MySEO 404 Monitor',
        'manage_options',
        'myseo-404-monitor',
        'myseo_render_monitor_page'
    );
}

function myseo_render_monitor_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    myseo_handle_monitor_clear();

    global $wpdb;
    $table = $wpdb->prefix . 'myseo_404_logs';
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY last_seen DESC LIMIT 200");

    $clear_url = wp_nonce_url(
        add_query_arg(array('page' => 'myseo-404-monitor', 'clear' => 1), admin_url('admin.php')),
        'myseo_clear_404'
    );

    echo '<div class="wrap">';
    echo '<h1>MySEO 404 Monitor</h1>';
    echo '<p><a class="button button-secondary" href="' . esc_url($clear_url) . '">Clear Logs</a></p>';

    echo '<table class="widefat striped">';
    echo '<thead><tr><th>ID</th><th>URL</th><th>Hits</th><th>Last Seen</th><th>Referrer</th></tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->url) . '</td>';
            echo '<td>' . esc_html($row->hits) . '</td>';
            echo '<td>' . esc_html($row->last_seen) . '</td>';
            echo '<td>' . esc_html($row->referrer) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5">No 404 logs found.</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}

function myseo_handle_monitor_clear() {
    if (!myseo_module_enabled('monitor')) {
        return;
    }
    if (!isset($_GET['clear'])) {
        return;
    }
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'myseo_clear_404')) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'myseo_404_logs';
    $wpdb->query("TRUNCATE TABLE {$table}");
}

function myseo_log_404() {
    if (!myseo_module_enabled('monitor')) {
        return;
    }
    if (!is_404()) {
        return;
    }

    $url = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
    $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? myseo_sanitize_text($_SERVER['HTTP_USER_AGENT']) : '';
    $ip = isset($_SERVER['REMOTE_ADDR']) ? myseo_sanitize_text($_SERVER['REMOTE_ADDR']) : '';

    global $wpdb;
    $table = $wpdb->prefix . 'myseo_404_logs';
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE url = %s LIMIT 1", $url));

    if ($existing) {
        $wpdb->update(
            $table,
            array(
                'hits' => (int) $existing->hits + 1,
                'last_seen' => current_time('mysql'),
                'referrer' => $referrer,
                'user_agent' => $user_agent,
                'ip_address' => $ip,
            ),
            array('id' => $existing->id)
        );
    } else {
        $wpdb->insert(
            $table,
            array(
                'url' => $url,
                'referrer' => $referrer,
                'user_agent' => $user_agent,
                'ip_address' => $ip,
                'hits' => 1,
                'last_seen' => current_time('mysql'),
                'created_at' => current_time('mysql'),
            )
        );
    }
}
