<?php
/**
 * Plugin Name: Custom Price Override for Countertops
 * Plugin URI: https://github.com/razzorenoff
 * Description: Overrides product prices based on JSON data from ACF field, visible only to administrators.
 * Version: 1.2
 * Author: @maincoder_ru
 * Author URI: https://github.com/razzorenoff
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Фильтр для подмены отображаемой цены в каталоге и на странице товара
add_filter('woocommerce_get_price_html', 'custom_override_price_display', 10, 2);
function custom_override_price_display($price_html, $product)
{
    if (!current_user_can('administrator')) {
        return $price_html; // Только администраторы видят измененные цены
    }

    if ($product->get_type() !== 'variable') {
        return $price_html; // Только для вариативных товаров
    }

    $product_id = $product->get_id();
    $countertops_product = get_field('field_6150cee7c4ae8', $product_id); // Используем конкретный ключ ACF
    if (!$countertops_product) {
        return $price_html; // Применяем только если чекбокс "Countertops product" включен
    }

    $json_data = get_field('field_25d55ad283aa40', $product_id); // Получаем JSON с данными
    if (!$json_data) {
        return $price_html;
    }

    $data = json_decode($json_data, true);
    if (!isset($data['variations'])) {
        return $price_html;
    }

    $min_price = PHP_INT_MAX;
    $max_price = 0;

    // Поиск минимальной и максимальной цены в JSON
    foreach ($data['variations'] as $material => $thicknesses) {
        foreach ($thicknesses as $thickness => $widths) {
            foreach ($widths as $width => $lengths) {
                foreach ($lengths as $length => $price) {
                    $min_price = min($min_price, $price);
                    $max_price = max($max_price, $price);
                }
            }
        }
    }

    if ($min_price === PHP_INT_MAX || $max_price === 0) {
        return $price_html;
    }

    // Логируем найденные цены
    echo "<script>console.log('Custom Price Override: Min price: " . $min_price . " | Max price: " . $max_price . "');</script>";

    return '<span class="price">' . wc_price($min_price) . ' - ' . wc_price($max_price) . '</span>';
}

// Проверка соответствия вариаций JSON и WooCommerce + подмена цен
add_action('wp_footer', 'custom_check_product_variations');
function custom_check_product_variations()
{
    if (!current_user_can('administrator')) {
        return; // Доступно только для админов
    }

    global $product;

    // Проверяем, что мы на странице товара WooCommerce
    if (!$product || !is_product() || $product->get_type() !== 'variable') {
        return;
    }

    $product_id = $product->get_id();
    $countertops_product = get_field('field_6150cee7c4ae8', $product_id);
    if (!$countertops_product) {
        return; // Только если чекбокс "Countertops product" включен
    }

    $json_data = get_field('field_25d55ad283aa40', $product_id);
    if (!$json_data) {
        echo "<script>console.warn('No JSON data found for product ID: {$product_id}');</script>";
        return;
    }

    $data = json_decode($json_data, true);
    if (!isset($data['variations'])) {
        echo "<script>console.warn('No variations found in JSON for product ID: {$product_id}');</script>";
        return;
    }

    // Получаем все вариации текущего открытого товара
    $variation_ids = $product->get_children();
    $existing_variations = [];

    foreach ($variation_ids as $variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation) {
            continue;
        }

        $attributes = $variation->get_attributes(); // Получаем все атрибуты вариации

        // Формируем ключ вариации, объединяя ВСЕ атрибуты
        $attribute_values = [];
        foreach ($attributes as $attribute_name => $attribute_value) {
            $clean_name = str_replace('pa_', '', $attribute_name); // Убираем префикс "pa_"
            $attribute_values[] = strtolower(trim($attribute_value));
        }
        $variation_key = implode('-', $attribute_values); // Объединяем значения в строку

        // Сохраняем ID вариации по ее атрибутам
        $existing_variations[$variation_key] = $variation;
    }

    // Логируем найденные вариации в консоль
    echo "<script>console.log('Existing variations for product ID {$product_id}:', " . json_encode(array_keys($existing_variations)) . ");</script>";

    // Проверяем соответствие вариаций из JSON с WooCommerce
    foreach ($data['variations'] as $material => $thicknesses) {
        foreach ($thicknesses as $thickness => $widths) {
            foreach ($widths as $width => $lengths) {
                foreach ($lengths as $length => $price) {
                    $variation_key = strtolower(trim("$material-$thickness-$width-$length"));

                    if (isset($existing_variations[$variation_key])) {
                        // Найдено соответствие, подменяем цену
                        $variation = $existing_variations[$variation_key];
                        $original_price = $variation->get_regular_price();
                        $variation->set_price($price);
                        $variation->set_regular_price($price);

                        echo "<script>console.log('Updated variation: {$variation_key} | Old price: {$original_price} | New price: {$price}');</script>";
                    } else {
                        // Вариации из JSON нет в WooCommerce
                        echo "<script>console.warn('Missing variation in WooCommerce: {$variation_key}');</script>";
                    }
                }
            }
        }
    }
}
