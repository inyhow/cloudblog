<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_render_index_status_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['myseo_index_status_nonce']) && wp_verify_nonce($_POST['myseo_index_status_nonce'], 'myseo_index_status')) {
        $post_id = isset($_POST['myseo_index_post_id']) ? (int) $_POST['myseo_index_post_id'] : 0;
        $status = isset($_POST['myseo_index_status']) ? myseo_sanitize_text($_POST['myseo_index_status']) : 'unknown';
        if ($post_id > 0) {
            update_post_meta($post_id, '_myseo_index_status', $status);
        }
    }

    $posts = get_posts(array(
        'post_type' => get_post_types(array('public' => true), 'names'),
        'post_status' => 'publish',
        'numberposts' => 100,
    ));

    echo '<div class="wrap"><h1>Google Index Status</h1>';
    echo '<div class="myseo-card"><h2>Update Status</h2><form method="post">';
    wp_nonce_field('myseo_index_status', 'myseo_index_status_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>Post ID</th><td><input type="number" min="1" name="myseo_index_post_id" class="small-text" required /></td></tr>';
    echo '<tr><th>Status</th><td><select name="myseo_index_status"><option value="indexed">Indexed</option><option value="discovered">Discovered</option><option value="crawled_not_indexed">Crawled Not Indexed</option><option value="excluded">Excluded</option><option value="unknown">Unknown</option></select></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Index Status');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Tracked Index Status</h2><table class="widefat striped"><thead><tr><th>Post</th><th>Status</th></tr></thead><tbody>';
    foreach ($posts as $post) {
        $status = get_post_meta($post->ID, '_myseo_index_status', true);
        if ($status === '') {
            $status = 'unknown';
        }
        echo '<tr><td>' . esc_html(get_the_title($post)) . '</td><td>' . esc_html($status) . '</td></tr>';
    }
    echo '</tbody></table></div></div>';
}
