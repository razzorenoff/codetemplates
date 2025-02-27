<?php
/**
 * Plugin Name: Custom Functions
 * Plugin URI: https://maincoder.ru/
 * Description: Custom Functions — универсальный плагин для WordPress и WooCommerce, который: отключает проверку сложности пароля, преобразует суммы в текст, динамически обновляет корзину и подсчитывает заказы, интегрируется с PDF Invoices & Packing Slips, автоматически заменяя шаблон счета (с резервным копированием оригинала).
 * Author: @maincoder_ru
 * Version: 1.0
 * Author URI: https://maincoder.ru/
 * Text Domain: maincoder-custom-functions
 * License: GPL
 */

/* ========================== */
/* ДОБАВЛЕНИЕ ДОПОЛНИТЕЛЬНЫХ НАСТРОЕК МАГАЗИНА В WOO */
/* ========================== */

function mcf_add_custom_fields_woocommerce_general( $settings ) {
    $custom_fields = array(
        // Дополнительные настройки магазина
        array(
            'title' => __('Дополнительные настройки магазина', 'maincoder-custom-functions'),
            'type'  => 'title',
            'id'    => 'mcf_store_additional_options',
        ),
        array(
            'title' => __('Юридическое наименование', 'maincoder-custom-functions'),
            'id'    => 'mcf_store_legal_name',
            'type'  => 'text',
        ),
        array(
            'title' => __('Телефон магазина', 'maincoder-custom-functions'),
            'id'    => 'mcf_store_phone',
            'type'  => 'text',
        ),
        array(
            'type' => 'sectionend',
            'id'   => 'mcf_store_additional_options',
        ),
        // Данные руководства и печати
        array(
            'title' => __('Данные руководства и печати', 'maincoder-custom-functions'),
            'type'  => 'title',
            'id'    => 'mcf_signatures_options',
        ),
        array(
            'title' => __('Фамилия И.О. Руководителя', 'maincoder-custom-functions'),
            'id'    => 'mcf_director_name',
            'type'  => 'text',
        ),
        array(
            'title' => __('Ссылка на факсимиле росписи Руководителя', 'maincoder-custom-functions'),
            'id'    => 'mcf_director_signature',
            'type'  => 'text',
            'css'   => 'min-width:300px;',
            'desc'  => __('Вставьте ссылку на изображение факсимиле', 'maincoder-custom-functions'),
        ),
        array(
            'title' => __('Фамилия И.О. Главного бухгалтера', 'maincoder-custom-functions'),
            'id'    => 'mcf_accountant_name',
            'type'  => 'text',
        ),
        array(
            'title' => __('Ссылка на факсимиле росписи Главного бухгалтера', 'maincoder-custom-functions'),
            'id'    => 'mcf_accountant_signature',
            'type'  => 'text',
            'css'   => 'min-width:300px;',
            'desc'  => __('Вставьте ссылку на изображение факсимиле', 'maincoder-custom-functions'),
        ),
        array(
            'title' => __('Ссылка на фото печати организации', 'maincoder-custom-functions'),
            'id'    => 'mcf_company_seal',
            'type'  => 'text',
            'css'   => 'min-width:300px;',
            'desc'  => __('Вставьте ссылку на изображение печати', 'maincoder-custom-functions'),
        ),
        array(
            'type' => 'sectionend',
            'id'   => 'mcf_signatures_options',
        ),
    );

    $new_settings = array();
    foreach ( $settings as $key => $value ) {
        $new_settings[] = $value;
        if ( isset( $value['id'] ) && 'woocommerce_store_address_2' === $value['id'] ) {
            foreach ( $custom_fields as $custom_field ) {
                $new_settings[] = $custom_field;
            }
        }
    }
    return $new_settings;
}
add_filter( 'woocommerce_general_settings', 'mcf_add_custom_fields_woocommerce_general' );

/* ========================== */
/* СОХРАНЕНИЕ ДОПОЛНИТЕЛЬНЫХ ПОЛЕЙ */
/* ========================== */

