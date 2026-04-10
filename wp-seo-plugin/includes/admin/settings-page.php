<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_settings_page() {
    add_options_page(
        'MySEO Settings',
        'MySEO',
        'manage_options',
        'myseo-settings',
        'myseo_render_settings_page'
    );
}

function myseo_register_admin_settings() {
    register_setting('myseo_settings', 'myseo_settings', 'myseo_sanitize_settings');
    register_setting('myseo_modules', 'myseo_modules', 'myseo_sanitize_modules');

    add_settings_section('myseo_general', 'General', '__return_null', 'myseo-settings');
    add_settings_section('myseo_modules', 'Modules', '__return_null', 'myseo-settings');

    add_settings_field('site_title_template', 'Title Template', 'myseo_field_title_template', 'myseo-settings', 'myseo_general');
    add_settings_field('site_description_template', 'Description Template', 'myseo_field_description_template', 'myseo-settings', 'myseo_general');
    add_settings_field('default_social_image', 'Default Social Image', 'myseo_field_social_image', 'myseo-settings', 'myseo_general');
    add_settings_field('google_site_verification', 'Google Site Verification', 'myseo_field_google_verification', 'myseo-settings', 'myseo_general');
    add_settings_field('breadcrumbs_separator', 'Breadcrumb Separator', 'myseo_field_breadcrumb_separator', 'myseo-settings', 'myseo_general');
    add_settings_field('toc_heading_label', 'TOC Heading Label', 'myseo_field_toc_heading_label', 'myseo-settings', 'myseo_general');
    add_settings_field('ga4_measurement_id', 'GA4 Measurement ID', 'myseo_field_ga4_measurement_id', 'myseo-settings', 'myseo_general');
    add_settings_field('ga4_property_id', 'GA4 Property ID', 'myseo_field_ga4_property_id', 'myseo-settings', 'myseo_general');
    add_settings_field('ga4_api_secret', 'GA4 API Secret', 'myseo_field_ga4_api_secret', 'myseo-settings', 'myseo_general');
    add_settings_field('gsc_property', 'GSC Property URL', 'myseo_field_gsc_property', 'myseo-settings', 'myseo_general');
    add_settings_field('google_fetch_frequency', 'Google Data Fetch Frequency', 'myseo_field_google_fetch_frequency', 'myseo-settings', 'myseo_general');
    add_settings_field('google_data_retention_days', 'Days to Preserve Google Data', 'myseo_field_google_data_retention_days', 'myseo-settings', 'myseo_general');
    add_settings_field('email_report_frequency_days', 'Email Report Frequency in Days', 'myseo_field_email_report_frequency_days', 'myseo-settings', 'myseo_general');
    add_settings_field('default_country_code', 'Default Country Code', 'myseo_field_default_country_code', 'myseo-settings', 'myseo_general');
    add_settings_field('news_publication_name', 'Google News Publication Name', 'myseo_field_news_publication_name', 'myseo-settings', 'myseo_general');
    add_settings_field('local_business_name', 'Local Business Name', 'myseo_field_local_business_name', 'myseo-settings', 'myseo_general');
    add_settings_field('local_business_type', 'Local Business Type', 'myseo_field_local_business_type', 'myseo-settings', 'myseo_general');
    add_settings_field('default_currency', 'Default Currency', 'myseo_field_default_currency', 'myseo-settings', 'myseo_general');
    add_settings_field('podcast_default_category', 'Podcast Category', 'myseo_field_podcast_default_category', 'myseo-settings', 'myseo_general');
    add_settings_field('setup_wizard_mode', 'Setup Wizard Mode', 'myseo_field_setup_wizard_mode', 'myseo-settings', 'myseo_general');
    add_settings_field('social_watermark_text', 'Watermark Text', 'myseo_field_social_watermark_text', 'myseo-settings', 'myseo_general');
    add_settings_field('social_watermark_enabled', 'Social Watermark', 'myseo_field_social_watermark_enabled', 'myseo-settings', 'myseo_general');
    add_settings_field('google_oauth_mode', 'Google OAuth Mode', 'myseo_field_google_oauth_mode', 'myseo-settings', 'myseo_general');
    add_settings_field('white_label_brand', 'White Label Brand', 'myseo_field_white_label_brand', 'myseo-settings', 'myseo_general');
    add_settings_field('modules_toggle', 'Enabled Modules', 'myseo_field_modules', 'myseo-settings', 'myseo_modules');
}

