<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_reports_page() {
    add_submenu_page('myseo-dashboard', 'Email Reports', 'Email Reports', 'manage_options', 'myseo-email-reports', 'myseo_render_reports_page');
}

function myseo_render_reports_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    myseo_handle_report_actions();

    global $wpdb;
    $reports_table = myseo_get_table_name('email_reports');
    $rows = $wpdb->get_results("SELECT * FROM {$reports_table} ORDER BY updated_at DESC, id DESC LIMIT 100");

    echo '<div class="wrap"><h1>SEO Performance Email Reports</h1>';
    echo '<div class="myseo-card">';
    echo '<h2>Create Report Schedule</h2>';
    echo '<form method="post">';
    wp_nonce_field('myseo_save_report', 'myseo_save_report_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th><label for="myseo_report_name">Report Name</label></th><td><input type="text" id="myseo_report_name" name="myseo_report_name" class="regular-text" required /></td></tr>';
    echo '<tr><th><label for="myseo_report_email">Recipient Email</label></th><td><input type="email" id="myseo_report_email" name="myseo_report_email" class="regular-text" required /></td></tr>';
    echo '<tr><th><label for="myseo_report_frequency">Frequency in Days</label></th><td><input type="number" min="1" id="myseo_report_frequency" name="myseo_report_frequency" value="' . esc_attr((int) myseo_get_option('email_report_frequency_days', 7)) . '" class="small-text" /></td></tr>';
    echo '<tr><th><label for="myseo_report_client">Client Label</label></th><td><input type="text" id="myseo_report_client" name="myseo_report_client" value="' . esc_attr(myseo_get_option('white_label_brand', '')) . '" class="regular-text" /></td></tr>';
    echo '<tr><th><label for="myseo_report_whitelabel">White Label</label></th><td><input type="checkbox" id="myseo_report_whitelabel" name="myseo_report_whitelabel" value="1" /> Remove plugin branding in outgoing reports</td></tr>';
    echo '</tbody></table>';
    submit_button('Save Report');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Scheduled Reports</h2>';
    echo '<table class="widefat striped"><thead><tr><th>Name</th><th>Email</th><th>Frequency</th><th>White Label</th><th>Next Send</th></tr></thead><tbody>';
    if ($rows) {
        foreach ($rows as $row) {
            echo '<tr><td>' . esc_html($row->report_name) . '</td><td>' . esc_html($row->recipient_email) . '</td><td>' . esc_html($row->frequency_days) . ' days</td><td>' . ($row->include_white_label ? 'Yes' : 'No') . '</td><td>' . esc_html($row->next_send_at) . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="5">No reports scheduled yet.</td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function myseo_handle_report_actions() {
    if (!isset($_POST['myseo_save_report_nonce']) || !wp_verify_nonce($_POST['myseo_save_report_nonce'], 'myseo_save_report')) {
        return;
    }

    global $wpdb;
    $table = myseo_get_table_name('email_reports');
    $now = current_time('mysql');
    $frequency = isset($_POST['myseo_report_frequency']) ? max(1, (int) $_POST['myseo_report_frequency']) : 7;

    $wpdb->insert($table, array(
        'report_name' => isset($_POST['myseo_report_name']) ? myseo_sanitize_text($_POST['myseo_report_name']) : 'SEO Report',
        'recipient_email' => isset($_POST['myseo_report_email']) ? sanitize_email(wp_unslash($_POST['myseo_report_email'])) : '',
        'frequency_days' => $frequency,
        'last_sent_at' => null,
        'next_send_at' => gmdate('Y-m-d H:i:s', strtotime('+' . $frequency . ' days')),
        'include_white_label' => isset($_POST['myseo_report_whitelabel']) ? 1 : 0,
        'client_id' => null,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ));

    if (isset($_POST['myseo_report_client']) && $_POST['myseo_report_client'] !== '') {
        myseo_update_option('white_label_brand', myseo_sanitize_text($_POST['myseo_report_client']));
    }
}
