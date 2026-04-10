<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_install_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $logs_table = $wpdb->prefix . 'myseo_404_logs';
    $redir_table = $wpdb->prefix . 'myseo_redirections';
    $keywords_table = $wpdb->prefix . 'myseo_keywords';
    $keyword_history_table = $wpdb->prefix . 'myseo_keyword_history';
    $post_metrics_table = $wpdb->prefix . 'myseo_post_metrics';
    $reports_table = $wpdb->prefix . 'myseo_email_reports';
    $schemas_table = $wpdb->prefix . 'myseo_custom_schemas';
    $clients_table = $wpdb->prefix . 'myseo_clients';
    $locations_table = $wpdb->prefix . 'myseo_locations';
    $podcasts_table = $wpdb->prefix . 'myseo_podcasts';
    $sync_logs_table = $wpdb->prefix . 'myseo_sync_logs';

    $sql = array();
    $sql[] = "CREATE TABLE {$logs_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        url TEXT NOT NULL,
        referrer TEXT NULL,
        user_agent TEXT NULL,
        ip_address VARCHAR(100) NULL,
        hits BIGINT UNSIGNED NOT NULL DEFAULT 1,
        last_seen DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY last_seen (last_seen)
    ) {$charset_collate};";

    $sql[] = "CREATE TABLE {$redir_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        source TEXT NOT NULL,
        target TEXT NOT NULL,
        status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
        is_regex TINYINT(1) NOT NULL DEFAULT 0,
        hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
        enabled TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY enabled (enabled)
    ) {$charset_collate};";

    $sql[] = "CREATE TABLE {$keywords_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        keyword VARCHAR(191) NOT NULL,
        target_url TEXT NULL,
        country_code VARCHAR(10) NULL,
        device_type VARCHAR(20) NOT NULL DEFAULT 'desktop',
        group_name VARCHAR(100) NULL,
        notes TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY keyword (keyword),
        KEY is_active (is_active)
    ) {$charset_collate};";

    $sql[] = "CREATE TABLE {$keyword_history_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        keyword_id BIGINT UNSIGNED NOT NULL,
        tracked_on DATE NOT NULL,
        rank_position INT NULL,
        previous_position INT NULL,
        clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
        impressions BIGINT UNSIGNED NOT NULL DEFAULT 0,
        ctr DECIMAL(8,4) NOT NULL DEFAULT 0,
        average_position DECIMAL(8,2) NULL,
        source VARCHAR(50) NOT NULL DEFAULT 'manual',
        PRIMARY KEY  (id),
        UNIQUE KEY keyword_day (keyword_id, tracked_on),
        KEY tracked_on (tracked_on)
    ) {$charset_collate};";

    $sql[] = "CREATE TABLE {$post_metrics_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT UNSIGNED NOT NULL,
        metric_date DATE NOT NULL,
        clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
        impressions BIGINT UNSIGNED NOT NULL DEFAULT 0,
        ctr DECIMAL(8,4) NOT NULL DEFAULT 0,
        average_position DECIMAL(8,2) NULL,
        page_speed_mobile INT NULL,
        page_speed_desktop INT NULL,
        adsense_earnings DECIMAL(12,2) NOT NULL DEFAULT 0,
        ai_search_clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY post_day (post_id, metric_date),
        KEY metric_date (metric_date)
    ) {$charset_collate};";

    $sql[] = "CREATE TABLE {$reports_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        report_name VARCHAR(191) NOT NULL,
        recipient_email VARCHAR(191) NOT NULL,
        frequency_days INT NOT NULL DEFAULT 7,
        last_sent_at DATETIME NULL,
        next_send_at DATETIME NULL,
        include_white_label TINYINT(1) NOT NULL DEFAULT 0,
        client_id BIGINT UNSIGNED NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY next_send_at (next_send_at),
        KEY is_active (is_active)
    ) {$charset_collate};";

    $sql[] = "CREATE TABLE {$schemas_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        schema_name VARCHAR(191) NOT NULL,
        schema_type VARCHAR(100) NOT NULL,
        schema_payload LONGTEXT NOT NULL,
        trigger_type VARCHAR(50) NOT NULL DEFAULT 'manual',
        trigger_value VARCHAR(191) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        validation_status VARCHAR(50) NOT NULL DEFAULT 'not_validated',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY schema_type (schema_type),
        KEY is_active (is_active)
    ) {$charset_collate};";

    $sql[] = "CREATE TABLE {$clients_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        client_name VARCHAR(191) NOT NULL,
        contact_email VARCHAR(191) NULL,
        site_url TEXT NULL,
        white_label_brand VARCHAR(191) NULL,
        report_frequency_days INT NOT NULL DEFAULT 7,
        notes TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id)
    ) {$charset_collate};";

    $sql[] = "CREATE TABLE {$locations_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        location_name VARCHAR(191) NOT NULL,
        street_address VARCHAR(255) NULL,
        city VARCHAR(100) NULL,
        region VARCHAR(100) NULL,
        postal_code VARCHAR(30) NULL,
        country_code VARCHAR(10) NULL,
        phone VARCHAR(50) NULL,
        email VARCHAR(191) NULL,
        latitude VARCHAR(50) NULL,
        longitude VARCHAR(50) NULL,
        opening_hours TEXT NULL,
        primary_location TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id)
    ) {$charset_collate};";

    $sql[] = "CREATE TABLE {$podcasts_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        podcast_name VARCHAR(191) NOT NULL,
        podcast_description TEXT NULL,
        publisher_name VARCHAR(191) NULL,
        feed_url TEXT NULL,
        site_url TEXT NULL,
        image_url TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id)
    ) {$charset_collate};";

    $sql[] = "CREATE TABLE {$sync_logs_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        provider VARCHAR(50) NOT NULL,
        sync_type VARCHAR(100) NOT NULL,
        country_code VARCHAR(10) NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        message TEXT NULL,
        synced_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY provider (provider),
        KEY synced_at (synced_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    foreach ($sql as $statement) {
        dbDelta($statement);
    }
}