function mcf_save_custom_woocommerce_settings() {
    $fields = array(
        'mcf_director_signature',
        'mcf_accountant_signature',
        'mcf_company_seal',
    );

    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_option( $field, esc_url_raw( $_POST[ $field ] ) );
        }
    }
}
add_action( 'woocommerce_update_options_general', 'mcf_save_custom_woocommerce_settings' );


/* ========================== */
/* ОТКЛЮЧЕНИЕ ТРЕБОВАНИЙ К СЛОЖНОСТИ ПАРОЛЯ */
/* ========================== */

/**
 * Отключает встроенный скрипт `zxcvbn.min.js`, отвечающий за проверку сложности пароля при регистрации и входе в WordPress.
 * 
 * Использование:
 * Достаточно активировать этот плагин, чтобы WordPress перестал требовать сложные пароли.
 */
add_action( 'wp_print_scripts', function () {
    wp_dequeue_script('zxcvbn-async');
    wp_deregister_script('zxcvbn-async');
});

/* ========================== */
/* ФУНКЦИЯ ПЕРЕВОДА ЧИСЛА В ТЕКСТ (РУБЛИ И КОПЕЙКИ) */
/* ========================== */

/**
 * Функция преобразует числовую сумму в текстовое представление (например, 1234.56 → "одна тысяча двести тридцать четыре рубля 56 копеек").
 * 
 * Использование:
 * `echo num2str(1234.56);`
 * 
 * Применение в WooCommerce:
 * Используется в файле `/wp-content/themes/..каталог дочерней темы../woocommerce/pdf/invoices/invoice.php`
 */
if (!function_exists('num2str')) {
    function num2str($num) {
        $nul = 'ноль';
        $ten = [
            ['','один','два','три','четыре','пять','шесть','семь','восемь','девять'],
            ['','одна','две','три','четыре','пять','шесть','семь','восемь','девять']
        ];
        $a20 = ['десять','одиннадцать','двенадцать','тринадцать','четырнадцать','пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать'];
        $tens = [2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят','восемьдесят','девяносто'];
        $hundred = ['','сто','двести','триста','четыреста','пятьсот','шестьсот','семьсот','восемьсот','девятьсот'];
        $unit = [
            ['копейка','копейки','копеек',1],
            ['рубль','рубля','рублей',0],
            ['тысяча','тысячи','тысяч',1],
            ['миллион','миллиона','миллионов',0],
            ['миллиард','миллиарда','миллиардов',0]
        ];
        list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
        $out = [];
        if (intval($rub) > 0) {
            foreach (str_split($rub, 3) as $uk => $v) {
                if (!intval($v)) continue;
                $uk = sizeof($unit) - $uk - 1;
                $gender = $unit[$uk][3];
                list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
                $out[] = $hundred[$i1];
                if ($i2 > 1) {
                    $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3];
                } else {
                    $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];
                }
                if ($uk > 1) $out[] = morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
            }
        } else {
            $out[] = $nul;
        }
        $out[] = morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]);
        $out[] = $kop . ' ' . morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]);
        return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
    }
}

/**
 * Функция склонения слов в зависимости от числа.
 * 
 * Использование:
 * `echo morph(5, 'рубль', 'рубля', 'рублей'); // "рублей"`
 */
if (!function_exists('morph')) {
    function morph($n, $f1, $f2, $f5) {
        $n = abs(intval($n)) % 100;
        if ($n > 10 && $n < 20) return $f5;
        $n = $n % 10;
        if ($n > 1 && $n < 5) return $f2;
        if ($n == 1) return $f1;
        return $f5;
    }
}

/* ========================== */
/* УПРАВЛЕНИЕ КОРЗИНОЙ WOOCOMMERCE */
/* ========================== */

/**
 * Убирает изображение товара в мини-корзине WooCommerce.
 * 
 * Использование:
 * Автоматически применяется после активации плагина.
 */
add_filter('woocommerce_cart_item_thumbnail', 'remove_image_from_mini_cart');
function remove_image_from_mini_cart($product_image) {
    if (!is_page(wc_get_page_id('cart'))) {
        return '';
    }
    return $product_image;
}

/**
 * Обновляет количество товаров в корзине без перезагрузки страницы.
 */
