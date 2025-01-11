<?php

if (!defined('ABSPATH')) {
    exit;
}

class URL_Builder {
    public static function init() {
        // Add scripts and styles only for the Bildungsangebot page
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function enqueue_assets() {
        if (is_page('bildungsangebot')) {
            wp_enqueue_script(
                'url-builder-js',
                plugin_dir_url(__DIR__) . 'assets/js/url-builder.js',
                ['jquery'],
                '1.0',
                true
            );

            wp_localize_script('url-builder-js', 'URLBuilder', [
                'ajax_url' => admin_url('admin-ajax.php'),
            ]);
        }
    }
}
