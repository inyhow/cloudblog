<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_google_sync() {
    add_action('myseo_google_data_sync', 'myseo_run_scheduled_google_sync');
    add_action('myseo_email_report_send', 'myseo_run_scheduled_email_reports');
}

function myseo_schedule_google_sync() {
    if (!wp_next_scheduled('myseo_google_data_sync')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'myseo_google_data_sync');
    }
    if (!wp_next_scheduled('myseo_email_report_send')) {
        wp_schedule_event(time() + 2 * HOUR_IN_SECONDS, 'daily', 'myseo_email_report_send');
    }
}

function myseo_clear_google_sync() {
    $timestamp = wp_next_scheduled('myseo_google_data_sync');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'myseo_google_data_sync');
    }
    $report_timestamp = wp_next_scheduled('myseo_email_report_send');
    if ($report_timestamp) {
        wp_unschedule_event($report_timestamp, 'myseo_email_report_send');
    }
}

function myseo_run_scheduled_google_sync() {
    $country = myseo_get_option('default_country_code', 'US');
    $access_token = myseo_get_google_access_token();
    if (!$access_token) {
        myseo_log_sync_event('google', 'scheduled_fetch', $country, 'failed', 'No valid Google access token available.');
        return;
    }

    $gsc_property = myseo_get_option('gsc_property', '');
    if ($gsc_property) {
        myseo_fetch_gsc_page_metrics($access_token, $gsc_property, $country);
        myseo_fetch_gsc_keyword_metrics($access_token, $gsc_property, $country);
    }

    $ga4_property_id = myseo_get_option('ga4_property_id', '');
    if ($ga4_property_id) {
        myseo_fetch_ga4_page_metrics($access_token, $ga4_property_id, $country);
    }
    myseo_log_sync_event('google', 'scheduled_fetch', $country, 'success', 'Scheduled sync executed.');
}

function myseo_maybe_bootstrap_google_services() {
    $access_token = myseo_get_google_access_token();
    if (!$access_token) {
        return;
    }

    $site_url = home_url('/');
    $gsc_property = myseo_get_option('gsc_property', '');
    if ($gsc_property === '') {
        $created_property = myseo_ensure_search_console_property($access_token, $site_url);
        if (!is_wp_error($created_property) && $created_property !== '') {
            myseo_update_option('gsc_property', $created_property);
        }
    }

    $ga4_property_id = myseo_get_option('ga4_property_id', '');
    if ($ga4_property_id === '') {
        $ga4_result = myseo_create_ga4_property_and_stream($access_token, $site_url);
        if (!is_wp_error($ga4_result) && !empty($ga4_result['property_id'])) {
            myseo_update_option('ga4_property_id', $ga4_result['property_id']);
        }
        if (!is_wp_error($ga4_result) && !empty($ga4_result['measurement_id'])) {
            myseo_update_option('ga4_measurement_id', $ga4_result['measurement_id']);
        }
    }
}

function myseo_run_scheduled_email_reports() {
    global $wpdb;
    $table = myseo_get_table_name('email_reports');
    $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE is_active = 1");
    $now = current_time('timestamp');

    foreach ($rows as $row) {
        $next = $row->next_send_at ? strtotime($row->next_send_at) : 0;
        if ($next > $now) {
            continue;
        }

        $subject = ($row->include_white_label ? myseo_get_option('white_label_brand', get_bloginfo('name')) : 'MySEO') . ' SEO Report';
        $body = "Scheduled SEO report placeholder.\n\nReport: {$row->report_name}\nGenerated: " . current_time('mysql');
        wp_mail($row->recipient_email, $subject, $body);

        $wpdb->update($table, array(
            'last_sent_at' => current_time('mysql'),
            'next_send_at' => gmdate('Y-m-d H:i:s', strtotime('+' . (int) $row->frequency_days . ' days', $now)),
            'updated_at' => current_time('mysql'),
        ), array('id' => $row->id));

        myseo_log_sync_event('reports', 'scheduled_email', '', 'success', 'Report sent to ' . $row->recipient_email);
    }
}