function myseo_register_admin() {
    add_action('admin_init', 'myseo_register_admin_settings');
    add_action('add_meta_boxes', 'myseo_add_metabox');
    add_action('save_post', 'myseo_save_metabox');
}

function myseo_sanitize_settings($input) {
    $existing = get_option('myseo_settings', array());
    $output = is_array($existing) ? $existing : array();
    $output['site_title_template'] = isset($input['site_title_template']) ? myseo_sanitize_text($input['site_title_template']) : '';
    $output['site_description_template'] = isset($input['site_description_template']) ? myseo_sanitize_text($input['site_description_template']) : '';
    $output['default_social_image'] = isset($input['default_social_image']) ? esc_url_raw(wp_unslash($input['default_social_image'])) : '';
    $output['google_site_verification'] = isset($input['google_site_verification']) ? myseo_sanitize_text($input['google_site_verification']) : '';
    $output['breadcrumbs_separator'] = isset($input['breadcrumbs_separator']) ? myseo_sanitize_text($input['breadcrumbs_separator']) : '/';
    $output['toc_heading_label'] = isset($input['toc_heading_label']) ? myseo_sanitize_text($input['toc_heading_label']) : 'On this page';
    $output['ga4_measurement_id'] = isset($input['ga4_measurement_id']) ? myseo_sanitize_text($input['ga4_measurement_id']) : '';
    $output['ga4_property_id'] = isset($input['ga4_property_id']) ? myseo_sanitize_text($input['ga4_property_id']) : '';
    $output['ga4_api_secret'] = isset($input['ga4_api_secret']) ? myseo_sanitize_text($input['ga4_api_secret']) : '';
    $output['gsc_property'] = isset($input['gsc_property']) ? esc_url_raw(wp_unslash($input['gsc_property'])) : '';
    $output['google_fetch_frequency'] = isset($input['google_fetch_frequency']) ? max(1, (int) $input['google_fetch_frequency']) : 1;
    $output['google_data_retention_days'] = isset($input['google_data_retention_days']) ? max(7, (int) $input['google_data_retention_days']) : 90;
    $output['email_report_frequency_days'] = isset($input['email_report_frequency_days']) ? max(1, (int) $input['email_report_frequency_days']) : 7;
    $output['default_country_code'] = isset($input['default_country_code']) ? strtoupper(myseo_sanitize_text($input['default_country_code'])) : 'US';
    $output['news_publication_name'] = isset($input['news_publication_name']) ? myseo_sanitize_text($input['news_publication_name']) : '';
    $output['local_business_name'] = isset($input['local_business_name']) ? myseo_sanitize_text($input['local_business_name']) : '';
    $output['local_business_type'] = isset($input['local_business_type']) ? myseo_sanitize_text($input['local_business_type']) : 'LocalBusiness';
    $output['default_currency'] = isset($input['default_currency']) ? strtoupper(myseo_sanitize_text($input['default_currency'])) : 'USD';
    $output['podcast_default_category'] = isset($input['podcast_default_category']) ? myseo_sanitize_text($input['podcast_default_category']) : 'Technology';
    $output['setup_wizard_mode'] = isset($input['setup_wizard_mode']) ? myseo_sanitize_text($input['setup_wizard_mode']) : 'standard';
    $output['social_watermark_text'] = isset($input['social_watermark_text']) ? myseo_sanitize_text($input['social_watermark_text']) : '';
    $output['social_watermark_enabled'] = isset($input['social_watermark_enabled']) ? 1 : 0;
    $output['google_oauth_mode'] = isset($input['google_oauth_mode']) ? myseo_sanitize_text($input['google_oauth_mode']) : 'product';
    $output['white_label_brand'] = isset($input['white_label_brand']) ? myseo_sanitize_text($input['white_label_brand']) : '';
    return $output;
}

function myseo_sanitize_modules($input) {
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
    $output = array();
    foreach ($defaults as $key => $enabled) {
        $output[$key] = isset($input[$key]) ? (bool) $input[$key] : false;
    }
    return $output;
}

