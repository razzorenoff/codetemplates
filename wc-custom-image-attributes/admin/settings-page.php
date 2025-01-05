<?php
if (!defined('ABSPATH')) {
    exit; // Запрет прямого доступа
}

// Подключение стилей и скриптов для административной панели
add_action('admin_enqueue_scripts', 'wc_custom_admin_scripts');

/**
 * Подключение JavaScript и CSS для страницы настроек.
 */
function wc_custom_admin_scripts() {
    wp_enqueue_script('wc-custom-instructions', plugin_dir_url(__FILE__) . 'instructions.js', ['jquery'], null, true);
    wp_enqueue_style('wc-custom-instructions', plugin_dir_url(__FILE__) . 'instructions.css');
}

// Добавление страницы настроек в меню WooCommerce
add_action('admin_menu', 'wc_custom_image_attributes_menu');
add_action('admin_init', 'wc_custom_image_attributes_settings');

/**
 * Регистрация страницы настроек в меню WooCommerce.
 */
function wc_custom_image_attributes_menu() {
    add_submenu_page(
        'woocommerce',                          // Родительское меню WooCommerce
        'Настройки ALT и TITLE',                // Заголовок страницы
        'Image Attributes',                     // Название в меню
        'manage_options',                       // Требуемые права
        'wc-custom-image-attributes',           // Слаг страницы
        'wc_custom_image_attributes_page'       // Callback-функция для вывода содержимого
    );
}

/**
 * Регистрация опции для хранения настроек.
 */
function wc_custom_image_attributes_settings() {
    register_setting('wc_custom_image_attributes', 'wc_custom_templates'); // Опция для шаблонов
}

/**
 * Отображение страницы настроек.
 */
function wc_custom_image_attributes_page() {
    $templates = get_option('wc_custom_templates', []); // Получение шаблонов из базы данных
    $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]); // Получение всех категорий товаров

    ?>
    <div class="wrap">
        <h1>Настройки ALT и TITLE изображений</h1>
        
        <!-- Кнопка для открытия инструкции -->
        <button id="wc-custom-instructions-btn" class="button button-primary" style="margin-bottom: 20px;">Инструкция по применению</button>
        
        <!-- Попап с инструкцией -->
        <div id="wc-custom-instructions-popup" class="wc-custom-popup">
            <div class="wc-custom-popup-content">
                <span id="wc-custom-popup-close">&times;</span>
                <div class="wc-custom-popup-scroll">
                    <?php echo file_get_contents(plugin_dir_path(__FILE__) . '../README-instructions.html'); ?>
                </div>
            </div>
        </div>

        <!-- Форма настроек -->
        <form method="post" action="options.php">
            <?php
            settings_fields('wc_custom_image_attributes'); // Генерация скрытых полей для безопасности
            do_settings_sections('wc_custom_image_attributes'); // Подключение секций настроек
            ?>
            <?php foreach ($categories as $category): ?>
                <?php 
                $max_images = wc_custom_get_max_gallery_images($category->term_id); // Получение максимального количества изображений в галерее категории
                ?>
                <div class="wc-custom-category">
                    <h2><?php echo esc_html($category->name); ?></h2>
                    
                    <div class="wc-custom-category-content">
                        <!-- Основное изображение -->
                        <div class="wc-custom-block">
                            <h3>Основное изображение</h3>
                            <div class="wc-custom-field">
                                <label>ALT:</label>
                                <input type="text" name="wc_custom_templates[<?php echo esc_attr($category->slug); ?>][alt]" 
                                       value="<?php echo esc_attr($templates[$category->slug]['alt'] ?? ''); ?>" 
                                       placeholder="Пример: {name} + ваш текст">
                            </div>
                            <div class="wc-custom-field">
                                <label>TITLE:</label>
                                <input type="text" name="wc_custom_templates[<?php echo esc_attr($category->slug); ?>][title]" 
                                       value="<?php echo esc_attr($templates[$category->slug]['title'] ?? ''); ?>" 
                                       placeholder="Пример: {name}">
                            </div>
                        </div>

                        <!-- Галерея изображений -->
                        <div class="wc-custom-block">
                            <h3>Галерея</h3>
                            <?php if ($max_images === 0): // Если изображений в галереях нет ?>
                                <p>В галерее товара изображения не добавлены.</p>
                            <?php else: ?>
                                <?php for ($i = 1; $i <= $max_images; $i++): ?>
                                    <div class="wc-custom-gallery-row">
                                        <strong>Изображение <?php echo $i; ?>:</strong>
                                        <div class="wc-custom-field">
                                            <label>ALT:</label>
                                            <input type="text" 
                                                   name="wc_custom_templates[<?php echo esc_attr($category->slug); ?>][alt_gallery_<?php echo $i; ?>]" 
                                                   value="<?php echo esc_attr($templates[$category->slug]["alt_gallery_$i"] ?? ''); ?>" 
                                                   placeholder="Пример: {name} - вид <?php echo $i; ?>">
                                        </div>
                                        <div class="wc-custom-field">
                                            <label>TITLE:</label>
                                            <input type="text" 
                                                   name="wc_custom_templates[<?php echo esc_attr($category->slug); ?>][title_gallery_<?php echo $i; ?>]" 
                                                   value="<?php echo esc_attr($templates[$category->slug]["title_gallery_$i"] ?? ''); ?>" 
                                                   placeholder="Пример: {name} - вид <?php echo $i; ?>">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php submit_button(); ?>
        </form>

        <!-- Кнопка для массового обновления товаров -->
        <button id="wc-custom-bulk-update" class="button button-secondary">Обновить товары</button>
    </div>
    <?php
}
