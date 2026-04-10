<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_dashboard_page() {
    add_menu_page(
        'MySEO Dashboard',
        'MySEO',
        'manage_options',
        'myseo-dashboard',
        'myseo_render_dashboard_page',
        'dashicons-chart-area',
        59
    );

    add_submenu_page('myseo-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'myseo-dashboard', 'myseo_render_dashboard_page');
    add_submenu_page('myseo-dashboard', 'Settings', 'Settings', 'manage_options', 'myseo-settings', 'myseo_render_settings_page');
    add_submenu_page('myseo-dashboard', 'Modules', 'Modules', 'manage_options', 'myseo-modules', 'myseo_render_modules_page');
    add_submenu_page('myseo-dashboard', 'Google Suite', 'Google Suite', 'manage_options', 'myseo-google-suite', 'myseo_render_google_suite_page');
    add_submenu_page('myseo-dashboard', 'Content Suite', 'Content Suite', 'manage_options', 'myseo-content-suite', 'myseo_render_content_suite_page');
    add_submenu_page('myseo-dashboard', 'Schema Suite', 'Schema Suite', 'manage_options', 'myseo-schema-suite', 'myseo_render_schema_suite_page');
    add_submenu_page('myseo-dashboard', 'Commerce & Local', 'Commerce & Local', 'manage_options', 'myseo-commerce-suite', 'myseo_render_commerce_suite_page');
    add_submenu_page('myseo-dashboard', 'Operations', 'Operations', 'manage_options', 'myseo-ops-suite', 'myseo_render_ops_suite_page');

    myseo_register_hidden_detail_pages();
}

add_action('admin_menu', 'myseo_register_dashboard_page');

function myseo_register_hidden_detail_pages() {
    add_submenu_page(null, 'Integrations', 'Integrations', 'manage_options', 'myseo-integrations', 'myseo_render_integrations_page');
    add_submenu_page(null, 'Google Sync', 'Google Sync', 'manage_options', 'myseo-google-sync', 'myseo_render_google_sync_page');
    add_submenu_page(null, 'Google API', 'Google API', 'manage_options', 'myseo-google-api', 'myseo_render_google_api_page');
    add_submenu_page(null, 'Performance Services', 'Performance Services', 'manage_options', 'myseo-performance-services', 'myseo_render_performance_services_page');
    add_submenu_page(null, 'Analytics Overview', 'Analytics Overview', 'manage_options', 'myseo-analytics-overview', 'myseo_render_analytics_overview_page');
    add_submenu_page(null, 'AI Suite', 'AI Suite', 'manage_options', 'myseo-ai-suite', 'myseo_render_ai_suite_page');
    add_submenu_page(null, 'Google Trends', 'Google Trends', 'manage_options', 'myseo-google-trends', 'myseo_render_trends_page');
    add_submenu_page(null, 'Rank Tracker', 'Rank Tracker', 'manage_options', 'myseo-rank-tracker', 'myseo_render_rank_tracker_page');
    add_submenu_page(null, 'History', 'History', 'manage_options', 'myseo-history', 'myseo_render_history_page');
    add_submenu_page(null, 'Content Overview', 'Content Overview', 'manage_options', 'myseo-content-overview', 'myseo_render_content_overview_page');
    add_submenu_page(null, 'Index Status', 'Index Status', 'manage_options', 'myseo-index-status', 'myseo_render_index_status_page');
    add_submenu_page(null, 'Local SEO', 'Local SEO', 'manage_options', 'myseo-local-seo', 'myseo_render_local_seo_page');
    add_submenu_page(null, 'WooCommerce SEO', 'WooCommerce SEO', 'manage_options', 'myseo-woocommerce-seo', 'myseo_render_woocommerce_seo_page');
    add_submenu_page(null, 'Podcast', 'Podcast', 'manage_options', 'myseo-podcast', 'myseo_render_podcast_page');
    add_submenu_page(null, 'Bulk Edit', 'Bulk Edit', 'manage_options', 'myseo-bulk-edit', 'myseo_render_bulk_edit_page');
    add_submenu_page(null, 'Image SEO', 'Image SEO', 'manage_options', 'myseo-image-seo', 'myseo_render_image_seo_page');
    add_submenu_page(null, 'Builder Integrations', 'Builder Integrations', 'manage_options', 'myseo-builder-integrations', 'myseo_render_integrations_extra_page');
    add_submenu_page(null, 'EDD SEO', 'EDD SEO', 'manage_options', 'myseo-edd-seo', 'myseo_render_edd_admin_page');
    add_submenu_page(null, 'Setup Wizard', 'Setup Wizard', 'manage_options', 'myseo-setup-wizard', 'myseo_render_setup_wizard_page');
    add_submenu_page(null, 'Schema Library', 'Schema Library', 'manage_options', 'myseo-schema-library', 'myseo_render_schema_library_page');
    add_submenu_page(null, 'Schema Builder', 'Schema Builder', 'manage_options', 'myseo-schema-builder', 'myseo_render_schema_builder_page');
    add_submenu_page(null, 'Email Reports', 'Email Reports', 'manage_options', 'myseo-email-reports', 'myseo_render_reports_page');
    add_submenu_page(null, 'Clients', 'Clients', 'manage_options', 'myseo-clients', 'myseo_render_clients_page');
    add_submenu_page(null, 'CSV Tools', 'CSV Tools', 'manage_options', 'myseo-csv-tools', 'myseo_render_csv_tools_page');
    add_submenu_page(null, 'Advanced Redirections', 'Advanced Redirections', 'manage_options', 'myseo-advanced-redirections', 'myseo_render_redirections_advanced_page');
    add_submenu_page(null, 'Redirections', 'Redirections', 'manage_options', 'myseo-redirections', 'myseo_render_redirections_page');
    add_submenu_page(null, '404 Monitor', '404 Monitor', 'manage_options', 'myseo-404-monitor', 'myseo_render_monitor_page');
    add_submenu_page(null, 'Import', 'Import', 'manage_options', 'myseo-import', 'myseo_render_import_page');
    add_submenu_page(null, 'Export/Import', 'Export/Import', 'manage_options', 'myseo-export-import', 'myseo_render_export_import_page');
}

function myseo_render_dashboard_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table_404 = $wpdb->prefix . 'myseo_404_logs';
    $table_redir = $wpdb->prefix . 'myseo_redirections';

    $count_404 = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_404}");
    $count_redir = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_redir}");

    $posts_missing = myseo_count_posts_missing_meta();
    $sitemap_url = home_url('/myseo-sitemap.xml');
    $google_verified = myseo_get_option('google_site_verification', '') !== '';
    $toc_enabled = myseo_module_enabled('toc');
    $breadcrumbs_enabled = myseo_module_enabled('breadcrumbs');
    $tracked_keywords = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . myseo_get_table_name('keywords'));
    $custom_schemas = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . myseo_get_table_name('custom_schemas'));
    $scheduled_reports = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . myseo_get_table_name('email_reports') . " WHERE is_active = 1");
    $orphan_pages = myseo_count_orphan_pages();
    $locations = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . myseo_get_table_name('locations'));
    $podcasts = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . myseo_get_table_name('podcasts') . " WHERE is_active = 1");
    $sync_logs = (int) $wpdb->get_var("SELECT COUNT(*) FROM " . myseo_get_table_name('sync_logs'));

    $recent_404 = $wpdb->get_results("SELECT * FROM {$table_404} ORDER BY last_seen DESC LIMIT 5");
    $top_redir = $wpdb->get_results("SELECT * FROM {$table_redir} ORDER BY hits DESC LIMIT 5");

    echo '<div class="wrap">';
    echo '<h1>MySEO Dashboard</h1>';

    echo '<div class="myseo-card-grid">';
    echo myseo_render_stat_card('Missing SEO Meta', $posts_missing);
    echo myseo_render_stat_card('404 URLs Logged', $count_404);
    echo myseo_render_stat_card('Redirect Rules', $count_redir);
    echo myseo_render_stat_card('Sitemap', '<a href="' . esc_url($sitemap_url) . '" target="_blank" rel="noopener">Open</a>');
    echo myseo_render_stat_card('Google Verification', $google_verified ? 'Configured' : 'Missing');
    echo myseo_render_stat_card('Content Features', ($toc_enabled ? 'TOC On' : 'TOC Off') . ' / ' . ($breadcrumbs_enabled ? 'Breadcrumbs On' : 'Breadcrumbs Off'));
    echo myseo_render_stat_card('Tracked Keywords', $tracked_keywords);
    echo myseo_render_stat_card('Custom Schemas', $custom_schemas);
    echo myseo_render_stat_card('Email Reports', $scheduled_reports);
    echo myseo_render_stat_card('Orphan Pages', $orphan_pages);
    echo myseo_render_stat_card('Locations', $locations);
    echo myseo_render_stat_card('Podcasts', $podcasts);
    echo myseo_render_stat_card('Sync Logs', $sync_logs);
    echo '</div>';

    echo '<div class="myseo-card-grid" style="margin-top:20px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">';

    echo '<div class="myseo-card">';
    echo '<h2 style="margin-top:0;">Recent 404s</h2>';
    if ($recent_404) {
        echo '<ul style="margin-left:18px;list-style:disc;">';
        foreach ($recent_404 as $row) {
            echo '<li>' . esc_html($row->url) . ' <span style="color:#6c757d;">(' . esc_html($row->hits) . ')</span></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No 404s logged.</p>';
    }
    echo '</div>';

    echo '<div class="myseo-card">';
    echo '<h2 style="margin-top:0;">Top Redirects</h2>';
    if ($top_redir) {
        echo '<ul style="margin-left:18px;list-style:disc;">';
        foreach ($top_redir as $row) {
            echo '<li>' . esc_html($row->source) . ' <span style="color:#6c757d;">(' . esc_html($row->hits) . ')</span></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No redirects yet.</p>';
    }
    echo '</div>';

    echo '</div>';
    echo '</div>';
}

function myseo_render_stat_card($label, $value) {
    return '<div class="myseo-card">
        <div class="myseo-stat-label">' . $label . '</div>
        <div class="myseo-stat-value">' . $value . '</div>
    </div>';
}

function myseo_count_posts_missing_meta() {
    $post_types = get_post_types(array('public' => true), 'names');
    $posts = get_posts(array(
        'post_type' => $post_types,
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
    ));

    $missing = 0;
    foreach ($posts as $post_id) {
        $title = get_post_meta($post_id, '_myseo_title', true);
        $desc = get_post_meta($post_id, '_myseo_description', true);
        if ($title === '' || $desc === '') {
            $missing++;
        }
    }

    return $missing;
}

function myseo_count_orphan_pages() {
    $posts = get_posts(array(
        'post_type' => get_post_types(array('public' => true), 'names'),
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
    ));

    $count = 0;
    foreach ($posts as $post_id) {
        if (function_exists('myseo_is_orphan_page') && myseo_is_orphan_page($post_id)) {
            $count++;
        }
    }

    return $count;
}
