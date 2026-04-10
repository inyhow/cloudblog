<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_render_integrations_extra_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $elementor = did_action('elementor/loaded') ? 'Detected' : 'Not detected';
    $divi = defined('ET_BUILDER_VERSION') ? 'Detected' : 'Not detected';
    $edd = class_exists('Easy_Digital_Downloads') ? 'Detected' : 'Not detected';

    echo '<div class="wrap"><h1>Builder and Store Integrations</h1><div class="myseo-card-grid">';
    echo '<div class="myseo-card"><h2>Elementor</h2><p class="myseo-muted">Status: ' . esc_html($elementor) . '</p><p>Ready for breadcrumbs widget, FAQ accordion mapping, and post SEO panel integration.</p><p><label><input type="checkbox" checked disabled /> Dedicated breadcrumbs widget foundation</label></p></div>';
    echo '<div class="myseo-card"><h2>Divi</h2><p class="myseo-muted">Status: ' . esc_html($divi) . '</p><p>Ready for accordion to FAQ Schema mapping and content analysis compatibility.</p><p><label><input type="checkbox" checked disabled /> Divi FAQ schema bridge</label></p></div>';
    echo '<div class="myseo-card"><h2>EDD SEO</h2><p class="myseo-muted">Status: ' . esc_html($edd) . '</p><p>Foundation ready for product SEO fields, schema, and archive enhancements on downloads.</p><p><label><input type="checkbox" checked disabled /> EDD SEO support layer</label></p></div>';
    echo '</div></div>';
}
