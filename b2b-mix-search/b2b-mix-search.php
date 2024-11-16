<?php
/*
Plugin Name: B2B Mix Search
Version: 1.1.0
Description: Mix search for B2B-Services with WooCommerce integration
Author: Vladimir Razzorenov (@maincoder_ru)
Author URI: https://maincoder.ru
Plugin URI: https://maincoder.ru
Requires PHP: 7.2
Requires at least: 5.9
Text Domain: b2b-mix-search
Domain Path: /languages/
*/

// === Локализация плагина ===
function b2b_mix_search_load_textdomain() {
    load_plugin_textdomain('b2b-mix-search', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'b2b_mix_search_load_textdomain');

// === Регистрация REST API маршрутов ===
function b2b_mix_search_register_routes() {
    // Основной поиск через Jet_Search
    register_rest_route('jet-search/v1', '/search-posts/', [
        'methods'  => 'GET',
        'callback' => 'b2b_mix_search_ajax_callback',
        'permission_callback' => '__return_true',
    ]);

    // Отладочный маршрут
    register_rest_route('b2b_debug/v1', '/custom-search/', [
        'methods'  => 'GET',
        'callback' => 'b2b_mix_search_debug',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'b2b_mix_search_register_routes');

// === Основной обработчик AJAX поиска ===
function b2b_mix_search_ajax_callback($request) {
    if (class_exists('Jet_Search_Rest_Search_Route')) {
        // Подкласс Jet_Search для кастомного поведения
        class b2b_mix_search extends Jet_Search_Rest_Search_Route {
            /**
             * Переопределение метода callback для обработки API запроса
             */
            public function callback($request) {
                $params = $request->get_params();

                if (empty($params['data'])) {
                    return [];
                }

                // Настройка параметров поиска
                $data = $params['data'];
                $lang = isset($params['lang']) ? sanitize_text_field($params['lang']) : '';
                $this->search_query['s'] = sanitize_text_field(urldecode($data['value']));
                $this->search_query['nopaging'] = false;
                $this->search_query['ignore_sticky_posts'] = false;
                $this->search_query['posts_per_page'] = (int)$data['limit_query_in_result_area'];
                $this->search_query['post_status'] = 'publish';

                $this->set_query_settings($data);

                // Многоязычность (Polylang/WPML)
                if (!empty($lang)) {
                    $this->search_query['lang'] = $lang;
                }

                add_filter('wp_query_search_exclusion_prefix', '__return_empty_string');

                // Выполняем поиск через WP_Query
                $search = new WP_Query(apply_filters('jet-search/ajax-search/query-args', $this->search_query, $this));

                // Если подключен Relevanssi, используем его
                if (function_exists('relevanssi_do_query')) {
                    relevanssi_do_query($search);
                }

                remove_filter('wp_query_search_exclusion_prefix', '__return_empty_string');

                // Формируем ответ
                $response = [
                    'error' => false,
                    'post_count' => $search->post_count,
                    'posts' => [],
                ];

                foreach ($search->posts as $key => $post) {
                    $response['posts'][] = [
                        'title' => $post->post_title,
                        'link' => esc_url(get_permalink($post->ID)),
                        'content' => Jet_Search_Template_Functions::get_post_content($data, $post),
                    ];
                }

                return $response;
            }
        }

        // Инициализация класса и вызов callback
        $jet_search_api = new b2b_mix_search();
        $response = $jet_search_api->callback($request);

        // Добавляем кастомные данные из внешнего API
        $params = $request->get_params();
        $search_items = b2b_mix_search_custom($params["data"]["value"]);

        if ($search_items) {
            foreach ($search_items as $item) {
                $product_link = b2b_mix_search_check_product($item);
                if ($product_link) {
                    $response['posts'][] = [
                        'title' => sanitize_text_field($item->shortDescription),
                        'link' => esc_url($product_link),
                        'content' => '',
                    ];
                }
            }
        }

        return new WP_REST_Response($response, 200);
    } else {
        return new WP_REST_Response([], 404);
    }
}

// === Отладочный маршрут для кастомного поиска ===
function b2b_mix_search_debug($request) {
    $params = $request->get_params();
    $endpoint = 'https://apt-api.awatera.com:3008/api/supplier/exact-search?keyword=' . urlencode($params['query']);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return new WP_REST_Response(curl_error($ch), 400);
    }

    curl_close($ch);
    return new WP_REST_Response(json_decode($response), 200);
}

// === Кастомный поиск через внешнее API ===
function b2b_mix_search_custom($search) {
    $endpoint = 'https://apt-api.awatera.com:3008/api/supplier/exact-search?keyword=' . urlencode($search);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return false;
    }

    curl_close($ch);
    return json_decode($response);
}

// === Проверка существующего продукта ===
function b2b_mix_search_check_product($data) {
    if (empty($data->mpn)) {
        return false;
    }

    $args = [
        'limit' => 1,
        'name' => sanitize_title($data->shortDescription),
    ];
    $query = new WC_Product_Query($args);

    $products = $query->get_products();
    if (!$products) {
        return b2b_mix_search_create_product($data);
    }

    return false;
}

// === Создание нового продукта ===
function b2b_mix_search_create_product($data) {
    global $wpdb;

    $current_time = current_time('mysql');
    $post_name = sanitize_title($data->shortDescription);

    $wpdb->insert($wpdb->posts, [
        'post_author' => 1,
        'post_title' => sanitize_text_field($data->shortDescription),
        'post_name' => $post_name,
        'post_status' => 'publish',
        'post_type' => 'product',
        'post_date' => $current_time,
        'post_modified' => $current_time,
    ]);

    $product_id = $wpdb->insert_id;
    update_post_meta($product_id, '_sku', $data->mpn);

    if (!empty($data->medianPrice1000->convertedPrice)) {
        $price = floatval($data->medianPrice1000->convertedPrice) * 2 * 100;
        update_post_meta($product_id, '_price', $price);
        update_post_meta($product_id, '_regular_price', $price);
    }

    if (!empty($data->manufacturer->name)) {
        $brand_name = sanitize_text_field($data->manufacturer->name);
        wp_set_object_terms($product_id, $brand_name, 'product_brands', true);
    }

    return get_permalink($product_id);
}

// === Обработка изображений для продуктов ===
function b2b_mix_search_handle_images($data, $product_id) {
    if (!empty($data->bestImage->base64)) {
        $image_base64 = base64_decode($data->bestImage->base64);
        $image_name = 'b2b_mix_product_' . time() . '.jpg';
        $upload_dir = wp_upload_dir();
        $image_path = $upload_dir['path'] . '/' . $image_name;

        file_put_contents($image_path, $image_base64);

        $attachment_id = wp_insert_attachment([
            'post_mime_type' => 'image/jpeg',
            'post_title' => sanitize_file_name($image_name),
            'post_content' => '',
            'post_status' => 'inherit',
        ], $image_path, $product_id);

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $image_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        set_post_thumbnail($product_id, $attachment_id);
    }
}
