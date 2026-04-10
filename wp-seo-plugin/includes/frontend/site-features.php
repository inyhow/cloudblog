<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_site_features() {
    add_action('wp_head', 'myseo_output_google_verification', 3);
    add_filter('the_content', 'myseo_inject_site_features', 8);
    add_shortcode('myseo_breadcrumbs', 'myseo_breadcrumbs_shortcode');
    add_shortcode('myseo_toc', 'myseo_toc_shortcode');
    add_shortcode('myseo_location', 'myseo_location_shortcode');
    add_shortcode('myseo_locations', 'myseo_locations_shortcode');
}

function myseo_output_google_verification() {
    $token = myseo_get_option('google_site_verification', '');
    if ($token === '') {
        return;
    }
    echo '<meta name="google-site-verification" content="' . esc_attr($token) . "\" />\n";
}

function myseo_inject_site_features($content) {
    if (!is_singular() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $prefix = '';

    if (myseo_module_enabled('breadcrumbs') && !has_shortcode($content, 'myseo_breadcrumbs')) {
        $prefix .= myseo_render_breadcrumbs();
    }

    if (myseo_module_enabled('toc')) {
        if (has_shortcode($content, 'myseo_toc')) {
            return do_shortcode($content);
        }

        $toc = myseo_build_toc_html($content);
        if ($toc !== '') {
            $prefix .= $toc;
        }
    }

    return $prefix . do_shortcode($content);
}

function myseo_toc_shortcode($atts = array(), $content = '') {
    if (!myseo_module_enabled('toc')) {
        return '';
    }

    global $post;
    if (!$post) {
        return '';
    }

    $source = $content !== '' ? $content : $post->post_content;
    return myseo_build_toc_html($source);
}

function myseo_build_toc_html(&$content) {
    if (!preg_match_all('/<h([2-4])([^>]*)>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER)) {
        return '';
    }

    $items = array();
    $used_ids = array();

    foreach ($matches as $index => $match) {
        $level = (int) $match[1];
        $attributes = $match[2];
        $inner_html = $match[3];
        $text = trim(wp_strip_all_tags($inner_html));

        if ($text === '') {
            continue;
        }

        if (preg_match('/id=(["\'])(.*?)\1/i', $attributes, $id_match)) {
            $id = sanitize_title($id_match[2]);
        } else {
            $id = myseo_unique_heading_id($text, $used_ids);
            $replacement = '<h' . $level . $attributes . ' id="' . esc_attr($id) . '">' . $inner_html . '</h' . $level . '>';
            $content = preg_replace('/' . preg_quote($match[0], '/') . '/', str_replace('\\', '\\\\', $replacement), $content, 1);
        }

        $used_ids[] = $id;
        $items[] = array(
            'level' => $level,
            'id' => $id,
            'text' => $text,
        );
    }

    if (!$items) {
        return '';
    }

    $label = myseo_get_option('toc_heading_label', 'On this page');
    $html = '<nav class="myseo-toc" aria-label="' . esc_attr($label) . '">';
    $html .= '<div class="myseo-toc__title">' . esc_html($label) . '</div>';
    $html .= '<ol class="myseo-toc__list">';
    foreach ($items as $item) {
        $html .= '<li class="myseo-toc__item myseo-toc__item--level-' . esc_attr($item['level']) . '">';
        $html .= '<a href="#' . esc_attr($item['id']) . '">' . esc_html($item['text']) . '</a>';
        $html .= '</li>';
    }
    $html .= '</ol></nav>';

    return $html;
}

function myseo_unique_heading_id($text, $used_ids) {
    $base = sanitize_title($text);
    if ($base === '') {
        $base = 'section';
    }
    $candidate = $base;
    $suffix = 2;
    while (in_array($candidate, $used_ids, true)) {
        $candidate = $base . '-' . $suffix;
        $suffix++;
    }
    return $candidate;
}

function myseo_breadcrumbs_shortcode() {
    if (!myseo_module_enabled('breadcrumbs')) {
        return '';
    }
    return myseo_render_breadcrumbs();
}

function myseo_breadcrumbs() {
    echo myseo_breadcrumbs_shortcode();
}

function myseo_render_breadcrumbs() {
    $items = myseo_get_breadcrumb_items();
    if (count($items) < 2) {
        return '';
    }

    $separator = myseo_get_option('breadcrumbs_separator', '/');
    $html = '<nav class="myseo-breadcrumbs" aria-label="Breadcrumbs"><ol>';

    foreach ($items as $index => $item) {
        $html .= '<li>';
        if (!empty($item['url']) && $index < count($items) - 1) {
            $html .= '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
        } else {
            $html .= '<span>' . esc_html($item['label']) . '</span>';
        }
        if ($index < count($items) - 1) {
            $html .= '<span class="myseo-breadcrumbs__sep"> ' . esc_html($separator) . ' </span>';
        }
        $html .= '</li>';
    }

    $html .= '</ol></nav>';
    return $html;
}

function myseo_toc() {
    echo myseo_toc_shortcode();
}

function myseo_location_shortcode($atts = array()) {
    if (!myseo_module_enabled('local_seo')) {
        return '';
    }

    global $wpdb;
    $atts = shortcode_atts(array('id' => 0), $atts);
    $table = myseo_get_table_name('locations');
    if ((int) $atts['id'] > 0) {
        $location = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $atts['id']));
    } else {
        $location = $wpdb->get_row("SELECT * FROM {$table} ORDER BY primary_location DESC, id ASC LIMIT 1");
    }

    if (!$location) {
        return '';
    }

    $html = '<div class="myseo-location-card">';
    $html .= '<h3>' . esc_html($location->location_name) . '</h3>';
    $html .= '<p>' . esc_html(trim($location->street_address . ', ' . $location->city . ', ' . $location->region . ' ' . $location->postal_code)) . '</p>';
    if ($location->phone) {
        $html .= '<p>' . esc_html($location->phone) . '</p>';
    }
    if ($location->opening_hours) {
        $html .= '<p>' . esc_html($location->opening_hours) . '</p>';
    }
    $html .= '</div>';
    return $html;
}

