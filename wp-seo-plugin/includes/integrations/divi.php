<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_divi_integration() {
    if (!defined('ET_BUILDER_VERSION')) {
        return;
    }

    add_filter('the_content', 'myseo_divi_faq_bridge', 25);
}

function myseo_divi_faq_bridge($content) {
    if (strpos($content, 'et_pb_accordion') === false) {
        return $content;
    }

    if (!preg_match_all('/et_pb_accordion_item_title[^>]*>(.*?)<\/.*?et_pb_accordion_item_content[^>]*>(.*?)<\//is', $content, $matches, PREG_SET_ORDER)) {
        return $content;
    }

    $faq = array();
    foreach ($matches as $match) {
        $faq[] = array(
            'question' => wp_strip_all_tags($match[1]),
            'answer' => wp_strip_all_tags($match[2]),
        );
    }

    if (!$faq) {
        return $content;
    }

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array(),
    );

    foreach ($faq as $item) {
        $schema['mainEntity'][] = array(
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => $item['answer'],
            ),
        );
    }

    return $content . '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

add_action('init', 'myseo_register_divi_integration');
