<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_woocommerce_seo_page() {
    add_submenu_page('myseo-dashboard', 'WooCommerce SEO', 'WooCommerce SEO', 'manage_options', 'myseo-woocommerce-seo', 'myseo_render_woocommerce_seo_page');
}

function myseo_render_woocommerce_seo_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $active = class_exists('WooCommerce');
    echo '<div class="wrap"><h1>WooCommerce SEO PRO</h1>';
    echo '<div class="myseo-card">';
    if ($active) {
        $products = wp_count_posts('product');
        echo '<p>WooCommerce is active. Published products: ' . esc_html($products ? $products->publish : 0) . '</p>';
        echo '<p>MySEO will enrich product schema, product social tags, and breadcrumb support for product pages.</p>';
    } else {
        echo '<p>WooCommerce is not active in this WordPress site yet. The module foundation is ready and will activate product-specific SEO enhancements when WooCommerce is available.</p>';
    }
    echo '<p class="myseo-muted">Covered scope: Product schema, price and availability schema, product breadcrumbs, and SEO status overview.</p>';
    echo '</div></div>';
}
