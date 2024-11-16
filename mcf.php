<?php
/**
	Plugin Name: B2B SERVICES (Custom Functions)
	Plugin URI: https://maincoder.ru/
	Description: Свои функции для сайта
	Author: maincoder
	Version: 1.0
	Author URI: https://maincoder.ru/
	Text Domain: b2b-custom-functions
	License: GPL
**/
/* Ниже размещаем код */

/*ОТКЛ. требования сложного пароля при регистрации*/
// отключите zxcvbn.min.js в wordpress - disable
add_action( 'wp_print_scripts', function () {
// Скрипт отмены счетчика сложности пароля
wp_dequeue_script('zxcvbn-async');
wp_deregister_script('zxcvbn-async');
} );
/*ОТКЛ. требования сложного пароля при регистрации*/

/*Этот код добавит поддержку файлов .xls в WordPress*/

// function custom_upload_mimes( $existing_mimes ) {
//     // Добавить MIME-тип для .xlsx
//     $existing_mimes['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
//     // Добавить MIME-тип для .xls
//     $mimes['xls'] = 'application/vnd.ms-excel';
//     // Вернуть измененный список
//     return $existing_mimes;
// }

// add_filter( 'upload_mimes', 'custom_upload_mimes' );

/* Эти функции (num2str и morph) нужна для переводы суммы счета в буквенный формат
Мы используем её в файле /wp-content/themes/astra/woocommerce/pdf/InvoicesB2B/invoice.php */

/**
 * Возвращает сумму прописью
 * @author runcore
 * @uses morph(...)
 */
if (!function_exists('num2str')) {
function num2str($num) {
    $nul='ноль';
    $ten=array(
        array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
        array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
    );
    $a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
    $tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
    $hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
    $unit=array( // Units
        array('копейка' ,'копейки' ,'копеек',    1),
        array('рубль'   ,'рубля'   ,'рублей'    ,0),
        array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
        array('миллион' ,'миллиона','миллионов' ,0),
        array('миллиард','милиарда','миллиардов',0),
    );
    //
    list($rub,$kop) = explode('.',sprintf("%015.2f", floatval($num)));
    $out = array();
    if (intval($rub)>0) {
        foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
            if (!intval($v)) continue;
            $uk = sizeof($unit)-$uk-1; // unit key
            $gender = $unit[$uk][3];
            list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
            // mega-logic
            $out[] = $hundred[$i1]; # 1xx-9xx
            if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
            else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
            // units without rub & kop
            if ($uk>1) $out[]= morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
        } //foreach
    }
    else $out[] = $nul;
    $out[] = morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
    $out[] = $kop.' '.morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
    return trim(preg_replace('/ {2,}/', ' ', join(' ',$out)));
}
}

/**
 * Склоняем словоформу
 * @ author runcore
 */
if (!function_exists('morph')) {
function morph($n, $f1, $f2, $f5) {
    $n = abs(intval($n)) % 100;
    if ($n>10 && $n<20) return $f5;
    $n = $n % 10;
    if ($n>1 && $n<5) return $f2;
    if ($n==1) return $f1;
    return $f5;
}
}
/*Удаляем изображение из корзины*/
add_filter( 'woocommerce_cart_item_thumbnail', 'remove_image_from_mini_cart' );
function remove_image_from_mini_cart( $product_image ) {
	$cart_page_id = wc_get_page_id( 'cart' );
	if( ! is_page( $cart_page_id ) ) {
		return '';
	}
	return $product_image;
}

/*Обновлене количества товаров в корзине*/
add_filter( 'woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment_amount' );
function woocommerce_header_add_to_cart_fragment_amount( $fragments_amount ) {
    ob_start(); ?>
    <div class="cart_total_amount"><?php echo sprintf (_n( '%d', '%d', WC()->cart->cart_contents_count ), WC()->cart->cart_contents_count ); ?></div> 
    <?php	$fragments_amount['div.cart_total_amount'] = ob_get_clean(); // селектор блока обертки
    return $fragments_amount;
}
	/**
	 * Get number of items in the cart.
	 *
	 * @return int
	 */
function get_cart_contents_count() {
		return apply_filters( 'woocommerce_cart_contents_count', array_sum( wp_list_pluck( $this->get_cart(), 'quantity' ) ) );
	}


/*Обновлене суммы корзины*/
add_filter( 'woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment' );
function woocommerce_header_add_to_cart_fragment( $fragments ) {
    ob_start(); ?>
    <div class="cart_total"><?php echo WC()->cart->get_cart_total(); ?></div> 
    <?php	$fragments['div.cart_total'] = ob_get_clean(); // селектор блока обертки
    return $fragments;
}

/*показать общую сумму всех оплаченных и завершенных заказов клиента
 * вставить в нужное место код: echo get_customer_total_order();
 * */
function get_customer_total_order() {
    $customer_orders = get_posts( array(
        'numberposts' => - 1,
        'meta_key'    => '_customer_user',
        'meta_value'  => get_current_user_id(),
        'post_type'   => array( 'shop_order' ),
        'post_status' => array( 'completed' )
    ) );

    $total = 0;
    foreach ( $customer_orders as $customer_order ) {
        $order = wc_get_order( $customer_order );
        $total += $order->get_total();
    }

    return $total;
}

/*вывести общее количество оплаченных заказов клиента
 * где шорткод с параметрами [wc_order_count status="pending,completed,otgruzhen,processing,na-sklade-v-rf,na-promezhutochno,v-puti-v-rf, "]  выведет сумму соответствующих значений.
 * */
function display_woocommerce_order_count( $atts, $content = null ) {
    $args = shortcode_atts( array(
        'status' => 'completed',
    ), $atts );
    $statuses    = array_map( 'trim', explode( ',', $args['status'] ) );
    $order_count = 0;
    foreach ( $statuses as $status ) {
        if ( 0 !== strpos( $status, 'wc-' ) ) {
            $status = 'wc-' . $status;
        }
        $order_count += wp_count_posts( 'shop_order' )->$status;
    }
    ob_start();
    echo number_format( $order_count );
    return ob_get_clean();
}
add_shortcode( 'wc_order_count', 'display_woocommerce_order_count' );


/* Далее не размещаем */
?>
