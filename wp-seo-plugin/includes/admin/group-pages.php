<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_render_google_suite_page() {
    global $wpdb;
    $active_tab = isset($_GET['tab']) ? myseo_sanitize_text($_GET['tab']) : 'overview';
    $tabs = array(
        'overview' => 'Overview',
        'api' => 'API',
        'sync' => 'Sync',
        'trends' => 'Trends',
        'performance' => 'Performance',
    );

    $ga4_measurement = myseo_get_option('ga4_measurement_id', '');
    $ga4_property = myseo_get_option('ga4_property_id', '');
    $gsc_property = myseo_get_option('gsc_property', '');
    $country = myseo_get_option('default_country_code', 'US');
    $fetch_frequency = (int) myseo_get_option('google_fetch_frequency', 1);
    $logs = $wpdb->get_results('SELECT * FROM ' . myseo_get_table_name('sync_logs') . ' ORDER BY synced_at DESC, id DESC LIMIT 10');

    echo '<div class="wrap myseo-suite-page"><h1>Google Suite</h1>';
    echo myseo_render_suite_tabs('myseo-google-suite', $tabs, $active_tab);
    echo '<div class="myseo-suite-panel">';

    if ($active_tab === 'overview') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_metric_card('GA4 Measurement', $ga4_measurement ? $ga4_measurement : 'Not configured');
        echo myseo_group_metric_card('GA4 Property', $ga4_property ? $ga4_property : 'Not configured');
        echo myseo_group_metric_card('GSC Property', $gsc_property ? $gsc_property : 'Not configured');
        echo myseo_group_metric_card('Default Country', $country);
        echo myseo_group_metric_card('Fetch Frequency', $fetch_frequency . ' day(s)');
        echo myseo_group_metric_card('Sync Logs', (string) $wpdb->get_var('SELECT COUNT(*) FROM ' . myseo_get_table_name('sync_logs')));
        echo '</div>';
        echo '<div class="myseo-card" style="margin-top:16px;"><h2>Quick Actions</h2><p><a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=myseo-google-api')) . '">Open API Auth</a> <a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=myseo-google-sync')) . '">Open Sync Console</a></p></div>';
    } elseif ($active_tab === 'api') {
        echo '<div class="myseo-card"><h2>API Access</h2>';
        echo '<p class="myseo-muted">Client ID, secret, redirect URI, access token, refresh token, and automatic authorization code exchange are managed from the dedicated Google API page.</p>';
        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=myseo-google-api')) . '">Open Google API Page</a></p>';
        echo '</div>';
    } elseif ($active_tab === 'sync') {
        echo '<div class="myseo-card"><h2>Recent Sync Logs</h2><table class="widefat striped"><thead><tr><th>Provider</th><th>Type</th><th>Status</th><th>Country</th><th>Time</th></tr></thead><tbody>';
        if ($logs) {
            foreach ($logs as $log) {
                echo '<tr><td>' . esc_html($log->provider) . '</td><td>' . esc_html($log->sync_type) . '</td><td>' . esc_html($log->status) . '</td><td>' . esc_html($log->country_code) . '</td><td>' . esc_html($log->synced_at) . '</td></tr>';
            }
        } else {
            echo '<tr><td colspan="5">No sync logs yet.</td></tr>';
        }
        echo '</tbody></table><p style="margin-top:12px;"><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=myseo-google-sync')) . '">Open Sync Console</a></p></div>';
    } elseif ($active_tab === 'trends') {
        echo '<div class="myseo-card"><h2>Google Trends</h2><p class="myseo-muted">Keyword watchlist, country scope, and trend workflows live in the Trends page.</p><p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=myseo-google-trends')) . '">Open Trends</a></p></div>';
    } elseif ($active_tab === 'performance') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Performance Services', 'PageSpeed, AdSense, and import country settings.', admin_url('admin.php?page=myseo-performance-services'));
        echo myseo_group_link_card('Analytics Overview', 'Posts, clicks, positions, PageSpeed, and AI traffic.', admin_url('admin.php?page=myseo-analytics-overview'));
        echo myseo_group_link_card('Index Status', 'Track indexed, discovered, and excluded URLs.', admin_url('admin.php?page=myseo-index-status'));
        echo '</div>';
    }

    echo '</div></div>';
}

