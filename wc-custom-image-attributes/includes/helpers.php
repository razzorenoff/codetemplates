<?php
if (!defined('ABSPATH')) {
    exit; // Запрет прямого доступа
}

/**
 * Замена переменных в строке шаблона.
 *
 * @param string $template Шаблон с переменными.
 * @param WC_Product $product Объект товара WooCommerce.
 * @param int|null $index Индекс изображения (для галереи).
 * @return string Строка с заменёнными переменными.
 */
function wc_custom_replace_template_variables($template, $product, $index = null) {
    // Получение категорий товара
    $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'all']);
    $category_names = wp_list_pluck($categories, 'name'); // Получаем только названия категорий

    // Определение последней категории (самой вложенной)
    $last_category = '';
    if (!empty($categories)) {
        // Сортируем категории по уровню вложенности
        usort($categories, function ($a, $b) {
            return $a->parent - $b->parent;
        });

        // Берём самую вложенную (последнюю в сортировке)
        $last_category = end($categories)->name;
    }

    // Список поддерживаемых переменных
    $variables = [
        '{name}' => $product->get_name(), // Название товара
        '{sku}' => $product->get_sku(), // Артикул
        '{category}' => $category_names ? $category_names[0] : '', // Первая категория
        '{last_category}' => $last_category, // Последняя категория
        '{price}' => wc_price($product->get_price()), // Цена товара
        '{index}' => $index !== null ? $index : '', // Индекс изображения
        '{short_description}' => $product->get_short_description(), // Краткое описание
        '{description}' => $product->get_description(), // Полное описание
        '{date}' => date('Y-m-d'), // Текущая дата
        '{time}' => date('H:i'), // Текущее время
    ];

    // Замена всех переменных в шаблоне
    return str_replace(array_keys($variables), array_values($variables), $template);
}

/**
 * Получение максимального количества изображений в галереях товаров категории.
 */
function wc_custom_get_max_gallery_images($category_id) {
    $args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ],
        ],
    ];
    $query = new WP_Query($args);
    $max_images = 0;

    while ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID());
        $gallery_images = $product->get_gallery_image_ids();
        $max_images = max($max_images, count($gallery_images));
    }
    wp_reset_postdata();

    return $max_images;
}

/**
 * Массовое обновление товаров через AJAX.
 */
add_action('wp_ajax_wc_custom_bulk_update', 'wc_custom_bulk_update');

function wc_custom_bulk_update() {
    $products = wc_get_products(['limit' => -1]);

    foreach ($products as $product) {
        wc_custom_update_image_attributes($product); // Перезапись ALT и TITLE
    }

    wp_send_json_success();
}
