<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_modules() {
    $defaults = array(
        'meta' => true,
        'sitemap' => true,
        'social' => true,
        'schema' => true,
        'redirections' => true,
        'monitor' => true,
        'breadcrumbs' => true,
        'toc' => true,
        'analytics' => true,
        'rank_tracker' => true,
        'advanced_schema' => true,
        'news_sitemap' => true,
        'video_sitemap' => true,
        'image_seo' => true,
        'local_seo' => true,
        'woocommerce_seo' => true,
        'podcast' => true,
    );
    $modules = get_option('myseo_modules', $defaults);
    if (!is_array($modules)) {
        $modules = $defaults;
    }
    update_option('myseo_modules', $modules);
}

function myseo_module_enabled($key) {
    $modules = get_option('myseo_modules', array());
    return isset($modules[$key]) ? (bool) $modules[$key] : false;
}
