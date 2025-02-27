<?php if (!defined('ABSPATH'))
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
                оплаты.Оплата данного счета означает согласие с условиями поставки товара. Счет действителен в течение
                5(пяти) банковских дней, не считая дня выписки счета. Уведомление об оплате обязательно, в противном
                случае НЕ ГАРАНТИРУЕТСЯ наличие товара на складе. Товар отпускается по факту прихода денег на р/с
                Поставщика. Доставка осуществляется силами Продавца, транспортными компаниями или самовывозом при наличии доверенности и паспорта.</div>
        </td>
        <td style="width: 32%; text-align: right; padding: 30px 0;"><img
                src="https://domain.com/wp-content/uploads/2023/08/b2b-1.png" style="width: 40%;"></td>
        </td>
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
        <td rowspan="2" style="min-height:19mm; height:auto; vertical-align: top;;">
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
    Счет на оплату №
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
        <?php $i = 0 /* для нумерации строк и подсчета количества позиций */?>
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
                        <?php $description_label = __('Description', 'woocommerce-pdf-invoices-packing-slips'); // registering alternate label translation ?>
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
        <td style="width:40mm; font-weight:bold;  text-align:right;">В том числе НДС 20%:</td>
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
/* Оборачиваем в функцию для возвращения суммы прописью (функция описана в плагине main-custom-functions -> mcf.php) */
$slova = num2str($orderp);
/* Выводим сумму словами, первую букву делаем заглавную и добавляем точку в конце*/
echo "<b>" . mb_strtoupper(mb_substr($slova, 0, 1)) . mb_substr($slova, 1, mb_strlen($slova)) . ".</b>"; ?>
<div>Стоимость доставки не включена в итоговую сумму оплаты по этому счету. В ряде случаев доставка может стать для Вас совершенно бесплатной. Доставка приобретенного товара осуществляется силами Продавца и транспортными компаниями. Сроки и стоимость рассчитываются индивидуально.</div>
<div style="margin-top: 20px;">
    <div style="background-color:#000000; width:100%; font-size:1px; height:2px;">&nbsp;</div>
    <br />

    <div style="position: relative;">
    <div style="display: inline-flex; flex-wrap:nowrap;">
        <img style="height:30px; margin-left: 130px; margin-top: -10px; margin-bottom: -20px;" src="https://domain.com/wp-content/uploads/2023/11/podpis.png" alt="">
        <img style="height:30px; margin-left: 230px; margin-top: -10px; margin-bottom: -20px;" src="https://domain.com/wp-content/uploads/2023/11/podpis.png" alt="">
    </div>

        <div>Руководитель ______________________/Иванов В. Н./   Главный бухгалтер ______________________/Петров В. Н./
        </div>

    </div>
    <div
        style="width:100%; position: absolute; left: 30px;  height: 100%; background: url('https://domain.com/wp-content/uploads/...'); background-size: 150px 150px; background-repeat: no-repeat; padding: 30px 10px;">
        М.П.</div>
</div>