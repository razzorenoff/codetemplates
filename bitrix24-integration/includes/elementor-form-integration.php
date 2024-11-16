<?php
// Обработчик для отправки данных формы Elementor в Битрикс24
function handle_elementor_form_submission($record, $handler) {
    $raw_fields = $record->get('fields');
    $fields = [];
    
    foreach ($raw_fields as $id => $field) {
        $fields[$id] = $field['value'];
    }

    $name = !empty($fields['name']) ? $fields['name'] : '';
    $last_name = !empty($fields['last_name']) ? $fields['last_name'] : '';
    $phone = !empty($fields['phone']) ? $fields['phone'] : '';
    $email = !empty($fields['email']) ? $fields['email'] : '';
    $address = !empty($fields['address']) ? $fields['address'] : '';
    $message = !empty($fields['message']) ? $fields['message'] : '';

    // Извлечение названия формы
    $form_name = $record->get_form_settings('form_name');
    if (empty($form_name)) {
        $form_name = 'Неизвестная форма';
    }

    // Создание контакта в Битрикс24
    $contact_id = create_bitrix24_contact($name, $last_name, $phone, $email, $address);
    if (!$contact_id) {
        bitrix24_log('Не удалось создать контакт в Битрикс24 для формы: ' . json_encode($fields));
        return;
    }

    // Подготовка данных для передачи в Битрикс24
    $comments = "Сообщение: " . $message . "\n";
    foreach ($raw_fields as $id => $field) {
        if ($id !== 'message') {
            $label = !empty($field['label']) ? $field['label'] : translate_field_type($field['type']);
            if ($id === 'acceptance') {
                $field_value = 'Согласие получено';
            } else {
                $field_value = $field['value'];
            }
            $comments .= $label . ": " . $field_value . "\n";
        }
    }

    $deal_data = [
        'fields' => [
            'TITLE' => 'Новая заявка с сайта ohco.4-deluxe.ru форма ' . $form_name,
            'STAGE_ID' => 'NEW',
            'CONTACT_ID' => $contact_id,
            'OPPORTUNITY' => 0,
            'CURRENCY_ID' => 'RUB',
            'OPENED' => 'Y',
            'CATEGORY_ID' => 0,
            'BEGINDATE' => date('Y-m-d'),
            'CLOSEDATE' => date('Y-m-d', strtotime('+30 days')),
            'COMMENTS' => $comments
        ]
    ];

    bitrix24_log('Данные сделки из формы: ' . json_encode($deal_data));
    $response = send_to_bitrix24($deal_data, 'crm.deal.add');

    if (!is_wp_error($response) && isset($response['body'])) {
        $body = json_decode($response['body'], true);
        if (isset($body['result'])) {
            bitrix24_log('Сделка успешно создана: ' . $body['result']);
        } else {
            bitrix24_log('Ошибка при создании сделки: ' . json_encode($body));
        }
    }
}
add_action('elementor_pro/forms/new_record', 'handle_elementor_form_submission', 10, 2);

// Функция для перевода типов полей на русский язык
function translate_field_type($type) {
    $translations = [
        'text' => 'Имя',
        'email' => 'Электронная почта',
        'tel' => 'Телефон',
        'textarea' => 'Сообщение',
        'radio' => 'Радио кнопка',
        'checkbox' => 'Интересует товар',
        'select' => 'Выпадающий список',
        'date' => 'Дата',
        'time' => 'Время',
        'url' => 'URL',
        'file' => 'Файл',
        'acceptance' => 'Согласие на обработку данных'
    ];

    return isset($translations[$type]) ? $translations[$type] : $type;
}
