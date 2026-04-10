<?php
/**
 * Plugin Name: MySEO
 * Description: A modular SEO plugin with metadata, basic sitemap, and social tags.
 * Version: 0.1.0
 * Author: Your Team
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MYSEO_PLUGIN_VERSION', '0.1.0');
define('MYSEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MYSEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MYSEO_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once MYSEO_PLUGIN_DIR . 'includes/bootstrap.php';

register_activation_hook(__FILE__, 'myseo_activate');
register_deactivation_hook(__FILE__, 'myseo_deactivate');

myseo_bootstrap();
