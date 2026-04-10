<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_sitemap() {
    if (!myseo_module_enabled('sitemap') && !myseo_module_enabled('news_sitemap') && !myseo_module_enabled('video_sitemap')) {
        return;
    }
    add_action('init', 'myseo_register_sitemap_rewrite');
    add_filter('query_vars', 'myseo_register_sitemap_query_var');
    add_action('template_redirect', 'myseo_render_sitemap');
}

function myseo_register_sitemap_rewrite() {
    add_rewrite_rule('myseo-sitemap\.xml$', 'index.php?myseo_sitemap=1', 'top');
    add_rewrite_rule('myseo-news-sitemap\.xml$', 'index.php?myseo_news_sitemap=1', 'top');
    add_rewrite_rule('myseo-video-sitemap\.xml$', 'index.php?myseo_video_sitemap=1', 'top');
}

function myseo_register_sitemap_query_var($vars) {
    $vars[] = 'myseo_sitemap';
    $vars[] = 'myseo_news_sitemap';
    $vars[] = 'myseo_video_sitemap';
    return $vars;
}

function myseo_render_sitemap() {
    if (get_query_var('myseo_news_sitemap')) {
        myseo_render_news_sitemap();
        return;
    }

    if (get_query_var('myseo_video_sitemap')) {
        myseo_render_video_sitemap();
        return;
    }

    if (!get_query_var('myseo_sitemap')) {
        return;
    }

    $posts = get_posts(array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'numberposts' => 2000,
        'orderby' => 'modified',
        'order' => 'DESC',
    ));

    header('Content-Type: application/xml; charset=' . get_bloginfo('charset'), true);

    echo '<?xml version="1.0" encoding="' . esc_attr(get_bloginfo('charset')) . "\"?>\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($posts as $post) {
        $loc = get_permalink($post);
        $lastmod = get_the_modified_time('c', $post);
        echo "<url>\n";
        echo '<loc>' . esc_url($loc) . "</loc>\n";
        echo '<lastmod>' . esc_html($lastmod) . "</lastmod>\n";
        echo "</url>\n";
    }

    echo "</urlset>";
    exit;
}

function myseo_render_news_sitemap() {
    if (!myseo_module_enabled('news_sitemap')) {
        return;
    }

    $publication_name = myseo_get_option('news_publication_name', get_bloginfo('name'));
    $language = str_replace('_', '-', get_locale());
    $posts = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'numberposts' => 100,
        'date_query' => array(
            array('after' => '2 days ago'),
        ),
    ));

    header('Content-Type: application/xml; charset=' . get_bloginfo('charset'), true);
    echo '<?xml version="1.0" encoding="' . esc_attr(get_bloginfo('charset')) . "\"?>\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
    foreach ($posts as $post) {
        echo '<url>';
        echo '<loc>' . esc_url(get_permalink($post)) . '</loc>';
        echo '<news:news>';
        echo '<news:publication><news:name>' . esc_html($publication_name) . '</news:name><news:language>' . esc_html($language) . '</news:language></news:publication>';
        echo '<news:publication_date>' . esc_html(get_the_date('c', $post)) . '</news:publication_date>';
        echo '<news:title>' . esc_html(get_the_title($post)) . '</news:title>';
        echo '</news:news>';
        echo '</url>';
    }
    echo '</urlset>';
    exit;
}

function myseo_render_video_sitemap() {
    if (!myseo_module_enabled('video_sitemap')) {
        return;
    }

    $posts = get_posts(array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'numberposts' => 200,
    ));

    header('Content-Type: application/xml; charset=' . get_bloginfo('charset'), true);
    echo '<?xml version="1.0" encoding="' . esc_attr(get_bloginfo('charset')) . "\"?>\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

    foreach ($posts as $post) {
        $video_url = myseo_detect_video_url($post->post_content);
        if (!$video_url) {
            continue;
        }
        echo '<url>';
        echo '<loc>' . esc_url(get_permalink($post)) . '</loc>';
        echo '<video:video>';
        echo '<video:title>' . esc_html(get_the_title($post)) . '</video:title>';
        echo '<video:description>' . esc_html(wp_trim_words(wp_strip_all_tags($post->post_content), 30)) . '</video:description>';
        echo '<video:content_loc>' . esc_url($video_url) . '</video:content_loc>';
        echo '</video:video>';
        echo '</url>';
    }

    echo '</urlset>';
    exit;
}
