<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_bulk_edit_page() {
    add_submenu_page('myseo-dashboard', 'Bulk Edit', 'Bulk Edit', 'manage_options', 'myseo-bulk-edit', 'myseo_render_bulk_edit_page');
}

function myseo_register_bulk_edit_hooks() {
    add_action('quick_edit_custom_box', 'myseo_render_quick_edit_fields', 10, 2);
    add_action('save_post', 'myseo_save_quick_edit_fields');
}

myseo_register_bulk_edit_hooks();

function myseo_render_bulk_edit_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    myseo_handle_bulk_edit_actions();

    $posts = get_posts(array(
        'post_type' => get_post_types(array('public' => true), 'names'),
        'post_status' => array('publish', 'draft'),
        'numberposts' => 100,
    ));

    echo '<div class="wrap"><h1>Advanced Bulk Edit</h1>';
    echo '<div class="myseo-card"><h2>Bulk Update</h2><form method="post">';
    wp_nonce_field('myseo_bulk_edit', 'myseo_bulk_edit_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>Post IDs</th><td><input type="text" name="myseo_bulk_post_ids" class="large-text" placeholder="1,2,3" /></td></tr>';
    echo '<tr><th>Append to Title</th><td><input type="text" name="myseo_bulk_title_append" class="regular-text" /></td></tr>';
    echo '<tr><th>Set Robots</th><td><input type="text" name="myseo_bulk_robots" class="regular-text" placeholder="index,follow" /></td></tr>';
    echo '<tr><th>Set Focus Keyword</th><td><input type="text" name="myseo_bulk_keyword" class="regular-text" /></td></tr>';
    echo '</tbody></table>';
    submit_button('Apply Bulk Edit');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Recent Content</h2><table class="widefat striped"><thead><tr><th>Title</th><th>SEO Title</th><th>Focus Keyword</th></tr></thead><tbody>';
    foreach ($posts as $post) {
        echo '<tr><td>' . esc_html(get_the_title($post)) . '</td><td>' . esc_html(get_post_meta($post->ID, '_myseo_title', true)) . '</td><td>' . esc_html(get_post_meta($post->ID, '_myseo_focus_keyword', true)) . '</td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function myseo_handle_bulk_edit_actions() {
    if (!isset($_POST['myseo_bulk_edit_nonce']) || !wp_verify_nonce($_POST['myseo_bulk_edit_nonce'], 'myseo_bulk_edit')) {
        return;
    }

    $ids_raw = isset($_POST['myseo_bulk_post_ids']) ? myseo_sanitize_text($_POST['myseo_bulk_post_ids']) : '';
    $ids = array_filter(array_map('absint', array_map('trim', explode(',', $ids_raw))));
    if (!$ids) {
        return;
    }

    $title_append = isset($_POST['myseo_bulk_title_append']) ? myseo_sanitize_text($_POST['myseo_bulk_title_append']) : '';
    $robots = isset($_POST['myseo_bulk_robots']) ? myseo_sanitize_text($_POST['myseo_bulk_robots']) : '';
    $keyword = isset($_POST['myseo_bulk_keyword']) ? myseo_sanitize_text($_POST['myseo_bulk_keyword']) : '';

    foreach ($ids as $post_id) {
        if ($title_append !== '') {
            $existing = get_post_meta($post_id, '_myseo_title', true);
            update_post_meta($post_id, '_myseo_title', trim($existing . ' ' . $title_append));
        }
        if ($robots !== '') {
            update_post_meta($post_id, '_myseo_robots', $robots);
        }
        if ($keyword !== '') {
            update_post_meta($post_id, '_myseo_focus_keyword', $keyword);
        }
    }
}

function myseo_render_quick_edit_fields($column_name, $post_type) {
    if ($column_name !== 'myseo_performance') {
        return;
    }

    echo '<fieldset class="inline-edit-col-right"><div class="inline-edit-col">';
    echo '<label><span class="title">Focus Keyword</span><span class="input-text-wrap"><input type="text" name="myseo_quick_focus_keyword" value="" /></span></label>';
    echo '<label><span class="title">Robots</span><span class="input-text-wrap"><input type="text" name="myseo_quick_robots" value="" /></span></label>';
    echo '</div></fieldset>';
}

function myseo_save_quick_edit_fields($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['myseo_quick_focus_keyword']) && $_POST['myseo_quick_focus_keyword'] !== '') {
        update_post_meta($post_id, '_myseo_focus_keyword', myseo_sanitize_text($_POST['myseo_quick_focus_keyword']));
    }
    if (isset($_POST['myseo_quick_robots']) && $_POST['myseo_quick_robots'] !== '') {
        update_post_meta($post_id, '_myseo_robots', myseo_sanitize_text($_POST['myseo_quick_robots']));
    }
}