function myseo_log_sync_event($provider, $sync_type, $country_code, $status, $message) {
    global $wpdb;
    $table = myseo_get_table_name('sync_logs');
    $wpdb->insert($table, array(
        'provider' => $provider,
        'sync_type' => $sync_type,
        'country_code' => $country_code,
        'status' => $status,
        'message' => $message,
        'synced_at' => current_time('mysql'),
    ));
}

function myseo_exchange_google_auth_code($code) {
    $client_id = myseo_get_google_oauth_client_id();
    $client_secret = myseo_get_google_oauth_client_secret();
    $redirect_uri = myseo_get_option('google_redirect_uri', admin_url('admin.php?page=myseo-google-api'));

    if ($client_id === '' || $client_secret === '') {
        return new WP_Error('myseo_google_missing_client', 'Google client credentials are missing.');
    }

    $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
        'timeout' => 20,
        'body' => array(
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code',
        ),
    ));

    return myseo_handle_google_token_response($response);
}

function myseo_refresh_google_access_token() {
    $client_id = myseo_get_google_oauth_client_id();
    $client_secret = myseo_get_google_oauth_client_secret();
    $refresh_token = myseo_get_option('google_refresh_token', '');

    if ($client_id === '' || $client_secret === '' || $refresh_token === '') {
        return new WP_Error('myseo_google_missing_refresh', 'Google refresh flow is not configured.');
    }

    $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
        'timeout' => 20,
        'body' => array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
        ),
    ));

    return myseo_handle_google_token_response($response, false);
}

function myseo_get_google_oauth_client_secret() {
    if (defined('MYSEO_GOOGLE_CLIENT_SECRET') && MYSEO_GOOGLE_CLIENT_SECRET) {
        return MYSEO_GOOGLE_CLIENT_SECRET;
    }
    return myseo_get_option('google_client_secret', '');
}

function myseo_handle_google_token_response($response, $store_refresh = true) {
    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($code < 200 || $code >= 300 || !is_array($body) || empty($body['access_token'])) {
        return new WP_Error('myseo_google_token_failed', 'Google token request failed.');
    }

    myseo_update_option('google_access_token', $body['access_token']);
    if (!empty($body['refresh_token']) && $store_refresh) {
        myseo_update_option('google_refresh_token', $body['refresh_token']);
    }
    if (!empty($body['expires_in'])) {
        myseo_update_option('google_access_token_expires_at', (string) (time() + (int) $body['expires_in']));
    }

    return $body;
}

function myseo_get_google_access_token() {
    $access_token = myseo_get_option('google_access_token', '');
    $expires_at = (int) myseo_get_option('google_access_token_expires_at', 0);

    if ($access_token !== '' && ($expires_at === 0 || $expires_at > time() + 60)) {
        return $access_token;
    }

    $refreshed = myseo_refresh_google_access_token();
    if (is_wp_error($refreshed)) {
        return '';
    }

    return !empty($refreshed['access_token']) ? $refreshed['access_token'] : '';
}

function myseo_fetch_gsc_page_metrics($access_token, $property, $country) {
    $response = wp_remote_post(
        'https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode($property) . '/searchAnalytics/query',
        array(
            'timeout' => 20,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'startDate' => gmdate('Y-m-d', strtotime('-7 days')),
                'endDate' => gmdate('Y-m-d'),
                'dimensions' => array('page'),
                'dimensionFilterGroups' => array(
                    array(
                        'filters' => array(
                            array(
                                'dimension' => 'country',
                                'operator' => 'equals',
                                'expression' => strtoupper($country),
                            ),
                        ),
                    ),
                ),
                'rowLimit' => 25,
            )),
        )
    );

    if (is_wp_error($response)) {
        myseo_log_sync_event('gsc', 'page_metrics', $country, 'failed', $response->get_error_message());
        return;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($code < 200 || $code >= 300 || !is_array($body)) {
        myseo_log_sync_event('gsc', 'page_metrics', $country, 'failed', 'Invalid GSC page response.');
        return;
    }

    if (!empty($body['rows']) && is_array($body['rows'])) {
        foreach ($body['rows'] as $row) {
            if (empty($row['keys'][0])) {
                continue;
            }
            $post_id = url_to_postid($row['keys'][0]);
            if ($post_id < 1) {
                continue;
            }
            myseo_upsert_post_metric(array(
                'post_id' => $post_id,
                'metric_date' => gmdate('Y-m-d'),
                'clicks' => isset($row['clicks']) ? (int) round($row['clicks']) : 0,
                'impressions' => isset($row['impressions']) ? (int) round($row['impressions']) : 0,
                'ctr' => isset($row['ctr']) ? (float) $row['ctr'] : 0,
                'average_position' => isset($row['position']) ? (float) $row['position'] : 0,
            ));
        }
    }

    myseo_log_sync_event('gsc', 'page_metrics', $country, 'success', 'GSC page metrics imported.');
}

