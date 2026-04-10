<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_render_trends_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['myseo_trends_nonce']) && wp_verify_nonce($_POST['myseo_trends_nonce'], 'myseo_trends')) {
        myseo_update_option('google_trends_keywords', isset($_POST['myseo_trends_keywords']) ? sanitize_textarea_field(wp_unslash($_POST['myseo_trends_keywords'])) : '');
        echo '<div class="notice notice-success"><p>Google Trends watchlist updated.</p></div>';
    }

    $keywords = myseo_get_option('google_trends_keywords', '');
    $country = myseo_get_option('default_country_code', 'US');

    echo '<div class="wrap"><h1>Google Trends Integration</h1>';
    echo '<div class="myseo-card"><h2>Trend Watchlist</h2><form method="post">';
    wp_nonce_field('myseo_trends', 'myseo_trends_nonce');
    echo '<p><textarea name="myseo_trends_keywords" rows="8" class="large-text" placeholder="seo plugin&#10;wordpress seo&#10;content ai">' . esc_textarea($keywords) . '</textarea></p>';
    echo '<p class="description">One keyword per line. Default country: ' . esc_html($country) . '.</p>';
    submit_button('Save Watchlist');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Usage</h2>';
    echo '<p>This page stores the trend watchlist and country scope used by your reporting workflows. External API fetching can be layered on top of this configuration without changing the data model.</p>';
    echo '</div></div>';
}
