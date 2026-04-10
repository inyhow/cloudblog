<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_pro_features() {
    add_filter('wp_get_attachment_image_attributes', 'myseo_enhance_image_attributes', 10, 3);
    add_filter('wp_generate_attachment_metadata', 'myseo_maybe_watermark_social_images', 20, 2);
    add_filter('the_content', 'myseo_mark_cloaked_links_as_external', 20);
    add_filter('the_content', 'myseo_auto_generate_image_captions', 21);
    add_action('wp_head', 'myseo_output_custom_schemas', 4);
    add_action('wp_head', 'myseo_output_local_business_schema', 5);
    add_action('wp_head', 'myseo_output_podcast_schema', 6);
    add_action('wp_head', 'myseo_output_advanced_contextual_schemas', 7);
    add_filter('post_password_required', 'myseo_handle_password_protected_noindex', 10, 2);
}

function myseo_enhance_image_attributes($attr, $attachment, $size) {
    if (!myseo_module_enabled('image_seo')) {
        return $attr;
    }

    $title = get_the_title($attachment);
    if (empty($attr['alt']) && $title) {
        $attr['alt'] = $title;
    }
    if (empty($attr['title']) && $title) {
        $attr['title'] = $title;
    }
    return $attr;
}

function myseo_maybe_watermark_social_images($metadata, $attachment_id) {
    if (!myseo_get_option('social_watermark_enabled', 0)) {
        return $metadata;
    }

    $watermark = myseo_get_option('social_watermark_text', '');
    if ($watermark === '') {
        return $metadata;
    }

    $file = get_attached_file($attachment_id);
    if (!$file || !file_exists($file)) {
        return $metadata;
    }

    $editor = wp_get_image_editor($file);
    if (is_wp_error($editor)) {
        return $metadata;
    }

    $editor->set_quality(90);
    $editor->save($file);

    return $metadata;
}

function myseo_output_custom_schemas() {
    if (!myseo_module_enabled('advanced_schema')) {
        return;
    }

    global $wpdb;
    $table = myseo_get_table_name('custom_schemas');
    $rows = $wpdb->get_results("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY id DESC");
    if (!$rows) {
        return;
    }

    foreach ($rows as $row) {
        if (!myseo_schema_matches_context($row)) {
            continue;
        }
        echo "<script type=\"application/ld+json\">\n";
        echo $row->schema_payload;
        echo "\n</script>\n";
    }
}

function myseo_output_local_business_schema() {
    if (!myseo_module_enabled('local_seo')) {
        return;
    }

    global $wpdb;
    $table = myseo_get_table_name('locations');
    $location = $wpdb->get_row("SELECT * FROM {$table} ORDER BY primary_location DESC, id ASC LIMIT 1");
    if (!$location) {
        return;
    }

    $type = myseo_get_option('local_business_type', 'LocalBusiness');
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => $type,
        'name' => $location->location_name,
        'address' => array(
            '@type' => 'PostalAddress',
            'streetAddress' => $location->street_address,
            'addressLocality' => $location->city,
            'addressRegion' => $location->region,
            'postalCode' => $location->postal_code,
            'addressCountry' => $location->country_code,
        ),
        'telephone' => $location->phone,
        'email' => $location->email,
    );

    if ($location->latitude && $location->longitude) {
        $schema['geo'] = array(
            '@type' => 'GeoCoordinates',
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
        );
    }

    echo "<script type=\"application/ld+json\">\n";
    echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "\n</script>\n";
}

function myseo_output_podcast_schema() {
    if (!myseo_module_enabled('podcast')) {
        return;
    }

    global $wpdb;
    $table = myseo_get_table_name('podcasts');
    $podcast = $wpdb->get_row("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    if (!$podcast) {
        return;
    }

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'PodcastSeries',
        'name' => $podcast->podcast_name,
        'description' => $podcast->podcast_description,
        'publisher' => array(
            '@type' => 'Organization',
            'name' => $podcast->publisher_name ? $podcast->publisher_name : get_bloginfo('name'),
        ),
        'url' => $podcast->site_url,
    );
    if ($podcast->image_url) {
        $schema['image'] = $podcast->image_url;
    }
    if ($podcast->feed_url) {
        $schema['sameAs'] = array($podcast->feed_url);
    }

    echo "<script type=\"application/ld+json\">\n";
    echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "\n</script>\n";
}

function myseo_output_advanced_contextual_schemas() {
    if (!myseo_module_enabled('advanced_schema') || !is_singular()) {
        return;
    }

    global $post;
    if (!$post) {
        return;
    }

    $schemas = array();
    $schemas[] = myseo_build_speakable_schema($post);
    $schemas[] = myseo_build_mentions_about_schema($post);
    $schemas[] = myseo_build_dataset_schema($post);
    $schemas[] = myseo_build_fact_check_schema($post);
    $schemas[] = myseo_build_carousel_schema($post);
    $schemas[] = myseo_build_bbpress_qa_schema($post);
    $schemas[] = myseo_fill_video_data_for_schema($post);

    foreach ($schemas as $schema) {
        if (!$schema) {
            continue;
        }
        echo "<script type=\"application/ld+json\">\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "\n</script>\n";
    }
}

function myseo_build_speakable_schema($post) {
    if (!is_single()) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => get_the_title($post),
        'speakable' => array(
            '@type' => 'SpeakableSpecification',
            'cssSelector' => array('h1', '.entry-content p:first-of-type'),
        ),
        'url' => get_permalink($post),
    );
}

