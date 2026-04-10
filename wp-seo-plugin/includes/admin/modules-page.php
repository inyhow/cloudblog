<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_modules_page() {
    add_options_page(
        'MySEO Modules',
        'MySEO Modules',
        'manage_options',
        'myseo-modules',
        'myseo_render_modules_page'
    );
}

function myseo_render_modules_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['myseo_modules_nonce']) && wp_verify_nonce($_POST['myseo_modules_nonce'], 'myseo_modules_save')) {
        $input = isset($_POST['myseo_modules']) ? (array) $_POST['myseo_modules'] : array();
        $sanitized = myseo_sanitize_modules($input);
        update_option('myseo_modules', $sanitized);
        echo '<div class="notice notice-success"><p>Modules updated.</p></div>';
    }

    $modules = get_option('myseo_modules', array());
    $items = array(
        'meta' => array('name' => 'SEO Meta', 'desc' => 'Title, description, canonical, robots.'),
        'social' => array('name' => 'Social Meta', 'desc' => 'Open Graph and Twitter cards.'),
        'schema' => array('name' => 'Schema', 'desc' => 'JSON-LD structured data.'),
        'sitemap' => array('name' => 'Sitemap', 'desc' => 'XML sitemap at /myseo-sitemap.xml'),
        'redirections' => array('name' => 'Redirections', 'desc' => 'Manage 301/302/307/410 rules.'),
        'monitor' => array('name' => '404 Monitor', 'desc' => 'Track not-found URLs.'),
        'breadcrumbs' => array('name' => 'Breadcrumbs', 'desc' => 'Render frontend breadcrumb navigation and shortcode support.'),
        'toc' => array('name' => 'Table of Contents', 'desc' => 'Generate an in-page heading index for long content.'),
    );

    echo '<div class="wrap">';
    echo '<h1>MySEO Modules</h1>';
    echo '<p>Enable only the features you need.</p>';

    echo '<form method="post" class="myseo-live-form" data-myseo-live-scope="modules">';
    wp_nonce_field('myseo_modules_save', 'myseo_modules_nonce');
    echo '<div class="myseo-live-status" aria-live="polite"></div>';

    echo '<div class="myseo-card-grid">';
    foreach ($items as $key => $item) {
        $enabled = isset($modules[$key]) ? (bool) $modules[$key] : false;
        echo '<div class="myseo-card">';
        echo '<div style="display:flex;align-items:center;gap:8px;">';
        echo '<input type="checkbox" id="myseo_module_' . esc_attr($key) . '" name="myseo_modules[' . esc_attr($key) . ']" value="1"' . checked($enabled, true, false) . ' />';
        echo '<label for="myseo_module_' . esc_attr($key) . '" style="font-weight:600;">' . esc_html($item['name']) . '</label>';
        echo '</div>';
        echo '<p class="myseo-muted" style="margin:8px 0 0;">' . esc_html($item['desc']) . '</p>';
        echo '</div>';
    }
    echo '</div>';

    echo '<noscript><p style="margin-top:16px;">';
    submit_button('Save Modules', 'primary', 'submit', false);
    echo '</p></noscript>';
    echo '</form>';
    echo '</div>';
}
