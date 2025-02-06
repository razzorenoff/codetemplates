<?php
/*
Plugin Name: Membre Grid Widget
Description: Custom Elementor widget for displaying a grid of Membres created in the WordPress admin.
Version: 1.0
Author: @maincoder_ru
*/

// Регистрация кастомного типа записи "Membre"
require_once __DIR__ . '/register-membre-post-type.php';

// Подключение и регистрация виджета для Elementor
ffunction membre_grid_widget_register( $widgets_manager ) {
    require_once plugin_dir_path( __FILE__ ) . 'widgets/class-membre-grid-widget.php';
    $widgets_manager->register( new \Elementor\Membre_Grid_Widget() );
}
add_action( 'elementor/widgets/register', 'membre_grid_widget_register' );

// Подключение стилей для виджета
function membre_grid_widget_enqueue_styles() {
    wp_enqueue_style( 'membre-grid-widget', plugin_dir_url( __FILE__ ) . 'assets/membre-grid.css' );
}
add_action( 'wp_enqueue_scripts', 'membre_grid_widget_enqueue_styles' );
?>
