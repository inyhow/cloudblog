<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_add_metabox() {
    if (!myseo_module_enabled('meta')) {
        return;
    }
    $screens = get_post_types(array('public' => true), 'names');
    foreach ($screens as $screen) {
        add_meta_box(
            'myseo_metabox',
            'MySEO',
            'myseo_render_metabox',
            $screen,
            'normal',
            'default'
        );
    }
}

function myseo_render_metabox($post) {
    wp_nonce_field('myseo_metabox', 'myseo_metabox_nonce');

    $title = get_post_meta($post->ID, '_myseo_title', true);
    $description = get_post_meta($post->ID, '_myseo_description', true);
    $canonical = get_post_meta($post->ID, '_myseo_canonical', true);
    $robots = get_post_meta($post->ID, '_myseo_robots', true);
    $focus_keyword = get_post_meta($post->ID, '_myseo_focus_keyword', true);
    $schema_type = get_post_meta($post->ID, '_myseo_schema_type', true);
    $schema_faq = get_post_meta($post->ID, '_myseo_schema_faq', true);
    $schema_product_price = get_post_meta($post->ID, '_myseo_schema_product_price', true);
    $schema_product_currency = get_post_meta($post->ID, '_myseo_schema_product_currency', true);
    $schema_product_availability = get_post_meta($post->ID, '_myseo_schema_product_availability', true);
    $schema_product_brand = get_post_meta($post->ID, '_myseo_schema_product_brand', true);
    $schema_howto = get_post_meta($post->ID, '_myseo_schema_howto', true);

    echo '<p><label for="myseo_title">SEO Title</label></p>';
    echo '<input type="text" id="myseo_title" name="myseo_title" value="' . esc_attr($title) . '" class="widefat" />';

    echo '<p><label for="myseo_description">Meta Description</label></p>';
    echo '<textarea id="myseo_description" name="myseo_description" class="widefat" rows="3">' . esc_textarea($description) . '</textarea>';

    echo '<p><label for="myseo_canonical">Canonical URL</label></p>';
    echo '<input type="url" id="myseo_canonical" name="myseo_canonical" value="' . esc_attr($canonical) . '" class="widefat" />';

    echo '<p><label for="myseo_robots">Robots (comma separated)</label></p>';
    echo '<input type="text" id="myseo_robots" name="myseo_robots" value="' . esc_attr($robots) . '" class="widefat" placeholder="index,follow" />';

    echo '<hr style="margin:16px 0;" />';
    echo '<h3>Schema</h3>';
    echo '<p><label for="myseo_schema_type">Schema Type</label></p>';
    echo '<select id="myseo_schema_type" name="myseo_schema_type" class="widefat">';
    $types = array(
        'article' => 'Article',
        'faq' => 'FAQ',
        'howto' => 'HowTo',
        'product' => 'Product',
    );
    if (!isset($types[$schema_type])) {
        $schema_type = 'article';
    }
    foreach ($types as $key => $label) {
        echo '<option value="' . esc_attr($key) . '"' . selected($schema_type, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Choose the structured data type for this post.</p>';

    echo '<div style="margin-top:10px;">';
    echo '<p><strong>FAQ JSON</strong> (array of {question, answer})</p>';
    echo '<textarea name="myseo_schema_faq" rows="4" class="widefat" placeholder=\'[{"question":"Q1","answer":"A1"}]\'>' . esc_textarea($schema_faq) . '</textarea>';
    echo '</div>';

    echo '<div style="margin-top:10px;">';
    echo '<p><strong>HowTo JSON</strong> (array of {name, text})</p>';
    echo '<textarea name="myseo_schema_howto" rows="4" class="widefat" placeholder=\'[{"name":"Step 1","text":"Do this"}]\'>' . esc_textarea($schema_howto) . '</textarea>';
    echo '</div>';

    echo '<div style="margin-top:10px;">';
    echo '<p><strong>Product Fields</strong></p>';
    echo '<input type="text" name="myseo_schema_product_price" value="' . esc_attr($schema_product_price) . '" class="widefat" placeholder="Price (e.g. 99.00)" />';
    echo '<input type="text" name="myseo_schema_product_currency" value="' . esc_attr($schema_product_currency) . '" class="widefat" placeholder="Currency (e.g. USD)" style="margin-top:6px;" />';
    echo '<input type="text" name="myseo_schema_product_availability" value="' . esc_attr($schema_product_availability) . '" class="widefat" placeholder="Availability (e.g. InStock)" style="margin-top:6px;" />';
    echo '<input type="text" name="myseo_schema_product_brand" value="' . esc_attr($schema_product_brand) . '" class="widefat" placeholder="Brand (optional)" style="margin-top:6px;" />';
    echo '</div>';

    echo '<hr style="margin:16px 0;" />';
    echo '<h3>Content Analysis</h3>';
    echo '<p><label for="myseo_focus_keyword">Focus Keyword</label></p>';
    echo '<input type="text" id="myseo_focus_keyword" name="myseo_focus_keyword" value="' . esc_attr($focus_keyword) . '" class="widefat" />';

    $analysis = myseo_build_analysis($post, $focus_keyword, $title, $description);
    echo '<ul style="margin-left:18px;list-style:disc;">';
    foreach ($analysis as $item) {
        $color = $item['ok'] ? 'green' : 'red';
        echo '<li><span style="color:' . esc_attr($color) . ';">' . esc_html($item['label']) . '</span></li>';
    }
    echo '</ul>';
}

function myseo_save_metabox($post_id) {
    if (!myseo_module_enabled('meta')) {
        return;
    }
    if (!isset($_POST['myseo_metabox_nonce']) || !wp_verify_nonce($_POST['myseo_metabox_nonce'], 'myseo_metabox')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['myseo_title'])) {
        update_post_meta($post_id, '_myseo_title', myseo_sanitize_text($_POST['myseo_title']));
    }
    if (isset($_POST['myseo_description'])) {
        update_post_meta($post_id, '_myseo_description', myseo_sanitize_text($_POST['myseo_description']));
    }
    if (isset($_POST['myseo_canonical'])) {
        update_post_meta($post_id, '_myseo_canonical', esc_url_raw(wp_unslash($_POST['myseo_canonical'])));
    }
    if (isset($_POST['myseo_robots'])) {
        update_post_meta($post_id, '_myseo_robots', myseo_sanitize_text($_POST['myseo_robots']));
    }
    if (isset($_POST['myseo_focus_keyword'])) {
        update_post_meta($post_id, '_myseo_focus_keyword', myseo_sanitize_text($_POST['myseo_focus_keyword']));
    }
    if (isset($_POST['myseo_schema_type'])) {
        update_post_meta($post_id, '_myseo_schema_type', myseo_sanitize_text($_POST['myseo_schema_type']));
    }
    if (isset($_POST['myseo_schema_faq'])) {
        update_post_meta($post_id, '_myseo_schema_faq', wp_unslash($_POST['myseo_schema_faq']));
    }
    if (isset($_POST['myseo_schema_howto'])) {
        update_post_meta($post_id, '_myseo_schema_howto', wp_unslash($_POST['myseo_schema_howto']));
    }
    if (isset($_POST['myseo_schema_product_price'])) {
        update_post_meta($post_id, '_myseo_schema_product_price', myseo_sanitize_text($_POST['myseo_schema_product_price']));
    }
    if (isset($_POST['myseo_schema_product_currency'])) {
        update_post_meta($post_id, '_myseo_schema_product_currency', myseo_sanitize_text($_POST['myseo_schema_product_currency']));
    }
    if (isset($_POST['myseo_schema_product_availability'])) {
        update_post_meta($post_id, '_myseo_schema_product_availability', myseo_sanitize_text($_POST['myseo_schema_product_availability']));
    }
    if (isset($_POST['myseo_schema_product_brand'])) {
        update_post_meta($post_id, '_myseo_schema_product_brand', myseo_sanitize_text($_POST['myseo_schema_product_brand']));
    }
}

function myseo_build_analysis($post, $focus_keyword, $title, $description) {
    $content = wp_strip_all_tags($post->post_content);
    $word_count = str_word_count($content);
    $focus_keyword = trim((string) $focus_keyword);
    $title_len = myseo_strlen(trim((string) $title));
    $desc_len = myseo_strlen(trim((string) $description));
    $heading_counts = myseo_count_headings($post->post_content);
    $link_counts = myseo_count_links($post->post_content);
    $image_alt = myseo_count_images_missing_alt($post->post_content);

    $items = array();
    $items[] = array(
        'label' => 'Word count at least 300 (current: ' . $word_count . ')',
        'ok' => $word_count >= 300,
    );
    $items[] = array(
        'label' => 'Title length 30-60 (current: ' . $title_len . ')',
        'ok' => $title_len >= 30 && $title_len <= 60,
    );
    $items[] = array(
        'label' => 'Description length 120-160 (current: ' . $desc_len . ')',
        'ok' => $desc_len >= 120 && $desc_len <= 160,
    );
    $items[] = array(
        'label' => 'Has at least one H2 heading (current: ' . $heading_counts['h2'] . ')',
        'ok' => $heading_counts['h2'] > 0,
    );
    $items[] = array(
        'label' => 'Has at least one internal link (current: ' . $link_counts['internal'] . ')',
        'ok' => $link_counts['internal'] > 0,
    );
    $items[] = array(
        'label' => 'Has at least one external link (current: ' . $link_counts['external'] . ')',
        'ok' => $link_counts['external'] > 0,
    );
    $items[] = array(
        'label' => 'Images missing alt text (current: ' . $image_alt . ')',
        'ok' => $image_alt === 0,
    );
    if ($focus_keyword !== '') {
        $items[] = array(
            'label' => 'Focus keyword appears in title',
            'ok' => stripos($title, $focus_keyword) !== false,
        );
        $items[] = array(
            'label' => 'Focus keyword appears in description',
            'ok' => stripos($description, $focus_keyword) !== false,
        );
        $items[] = array(
            'label' => 'Focus keyword appears in content',
            'ok' => stripos($content, $focus_keyword) !== false,
        );
    } else {
        $items[] = array(
            'label' => 'Focus keyword is set',
            'ok' => false,
        );
    }

    return $items;
}

function myseo_strlen($value) {
    if (function_exists('mb_strlen')) {
        return mb_strlen($value);
    }
    return strlen($value);
}

function myseo_count_headings($html) {
    $counts = array('h1' => 0, 'h2' => 0, 'h3' => 0);
    foreach (array('h1', 'h2', 'h3') as $tag) {
        if (preg_match_all('#<' . $tag . '[^>]*>#i', $html, $matches)) {
            $counts[$tag] = count($matches[0]);
        }
    }
    return $counts;
}

function myseo_count_links($html) {
    $internal = 0;
    $external = 0;
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

    if (preg_match_all('#<a[^>]+href=["\']([^"\']+)["\']#i', $html, $matches)) {
        foreach ($matches[1] as $href) {
            $host = wp_parse_url($href, PHP_URL_HOST);
            if (!$host || $host === $site_host) {
                $internal++;
            } else {
                $external++;
            }
        }
    }

    return array('internal' => $internal, 'external' => $external);
}

function myseo_count_images_missing_alt($html) {
    $missing = 0;
    if (preg_match_all('#<img[^>]*>#i', $html, $matches)) {
        foreach ($matches[0] as $img) {
            if (!preg_match('#\salt=["\'].*?["\']#i', $img)) {
                $missing++;
            }
        }
    }
    return $missing;
}
