<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_podcast_page() {
    add_submenu_page('myseo-dashboard', 'Podcast', 'Podcast', 'manage_options', 'myseo-podcast', 'myseo_render_podcast_page');
}

function myseo_render_podcast_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    myseo_handle_podcast_actions();

    global $wpdb;
    $table = myseo_get_table_name('podcasts');
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY updated_at DESC");

    echo '<div class="wrap"><h1>Podcast Module</h1>';
    echo '<div class="myseo-card"><h2>Add Podcast</h2><form method="post">';
    wp_nonce_field('myseo_save_podcast', 'myseo_save_podcast_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>Name</th><td><input type="text" name="myseo_podcast_name" class="regular-text" required /></td></tr>';
    echo '<tr><th>Description</th><td><textarea name="myseo_podcast_description" rows="4" class="large-text"></textarea></td></tr>';
    echo '<tr><th>Publisher</th><td><input type="text" name="myseo_podcast_publisher" class="regular-text" /></td></tr>';
    echo '<tr><th>Feed URL</th><td><input type="url" name="myseo_podcast_feed_url" class="regular-text" /></td></tr>';
    echo '<tr><th>Site URL</th><td><input type="url" name="myseo_podcast_site_url" class="regular-text" /></td></tr>';
    echo '<tr><th>Image URL</th><td><input type="url" name="myseo_podcast_image_url" class="regular-text" /></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Podcast');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Podcasts</h2><table class="widefat striped"><thead><tr><th>Name</th><th>Publisher</th><th>Feed</th><th>Status</th></tr></thead><tbody>';
    if ($rows) {
        foreach ($rows as $row) {
            echo '<tr><td>' . esc_html($row->podcast_name) . '</td><td>' . esc_html($row->publisher_name) . '</td><td>' . esc_html($row->feed_url) . '</td><td>' . ($row->is_active ? 'Active' : 'Inactive') . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="4">No podcasts yet.</td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function myseo_handle_podcast_actions() {
    if (!isset($_POST['myseo_save_podcast_nonce']) || !wp_verify_nonce($_POST['myseo_save_podcast_nonce'], 'myseo_save_podcast')) {
        return;
    }

    global $wpdb;
    $table = myseo_get_table_name('podcasts');
    $now = current_time('mysql');
    $wpdb->insert($table, array(
        'podcast_name' => isset($_POST['myseo_podcast_name']) ? myseo_sanitize_text($_POST['myseo_podcast_name']) : '',
        'podcast_description' => isset($_POST['myseo_podcast_description']) ? sanitize_textarea_field(wp_unslash($_POST['myseo_podcast_description'])) : '',
        'publisher_name' => isset($_POST['myseo_podcast_publisher']) ? myseo_sanitize_text($_POST['myseo_podcast_publisher']) : '',
        'feed_url' => isset($_POST['myseo_podcast_feed_url']) ? esc_url_raw(wp_unslash($_POST['myseo_podcast_feed_url'])) : '',
        'site_url' => isset($_POST['myseo_podcast_site_url']) ? esc_url_raw(wp_unslash($_POST['myseo_podcast_site_url'])) : '',
        'image_url' => isset($_POST['myseo_podcast_image_url']) ? esc_url_raw(wp_unslash($_POST['myseo_podcast_image_url'])) : '',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ));
}
