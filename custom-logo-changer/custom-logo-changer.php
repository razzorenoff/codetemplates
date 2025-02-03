<?php
/**
 * Plugin Name: Custom Logo Changer
 * Description: Use Elementor logo settings if configured, otherwise fallback to plugin settings for sticky and hover functionality.
 * Author: @maincoder_ru
 * Author URI: https://github.com/razzorenoff
 * Version: 4.2
 */

// Подключаем JavaScript для логики замены логотипа
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('custom-logo-changer', plugin_dir_url(__FILE__) . 'js/custom-logo-changer.js', [], null, true);

    // Проверяем настройки Elementor для текущей страницы/поста
    $elementor_settings = get_post_meta(get_the_ID(), '_elementor_page_settings', true);

    // Проверяем настройки логотипов через Elementor
    $elementor_default_logo = $elementor_settings['styler_page_header_logo']['url'] ?? null;
    $elementor_sticky_logo = $elementor_settings['styler_page_header_sticky_logo']['url'] ?? null;

    // Проверяем текущую страницу
    $is_shop_page = is_shop(); // Страница магазина
    $is_product_page = is_singular('product'); // Страница отдельного товара
    $is_blog_post = is_singular('post'); // Статья блога

    // Передаём данные в JavaScript
    wp_localize_script('custom-logo-changer', 'stylerSettings', [
        'elementor_default_logo' => $elementor_default_logo, // Логотип по умолчанию из Elementor
        'elementor_sticky_logo' => $elementor_sticky_logo, // Sticky-логотип из Elementor
        'sticky_logo' => styler_settings('sticky_logo')['url'] ?? '', // Цветной логотип
        'default_logo' => styler_settings('img_logo')['url'] ?? '', // Белый логотип
        'is_shop_page' => $is_shop_page, // Флаг для страницы магазина
        'is_product_page' => $is_product_page, // Флаг для страницы товара
        'is_blog_post' => $is_blog_post, // Флаг для статьи блога
    ]);
});

// Подключаем CSS-стили для управления отображением логотипов
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('custom-logo-changer-styles', plugin_dir_url(__FILE__) . 'css/custom-logo-styles.css');
});