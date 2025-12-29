<?php
/**
 * Plugin Name: Elementor Ajax Product Filter for Woocommerce
 * Plugin URI: https://example.com/product-filter-plugin
 * Description: A super lightweight and fast Elementor widget for filtering WooCommerce products by categories via AJAX.
 * Version: 1.0.1
 * Author: Elod Pal
 * Author URI: https://elodpal.ro
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: product-filter-plugin
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'PRODUCT_FILTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRODUCT_FILTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// NOTE: don't include the widget class here — it extends Elementor classes
// which may not be loaded yet. Include it after confirming dependencies.

// Initialize the plugin
class ProductFilterPlugin {

    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    public function init() {
        // Check if Elementor is active
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', array( $this, 'elementor_missing_notice' ) );
            return;
        }

        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }

        // Register the widget (include the widget file when widgets are registered)
        add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ) );

        // Enqueue scripts and styles
        add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Register AJAX handlers
        add_action( 'wp_ajax_filter_products', array( $this, 'ajax_filter_products' ) );
        add_action( 'wp_ajax_nopriv_filter_products', array( $this, 'ajax_filter_products' ) );
    }

    public function register_widgets() {
        // Ensure the widget class file is loaded only when Elementor is ready
        if ( ! class_exists( 'ProductFilterWidget' ) ) {
            require_once PRODUCT_FILTER_PLUGIN_DIR . 'includes/class-product-filter-widget.php';
        }

        if ( class_exists( '\\Elementor\\Plugin' ) && method_exists( '\\Elementor\\Plugin', 'instance' ) ) {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \ProductFilterWidget() );
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'product-filter-js',
            PRODUCT_FILTER_PLUGIN_URL . 'assets/js/product-filter.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'product-filter-css',
            PRODUCT_FILTER_PLUGIN_URL . 'assets/css/product-filter.css',
            array(),
            '1.0.0'
        );

        wp_localize_script( 'product-filter-js', 'productFilterAjax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'product_filter_nonce' )
        ) );
    }

    public function ajax_filter_products() {
        check_ajax_referer( 'product_filter_nonce', 'nonce' );

        $categories = isset( $_POST['categories'] ) ? array_map( 'intval', $_POST['categories'] ) : array();
        $container_selector = sanitize_text_field( $_POST['container_selector'] );
        $template_id = isset( $_POST['template_id'] ) ? intval( $_POST['template_id'] ) : 0;

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $categories,
                    'operator' => 'IN',
                ),
            ),
        );

        $query = new WP_Query( $args );

        ob_start();
        if ( $query->have_posts() ) {
            // If an Elementor loop template ID was provided and Elementor is available,
            // render each post using the Elementor template so the loop-grid markup and
            // inline styles are preserved.
            if ( $template_id && class_exists( '\\Elementor\\Plugin' ) ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id );
                }
            } else {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    wc_get_template_part( 'content', 'product' );
                }
            }
        } else {
            echo '<p>No products found.</p>';
        }
        wp_reset_postdata();

        $html = ob_get_clean();

        wp_send_json_success( array( 'html' => $html ) );
    }

    public function elementor_missing_notice() {
        echo '<div class="notice notice-error"><p>' . __( 'Product Filter Plugin requires Elementor to be installed and activated.', 'product-filter-plugin' ) . '</p></div>';
    }

    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>' . __( 'Product Filter Plugin requires WooCommerce to be installed and activated.', 'product-filter-plugin' ) . '</p></div>';
    }
}

new ProductFilterPlugin();
