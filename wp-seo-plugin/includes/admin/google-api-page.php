<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_render_google_api_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['myseo_google_connected']) && $_GET['myseo_google_connected'] === '1') {
        echo '<div class="notice notice-success"><p>Google account connected successfully.</p></div>';
    }
    if (isset($_GET['myseo_google_error']) && $_GET['myseo_google_error'] !== '') {
        echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash($_GET['myseo_google_error'])) . '</p></div>';
    }
    if (isset($_GET['code']) && isset($_GET['state'])) {
        $state = sanitize_text_field(wp_unslash($_GET['state']));
        $expected_state = (string) get_transient('myseo_google_oauth_state');
        if ($expected_state !== '' && hash_equals($expected_state, $state)) {
            delete_transient('myseo_google_oauth_state');
            $result = myseo_exchange_google_auth_code(sanitize_textarea_field(wp_unslash($_GET['code'])));
            if (is_wp_error($result)) {
                myseo_log_sync_event('google', 'oauth_exchange', myseo_get_option('default_country_code', 'US'), 'failed', $result->get_error_message());
                wp_safe_redirect(
                    add_query_arg(
                        array(
                            'page' => 'myseo-google-api',
                            'myseo_google_error' => rawurlencode($result->get_error_message()),
                        ),
                        admin_url('admin.php')
                    )
                );
                exit;
            }
            myseo_maybe_bootstrap_google_services();
            myseo_log_sync_event('google', 'oauth_exchange', myseo_get_option('default_country_code', 'US'), 'success', 'Tokens exchanged successfully.');
            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page' => 'myseo-google-api',
                        'myseo_google_connected' => 1,
                    ),
                    admin_url('admin.php')
                )
            );
            exit;
        } else {
            echo '<div class="notice notice-error"><p>Google OAuth state validation failed.</p></div>';
        }
    }

    if (isset($_POST['myseo_google_api_nonce']) && wp_verify_nonce($_POST['myseo_google_api_nonce'], 'myseo_google_api')) {
        $redirect_uri = myseo_get_option('google_redirect_uri', admin_url('admin.php?page=myseo-google-api'));
        myseo_update_option('google_client_id', isset($_POST['myseo_google_client_id']) ? myseo_sanitize_text($_POST['myseo_google_client_id']) : '');
        myseo_update_option('google_client_secret', isset($_POST['myseo_google_client_secret']) ? myseo_sanitize_text($_POST['myseo_google_client_secret']) : '');
        myseo_update_option('google_redirect_uri', isset($_POST['myseo_google_redirect_uri']) ? esc_url_raw(wp_unslash($_POST['myseo_google_redirect_uri'])) : $redirect_uri);
        myseo_update_option('google_access_token', isset($_POST['myseo_google_access_token']) ? sanitize_textarea_field(wp_unslash($_POST['myseo_google_access_token'])) : '');
        myseo_update_option('google_refresh_token', isset($_POST['myseo_google_refresh_token']) ? sanitize_textarea_field(wp_unslash($_POST['myseo_google_refresh_token'])) : '');
        echo '<div class="notice notice-success"><p>Google API credentials saved.</p></div>';
    }

    if (isset($_POST['myseo_google_mode_nonce']) && wp_verify_nonce($_POST['myseo_google_mode_nonce'], 'myseo_google_mode')) {
        $oauth_mode = isset($_POST['myseo_google_oauth_mode']) ? myseo_sanitize_text($_POST['myseo_google_oauth_mode']) : 'product';
        myseo_update_option('google_oauth_mode', $oauth_mode);
        echo '<div class="notice notice-success"><p>Google OAuth mode updated.</p></div>';
    }

    if (isset($_POST['myseo_google_disconnect_nonce']) && wp_verify_nonce($_POST['myseo_google_disconnect_nonce'], 'myseo_google_disconnect')) {
        myseo_update_option('google_access_token', '');
        myseo_update_option('google_refresh_token', '');
        myseo_update_option('google_access_token_expires_at', '');
        myseo_update_option('google_authorization_code', '');
        echo '<div class="notice notice-success"><p>Google account disconnected.</p></div>';
    }

    if (isset($_POST['myseo_google_exchange_nonce']) && wp_verify_nonce($_POST['myseo_google_exchange_nonce'], 'myseo_google_exchange')) {
        $code = isset($_POST['myseo_google_auth_code']) ? sanitize_textarea_field(wp_unslash($_POST['myseo_google_auth_code'])) : '';
        if ($code !== '') {
            myseo_update_option('google_authorization_code', $code);
            $result = myseo_exchange_google_auth_code($code);
            if (is_wp_error($result)) {
                myseo_log_sync_event('google', 'oauth_exchange', myseo_get_option('default_country_code', 'US'), 'failed', $result->get_error_message());
                echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                myseo_log_sync_event('google', 'oauth_exchange', myseo_get_option('default_country_code', 'US'), 'success', 'Tokens exchanged successfully.');
                echo '<div class="notice notice-success"><p>Authorization code exchanged successfully. Access token saved.</p></div>';
            }
        }
    }

    $oauth_mode = myseo_get_option('google_oauth_mode', 'product');
    $redirect_uri = myseo_get_option('google_redirect_uri', admin_url('admin.php?page=myseo-google-api'));
    $client_id = myseo_get_google_oauth_client_id();
    $redirect_uri = myseo_get_option('google_redirect_uri', $redirect_uri);
    $auth_url = myseo_get_google_auth_url($client_id, $redirect_uri);

    $is_connected = myseo_get_option('google_refresh_token', '') !== '' || myseo_get_option('google_access_token', '') !== '';

    echo '<div class="wrap"><h1>Google API</h1>';

    echo '<div class="myseo-card"><h2>Mode</h2><form method="post" class="myseo-live-form" data-myseo-live-scope="google">';
    wp_nonce_field('myseo_google_mode', 'myseo_google_mode_nonce');
    echo '<div class="myseo-live-status" aria-live="polite"></div>';
    echo '<p><select name="myseo_google_oauth_mode">';
    echo '<option value="product"' . selected($oauth_mode, 'product', false) . '>Product Mode</option>';
    echo '<option value="developer"' . selected($oauth_mode, 'developer', false) . '>Developer Mode</option>';
    echo '</select> ';
    submit_button('Save Mode', 'secondary', '', false);
    echo '</p><p class="myseo-muted">Product mode only shows connect buttons. Developer mode exposes Client ID and Client Secret fields.</p>';
    echo '</form></div>';

    echo '<div class="myseo-card" id="myseo-google-credentials-card" style="margin-top:16px;' . ($oauth_mode === 'developer' ? '' : 'display:none;') . '"><h2>OAuth Credentials</h2><form method="post" class="myseo-live-form" data-myseo-live-scope="google">';
    wp_nonce_field('myseo_google_api', 'myseo_google_api_nonce');
    echo '<div class="myseo-live-status" aria-live="polite"></div>';
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>Client ID</th><td><input type="text" name="myseo_google_client_id" value="' . esc_attr(myseo_get_option('google_client_id', '')) . '" class="large-text" /></td></tr>';
    echo '<tr><th>Client Secret</th><td><input type="text" name="myseo_google_client_secret" value="' . esc_attr(myseo_get_option('google_client_secret', '')) . '" class="large-text" /></td></tr>';
    echo '<tr><th>Redirect URI</th><td><input type="url" name="myseo_google_redirect_uri" value="' . esc_attr($redirect_uri) . '" class="large-text" /></td></tr>';
    echo '<tr><th>Access Token</th><td><textarea name="myseo_google_access_token" rows="4" class="large-text code">' . esc_textarea(myseo_get_option('google_access_token', '')) . '</textarea></td></tr>';
    echo '<tr><th>Refresh Token</th><td><textarea name="myseo_google_refresh_token" rows="4" class="large-text code">' . esc_textarea(myseo_get_option('google_refresh_token', '')) . '</textarea></td></tr>';
    echo '</tbody></table>';
    submit_button('Save Credentials');
    echo '</form></div>';

    echo '<div class="myseo-card" id="myseo-google-connection-card" style="margin-top:16px;' . ($oauth_mode === 'product' ? '' : 'display:none;') . '"><h2>Google Connection</h2>';
    echo '<p class="myseo-muted">Connect this site to Google Search Console and Google Analytics with one click. No manual token fields are shown in product mode.</p>';
    echo '<div class="myseo-card-grid">';
    echo myseo_group_metric_card('Connection Status', $is_connected ? 'Connected' : 'Not connected');
    echo myseo_group_metric_card('Search Console', myseo_get_option('gsc_property', '') ? 'Configured' : 'Not configured');
    echo myseo_group_metric_card('GA4 Property', myseo_get_option('ga4_property_id', '') ? 'Configured' : 'Not configured');
    echo '</div>';
    echo '</div>';

    echo '<div class="myseo-card" id="myseo-google-auth-card" style="margin-top:16px;"><h2>Authorization</h2>';
    echo '<div id="myseo-google-auth-ready"' . ($client_id !== '' ? '' : ' style="display:none;"') . '>';
    echo '<p><a id="myseo-google-auth-button" class="button button-secondary" href="' . esc_url($auth_url) . '" target="_blank" rel="noopener" data-myseo-google-auth="1">' . ($is_connected ? 'Reconnect Google Services' : 'Connect Google Services') . '</a></p>';
    echo '</div>';
    echo '<div id="myseo-google-auth-missing"' . ($client_id === '' ? '' : ' style="display:none;"') . '>';
    echo '<p>Google OAuth client is not configured yet.</p>';
    echo '<p class="myseo-muted">If you are testing locally, switch this page to Developer Mode and fill Client ID / Secret. If this is a product build, define `MYSEO_GOOGLE_CLIENT_ID` and `MYSEO_GOOGLE_CLIENT_SECRET` in your server config.</p>';
    echo '</div>';
    echo '<form method="post" id="myseo-google-manual-code-form" style="margin-top:16px;' . ($oauth_mode === 'developer' ? '' : 'display:none;') . '">';
    wp_nonce_field('myseo_google_exchange', 'myseo_google_exchange_nonce');
    echo '<p><textarea name="myseo_google_auth_code" rows="4" class="large-text code" placeholder="Paste the Google authorization code here"></textarea></p>';
    submit_button('Manual Authorization Code Fallback', 'secondary');
    echo '</form>';
    if ($is_connected) {
        echo '<form method="post" style="margin-top:16px;">';
        wp_nonce_field('myseo_google_disconnect', 'myseo_google_disconnect_nonce');
        submit_button('Disconnect Google Account', 'delete');
        echo '</form>';
    }
    echo '<p class="myseo-muted">Recommended flow: click Connect Google Services, finish consent, and let this page automatically exchange tokens and provision Search Console / GA4 resources when possible.</p>';
    echo '</div></div>';
}

