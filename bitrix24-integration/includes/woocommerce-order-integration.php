<?php
// Функция для отправки данных заказа в Битрикс с задержкой
function send_order_meta_to_bitrix($order_id) {
    if (empty($order_id) || !is_numeric($order_id)) {
        bitrix24_log('Некорректный order_id: ' . $order_id);
        return;
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        bitrix24_log('Не удалось получить заказ с ID: ' . $order_id);
        return;
    }

    // Вызов сохранения метаполей перед попыткой получить их значения
    $order_data = $order->get_data();  // Получаем данные заказа
    do_action('woocommerce_checkout_update_order_meta', $order_id, $order_data);

    // Задержка для гарантированного сохранения метаполей
    sleep(5);

    $lift_option = sanitize_text_field($order->get_meta('_cf_lift_option'));
    $floor_count = intval($order->get_meta('_cf_floor_count'));

    // Получение суммы комиссии и использование её как стоимости подъема на этаж
    $lift_cost = 0;
    foreach ($order->get_fees() as $fee) {
        if ($fee->get_name() === 'Стоимость подъема на этаж') {
            $lift_cost = floatval($fee->get_total());
            break;
        }
    }

    // Логирование, если значения метаполей не получены
    if (!$lift_option) {
        bitrix24_log("Не удалось получить значение метаполя '_cf_lift_option' для заказа ID: $order_id.");
    }
    if (!$floor_count) {
        bitrix24_log("Не удалось получить значение метаполя '_cf_floor_count' для заказа ID: $order_id.");
    }
    if (!$lift_cost) {
        bitrix24_log("Не удалось получить значение комиссии для заказа ID: $order_id.");
    }

    $contact_id = create_bitrix24_contact(
        $order->get_billing_first_name(),
        $order->get_billing_last_name(),
        $order->get_billing_phone(),
        $order->get_billing_email(),
        trim($order->get_billing_address_1() . 
             ($order->get_billing_address_2() ? ', ' . $order->get_billing_address_2() : '') . 
             ', ' . $order->get_billing_city() . 
             ', ' . $order->get_billing_state() . 
             ', ' . $order->get_billing_postcode() . 
             ', ' . $order->get_billing_country(), ', ')
    );

    if (!$contact_id) {
        bitrix24_log('Не удалось создать контакт в Битрикс24 для заказа ID: ' . $order_id);
        return;
    }

    bitrix24_log('Контакт успешно создан с ID: ' . $contact_id);

    $total_shipping_cost = (float) $order->get_shipping_total();

    // Общая стоимость заказа включает все компоненты (товары, доставка, подъем на этаж)
    $opportunity = $order->get_total();


    $order_notes = $order->get_customer_note();
    if (empty($order_notes)) {
        $order_notes = 'Нет';
    }

    $comments = get_customer_info($order) . "\n" .
                get_payment_info($order) . "\n" .
                "Информация о заказе:\n" .
                "Номер заказа на сайте: " . $order->get_order_number() . "\n" .
                get_order_items_info($order);

    if ($total_shipping_cost) {
        $comments .= "Стоимость доставки по адресу заказчика: " . (float)$total_shipping_cost . "\n";
    }

    if ($lift_option) {
        $comments .= "Подъем на этаж: " . get_readable_lift_option($lift_option) . "\n";
    }

    if ($floor_count) {
        $comments .= "Количество этажей: " . $floor_count . "\n";
    }

    if ($lift_cost) {
        $comments .= "Стоимость подъема на этаж: " . $lift_cost . "\n";
    }

    $comments .= "Общие итоги заказа: " . $opportunity . "\n" .
                 "Примечания к заказу: " . $order_notes;

    $deal_data = [
        'fields' => [
            'TITLE' => 'Новый заказ из корзины с сайта ohco.4-deluxe.ru ' . $order->get_order_number(),
            'STAGE_ID' => 'NEW',
            'CONTACT_ID' => $contact_id,
            'OPPORTUNITY' => $opportunity,
            'CURRENCY_ID' => $order->get_currency(),
            'OPENED' => 'Y',
            'CATEGORY_ID' => 0,
            'BEGINDATE' => date('Y-m-d'),
            'CLOSEDATE' => date('Y-m-d', strtotime('+30 days')),
            'COMMENTS' => $comments
        ]
    ];

    bitrix24_log('Данные сделки для отправки: ' . json_encode($deal_data));

    $response = send_to_bitrix24($deal_data, 'crm.deal.add');

    bitrix24_log('Ответ от Битрикс24: ' . wp_remote_retrieve_body($response));

    if (!is_wp_error($response) && isset($response['body'])) {
        $body = json_decode($response['body'], true);
        if (isset($body['result'])) {
            $order->update_meta_data('_bitrix24_deal_created', true);
            $order->save();
            bitrix24_log('Сделка успешно создана для заказа ID: ' . $order_id);
        } else {
            bitrix24_log('Ошибка при создании сделки для заказа ID: ' . $order_id . '. Ответ: ' . json_encode($body));
        }
    } else {
        bitrix24_log('Ошибка при попытке создать сделку для заказа ID: ' . $order_id);
    }
}
add_action('woocommerce_checkout_order_processed', 'send_order_meta_to_bitrix');

// Функция для получения информации о товарах в заказе
function get_order_items_info($order) {
    $items = $order->get_items();
    $items_info = "";
    
    foreach ($items as $item_id => $item) {
        $product_name = $item->get_name();
        $quantity = $item->get_quantity();
        $total = $item->get_total();
        $items_info .= "Товар: $product_name, \nКоличество: $quantity, \nСтоимость товара: $total\n";
    }
    
    return $items_info;
}

// Функция для получения информации о покупателе
function get_customer_info($order) {
    $customer_info = "Информация о покупателе:\n";
    $customer_info .= "Имя: " . $order->get_billing_first_name() . " " . $order->get_billing_last_name() . "\n";
    $customer_info .= "Телефон: " . $order->get_billing_phone() . "\n";
    $customer_info .= "Email: " . $order->get_billing_email() . "\n";
    $customer_info .= "Адрес: " . $order->get_billing_address_1() . 
                      ($order->get_billing_address_2() ? ', ' . $order->get_billing_address_2() : '') . 
                      ', ' . $order->get_billing_city() . 
                      ', ' . $order->get_billing_state() . 
                      ', ' . $order->get_billing_postcode() . 
                      ', ' . $order->get_billing_country() . "\n";
    return $customer_info;
}

// Функция для получения информации о платеже
function get_payment_info($order) {
    $payment_info = "Платежная информация:\n";
    $payment_info .= "Способ оплаты: " . $order->get_payment_method_title() . "\n";
    $payment_info .= "Статус оплаты: Не оплачено\n";  // Всегда писать "Не оплачено"
    return $payment_info;
}

// Функция для получения читаемых значений радиокнопок
function get_readable_lift_option($value) {
    $options = [
        'lift' => 'Требуется, грузового лифта нет',
        'no_lift' => 'Не требуется, или есть грузовой лифт'
    ];
    return isset($options[$value]) ? $options[$value] : $value;
}
