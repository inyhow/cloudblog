<?php

if (!defined('ABSPATH')) {
    exit;
}

function myseo_register_elementor_integration() {
    if (!did_action('elementor/loaded')) {
        return;
    }

    add_action('elementor/widgets/register', 'myseo_register_elementor_widgets');
}

function myseo_register_elementor_widgets($widgets_manager) {
    if (!class_exists('\\Elementor\\Widget_Base')) {
        return;
    }

    if (!class_exists('MySEO_Elementor_Breadcrumbs_Widget')) {
        class MySEO_Elementor_Breadcrumbs_Widget extends \Elementor\Widget_Base {
            public function get_name() {
                return 'myseo_breadcrumbs';
            }

            public function get_title() {
                return 'MySEO Breadcrumbs';
            }

            public function get_icon() {
                return 'eicon-posts-grid';
            }

            public function get_categories() {
                return array('general');
            }

            protected function render() {
                echo myseo_render_breadcrumbs();
            }
        }
    }

    if (!class_exists('MySEO_Elementor_Faq_Widget')) {
        class MySEO_Elementor_Faq_Widget extends \Elementor\Widget_Base {
            public function get_name() {
                return 'myseo_faq_schema';
            }

            public function get_title() {
                return 'MySEO FAQ Schema';
            }

            public function get_icon() {
                return 'eicon-help-o';
            }

            public function get_categories() {
                return array('general');
            }

            protected function render() {
                echo '<div class="myseo-faq-widget">FAQ Schema is injected from post content or MySEO FAQ meta fields.</div>';
            }
        }
    }

    $widgets_manager->register(new MySEO_Elementor_Breadcrumbs_Widget());
    $widgets_manager->register(new MySEO_Elementor_Faq_Widget());
}

add_action('init', 'myseo_register_elementor_integration');
