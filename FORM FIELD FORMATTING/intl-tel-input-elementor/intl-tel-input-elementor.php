<?php
/**
 * Plugin Name: Форматирование полей в формах WordPress
 * Description: Добавляет поле ввода телефонного номера с флагами и кодами стран для форм Elementor и WooCommerce. Устанавливает Россию по умолчанию и применяет маску ввода для российских номеров. Добавляет маску ввода для электронной почты. Очищает поля форм и отключает автозаполнение.
 * Version: 1.29
 * Author: @maincoder_ru
 * Text Domain: intl-tel-input-elementor
 */

// Подключение необходимых стилей и скриптов
function enqueue_intl_tel_input_assets() {
    wp_enqueue_style('intl-tel-input-css', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css');
    wp_enqueue_script('intl-tel-input-js', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js', array('jquery'), '17.0.8', true);
    wp_enqueue_script('intl-tel-input-utils-js', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js', array('intl-tel-input-js'), '17.0.8', true);
    wp_enqueue_script('jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js', array('jquery'), '1.14.16', true);
}
add_action('wp_enqueue_scripts', 'enqueue_intl_tel_input_assets');

// Добавление стилей для всплывающей подсказки
function add_custom_styles() {
    ?>
    <style>
        .error-tooltip {
            display: none;
            position: absolute;
            background-color: #f44336;
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
        }

        .error-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #f44336 transparent transparent transparent;
        }
    </style>
    <?php
}
add_action('wp_head', 'add_custom_styles');

// Очищение данных и отключение автозаполнения
function reset_checkout_and_elementor_forms() {
    // Очищаем значения полей WooCommerce
    add_filter('woocommerce_checkout_get_value', '__return_empty_string');

    // Отключаем автозаполнение в браузере
    add_filter('woocommerce_checkout_fields', 'disable_autocomplete_checkout_fields');
    
    function disable_autocomplete_checkout_fields($fields) {
        foreach ($fields as &$fieldset) {
            foreach ($fieldset as &$field) {
                $field['autocomplete'] = 'off';
            }
        }
        return $fields;
    }

    // Очищаем данные сессии WooCommerce
    add_action('woocommerce_before_checkout_form', 'clear_wc_session_data');
    
    function clear_wc_session_data() {
        if (WC()->session) {
            WC()->session->set('customer', array());
        }
    }

    // Очищаем формы Elementor при загрузке
    add_action('wp_footer', 'reset_elementor_form_fields');

    function reset_elementor_form_fields() {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var wcFields = document.querySelectorAll('.woocommerce-checkout input, .woocommerce-checkout select, .woocommerce-checkout textarea');
                wcFields.forEach(function(field) {
                    field.value = '';
                });

                var elementorFields = document.querySelectorAll('.elementor-form input, .elementor-form select, .elementor-form textarea');
                elementorFields.forEach(function(field) {
                    field.value = '';
                });
            });
        </script>
        <?php
    }
}
add_action('init', 'reset_checkout_and_elementor_forms');

// Инициализация intl-tel-input для полей Elementor и WooCommerce
function init_intl_tel_input() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            function initializeIntlTelInput(input) {
                var iti = window.intlTelInput(input, {
                    initialCountry: "ru", // Устанавливаем Россию по умолчанию
                    separateDialCode: true, // Отображение кода страны отдельно
                    autoPlaceholder: "aggressive", // Включение плейсхолдера
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
                });

                // Применение маски для российских номеров
                $(input).mask('(000) 000-00-00', { 
                    placeholder: '(___) ___-__-__', 
                    clearIfNotMatch: true 
                });

                // Удаление первой цифры 8 или +7 и корректное отображение маски
                $(input).on('input', function() {
                    var value = input.value.replace(/[^0-9]/g, ''); // Удаляем все кроме цифр
                    if (value.startsWith('8')) {
                        value = value.substring(1);
                    } else if (value.startsWith('7')) {
                        value = value.substring(1);
                    }
                    $(input).mask('(000) 000-00-00', { placeholder: '(___) ___-__-__' });
                    input.value = value;
                });

                // Обработка вставки текста
                $(input).on('paste', function() {
                    setTimeout(function() {
                        var value = input.value.replace(/[^0-9]/g, ''); // Удаляем все кроме цифр
                        if (value.startsWith('8')) {
                            value = value.substring(1);
                        } else if (value.startsWith('7')) {
                            value = value.substring(1);
                        }
                        $(input).mask('(000) 000-00-00', { placeholder: '(___) ___-__-__' });
                        input.value = value;
                    }, 100);
                });

                // Удаление скобок и дефисов перед отправкой формы и добавление кода страны
                $(input.form).on('submit', function() {
                    if (iti.isValidNumber()) {
                        var fullNumber = iti.getNumber();
                        input.value = fullNumber; // Сохраняем номер телефона в международном формате
                    } else {
                        showErrorTooltip(input, 'Вы ввели недопустимые данные. Попробуйте еще раз.');
                        input.value = '';
                        return false; // Останавливаем отправку формы при неверном номере
                    }
                });
            }

            // Показ всплывающей подсказки
            function showErrorTooltip(input, message) {
                var tooltip = $('<div class="error-tooltip"></div>').text(message);
                $('body').append(tooltip);
                var offset = $(input).offset();
                tooltip.css({
                    top: offset.top - tooltip.outerHeight() - 10,
                    left: offset.left + ($(input).outerWidth() - tooltip.outerWidth()) / 2
                }).fadeIn();

                setTimeout(function() {
                    tooltip.fadeOut(function() {
                        tooltip.remove();
                    });
                }, 5000);
            }

            // Функция для инициализации полей
            function initPhoneAndEmailFields(container) {
                var phoneInputSelectors = container.find('input[type="tel"], #billing_phone, #shipping_phone, #account_phone'); // Массив селекторов полей ввода телефона
                phoneInputSelectors.each(function() {
                    initializeIntlTelInput(this);
                });

                // Применение маски ввода для поля электронной почты
                container.find('input[type="email"]').inputmask({
                    alias: "email",
                    placeholder: "mail@domain.com",
                    clearIncomplete: true,
                    clearMaskOnLostFocus: true
                });

                // Очистка поля электронной почты, если оно не соответствует маске при потере фокуса
                container.find('input[type="email"]').on('blur', function() {
                    var email = $(this).val();
                    var regex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+\.[a-zA-Z]{2,}$/; // Обновленное регулярное выражение
                    if (!regex.test(email)) {
                        showErrorTooltip(this, 'Вы ввели недопустимые данные. Попробуйте еще раз.');
                        $(this).val('');
                    }
                });
            }

            // Инициализация на всех полях ввода при загрузке страницы
            initPhoneAndEmailFields($(document));

            // Инициализация на полях ввода в попапах при их открытии
            $(document).on('elementor/popup/show', function(event, id, instance) {
                initPhoneAndEmailFields(instance.$element);
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'init_intl_tel_input', 100);
