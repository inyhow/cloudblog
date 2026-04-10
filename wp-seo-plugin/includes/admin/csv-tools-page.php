<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_csv_tools_page() {
    add_submenu_page('myseo-dashboard', 'CSV Tools', 'CSV Tools', 'manage_options', 'myseo-csv-tools', 'myseo_render_csv_tools_page');
}

function myseo_render_csv_tools_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    if (isset($_POST['myseo_export_seo_csv'])) {
        myseo_stream_seo_csv();
        return;
    }
    if (isset($_POST['myseo_export_redirections_csv'])) {
        myseo_stream_redirections_csv();
        return;
    }
    if (isset($_POST['myseo_export_404_csv'])) {
        myseo_stream_404_csv();
        return;
    }
    if (isset($_POST['myseo_import_csv_nonce']) && wp_verify_nonce($_POST['myseo_import_csv_nonce'], 'myseo_import_csv')) {
        $message = myseo_handle_csv_import();
    }

    echo '<div class="wrap"><h1>CSV Tools</h1>';
    if ($message) {
        echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    }

    echo '<div class="myseo-card-grid">';
    echo '<div class="myseo-card"><h2>Export SEO Data</h2><form method="post">';
    submit_button('Download SEO CSV', 'secondary', 'myseo_export_seo_csv', false);
    echo '</form></div>';

    echo '<div class="myseo-card"><h2>Export Redirections</h2><form method="post">';
    submit_button('Download Redirections CSV', 'secondary', 'myseo_export_redirections_csv', false);
    echo '</form></div>';

    echo '<div class="myseo-card"><h2>Export 404 Log</h2><form method="post">';
    submit_button('Download 404 CSV', 'secondary', 'myseo_export_404_csv', false);
    echo '</form></div>';
    echo '</div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Import CSV</h2>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('myseo_import_csv', 'myseo_import_csv_nonce');
    echo '<p><label><input type="radio" name="myseo_csv_import_type" value="seo" checked /> SEO Data</label> ';
    echo '<label><input type="radio" name="myseo_csv_import_type" value="redirections" /> Redirections</label></p>';
    echo '<input type="file" name="myseo_csv_file" accept=".csv,text/csv" required />';
    echo '<p class="description">SEO CSV columns: post_id,seo_title,meta_description,canonical,robots,focus_keyword</p>';
    echo '<p class="description">Redirections CSV columns: source,target,status_code,is_regex,enabled</p>';
    submit_button('Import CSV');
    echo '</form></div></div>';
}

function myseo_stream_seo_csv() {
    $posts = get_posts(array(
        'post_type' => get_post_types(array('public' => true), 'names'),
        'post_status' => array('publish', 'draft', 'private', 'future'),
        'numberposts' => -1,
        'fields' => 'ids',
    ));

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=myseo-seo-data.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('post_id', 'seo_title', 'meta_description', 'canonical', 'robots', 'focus_keyword'));
    foreach ($posts as $post_id) {
        fputcsv($output, array(
            $post_id,
            get_post_meta($post_id, '_myseo_title', true),
            get_post_meta($post_id, '_myseo_description', true),
            get_post_meta($post_id, '_myseo_canonical', true),
            get_post_meta($post_id, '_myseo_robots', true),
            get_post_meta($post_id, '_myseo_focus_keyword', true),
        ));
    }
    fclose($output);
    exit;
}

function myseo_stream_redirections_csv() {
    global $wpdb;
    $table = myseo_get_table_name('redirections');
    $rows = $wpdb->get_results("SELECT source, target, status_code, is_regex, enabled FROM {$table}", ARRAY_A);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=myseo-redirections.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('source', 'target', 'status_code', 'is_regex', 'enabled'));
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

function myseo_stream_404_csv() {
    global $wpdb;
    $table = myseo_get_table_name('404_logs');
    $rows = $wpdb->get_results("SELECT url, referrer, user_agent, ip_address, hits, last_seen, created_at FROM {$table}", ARRAY_A);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=myseo-404-log.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('url', 'referrer', 'user_agent', 'ip_address', 'hits', 'last_seen', 'created_at'));
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

function myseo_handle_csv_import() {
    if (!isset($_FILES['myseo_csv_file']) || $_FILES['myseo_csv_file']['error'] !== UPLOAD_ERR_OK) {
        return 'CSV upload failed.';
    }

    $handle = fopen($_FILES['myseo_csv_file']['tmp_name'], 'r');
    if (!$handle) {
        return 'Could not open CSV.';
    }

    $type = isset($_POST['myseo_csv_import_type']) ? myseo_sanitize_text($_POST['myseo_csv_import_type']) : 'seo';
    $headers = fgetcsv($handle);
    $count = 0;

    if ($type === 'seo') {
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            $post_id = isset($data['post_id']) ? (int) $data['post_id'] : 0;
            if ($post_id < 1) {
                continue;
            }
            update_post_meta($post_id, '_myseo_title', isset($data['seo_title']) ? $data['seo_title'] : '');
            update_post_meta($post_id, '_myseo_description', isset($data['meta_description']) ? $data['meta_description'] : '');
            update_post_meta($post_id, '_myseo_canonical', isset($data['canonical']) ? esc_url_raw($data['canonical']) : '');
            update_post_meta($post_id, '_myseo_robots', isset($data['robots']) ? sanitize_text_field($data['robots']) : '');
            update_post_meta($post_id, '_myseo_focus_keyword', isset($data['focus_keyword']) ? sanitize_text_field($data['focus_keyword']) : '');
            $count++;
        }
    } elseif ($type === 'redirections') {
        global $wpdb;
        $table = myseo_get_table_name('redirections');
        $now = current_time('mysql');
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            if (empty($data['source']) || empty($data['target'])) {
                continue;
            }
            $wpdb->insert($table, array(
                'source' => sanitize_text_field($data['source']),
                'target' => sanitize_text_field($data['target']),
                'status_code' => isset($data['status_code']) ? (int) $data['status_code'] : 301,
                'is_regex' => !empty($data['is_regex']) ? 1 : 0,
                'hits' => 0,
                'enabled' => !isset($data['enabled']) || $data['enabled'] ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ));
            $count++;
        }
    }

    fclose($handle);
    return 'Imported ' . $count . ' row(s).';
}
