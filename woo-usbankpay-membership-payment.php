<?php
/**
 * Plugin Name: Membership USBankPay
 * Description: Membership USBankPay payment gateway plugin for WooCommerce. Start accepting payments on your store using Membership
 * Version: 1.0
 * Requires at least: 4.9
 * Requires PHP: 7.0
 */
 
defined( 'ABSPATH' ) or die( 'No script allowed.' );
define( 'WC_USBANKPAY_MEMBERSHIP_PLUGIN_DIR', plugin_dir_url( __FILE__ )); 
define( 'WC_USBANKPAY_MEMBERSHIP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ )); 
define( 'WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG', 'woo_usbankpay_membership_payment'); 
define( 'WC_USBANKPAY_MEMBERSHIP_MAIN_FILE', __FILE__ );
define( 'WC_MERCHANT_VERSION',1.1);

define('WC_USBANKPAY_MEMBERSHIP_LIC_VERIFICATION_KEY', '5fa032e7775e82.56603060' );
define('WC_USBANKPAY_MEMBERSHIP_LIC_HOST_SERVER', 'https://www.merchants.money');

// Include all required files
require_once WC_USBANKPAY_MEMBERSHIP_PLUGIN_DIR_PATH . 'includes/class-woo-usbankpay-membership-payment.php';

// Write a new permalink entry on code activation
register_activation_hook( __FILE__, 'woo_usbankpay_membership_activation' );
function woo_usbankpay_membership_activation() {
}

// If the plugin is deactivated, clean the permalink structure
register_deactivation_hook( __FILE__, 'woo_usbankpay_membership_deactivation' );
function woo_usbankpay_membership_deactivation() {
}

/**
 * Add plugin action links.
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woo_usbankpay_membership_plugin_action_links' );
function woo_usbankpay_membership_plugin_action_links( $links ) 
{
    $plugin_links = array(
            '<a href="'.admin_url().'admin.php?page=wc-settings&tab=checkout&section='.WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG.'">' . esc_html__( 'Settings', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ) . '</a>',
    );
    return array_merge( $plugin_links, $links );
}

// check if WooCommerce is activated
function woo_usbankpay_membership_wc_check()
{
    if(class_exists( 'WooCommerce')) 
    {
        return true;
    }else{
        return false;
    }
}


add_action( 'plugins_loaded', 'woo_usbankpay_membership_init_payment_gateway_class' );
function woo_usbankpay_membership_init_payment_gateway_class() 
{
    if(woo_usbankpay_membership_wc_check()) 
    {
        $woo_usbankpay_membership_payment = new Woo_Usbankpay_Membership_Payment();
        
                require_once WC_USBANKPAY_MEMBERSHIP_PLUGIN_DIR_PATH . 'includes/class-woo-usbankpay-membership-gateway.php';
        
    }
}


?>