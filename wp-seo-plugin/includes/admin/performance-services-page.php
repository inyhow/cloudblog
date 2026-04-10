<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_render_performance_services_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['myseo_perf_services_nonce']) && wp_verify_nonce($_POST['myseo_perf_services_nonce'], 'myseo_perf_services')) {
        myseo_update_option('pagespeed_api_key', isset($_POST['myseo_pagespeed_api_key']) ? myseo_sanitize_text($_POST['myseo_pagespeed_api_key']) : '');
        myseo_update_option('adsense_publisher_id', isset($_POST['myseo_adsense_publisher_id']) ? myseo_sanitize_text($_POST['myseo_adsense_publisher_id']) : '');
        myseo_update_option('gsc_import_country', isset($_POST['myseo_gsc_import_country']) ? strtoupper(myseo_sanitize_text($_POST['myseo_gsc_import_country'])) : 'US');
        echo '<div class="notice notice-success"><p>Performance services settings saved.</p></div>';
    }

    echo '<div class="wrap"><h1>Performance Services</h1>';
    echo '<div class="myseo-card"><h2>PageSpeed / AdSense / Country Import</h2><form method="post">';
    wp_nonce_field('myseo_perf_services', 'myseo_perf_services_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>PageSpeed API Key</th><td><input type="text" name="myseo_pagespeed_api_key" value="' . esc_attr(myseo_get_option('pagespeed_api_key', '')) . '" class="large-text" /></td></tr>';
    echo '<tr><th>AdSense Publisher ID</th><td><input type="text" name="myseo_adsense_publisher_id" value="' . esc_attr(myseo_get_option('adsense_publisher_id', '')) . '" class="regular-text" /></td></tr>';
    echo '<tr><th>Import Country</th><td><input type="text" name="myseo_gsc_import_country" value="' . esc_attr(myseo_get_option('gsc_import_country', myseo_get_option('default_country_code', 'US'))) . '" class="small-text" /></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Services');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Coverage</h2>';
    echo '<ul style="margin-left:18px;list-style:disc;">';
    echo '<li>Track PageSpeed for each post and page via stored API key</li>';
    echo '<li>Store AdSense publisher details for earning history workflows</li>';
    echo '<li>Configure country-specific GSC and GA imports</li>';
    echo '</ul></div></div>';
}
