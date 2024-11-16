<?php

class Form_Field_Formatting_Frontend {

    private $script_priority = 999;  // Приоритет загрузки скриптов (максимально низкий)
    private $hook_priority = 999;    // Приоритет выполнения хуков (максимально низкий)

    public function init() {
        // Подключаем скрипты и стили при загрузке страницы
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), $this->script_priority);
        
        // Инициализация поля телефона и email в футере, после загрузки страницы
        add_action('wp_footer', array($this, 'init_intl_tel_input'), $this->script_priority + 1);
        
        // Сброс значений полей при загрузке страницы и другие настройки
        add_action('init', array($this, 'reset_checkout_and_elementor_forms'), $this->hook_priority);
    }

    public function enqueue_assets() {
        // Подключаем внешние CDN-ресурсы для стилей и скриптов
        wp_enqueue_style('intl-tel-input-css', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css');
        wp_enqueue_script('intl-tel-input-js', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js', array('jquery'), null, true);
        wp_enqueue_script('intl-tel-input-utils-js', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js', array('intl-tel-input-js'), null, true);
        wp_enqueue_script('jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js', array('jquery'), null, true);
        wp_enqueue_script('jquery-inputmask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.7-beta.29/jquery.inputmask.min.js', array('jquery'), null, true);
    }

    public function reset_checkout_and_elementor_forms() {
        // Сброс значений полей в процессе оформления заказа WooCommerce
        add_filter('woocommerce_checkout_get_value', '__return_empty_string', $this->hook_priority);

        // Отключаем автозаполнение для полей телефона и email
        add_filter('woocommerce_checkout_fields', array($this, 'disable_autocomplete_checkout_fields'), $this->hook_priority + 1);
        
        // Сброс значений полей в Elementor формах при загрузке страницы
        add_action('wp_footer', array($this, 'reset_elementor_form_fields'), $this->hook_priority + 3);
    }

    public function disable_autocomplete_checkout_fields($fields) {
        // Проходим по всем полям и отключаем автозаполнение для определенных полей
        foreach ($fields as $fieldset_key => &$fieldset) {
            foreach ($fieldset as $key => &$field) {
                if (!$this->should_skip_field($key) && in_array($key, ['billing_phone', 'shipping_phone', 'account_phone', 'billing_email', 'shipping_email', 'account_email'])) {
                    $field['autocomplete'] = 'off';
                }
            }
        }
        return $fields;
    }

    public function reset_elementor_form_fields() {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log("Сброс значений полей в WooCommerce и Elementor формах.");

                // Сбрасываем значения полей телефона и email в WooCommerce формах
                var wcFields = document.querySelectorAll('.woocommerce-checkout input[name="billing_phone"], .woocommerce-checkout input[name="shipping_phone"], .woocommerce-checkout input[name="account_phone"], .woocommerce-checkout input[name="billing_email"], .woocommerce-checkout input[name="shipping_email"], .woocommerce-checkout input[name="account_email"]');
                wcFields.forEach(function(field) {
                    if (!shouldSkipField(field.name)) {
                        field.value = '';
                    }
                });

                // Сбрасываем значения полей телефона и email в Elementor формах
                var elementorFields = document.querySelectorAll('.elementor-form input[type="tel"], .elementor-form input[type="email"]');
                elementorFields.forEach(function(field) {
                    if (!shouldSkipField(field.name)) {
                        field.value = '';
                    }
                });

                // Добавление крестика для очистки поля адреса
                var addressFields = document.querySelectorAll('.woocommerce-checkout input[name="billing_address_1"], .woocommerce-checkout input[name="shipping_address_1"]');
                addressFields.forEach(function(field) {
                    var wrapper = document.createElement('div');
                    wrapper.className = 'address-wrapper';
                    field.parentNode.insertBefore(wrapper, field);
                    wrapper.appendChild(field);

                    var clearIcon = document.createElement('span');
                    clearIcon.className = 'clear-icon';
                    clearIcon.innerHTML = '&times;';
                    wrapper.appendChild(clearIcon);

                    clearIcon.addEventListener('click', function() {
                        console.log("Поле адреса очищено.");
                        field.value = '';
                    });
                });
            });

            function shouldSkipField(fieldName) {
                var skipFields = ['_cf_lift_option', '_cf_lift_option_label', '_cf_floor_count', '_cf_lift_cost'];
                return skipFields.includes(fieldName);
            }
        </script>
        <style>
            .address-wrapper {
                position: relative;
                display: inline-block;
                width: 100%;
            }

            .address-wrapper .clear-icon {
                position: absolute;
                top: 50%;
                right: 10px;
                transform: translateY(-50%);
                cursor: pointer;
                color: #ccc;
                font-size: 20px;
                display: none;
            }

            .address-wrapper input {
                padding-right: 30px; /* Увеличиваем отступ для поля, чтобы учесть место для иконки */
            }

            .address-wrapper input:not(:placeholder-shown) + .clear-icon {
                display: inline;
            }

            /* Стили для всплывающей подсказки */
            .error-tooltip {
                background-color: #000; /* Черный фон */
                border: 1px solid #fff; /* Белая обводка */
                color: #fff; /* Белый текст */
                font-size: 14px; /* Размер текста 14px */
                padding: 5px 10px; /* Внутренний отступ */
                border-radius: 5px; /* Сглаженные углы */
                position: absolute;
                z-index: 1000; /* Поверх всех элементов */
                display: none; /* По умолчанию скрыто */
                white-space: nowrap; /* Текст в одну строку */
            }

            .error-tooltip::after {
                content: ''; /* Добавляем стрелку, указывающую вниз */
                position: absolute;
                left: 50%; /* Центрируем стрелку по горизонтали */
                transform: translateX(-50%); /* Точное центрирование */
                top: 100%; /* Позиционируем стрелку снизу подсказки */
                border-width: 10px; /* Размер стрелки */
                border-style: solid;
                border-color: #fff transparent transparent transparent; /* Цвет стрелки: белый */
            }
        </style>
        <?php
    }

    public function init_intl_tel_input() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                console.log("Инициализация полей телефона.");

                function initializeIntlTelInput(input) {
                    var iti = window.intlTelInput(input, {
                        initialCountry: "ru", 
                        separateDialCode: true, 
                        autoPlaceholder: "aggressive", 
                        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
                    });

                    // Применяем маску, чтобы скобки и тире оставались видимыми
                    $(input).mask('(000) 000-00-00', { placeholder: '(___) ___-__-__' });

                    // Обработка вставки текста
                    $(input).on('paste', function() {
                        setTimeout(function() {
                            var value = input.value.replace(/[^0-9]/g, ''); // Удаляем все кроме цифр
                            if (value.startsWith('8') || value.startsWith('7')) {
                                value = value.substring(1);
                            }
                            $(input).mask('(000) 000-00-00', { placeholder: '(___) ___-__-__' });
                            input.value = value;
                            console.log("Вставка текста в поле телефона: " + input.value);
                        }, 100);
                    });

                    $(input).on('input', function() {
                        var value = input.value.replace(/\D/g, '');
                        if (value.startsWith('8') || value.startsWith('7')) {
                            value = value.substring(1);
                        }
                        input.value = value;
                        $(input).mask('(000) 000-00-00', { placeholder: '(___) ___-__-__' });
                        console.log("Ввод номера телефона: " + input.value);
                    });

                    // Проверка на минимальное количество цифр (10 для РФ)
                    $(input).on('blur', function() {
                        var digitsOnly = input.value.replace(/\D/g, '');
                        if (digitsOnly.length < 10) {
                            showErrorTooltip(input, 'Введите полный номер телефона.');
                            console.log("Ошибка: недостаточное количество цифр в номере.");
                            input.value = ''; // Очищаем поле
                        }
                    });

                    // Проверяем корректность введенного номера перед отправкой формы
                    $(input.form).on('submit', function() {
                        var digitsOnly = input.value.replace(/\D/g, '');
                        if (digitsOnly.length < 10 || !iti.isValidNumber()) {
                            showErrorTooltip(input, 'Вы ввели некорректный номер телефона.');
                            console.log("Ошибка: некорректный номер телефона.");
                            input.value = ''; // Очищаем поле
                            return false; // Останавливаем отправку формы
                        } else {
                            var fullNumber = iti.getNumber();
                            if (!fullNumber.startsWith('+7')) {
                                fullNumber = '+7' + fullNumber; // Добавляем +7 если его нет
                            }
                            input.value = fullNumber; 
                            console.log("Корректный номер телефона: " + fullNumber);
                        }
                    });
                }

                function showErrorTooltip(input, message) {
                    // Создаем всплывающую подсказку и позиционируем её
                    var tooltip = $('<div class="error-tooltip intl-tel-input-elementor"></div>').text(message);
                    $('body').append(tooltip);
                    var offset = $(input).offset();
                    tooltip.css({
                        top: offset.top - tooltip.outerHeight() - 10, // Располагаем подсказку над полем
                        left: offset.left + ($(input).outerWidth() - tooltip.outerWidth()) / 2, // Центрируем подсказку
                        position: 'absolute'
                    }).fadeIn().css("display", "block");

                    // Автоматическое скрытие подсказки через 5 секунд
                    setTimeout(function() {
                        tooltip.fadeOut(function() {
                            tooltip.remove();
                        });
                    }, 5000);

                    console.log("Показана всплывающая подсказка: " + message);
                }

                function initPhoneAndEmailFields(container) {
                    var phoneInputSelectors = container.find('input[type="tel"], #billing_phone, #shipping_phone, #account_phone');
                    phoneInputSelectors.each(function() {
                        initializeIntlTelInput(this);
                    });

                    // Применяем маску для поля email и сохраняем плейсхолдер
                    container.find('input[type="email"]').inputmask({
                        alias: "email",
                        placeholder: "mail@domain.com",
                        clearIncomplete: false, // Маска не будет очищаться при потере фокуса
                        showMaskOnHover: false, // Показывать маску всегда
                        showMaskOnFocus: true
                    });

                    // Проверяем корректность email при потере фокуса
                    container.find('input[type="email"]').on('blur', function() {
                        var email = $(this).val();
                        var regex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+\.[a-zA-Z]{2,}$/;
                        if (!regex.test(email)) {
                            showErrorTooltip(this, 'Вы ввели недопустимые данные. Попробуйте еще раз.');
                            console.log("Ошибка: некорректный email.");
                            $(this).val(''); // Очищаем поле
                        }
                    });
                }

                // Инициализация полей на странице при её загрузке
                initPhoneAndEmailFields($(document));

                // Инициализация полей в Elementor popup при его открытии
                $(document).on('elementor/popup/show', function(event, id, instance) {
                    initPhoneAndEmailFields(instance.$element);
                });
            });
        </script>
        <?php
    }

    // Проверка полей, которые не должны быть затронуты плагином
    private function should_skip_field($field_name) {
        $skip_fields = ['_cf_lift_option', '_cf_lift_option_label', '_cf_floor_count', '_cf_lift_cost'];
        return in_array($field_name, $skip_fields);
    }
}
