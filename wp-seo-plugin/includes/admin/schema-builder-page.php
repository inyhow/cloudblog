<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_render_schema_builder_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['myseo_schema_builder_nonce']) && wp_verify_nonce($_POST['myseo_schema_builder_nonce'], 'myseo_schema_builder')) {
        $name = isset($_POST['myseo_builder_name']) ? myseo_sanitize_text($_POST['myseo_builder_name']) : 'Custom Schema';
        $type = isset($_POST['myseo_builder_type']) ? myseo_sanitize_text($_POST['myseo_builder_type']) : 'Article';
        $fields = array(
            '@context' => 'https://schema.org',
            '@type' => $type,
        );

        if (!empty($_POST['myseo_builder_headline'])) {
            $fields['headline'] = myseo_sanitize_text($_POST['myseo_builder_headline']);
        }
        if (!empty($_POST['myseo_builder_description'])) {
            $fields['description'] = myseo_sanitize_text($_POST['myseo_builder_description']);
        }
        if (!empty($_POST['myseo_builder_url'])) {
            $fields['url'] = esc_url_raw(wp_unslash($_POST['myseo_builder_url']));
        }

        global $wpdb;
        $table = myseo_get_table_name('custom_schemas');
        $now = current_time('mysql');
        $wpdb->insert($table, array(
            'schema_name' => $name,
            'schema_type' => $type,
            'schema_payload' => wp_json_encode($fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'trigger_type' => 'manual',
            'trigger_value' => '',
            'is_active' => 1,
            'validation_status' => 'builder_generated',
            'created_at' => $now,
            'updated_at' => $now,
        ));

        echo '<div class="notice notice-success"><p>Schema saved from builder.</p></div>';
    }

    echo '<div class="wrap"><h1>Custom Schema Builder</h1>';
    echo '<div class="myseo-card"><h2>Builder</h2><form method="post">';
    wp_nonce_field('myseo_schema_builder', 'myseo_schema_builder_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>Name</th><td><input type="text" name="myseo_builder_name" class="regular-text" required /></td></tr>';
    echo '<tr><th>Type</th><td><select name="myseo_builder_type">';
    foreach (myseo_get_schema_type_options() as $type) {
        echo '<option value="' . esc_attr($type) . '">' . esc_html($type) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th>Headline</th><td><input type="text" name="myseo_builder_headline" class="large-text" /></td></tr>';
    echo '<tr><th>Description</th><td><textarea name="myseo_builder_description" rows="4" class="large-text"></textarea></td></tr>';
    echo '<tr><th>URL</th><td><input type="url" name="myseo_builder_url" class="large-text" /></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Schema');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Builder Scope</h2><p>Supports custom JSON-LD generation, unlimited saved schemas, and automatable schema templates built from a guided form.</p></div></div>';
}