function myseo_locations_shortcode() {
    if (!myseo_module_enabled('local_seo')) {
        return '';
    }

    global $wpdb;
    $table = myseo_get_table_name('locations');
    $locations = $wpdb->get_results("SELECT * FROM {$table} ORDER BY primary_location DESC, location_name ASC");
    if (!$locations) {
        return '';
    }

    $html = '<div class="myseo-location-grid">';
    foreach ($locations as $location) {
        $html .= myseo_location_shortcode(array('id' => $location->id));
    }
    $html .= '</div>';
    return $html;
}

function myseo_get_breadcrumb_items() {
    $items = array(
        array(
            'label' => get_bloginfo('name'),
            'url' => home_url('/'),
        ),
    );

    if (is_singular()) {
        global $post;
        if (!$post) {
            return $items;
        }

        if (is_singular('post')) {
            $categories = get_the_category($post->ID);
            if (!empty($categories)) {
                $primary = $categories[0];
                $items[] = array(
                    'label' => $primary->name,
                    'url' => get_category_link($primary),
                );
            }
        }

        if (is_page($post)) {
            $ancestors = array_reverse(get_post_ancestors($post));
            foreach ($ancestors as $ancestor_id) {
                $items[] = array(
                    'label' => get_the_title($ancestor_id),
                    'url' => get_permalink($ancestor_id),
                );
            }
        }

        $items[] = array(
            'label' => get_the_title($post),
            'url' => '',
        );
    } elseif (is_category()) {
        $term = get_queried_object();
        $items[] = array(
            'label' => $term ? $term->name : '',
            'url' => '',
        );
    }

    return array_filter($items, 'myseo_filter_breadcrumb_item');
}

function myseo_filter_breadcrumb_item($item) {
    return !empty($item['label']);
}
