<?php
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly


if( !class_exists( 'YITH_WooCommerce_Sequential_Order_Number') ) {

    class YITH_WooCommerce_Sequential_Order_Number{

        /**Single instance of the class
         * @var YITH_WooCommerce_Sequential_Order_Number
         * #@since 1.0.0
         */
        protected static $instance;
        /**
         * @var Panel
         */
        protected $_panel;
        /**
         * @var Panel Page
         */
        protected $_panel_page              =   'yith_wc_sequential_order_number_panel';

        /**
         * @var string Premium version landing link
         */
        protected $premium_landing_url     = '';

        /**
         * @var string Plugin official documentation
         */
        protected $_official_documentation = '';

        /**
         * @var string , custom numeration postmeta name
         */
        private $_order_number_meta         =   '_ywson_custom_number_order';

        private $_order_number_complete     =   '_ywson_custom_number_order_complete';

        private $_order_free_number_meta    =   '_ywson_custom_free_number_order';
        
        private $_order_quote_number_meta    =   '_ywson_custom_quote_number_order';
        private $_order_quote_number_complete    =   'ywson_custom_quote_number_order';

        /**
         * @var string, order post type
         */
        private $_shop_order_type           =   'shop_order';


        public function __construct(){
            // Load Plugin Framework
            add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ),15 );
            //Add action links
            add_filter( 'plugin_action_links_' . plugin_basename( YWSON_DIR . '/' . basename( YWSON_FILE ) ), array( $this, 'action_links' ),5 );
            //add row meta
            add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );
            //  Add action menu
            add_action( 'admin_menu', array( $this, 'add_menu_page' ),5 );
            // register plugin to licence/update system
            add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
            add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script') );

            /*insert custom numeration order*/
           /* add_action( 'woocommerce_checkout_order_processed', array( $this, 'create_progressive_numeration' ), 5, 2 );
            add_action( 'woocommerce_process_shop_order_meta',  array( $this, 'create_progressive_numeration' ), 5, 2 );
            add_action( 'woocommerce_api_create_order',         array( $this, 'create_progressive_numeration' ), 10, 2 );
*/
            if( version_compare( WC()->version, '3.0.0', '>=' ) ) {
                add_action( 'woocommerce_checkout_create_order', array( $this, 'create_progressive_numeration_new' ),15,1 );
            }else{
                add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'create_progressive_numeration' ), 15, 2 );
            }

            add_action( 'woocommerce_process_shop_order_meta',    array( $this, 'create_progressive_numeration' ), 99, 2 );
            add_action( 'woocommerce_before_resend_order_emails', array( $this, 'create_progressive_numeration' ), 10, 1 );
            add_action( 'woocommerce_api_create_order',           array( $this, 'create_progressive_numeration' ), 10, 1 );

            //Add compatibility with YITH Account Funds
            add_action( 'woocommerce_checkout_update_order_deposit_meta', array( $this, 'create_progressive_numeration' ), 10, 1 );


            /*print custom order number*/
            add_filter( 'woocommerce_order_number', array( $this, 'get_custom_order_number' ), 10, 2);

            /*WooCommerce Subscription support*/
            add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'ywson_subscriptions_remove_renewal_order_meta_query' ), 10, 4 );
            add_action( 'woocommerce_subscriptions_renewal_order_created',    array( $this, 'ywson_subscriptions_renewal_order_created' ), 10, 4 );
            /*order tracking page*/
            add_filter('woocommerce_shortcode_order_tracking_order_id', array( $this, 'ywson_get_order_by_custom_id') );

            if ( is_admin() )
            {
                /*Enable search order by id in [woocommerce_order_tracking] shortcode*/
                add_filter( 'woocommerce_shop_order_search_fields', array( $this, 'add_custom_search_fields' ) );

                /*WooCommerce PreOrders support*/
                add_filter( 'wc_pre_orders_search_fields',array( $this, 'add_custom_search_fields' ) );

            }

            $is_quote_active = ( defined( 'YITH_YWRAQ_PREMIUM' ) && ( version_compare( YITH_YWRAQ_VERSION , '1.5.7','>=') ) );
            $quote_action = version_compare( WC()->version, '3.0.0', '>=' ) ? 'ywraq_add_order_meta' : 'ywraq_after_create_order';

            if( $is_quote_active && $this->is_quote_numeration_enabled() ) {

                add_action( 'ywraq_after_create_order', array( $this, 'create_quote_progressive_numeration'), 10, 1 );
                add_filter( 'ywson_order_date', array( $this, 'change_order_date' ), 10, 2 );
                add_filter( 'ywson_free_order_date', array( $this, 'change_order_date' ), 10, 2 );
                add_filter( 'ywraq_quote_number', array( $this, 'get_custom_order_number' ), 10 , 1 );
                add_action( 'woocommerce_checkout_order_processed', array( $this, 'change_order_number' ), 10 ,3 );
                add_filter( 'ywraq_order_metabox', array( $this, 'add_quote_number_info' ) );
            }

            //Add compatibility with One Click Checkout
            add_action( 'yith_wooc_update_order_meta', array( $this, 'create_progressive_numeration' ), 10, 1 );

            //Add compatibility with Quick Export
            add_filter( 'yith_quick_export_orders_columns_order', array( $this, 'add_export_orders_column_sequential_order' ) );
        }


        /**Insert an progressive numeration in custom order number
         * @author YITHEMES
         * @since 1.0.0
         * @param $post_id
         * @param $post
         * @use  woocommerce_checkout_order_processed, woocommerce_process_shop_order_meta, woocommerce_api_create_order
         */
        public function create_progressive_numeration( $post_id, $post=array() )
        {

            if ( is_array( $post ) || is_null( $post ) || ( 'shop_order' == $post->post_type && 'auto-draft' != $post->post_status ) || ( $this->_shop_order_type == $post->post_type ) && ( $post->post_status!='auto-draft' ) ) {

                $post_id = is_a( $post_id, 'WC_Order' ) ? yit_get_prop( $post_id,'id' ) : $post_id;
                $order = wc_get_order( $post_id );

                $quote_custom_number = yit_get_prop( $order, $this->_order_quote_number_meta );
                $req_status = yit_get_prop( $order , 'ywraq_raq_status' );
                $current_order_number = yit_get_prop($order, $this->_order_number_complete );


                //if this order is a quote or simple order
                if( ( $quote_custom_number!= '' && 'accepted' == $req_status  ) || ( '' == $req_status && $current_order_number === ''  ) ){


                    if ($this->is_free_numeration_enabled() && $this->is_order_free($order)) {
                        $this->save_custom_meta_free_order($order);
                    } else {

                        $this->save_custom_meta_order($order);
                    }

                }
            }
        }

        public function create_progressive_numeration_new( $order ){


            $quote_custom_number = yit_get_prop( $order, $this->_order_quote_number_meta );
            $req_status = yit_get_prop( $order , 'ywraq_raq_status' );
            $current_order_number = yit_get_prop($order, $this->_order_number_complete );


            //if this order is a quote or simple order
            if( ( $quote_custom_number!= '' && 'accepted' == $req_status  ) || ( '' == $req_status && $current_order_number === ''  ) ){


                if ($this->is_free_numeration_enabled() && $this->is_order_free($order)) {
                    $this->save_custom_meta_free_order($order);
                } else {

                    $this->save_custom_meta_order($order);
                }

            }
        }

        /**
         * @param int $order_id
         */
        public function create_quote_progressive_numeration( $order_id ){

            $order = wc_get_order( $order_id );
            $order_status = $order->get_status();

            if( 'ywraq-new' == $order_status && $this->is_quote_numeration_enabled() ){

                $this->save_custom_quote_meta_order( $order );
            }

            
        }

        /**Save the custom order meta
         * @author YITHEMES
         * @since 1.0.0
         * @param WC_Order $order
         */
        private function save_custom_meta_order( $order ){

            $order_date = version_compare( WC()->version,'3.0.0','>=' ) ? current_time( 'timestamp', true ) : $order->order_date;
            $order_id = yit_get_prop( $order, 'id' );
            


            $order_date =   apply_filters('ywson_order_date', $order_date, $order_id );
            $prefix     =   $this->get_prefix_suffix( $this->get_prefix_order(), $order_date );
            $next_num   =   $this->get_next_number_order();
            $suffix     =   $this->get_prefix_suffix( $this->get_suffix_order(), $order_date );

            
            $meta = array(
                $this->_order_number_meta => $next_num,
                $this->_order_number_complete =>  $prefix . $next_num . $suffix
            );

            $current_filter = current_filter();

            $filters = array(
                'woocommerce_checkout_update_order_meta',
                'woocommerce_process_shop_order_meta',
                'woocommerce_checkout_order_processed'
            );
            if( in_array( $current_filter, $filters ) ) {

                yit_save_prop( $order, $meta );
            }
            else{
                yit_set_prop( $order, $meta );
            }

            
        }

        /**
         * save the custom quote meta
         * @author YITHEMES
         * @since 1.0.6
         * @param WC_Order $order
         *
         */
        private function save_custom_quote_meta_order( $order ){

            $order_date = version_compare( WC()->version,'3.0.0','>=' ) ? current_time( 'timestamp', true ): $order->order_date;
            $order_id = yit_get_prop( $order, 'id' );
            

            $order_date =    apply_filters('ywson_quote_order_date', $order_date , $order_id );
            $prefix     =   $this->get_prefix_suffix( $this->get_prefix_quote_order(), $order_date );
            $next_num   =   $this->get_next_number_quote_order();
            $suffix     =   $this->get_prefix_suffix( $this->get_suffix_quote_order(), $order_date );

            $meta = array(
                $this->_order_quote_number_meta => $next_num,
                $this->_order_quote_number_complete =>  $prefix . $next_num . $suffix
            );


            yit_save_prop( $order, $meta );

            $this->quote_order_number = $prefix . $next_num . $suffix;
        }
        /**Save the custom free orders meta
         * @author YITHEMES
         * @since 1.0.0
         * @param WC_Order $order
         */
        private function save_custom_meta_free_order ( $order){

            $order_date = version_compare( WC()->version,'3.0.0','>=' ) ? current_time( 'timestamp', true ) : $order->order_date;
            $order_id = yit_get_prop( $order, 'id' );
            
            $order_date =  apply_filters('ywson_free_order_date', $order_date, $order_id );
            $prefix     =   $this->get_prefix_suffix( $this->get_prefix_free_order(),  $order_date );
            $next_num   =   $this->get_next_number_free_order();
            $suffix     =   $this->get_prefix_suffix( $this->get_suffix_free_order(),  $order_date );

            $meta = array(
                $this->_order_free_number_meta => $next_num,
                $this->_order_number_complete =>  $prefix . $next_num . $suffix
            );

            $current_filter = current_filter();
            $filters = array(
                'woocommerce_checkout_update_order_meta',
                'woocommerce_process_shop_order_meta',
                'woocommerce_checkout_order_processed'
            );

            if( in_array( $current_filter, $filters ) ) {
                yit_delete_prop( $order, $this->_order_number_meta );
                yit_save_prop( $order, $meta );

            }else {
                
                yit_set_prop( $order, $meta );
            }
           
            
          

        }

        /**check if this order is free
         * @author YITHEMES
         * @since 1.0.0
         * @param WC_Order $order
         * @return bool
         */
        private function is_order_free( $order ){
            
            $type_free  =    $this->get_type_free_order();
            $free       =    true;
            switch  ( $type_free ) {

                case 'order_tot' :
                    $total  =   floatval( preg_replace( '#[^\d.]#', '', $order->get_total() ) );
                    if( $total!=0 )
                        $free   =   false;
                    break;

                case 'product_ord' :

                    $product_in_order   = $order->get_items();
                    $free   =   true;
                    foreach( $product_in_order as $product ) {
                        if( $product['line_total']>0 ) {
                            $free   =   false;
                            break;
                        }
                    }
                    break;
            }

            return $free;
        }


        /**Return the next order id
         * @author YITHEMES
         * @return int
         * @use create_progressive_numeration
         */
        private function get_next_number_order() {
            $current_number = $this->get_order_number_option('ywson_start_order_number');

            $next_number    =    absint( $current_number );
            $next_number = $next_number+1;
            $this->update_order_number_option( 'ywson_start_order_number', $next_number );

            return $current_number;

        }

        /**Return the next free order id
         * @author YITHEMES
         * @return int
         * @use update_meta_free_orders
         */
        private function get_next_number_free_order() {

            $current_number = $this->get_order_number_option('ywson_start_free_order_number');

            $next_number    =    absint( $current_number );
            $next_number = $next_number+1;


            $this->update_order_number_option( 'ywson_start_free_order_number', $next_number );
            return $current_number;

        }


        /**Return the next quote number
         * @author YITHEMES
         * @since 1.0.8
         * @return int
         */
        private function get_next_number_quote_order() {
            $current_number = $this->get_order_number_option( 'ywson_start_quote_order_number' );

            $next_number    =    absint( $current_number );
            $next_number = $next_number+1;
            
            $this->update_order_number_option( 'ywson_start_quote_order_number', $next_number );
            return $current_number;

        }

        /**
         * @author YITHEMES
         * @since 1.0.8
         * @param string $option_name
         * @param  int $value
         * @return false|int
         */
        public function update_order_number_option ( $option_name , $value ){

            global $wpdb;

            $update_args = array(
                'option_value' => $value,
            );

            return  $wpdb->update( $wpdb->options, $update_args, array( 'option_name' => $option_name ) );
        }

        /**
         * @author YITHEMES
         * @since 1.0.8
         * @param string $option_name
         * @return int
         */
        public function get_order_number_option( $option_name ) {

            global $wpdb;

            $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option_name ) );

            return absint( $row->option_value );
        }

        /**Returns a formatted string
         * @author YITHEMES
         * @param $custom_string
         * @param $current_date
         * @return string
         */
        public function get_prefix_suffix( $custom_string, $current_date )  {

           $current_date = is_string( $current_date ) ? strtotime( $current_date ) : $current_date ;
;
            $custom_string  =   str_replace(
                array(  '[D]','[DD]','[M]','[MM]','[YY]','[YYYY]','[h]','[hh]','[m]','[s]' ),
                array(  date('j', $current_date ),date('d',  $current_date ),date('n',  $current_date  ),date('m',  $current_date  ),
                        date('y', $current_date ),date('Y',  $current_date  ),date('G', $current_date  ),date('H',  $current_date  ),
                        date('i', $current_date ),date('s',  $current_date ) ) ,
                $custom_string
            );

            return $custom_string;
        }

        /**display the custom formatted id order
         * @author YITHEMES
         * @param $order_number
         * @param $order
         * @return string
         */
        public function get_custom_order_number ( $order_number, $order =false ) {

            $order = wc_get_order( $order_number );
           
            $is_a_quote  = yit_get_prop( $order , 'ywraq_raq_status'  );
            
           
            if( '' !== $is_a_quote ){

                $order_number_complete=  $this->get_sequential_order_meta( $order, $this->_order_quote_number_complete );

                $order_number_complete = empty( $order_number_complete ) && isset( $this->quote_order_number ) ? $this->quote_order_number : $order_number_complete;
                if( !empty( $order_number_complete ) ){

                    $order_number = $order_number_complete;
                }
            }else {
                $order_number_complete = $this->get_sequential_order_meta( $order, $this->_order_number_complete  );
                if( $order_number_complete ) {
                    $order_number = $order_number_complete;
                }
            }

            return $order_number;
        }

        /**
         * @param WC_Order $order
         * @param string $meta_key
         * @return string
         * @author Strano Salvatore
         * @since 1.0.10
         */
        public function get_sequential_order_meta( $order, $meta_key ){


            if( version_compare( WC()->version, '3.0.0', '>=' ) ){
                $value = yit_get_prop( $order, $meta_key );
            }
            else{
                $value = get_post_meta( $order->id, $meta_key, true );
            }

           
            return $value;
        }

        /**add custom order meta to search fields
         * @author YITHEMES
         * @param $search_fields
         * @return array
         * @use woocommerce_shop_order_search_fields
         */
        public function add_custom_search_fields ( $search_fields ) {

            $search_fields[]    =   $this->_order_number_complete;
            $search_fields[]    = $this->_order_quote_number_complete;

            return $search_fields;
        }


        /**create a custom order number for subscriptions
         * @param $renewal_order
         * @param $original_order
         * @param $product_id
         * @param $new_order_role
         */
        public function ywson_subscriptions_renewal_order_created( $renewal_order, $original_order, $product_id, $new_order_role ) {
            $order_post =   get_post( $renewal_order->id );

            $this->create_progressive_numeration( $order_post->ID, $order_post );

        }

        public function ywson_subscriptions_remove_renewal_order_meta_query( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {

            $order_meta_query .= " AND meta_key NOT IN ( '$this->_order_number_complete','$this->_order_number_meta','$this->_order_free_number_meta' )";

            return $order_meta_query;
        }

        /**get the real order id by custom_id
         * @author YITHEMES
         * @since 1.0.0
         * @param $ywson_custom_number_order_complete
         * @return order_id
         */
        public function ywson_get_order_by_custom_id ( $ywson_custom_number_order_complete ) {

            $args   =   array(
                'meta_key'      =>  '_ywson_custom_number_order_complete',
                'meta_value'    =>  $ywson_custom_number_order_complete,
                'post_type'     =>  'shop_order',
                'post_status'   =>  'any',
                'fields'        =>  'ids'
            );

            $order = get_posts( $args );

             $order_id  = ! empty( $order ) ? $order : null;

            if ( $order_id [0]!== null ) {
                return $order_id[0];
            }
        }

        /**
         * add script in admin
         * @author YITHEMES
         * @since 1.0.0
         * @use admin_enqueue_scripts
         */
        public function enqueue_admin_script ()
        {
            wp_register_style('yit-sequentialorder-style', YWSON_ASSETS_URL . 'css/ywson_admin.css',array(),YWSON_VERSION );
            wp_enqueue_style('yit-sequentialorder-style');
            wp_enqueue_script( 'yit-sequentialorder-script', YWSON_ASSETS_URL . 'js/ywson_admin.js', array('jquery'),YWSON_VERSION, true );
        }


        /**Returns single instance of the class
         * @author YITHEMES
         * @since 1.0.0
         * @return YITH_WooCommerce_Sequential_Order_Number
         */
        public static function get_instance()
        {
            if( is_null( self::$instance ) ){
                self::$instance = new self( $_REQUEST );
            }
            return self::$instance;
        }


        /**
         * Add a panel under YITH Plugins tab
         *
         * @return   void
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @use     /Yit_Plugin_Panel class
         * @see      plugin-fw/lib/yit-plugin-panel.php
         */
        public function add_menu_page() {
            if ( ! empty( $this->_panel ) ) {
                return;
            }

            $admin_tabs = array(
                'general'      => __( 'General', 'ywson' ),
            );

            $args = array(
                'create_menu_page' => true,
                'parent_slug'      => '',
                'page_title'       => __( 'Sequential Order Number', 'ywson' ),
                'menu_title'       => __( 'Sequential Order Number', 'ywson' ),
                'capability'       => 'manage_options',
                'parent'           => '',
                'parent_page'      => 'yit_plugin_panel',
                'page'             => $this->_panel_page,
                'admin-tabs'       => $admin_tabs,
                'options-path'     => YWSON_DIR . '/plugin-options'
            );

            $this->_panel = new YIT_Plugin_Panel_WooCommerce( $args );

        }

        /**load plugin_fw
         * @author YITHEMES
         * @since 1.0.0
         */
        public function plugin_fw_loader() {
            if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
                global $plugin_fw_data;
                if( ! empty( $plugin_fw_data ) ){
                    $plugin_fw_file = array_shift( $plugin_fw_data );
                    require_once( $plugin_fw_file );
                }
            }
        }

        /**
         * Action Links
         *
         * add the action links to plugin admin page
         *
         * @param $links | links plugin array
         *
         * @return   mixed Array
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @return mixed
         * @use plugin_action_links_{$plugin_file_name}
         */
        public function action_links( $links ) {

            $links[] = '<a href="' . admin_url( "admin.php?page={$this->_panel_page}" ) . '">' . __( 'Settings', 'ywson' ) . '</a>';

            return $links;
        }

        /**
         * plugin_row_meta
         *
         * add the action links to plugin admin page
         *
         * @param $plugin_meta
         * @param $plugin_file
         * @param $plugin_data
         * @param $status
         *
         * @return   Array
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @use plugin_row_meta
         */
        public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
            if ( ( defined( 'YWSON_INIT' ) && ( YWSON_INIT == $plugin_file ) ) ){

                $plugin_meta[] = '<a href="' . $this->_official_documentation . '" target="_blank">' . __( 'Plugin Documentation', 'ywson' ) . '</a>';
            }

            return $plugin_meta;
        }

        /**
         * Register plugins for activation tab
         *
         * @return void
         * @since    1.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function register_plugin_for_activation() {
            if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
                require_once YWSON_DIR.'plugin-fw/licence/lib/yit-licence.php';
                require_once YWSON_DIR.'plugin-fw/licence/lib/yit-plugin-licence.php';
            }
            YIT_Plugin_Licence()->register( YWSON_INIT, YWSON_SECRET_KEY, YWSON_SLUG );
        }

        /**
         * Register plugins for update tab
         *
         * @return void
         * @since    1.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function register_plugin_for_updates() {
            if ( ! class_exists( 'YIT_Upgrade' ) ) {
                require_once(YWSON_DIR.'plugin-fw/lib/yit-upgrade.php');
            }
            YIT_Upgrade()->register( YWSON_SLUG, YWSON_INIT );
        }

        /**
         * Get the premium landing uri
         *
         * @since   1.0.0
         * @author  Andrea Grillo <andrea.grillo@yithemes.com>
         * @return  string The premium landing link
         */
        public function get_premium_landing_uri(){
            return defined( 'YITH_REFER_ID' ) ? $this->premium_landing_url . '?refer_id=' . YITH_REFER_ID : $this->premium_landing_url;
        }


        /**return current order number
         * @author YITHEMES
         * @since 1.0.0
         * @return int
         */
        private function get_current_order_number() {
            return get_option( 'ywson_start_order_number', 1);
        }

        /**set next order number
         * @author YITHEMES
         * @since 1.0.0
         * @param $next_number
         */
        private function set_current_order_number( $next_number ) {
            update_option( 'ywson_start_order_number', $next_number );
        }

        /**get prefix order
         * @author YITHEMES
         * @since 1.0.0
         * @return string
         */
        private function get_prefix_order() {
            return get_option( 'ywson_order_prefix','' );
        }

        /**get suffix order
         * @author YITHEMES
         * @since 1.0.0
         * @return string
         */
        private function get_suffix_order() {
            return get_option( 'ywson_order_suffix','' );
        }

        /**return current free order number
         * @author YITHEMES
         * @since 1.0.0
         * @return int
         */
        private function get_current_free_order_number() {
            return get_option( 'ywson_start_free_order_number', 1 );
        }

        /**set next free order number
         * @author YITHEMES
         * @since 1.0.0
         * @param $next_number
         */
        private function set_current_free_order_number( $next_number ){
            update_option( 'ywson_start_free_order_number', $next_number );
        }

        /**get prefix free order
         * @author YITHEMES
         * @since 1.0.0
         * @return string
         */
        private function get_prefix_free_order() {
            return get_option('ywson_free_order_prefix','');
        }

        /**get suffix free order
         * @author YITHEMES
         * @since 1.0.0
         * @return string
         */
        private function get_suffix_free_order() {
            return get_option('ywson_free_order_suffix','');
        }

        /**Get the current "type" of free orders
         * @author YITHEMES
         * @since 1.0.0
         * @return string
         */
        private function get_type_free_order() {
            return get_option('ywson_free_order_type');
        }

        /**check if free numeration option is enabled
         * @author YITHEMES
         * @since 1.0.0
         * @return boolean
         */
        private function is_free_numeration_enabled() {
            return get_option('ywson_check_custom_free_order', 'no')=='yes' ? true : false;
        }

        /**return current quote order number
         * @author YITHEMES
         * @since 1.0.6
         * @return int
         */
        private function get_current_quote_order_number() {
            return get_option( 'ywson_start_quote_order_number', 1 );
        }

        /**set next quote order number
         * @author YITHEMES
         * @since 1.0.6
         * @param $next_number
         */
        private function set_current_quote_order_number( $next_number ){
            update_option( 'ywson_start_quote_order_number', $next_number );
        }

        /**get prefix quote order
         * @author YITHEMES
         * @since 1.0.6
         * @return string
         */
        private function get_prefix_quote_order() {
            return get_option('ywson_quote_order_prefix','');
        }

        /**get suffix quote order
         * @author YITHEMES
         * @since 1.0.6
         * @return string
         */
        private function get_suffix_quote_order() {
            return get_option('ywson_quote_order_suffix','');
        }

        private function  is_quote_numeration_enabled(){

            return get_option( 'ywson_check_custom_quote_order', 'no' ) == 'yes';
        }

        /**
         * if a quote change to order set the new order date
         * @author YITHEMES
         * @since 1.0.6
         * @param $order_date
         * @param $order_id
         * @return bool|string
         */
        public function change_order_date( $order_date, $order_id ){
            
            $order = wc_get_order( $order_id );
            $is_req_a_quote = yit_get_prop( $order, 'ywraq_raq' );

            if( $is_req_a_quote == 'yes' ){

                $date_format = get_option( 'date_format' );

                $order_date = date( $date_format, current_time( 'timestamp') );
            }
            return $order_date;

        }

        /**
         * add quote number info into metabox
         * @author YITHEMES
         * @since 1.0.8
         * @param array $quote_metabox
         * @return array
         */
        public function add_quote_number_info( $quote_metabox ){

          $post_id = !empty( $_REQUEST['post'] ) ? $_REQUEST['post'] : false ;


            if( $post_id && get_post_type( $post_id ) == 'shop_order' ){

                $order_number_complete= get_post_meta( $post_id, $this->_order_quote_number_complete, true);

                if( !empty( $order_number_complete ) ){
                    
                    $quote_number_info = array(
                        'ywraq_sequential_quote_number' => array(
                            'desc'  => sprintf('%s: <span>#%s</span>', __('Sequential Quote Number', 'ywson' ), $order_number_complete ),
                            'type'  => 'title'
                        )
                    );

                    array_unshift( $quote_metabox,  $quote_number_info['ywraq_sequential_quote_number']   );
                    
                    
                }
            }

            return $quote_metabox;
        }

        public function add_export_orders_column_sequential_order( $columns ){

            $new_colum = array( 'ywson_custom_number_order_complete' );
            $columns = array_merge( $new_colum, $columns );
            return $columns;
        }


        public function change_order_number( $oder_id, $posted_data, $order ){

                $this->create_progressive_numeration_new( $order );

        }

    }
}