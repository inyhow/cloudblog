<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once MYSEO_PLUGIN_DIR . 'includes/helpers.php';
require_once MYSEO_PLUGIN_DIR . 'includes/database.php';
require_once MYSEO_PLUGIN_DIR . 'includes/google-sync.php';
require_once MYSEO_PLUGIN_DIR . 'includes/modules.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/metabox.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/settings-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/redirections-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/monitor-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/redirections-advanced-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/import-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/modules-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/export-import-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/dashboard-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/group-pages.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/assets.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/integrations-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/google-sync-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/google-api-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/rank-tracker-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/history-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/performance-services-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/schema-builder-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/reports-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/schema-library-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/clients-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/csv-tools-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/content-overview-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/post-performance.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/analytics-overview-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/local-seo-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/woocommerce-seo-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/podcast-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/bulk-edit-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/setup-wizard-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/image-seo-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/index-status-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/integrations-extra-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/ai-suite-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/admin/trends-page.php';
require_once MYSEO_PLUGIN_DIR . 'includes/integrations/elementor.php';
require_once MYSEO_PLUGIN_DIR . 'includes/integrations/edd.php';
require_once MYSEO_PLUGIN_DIR . 'includes/integrations/divi.php';
require_once MYSEO_PLUGIN_DIR . 'includes/frontend/meta-output.php';
require_once MYSEO_PLUGIN_DIR . 'includes/frontend/schema-output.php';
require_once MYSEO_PLUGIN_DIR . 'includes/frontend/site-features.php';
require_once MYSEO_PLUGIN_DIR . 'includes/frontend/pro-features.php';
require_once MYSEO_PLUGIN_DIR . 'includes/sitemap/sitemap.php';

function myseo_bootstrap() {
    myseo_register_modules();
    myseo_register_google_sync();
    add_action('init', 'myseo_maybe_refresh_rewrites', 99);
    myseo_register_admin();
    myseo_register_frontend();
    myseo_register_site_features();
    myseo_register_pro_features();
    myseo_register_sitemap();
    myseo_register_redirections();
    myseo_register_404_monitor();
}

function myseo_activate() {
    myseo_install_tables();
    myseo_schedule_google_sync();
    myseo_register_sitemap_rewrite();
    flush_rewrite_rules();
}

function myseo_deactivate() {
    myseo_clear_google_sync();
    flush_rewrite_rules();
}

function myseo_maybe_refresh_rewrites() {
    global $wp_rewrite;

    if (!($wp_rewrite instanceof WP_Rewrite)) {
        return;
    }

    $rewrite_version = get_option('myseo_rewrite_version', '');
    if ($rewrite_version === MYSEO_PLUGIN_VERSION) {
        return;
    }
    myseo_register_sitemap_rewrite();
    flush_rewrite_rules(false);
    update_option('myseo_rewrite_version', MYSEO_PLUGIN_VERSION);
}
