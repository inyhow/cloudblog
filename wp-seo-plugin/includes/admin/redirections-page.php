<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_redirections() {
    add_action('template_redirect', 'myseo_handle_redirection', 0);
}

function myseo_register_redirections_page() {
    add_options_page(
        'MySEO Redirections',
        'MySEO Redirections',
        'manage_options',
        'myseo-redirections',
        'myseo_render_redirections_page'
    );
}

function myseo_render_redirections_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    myseo_handle_redirection_form();
    myseo_handle_redirection_delete();

    global $wpdb;
    $table = $wpdb->prefix . 'myseo_redirections';
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC LIMIT 200");

    echo '<div class="wrap">';
    echo '<h1>MySEO Redirections</h1>';

    echo '<h2>Add Redirect</h2>';
    echo '<form method="post">';
    wp_nonce_field('myseo_add_redirect', 'myseo_add_redirect_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th><label for="myseo_source">Source URL</label></th><td><input type="text" id="myseo_source" name="myseo_source" class="regular-text" required /></td></tr>';
    echo '<tr><th><label for="myseo_target">Target URL</label></th><td><input type="text" id="myseo_target" name="myseo_target" class="regular-text" required /></td></tr>';
    echo '<tr><th><label for="myseo_status">Status Code</label></th><td><select id="myseo_status" name="myseo_status"><option value="301">301</option><option value="302">302</option><option value="307">307</option><option value="410">410</option></select></td></tr>';
    echo '<tr><th><label for="myseo_regex">Regex</label></th><td><input type="checkbox" id="myseo_regex" name="myseo_regex" value="1" /> Use regex</td></tr>';
    echo '</tbody></table>';
    submit_button('Add Redirect');
    echo '</form>';

    echo '<h2>Existing Redirects</h2>';
    echo '<table class="widefat striped">';
    echo '<thead><tr><th>ID</th><th>Source</th><th>Target</th><th>Status</th><th>Regex</th><th>Hits</th><th>Actions</th></tr></thead><tbody>';

    if ($rows) {
        foreach ($rows as $row) {
            $delete_url = wp_nonce_url(
                add_query_arg(array('page' => 'myseo-redirections', 'delete' => $row->id), admin_url('admin.php')),
                'myseo_delete_redirect_' . $row->id
            );
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->source) . '</td>';
            echo '<td>' . esc_html($row->target) . '</td>';
            echo '<td>' . esc_html($row->status_code) . '</td>';
            echo '<td>' . ($row->is_regex ? 'Yes' : 'No') . '</td>';
            echo '<td>' . esc_html($row->hits) . '</td>';
            echo '<td><a href="' . esc_url($delete_url) . '">Delete</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7">No redirects found.</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}

function myseo_handle_redirection_form() {
    if (!myseo_module_enabled('redirections')) {
        return;
    }
    if (!isset($_POST['myseo_add_redirect_nonce'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['myseo_add_redirect_nonce'], 'myseo_add_redirect')) {
        return;
    }

    $source = isset($_POST['myseo_source']) ? myseo_sanitize_text($_POST['myseo_source']) : '';
    $target = isset($_POST['myseo_target']) ? myseo_sanitize_text($_POST['myseo_target']) : '';
    $status = isset($_POST['myseo_status']) ? (int) $_POST['myseo_status'] : 301;
    $is_regex = isset($_POST['myseo_regex']) ? 1 : 0;

    if ($source === '' || $target === '') {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'myseo_redirections';
    $wpdb->insert($table, array(
        'source' => $source,
        'target' => $target,
        'status_code' => $status,
        'is_regex' => $is_regex,
        'hits' => 0,
        'enabled' => 1,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    ));
}

function myseo_handle_redirection_delete() {
    if (!myseo_module_enabled('redirections')) {
        return;
    }
    if (!isset($_GET['delete'])) {
        return;
    }
    $id = (int) $_GET['delete'];
    if ($id < 1) {
        return;
    }
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'myseo_delete_redirect_' . $id)) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'myseo_redirections';
    $wpdb->delete($table, array('id' => $id));
}

function myseo_handle_redirection() {
    if (!myseo_module_enabled('redirections')) {
        return;
    }
    if (is_admin()) {
        return;
    }

    $request = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
    $path = strtok($request, '?');

    global $wpdb;
    $table = $wpdb->prefix . 'myseo_redirections';
    $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE enabled = 1 ORDER BY id DESC LIMIT 500");

    foreach ($rows as $row) {
        if ($row->is_regex) {
            $pattern = '#' . $row->source . '#';
            if (@preg_match($pattern, $path)) {
                if (preg_match($pattern, $path)) {
                    myseo_apply_redirection($row, $path);
                }
            }
        } else {
            if ($row->source === $path) {
                myseo_apply_redirection($row, $path);
            }
        }
    }
}

function myseo_apply_redirection($row, $path) {
    global $wpdb;
    $table = $wpdb->prefix . 'myseo_redirections';
    $wpdb->query($wpdb->prepare("UPDATE {$table} SET hits = hits + 1, updated_at = %s WHERE id = %d", current_time('mysql'), $row->id));

    $status = (int) $row->status_code;
    $target = $row->target;

    if ($row->is_regex) {
        $pattern = '#' . $row->source . '#';
        $target = preg_replace($pattern, $row->target, $path);
    }

    wp_redirect($target, $status);
    exit;
}
