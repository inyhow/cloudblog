<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_admin_enqueue_assets($hook) {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (strpos($hook, 'myseo') === false && strpos($page, 'myseo') !== 0) {
        return;
    }
    wp_enqueue_style('myseo-admin', MYSEO_PLUGIN_URL . 'assets/admin.css', array(), MYSEO_PLUGIN_VERSION);
    wp_enqueue_script('myseo-admin', MYSEO_PLUGIN_URL . 'assets/admin.js', array(), MYSEO_PLUGIN_VERSION, true);
    wp_localize_script(
        'myseo-admin',
        'myseoAdmin',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('myseo_admin_ajax'),
        )
    );
}

add_action('admin_enqueue_scripts', 'myseo_admin_enqueue_assets');

function myseo_ajax_save_setting() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'), 403);
    }
    check_ajax_referer('myseo_admin_ajax');

    $key = isset($_POST['key']) ? sanitize_key(wp_unslash($_POST['key'])) : '';
    if ($key === '') {
        wp_send_json_error(array('message' => 'Missing setting key.'), 400);
    }

    $value = isset($_POST['value']) ? wp_unslash($_POST['value']) : '';
    myseo_update_option($key, myseo_sanitize_setting_value($key, $value));

    wp_send_json_success(array('message' => 'Saved.'));
}

function myseo_ajax_save_module() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'), 403);
    }
    check_ajax_referer('myseo_admin_ajax');

    $module = isset($_POST['module']) ? sanitize_key(wp_unslash($_POST['module'])) : '';
    if ($module === '') {
        wp_send_json_error(array('message' => 'Missing module key.'), 400);
    }

    $modules = get_option('myseo_modules', array());
    if (!is_array($modules)) {
        $modules = array();
    }
    $modules[$module] = !empty($_POST['enabled']);
    update_option('myseo_modules', myseo_sanitize_modules($modules));

    wp_send_json_success(array('message' => 'Saved.'));
}

function myseo_ajax_get_google_auth_state() {
    $client_id = function_exists('myseo_get_google_oauth_client_id') ? myseo_get_google_oauth_client_id() : '';
    $redirect_uri = myseo_get_option('google_redirect_uri', admin_url('admin.php?page=myseo-google-api'));
    $auth_url = function_exists('myseo_get_google_auth_url') ? myseo_get_google_auth_url($client_id, $redirect_uri) : '';

    return array(
        'oauthMode' => myseo_get_option('google_oauth_mode', 'product'),
        'clientReady' => $client_id !== '',
        'authUrl' => $auth_url,
    );
}

function myseo_ajax_save_google_setting() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'), 403);
    }
    check_ajax_referer('myseo_admin_ajax');

    $key = isset($_POST['key']) ? sanitize_key(wp_unslash($_POST['key'])) : '';
    $allowed = array(
        'google_oauth_mode',
        'google_client_id',
        'google_client_secret',
        'google_redirect_uri',
        'google_access_token',
        'google_refresh_token',
    );
    if (!in_array($key, $allowed, true)) {
        wp_send_json_error(array('message' => 'Invalid Google setting key.'), 400);
    }

    $value = isset($_POST['value']) ? wp_unslash($_POST['value']) : '';
    myseo_update_option($key, myseo_sanitize_setting_value($key, $value));

    wp_send_json_success(
        array_merge(
            array('message' => 'Saved.'),
            myseo_ajax_get_google_auth_state()
        )
    );
}

add_action('wp_ajax_myseo_save_setting', 'myseo_ajax_save_setting');
add_action('wp_ajax_myseo_save_module', 'myseo_ajax_save_module');
add_action('wp_ajax_myseo_save_google_setting', 'myseo_ajax_save_google_setting');

function myseo_enqueue_frontend_assets() {
    wp_enqueue_style('myseo-frontend', MYSEO_PLUGIN_URL . 'assets/frontend.css', array(), MYSEO_PLUGIN_VERSION);
}

add_action('wp_enqueue_scripts', 'myseo_enqueue_frontend_assets');
