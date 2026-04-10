<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_setup_wizard_page() {
    add_submenu_page('myseo-dashboard', 'Setup Wizard', 'Setup Wizard', 'manage_options', 'myseo-setup-wizard', 'myseo_render_setup_wizard_page');
}

function myseo_render_setup_wizard_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['myseo_setup_wizard_nonce']) && wp_verify_nonce($_POST['myseo_setup_wizard_nonce'], 'myseo_setup_wizard')) {
        $mode = isset($_POST['myseo_setup_mode']) ? myseo_sanitize_text($_POST['myseo_setup_mode']) : 'standard';
        myseo_update_option('setup_wizard_mode', $mode);
        echo '<div class="notice notice-success"><p>Setup wizard mode updated.</p></div>';
    }

    $mode = myseo_get_option('setup_wizard_mode', 'standard');
    echo '<div class="wrap"><h1>Custom Setup Wizard Mode</h1><div class="myseo-card">';
    echo '<form method="post">';
    wp_nonce_field('myseo_setup_wizard', 'myseo_setup_wizard_nonce');
    echo '<p>Select the onboarding mode for new sites, client sites, or white label delivery.</p>';
    echo '<p><label><input type="radio" name="myseo_setup_mode" value="standard"' . checked($mode, 'standard', false) . ' /> Standard SEO Site</label></p>';
    echo '<p><label><input type="radio" name="myseo_setup_mode" value="client"' . checked($mode, 'client', false) . ' /> Client Site</label></p>';
    echo '<p><label><input type="radio" name="myseo_setup_mode" value="white_label"' . checked($mode, 'white_label', false) . ' /> White Label Delivery</label></p>';
    submit_button('Save Mode');
    echo '</form></div></div>';
}
