<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_integrations_page() {
    add_submenu_page('myseo-dashboard', 'Integrations', 'Integrations', 'manage_options', 'myseo-integrations', 'myseo_render_integrations_page');
}

function myseo_render_integrations_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $ga4 = myseo_get_option('ga4_measurement_id', '');
    $gsc = myseo_get_option('gsc_property', '');
    $country = myseo_get_option('default_country_code', 'US');
    $fetch_frequency = (int) myseo_get_option('google_fetch_frequency', 1);
    $retention = (int) myseo_get_option('google_data_retention_days', 90);

    echo '<div class="wrap"><h1>Integrations</h1>';
    echo '<div class="myseo-card-grid">';
    echo '<div class="myseo-card"><h2>Google Analytics 4</h2><p class="myseo-muted">Measurement ID: ' . esc_html($ga4 ? $ga4 : 'Not configured') . '</p></div>';
    echo '<div class="myseo-card"><h2>Google Search Console</h2><p class="myseo-muted">Property: ' . esc_html($gsc ? $gsc : 'Not configured') . '</p></div>';
    echo '<div class="myseo-card"><h2>Data Collection</h2><p class="myseo-muted">Country: ' . esc_html($country) . '<br />Fetch every ' . esc_html($fetch_frequency) . ' day(s)<br />Retention: ' . esc_html($retention) . ' day(s)</p></div>';
    echo '</div>';
    echo '<p style="margin-top:16px;">Configure credentials on the Settings page. This screen is the operational summary for GA4, GSC, country-specific imports, and fetch cadence.</p>';
    echo '</div>';
}
