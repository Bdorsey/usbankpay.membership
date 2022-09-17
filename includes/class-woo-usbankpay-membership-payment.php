<?php
class Woo_Usbankpay_Membership_Payment 
{
    public function __construct() 
    {		
        $this->init();
        $this->add_actions();
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woo-usbankpay-membership-webhook.php';        
        $this->define_public_hooks();    
        $this->define_admin_hooks();
    }
        
    private function define_public_hooks() 
    {
       $woo_usbankpay_membership_webhook = new Woo_Usbankpay_Membership_Webhook();
       add_action( 'rest_api_init', [$woo_usbankpay_membership_webhook, 'register_all_routes']);
    }
        
    private function define_admin_hooks()
    {
       add_filter( 'init', array( $this, 'register_order_statuses' ) );
       add_filter( 'wc_order_statuses', array( $this, 'custom_order_status' ));  
    }
        
    function register_order_statuses() 
    {
        register_post_status( 'wc-pending_deposit', array(
        'label' => 'Pending Deposit',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop( 'Pending Deposit <span class="count">(%s)</span>', 'Pending Deposit <span class="count">(%s)</span>' )
        ));
    }
        
    function custom_order_status( $order_statuses ) 
    {
        $order_statuses['wc-pending_deposit'] = _x( 'Pending Deposit', 'Order status', 'woocommerce' );
        return $order_statuses;    
    }
        
    public function init() 
    {

    }

    public function add_actions() 
    {
        add_action( 'admin_menu', [$this, 'admin_menu'] );		

        add_action( 'init', [$this, 'payment_settings'] );
        add_action( 'woocommerce_init', [$this, 'enable_session'] );
        add_action( 'wp', [$this, 'wc_yodly_show_error_messages'] );
        add_filter( 'woocommerce_payment_gateways', [$this, 'wc_usbankpay_membership_add_payment_gateway_class'] );
    }

    /**;
     * Add admin menu
     *
     * @since    1.0.0
     */
    public function admin_menu() 
    {
        /* add new top level */
        add_menu_page(
                __('Membership USBankPay'),
                __('Membership USBankPay'),
                'manage_woocommerce',
                'woo-usbankpay-membership-gateway-orders',
                [$this, 'orders_page'],
                plugins_url('assets/images/usbankpay_membership.png', __DIR__),
                56
        );

        /* add the submenus */
        add_submenu_page(
                'woo-usbankpay-membership-gateway-orders',
                __('Orders'),
                __('Orders'),
                'manage_woocommerce',
                'woo-usbankpay-membership-gateway-orders',
                [$this, 'orders_page']
        );

        add_submenu_page(
                'woo-usbankpay-membership-gateway-orders',
                __('Settings'),
                __('Settings'),
                'manage_woocommerce',
                'woo-usbankpay-membership-gateway-settings',
                [$this, 'settings_page']
        );

    }

    /**
    * Orders Page
    *
    * @since    1.0.0
    */
    public function orders_page() 
    {
        require WC_USBANKPAY_MEMBERSHIP_PLUGIN_DIR_PATH . '/admin/includes/orders.php';
    }
    
    /**
    * Settings Page
    *
    * @since    1.0.0
    */
    public function settings_page() 
    {
		
    }

    /**
     * Settings Page redirect
     *
     * @since    1.0.0
     */
    public function payment_settings() 
    {		
        if(isset($_GET['page']) && !empty($_GET['page'])) {

            if($_GET['page'] == 'woo-usbankpay-membership-gateway-settings') {
                wp_redirect(admin_url().'admin.php?page=wc-settings&tab=checkout&section='.WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG);
                exit();
            }
        }
    }

    public function enable_session() 
    {
        if(WC()->session) {
                if ( ! WC()->session->has_session() ) {
                        WC()->session->set_customer_session_cookie( true );
                }
        }
    }

    /**
     * Show error messages if exists in session
     */
    public function wc_yodly_show_error_messages() 
    {
        if(WC()->session) {
                $error_message = WC()->session->get( 'wc_yodly_error' );
                if($error_message) {
                        wc_add_notice( $error_message, 'error' );
                        WC()->session->__unset( 'wc_yodly_error' );
                }
        }
    }

    /**
     * Add Gateway class to all payment gateway methods
     */
    public function wc_usbankpay_membership_add_payment_gateway_class( $methods )
    {
        $methods[] = 'Woo_Usbankpay_Membership_Gateway'; 
        return $methods;
    }

}