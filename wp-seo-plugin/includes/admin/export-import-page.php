<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_export_import_page() {
    add_options_page(
        'MySEO Export/Import',
        'MySEO Export/Import',
        'manage_options',
        'myseo-export-import',
        'myseo_render_export_import_page'
    );
}

function myseo_render_export_import_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    if (isset($_POST['myseo_export'])) {
        myseo_stream_export();
        return;
    }

    if (isset($_POST['myseo_import_nonce']) && wp_verify_nonce($_POST['myseo_import_nonce'], 'myseo_export_import')) {
        $message = myseo_handle_import_upload();
    }

    echo '<div class="wrap">';
    echo '<h1>MySEO Export / Import</h1>';

    if ($message) {
        echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    }

    echo '<h2>Export</h2>';
    echo '<form method="post">';
    submit_button('Download JSON', 'secondary', 'myseo_export', false);
    echo '</form>';

    echo '<h2>Import</h2>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('myseo_export_import', 'myseo_import_nonce');
    echo '<input type="file" name="myseo_import_file" accept="application/json" required />';
    submit_button('Import JSON');
    echo '</form>';
    echo '</div>';
}

function myseo_stream_export() {
    $payload = array(
        'settings' => get_option('myseo_settings', array()),
        'modules' => get_option('myseo_modules', array()),
        'clients' => myseo_export_clients(),
        'reports' => myseo_export_reports(),
        'version' => MYSEO_PLUGIN_VERSION,
        'exported_at' => current_time('mysql'),
    );

    $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename=myseo-export.json');
    echo $json;
    exit;
}

function myseo_handle_import_upload() {
    if (!isset($_FILES['myseo_import_file']) || $_FILES['myseo_import_file']['error'] !== UPLOAD_ERR_OK) {
        return 'Upload failed.';
    }

    $content = file_get_contents($_FILES['myseo_import_file']['tmp_name']);
    if (!$content) {
        return 'Empty file.';
    }

    $data = json_decode($content, true);
    if (!is_array($data)) {
        return 'Invalid JSON.';
    }

    if (isset($data['settings']) && is_array($data['settings'])) {
        update_option('myseo_settings', $data['settings']);
    }
    if (isset($data['modules']) && is_array($data['modules'])) {
        update_option('myseo_modules', myseo_sanitize_modules($data['modules']));
    }
    if (isset($data['clients']) && is_array($data['clients'])) {
        myseo_import_clients($data['clients']);
    }
    if (isset($data['reports']) && is_array($data['reports'])) {
        myseo_import_reports($data['reports']);
    }

    return 'Import completed.';
}

function myseo_export_clients() {
    global $wpdb;
    $table = myseo_get_table_name('clients');
    return $wpdb->get_results("SELECT client_name, contact_email, site_url, white_label_brand, report_frequency_days, notes FROM {$table}", ARRAY_A);
}

function myseo_export_reports() {
    global $wpdb;
    $table = myseo_get_table_name('email_reports');
    return $wpdb->get_results("SELECT report_name, recipient_email, frequency_days, include_white_label, is_active FROM {$table}", ARRAY_A);
}

function myseo_import_clients($rows) {
    global $wpdb;
    $table = myseo_get_table_name('clients');
    foreach ($rows as $row) {
        if (empty($row['client_name'])) {
            continue;
        }
        $wpdb->insert($table, array(
            'client_name' => myseo_sanitize_text($row['client_name']),
            'contact_email' => isset($row['contact_email']) ? sanitize_email($row['contact_email']) : '',
            'site_url' => isset($row['site_url']) ? esc_url_raw($row['site_url']) : '',
            'white_label_brand' => isset($row['white_label_brand']) ? myseo_sanitize_text($row['white_label_brand']) : '',
            'report_frequency_days' => isset($row['report_frequency_days']) ? (int) $row['report_frequency_days'] : 7,
            'notes' => isset($row['notes']) ? sanitize_textarea_field($row['notes']) : '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }
}

function myseo_import_reports($rows) {
    global $wpdb;
    $table = myseo_get_table_name('email_reports');
    foreach ($rows as $row) {
        if (empty($row['report_name'])) {
            continue;
        }
        $wpdb->insert($table, array(
            'report_name' => myseo_sanitize_text($row['report_name']),
            'recipient_email' => isset($row['recipient_email']) ? sanitize_email($row['recipient_email']) : '',
            'frequency_days' => isset($row['frequency_days']) ? (int) $row['frequency_days'] : 7,
            'last_sent_at' => null,
            'next_send_at' => gmdate('Y-m-d H:i:s', strtotime('+' . ((int) ($row['frequency_days'] ?? 7)) . ' days')),
            'include_white_label' => !empty($row['include_white_label']) ? 1 : 0,
            'client_id' => null,
            'is_active' => !empty($row['is_active']) ? 1 : 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }
}
