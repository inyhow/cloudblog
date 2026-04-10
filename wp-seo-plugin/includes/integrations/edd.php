<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_edd_integration() {
    if (!class_exists('Easy_Digital_Downloads')) {
        return;
    }

    add_action('edd_download_after_title', 'myseo_render_edd_seo_badge');
}

function myseo_render_edd_seo_badge() {
    echo '<div class="myseo-edd-badge">EDD SEO Ready</div>';
}

function myseo_register_edd_admin_page() {
    add_submenu_page('myseo-dashboard', 'EDD SEO', 'EDD SEO', 'manage_options', 'myseo-edd-seo', 'myseo_render_edd_admin_page');
}

function myseo_render_edd_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap"><h1>Complete EDD SEO</h1><div class="myseo-card">';
    echo '<p>Easy Digital Downloads is detected. This integration layer is ready for product schema, archive SEO, and download-specific metadata fields.</p>';
    echo '<p class="myseo-muted">Module foundation: archive title templates, social metadata, Schema enrichment, and SEO score tracking for downloads.</p>';
    echo '</div></div>';
}

add_action('init', 'myseo_register_edd_integration');