add_filter('woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment_amount');
function woocommerce_header_add_to_cart_fragment_amount($fragments_amount) {
    ob_start(); ?>
    <div class="cart_total_amount"><?php echo WC()->cart->cart_contents_count; ?></div>
    <?php
    $fragments_amount['div.cart_total_amount'] = ob_get_clean();
    return $fragments_amount;
}

/**
 * Обновляет сумму корзины без перезагрузки страницы.
 */
add_filter('woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment');
function woocommerce_header_add_to_cart_fragment($fragments) {
    ob_start(); ?>
    <div class="cart_total"><?php echo WC()->cart->get_cart_total(); ?></div>
    <?php
    $fragments['div.cart_total'] = ob_get_clean();
    return $fragments;
}

/* ========================== */
/* ПОДСЧЁТ ОБЩЕЙ СУММЫ ОПЛАЧЕННЫХ ЗАКАЗОВ КЛИЕНТА */
/* ========================== */

/**
 * Функция получает общую сумму всех оплаченных и завершённых заказов текущего пользователя.
 *
 * Использование:
 * - Вставить в нужное место шаблона: `echo get_customer_total_order();`
 * - Использовать в контенте через шорткод: `[customer_total_order]`
 *
 * Пример:
 * `[customer_total_order]` выведет сумму всех завершённых заказов текущего пользователя.
 */
function get_customer_total_order() {
    $customer_orders = get_posts([
        'numberposts' => -1,
        'meta_key'    => '_customer_user',
        'meta_value'  => get_current_user_id(),
        'post_type'   => 'shop_order',
        'post_status' => 'completed'
    ]);

    $total = 0;
    foreach ($customer_orders as $order) {
        $total += wc_get_order($order)->get_total();
    }

    return number_format($total, 2, ',', ' ') . ' ' . get_woocommerce_currency_symbol();
}

/**
 * Создаёт шорткод для вывода общей суммы оплаченных заказов.
 * 
 * Шорткод:
 * `[customer_total_order]`
 */
function customer_total_order_shortcode() {
    return get_customer_total_order();
}
add_shortcode('customer_total_order', 'customer_total_order_shortcode');


/**
 * Выводит количество заказов по статусам.
 * 
 * Шорткод:
 * `[wc_order_count status="completed,pending"]`
 */
function display_woocommerce_order_count($atts) {
    $args = shortcode_atts(['status' => 'completed'], $atts);
    $statuses = array_map('trim', explode(',', $args['status']));
    $order_count = 0;
    foreach ($statuses as $status) {
        $status = 'wc-' . ltrim($status, 'wc-');
        $order_count += wp_count_posts('shop_order')->$status ?? 0;
    }
    return number_format($order_count);
}
add_shortcode('wc_order_count', 'display_woocommerce_order_count');

/* ========================== */
/* МОДИФИКАЦИЯ ШАБЛОНА СЧЁТА PDF */
/* ========================== */

/**
 * Функция замены шаблона invoice.php плагина PDF Invoices & Packing Slips for WooCommerce.
 *
 * При активации нашего плагина:
 * 1. Проверяется наличие файла шаблона.
 * 2. Если резервная копия отсутствует, создаётся её копия (invoice.php.bak).
 * 3. Исходный файл invoice.php перезаписывается новым кодом (старый код удаляется).
 * В новом коде в начале содержится предупреждение о том, что редактировать данный файл нельзя.
 */
