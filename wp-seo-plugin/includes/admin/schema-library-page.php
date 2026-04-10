<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_schema_library_page() {
    add_submenu_page('myseo-dashboard', 'Schema Library', 'Schema Library', 'manage_options', 'myseo-schema-library', 'myseo_render_schema_library_page');
}

function myseo_render_schema_library_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    myseo_handle_schema_library_actions();

    global $wpdb;
    $table = myseo_get_table_name('custom_schemas');
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY updated_at DESC, id DESC LIMIT 200");
    $types = myseo_get_schema_type_options();

    echo '<div class="wrap"><h1>Advanced Schema Library</h1>';
    echo '<div class="myseo-card">';
    echo '<h2>Add Custom Schema</h2>';
    echo '<form method="post">';
    wp_nonce_field('myseo_save_schema', 'myseo_save_schema_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th><label for="myseo_schema_name">Name</label></th><td><input type="text" id="myseo_schema_name" name="myseo_schema_name" class="regular-text" required /></td></tr>';
    echo '<tr><th><label for="myseo_schema_type_library">Schema Type</label></th><td><select id="myseo_schema_type_library" name="myseo_schema_type_library">';
    foreach ($types as $type) {
        echo '<option value="' . esc_attr($type) . '">' . esc_html($type) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th><label for="myseo_schema_trigger_type">Trigger Type</label></th><td><select id="myseo_schema_trigger_type" name="myseo_schema_trigger_type"><option value="manual">Manual</option><option value="post_type">Post Type</option><option value="category">Category</option></select></td></tr>';
    echo '<tr><th><label for="myseo_schema_trigger_value">Trigger Value</label></th><td><input type="text" id="myseo_schema_trigger_value" name="myseo_schema_trigger_value" class="regular-text" /></td></tr>';
    echo '<tr><th><label for="myseo_schema_payload">JSON-LD Payload</label></th><td><textarea id="myseo_schema_payload" name="myseo_schema_payload" rows="10" class="large-text code" required></textarea></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Schema');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;">';
    echo '<h2>Import Schema From Any Website</h2>';
    echo '<form method="post">';
    wp_nonce_field('myseo_import_schema', 'myseo_import_schema_nonce');
    echo '<p><input type="url" name="myseo_import_schema_url" class="regular-text" placeholder="https://example.com/article" required /></p>';
    submit_button('Import Schema');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Saved Schemas</h2>';
    echo '<table class="widefat striped"><thead><tr><th>Name</th><th>Type</th><th>Trigger</th><th>Validation</th><th>Google Validation</th></tr></thead><tbody>';
    if ($rows) {
        foreach ($rows as $row) {
            $validator_url = 'https://search.google.com/test/rich-results?url=' . rawurlencode(home_url('/'));
            echo '<tr><td>' . esc_html($row->schema_name) . '</td><td>' . esc_html($row->schema_type) . '</td><td>' . esc_html($row->trigger_type . ($row->trigger_value ? ': ' . $row->trigger_value : '')) . '</td><td>' . esc_html($row->validation_status) . '</td><td><a href="' . esc_url($validator_url) . '" target="_blank" rel="noopener">Validate</a></td></tr>';
        }
    } else {
        echo '<tr><td colspan="5">No custom schemas yet.</td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function myseo_handle_schema_library_actions() {
    if (isset($_POST['myseo_import_schema_nonce']) && wp_verify_nonce($_POST['myseo_import_schema_nonce'], 'myseo_import_schema')) {
        myseo_import_schema_from_website();
        return;
    }

    if (!isset($_POST['myseo_save_schema_nonce']) || !wp_verify_nonce($_POST['myseo_save_schema_nonce'], 'myseo_save_schema')) {
        return;
    }

    $payload = isset($_POST['myseo_schema_payload']) ? trim(wp_unslash($_POST['myseo_schema_payload'])) : '';
    $decoded = json_decode($payload, true);
    $validation = is_array($decoded) ? 'valid_json' : 'invalid_json';

    global $wpdb;
    $table = myseo_get_table_name('custom_schemas');
    $now = current_time('mysql');
    $wpdb->insert($table, array(
        'schema_name' => isset($_POST['myseo_schema_name']) ? myseo_sanitize_text($_POST['myseo_schema_name']) : 'Custom Schema',
        'schema_type' => isset($_POST['myseo_schema_type_library']) ? myseo_sanitize_text($_POST['myseo_schema_type_library']) : 'Article',
        'schema_payload' => $payload,
        'trigger_type' => isset($_POST['myseo_schema_trigger_type']) ? myseo_sanitize_text($_POST['myseo_schema_trigger_type']) : 'manual',
        'trigger_value' => isset($_POST['myseo_schema_trigger_value']) ? myseo_sanitize_text($_POST['myseo_schema_trigger_value']) : '',
        'is_active' => 1,
        'validation_status' => $validation,
        'created_at' => $now,
        'updated_at' => $now,
    ));
}

function myseo_import_schema_from_website() {
    $url = isset($_POST['myseo_import_schema_url']) ? esc_url_raw(wp_unslash($_POST['myseo_import_schema_url'])) : '';
    if ($url === '') {
        return;
    }

    $response = wp_remote_get($url, array('timeout' => 15));
    if (is_wp_error($response)) {
        return;
    }

    $body = wp_remote_retrieve_body($response);
    if (!preg_match('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $body, $matches)) {
        return;
    }

    global $wpdb;
    $table = myseo_get_table_name('custom_schemas');
    $now = current_time('mysql');
    $payload = trim($matches[1]);
    $wpdb->insert($table, array(
        'schema_name' => 'Imported from ' . wp_parse_url($url, PHP_URL_HOST),
        'schema_type' => 'Imported',
        'schema_payload' => $payload,
        'trigger_type' => 'manual',
        'trigger_value' => '',
        'is_active' => 1,
        'validation_status' => json_decode($payload, true) ? 'valid_json' : 'invalid_json',
        'created_at' => $now,
        'updated_at' => $now,
    ));
}