function myseo_render_content_suite_page() {
    global $wpdb;
    $active_tab = isset($_GET['tab']) ? myseo_sanitize_text($_GET['tab']) : 'overview';
    $tabs = array(
        'overview' => 'Overview',
        'keywords' => 'Keywords',
        'history' => 'History',
        'optimization' => 'Optimization',
        'media' => 'Media',
    );

    echo '<div class="wrap myseo-suite-page"><h1>Content Suite</h1>';
    echo myseo_render_suite_tabs('myseo-content-suite', $tabs, $active_tab);
    echo '<div class="myseo-suite-panel">';

    if ($active_tab === 'overview') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_metric_card('Tracked Keywords', (string) $wpdb->get_var('SELECT COUNT(*) FROM ' . myseo_get_table_name('keywords')));
        echo myseo_group_metric_card('Orphan Pages', (string) myseo_count_orphan_pages());
        echo myseo_group_metric_card('Posts Missing SEO', (string) myseo_count_posts_missing_meta());
        echo myseo_group_metric_card('Image SEO', myseo_module_enabled('image_seo') ? 'Enabled' : 'Disabled');
        echo '</div>';
    } elseif ($active_tab === 'keywords') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Rank Tracker', 'Tracked keywords and winners/losers.', admin_url('admin.php?page=myseo-rank-tracker'));
        echo myseo_group_link_card('Analytics Overview', 'Winning and losing posts with performance metrics.', admin_url('admin.php?page=myseo-analytics-overview'));
        echo '</div>';
    } elseif ($active_tab === 'history') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('History', 'Keyword and post performance history.', admin_url('admin.php?page=myseo-history'));
        echo myseo_group_link_card('Content Overview', 'SEO overview, filters, and orphan pages.', admin_url('admin.php?page=myseo-content-overview'));
        echo '</div>';
    } elseif ($active_tab === 'optimization') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Bulk Edit', 'Bulk and quick editing tools.', admin_url('admin.php?page=myseo-bulk-edit'));
        echo myseo_group_link_card('Content Overview', 'SEO overview, filters, and orphan pages.', admin_url('admin.php?page=myseo-content-overview'));
        echo '</div>';
    } elseif ($active_tab === 'media') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Image SEO', 'Alt/title/caption automation and replacement.', admin_url('admin.php?page=myseo-image-seo'));
        echo myseo_group_link_card('CSV Tools', 'Import and export operational SEO data.', admin_url('admin.php?page=myseo-csv-tools'));
        echo '</div>';
    }

    echo '</div></div>';
}

function myseo_render_schema_suite_page() {
    global $wpdb;
    $active_tab = isset($_GET['tab']) ? myseo_sanitize_text($_GET['tab']) : 'overview';
    $tabs = array(
        'overview' => 'Overview',
        'library' => 'Library',
        'builder' => 'Builder',
        'media' => 'Podcast & Video',
        'builders' => 'Page Builders',
    );

    echo '<div class="wrap myseo-suite-page"><h1>Schema Suite</h1>';
    echo myseo_render_suite_tabs('myseo-schema-suite', $tabs, $active_tab);
    echo '<div class="myseo-suite-panel">';

    if ($active_tab === 'overview') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_metric_card('Custom Schemas', (string) $wpdb->get_var('SELECT COUNT(*) FROM ' . myseo_get_table_name('custom_schemas')));
        echo myseo_group_metric_card('Podcasts', (string) $wpdb->get_var('SELECT COUNT(*) FROM ' . myseo_get_table_name('podcasts') . ' WHERE is_active = 1'));
        echo myseo_group_metric_card('Schema Mode', myseo_module_enabled('advanced_schema') ? 'Advanced' : 'Basic');
        echo '</div>';
    } elseif ($active_tab === 'library') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Schema Library', 'Imported and custom JSON-LD schemas.', admin_url('admin.php?page=myseo-schema-library'));
        echo myseo_group_link_card('Import Schema', 'Import schema from any website.', admin_url('admin.php?page=myseo-schema-library'));
        echo '</div>';
    } elseif ($active_tab === 'builder') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Schema Builder', 'Guided builder for structured data.', admin_url('admin.php?page=myseo-schema-builder'));
        echo myseo_group_link_card('Google Validation', 'Validate output using Google rich results tools.', admin_url('admin.php?page=myseo-schema-library'));
        echo '</div>';
    } elseif ($active_tab === 'media') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Podcast', 'Podcast module and podcast schema.', admin_url('admin.php?page=myseo-podcast'));
        echo myseo_group_link_card('Schema Library', 'Video, speakable, and media schemas.', admin_url('admin.php?page=myseo-schema-library'));
        echo '</div>';
    } elseif ($active_tab === 'builders') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Builder Integrations', 'Elementor, Divi, and EDD integration status.', admin_url('admin.php?page=myseo-builder-integrations'));
        echo '</div>';
    }

    echo '</div></div>';
}