function myseo_ensure_search_console_property($access_token, $site_url) {
    $property = trailingslashit($site_url);

    $list_response = wp_remote_get(
        'https://searchconsole.googleapis.com/webmasters/v3/sites',
        array(
            'timeout' => 20,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        )
    );

    if (!is_wp_error($list_response)) {
        $list_body = json_decode(wp_remote_retrieve_body($list_response), true);
        if (!empty($list_body['siteEntry']) && is_array($list_body['siteEntry'])) {
            foreach ($list_body['siteEntry'] as $entry) {
                if (!empty($entry['siteUrl']) && trailingslashit($entry['siteUrl']) === $property) {
                    myseo_log_sync_event('gsc', 'create_property', '', 'success', 'Existing property selected.');
                    return $property;
                }
            }
        }
    }

    $create_response = wp_remote_request(
        'https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode($property),
        array(
            'method' => 'PUT',
            'timeout' => 20,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        )
    );

    if (is_wp_error($create_response)) {
        myseo_log_sync_event('gsc', 'create_property', '', 'failed', $create_response->get_error_message());
        return $create_response;
    }

    $code = wp_remote_retrieve_response_code($create_response);
    if ($code >= 200 && $code < 300) {
        myseo_log_sync_event('gsc', 'create_property', '', 'success', 'Search Console property created.');
        return $property;
    }

    myseo_log_sync_event('gsc', 'create_property', '', 'failed', 'Search Console property creation failed.');
    return new WP_Error('myseo_gsc_create_failed', 'Failed to create Search Console property.');
}

function myseo_create_ga4_property_and_stream($access_token, $site_url) {
    $account_summaries_response = wp_remote_get(
        'https://analyticsadmin.googleapis.com/v1beta/accountSummaries',
        array(
            'timeout' => 20,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        )
    );

    if (is_wp_error($account_summaries_response)) {
        myseo_log_sync_event('ga4', 'list_accounts', '', 'failed', $account_summaries_response->get_error_message());
        return $account_summaries_response;
    }

    $account_body = json_decode(wp_remote_retrieve_body($account_summaries_response), true);
    $account_name = '';
    if (!empty($account_body['accountSummaries'][0]['account'])) {
        $account_name = $account_body['accountSummaries'][0]['account'];
    }
    if ($account_name === '') {
        return new WP_Error('myseo_ga4_no_account', 'No Google Analytics account available for this user.');
    }

    $property_response = wp_remote_post(
        'https://analyticsadmin.googleapis.com/v1beta/properties',
        array(
            'timeout' => 20,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'displayName' => get_bloginfo('name'),
                'timeZone' => wp_timezone_string() ? wp_timezone_string() : 'UTC',
                'currencyCode' => myseo_get_option('default_currency', 'USD'),
                'industryCategory' => 'TECHNOLOGY',
                'parent' => $account_name,
            )),
        )
    );

    if (is_wp_error($property_response)) {
        myseo_log_sync_event('ga4', 'create_property', '', 'failed', $property_response->get_error_message());
        return $property_response;
    }

    $property_code = wp_remote_retrieve_response_code($property_response);
    $property_body = json_decode(wp_remote_retrieve_body($property_response), true);
    if ($property_code < 200 || $property_code >= 300 || empty($property_body['name'])) {
        myseo_log_sync_event('ga4', 'create_property', '', 'failed', 'GA4 property creation failed.');
        return new WP_Error('myseo_ga4_property_failed', 'Failed to create GA4 property.');
    }

    $property_name = $property_body['name'];
    $property_id = '';
    if (preg_match('#properties/(\d+)$#', $property_name, $matches)) {
        $property_id = $matches[1];
    }

    $stream_response = wp_remote_post(
        'https://analyticsadmin.googleapis.com/v1beta/' . $property_name . '/dataStreams',
        array(
            'timeout' => 20,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'displayName' => get_bloginfo('name') . ' Web Stream',
                'type' => 'WEB_DATA_STREAM',
                'webStreamData' => array(
                    'defaultUri' => home_url('/'),
                ),
            )),
        )
    );

    if (is_wp_error($stream_response)) {
        myseo_log_sync_event('ga4', 'create_stream', '', 'failed', $stream_response->get_error_message());
        return $stream_response;
    }

    $stream_code = wp_remote_retrieve_response_code($stream_response);
    $stream_body = json_decode(wp_remote_retrieve_body($stream_response), true);
    if ($stream_code < 200 || $stream_code >= 300) {
        myseo_log_sync_event('ga4', 'create_stream', '', 'failed', 'GA4 stream creation failed.');
        return new WP_Error('myseo_ga4_stream_failed', 'Failed to create GA4 web stream.');
    }

    $measurement_id = !empty($stream_body['webStreamData']['measurementId']) ? $stream_body['webStreamData']['measurementId'] : '';
    myseo_log_sync_event('ga4', 'create_property', '', 'success', 'GA4 property and web stream created.');

    return array(
        'property_id' => $property_id,
        'measurement_id' => $measurement_id,
    );
}

