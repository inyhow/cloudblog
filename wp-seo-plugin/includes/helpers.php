<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_get_option($key, $default = '') {
    $options = get_option('myseo_settings', array());
    if (!is_array($options)) {
        $options = array();
    }
    return array_key_exists($key, $options) ? $options[$key] : $default;
}

function myseo_update_option($key, $value) {
    $options = get_option('myseo_settings', array());
    if (!is_array($options)) {
        $options = array();
    }
    $options[$key] = $value;
    update_option('myseo_settings', $options);
}

function myseo_sanitize_setting_value($key, $value) {
    switch ($key) {
        case 'default_social_image':
        case 'gsc_property':
        case 'google_redirect_uri':
            return esc_url_raw(wp_unslash($value));
        case 'google_fetch_frequency':
            return max(1, (int) $value);
        case 'google_data_retention_days':
            return max(7, (int) $value);
        case 'email_report_frequency_days':
            return max(1, (int) $value);
        case 'default_country_code':
        case 'default_currency':
            return strtoupper(myseo_sanitize_text($value));
        case 'social_watermark_enabled':
            return empty($value) ? 0 : 1;
        case 'google_access_token':
        case 'google_refresh_token':
        case 'google_authorization_code':
            return sanitize_textarea_field(wp_unslash($value));
        case 'google_oauth_mode':
            $mode = myseo_sanitize_text($value);
            return in_array($mode, array('product', 'developer'), true) ? $mode : 'product';
        default:
            return myseo_sanitize_text($value);
    }
}

function myseo_sanitize_text($value) {
    return sanitize_text_field(wp_unslash($value));
}

function myseo_get_table_name($suffix) {
    global $wpdb;
    return $wpdb->prefix . 'myseo_' . $suffix;
}

function myseo_get_schema_type_options() {
    return array(
        'Article', 'BlogPosting', 'Product', 'FAQPage', 'HowTo', 'Recipe', 'Event',
        'Course', 'JobPosting', 'LocalBusiness', 'Organization', 'Person',
        'VideoObject', 'NewsArticle', 'Dataset', 'ClaimReview', 'PodcastSeries',
        'PodcastEpisode', 'ItemList', 'QAPage', 'Carousel', 'Service', 'SoftwareApplication', 'Book',
        'SpeakableSpecification', 'AboutPage', 'ProfilePage', 'CollectionPage', 'FAQPage', 'BreadcrumbList'
    );
}

function myseo_detect_video_url($content) {
    if (preg_match('#https?://[^\s"\']+\.(mp4|mov|m4v|webm)#i', $content, $matches)) {
        return $matches[0];
    }
    if (preg_match('#https?://(?:www\.)?(youtube\.com/watch\?v=[^"\']+|youtu\.be/[^"\']+)#i', $content, $matches)) {
        return $matches[0];
    }
    if (preg_match('#https?://(?:www\.)?vimeo\.com/[^"\']+#i', $content, $matches)) {
        return $matches[0];
    }
    return '';
}
