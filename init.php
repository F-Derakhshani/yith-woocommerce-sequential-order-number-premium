<?php
/**
 * Plugin Name: YITH WooCommerce Sequential Order Number
 * Plugin URI:
 * Description: YITH WooCommerce Sequential Order Number allows you to create sequential order!
 * Version: 1.0.11
 * Author: Yithemes
 * Author URI: http://yithemes.com/
 * Text Domain: ywson
 * Domain Path: /languages/
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Sequential Order Number
 * @version 1.0.11
 */

/*
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly


    function yith_ywson_install_woocommerce_admin_notice() {
        ?>
        <div class="error">
            <p><?php _e( 'YITH WooCommerce Sequential Order Numbers is enabled but not effective. It requires WooCommerce in order to work.', 'ywson' ); ?></p>
        </div>
    <?php
    }


if( ! function_exists( 'yit_deactive_free_version' ) ) {
    require_once 'plugin-fw/yit-deactive-plugin.php';
}
yit_deactive_free_version( 'YWSON_FREE_INIT', plugin_basename( __FILE__ ) );


if ( !defined( 'YWSON_VERSION' ) ) {
    define( 'YWSON_VERSION', '1.0.11' );
}

if ( ! defined( 'YWSON_PREMIUM' ) ) {
    define( 'YWSON_PREMIUM', '1' );
}

if ( !defined( 'YWSON_INIT' ) ) {
    define( 'YWSON_INIT', plugin_basename( __FILE__ ) );
}

if ( !defined( 'YWSON_FILE' ) ) {
    define( 'YWSON_FILE', __FILE__ );
}

if ( !defined( 'YWSON_DIR' ) ) {
    define( 'YWSON_DIR', plugin_dir_path( __FILE__ ) );
}

if ( !defined( 'YWSON_URL' ) ) {
    define( 'YWSON_URL', plugins_url( '/', __FILE__ ) );
}

if ( !defined( 'YWSON_ASSETS_URL' ) ) {
    define( 'YWSON_ASSETS_URL', YWSON_URL . 'assets/' );
}

if ( !defined( 'YWSON_ASSETS_PATH' ) ) {
    define( 'YWSON_ASSETS_PATH', YWSON_DIR . 'assets/' );
}

if ( !defined( 'YWSON_TEMPLATE_PATH' ) ) {
    define( 'YWSON_TEMPLATE_PATH', YWSON_DIR . 'templates/' );
}

if ( !defined( 'YWSON_INC' ) ) {
    define( 'YWSON_INC', YWSON_DIR . 'includes/' );
}

if( !defined(' YWSON_SLUG' ) ){
    define( 'YWSON_SLUG', 'yith-woocommerce-sequential-order-number' );
}

if ( ! defined( 'YWSON_SECRET_KEY' ) ) {
    define( 'YWSON_SECRET_KEY', 'y19QENi2B7JVe5T4pEBI' );
}

if ( !function_exists( 'yith_plugin_registration_hook' ) ) {
    require_once 'plugin-fw/yit-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

/* Plugin Framework Version Check */
if( ! function_exists( 'yit_maybe_plugin_fw_loader' ) && file_exists( YWSON_DIR . 'plugin-fw/init.php' ) )
    require_once( YWSON_DIR . 'plugin-fw/init.php' );

yit_maybe_plugin_fw_loader( YWSON_DIR  );


if( !function_exists( 'ywson_init_plugin_configuration' ) ){

    function ywson_init_plugin_configuration() {

        $paged = 1;
        $args = apply_filters('ywson_shop_order_params', array(
                'post_type' => 'shop_order',
                'post_status' => 'any',
                'posts_per_page' => 15,
                'paged' => $paged,
                'fields' => 'ids'
            )
        );

        $order_ids = get_posts($args);

        while( count( $order_ids ) > 0 ) {


            foreach( $order_ids as $order_id ) {

                $custom_id = get_post_meta( $order_id, '_ywson_custom_number_order_complete', true );

                if ( empty( $custom_id ) ) {
                    update_post_meta( $order_id, '_ywson_custom_number_order_complete', $order_id );
                }
            }

            $paged++;

            $args[ 'paged' ] = $paged;

            $order_ids = get_posts( $args );
        }

    }
}

register_activation_hook( __FILE__, 'ywson_init_plugin_configuration' );


if ( ! function_exists( 'YITH_Sequential_Order_Number_Premium_Init' ) ) {
    /**
     * Unique access to instance of YITH_Sequential_Order_Number class
     *
     * @return YITH_WooCommerce_Sequential_Order_Number
     * @since 1.0.3
     */
    function YITH_Sequential_Order_Number_Premium_Init() {

        load_plugin_textdomain( 'ywson', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        // Load required classes and functions
        include_once(YWSON_INC.'class.yith-woocommerce-sequential-order-number.php');

        global $YITH_Sequential_Order_Number;
        $YITH_Sequential_Order_Number = YITH_WooCommerce_Sequential_Order_Number::get_instance();

    }
}

add_action('yith_wc_sequential_order_number_premium_init', 'YITH_Sequential_Order_Number_Premium_Init' );

if( !function_exists( 'yith_sequential_order_number_premium_install' ) ){
    /**
     * install sequential order number
     * @author YIThemes
     * @since 1.0.3
     */
    function yith_sequential_order_number_premium_install(){

        if( !function_exists( 'WC' ) ){
            add_action( 'admin_notices', 'yith_ywson_install_woocommerce_admin_notice' );
        }
        else
            do_action( 'yith_wc_sequential_order_number_premium_init' );

    }
}

add_action( 'plugins_loaded', 'yith_sequential_order_number_premium_install', 11 );