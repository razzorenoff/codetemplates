<?php
/**
 * Plugin Name: Форматирование полей в формах WordPress
 * Description: Плагин для форматирования полей ввода в формах Elementor и WooCommerce. Включает маски ввода, очистку полей и сессий.
 * Version: 1.0
 * Author: @maincoder_ru
 * Text Domain: form-field-formatting
 */

// Подключение основных файлов
require_once plugin_dir_path(__FILE__) . 'includes/class-form-field-formatting-frontend.php';

// Инициализация плагина
function form_field_formatting_init() {
    // Инициализация обработки фронтенда
    $frontend = new Form_Field_Formatting_Frontend();
    $frontend->init();
}
add_action('plugins_loaded', 'form_field_formatting_init');
