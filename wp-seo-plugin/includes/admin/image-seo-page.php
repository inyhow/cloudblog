<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_image_seo_page() {
    add_submenu_page('myseo-dashboard', 'Image SEO', 'Image SEO', 'manage_options', 'myseo-image-seo', 'myseo_render_image_seo_page');
}

function myseo_render_image_seo_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    if (isset($_POST['myseo_image_seo_nonce']) && wp_verify_nonce($_POST['myseo_image_seo_nonce'], 'myseo_image_seo')) {
        $message = myseo_handle_image_seo_actions();
    }

    echo '<div class="wrap"><h1>Image SEO PRO</h1>';
    if ($message) {
        echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    }
    echo '<div class="myseo-card"><h2>Find and Replace / Automate Captions</h2><form method="post">';
    wp_nonce_field('myseo_image_seo', 'myseo_image_seo_nonce');
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>Find in Attachment Title</th><td><input type="text" name="myseo_image_find" class="regular-text" /></td></tr>';
    echo '<tr><th>Replace With</th><td><input type="text" name="myseo_image_replace" class="regular-text" /></td></tr>';
    echo '<tr><th>Actions</th><td><label><input type="checkbox" name="myseo_update_alt" value="1" checked /> Update alt</label><br /><label><input type="checkbox" name="myseo_update_title" value="1" checked /> Update title</label><br /><label><input type="checkbox" name="myseo_update_caption" value="1" checked /> Automate captions</label></td></tr>';
    echo '</tbody></table>';
    submit_button('Run Image SEO Update');
    echo '</form></div></div>';
}

function myseo_handle_image_seo_actions() {
    $find = isset($_POST['myseo_image_find']) ? myseo_sanitize_text($_POST['myseo_image_find']) : '';
    $replace = isset($_POST['myseo_image_replace']) ? myseo_sanitize_text($_POST['myseo_image_replace']) : '';
    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'post_status' => 'inherit',
        'numberposts' => -1,
    ));

    $count = 0;
    foreach ($attachments as $attachment) {
        $title = get_the_title($attachment);
        $updated = $find !== '' ? str_replace($find, $replace, $title) : $title;

        if (isset($_POST['myseo_update_alt'])) {
            update_post_meta($attachment->ID, '_wp_attachment_image_alt', $updated);
        }
        if (isset($_POST['myseo_update_title'])) {
            wp_update_post(array('ID' => $attachment->ID, 'post_title' => $updated));
        }
        if (isset($_POST['myseo_update_caption'])) {
            wp_update_post(array('ID' => $attachment->ID, 'post_excerpt' => $updated));
        }
        $count++;
    }

    return 'Updated ' . $count . ' image attachment(s).';
}