function myseo_render_ops_suite_page() {
    global $wpdb;
    $active_tab = isset($_GET['tab']) ? myseo_sanitize_text($_GET['tab']) : 'overview';
    $tabs = array(
        'overview' => 'Overview',
        'redirects' => 'Redirects',
        'reports' => 'Reports',
        'migration' => 'Import & Export',
        'clients' => 'Clients',
    );

    echo '<div class="wrap myseo-suite-page"><h1>Operations</h1>';
    echo myseo_render_suite_tabs('myseo-ops-suite', $tabs, $active_tab);
    echo '<div class="myseo-suite-panel">';

    if ($active_tab === 'overview') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_metric_card('Redirect Rules', (string) $wpdb->get_var('SELECT COUNT(*) FROM ' . myseo_get_table_name('redirections')));
        echo myseo_group_metric_card('404 Logs', (string) $wpdb->get_var('SELECT COUNT(*) FROM ' . myseo_get_table_name('404_logs')));
        echo myseo_group_metric_card('Scheduled Reports', (string) $wpdb->get_var('SELECT COUNT(*) FROM ' . myseo_get_table_name('email_reports') . ' WHERE is_active = 1'));
        echo myseo_group_metric_card('Clients', (string) $wpdb->get_var('SELECT COUNT(*) FROM ' . myseo_get_table_name('clients')));
        echo '</div>';
    } elseif ($active_tab === 'redirects') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Redirections', 'Core redirect manager.', admin_url('admin.php?page=myseo-redirections'));
        echo myseo_group_link_card('Advanced Redirections', '.htaccess sync and 404 conversions.', admin_url('admin.php?page=myseo-advanced-redirections'));
        echo myseo_group_link_card('404 Monitor', 'Monitor broken URLs.', admin_url('admin.php?page=myseo-404-monitor'));
        echo '</div>';
    } elseif ($active_tab === 'reports') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Email Reports', 'Scheduled and white-label reports.', admin_url('admin.php?page=myseo-email-reports'));
        echo myseo_group_link_card('CSV Tools', 'CSV import/export and logs.', admin_url('admin.php?page=myseo-csv-tools'));
        echo '</div>';
    } elseif ($active_tab === 'migration') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Import', 'Import from Yoast and Rank Math.', admin_url('admin.php?page=myseo-import'));
        echo myseo_group_link_card('Export/Import', 'Settings export/import.', admin_url('admin.php?page=myseo-export-import'));
        echo myseo_group_link_card('CSV Tools', 'CSV import/export and logs.', admin_url('admin.php?page=myseo-csv-tools'));
        echo '</div>';
    } elseif ($active_tab === 'clients') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Clients', 'Client sites and branding.', admin_url('admin.php?page=myseo-clients'));
        echo myseo_group_link_card('Email Reports', 'White-label and client reporting.', admin_url('admin.php?page=myseo-email-reports'));
        echo '</div>';
    }

    echo '</div></div>';
}

function myseo_render_commerce_suite_page() {
    global $wpdb;
    $active_tab = isset($_GET['tab']) ? myseo_sanitize_text($_GET['tab']) : 'overview';
    $tabs = array(
        'overview' => 'Overview',
        'local' => 'Local SEO',
        'commerce' => 'Commerce',
        'setup' => 'Setup',
    );

    echo '<div class="wrap myseo-suite-page"><h1>Commerce & Local</h1>';
    echo myseo_render_suite_tabs('myseo-commerce-suite', $tabs, $active_tab);
    echo '<div class="myseo-suite-panel">';

    if ($active_tab === 'overview') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_metric_card('Locations', (string) $wpdb->get_var('SELECT COUNT(*) FROM ' . myseo_get_table_name('locations')));
        echo myseo_group_metric_card('Local SEO', myseo_module_enabled('local_seo') ? 'Enabled' : 'Disabled');
        echo myseo_group_metric_card('WooCommerce SEO', myseo_module_enabled('woocommerce_seo') ? 'Enabled' : 'Disabled');
        echo '</div>';
    } elseif ($active_tab === 'local') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Local SEO', 'Locations, local schema, and blocks.', admin_url('admin.php?page=myseo-local-seo'));
        echo myseo_group_link_card('Settings', 'Local business type and business settings.', admin_url('admin.php?page=myseo-settings'));
        echo '</div>';
    } elseif ($active_tab === 'commerce') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('WooCommerce SEO', 'Product SEO and schema support.', admin_url('admin.php?page=myseo-woocommerce-seo'));
        echo myseo_group_link_card('Builder Integrations', 'EDD and builder compatibility.', admin_url('admin.php?page=myseo-builder-integrations'));
        echo '</div>';
    } elseif ($active_tab === 'setup') {
        echo '<div class="myseo-card-grid">';
        echo myseo_group_link_card('Setup Wizard', 'Standard, client, and white-label setup modes.', admin_url('admin.php?page=myseo-setup-wizard'));
        echo myseo_group_link_card('Clients', 'Client site configuration and branding.', admin_url('admin.php?page=myseo-clients'));
        echo '</div>';
    }

    echo '</div></div>';
}

function myseo_group_link_card($title, $description, $url) {
    return '<div class="myseo-card"><h2>' . esc_html($title) . '</h2><p class="myseo-muted">' . esc_html($description) . '</p><p><a class="button button-secondary" href="' . esc_url($url) . '">Open</a></p></div>';
}

function myseo_group_metric_card($title, $value) {
    return '<div class="myseo-card"><div class="myseo-stat-label">' . esc_html($title) . '</div><div class="myseo-stat-value">' . esc_html($value) . '</div></div>';
}

function myseo_render_suite_tabs($page_slug, $tabs, $active_tab) {
    $html = '<nav class="myseo-tabs">';
    foreach ($tabs as $slug => $label) {
        $class = $slug === $active_tab ? 'myseo-tab is-active' : 'myseo-tab';
        $url = add_query_arg(
            array(
                'page' => $page_slug,
                'tab' => $slug,
            ),
            admin_url('admin.php')
        );
        $html .= '<a class="' . esc_attr($class) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }
    $html .= '</nav>';
    return $html;
}
