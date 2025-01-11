<?php
/*
Plugin Name: ACF Relationship Order by Custom Field
Plugin URI: https://example.com
Description: Adds sorting settings (Meta Key and Sort Order) to ACF blocks directly in the Gutenberg editor.
Version: 2.0
Author: @maincoder_ru
Author URI: https://github.com/razzorenoff
License: GPLv2 or later
Text Domain: acf-relationship-order
*/

// Проверка, запущен ли WordPress
if (!defined('ABSPATH')) {
    exit;
}

// Получение списка зарегистрированных ACF полей
function maincoder_get_acf_fields() {
    $acf_fields = array();
    $field_groups = acf_get_field_groups();

    if ($field_groups) {
        foreach ($field_groups as $group) {
            $fields = acf_get_fields($group['key']);
            if ($fields) {
                foreach ($fields as $field) {
                    $acf_fields[$field['name']] = $field['label'] ? $field['label'] : $field['name'];
                }
            }
        }
    }

    return $acf_fields;
}

// Добавляем настройки к панели блока Gutenberg
add_action('acf/init', 'maincoder_add_sorting_settings_to_block');

function maincoder_add_sorting_settings_to_block() {
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group(array(
            'key' => 'group_acf_block_sorting_settings',
            'title' => 'Sorting Settings',
            'fields' => array(
                array(
                    'key' => 'field_block_sort_meta_key',
                    'label' => 'Meta Key for Sorting',
                    'name' => 'sort_meta_key',
                    'type' => 'select', // Выпадающий список
                    'choices' => maincoder_get_acf_fields(),
                    'instructions' => 'Select the ACF field to use for sorting.',
                    'required' => 0,
                    'allow_null' => true,
                ),
                array(
                    'key' => 'field_block_sort_order',
                    'label' => 'Sort Order',
                    'name' => 'sort_order',
                    'type' => 'select',
                    'choices' => array(
                        'ASC'  => 'Ascending',
                        'DESC' => 'Descending',
                    ),
                    'default_value' => 'ASC',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/events-list', // Укажите имя вашего ACF-блока
                    ),
                ),
            ),
        ));
    }
}

// Применяем настройки сортировки из блока
add_filter('acf/fields/relationship/query', 'maincoder_apply_sorting_from_block_settings', 10, 3);

function maincoder_apply_sorting_from_block_settings($args, $field, $post_id) {
    // Получаем настройки из контекста блока
    if (!empty($field['sort_meta_key']) && !empty($field['sort_order'])) {
        $args['orderby'] = 'meta_value';
        $args['meta_key'] = $field['sort_meta_key'];
        $args['meta_type'] = 'DATE';
        $args['order'] = $field['sort_order'];
    }

    return $args;
}

// Уведомление о включении плагина
function maincoder_acf_relationship_order_activation() {
    if (!function_exists('acf')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('This plugin requires the Advanced Custom Fields plugin to be installed and activated.', 'acf-relationship-order'),
            __('Activation Error', 'acf-relationship-order'),
            array('back_link' => true)
        );
    }
}
register_activation_hook(__FILE__, 'maincoder_acf_relationship_order_activation');
