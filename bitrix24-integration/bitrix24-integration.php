<?php
/*
Plugin Name: Bitrix24 Integration
Description: Плагин для отправки данных форм и заказов в Битрикс24.
Version: 3.8
Author: @maincoder_ru
*/

// Подключение файлов с логикой для Elementor и WooCommerce
require_once plugin_dir_path(__FILE__) . 'includes/elementor-form-integration.php';
require_once plugin_dir_path(__FILE__) . 'includes/woocommerce-order-integration.php';

// Функция для логирования ошибок и данных
function bitrix24_log($message) {
    if (WP_DEBUG === true) {
        error_log($message);
    }
}

// Функция для отправки данных в Битрикс24
function send_to_bitrix24($data, $endpoint) {
    $webhook_url = 'https://nordbass.bitrix24.ru/rest/919/mdib6y77izn3u91d/' . $endpoint . '.json';  // URL Битрикс-вебхука
    $response = wp_remote_post($webhook_url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) {
        bitrix24_log('Ошибка при отправке данных в Битрикс24: ' . $response->get_error_message());
    } else {
        bitrix24_log('Ответ от Битрикс24: ' . wp_remote_retrieve_body($response));
    }

    return $response;
}

// Общая функция для создания контакта в Битрикс24
function create_bitrix24_contact($name, $last_name, $phone, $email, $address = '') {
    // Поиск существующего контакта
    $contact_id = find_bitrix24_contact($phone, $name);
    if ($contact_id) {
        return $contact_id;
    }

    $contact_data = [
        'fields' => [
            'NAME' => $name,
            'LAST_NAME' => $last_name,
            'OPENED' => 'Y',
            'TYPE_ID' => 'CLIENT',
            'PHONE' => [['VALUE' => $phone, 'VALUE_TYPE' => 'WORK']],
            'EMAIL' => [['VALUE' => $email, 'VALUE_TYPE' => 'WORK']],
            'ADDRESS' => $address
        ]
    ];

    bitrix24_log('Данные контакта из формы: ' . json_encode($contact_data));
    $response = send_to_bitrix24($contact_data, 'crm.contact.add');

    if (!is_wp_error($response) && isset($response['body'])) {
        $body = json_decode($response['body'], true);
        if (isset($body['result'])) {
            return $body['result'];
        }
    }

    return null;
}

// Функция для поиска контакта в Битрикс24
function find_bitrix24_contact($phone, $name) {
    $filter = [
        'PHONE' => $phone,
        'NAME' => $name
    ];

    $response = send_to_bitrix24(['filter' => $filter], 'crm.contact.list');
    if (!is_wp_error($response) && isset($response['body'])) {
        $body = json_decode($response['body'], true);
        if (isset($body['result']) && count($body['result']) > 0) {
            return $body['result'][0]['ID'];
        }
    }

    return null;
}
