<?php
// Регистрация кастомного типа записи "Membre"
function membre_register_post_type() {
    $labels = array(
        'name' => __('Участники', 'membre-grid-widget'),
        'singular_name' => __('Участник', 'membre-grid-widget'),
        'add_new' => __('Добавить нового участника', 'membre-grid-widget'),
        'add_new_item' => __('Добавить нового участника', 'membre-grid-widget'),
        'edit_item' => __('Редактировать участника', 'membre-grid-widget'),
        'new_item' => __('Новый участник', 'membre-grid-widget'),
        'view_item' => __('Просмотреть участника', 'membre-grid-widget'),
        'search_items' => __('Поиск участников', 'membre-grid-widget'),
        'not_found' => __('Участники не найдены', 'membre-grid-widget'),
        'not_found_in_trash' => __('Участники не найдены в корзине', 'membre-grid-widget'),
        'all_items' => __('Все участники', 'membre-grid-widget'),
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => array('title', 'thumbnail'),
        'show_in_rest' => true,
        'publicly_queryable' => true,
        'show_in_menu' => true,
    );
    register_post_type('membre', $args);
}
add_action('init', 'membre_register_post_type');

// Добавление кастомных полей для типа записи Membre
function membre_add_custom_meta_boxes() {
    add_meta_box(
        'membre_meta_box',
        __('Детали участника', 'membre-grid-widget'),
        'membre_meta_box_callback',
        'membre',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'membre_add_custom_meta_boxes');

function membre_meta_box_callback($post) {
    wp_nonce_field(basename(__FILE__), 'membre_nonce');
    
    $membre_position = get_post_meta($post->ID, '_membre_position', true);
    $membre_profile_link = get_post_meta($post->ID, '_membre_profile_link', true);
    
    echo '<p><label for="membre_position">' . __('Должность', 'membre-grid-widget') . '</label>';
    echo '<input type="text" id="membre_position" name="membre_position" value="' . esc_attr($membre_position) . '" class="widefat" /></p>';
    
    echo '<p><label for="membre_profile_link">' . __('Ссылка на профиль', 'membre-grid-widget') . '</label>';
    echo '<input type="url" id="membre_profile_link" name="membre_profile_link" value="' . esc_url($membre_profile_link) . '" class="widefat" /></p>';
}

function membre_save_custom_meta_data($post_id) {
    if (!isset($_POST['membre_nonce']) || !wp_verify_nonce($_POST['membre_nonce'], basename(__FILE__))) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (isset($_POST['membre_position'])) {
        update_post_meta($post_id, '_membre_position', sanitize_text_field($_POST['membre_position']));
    }
    
    if (isset($_POST['membre_profile_link'])) {
        update_post_meta($post_id, '_membre_profile_link', esc_url_raw($_POST['membre_profile_link']));
    }
}

// Добавление кастомных столбцов в список записей Membre в админке
function membre_custom_columns($columns) {
    $columns['membre_position'] = __('Должность', 'membre-grid-widget');
    return $columns;
}
add_filter('manage_membre_posts_columns', 'membre_custom_columns');

function membre_custom_columns_data($column, $post_id) {
    switch ($column) {
        case 'membre_position':
            echo esc_html(get_post_meta($post_id, '_membre_position', true));
            break;
    }
}
add_action('manage_membre_posts_custom_column', 'membre_custom_columns_data', 10, 2);

add_action('save_post', 'membre_save_custom_meta_data');
?>
