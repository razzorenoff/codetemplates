<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Elementor_Telegram_Carousel_Widget extends Widget_Base {

    public function get_name() {
        return 'telegram_carousel';
    }

    public function get_title() {
        return 'Telegram Carousel';
    }

    public function get_icon() {
        return 'eicon-carousel';
    }

    public function get_categories() {
        return ['basic'];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'plugin-name'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => __('Background Color', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .telegram-message' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'items_per_page',
            [
                'label' => __('Items per Page', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 3,
                'min' => 1,
                'max' => 10,
                'step' => 1,
                'desktop_default' => 3,
                'tablet_default' => 2,
                'mobile_default' => 1,
            ]
        );

        $this->add_control(
            'show_arrows',
            [
                'label' => __('Show Arrows', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'arrow_size',
            [
                'label' => __('Arrow Size', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 20,
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'arrow_color',
            [
                'label' => __('Arrow Color', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .telegram-carousel-arrow' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'arrow_icon_prev',
            [
                'label' => __('Previous Arrow Icon', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'arrow_icon_next',
            [
                'label' => __('Next Arrow Icon', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'author_text_color',
            [
                'label' => __('Author Text Color', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .telegram-message .author' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'message_text_color',
            [
                'label' => __('Message Text Color', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .telegram-message .message' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'border_color',
            [
                'label' => __('Border Color', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .telegram-message' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => __('Border Radius', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .telegram-message' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'padding',
            [
                'label' => __('Padding', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'selectors' => [
                    '{{WRAPPER}} .telegram-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'margin',
            [
                'label' => __('Margin', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'selectors' => [
                    '{{WRAPPER}} .telegram-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label' => __('Autoplay', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'autoplay_speed',
            [
                'label' => __('Autoplay Speed', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 3000,
                'condition' => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'loop',
            [
                'label' => __('Loop', 'plugin-name'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/telegram_messages.json';

        if (!file_exists($file_path)) {
            echo '<p>No messages found.</p>';
            return;
        }

        $messages = json_decode(file_get_contents($file_path), true);

        if (empty($messages)) {
            echo '<p>No messages found.</p>';
            return;
        }

        // Carousel HTML
        echo '<div class="telegram-carousel">';
        foreach ($messages as $message) {
            echo '<div class="telegram-message" style="flex: 0 0 calc(100% / ' . esc_attr($settings['items_per_page']) . ');">';
            if (!empty($message['author_avatar'])) {
                echo '<div class="author-avatar">';
                echo '<img src="' . esc_url($message['author_avatar']) . '" alt="Avatar" style="width: 50px; height: 50px; border-radius: 50%;">';
                echo '</div>';
            }
            echo '<div class="message-content">';
            echo '<p class="author">' . esc_html($message['author_name']) . '</p>';
            if (!empty($message['reply_to_message'])) {
                echo '<div class="reply_to_message_container">';
                echo '<p class="reply_to_message">' . esc_html(mb_substr($message['reply_to_message'], 0, 60)) . '...</p>';
                echo '</div>';
            }
            echo '<p class="message">' . esc_html($message['text']) . '</p>';
            if (!empty($message['photo'])) {
                echo '<img src="' . esc_url($message['photo']) . '" alt="Photo" style="max-width:100%;">';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    protected function _content_template() {
        ?>
        <# 
        var settings = settings;
        #>
        <div class="telegram-carousel">
            <# _.each(settings.messages, function(message) { #>
            <div class="telegram-message" style="flex: 0 0 calc(100% / {{ settings.items_per_page }});">
                <# if (message.author_avatar) { #>
                <div class="author-avatar">
                    <img src="{{ message.author_avatar }}" alt="Avatar" style="width: 50px; height: 50px; border-radius: 50%;">
                </div>
                <# } #>
                <div class="message-content">
                    <p class="author">{{ message.author_name }}</p>
                    <# if (message.reply_to_message) { #>
                    <div class="reply_to_message_container">
                        <p class="reply_to_message">{{ message.reply_to_message.slice(0, 60) }}...</p>
                    </div>
                    <# } #>
                    <p class="message">{{ message.text }}</p>
                    <# if (message.photo) { #>
                    <img src="{{ message.photo }}" alt="Photo" style="max-width:100%;">
                    <# } #>
                </div>
            </div>
            <# }); #>
        </div>
        <?php
    }
}
