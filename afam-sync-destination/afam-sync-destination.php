<?php
/**
 * Plugin Name: AFAM Sync Destination
 * Description: Прием и обработка данных для синхронизации с afam.md.
 * Version: 1.6
 * Author: @maincoder_ru
 */

add_action('rest_api_init', function () {
    register_rest_route('custom-sync/v1', '/import', [
        'methods'  => 'POST',
        'callback' => 'afam_handle_imported_post',
        'permission_callback' => function () {
            $headers = getallheaders();

            // Проверяем наличие заголовка Authorization
            if (!isset($headers['Authorization']) || strpos($headers['Authorization'], 'Bearer') !== 0) {
                return false;
            }

            // Проверяем ключ авторизации
            return $headers['Authorization'] === 'Bearer ' . constant('AFAM_API_KEY');
        },
    ]);
});

function afam_handle_imported_post($request) {
    $headers = getallheaders();
    error_log('Headers: ' . print_r($headers, true));
    error_log('Request Route: ' . $request->get_route());

    $data = $request->get_json_params();

    // Проверка обязательных полей
    if (empty($data['title']) || empty($data['content']) || empty($data['type'])) {
        return new WP_Error('missing_data', 'Обязательные поля отсутствуют', ['status' => 400]);
    }

    // Проверка существования записи
    $existing_post = get_page_by_title(sanitize_text_field($data['title']), OBJECT, sanitize_text_field($data['type']));
    if ($existing_post) {
        $post_id = wp_update_post([
            'ID'           => $existing_post->ID,
            'post_title'   => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['content']),
            'post_excerpt' => sanitize_text_field($data['excerpt']),
            'post_status'  => sanitize_text_field($data['status']),
            'post_date'    => sanitize_text_field($data['date']),
        ]);
    } else {
        $post_id = wp_insert_post([
            'post_title'   => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['content']),
            'post_excerpt' => sanitize_text_field($data['excerpt']),
            'post_status'  => sanitize_text_field($data['status']),
            'post_date'    => sanitize_text_field($data['date']),
            'post_type'    => sanitize_text_field($data['type']),
            'post_author'  => intval($data['author']),
        ]);
    }

    if (is_wp_error($post_id)) {
        return new WP_Error('insert_failed', 'Ошибка создания или обновления записи', ['status' => 500]);
    }

    // Обработка рубрик
    if (!empty($data['categories'])) {
        $category_ids = [];
        foreach ($data['categories'] as $category_name) {
            $category = get_term_by('name', $category_name, 'category');
            if (!$category) {
                $category_data = wp_insert_term($category_name, 'category');
                if (!is_wp_error($category_data)) {
                    $category_ids[] = $category_data['term_id'];
                }
            } else {
                $category_ids[] = $category->term_id;
            }
        }
        wp_set_post_categories($post_id, $category_ids);
    }

    // Обработка меток
    if (!empty($data['tags'])) {
        $tag_ids = [];
        foreach ($data['tags'] as $tag_name) {
            $tag = get_term_by('name', $tag_name, 'post_tag');
            if (!$tag) {
                $tag_data = wp_insert_term($tag_name, 'post_tag');
                if (!is_wp_error($tag_data)) {
                    $tag_ids[] = $tag_data['term_id'];
                }
            } else {
                $tag_ids[] = $tag->term_id;
            }
        }
        wp_set_post_tags($post_id, $tag_ids);
    }

    // Проверка и загрузка изображения
    if (!empty($data['image'])) {
        // Проверяем, если изображение уже привязано
        if (!has_post_thumbnail($post_id)) {
            $image_id = afam_sideload_image($data['image'], $post_id);
            if (!is_wp_error($image_id)) {
                set_post_thumbnail($post_id, $image_id);
            }
        }
    }

    return new WP_REST_Response(['success' => true, 'post_id' => $post_id], 200);
}

// Функция загрузки изображений
function afam_sideload_image($url, $post_id) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Проверяем, если изображение уже существует в библиотеке
    $existing_image = afam_find_existing_image($url);
    if ($existing_image) {
        return $existing_image; // Возвращаем ID существующего изображения
    }

    $tmp = download_url($url);
    $file_array = [
        'name'     => basename($url),
        'tmp_name' => $tmp,
    ];

    if (is_wp_error($tmp)) {
        return $tmp;
    }

    $id = media_handle_sideload($file_array, $post_id);

    if (is_wp_error($id)) {
        @unlink($file_array['tmp_name']);
        return $id;
    }

    return $id;
}

// Функция для поиска существующего изображения
function afam_find_existing_image($url) {
    $image_name = basename($url);

    // Поиск изображения в библиотеке медиафайлов
    $args = [
        'post_type'  => 'attachment',
        'post_status' => 'inherit',
        'meta_query' => [
            [
                'key'     => '_wp_attached_file',
                'value'   => $image_name,
                'compare' => 'LIKE',
            ],
        ],
    ];

    $query = new WP_Query($args);
    if ($query->have_posts()) {
        return $query->posts[0]->ID; // Возвращаем ID найденного изображения
    }

    return false;
}

// Отключение Promoter, если он не используется
add_filter('tribe_events_promoter_should_load', '__return_false');
