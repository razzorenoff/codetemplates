<?php
/*
Plugin Name: AQSI Integration
Description: Integration between WooCommerce, Elementor, JetEngine and aQsi API V3.
Version: 1.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include necessary files.
include_once plugin_dir_path( __FILE__ ) . 'includes/class-aqsi-api.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-integration.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/class-elementor-jetengine-integration.php';

// Initialize the plugin.
function aqsi_integration_init() {
    // Initialize the AQSI API.
    $aqsi_api = new AQSI_API();

    // Initialize WooCommerce integration.
    $woocommerce_integration = new WooCommerce_Integration( $aqsi_api );

    // Initialize Elementor and JetEngine integration.
    $elementor_jetengine_integration = new Elementor_JetEngine_Integration( $aqsi_api );
}

add_action( 'plugins_loaded', 'aqsi_integration_init' );

// Add settings menu item.
add_action( 'admin_menu', 'aqsi_integration_menu' );

function aqsi_integration_menu() {
    add_options_page( 'AQSI Integration Settings', 'AQSI Integration', 'manage_options', 'aqsi-integration', 'aqsi_integration_settings_page' );
}

function aqsi_integration_settings_page() {
    ?>
    <div class="wrap">
        <h1>AQSI Integration Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'aqsi_integration_settings' );
            do_settings_sections( 'aqsi-integration' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action( 'admin_init', 'aqsi_integration_settings_init' );

function aqsi_integration_settings_init() {
    register_setting( 'aqsi_integration_settings', 'aqsi_api_key' );

    add_settings_section(
        'aqsi_integration_section',
        'API Settings',
        null,
        'aqsi-integration'
    );

    add_settings_field(
        'aqsi_api_key',
        'AQSI API Key',
        'aqsi_api_key_render',
        'aqsi-integration',
        'aqsi_integration_section'
    );
}

function aqsi_api_key_render() {
    ?>
    <input type="text" name="aqsi_api_key" value="<?php echo get_option( 'aqsi_api_key' ); ?>">
    <?php
}

// Временный тестовый скрипт для проверки работы API.
add_action( 'admin_notices', 'aqsi_api_test' );

function aqsi_api_test() {
    if ( current_user_can( 'manage_options' ) ) {
        $aqsi_api = new AQSI_API();
        $response = $aqsi_api->get_orders();

        if ( $response ) {
            echo '<pre>' . print_r( $response, true ) . '</pre>';
        } else {
            echo '<div class="notice notice-error"><p>AQSI API Connection Failed.</p></div>';
        }
    }
}
?>