function myseo_build_mentions_about_schema($post) {
    $content = wp_strip_all_tags($post->post_content);
    preg_match_all('/@([A-Za-z0-9\-_]+)/', $content, $mentions);
    preg_match_all('/#([A-Za-z0-9\-_]+)/', $content, $topics);

    $about = array();
    foreach (array_slice(array_unique($topics[1]), 0, 5) as $topic) {
        $about[] = array('@type' => 'Thing', 'name' => $topic);
    }

    $mentions_list = array();
    foreach (array_slice(array_unique($mentions[1]), 0, 5) as $mention) {
        $mentions_list[] = array('@type' => 'Thing', 'name' => $mention);
    }

    if (!$about && !$mentions_list) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title($post),
        'about' => $about,
        'mentions' => $mentions_list,
    );
}

function myseo_build_dataset_schema($post) {
    if (!has_tag('dataset', $post) && strpos(strtolower($post->post_title), 'dataset') === false) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'Dataset',
        'name' => get_the_title($post),
        'description' => wp_trim_words(wp_strip_all_tags($post->post_content), 40),
        'url' => get_permalink($post),
    );
}

function myseo_build_fact_check_schema($post) {
    if (!has_tag('fact-check', $post) && !has_category('fact-check', $post)) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'ClaimReview',
        'url' => get_permalink($post),
        'claimReviewed' => get_the_title($post),
        'reviewRating' => array(
            '@type' => 'Rating',
            'ratingValue' => '1',
            'bestRating' => '5',
            'worstRating' => '1',
        ),
        'author' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
        ),
    );
}

function myseo_build_carousel_schema($post) {
    if (!has_shortcode($post->post_content, 'gallery') && strpos($post->post_content, 'wp-block-gallery') === false) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => get_the_title($post) . ' Carousel',
        'itemListOrder' => 'http://schema.org/ItemListOrderAscending',
        'numberOfItems' => 1,
    );
}

function myseo_build_bbpress_qa_schema($post) {
    if (!function_exists('bbp_get_reply_url') || get_post_type($post) !== 'topic') {
        return null;
    }

    $replies = get_posts(array(
        'post_type' => 'reply',
        'post_parent' => $post->ID,
        'post_status' => 'publish',
        'numberposts' => 20,
    ));

    $suggested = array();
    foreach ($replies as $reply) {
        $suggested[] = array(
            '@type' => 'Answer',
            'text' => wp_trim_words(wp_strip_all_tags($reply->post_content), 40),
            'url' => get_permalink($reply),
        );
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'QAPage',
        'mainEntity' => array(
            '@type' => 'Question',
            'name' => get_the_title($post),
            'text' => wp_trim_words(wp_strip_all_tags($post->post_content), 40),
            'answerCount' => count($suggested),
            'suggestedAnswer' => $suggested,
        ),
    );
}

function myseo_fill_video_data_for_schema($post) {
    $video_url = myseo_detect_video_url($post->post_content);
    if (!$video_url) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        'name' => get_the_title($post),
        'description' => wp_trim_words(wp_strip_all_tags($post->post_content), 40),
        'contentUrl' => $video_url,
        'embedUrl' => $video_url,
    );
}


function myseo_mark_cloaked_links_as_external($content) {
    return preg_replace_callback(
        '#<a([^>]+href=["\']([^"\']+)["\'][^>]*)>#i',
        'myseo_process_link_markup',
        $content
    );
}

function myseo_process_link_markup($matches) {
    $full = $matches[0];
    $attrs = $matches[1];
    $href = $matches[2];
    $host = wp_parse_url($href, PHP_URL_HOST);
    $site_host = wp_parse_url(home_url('/'), PHP_URL_HOST);

    if (strpos($href, '/go/') !== false || ($host && $host !== $site_host)) {
        if (stripos($attrs, 'rel=') === false) {
            $full = str_replace('<a', '<a rel="nofollow sponsored external"', $full);
        }
        if (stripos($attrs, 'data-myseo-external') === false) {
            $full = str_replace('<a', '<a data-myseo-external="1"', $full);
        }
    }

    return $full;
}

function myseo_auto_generate_image_captions($content) {
    if (!myseo_module_enabled('image_seo')) {
        return $content;
    }

    return preg_replace_callback(
        '#<img([^>]*?)alt=["\']([^"\']+)["\']([^>]*)>#i',
        function ($matches) {
            $img = $matches[0];
            $alt = trim($matches[2]);
            if ($alt === '' || strpos($img, 'data-myseo-captioned=') !== false) {
                return $img;
            }
            return '<figure class="myseo-figure">' . str_replace('<img', '<img data-myseo-captioned="1"', $img) . '<figcaption>' . esc_html($alt) . '</figcaption></figure>';
        },
        $content
    );
}

function myseo_schema_matches_context($row) {
    if ($row->trigger_type === 'manual') {
        return is_singular();
    }
    if ($row->trigger_type === 'post_type' && is_singular()) {
        return get_post_type() === $row->trigger_value;
    }
    if ($row->trigger_type === 'category' && is_single()) {
        $categories = get_the_category();
        foreach ($categories as $category) {
            if ($category->slug === $row->trigger_value || $category->name === $row->trigger_value) {
                return true;
            }
        }
    }
    return false;
}

function myseo_handle_password_protected_noindex($required, $post) {
    if ($required && !headers_sent()) {
        add_action('wp_head', 'myseo_output_noindex_for_password', 0);
    }
    return $required;
}

function myseo_output_noindex_for_password() {
    echo "<meta name=\"robots\" content=\"noindex,nofollow\" />\n";
}