function myseo_fetch_gsc_keyword_metrics($access_token, $property, $country) {
    $response = wp_remote_post(
        'https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode($property) . '/searchAnalytics/query',
        array(
            'timeout' => 20,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'startDate' => gmdate('Y-m-d', strtotime('-7 days')),
                'endDate' => gmdate('Y-m-d'),
                'dimensions' => array('query'),
                'dimensionFilterGroups' => array(
                    array(
                        'filters' => array(
                            array(
                                'dimension' => 'country',
                                'operator' => 'equals',
                                'expression' => strtoupper($country),
                            ),
                        ),
                    ),
                ),
                'rowLimit' => 50,
            )),
        )
    );

    if (is_wp_error($response)) {
        myseo_log_sync_event('gsc', 'keyword_metrics', $country, 'failed', $response->get_error_message());
        return;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($code < 200 || $code >= 300 || !is_array($body)) {
        myseo_log_sync_event('gsc', 'keyword_metrics', $country, 'failed', 'Invalid GSC keyword response.');
        return;
    }

    if (!empty($body['rows']) && is_array($body['rows'])) {
        foreach ($body['rows'] as $row) {
            if (empty($row['keys'][0])) {
                continue;
            }
            $keyword_id = myseo_get_or_create_keyword($row['keys'][0], $country);
            if ($keyword_id < 1) {
                continue;
            }
            myseo_upsert_keyword_history(array(
                'keyword_id' => $keyword_id,
                'tracked_on' => gmdate('Y-m-d'),
                'rank_position' => isset($row['position']) ? (int) round($row['position']) : null,
                'previous_position' => myseo_get_latest_keyword_position($keyword_id),
                'clicks' => isset($row['clicks']) ? (int) round($row['clicks']) : 0,
                'impressions' => isset($row['impressions']) ? (int) round($row['impressions']) : 0,
                'ctr' => isset($row['ctr']) ? (float) $row['ctr'] : 0,
                'average_position' => isset($row['position']) ? (float) $row['position'] : null,
                'source' => 'gsc',
            ));
        }
    }

    myseo_log_sync_event('gsc', 'keyword_metrics', $country, 'success', 'GSC keyword metrics imported.');
}

