<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_import_page() {
    add_options_page(
        'MySEO Import',
        'MySEO Import',
        'manage_options',
        'myseo-import',
        'myseo_render_import_page'
    );
}

function myseo_render_import_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $result = null;
    if (isset($_POST['myseo_import_nonce']) && wp_verify_nonce($_POST['myseo_import_nonce'], 'myseo_import')) {
        $source = isset($_POST['myseo_import_source']) ? sanitize_text_field(wp_unslash($_POST['myseo_import_source'])) : '';
        if ($source === 'yoast') {
            $result = myseo_import_from_yoast();
        } elseif ($source === 'rankmath') {
            $result = myseo_import_from_rankmath();
        } else {
            $result = array('imported' => 0, 'skipped' => 0, 'message' => 'Unsupported source selected.');
        }
    }

    echo '<div class="wrap">';
    echo '<h1>MySEO Import</h1>';
    echo '<p>Import SEO metadata from other plugins.</p>';

    if ($result) {
        echo '<div class="notice notice-success"><p>';
        echo esc_html($result['message']);
        echo ' Imported: ' . esc_html($result['imported']) . ', Skipped: ' . esc_html($result['skipped']);
        echo '</p></div>';
    }

    echo '<form method="post">';
    wp_nonce_field('myseo_import', 'myseo_import_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th><label for="myseo_import_source">Source</label></th>';
    echo '<td><select id="myseo_import_source" name="myseo_import_source">';
    echo '<option value="yoast">Yoast SEO</option>';
    echo '<option value="rankmath">Rank Math</option>';
    echo '</select></td></tr>';
    echo '</tbody></table>';
    submit_button('Run Import');
    echo '</form>';
    echo '</div>';
}

function myseo_import_from_yoast() {
    global $wpdb;
    $meta_map = array(
        '_yoast_wpseo_title' => '_myseo_title',
        '_yoast_wpseo_metadesc' => '_myseo_description',
        '_yoast_wpseo_canonical' => '_myseo_canonical',
        '_yoast_wpseo_focuskw' => '_myseo_focus_keyword',
        '_yoast_wpseo_meta-robots-noindex' => '_myseo_robots',
    );

    $imported = 0;
    $skipped = 0;

    $posts = get_posts(array(
        'post_type' => get_post_types(array('public' => true), 'names'),
        'post_status' => array('publish', 'draft', 'private', 'future'),
        'numberposts' => -1,
        'fields' => 'ids',
    ));

    foreach ($posts as $post_id) {
        $did_import = false;
        foreach ($meta_map as $from => $to) {
            $value = get_post_meta($post_id, $from, true);
            if ($value === '' || $value === null) {
                continue;
            }
            if ($from === '_yoast_wpseo_meta-robots-noindex') {
                $value = $value === '1' ? 'noindex,follow' : 'index,follow';
            }
            update_post_meta($post_id, $to, $value);
            $did_import = true;
        }
        if ($did_import) {
            $imported++;
        } else {
            $skipped++;
        }
    }

    return array(
        'imported' => $imported,
        'skipped' => $skipped,
        'message' => 'Yoast import completed.',
    );
}

function myseo_import_from_rankmath() {
    $meta_map = array(
        '_rank_math_title' => '_myseo_title',
        '_rank_math_description' => '_myseo_description',
        '_rank_math_canonical_url' => '_myseo_canonical',
        '_rank_math_focus_keyword' => '_myseo_focus_keyword',
        '_rank_math_robots' => '_myseo_robots',
    );

    $imported = 0;
    $skipped = 0;

    $posts = get_posts(array(
        'post_type' => get_post_types(array('public' => true), 'names'),
        'post_status' => array('publish', 'draft', 'private', 'future'),
        'numberposts' => -1,
        'fields' => 'ids',
    ));

    foreach ($posts as $post_id) {
        $did_import = false;
        foreach ($meta_map as $from => $to) {
            $value = get_post_meta($post_id, $from, true);
            if ($value === '' || $value === null) {
                continue;
            }
            if ($from === '_rank_math_focus_keyword' && is_array($value)) {
                $value = isset($value[0]) ? $value[0] : '';
            }
            if ($from === '_rank_math_robots') {
                if (is_array($value)) {
                    $value = implode(',', array_filter($value));
                } elseif (!is_string($value)) {
                    $value = '';
                }
            }
            if ($value === '') {
                continue;
            }
            update_post_meta($post_id, $to, $value);
            $did_import = true;
        }
        if ($did_import) {
            $imported++;
        } else {
            $skipped++;
        }
    }

    return array(
        'imported' => $imported,
        'skipped' => $skipped,
        'message' => 'Rank Math import completed.',
    );
}
