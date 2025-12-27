<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ProductFilterWidget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'product_filter';
    }

    public function get_title() {
        return __( 'Product Filter', 'product-filter-plugin' );
    }

    public function get_icon() {
        return 'eicon-filter';
    }

    public function get_categories() {
        return [ 'woocommerce-elements' ];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'section_filter',
            [
                'label' => __( 'Filter Settings', 'product-filter-plugin' ),
            ]
        );

        $this->add_control(
            'selected_categories',
            [
                'label' => __( 'Select Categories', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_product_categories(),
                'multiple' => true,
                'label_block' => true,
            ]
        );

        $this->add_control(
            'container_selector',
            [
                'label' => __( 'Loop Container Selector', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '.elementor-loop-container',
                'description' => __( 'CSS selector for the container holding the product loop.', 'product-filter-plugin' ),
            ]
        );

        $this->add_control(
            'title',
            [
                'label' => __( 'Widget Title', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Filter by Category', 'product-filter-plugin' ),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'show_reset_link',
            [
                'label' => __( 'Show Reset Link', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'label_on' => __( 'Yes', 'product-filter-plugin' ),
                'label_off' => __( 'No', 'product-filter-plugin' ),
            ]
        );

        $this->add_control(
            'reset_link_text',
            [
                'label' => __( 'Reset Link Text', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Reset Filter', 'product-filter-plugin' ),
                'condition' => [
                    'show_reset_link' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => __( 'Style', 'product-filter-plugin' ),
            ]
        );

        $this->add_control(
            'box_bg_color',
            [
                'label' => __( 'Box Background Color', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
            ]
        );

        $this->add_control(
            'box_border_color',
            [
                'label' => __( 'Box Border Color', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
            ]
        );

        $this->add_control(
            'box_border_radius',
            [
                'label' => __( 'Box Border Radius (px)', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 5,
                'min' => 0,
                'max' => 100,
            ]
        );

        $this->add_control(
            'box_padding',
            [
                'label' => __( 'Box Padding (px)', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 15,
                'min' => 0,
                'max' => 200,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __( 'Title Color', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => __( 'Label / Text Color', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
            ]
        );

        // Use Elementor Group Control for typography so global presets are available
        if ( class_exists( '\Elementor\Group_Control_Typography' ) ) {
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'list_typography',
                    'label' => __( 'Filters Typography', 'product-filter-plugin' ),
                    'selector' => '{{WRAPPER}} label',
                ]
            );

            // Title typography
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'title_typography',
                    'label' => __( 'Title Typography', 'product-filter-plugin' ),
                    'selector' => '{{WRAPPER}} h4',
                ]
            );

            // Reset link typography
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'reset_typography',
                    'label' => __( 'Reset Link Typography', 'product-filter-plugin' ),
                    'selector' => '{{WRAPPER}} .product-filter-reset-link',
                ]
            );
        }

        $this->add_control(
            'reset_link_color',
            [
                'label' => __( 'Reset Link Color', 'product-filter-plugin' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'condition' => [
                    'show_reset_link' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $selected_categories = $settings['selected_categories'];
        $container_selector = $settings['container_selector'];
        $title = isset( $settings['title'] ) ? $settings['title'] : __( 'Filter by Category', 'product-filter-plugin' );
        $show_reset = isset( $settings['show_reset_link'] ) && $settings['show_reset_link'] === 'yes';
        $reset_text = isset( $settings['reset_link_text'] ) ? $settings['reset_link_text'] : __( 'Reset Filter', 'product-filter-plugin' );

        if ( empty( $selected_categories ) ) {
            echo '<p>' . __( 'No categories selected.', 'product-filter-plugin' ) . '</p>';
            return;
        }

        // Build inline styles from settings
        $box_bg = ! empty( $settings['box_bg_color'] ) ? $settings['box_bg_color'] : '';
        $box_border = ! empty( $settings['box_border_color'] ) ? $settings['box_border_color'] : '';
        $box_radius = isset( $settings['box_border_radius'] ) ? intval( $settings['box_border_radius'] ) : 0;
        $box_padding = isset( $settings['box_padding'] ) ? intval( $settings['box_padding'] ) : 0;
        $title_color = ! empty( $settings['title_color'] ) ? $settings['title_color'] : '';
        $label_color = ! empty( $settings['label_color'] ) ? $settings['label_color'] : '';
        $reset_color = ! empty( $settings['reset_link_color'] ) ? $settings['reset_link_color'] : '';

        // Typography for list items is handled by Elementor Group Control (list_typography)

        $wrapper_styles = [];
        if ( $box_bg ) $wrapper_styles[] = 'background-color: ' . esc_attr( $box_bg );
        if ( $box_border ) $wrapper_styles[] = 'border: 1px solid ' . esc_attr( $box_border );
        if ( $box_radius ) $wrapper_styles[] = 'border-radius: ' . $box_radius . 'px';
        if ( $box_padding ) $wrapper_styles[] = 'padding: ' . $box_padding . 'px';

        $wrapper_style_attr = $wrapper_styles ? ' style="' . esc_attr( implode( '; ', $wrapper_styles ) ) . '"' : '';

        $title_style_attr = $title_color ? ' style="color: ' . esc_attr( $title_color ) . '"' : '';

        // Unique ID to scope injected styles and avoid specificity conflicts
        $widget_id = 'product-filter-' . uniqid();

        echo '<div id="' . esc_attr( $widget_id ) . '" class="product-filter-widget" data-container="' . esc_attr( $container_selector ) . '"' . $wrapper_style_attr . '>';
        echo '<h4' . $title_style_attr . '>' . esc_html( $title ) . '</h4>';

        // Inject scoped styles to ensure widget styling has precedence
        $scoped_rules = [];
        if ( $box_bg ) $scoped_rules[] = '#' . $widget_id . ' { background-color: ' . esc_attr( $box_bg ) . '; }';
        if ( $box_border ) $scoped_rules[] = '#' . $widget_id . ' { border: 1px solid ' . esc_attr( $box_border ) . '; }';
        if ( $box_radius ) $scoped_rules[] = '#' . $widget_id . ' { border-radius: ' . $box_radius . 'px; }';
        if ( $box_padding ) $scoped_rules[] = '#' . $widget_id . ' { padding: ' . $box_padding . 'px; }';
        if ( $title_color ) $scoped_rules[] = '#' . $widget_id . ' h4 { color: ' . esc_attr( $title_color ) . '; }';
        if ( $label_color ) $scoped_rules[] = '#' . $widget_id . ' label { color: ' . esc_attr( $label_color ) . '; }';
        if ( $reset_color ) $scoped_rules[] = '#' . $widget_id . ' .product-filter-reset-link { color: ' . esc_attr( $reset_color ) . '; }';

        // List typography is output by Elementor via the Group Control using selector '{{WRAPPER}} label'

        if ( $scoped_rules ) {
            echo '<style type="text/css">' . implode( ' ', $scoped_rules ) . '</style>';
        }

        foreach ( $selected_categories as $cat_id ) {
            $term = get_term( $cat_id, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                $label_style_attr = $label_color ? ' style="color: ' . esc_attr( $label_color ) . '"' : '';
                echo '<label' . $label_style_attr . '>';
                echo '<input type="checkbox" class="product-filter-checkbox" value="' . esc_attr( $cat_id ) . '" data-category="' . esc_attr( $cat_id ) . '"> ' . esc_html( $term->name );
                echo '</label><br>';
            }
        }

        echo '<div class="product-filter-loading" style="display:none;">' . __( 'Loading...', 'product-filter-plugin' ) . '</div>';

        if ( $show_reset ) {
            $reset_style_attr = $reset_color ? ' style="color: ' . esc_attr( $reset_color ) . '"' : '';
            echo '<div class="product-filter-reset-wrapper">';
            echo '<a href="#" class="product-filter-reset-link"' . $reset_style_attr . '>' . esc_html( $reset_text ) . '</a>';
            echo '</div>';
        }

        echo '</div>';
    }

    protected function get_product_categories() {
        $categories = get_terms( array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ) );

        $options = [];
        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            foreach ( $categories as $category ) {
                $options[ $category->term_id ] = $category->name;
            }
        }

        return $options;
    }

    public function get_script_depends() {
        return [ 'product-filter-js' ];
    }

    public function get_style_depends() {
        return [ 'product-filter-css' ];
    }
}