function mcf_modify_invoice_template() {
    // Формируем путь к файлу шаблона счета
    $invoice_file = WP_PLUGIN_DIR . '/woocommerce-pdf-invoices-packing-slips/templates/Simple/invoice.php';
    // Формируем путь к резервной копии файла
    $backup_file  = $invoice_file . '.bak';

    // Проверяем, существует ли файл шаблона
    if ( file_exists( $invoice_file ) ) {
        // Если резервная копия ещё не создана, создаём её
        if ( ! file_exists( $backup_file ) ) {
            copy( $invoice_file, $backup_file );
        }

        // Новый код, которым будет полностью заменён файл invoice.php
        $new_code = <<<'NEWCODE'
<?php
/**
 * Этот файл генерируется автоматически плагином "Custom Functions".
 * Редактировать данный файл нельзя!
 * Для редактирования шаблона изменяйте файл плагина по адресу: wp-content/plugins/maincoder-custom-functions/
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly ?>
<?php do_action('wpo_wcpdf_before_document', $this->type, $this->order); ?>
<style>
    body {
        margin-left: auto;
        margin-right: auto;
        border: 1px #efefef solid;
        font-size: 9pt;
    }

    table.invoice_bank_rekv {
        border-collapse: collapse;
        border: 1px solid;
    }

    table.invoice_bank_rekv>tbody>tr>td,
    table.invoice_bank_rekv>tr>td {
        border: 1px solid;
    }

    table.invoice_items {
        border: 2px solid;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    table.invoice_items td,
    table.invoice_items th {
        border: 1px solid;
        text-align: center;
    }

    .woocommerce-Price-currencySymbol {
        display: none;
    }

    .price {
        width: 10%;
    }

    .sum {
        width: 12%;
    }

    .number {
        width: 5%;
    }

    .quantity,
    .value {
        width: 8%;
    }
</style>
<table width="100%" style="font-family: Arial;">
    <tr>
        <td style="width: 68%; padding: 20px 0;">
            <div style="text-align: justify; font-size: 10px;">Внимание! Оплата в рублях по курсу ЦБ РФ на день
                оплаты. Оплата данного счета означает согласие с условиями поставки товара. Счет действителен в течение
                5 (пяти) банковских дней, не считая дня выписки счета. Уведомление об оплате обязательно, в противном
                случае НЕ ГАРАНТИРУЕТСЯ наличие товара на складе. Товар отпускается по факту прихода денег на р/с
                Поставщика. Доставка осуществляется силами Продавца, транспортными компаниями или самовывозом при наличии доверенности и паспорта.</div>
        </td>
        <td style="width: 32%; text-align: right; padding: 30px 0;"><img
                src="../wp-content/uploads/2025/02/r.png" style="width: 40%;"></td>
    </tr>
</table>
<!--<h1 class="document-type-label">-->
<!--<?php if ($this->has_header_logo())
    echo $this->get_title(); ?>-->
<!--</h1>-->
<?php do_action('wpo_wcpdf_after_document_label', $this->type, $this->order); ?>
<table width="100%" cellpadding="2" cellspacing="2" class="invoice_bank_rekv">
    <tr>
        <td colspan="2" rowspan="2" style="min-height:13mm;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="height: 13mm;">
                <tr>
                    <td valign="top">
                        <div>АО "АЛЬФА-БАНК" г. Москва</div>
                    </td>
                </tr>
                <tr>
                    <td valign="bottom" style="height: 3mm;">
                        <div>Банк получателя </div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="min-height:7mm;height:auto; ">
            <div>БИK</div>
        </td>
        <td rowspan="2" style="vertical-align: top; ">
            <div style=" height: 7mm; line-height: 7mm; vertical-align: top;">044525593</div>
            <div style="width:auto; ">30101810200000000593</div>
        </td>
    </tr>
    <tr>
        <td style="width: 25mm;">
            <div>Сч. №</div>
        </td>
    </tr>
    <tr>
        <td style="min-height:6mm; height:auto; ">
            <div>ИНН 9715364958</div>
        </td>
        <td style="min-height:6mm; height:auto;">
            <div>КПП 771501001</div>
        </td>
        <td rowspan="2" style="min-height:19mm; height:auto; vertical-align: top;">
            <div>Сч. №</div>
        </td>
        <td rowspan="2" style="min-height:19mm; height:auto; vertical-align: top;">
            <div>40702810502310003384</div>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="min-height:13mm; height:auto;">
            <table border="0" cellpadding="0" cellspacing="0" style="height: 13mm;">
                <tr>
                    <td valign="top">
                        <div>ООО "МОЯ ФИРМА"</div>
                    </td>
                </tr>
                <tr>
                    <td valign="bottom" style="height: 3mm;">
                        <div style="font-size: 10pt;">Получатель</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br />
<div style="font-weight: bold; font-size: 16pt; padding: 5px 0 0 5px;">
    Счет №
    <?php $this->invoice_number(); ?> от
    <?php $this->invoice_date(); ?>
</div>
<br />
<div style="background-color:#000000; width:100%; font-size:1px; height:2px;">&nbsp;</div>
<table width="100%">
    <tr>
        <td style="width: 30mm; padding-top: 5px;">
            <div style=" padding-left:2px; padding-bottom: 5px; padding-top: 5px;">Поставщик (Исполнитель): </div>
        </td>
        <td>
            <div style="font-weight:bold;  padding-left:2px; padding-top: 5px;">
                ООО "МОЯ ФИРМА", ИНН 9715364958, КПП 771501001, 127273, Москва г, Отрадная ул, дом 2Б, строение 9,<br>
                тел.: +7(495)123-45-67</div>
        </td>
    </tr>
    <tr>
        <td style="width: 30mm;">
            <div style=" padding-left:2px; padding-top: 5px;">Покупатель: </div>
        </td>
        <td>
            <div style="font-weight:bold;  padding-left:2px; padding-bottom: 5px; padding-top: 5px;">
                <span>
                    <?php $this->billing_address(); ?>,
                    <?php $this->billing_email(); ?>,
                    <?php $this->billing_phone(); ?>
                </span>
                <?php $this->custom_field('last_name');
                echo " ";
                $this->custom_field('first_name');
                echo " ";
                $this->custom_field('billing_patronymic_name'); ?>
            </div>
        </td>
    </tr>
    <tr>
        <td style="width: 30mm;">
            <div style=" padding-left:2px; padding-top: 5px;">Основание: </div>
        </td>
        <td>
            <div style="font-weight:bold;  padding-left:2px; padding-top: 5px; padding-bottom: 10px; ">
                Заказ на сайте №
                <?php $this->order_number(); ?> от
                <?php $this->order_date(); ?>
            </div>
        </td>
    </tr>
</table>
<?php do_action('wpo_wcpdf_before_order_details', $this->type, $this->order); ?>
<table width="100%" class="invoice_items" cellpadding="2" cellspacing="2">
    <thead>
        <tr>
            <th class="number">№</th>
            <th class="product">Товары (работы, услуги)</th>
            <th class="quantity">Кол-во</th>
            <th class="value">Ед.</th>
            <th class="price">Цена</th>
            <th class="sum">Сумма</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 0 /* для нумерации строк и подсчёта количества позиций */?>
        <!-- В цикле выводим список позиций -->
        <?php $items = $this->get_order_items();
        if (sizeof($items) > 0):
            foreach ($items as $item_id => $item): ?>
                <tr
                    class="<?php echo apply_filters('wpo_wcpdf_item_row_class', $item_id, $this->type, $this->order, $item_id); ?>">
                    <td>
                        <?php $i++;
                        echo $i; ?>
                    </td><!-- номер строки -->
                    <td style="text-align: left;" class="product">
                        <?php $description_label = __('Description', 'woocommerce-pdf-invoices-packing-slips'); // регистрация альтернативного перевода метки ?>
                        <span class="item-name">
                            <?php echo $item['name']; ?>
                        </span>
                        <?php do_action('wpo_wcpdf_before_item_meta', $this->type, $item, $this->order); ?>
                        <span class="item-meta">
                            <?php echo $item['meta']; ?>
                        </span>
                        <?php do_action('wpo_wcpdf_after_item_meta', $this->type, $item, $this->order); ?>
                    </td><!-- наименование продукта -->
                    <td style="text-align:  right;" class="quantity">
                        <?php echo $item['quantity']; ?>
                    </td><!-- количество -->
                    <td style="text-align:  right;" class="value">шт.
                    </td><!-- единица измерения -->
                    <td style="text-align:  right;" class="price">
                        <?php echo $item['single_line_total']; ?>
                    </td><!-- цена за штуку -->
                    <td style="text-align:  right;" class="sum">
                        <?php echo $item['order_price']; ?>
                    </td><!-- сумма -->
                </tr>
            <?php endforeach; endif; ?>
    </tbody>
</table>
<!-- Выводим таблицу Итого -->
<table border="0" width="100%" cellpadding="1" cellspacing="1">
    <tr>
        <td></td>
        <td style="width:27mm; font-weight:bold;  text-align:right;">Итого:</td>
        <td style="width:27mm; font-weight:bold;  text-align:right;">
            <?php echo $order->get_total(); ?> руб.
        </td>
    </tr>
    <tr>
        <td></td>
        <td style="width:40mm; font-weight:bold;  text-align:right;">В том числе НДС:</td>
        <td style="width:27mm; font-weight:bold;  text-align:right;">
            <?php echo $order->get_total_tax(); ?> руб.
        </td>
    </tr>
    <tr>
        <td></td>
        <td style="width:27mm; font-weight:bold;  text-align:right;">Всего к оплате:</td>
        <td style="width:27mm; font-weight:bold;  text-align:right;">
            <?php echo $order->get_total(); ?> руб.
        </td>
    </tr>
</table>
Всего наименований:
<?php echo $i; ?> на сумму
<?php echo $order->get_total(); ?> руб.<br>
<?php
/* Указываем переменной orderp чистое значение суммы заказа */
$orderp = $order->get_total();
/* Конвертируем его из строки в тип double */
$orderp = (double) $orderp;
/* Оборачиваем в функцию для возвращения суммы прописью (функция описана в плагине mcf.php) */ 
$slova = num2str($orderp);
/* Выводим сумму словами, первую букву делаем заглавной и добавляем точку в конце*/
echo "<b>" . mb_strtoupper(mb_substr($slova, 0, 1)) . mb_substr($slova, 1, mb_strlen($slova)) . ".</b>"; ?>
<div>Стоимость доставки не включена в итоговую сумму оплаты по этому счету. В ряде случаев доставка может стать для Вас совершенно бесплатной. Доставка приобретенного товара осуществляется силами Продавца и транспортными компаниями. Сроки и стоимость рассчитываются индивидуально.</div>
<div style="margin-top: 20px;">
    <div style="background-color:#000000; width:100%; font-size:1px; height:2px;">&nbsp;</div>
    <br />
    <div style="position: relative;">
    <div style="display: inline-flex; flex-wrap:nowrap;">
        <img style="height:30px; margin-left: 130px; margin-top: -10px; margin-bottom: -20px;" src="../wp-content/uploads/2025/02/podpis.png" alt="">
        <img style="height:30px; margin-left: 230px; margin-top: -10px; margin-bottom: -20px;" src="../wp-content/uploads/2025/02/podpis.png" alt="">
    </div>
        <div>Руководитель ______________________/Иванов В. Н./   Главный бухгалтер ______________________/Иванов В. Н./
        </div>
    </div>
    <div style="width:100%; position: absolute; left: 30px;  height: 100%; background: url('../wp-content/uploads/2025/02/msr-preview.png'); background-size: 150px 150px; background-repeat: no-repeat; padding: 30px 10px;">
        М.П.</div>
</div>
NEWCODE;

        // Перезаписываем файл новым содержимым (старый код удаляется)
        file_put_contents( $invoice_file, $new_code );
    }
}

/**
 * Функция восстановления исходного шаблона invoice.php при деактивации нашего плагина.
 *
 * Если существует резервная копия (invoice.php.bak), то она копируется обратно в invoice.php,
 * а затем резервная копия удаляется.
 */
function mcf_restore_invoice_template() {
    // Формируем путь к файлу шаблона счета
    $invoice_file = WP_PLUGIN_DIR . '/woocommerce-pdf-invoices-packing-slips/templates/Simple/invoice.php';
    // Формируем путь к резервной копии файла
    $backup_file  = $invoice_file . '.bak';

    // Если резервная копия существует, восстанавливаем исходное содержимое файла
    if ( file_exists( $backup_file ) ) {
        copy( $backup_file, $invoice_file );
        // Удаляем резервную копию после восстановления
        unlink( $backup_file );
    }
}

// Регистрируем функцию модификации шаблона при активации нашего плагина
register_activation_hook( __FILE__, 'mcf_modify_invoice_template' );

// Регистрируем функцию восстановления исходного шаблона при деактивации нашего плагина
register_deactivation_hook( __FILE__, 'mcf_restore_invoice_template' );


?>
