<?php
/*
Plugin Name: Парсинг канала Телеграмм
Description: Плагин для парсинга публикаций из канала Телеграмм и отображения их в Elementor.
Version: 1.0
Author: Ваше имя
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add settings to the admin menu
add_action('admin_menu', 'telegram_channel_parser_menu');
function telegram_channel_parser_menu() {
    add_options_page('Настройки Телеграмм', 'Настройки Телеграмм', 'manage_options', 'telegram-channel-parser', 'telegram_channel_parser_options');
}

function telegram_channel_parser_options() {
    ?>
    <div class="wrap">
        <h1>Настройки Телеграмм</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('telegram_channel_parser_options_group');
            do_settings_sections('telegram-channel-parser');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register and define the settings
add_action('admin_init', 'telegram_channel_parser_settings');
function telegram_channel_parser_settings() {
    register_setting('telegram_channel_parser_options_group', 'telegram_channel_name');
    register_setting('telegram_channel_parser_options_group', 'telegram_bot_token');

    add_settings_section('telegram_channel_parser_main', 'Основные настройки', null, 'telegram-channel-parser');

    add_settings_field('telegram_channel_name', 'Имя канала Телеграмм', 'telegram_channel_name_render', 'telegram-channel-parser', 'telegram_channel_parser_main');
    add_settings_field('telegram_bot_token', 'Токен бота Телеграмм', 'telegram_bot_token_render', 'telegram-channel-parser', 'telegram_channel_parser_main');
}

function telegram_channel_name_render() {
    ?>
    <input type="text" name="telegram_channel_name" value="<?php echo esc_attr(get_option('telegram_channel_name')); ?>" />
    <?php
}

function telegram_bot_token_render() {
    ?>
    <input type="text" name="telegram_bot_token" value="<?php echo esc_attr(get_option('telegram_bot_token')); ?>" />
    <?php
}

// Schedule cron job on activation
register_activation_hook(__FILE__, 'telegram_channel_parser_activation');
function telegram_channel_parser_activation() {
    if (!wp_next_scheduled('telegram_channel_parser_cron_event')) {
        wp_schedule_event(time(), '5min', 'telegram_channel_parser_cron_event');
    }
}

// Clear scheduled cron job on deactivation
register_deactivation_hook(__FILE__, 'telegram_channel_parser_deactivation');
function telegram_channel_parser_deactivation() {
    wp_clear_scheduled_hook('telegram_channel_parser_cron_event');
}

// Custom cron schedule
add_filter('cron_schedules', 'telegram_channel_parser_cron_schedules');
function telegram_channel_parser_cron_schedules($schedules) {
    $schedules['5min'] = array(
        'interval' => 300,
        'display' => __('Every 5 Minutes')
    );
    return $schedules;
}

// Hook function to our cron event
add_action('telegram_channel_parser_cron_event', 'telegram_channel_parser_fetch_messages');
function telegram_channel_parser_fetch_messages() {
    $channel_name = get_option('telegram_channel_name');
    $bot_token = get_option('telegram_bot_token');

    if (empty($channel_name) || empty($bot_token)) {
        return;
    }

    // Fetch messages from Telegram API
    $api_url = "https://api.telegram.org/bot{$bot_token}/getUpdates";
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['result'])) {
        return;
    }

    $messages = array_reverse(array_slice($data['result'], -10)); // Fetch last 10 messages and reverse order
    $parsed_messages = array();

    foreach ($messages as $message) {
        if (isset($message['message'])) {
            $message_data = $message['message'];
            $parsed_message = array(
                'author_name' => $message_data['from']['first_name'] . ' ' . $message_data['from']['last_name'],
                'author_avatar' => isset($message_data['from']['photo']) ? "https://api.telegram.org/file/bot{$bot_token}/" . $message_data['from']['photo']['file_id'] : '',
                'text' => $message_data['text'],
                'date' => date('Y-m-d H:i:s', $message_data['date']),
                'photo' => isset($message_data['photo']) ? "https://api.telegram.org/file/bot{$bot_token}/" . end($message_data['photo'])['file_id'] : '',
                'reply_to_message' => isset($message_data['reply_to_message']) ? $message_data['reply_to_message']['text'] : ''
            );
            $parsed_messages[] = $parsed_message;
        }
    }

    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/telegram_messages.json';
    file_put_contents($file_path, json_encode($parsed_messages, JSON_PRETTY_PRINT));
}

// Elementor widget for Telegram messages
add_action('elementor/widgets/widgets_registered', 'register_telegram_carousel_widget');
function register_telegram_carousel_widget($widgets_manager) {
    require_once(__DIR__ . '/widgets/telegram-carousel-widget.php');
    $widgets_manager->register(new \Elementor_Telegram_Carousel_Widget());
}

// Enqueue Slick Carousel and custom styles
add_action('wp_enqueue_scripts', 'enqueue_slick_carousel');
function enqueue_slick_carousel() {
    wp_enqueue_style('slick-carousel', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
    wp_enqueue_style('slick-carousel-theme', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
    wp_enqueue_script('slick-carousel', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], '1.8.1', true);
    wp_enqueue_style('telegram-carousel-style', plugins_url('/assets/style.css', __FILE__));
}

// Ensure carousel is initialized after page load
add_action('wp_footer', function() {
    ?>
    <script>
        function initializeTelegramCarousel() {
            var $carousel = jQuery('.telegram-carousel');
            if ($carousel.length) {
                $carousel.not('.slick-initialized').slick({
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    autoplay: true,
                    autoplaySpeed: 3000,
                    infinite: true,
                    arrows: true,
                    prevArrow: '<button type="button" class="telegram-carousel-arrow slick-prev"></button>',
                    nextArrow: '<button type="button" class="telegram-carousel-arrow slick-next"></button>',
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 2
                            }
                        },
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 1
                            }
                        }
                    ]
                });
            }
        }

        jQuery(document).ready(function() {
            initializeTelegramCarousel();
        });

        jQuery(document).on('elementor/popup/show', function() {
            initializeTelegramCarousel();
        });

        jQuery(document).on('elementor/frontend/init', function() {
            elementorFrontend.hooks.addAction('frontend/element_ready/widget', function() {
                initializeTelegramCarousel();
            });
        });
    </script>
    <?php
});
?>
