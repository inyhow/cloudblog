<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_output_schema() {
    if (!myseo_module_enabled('schema')) {
        return;
    }

    if (is_singular()) {
        global $post;
        if (!$post) {
            return;
        }

        $schema = myseo_build_schema_for_post($post);
        if ($schema) {
            myseo_print_json_ld($schema);
        }

        $breadcrumb = myseo_build_breadcrumb_schema($post);
        if ($breadcrumb) {
            myseo_print_json_ld($breadcrumb);
        }
    }
}

function myseo_build_schema_for_post($post) {
    $type = get_post_meta($post->ID, '_myseo_schema_type', true);
    if ($type === 'faq') {
        return myseo_build_faq_schema($post);
    }
    if ($type === 'howto') {
        return myseo_build_howto_schema($post);
    }
    if ($type === 'product') {
        return myseo_build_product_schema($post);
    }
    return myseo_build_article_schema($post);
}

function myseo_build_article_schema($post) {
    $title = get_the_title($post);
    $description = wp_strip_all_tags(get_the_excerpt($post));
    $author_id = (int) $post->post_author;
    $author_name = get_the_author_meta('display_name', $author_id);
    $author_url = get_author_posts_url($author_id);

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $title,
        'description' => $description,
        'author' => array(
            '@type' => 'Person',
            'name' => $author_name,
            'url' => $author_url,
        ),
        'datePublished' => get_the_date('c', $post),
        'dateModified' => get_the_modified_date('c', $post),
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id' => get_permalink($post),
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
        ),
    );
}

function myseo_build_faq_schema($post) {
    $raw = get_post_meta($post->ID, '_myseo_schema_faq', true);
    $items = myseo_parse_json_array($raw);
    if (!$items) {
        return null;
    }

    $main_entity = array();
    foreach ($items as $item) {
        if (empty($item['question']) || empty($item['answer'])) {
            continue;
        }
        $main_entity[] = array(
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => $item['answer'],
            ),
        );
    }

    if (!$main_entity) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $main_entity,
    );
}

function myseo_build_howto_schema($post) {
    $raw = get_post_meta($post->ID, '_myseo_schema_howto', true);
    $items = myseo_parse_json_array($raw);
    if (!$items) {
        return null;
    }

    $steps = array();
    foreach ($items as $item) {
        if (empty($item['name']) || empty($item['text'])) {
            continue;
        }
        $steps[] = array(
            '@type' => 'HowToStep',
            'name' => $item['name'],
            'text' => $item['text'],
        );
    }

    if (!$steps) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => get_the_title($post),
        'step' => $steps,
    );
}

function myseo_build_product_schema($post) {
    $price = get_post_meta($post->ID, '_myseo_schema_product_price', true);
    $currency = get_post_meta($post->ID, '_myseo_schema_product_currency', true);
    $availability = get_post_meta($post->ID, '_myseo_schema_product_availability', true);
    $brand = get_post_meta($post->ID, '_myseo_schema_product_brand', true);

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => get_the_title($post),
        'description' => wp_strip_all_tags(get_the_excerpt($post)),
    );

    if ($brand) {
        $schema['brand'] = array(
            '@type' => 'Brand',
            'name' => $brand,
        );
    }

    if ($price && $currency) {
        $schema['offers'] = array(
            '@type' => 'Offer',
            'price' => $price,
            'priceCurrency' => $currency,
        );
        if ($availability) {
            $schema['offers']['availability'] = 'https://schema.org/' . $availability;
        }
        $schema['offers']['url'] = get_permalink($post);
    }

    return $schema;
}

function myseo_build_breadcrumb_schema($post) {
    $items = array();
    $position = 1;

    $items[] = array(
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => get_bloginfo('name'),
        'item' => home_url('/'),
    );

    if (is_page($post)) {
        $ancestors = array_reverse(get_post_ancestors($post));
        foreach ($ancestors as $ancestor_id) {
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title($ancestor_id),
                'item' => get_permalink($ancestor_id),
            );
        }
    }

    $items[] = array(
        '@type' => 'ListItem',
        'position' => $position,
        'name' => get_the_title($post),
        'item' => get_permalink($post),
    );

    if (count($items) < 2) {
        return null;
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    );
}

function myseo_print_json_ld($data) {
    echo "<script type=\"application/ld+json\">\n";
    echo wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "\n</script>\n";
}

function myseo_parse_json_array($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') {
        return null;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return null;
    }
    return $decoded;
}