function myseo_fetch_ga4_page_metrics($access_token, $property_id, $country) {
    $response = wp_remote_post(
        'https://analyticsdata.googleapis.com/v1beta/properties/' . rawurlencode($property_id) . ':runReport',
        array(
            'timeout' => 20,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'dateRanges' => array(
                    array(
                        'startDate' => '7daysAgo',
                        'endDate' => 'today',
                    ),
                ),
                'dimensions' => array(
                    array('name' => 'pagePath'),
                ),
                'metrics' => array(
                    array('name' => 'screenPageViews'),
                    array('name' => 'sessions'),
                ),
                'dimensionFilter' => array(
                    'filter' => array(
                        'fieldName' => 'countryId',
                        'stringFilter' => array(
                            'matchType' => 'EXACT',
                            'value' => strtoupper($country),
                        ),
                    ),
                ),
                'limit' => 50,
            )),
        )
    );

    if (is_wp_error($response)) {
        myseo_log_sync_event('ga4', 'page_metrics', $country, 'failed', $response->get_error_message());
        return;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($code < 200 || $code >= 300 || !is_array($body)) {
        myseo_log_sync_event('ga4', 'page_metrics', $country, 'failed', 'Invalid GA4 response.');
        return;
    }

    if (!empty($body['rows']) && is_array($body['rows'])) {
        foreach ($body['rows'] as $row) {
            $path = isset($row['dimensionValues'][0]['value']) ? $row['dimensionValues'][0]['value'] : '';
            if ($path === '') {
                continue;
            }
            $post_id = url_to_postid(home_url($path));
            if ($post_id < 1) {
                continue;
            }
            $views = isset($row['metricValues'][0]['value']) ? (int) $row['metricValues'][0]['value'] : 0;
            $sessions = isset($row['metricValues'][1]['value']) ? (int) $row['metricValues'][1]['value'] : 0;
            myseo_upsert_post_metric(array(
                'post_id' => $post_id,
                'metric_date' => gmdate('Y-m-d'),
                'clicks' => $sessions,
                'impressions' => $views,
                'ctr' => $views > 0 ? round($sessions / $views, 4) : 0,
            ));
        }
    }

    myseo_log_sync_event('ga4', 'page_metrics', $country, 'success', 'GA4 page metrics imported.');
}

function myseo_upsert_post_metric($data) {
    global $wpdb;
    $table = myseo_get_table_name('post_metrics');
    $defaults = array(
        'post_id' => 0,
        'metric_date' => gmdate('Y-m-d'),
        'clicks' => 0,
        'impressions' => 0,
        'ctr' => 0,
        'average_position' => null,
        'page_speed_mobile' => null,
        'page_speed_desktop' => null,
        'adsense_earnings' => 0,
        'ai_search_clicks' => 0,
    );
    $row = array_merge($defaults, $data);
    $wpdb->replace($table, $row);
}

function myseo_get_or_create_keyword($keyword, $country) {
    global $wpdb;
    $table = myseo_get_table_name('keywords');
    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE keyword = %s LIMIT 1", $keyword));
    if ($existing) {
        return (int) $existing;
    }
    $now = current_time('mysql');
    $wpdb->insert($table, array(
        'keyword' => $keyword,
        'target_url' => '',
        'country_code' => $country,
        'device_type' => 'desktop',
        'group_name' => 'Imported',
        'notes' => '',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ));
    return (int) $wpdb->insert_id;
}

function myseo_get_latest_keyword_position($keyword_id) {
    global $wpdb;
    $table = myseo_get_table_name('keyword_history');
    $value = $wpdb->get_var($wpdb->prepare("SELECT rank_position FROM {$table} WHERE keyword_id = %d ORDER BY tracked_on DESC, id DESC LIMIT 1", $keyword_id));
    return $value !== null ? (int) $value : null;
}

function myseo_upsert_keyword_history($data) {
    global $wpdb;
    $table = myseo_get_table_name('keyword_history');
    $defaults = array(
        'keyword_id' => 0,
        'tracked_on' => gmdate('Y-m-d'),
        'rank_position' => null,
        'previous_position' => null,
        'clicks' => 0,
        'impressions' => 0,
        'ctr' => 0,
        'average_position' => null,
        'source' => 'manual',
    );
    $row = array_merge($defaults, $data);
    $wpdb->replace($table, $row);
}
