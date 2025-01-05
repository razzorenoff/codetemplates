<?php
/**
 * Plugin Name: WooCommerce Custom Image Attributes
 * Description: Автоматическое назначение ALT и TITLE для изображений товаров WooCommerce на основе настраиваемых шаблонов.
 * Version: 1.3
 * Author: @maincoder_ru
 * Text Domain: wc-custom-image-attributes
 */

if (!defined('ABSPATH')) {
    exit; // Запрет прямого доступа
}

// Подключение файлов административной панели и вспомогательных функций
require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php'; // Административная панель
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';    // Вспомогательные функции
require_once plugin_dir_path(__FILE__) . 'includes/process-images.php'; // Логика обработки ALT и TITLE изображений

// Регистрация хуков активации и деактивации плагина
register_activation_hook(__FILE__, 'wc_custom_image_attributes_activate');
register_deactivation_hook(__FILE__, 'wc_custom_image_attributes_deactivate');

/**
 * Функция, выполняемая при активации плагина.
 * Она создаёт пустую опцию для хранения шаблонов, если она ещё не существует.
 */
function wc_custom_image_attributes_activate() {
    if (!get_option('wc_custom_templates')) {
        add_option('wc_custom_templates', []);
    }
}

/**
 * Функция, выполняемая при деактивации плагина.
 * Она оставляет данные, чтобы не терять настройки при повторной активации.
 */
function wc_custom_image_attributes_deactivate() {
    // Никаких действий при деактивации, чтобы сохранить данные
}
