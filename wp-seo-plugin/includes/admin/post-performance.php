<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_post_performance_columns() {
    $post_types = get_post_types(array('public' => true), 'names');
    foreach ($post_types as $post_type) {
        add_filter("manage_{$post_type}_posts_columns", 'myseo_add_post_performance_column');
        add_action("manage_{$post_type}_posts_custom_column", 'myseo_render_post_performance_column', 10, 2);
        add_filter("manage_edit-{$post_type}_sortable_columns", 'myseo_sortable_post_performance_column');
    }
}

function myseo_add_post_performance_column($columns) {
    $columns['myseo_performance'] = 'SEO Performance';
    return $columns;
}

function myseo_render_post_performance_column($column, $post_id) {
    if ($column !== 'myseo_performance') {
        return;
    }

    global $wpdb;
    $table = myseo_get_table_name('post_metrics');
    $latest = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d ORDER BY metric_date DESC LIMIT 1", $post_id));
    echo esc_html(myseo_get_performance_badge($latest));
}

function myseo_sortable_post_performance_column($columns) {
    $columns['myseo_performance'] = 'myseo_performance';
    return $columns;
}

function myseo_get_performance_badge($metric_row) {
    if (!$metric_row) {
        return 'No Data';
    }

    $score = 0;
    if ((int) $metric_row->clicks > 100) {
        $score += 2;
    } elseif ((int) $metric_row->clicks > 0) {
        $score += 1;
    }
    if ((float) $metric_row->average_position > 0 && (float) $metric_row->average_position <= 10) {
        $score += 2;
    } elseif ((float) $metric_row->average_position <= 30) {
        $score += 1;
    }
    if ((int) $metric_row->page_speed_mobile >= 80) {
        $score += 1;
    }

    if ($score >= 4) {
        return 'Winning';
    }
    if ($score >= 2) {
        return 'Stable';
    }
    return 'Needs Work';
}

myseo_register_post_performance_columns();
