<?php
/**
 * Plugin Name: Custom Floor Plugin
 * Description: Плагин для расчета стоимости подъема на этаж и добавления информации на странице оформления заказа.
 * Version: 1.0
 * Author: @maincoder_ru
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Подключение скриптов и стилей
function cf_enqueue_scripts() {
    // Подключаем основной скрипт с низким приоритетом, чтобы он загружался последним
    wp_enqueue_script( 'cf-scripts', plugin_dir_url( __FILE__ ) . 'js/cf-scripts.js', array( 'jquery' ), '1.0', true );

    // Подключаем стили
    wp_enqueue_style( 'cf-styles', plugin_dir_url( __FILE__ ) . 'css/cf-styles.css' );

    // Локализация данных для скрипта
    wp_localize_script( 'cf-scripts', 'cf_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}
add_action( 'wp_enqueue_scripts', 'cf_enqueue_scripts', 100 ); // Приоритет 100, чтобы скрипт загружался последним


// Добавление вкладки в настройки WooCommerce
function cf_add_shipping_tab( $settings_tabs ) {
    $settings_tabs['cf_lift'] = __( 'Подъем на этаж', 'woocommerce' );
    return $settings_tabs;
}
add_filter( 'woocommerce_settings_tabs_array', 'cf_add_shipping_tab', 50 );

function cf_settings_tab_content() {
    woocommerce_admin_fields( get_cf_settings() );
}
add_action( 'woocommerce_settings_tabs_cf_lift', 'cf_settings_tab_content' );

function cf_save_settings() {
    woocommerce_update_options( get_cf_settings() );
}
add_action( 'woocommerce_update_options_cf_lift', 'cf_save_settings' );

function get_cf_settings() {
    $settings = array(
        'section_title' => array(
            'name'     => __( 'Настройки подъема на этаж', 'woocommerce' ),
            'type'     => 'title',
            'desc'     => '',
            'id'       => 'cf_lift_settings'
        ),
        'floor_price' => array(
            'name' => __( 'Цена подъема на один этаж', 'woocommerce' ),
            'type' => 'number',
            'desc' => __( 'Укажите цену подъема на один этаж', 'woocommerce' ),
            'id'   => 'cf_floor_price'
        ),
        'section_end' => array(
            'type' => 'sectionend',
            'id'   => 'cf_lift_settings'
        )
    );
    return $settings;
}

// Добавление блока "Подъем на этаж" на страницу оформления заказа
function cf_add_custom_checkout_field( $checkout ) {
    echo '<div id="cf_checkout_additional_info"><h2>' . __( 'Подъем на этаж', 'woocommerce' ) . '</h2>';

    // Убедимся, что по умолчанию выбрана опция "Без подъема на этаж"
    woocommerce_form_field( 'cf_lift_option', array(
        'type' => 'radio',
        'class' => array('cf-lift-option'),
        'label' => __( 'Выберите вариант подъема на этаж' ),
        'options' => array(
            'no_lift' => 'Без подъема на этаж или есть грузовой лифт',
            'lift' => 'Требуется подъем на этаж, грузового лифта нет'
        ),
        'default' => 'no_lift', // Указываем 'no_lift' как значение по умолчанию
    ), 'no_lift'); // Также указываем значение по умолчанию здесь

    echo '<div id="cf_floor_details" class="hidden">';
    woocommerce_form_field( 'cf_floor_count', array(
        'type' => 'number',
        'class' => array('cf-floor-count'),
        'label' => __( 'Количество этажей' ),
        'default' => 1,
    ), $checkout->get_value( 'cf_floor_count' ));

    echo '<p id="cf_lift_cost" class="cf-lift-cost">' . __( 'Стоимость подъема на этаж: ', 'woocommerce' ) . '<span class="cf-cost-amount">0</span></p>';
    echo '</div>';

    echo '<input type="hidden" name="cf_lift_cost" value="0">'; // Добавлено скрытое поле для стоимости подъема

    echo '</div>';
}

add_action( 'woocommerce_after_order_notes', 'cf_add_custom_checkout_field' );

// Сохранение значений пользовательских полей
function cf_save_custom_checkout_field( $order_id ) {
    if ( ! empty( $_POST['cf_lift_option'] ) ) {
        $lift_option = sanitize_text_field( $_POST['cf_lift_option'] );
        $lift_option_label = ($lift_option == 'lift') ? 'Требуется подъем на этаж, грузового лифта нет' : 'Без подъема на этаж или есть грузовой лифт';
        update_post_meta( $order_id, '_cf_lift_option', $lift_option );
        update_post_meta( $order_id, '_cf_lift_option_label', $lift_option_label );
    }
    if ( ! empty( $_POST['cf_floor_count'] ) ) {
        update_post_meta( $order_id, '_cf_floor_count', intval( $_POST['cf_floor_count'] ) );
    }
    if ( isset( $_POST['cf_lift_cost'] ) ) {
        update_post_meta( $order_id, '_cf_lift_cost', floatval( $_POST['cf_lift_cost'] ) );
    }
}
add_action( 'woocommerce_checkout_update_order_meta', 'cf_save_custom_checkout_field' );

// Показ значений пользовательских полей в админке
function cf_display_order_data_in_admin( $order ) {
    $lift_option_label = get_post_meta( $order->get_id(), '_cf_lift_option_label', true );
    $floor_count = get_post_meta( $order->get_id(), '_cf_floor_count', true );
    $lift_cost = get_post_meta( $order->get_id(), '_cf_lift_cost', true ); ?>
    <div class="order_data_column">
        <h4><?php _e( 'Подъем на этаж', 'woocommerce' ); ?></h4>
        <p><strong><?php _e( 'Вариант подъема на этаж', 'woocommerce' ); ?>:</strong> <?php echo $lift_option_label; ?></p>
        <p><strong><?php _e( 'Количество этажей', 'woocommerce' ); ?>:</strong> <?php echo $floor_count; ?></p>
        <p><strong><?php _e( 'Стоимость подъема на этаж', 'woocommerce' ); ?>:</strong> <?php echo wc_price( $lift_cost ); ?></p>
    </div>
<?php }
add_action( 'woocommerce_admin_order_data_after_billing_address', 'cf_display_order_data_in_admin', 10, 1 );

// Расчет стоимости подъема на этаж
function cf_calculate_lift_cost( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    $floor_price = floatval( get_option( 'cf_floor_price', 0 ) );
    $lift_option = WC()->session->get( 'cf_lift_option' );
    $floor_count = intval( WC()->session->get( 'cf_floor_count', 1 ) );

    $lift_cost = 0;

    if ( $lift_option == 'lift' ) {
        $lift_cost = $floor_price * $floor_count;
    }

    WC()->cart->add_fee( __( 'Стоимость подъема на этаж', 'woocommerce' ), $lift_cost );

    // Сохранение стоимости подъема на этаж в мета данных заказа
    WC()->session->set( 'cf_lift_cost', $lift_cost );
}
add_action( 'woocommerce_cart_calculate_fees', 'cf_calculate_lift_cost' );

// AJAX обработка данных
function cf_update_lift_option() {
    $lift_option = sanitize_text_field( $_POST['lift_option'] );
    $floor_count = intval( $_POST['floor_count'] );
    $floor_price = floatval( get_option( 'cf_floor_price', 0 ) );

    $lift_cost = 0;

    if ( $lift_option == 'lift' ) {
        $lift_cost = $floor_price * $floor_count;
    }

    WC()->session->set( 'cf_lift_option', $lift_option );
    WC()->session->set( 'cf_floor_count', $floor_count );
    WC()->session->set( 'cf_lift_cost', $lift_cost );

    // Пересчет стоимости
    WC()->cart->calculate_totals();

    wp_send_json_success(array('lift_cost' => $lift_cost));
}
add_action( 'wp_ajax_cf_update_lift_option', 'cf_update_lift_option' );
add_action( 'wp_ajax_nopriv_cf_update_lift_option', 'cf_update_lift_option' );