function myseo_render_settings_page() {
    if (isset($_POST['myseo_settings_modules_nonce']) && wp_verify_nonce($_POST['myseo_settings_modules_nonce'], 'myseo_settings_modules_save')) {
        $input = isset($_POST['myseo_modules']) ? (array) $_POST['myseo_modules'] : array();
        update_option('myseo_modules', myseo_sanitize_modules($input));
        echo '<div class="notice notice-success"><p>Modules updated.</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>MySEO Settings</h1>';
    echo '<form method="post" action="options.php" class="myseo-live-form" data-myseo-live-scope="settings">';
    settings_fields('myseo_settings');
    echo '<p class="description">Changes save automatically.</p>';
    echo '<div class="myseo-live-status" aria-live="polite"></div>';
    echo '<table class="form-table" role="presentation"><tbody>';
    do_settings_fields('myseo-settings', 'myseo_general');
    echo '</tbody></table>';
    echo '<noscript>';
    submit_button();
    echo '</noscript>';
    echo '</form>';

    echo '<hr style="margin:24px 0;" />';
    echo '<h2>Enabled Modules</h2>';
    echo '<form method="post" class="myseo-live-form" data-myseo-live-scope="modules">';
    wp_nonce_field('myseo_settings_modules_save', 'myseo_settings_modules_nonce');
    echo '<p class="description">Module changes save automatically.</p>';
    echo '<div class="myseo-live-status" aria-live="polite"></div>';
    myseo_field_modules();
    echo '<noscript>';
    submit_button('Save Modules');
    echo '</noscript>';
    echo '</form>';
    echo '</div>';
}

function myseo_field_title_template() {
    $value = myseo_get_option('site_title_template', '%title% - %site%');
    echo '<input type="text" name="myseo_settings[site_title_template]" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Variables: %title%, %site%</p>';
}

function myseo_field_description_template() {
    $value = myseo_get_option('site_description_template', '%excerpt%');
    echo '<textarea name="myseo_settings[site_description_template]" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">Variables: %excerpt%, %site%</p>';
}

function myseo_field_social_image() {
    $value = myseo_get_option('default_social_image', '');
    echo '<input type="url" name="myseo_settings[default_social_image]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function myseo_field_google_verification() {
    $value = myseo_get_option('google_site_verification', '');
    echo '<input type="text" name="myseo_settings[google_site_verification]" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Paste the Search Console token from the meta tag.</p>';
}

function myseo_field_breadcrumb_separator() {
    $value = myseo_get_option('breadcrumbs_separator', '/');
    echo '<input type="text" name="myseo_settings[breadcrumbs_separator]" value="' . esc_attr($value) . '" class="small-text" />';
}

function myseo_field_toc_heading_label() {
    $value = myseo_get_option('toc_heading_label', 'On this page');
    echo '<input type="text" name="myseo_settings[toc_heading_label]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function myseo_field_ga4_measurement_id() {
    $value = myseo_get_option('ga4_measurement_id', '');
    echo '<input type="text" name="myseo_settings[ga4_measurement_id]" value="' . esc_attr($value) . '" class="regular-text" placeholder="G-XXXXXXXXXX" />';
}

function myseo_field_ga4_property_id() {
    $value = myseo_get_option('ga4_property_id', '');
    echo '<input type="text" name="myseo_settings[ga4_property_id]" value="' . esc_attr($value) . '" class="regular-text" placeholder="123456789" />';
}

function myseo_field_ga4_api_secret() {
    $value = myseo_get_option('ga4_api_secret', '');
    echo '<input type="text" name="myseo_settings[ga4_api_secret]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function myseo_field_gsc_property() {
    $value = myseo_get_option('gsc_property', '');
    echo '<input type="url" name="myseo_settings[gsc_property]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function myseo_field_google_fetch_frequency() {
    $value = (int) myseo_get_option('google_fetch_frequency', 1);
    echo '<input type="number" min="1" name="myseo_settings[google_fetch_frequency]" value="' . esc_attr($value) . '" class="small-text" /> days';
}

function myseo_field_google_data_retention_days() {
    $value = (int) myseo_get_option('google_data_retention_days', 90);
    echo '<input type="number" min="7" name="myseo_settings[google_data_retention_days]" value="' . esc_attr($value) . '" class="small-text" /> days';
}

function myseo_field_email_report_frequency_days() {
    $value = (int) myseo_get_option('email_report_frequency_days', 7);
    echo '<input type="number" min="1" name="myseo_settings[email_report_frequency_days]" value="' . esc_attr($value) . '" class="small-text" /> days';
}

function myseo_field_default_country_code() {
    $value = myseo_get_option('default_country_code', 'US');
    echo '<input type="text" name="myseo_settings[default_country_code]" value="' . esc_attr($value) . '" class="small-text" />';
}

function myseo_field_news_publication_name() {
    $value = myseo_get_option('news_publication_name', '');
    echo '<input type="text" name="myseo_settings[news_publication_name]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function myseo_field_local_business_name() {
    $value = myseo_get_option('local_business_name', '');
    echo '<input type="text" name="myseo_settings[local_business_name]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function myseo_field_local_business_type() {
    $value = myseo_get_option('local_business_type', 'LocalBusiness');
    echo '<input type="text" name="myseo_settings[local_business_type]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function myseo_field_default_currency() {
    $value = myseo_get_option('default_currency', 'USD');
    echo '<input type="text" name="myseo_settings[default_currency]" value="' . esc_attr($value) . '" class="small-text" />';
}

function myseo_field_podcast_default_category() {
    $value = myseo_get_option('podcast_default_category', 'Technology');
    echo '<input type="text" name="myseo_settings[podcast_default_category]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function myseo_field_setup_wizard_mode() {
    $value = myseo_get_option('setup_wizard_mode', 'standard');
    echo '<select name="myseo_settings[setup_wizard_mode]">';
    echo '<option value="standard"' . selected($value, 'standard', false) . '>Standard</option>';
    echo '<option value="client"' . selected($value, 'client', false) . '>Client</option>';
    echo '<option value="white_label"' . selected($value, 'white_label', false) . '>White Label</option>';
    echo '</select>';
}

function myseo_field_social_watermark_text() {
    $value = myseo_get_option('social_watermark_text', '');
    echo '<input type="text" name="myseo_settings[social_watermark_text]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function myseo_field_social_watermark_enabled() {
    $value = (bool) myseo_get_option('social_watermark_enabled', 0);
    echo '<label><input type="checkbox" name="myseo_settings[social_watermark_enabled]" value="1"' . checked($value, true, false) . ' /> Enable watermark on social media images</label>';
}

function myseo_field_google_oauth_mode() {
    $value = myseo_get_option('google_oauth_mode', 'product');
    echo '<select name="myseo_settings[google_oauth_mode]">';
    echo '<option value="product"' . selected($value, 'product', false) . '>Product Mode</option>';
    echo '<option value="developer"' . selected($value, 'developer', false) . '>Developer Mode</option>';
    echo '</select>';
    echo '<p class="description">Product mode hides Client ID and Secret from site owners and shows only connect buttons.</p>';
}

function myseo_field_white_label_brand() {
    $value = myseo_get_option('white_label_brand', '');
    echo '<input type="text" name="myseo_settings[white_label_brand]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function myseo_field_modules() {
    $modules = get_option('myseo_modules', array());
    $items = array(
        'meta' => 'SEO Meta',
        'social' => 'Social Meta',
        'schema' => 'Schema (JSON-LD)',
        'sitemap' => 'Sitemap',
        'redirections' => 'Redirections',
        'monitor' => '404 Monitor',
        'breadcrumbs' => 'Breadcrumbs',
        'toc' => 'Table of Contents',
        'analytics' => 'Google Analytics / GSC',
        'rank_tracker' => 'Keyword Rank Tracker',
        'advanced_schema' => 'Advanced Schema',
        'news_sitemap' => 'Google News Sitemap',
        'video_sitemap' => 'Video Sitemap',
        'image_seo' => 'Image SEO',
        'local_seo' => 'Local SEO',
        'woocommerce_seo' => 'WooCommerce SEO',
        'podcast' => 'Podcast Module',
    );

    foreach ($items as $key => $label) {
        $checked = isset($modules[$key]) ? (bool) $modules[$key] : false;
        echo '<label style="display:block;margin:6px 0;">';
        echo '<input type="checkbox" name="myseo_modules[' . esc_attr($key) . ']" value="1"' . checked($checked, true, false) . ' />';
        echo ' ' . esc_html($label) . '</label>';
    }
}
