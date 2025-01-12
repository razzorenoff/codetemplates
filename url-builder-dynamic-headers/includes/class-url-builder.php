<?php

if (!defined('ABSPATH')) {
    exit;
}

class URL_Builder {
    public static function init() {
        // Подключаем скрипты и стили
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);

        // Добавляем пользовательские правила для rewrite
        add_action('init', [self::class, 'add_rewrite_rules']);

        // Регистрируем переменную для фильтров
        add_filter('query_vars', [self::class, 'register_query_vars']);

        // Сбрасываем правила rewrite при активации плагина
        register_activation_hook(__FILE__, [self::class, 'flush_rewrite_rules']);

        // Сбрасываем правила rewrite при деактивации плагина
        register_deactivation_hook(__FILE__, [self::class, 'flush_rewrite_rules']);
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

    public static function add_rewrite_rules() {
        add_rewrite_rule(
            '^bildungsangebot/(.+)/?$',
            'index.php?pagename=bildungsangebot&filters=$matches[1]',
            'top'
        );
    }

    public static function register_query_vars($vars) {
        $vars[] = 'filters';
        return $vars;
    }

    public static function flush_rewrite_rules() {
        flush_rewrite_rules();
    }
}