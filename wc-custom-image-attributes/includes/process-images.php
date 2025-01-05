<?php
if (!defined('ABSPATH')) {
    exit; // Запрет прямого доступа
}

/**
 * Обновление ALT и TITLE для изображений товара.
 *
 * @param WC_Product $product Объект товара WooCommerce.
 */
function wc_custom_update_image_attributes($product) {
    $templates = get_option('wc_custom_templates', []); // Получение шаблонов из настроек
    $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'slugs']); // Получение категорий товара

    foreach ($categories as $category_slug) {
        if (!isset($templates[$category_slug])) {
            continue; // Пропускаем категории без настроенных шаблонов
        }

        $template = $templates[$category_slug];

        // 1. Обновляем основное изображение
        $main_image_id = $product->get_image_id();
        if ($main_image_id) {
            // Генерация ALT и TITLE из шаблонов
            $alt = wc_custom_replace_template_variables($template['alt'] ?? '', $product);
            $title = wc_custom_replace_template_variables($template['title'] ?? '', $product);

            // Пример для основного изображения:
            // ALT: "{last_category} - {name}" → "Кирпичи - Кирпич утолщённый пустотелый Белый"
            // TITLE: "{name}" → "Кирпич утолщённый пустотелый Белый"

            // Обновляем ALT и TITLE для основного изображения
            update_post_meta($main_image_id, '_wp_attachment_image_alt', $alt);
            wp_update_post(['ID' => $main_image_id, 'post_title' => $title]);
        }

        // 2. Обновляем изображения в галерее
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $index => $gallery_id) {
            // Генерация ALT и TITLE для каждого изображения галереи
            $alt = wc_custom_replace_template_variables($template["alt_gallery_" . ($index + 1)] ?? '', $product, $index + 1);
            $title = wc_custom_replace_template_variables($template["title_gallery_" . ($index + 1)] ?? '', $product, $index + 1);

            // Пример для галереи:
            // ALT: "{name} - вид {index}" → "Кирпич утолщённый пустотелый Белый - вид 2"
            // TITLE: "{name}" → "Кирпич утолщённый пустотелый Белый"

            // Обновляем ALT и TITLE для изображения в галерее
            update_post_meta($gallery_id, '_wp_attachment_image_alt', $alt);
            wp_update_post(['ID' => $gallery_id, 'post_title' => $title]);
        }
    }
}
