<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_frontend() {
    add_action('wp_head', 'myseo_output_meta', 1);
    add_action('wp_head', 'myseo_output_schema', 2);
}

function myseo_output_meta() {
    if (!myseo_module_enabled('meta')) {
        return;
    }

    if (is_singular()) {
        global $post;
        if (!$post) {
            return;
        }

        $title = get_post_meta($post->ID, '_myseo_title', true);
        $description = get_post_meta($post->ID, '_myseo_description', true);
        $canonical = get_post_meta($post->ID, '_myseo_canonical', true);
        $robots = get_post_meta($post->ID, '_myseo_robots', true);

        $title = myseo_resolve_template($title, $post);
        $description = myseo_resolve_template($description, $post, true);

        if ($title) {
            echo '<meta name="title" content="' . esc_attr($title) . "\" />\n";
        }
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . "\" />\n";
        }
        if ($canonical) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . "\" />\n";
        }
        if ($robots) {
            echo '<meta name="robots" content="' . esc_attr($robots) . "\" />\n";
        }

        myseo_output_social($title, $description, $canonical);
    }
}

function myseo_output_social($title, $description, $canonical) {
    if (!myseo_module_enabled('social')) {
        return;
    }

    $default_image = myseo_get_option('default_social_image', '');
    $url = $canonical ? $canonical : get_permalink();

    if ($title) {
        echo '<meta property="og:title" content="' . esc_attr($title) . "\" />\n";
    }
    if ($description) {
        echo '<meta property="og:description" content="' . esc_attr($description) . "\" />\n";
    }
    echo '<meta property="og:type" content="article" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . "\" />\n";

    if ($default_image) {
        echo '<meta property="og:image" content="' . esc_url($default_image) . "\" />\n";
    }

    if ($title) {
        echo '<meta name="twitter:title" content="' . esc_attr($title) . "\" />\n";
    }
    if ($description) {
        echo '<meta name="twitter:description" content="' . esc_attr($description) . "\" />\n";
    }
    if ($default_image) {
        echo '<meta name="twitter:image" content="' . esc_url($default_image) . "\" />\n";
    }
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
}

function myseo_resolve_template($value, $post, $fallback_to_global = false) {
    $value = trim((string) $value);
    if ($value === '' && $fallback_to_global) {
        $value = myseo_get_option('site_description_template', '%excerpt%');
    }
    if ($value === '') {
        $value = myseo_get_option('site_title_template', '%title% - %site%');
    }

    $replacements = array(
        '%title%' => get_the_title($post),
        '%site%' => get_bloginfo('name'),
        '%excerpt%' => wp_strip_all_tags(get_the_excerpt($post)),
    );

    return str_replace(array_keys($replacements), array_values($replacements), $value);
}
