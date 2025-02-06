<?php
namespace Elementor;

class Membre_Grid_Widget extends Widget_Base {

    public function get_name() {
        return 'membre_grid';
    }

    public function get_title() {
        return __('Membre Grid', 'membre-grid-widget');
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {
        // Content controls for grid layout
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Настройки сетки', 'membre-grid-widget'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => __('Количество колонок', 'membre-grid-widget'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    '2' => __('2 колонки', 'membre-grid-widget'),
                    '3' => __('3 колонки', 'membre-grid-widget'),
                    '4' => __('4 колонки', 'membre-grid-widget'),
                ],
                'default' => '3',
            ]
        );

        $this->add_control(
            'show_position',
            [
                'label' => __('Показать должность', 'membre-grid-widget'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'profile_link_text',
            [
                'label' => __('Текст ссылки профиля', 'membre-grid-widget'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('View Profile', 'membre-grid-widget'),
                'placeholder' => __('Введите текст', 'membre-grid-widget'),
            ]
        );

        $this->end_controls_section();

        // Style controls for text (Name & Position)
        $this->start_controls_section(
            'text_style_section',
            [
                'label' => __('Стили текста', 'membre-grid-widget'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Name typography & color
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'name_typography',
                'label' => __('Типография имени', 'membre-grid-widget'),
                'selector' => '{{WRAPPER}} .membre-name',
            ]
        );

        $this->add_control(
            'name_color',
            [
                'label' => __('Цвет имени', 'membre-grid-widget'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .membre-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Position typography & color
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'position_typography',
                'label' => __('Типография должности', 'membre-grid-widget'),
                'selector' => '{{WRAPPER}} .membre-position',
                'condition' => [
                    'show_position' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'position_color',
            [
                'label' => __('Цвет должности', 'membre-grid-widget'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .membre-position' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_position' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section for Image
        $this->start_controls_section(
            'image_style_section',
            [
                'label' => __('Стили изображения', 'membre-grid-widget'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label' => __('Размер изображения', 'membre-grid-widget'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 300,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .membre-image img' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'label' => __('Граница изображения', 'membre-grid-widget'),
                'selector' => '{{WRAPPER}} .membre-image img',
            ]
        );

        $this->add_control(
            'image_border_radius',
            [
                'label' => __('Радиус границы', 'membre-grid-widget'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .membre-image img' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Profile Link/Button style
        $this->start_controls_section(
            'profile_link_style_section',
            [
                'label' => __('Стили ссылки профиля', 'membre-grid-widget'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'profile_link_typography',
                'label' => __('Типография текста ссылки', 'membre-grid-widget'),
                'selector' => '{{WRAPPER}} .membre-profile-link',
            ]
        );

        $this->add_control(
            'profile_link_color',
            [
                'label' => __('Цвет текста ссылки', 'membre-grid-widget'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .membre-profile-link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'profile_link_background',
                'label' => __('Фон ссылки', 'membre-grid-widget'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .membre-profile-link',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $args = array(
            'post_type' => 'membre',
            'posts_per_page' => -1,
        );
        $query = new \WP_Query( $args );

        echo '<div class="membre-grid" style="grid-template-columns: repeat(' . esc_attr($settings['columns']) . ', 1fr);">';
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $position = get_post_meta( get_the_ID(), '_membre_position', true );
                $profile_link = get_post_meta( get_the_ID(), '_membre_profile_link', true );
                
                echo '<div class="membre-item">';
                if ( has_post_thumbnail() ) {
                    echo '<div class="membre-image"><a href="' . esc_url($profile_link) . '">' . get_the_post_thumbnail( get_the_ID(), 'thumbnail' ) . '</a></div>';
                }
                echo '<h3 class="membre-name">' . get_the_title() . '</h3>';
                
                if ($settings['show_position'] === 'yes') {
                    echo '<p class="membre-position">' . esc_html($position) . '</p>';
                }

                echo '<a href="' . esc_url($profile_link) . '" class="membre-profile-link">' . esc_html($settings['profile_link_text']) . '</a>';
                echo '</div>';
            }
        } else {
            echo '<p>' . __('No Membres found', 'membre-grid-widget') . '</p>';
        }
        
        echo '</div>';
        
        wp_reset_postdata();
    }
}
?>
