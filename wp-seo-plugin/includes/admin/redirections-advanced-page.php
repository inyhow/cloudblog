<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_redirections_advanced_page() {
    add_submenu_page('myseo-dashboard', 'Advanced Redirections', 'Advanced Redirections', 'manage_options', 'myseo-advanced-redirections', 'myseo_render_redirections_advanced_page');
}

function myseo_render_redirections_advanced_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    if (isset($_POST['myseo_sync_htaccess_nonce']) && wp_verify_nonce($_POST['myseo_sync_htaccess_nonce'], 'myseo_sync_htaccess')) {
        $message = myseo_sync_redirections_to_htaccess();
    }
    if (isset($_POST['myseo_convert_404_nonce']) && wp_verify_nonce($_POST['myseo_convert_404_nonce'], 'myseo_convert_404')) {
        $message = myseo_convert_top_404_to_redirects();
    }

    echo '<div class="wrap"><h1>Advanced Redirections</h1>';
    if ($message) {
        echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    }
    echo '<div class="myseo-card-grid">';
    echo '<div class="myseo-card"><h2>Sync to .htaccess</h2><form method="post">';
    wp_nonce_field('myseo_sync_htaccess', 'myseo_sync_htaccess_nonce');
    submit_button('Sync Redirections to .htaccess', 'secondary', 'submit', false);
    echo '</form></div>';
    echo '<div class="myseo-card"><h2>Convert Top 404s</h2><form method="post">';
    wp_nonce_field('myseo_convert_404', 'myseo_convert_404_nonce');
    submit_button('Create Redirects for Top 404 URLs', 'secondary', 'submit', false);
    echo '</form></div>';
    echo '</div></div>';
}

function myseo_sync_redirections_to_htaccess() {
    global $wpdb;
    $table = myseo_get_table_name('redirections');
    $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE enabled = 1 AND is_regex = 0 ORDER BY id DESC");
    $htaccess = ABSPATH . '.htaccess';
    if (!is_writable($htaccess) && file_exists($htaccess)) {
        return '.htaccess is not writable.';
    }

    $block = "\n# BEGIN MySEO\n";
    foreach ($rows as $row) {
        $source = ltrim((string) $row->source, '/');
        $target = $row->target;
        $block .= 'Redirect ' . (int) $row->status_code . ' /' . $source . ' ' . $target . "\n";
    }
    $block .= "# END MySEO\n";

    $existing = file_exists($htaccess) ? file_get_contents($htaccess) : '';
    $existing = preg_replace('/\n?# BEGIN MySEO.*?# END MySEO\n?/s', "\n", (string) $existing);
    file_put_contents($htaccess, rtrim($existing) . $block);
    return 'Redirections synced to .htaccess.';
}

function myseo_convert_top_404_to_redirects() {
    global $wpdb;
    $logs_table = myseo_get_table_name('404_logs');
    $redir_table = myseo_get_table_name('redirections');
    $rows = $wpdb->get_results("SELECT * FROM {$logs_table} ORDER BY hits DESC, last_seen DESC LIMIT 10");
    $created = 0;

    foreach ($rows as $row) {
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$redir_table} WHERE source = %s", $row->url));
        if ($exists) {
            continue;
        }
        $wpdb->insert($redir_table, array(
            'source' => $row->url,
            'target' => home_url('/'),
            'status_code' => 301,
            'is_regex' => 0,
            'hits' => 0,
            'enabled' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
        $created++;
    }

    return 'Created ' . $created . ' redirect(s) from top 404 URLs.';
}
