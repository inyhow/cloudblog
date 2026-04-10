<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_content_overview_page() {
    add_submenu_page('myseo-dashboard', 'Content Overview', 'Content Overview', 'manage_options', 'myseo-content-overview', 'myseo_render_content_overview_page');
}

function myseo_render_content_overview_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $post_type = isset($_GET['post_type_filter']) ? myseo_sanitize_text($_GET['post_type_filter']) : '';
    $seo_status = isset($_GET['seo_status']) ? myseo_sanitize_text($_GET['seo_status']) : '';
    $orphan_only = isset($_GET['orphan_only']) ? 1 : 0;

    $posts = get_posts(array(
        'post_type' => $post_type !== '' ? $post_type : get_post_types(array('public' => true), 'names'),
        'post_status' => array('publish', 'draft'),
        'numberposts' => -1,
    ));

    $rows = array();
    foreach ($posts as $post) {
        $item = myseo_build_content_overview_row($post);
        if ($seo_status === 'missing' && $item['seo_complete']) {
            continue;
        }
        if ($seo_status === 'complete' && !$item['seo_complete']) {
            continue;
        }
        if ($orphan_only && !$item['orphan']) {
            continue;
        }
        $rows[] = $item;
    }

    echo '<div class="wrap"><h1>Advanced Content SEO Overview</h1>';
    echo '<form method="get" style="margin-bottom:16px;">';
    echo '<input type="hidden" name="page" value="myseo-content-overview" />';
    echo '<select name="post_type_filter"><option value="">All Post Types</option>';
    foreach (get_post_types(array('public' => true), 'objects') as $type) {
        echo '<option value="' . esc_attr($type->name) . '"' . selected($post_type, $type->name, false) . '>' . esc_html($type->labels->singular_name) . '</option>';
    }
    echo '</select> ';
    echo '<select name="seo_status"><option value="">All SEO Status</option><option value="missing"' . selected($seo_status, 'missing', false) . '>Missing SEO</option><option value="complete"' . selected($seo_status, 'complete', false) . '>SEO Complete</option></select> ';
    echo '<label><input type="checkbox" name="orphan_only" value="1"' . checked($orphan_only, 1, false) . ' /> Orphan Pages Only</label> ';
    submit_button('Filter', 'secondary', '', false);
    echo '</form>';

    echo '<div class="myseo-card">';
    echo '<table class="widefat striped"><thead><tr><th>Title</th><th>Type</th><th>SEO</th><th>Orphan</th><th>Keyword</th><th>Position</th><th>Clicks</th><th>Performance</th></tr></thead><tbody>';
    if ($rows) {
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td><a href="' . esc_url(get_edit_post_link($row['post_id'])) . '">' . esc_html($row['title']) . '</a></td>';
            echo '<td>' . esc_html($row['post_type']) . '</td>';
            echo '<td>' . ($row['seo_complete'] ? 'Complete' : 'Missing') . '</td>';
            echo '<td>' . ($row['orphan'] ? 'Yes' : 'No') . '</td>';
            echo '<td>' . esc_html($row['focus_keyword']) . '</td>';
            echo '<td>' . esc_html($row['position']) . '</td>';
            echo '<td>' . esc_html($row['clicks']) . '</td>';
            echo '<td>' . esc_html($row['badge']) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="8">No content matched your filters.</td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function myseo_build_content_overview_row($post) {
    global $wpdb;
    $metrics_table = myseo_get_table_name('post_metrics');
    $latest = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$metrics_table} WHERE post_id = %d ORDER BY metric_date DESC LIMIT 1", $post->ID));

    $title = get_post_meta($post->ID, '_myseo_title', true);
    $description = get_post_meta($post->ID, '_myseo_description', true);
    $focus_keyword = get_post_meta($post->ID, '_myseo_focus_keyword', true);

    return array(
        'post_id' => $post->ID,
        'title' => get_the_title($post),
        'post_type' => $post->post_type,
        'seo_complete' => $title !== '' && $description !== '',
        'orphan' => myseo_is_orphan_page($post->ID),
        'focus_keyword' => $focus_keyword,
        'position' => $latest ? $latest->average_position : '-',
        'clicks' => $latest ? $latest->clicks : 0,
        'badge' => myseo_get_performance_badge($latest),
    );
}

function myseo_is_orphan_page($post_id) {
    $url = get_permalink($post_id);
    if (!$url) {
        return false;
    }

    $posts = get_posts(array(
        'post_type' => get_post_types(array('public' => true), 'names'),
        'post_status' => 'publish',
        'numberposts' => -1,
        'exclude' => array($post_id),
    ));

    foreach ($posts as $post) {
        if (strpos($post->post_content, $url) !== false) {
            return false;
        }
    }

    return true;
}
