<?php
/**
 * Plugin Name: AFAM Sync Source
 * Description: Синхронизация статей и мероприятий с сайта afam.md на dashboard.afam.md.
 * Version: 1.4
 * Author: @maincoder_ru
 */

// Хук для синхронизации при сохранении поста
add_action('save_post', 'afam_sync_to_dashboard', 10, 3);

function afam_sync_to_dashboard($post_ID, $post, $update) {
    // Проверяем тип записи (только 'post' или 'tribe_events')
    if (!in_array($post->post_type, ['post', 'tribe_events'])) {
        return;
    }

    // Подготовка данных для отправки
    $data = [
        'title'      => $post->post_title,
        'content'    => $post->post_content,
        'excerpt'    => $post->post_excerpt,
        'status'     => $post->post_status,
        'date'       => $post->post_date,
        'categories' => wp_get_post_categories($post_ID, ['fields' => 'names']), // Названия рубрик
        'tags'       => wp_get_post_tags($post_ID, ['fields' => 'names']),       // Названия меток
        'author'     => $post->post_author,
        'type'       => $post->post_type,
        'image'      => get_the_post_thumbnail_url($post_ID, 'full'),
    ];

    // Отправка данных на сайт-приёмник
    $response = wp_remote_post('https://dashboard.afam.md/wp-json/custom-sync/v1/import', [
        'body'    => json_encode($data),
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . constant('AFAM_API_KEY'),
        ],
        'timeout' => 20, // Увеличен таймаут для предотвращения ошибок cURL
    ]);

    // Логирование ошибок
    if (is_wp_error($response)) {
        error_log('AFAM Sync Error: ' . $response->get_error_message());
    } else {
        error_log('AFAM Sync Success: ' . print_r(wp_remote_retrieve_body($response), true));
    }
}

// Добавление ключа API в wp-config.php
register_activation_hook(__FILE__, 'afam_sync_add_api_key');
function afam_sync_add_api_key() {
    $key = bin2hex(random_bytes(32));
    $config_file = ABSPATH . 'wp-config.php';
    if (is_writable($config_file)) {
        $config_content = file_get_contents($config_file);
        if (strpos($config_content, 'AFAM_API_KEY') === false) {
            $key_code = "\n/** AFAM API Key */\ndefine('AFAM_API_KEY', '$key');\n";
            file_put_contents($config_file, $key_code, FILE_APPEND);
        }
    } else {
        error_log('AFAM Sync Source: wp-config.php недоступен для записи.');
    }
}
// Отключение Promoter, если он не используется
add_filter('tribe_events_promoter_should_load', '__return_false');