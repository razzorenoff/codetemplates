<?php

class AQSI_API {
    private $api_url = 'https://api.aqsi.ru/v3/';
    private $api_key;

    public function __construct() {
        $this->api_key = get_option( 'aqsi_api_key' ); // Получаем API ключ из настроек.
    }

    private function request( $endpoint, $method = 'GET', $data = [] ) {
        $url = $this->api_url . $endpoint;
        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
        ];

        if ( ! empty( $data ) ) {
            $args['body'] = json_encode( $data );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    public function get_orders() {
        return $this->request( 'orders' );
    }

    public function create_order( $order_data ) {
        return $this->request( 'orders', 'POST', $order_data );
    }

    // Другие методы для работы с aQsi API.
}

?>
