<?php

class WooCommerce_Integration {
    private $aqsi_api;

    public function __construct( $aqsi_api ) {
        $this->aqsi_api = $aqsi_api;

        add_action( 'woocommerce_thankyou', [ $this, 'send_order_to_aqsi' ] );
    }

    public function send_order_to_aqsi( $order_id ) {
        $order = wc_get_order( $order_id );
        $order_data = [
            // Формируем данные заказа для aQsi API.
        ];

        $response = $this->aqsi_api->create_order( $order_data );

        if ( ! $response ) {
            // Обработка ошибки.
        }
    }
}

?>