function myseo_get_google_oauth_client_id() {
    if (defined('MYSEO_GOOGLE_CLIENT_ID') && MYSEO_GOOGLE_CLIENT_ID) {
        return MYSEO_GOOGLE_CLIENT_ID;
    }
    return myseo_get_option('google_client_id', '');
}

function myseo_get_google_auth_url($client_id = '', $redirect_uri = '') {
    if ($client_id === '') {
        $client_id = myseo_get_google_oauth_client_id();
    }
    if ($redirect_uri === '') {
        $redirect_uri = myseo_get_option('google_redirect_uri', admin_url('admin.php?page=myseo-google-api'));
    }

    if ($client_id === '') {
        return '';
    }

    $scopes = array(
        'https://www.googleapis.com/auth/webmasters.readonly',
        'https://www.googleapis.com/auth/analytics.readonly',
        'https://www.googleapis.com/auth/siteverification',
        'https://www.googleapis.com/auth/webmasters',
        'https://www.googleapis.com/auth/analytics.edit',
    );
    $state = wp_generate_password(24, false, false);
    set_transient('myseo_google_oauth_state', $state, 15 * MINUTE_IN_SECONDS);

    return 'https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=' . rawurlencode($client_id) . '&redirect_uri=' . rawurlencode($redirect_uri) . '&scope=' . rawurlencode(implode(' ', $scopes)) . '&access_type=offline&prompt=consent&state=' . rawurlencode($state);
}
