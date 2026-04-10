<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_local_seo_page() {
    add_submenu_page('myseo-dashboard', 'Local SEO', 'Local SEO', 'manage_options', 'myseo-local-seo', 'myseo_render_local_seo_page');
}

function myseo_render_local_seo_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    myseo_handle_local_seo_actions();

    global $wpdb;
    $table = myseo_get_table_name('locations');
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY primary_location DESC, updated_at DESC");

    echo '<div class="wrap"><h1>Local SEO PRO</h1>';
    echo '<div class="myseo-card"><h2>Add Location</h2><form method="post">';
    wp_nonce_field('myseo_save_location', 'myseo_save_location_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>Name</th><td><input type="text" name="myseo_location_name" class="regular-text" required /></td></tr>';
    echo '<tr><th>Street Address</th><td><input type="text" name="myseo_location_street" class="regular-text" /></td></tr>';
    echo '<tr><th>City</th><td><input type="text" name="myseo_location_city" class="regular-text" /></td></tr>';
    echo '<tr><th>Region</th><td><input type="text" name="myseo_location_region" class="regular-text" /></td></tr>';
    echo '<tr><th>Postal Code</th><td><input type="text" name="myseo_location_postal" class="regular-text" /></td></tr>';
    echo '<tr><th>Country</th><td><input type="text" name="myseo_location_country" value="' . esc_attr(myseo_get_option('default_country_code', 'US')) . '" class="small-text" /></td></tr>';
    echo '<tr><th>Phone</th><td><input type="text" name="myseo_location_phone" class="regular-text" /></td></tr>';
    echo '<tr><th>Email</th><td><input type="email" name="myseo_location_email" class="regular-text" /></td></tr>';
    echo '<tr><th>Latitude</th><td><input type="text" name="myseo_location_latitude" class="regular-text" /></td></tr>';
    echo '<tr><th>Longitude</th><td><input type="text" name="myseo_location_longitude" class="regular-text" /></td></tr>';
    echo '<tr><th>Opening Hours</th><td><textarea name="myseo_location_hours" rows="3" class="large-text"></textarea></td></tr>';
    echo '<tr><th>Primary</th><td><label><input type="checkbox" name="myseo_location_primary" value="1" /> Mark as primary location</label></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Location');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Locations</h2><table class="widefat striped"><thead><tr><th>Name</th><th>Address</th><th>Phone</th><th>Primary</th></tr></thead><tbody>';
    if ($rows) {
        foreach ($rows as $row) {
            echo '<tr><td>' . esc_html($row->location_name) . '</td><td>' . esc_html(trim($row->street_address . ' ' . $row->city . ' ' . $row->region)) . '</td><td>' . esc_html($row->phone) . '</td><td>' . ($row->primary_location ? 'Yes' : 'No') . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="4">No locations yet.</td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function myseo_handle_local_seo_actions() {
    if (!isset($_POST['myseo_save_location_nonce']) || !wp_verify_nonce($_POST['myseo_save_location_nonce'], 'myseo_save_location')) {
        return;
    }

    global $wpdb;
    $table = myseo_get_table_name('locations');
    $now = current_time('mysql');
    $is_primary = isset($_POST['myseo_location_primary']) ? 1 : 0;
    if ($is_primary) {
        $wpdb->query("UPDATE {$table} SET primary_location = 0");
    }

    $wpdb->insert($table, array(
        'location_name' => isset($_POST['myseo_location_name']) ? myseo_sanitize_text($_POST['myseo_location_name']) : '',
        'street_address' => isset($_POST['myseo_location_street']) ? myseo_sanitize_text($_POST['myseo_location_street']) : '',
        'city' => isset($_POST['myseo_location_city']) ? myseo_sanitize_text($_POST['myseo_location_city']) : '',
        'region' => isset($_POST['myseo_location_region']) ? myseo_sanitize_text($_POST['myseo_location_region']) : '',
        'postal_code' => isset($_POST['myseo_location_postal']) ? myseo_sanitize_text($_POST['myseo_location_postal']) : '',
        'country_code' => isset($_POST['myseo_location_country']) ? strtoupper(myseo_sanitize_text($_POST['myseo_location_country'])) : 'US',
        'phone' => isset($_POST['myseo_location_phone']) ? myseo_sanitize_text($_POST['myseo_location_phone']) : '',
        'email' => isset($_POST['myseo_location_email']) ? sanitize_email(wp_unslash($_POST['myseo_location_email'])) : '',
        'latitude' => isset($_POST['myseo_location_latitude']) ? myseo_sanitize_text($_POST['myseo_location_latitude']) : '',
        'longitude' => isset($_POST['myseo_location_longitude']) ? myseo_sanitize_text($_POST['myseo_location_longitude']) : '',
        'opening_hours' => isset($_POST['myseo_location_hours']) ? sanitize_textarea_field(wp_unslash($_POST['myseo_location_hours'])) : '',
        'primary_location' => $is_primary,
        'created_at' => $now,
        'updated_at' => $now,
    ));
}
