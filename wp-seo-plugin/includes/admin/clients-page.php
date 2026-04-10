<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_clients_page() {
    add_submenu_page('myseo-dashboard', 'Clients', 'Clients', 'manage_options', 'myseo-clients', 'myseo_render_clients_page');
}

function myseo_render_clients_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    myseo_handle_clients_actions();

    global $wpdb;
    $table = myseo_get_table_name('clients');
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY updated_at DESC, id DESC LIMIT 100");

    echo '<div class="wrap"><h1>Client Management</h1>';
    echo '<div class="myseo-card">';
    echo '<h2>Add Client</h2>';
    echo '<form method="post">';
    wp_nonce_field('myseo_save_client', 'myseo_save_client_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th><label for="myseo_client_name">Client Name</label></th><td><input type="text" id="myseo_client_name" name="myseo_client_name" class="regular-text" required /></td></tr>';
    echo '<tr><th><label for="myseo_client_email">Contact Email</label></th><td><input type="email" id="myseo_client_email" name="myseo_client_email" class="regular-text" /></td></tr>';
    echo '<tr><th><label for="myseo_client_site">Site URL</label></th><td><input type="url" id="myseo_client_site" name="myseo_client_site" class="regular-text" /></td></tr>';
    echo '<tr><th><label for="myseo_client_brand">White Label Brand</label></th><td><input type="text" id="myseo_client_brand" name="myseo_client_brand" class="regular-text" /></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Client');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Clients</h2>';
    echo '<table class="widefat striped"><thead><tr><th>Name</th><th>Email</th><th>Site</th><th>White Label</th></tr></thead><tbody>';
    if ($rows) {
        foreach ($rows as $row) {
            echo '<tr><td>' . esc_html($row->client_name) . '</td><td>' . esc_html($row->contact_email) . '</td><td>' . esc_html($row->site_url) . '</td><td>' . esc_html($row->white_label_brand) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="4">No clients yet.</td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function myseo_handle_clients_actions() {
    if (!isset($_POST['myseo_save_client_nonce']) || !wp_verify_nonce($_POST['myseo_save_client_nonce'], 'myseo_save_client')) {
        return;
    }

    global $wpdb;
    $table = myseo_get_table_name('clients');
    $now = current_time('mysql');
    $wpdb->insert($table, array(
        'client_name' => isset($_POST['myseo_client_name']) ? myseo_sanitize_text($_POST['myseo_client_name']) : '',
        'contact_email' => isset($_POST['myseo_client_email']) ? sanitize_email(wp_unslash($_POST['myseo_client_email'])) : '',
        'site_url' => isset($_POST['myseo_client_site']) ? esc_url_raw(wp_unslash($_POST['myseo_client_site'])) : '',
        'white_label_brand' => isset($_POST['myseo_client_brand']) ? myseo_sanitize_text($_POST['myseo_client_brand']) : '',
        'report_frequency_days' => (int) myseo_get_option('email_report_frequency_days', 7),
        'notes' => '',
        'created_at' => $now,
        'updated_at' => $now,
    ));
}
