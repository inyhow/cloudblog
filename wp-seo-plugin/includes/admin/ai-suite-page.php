<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_render_ai_suite_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['myseo_ai_suite_nonce']) && wp_verify_nonce($_POST['myseo_ai_suite_nonce'], 'myseo_ai_suite')) {
        myseo_update_option('content_ai_credits', max(0, (int) $_POST['myseo_content_ai_credits']));
        myseo_update_option('content_ai_trial_enabled', isset($_POST['myseo_content_ai_trial_enabled']) ? 1 : 0);
        myseo_update_option('ai_search_tracker_enabled', isset($_POST['myseo_ai_search_tracker_enabled']) ? 1 : 0);
        echo '<div class="notice notice-success"><p>AI suite settings updated.</p></div>';
    }

    $credits = (int) myseo_get_option('content_ai_credits', 25);
    $trial = (bool) myseo_get_option('content_ai_trial_enabled', 1);
    $tracker = (bool) myseo_get_option('ai_search_tracker_enabled', 1);

    echo '<div class="wrap"><h1>AI Suite</h1>';
    echo '<div class="myseo-card"><h2>Free Content AI Trial With Credits</h2><form method="post">';
    wp_nonce_field('myseo_ai_suite', 'myseo_ai_suite_nonce');
    echo '<p><label><input type="checkbox" name="myseo_content_ai_trial_enabled" value="1"' . checked($trial, true, false) . ' /> Enable trial mode</label></p>';
    echo '<p><label>Credits <input type="number" min="0" name="myseo_content_ai_credits" value="' . esc_attr($credits) . '" class="small-text" /></label></p>';
    echo '<p><label><input type="checkbox" name="myseo_ai_search_tracker_enabled" value="1"' . checked($tracker, true, false) . ' /> Enable AI Search Traffic Tracker</label></p>';
    submit_button('Save AI Settings');
    echo '</form></div>';

    echo '<div class="myseo-card" style="margin-top:16px;"><h2>Capabilities</h2>';
    echo '<ul style="margin-left:18px;list-style:disc;">';
    echo '<li>Trial credit management for content AI workflows</li>';
    echo '<li>AI search traffic tracking flag stored for analytics ingestion</li>';
    echo '<li>Foundation for post-level AI optimization badges and suggestions</li>';
    echo '</ul></div></div>';
}
