<?php
class Woo_Usbankpay_Membership_Gateway extends WC_Payment_Gateway {
    
    /** @var bool Whether or not logging is enabled */
    public static $log_enabled = false;
    
    /** @var WC_Logger Logger instance */
    public static $log = false;
    
    /**
     * Constructor for the gateway class
     *
     * @access public
     * @return void
     */
    public function __construct() 
    {   
        // Load the settings.
        $this->title    = 'Membership USBankPay';

        $this->id                   = WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG; 
        $this->method_title         = __( $this->title, 'woodev_payment' );  
        $this->method_description   = __( 'Membership USBankPay payment gateway plugin for WooCommerce. Start accepting payments on your store using Membership. ', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG );
        $this->has_fields           = false;
        $this->supports             = array('products');
        $this->available_currencies = array( 'USD' );
        
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title                = $this->get_option( 'title' );
        if(!is_admin()) {
            $this->order_button_text= 'Pay with '.$this->get_option( 'title' );
        }
        $this->description          = $this->get_option( 'description' );
        
        $this->merchant_environment_mu   = 'production';
        $this->merchant_environment_mu_id = 4;
        
        $this->mu_merchant_id          = $this->get_option( 'merchant_id' );        
        
        if($_SERVER['HTTP_HOST'] == '127.0.0.1')
        {
            $this->merchant_api_url_mu     = 'http://127.0.0.1/merchant_money/merchants.money/api/v1_1/api';
            $this->merchant_environment_mu_id = 2;
            $this->merchant_environment_mu = 'sandbox';
        }
        else
        {
            $this->merchant_api_url_mu     = 'https://merchants.money/api/v1_1/api';
        }
        
        $this->enabled              = $this->is_valid_for_use() ? 'yes' : 'no';
        
        // Receipt page
        add_action( 'woocommerce_receipt_'.WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG, array( $this, 'receipt_page' ) );        
        add_action( 'wp_enqueue_scripts', [ $this, 'mu_payment_scripts' ] );

        // Save settings
        if ( is_admin() ) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_payment' ) );
            add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_payment' ) );
        }	        
    }
    
    public function mu_payment_scripts()
    {
        wp_register_style( 'usbankpay_membership_styles', plugins_url( 'assets/css/usbankpay_membership-style.css', WC_USBANKPAY_MEMBERSHIP_MAIN_FILE ), [], WC_MEMBERSHIP_USBANKPAY );
        wp_enqueue_style( 'usbankpay_membership_styles' );
        
        wp_register_style( 'Bootstrap_css_usbankpay_membership', plugins_url( 'assets/css/usbankpay_membership_bootstrap.css', WC_USBANKPAY_MEMBERSHIP_MAIN_FILE ), [], WC_MEMBERSHIP_USBANKPAY );
        wp_enqueue_style( 'Bootstrap_css_usbankpay_membership' );
        
        wp_register_script('usbankpay_membership_prefix_bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js',array('jquery'), '1.0', true);
        wp_enqueue_script('usbankpay_membership_prefix_bootstrap');
        
        wp_register_script( 'usbankpay_membership_js', plugins_url( 'assets/js/usbankpay_membership.js', WC_USBANKPAY_MEMBERSHIP_MAIN_FILE ), [], WC_MEMBERSHIP_USBANKPAY );
        wp_enqueue_script( 'usbankpay_membership_js' );        
    }
    
    public function get_icon() 
    {		
        $icon = WC_MERCHANTS_MONEY_PLUGIN_DIR.'assets/images/Bank_Pay.png';        

        $icons_str = '<img class = "payment_method_usbankpay_membership_img_mu" src="'.$icon.'" />';

        return apply_filters( 'woocommerce_gateway_icon', $icons_str, $this->id );
    }
    
    public function validate_fields()
    {         
        if(empty($_POST['mu_phone_number'])) 
        {
            wc_add_notice(  'Phone Number is required!', 'error' );
            return false;
	}
        
        if(empty($_POST['mu_sms_code'])) 
        {
            wc_add_notice(  'SMS code Number is required!', 'error' );
            return false;
	}
	return true; 
    }
        
    public function payment_fields()
    {
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }
        
        $this->elements_form();
    }
    
    public function elements_form()
    {
        ?>                
        <fieldset id="show_details" class="wc-credit-card-form wc-payment-form" style="background:transparent;">  
            <b>Verify your phone number</b> - we will send you an SMS to make sure it's you
            
            <div style="padding-top: 5px;" class="merchant_money_input_mu">
                <label for="mu_phone_number">Phone Number<span style="color:#F00">*</span></label>
                <input type="text" placeholder="xxxxxxxxxx" name="mu_phone_number" id="merchant_phone_number_mu" value="" required>
                <span class="help-block" id="merchant_phone_number_mu_error" style="color:#F00; display: none;">This field is required.</span>
                <span class="help-block" id="merchant_phone_number_mu_success" style="color:green; display: none;">insert the otp below sent to your mobile number</span>
            </div>
            <div class="row">
                <div class="col-md-12">                    
                    <a href="javascript:void(0)" class="merchant_button btn btn-danger form-control" id="send_verification_code_mu" style="float: right;text-decoration: none;font-size: 16px;">Send Verification Code</a>
                </div>                                
            </div>            
            
            <div style="padding-top: 5px;" class="merchant_money_input_mu">
                <label for="mu_sms_code">SMS Code<span style="color:#F00">*</span></label>
                <input type="text" placeholder="SMS Code" name="mu_sms_code" id="merchant_sms_code_mu" value="" required>
                <span class="help-block" id="merchant_sms_code_mu_error" style="color:#F00; display: none;">This field is required.</span>
                <span class="help-block" id="merchant_sms_code_mu_success" style="color:green; display: none;">OTP verified successfully please click below pay button to continue</span>
            </div>
            
            <input type="hidden" value="" name="merchant_sms_success_id_mu" id="merchant_sms_success_id_mu"/>            
            <input type="hidden" value="<?php echo base64_encode($this->mu_merchant_id); ?>" name="mu_merchant_id_value" id="mu_merchant_id_value"/>
            
            <br/>
            <div class="row">
                <div class="col-md-12">
                    <a href="javascript:void(0)" class="merchant_button btn btn-success form-control" style="margin-top:10px; text-decoration: none;" id="verify_code_mu">Verify SMS Code</a>
                </div>                
            </div>            
            <div class="clear"></div>
        </fieldset>
        
        <fieldset id="show_success_message" style="background:transparent;display:none;">  
            <div style="padding-top: 5px;">                                
                <span class="help-block" style="color:green;">OTP verified successfully please click below pay button to continue</span>
            </div>
        </fieldset>
        <?php
    }

    public function init_form_fields() 
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Membership USBankPay Gateway', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ),
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => __( 'Title', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ),
                'default'     => __( 'Membership USBankPay', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ),
                'desc_tip'    => true
            ),
            'description' => array(
                'title'       => __( 'Description', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __( 'This controls the description which the user sees during checkout.', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ),
                'default'     => __( "Pay using your Membership" )
            ),                                   
            'merchant_id' => array(
                'title'       => __( 'Merchant ID', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ),
                'type'        => 'text',
                'placeholder' => 'Merchant ID'
            )
        );
    }
    
    public function process_payment( $order_id ) 
    {           
        global $woocommerce;
        
        if(isset($_POST['merchant_sms_success_id_mu']) && !empty($_POST['merchant_sms_success_id_mu']))
        {
            update_post_meta($order_id,'echeck_sms_success_id', $_POST['merchant_sms_success_id_mu']);
        }
        
        $order = wc_get_order( $order_id );
        
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );

    }
    
    public function receipt_page( $order_id ) 
    {
        $order = wc_get_order( $order_id );
        $echeck_sms_success_id = !empty(get_post_meta( $order->get_id(), 'echeck_sms_success_id', true ))?get_post_meta( $order->get_id(), 'echeck_sms_success_id', true ):'';
        $echeck_routing_number = !empty(get_post_meta( $order->get_id(), 'echeck_routing_number', true ))?get_post_meta( $order->get_id(), 'echeck_routing_number', true ):'';
        $echeck_account_number = !empty(get_post_meta( $order->get_id(), 'echeck_account_number', true ))?get_post_meta( $order->get_id(), 'echeck_account_number', true ):'';        
                
        if($echeck_sms_success_id != '' && $echeck_routing_number == 'sms_true' && $echeck_account_number == 'sms_true')
        {
            echo $this->merchant_sms_verification_payment( $order_id );
        }
        else
        {
            echo $this->merchant_sms_verification_payment( $order_id );
        }       
    }
    
    public function merchant_sms_verification_payment($order_id)
    {        
        global $woocommerce;        
        $error = false;

        if($order_id) {
            $order = wc_get_order( $order_id );
        }
        else {
            $error = true;
        }
        
        if(!$order) {
            $error = true;
        }

        if($error) 
        {
            wp_safe_redirect( get_site_url() );
        }
        
        $order_total = (float) $order->get_total();
        $echeck_sms_success_id = !empty(get_post_meta( $order->get_id(), 'echeck_sms_success_id', true ))?get_post_meta( $order->get_id(), 'echeck_sms_success_id', true ):'';
        
        // Redirect back if order already complete
        $returnUrl = $this->get_return_url( $order );
        if( $order->has_status('completed') || $order->has_status('processing') || $order->has_status('paid') || $order->has_status('delivered')) {
            wp_safe_redirect( $returnUrl );
        }

        // Get each accounts and check with selected ones
        $final_account = null;
        
        $data_get_balance['sms_success_id'] = $echeck_sms_success_id;
        $data_get_balance['merchant_environment_id'] = $this->merchant_environment_mu_id;
        
        $merchant_get_account = $this->getAccountBalanceMerchantSMS($data_get_balance);        

        if($merchant_get_account && isset($merchant_get_account['account'])) 
        {
            $account = $merchant_get_account['account'][0];

            if($account['CONTAINER'] == 'bank' && isset($account['bankTransferCode']) && isset($account['currentBalance'])) 
            {
                $final_account = $account;
            }
        }
        
        if($final_account) 
        {
            $current_balance = $final_account['currentBalance']['amount'];            

            if($current_balance > $order_total) 
            {
                $json = json_encode($_POST);
                if($json) {
                    update_post_meta($order_id, '_woo_merchant_payment_response_mu', $json);
                }

                update_post_meta($order_id, '_woo_merchant_billing_full_name_mu', $order->get_billing_first_name().' '.$order->get_billing_last_name());
                update_post_meta($order_id, '_woo_merchant_order_id_mu', $order_id);

                // Payment complete
                $order->payment_complete();
                // Add order note
                $order->add_order_note( sprintf( __( 'Membership USBankPay payment verification successful.', 'woocommerce' )) );

                // Send information to merchant dashboard
                $record = [];
                $record['order_id'] = $order_id;
                $record['merchant_id'] = $this->mu_merchant_id;
                $record['first_name'] = $order->get_billing_first_name();
                $record['last_name'] = $order->get_billing_last_name();
                $record['address_line_1'] = $order->get_billing_address_1();
                $record['address_line_2'] = !empty($order->get_billing_address_2())?$order->get_billing_address_2():'N/A';
                $record['city'] = $order->get_billing_city();
                $record['state'] = $order->get_billing_state();
                $record['postcode'] = $order->get_billing_postcode();
                $record['country'] = $order->get_billing_country();
                $record['email'] = $order->get_billing_email();
                $record['phone'] = $order->get_billing_phone();
                $record['order_total'] = $order->get_total();
                $record['account_id'] = isset($final_account['id']) ? $final_account['id'] : $final_account['id'];
                $record['provider_account_id'] = isset($final_account['providerAccountId']) ? $final_account['providerAccountId'] : $final_account['providerAccountId'];
                $record['account_number'] = isset($final_account['fullAccountNumber']) ? $final_account['fullAccountNumber'] : $final_account['accountNumber'];
                $record['full_account_number'] = isset($final_account['fullAccountNumberList']['paymentAccountNumber']) ? $final_account['fullAccountNumberList']['paymentAccountNumber'] : '';
                $record['routing_number'] = $final_account['bankTransferCode'][0]['id'];
                $record['merchant_website'] = get_site_url();
                $record['merchant_user_id'] = 'N/A';
                $record['merchant_user_login_name'] = 'N/A';
                $record['merchant_environment'] = $this->merchant_environment_mu;
                $record['echeck_routing_number'] = !empty(get_post_meta( $order->get_id(), 'echeck_routing_number', true ))?get_post_meta( $order->get_id(), 'echeck_routing_number', true ):'N/A';
                $record['echeck_account_number'] = !empty(get_post_meta( $order->get_id(), 'echeck_account_number', true ))?get_post_meta( $order->get_id(), 'echeck_account_number', true ):'N/A';
                $record['transaction_type'] = 'SMS';
                $record['parent_transaction'] = base64_decode($echeck_sms_success_id);
                $record['status'] = 0;
                $record['plugin_version'] = 3;

                $orderUpdate = new WC_Order($order_id);
                $orderUpdate->update_status('on-hold');
                
                // Insert record in merchant dashboard
                $status = $this->insert_into_merchant_dashboard($record);                
                if($status == FALSE) 
                {
                    $order->add_order_note( 'Failed to add record in merchant dashboard.'.json_encode($record) );
                }
                
                // Remove cart
                $woocommerce->cart->empty_cart();
                wp_safe_redirect( $returnUrl );

            }
            else 
            {
                $order->update_status('failed', sprintf( __( 'Payment failed due to insufficient balance in account.', 'woocommerce' )) );
                wp_safe_redirect( $returnUrl );
            }
        }
        else 
        {
            $order->update_status('failed', sprintf( __( 'Error in payment.', 'woocommerce' )) );
            wp_safe_redirect( $returnUrl );
        }
    }    

    /**
     * Check if this gateway is enabled and available in the user's country
     *
     * @access public
     * @return bool
     */
    function is_valid_for_use() 
    {        
        $is_available          = false;
        $is_available_currency = in_array( get_woocommerce_currency(), $this->available_currencies );

        if ( $is_available_currency && $this->mu_merchant_id && $this->get_option('enabled')=='yes') {
            $is_available = true;
        }

        return $is_available;
    }
    
    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @since 1.0.0
     */
    public function admin_options() 
    {
        if ( in_array( get_woocommerce_currency(), $this->available_currencies ) ) {
            parent::admin_options();
        } else {
        ?>
            <h3><?php _e( 'Membership USBankPay Payment', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ); ?></h3>
            <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ); ?></strong> <?php /* translators: 1: a href link 2: closing href */ echo sprintf( __( 'Choose US Dollars as your store currency in %1$sGeneral Settings%2$s to enable the Payment Gateway.', WC_USBANKPAY_MEMBERSHIP_PAYMENT_SLUG ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '">', '</a>' ); ?></p></div>
            <?php
        }
    }
    
    private function getAccountBalanceMerchantSMS($accountData) 
    {   
        $responseData = [];
        $curl = curl_init(); 
        $url = $this->merchant_api_url_mu.'/get_account_balance_merchant_sms_verification';
        
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($accountData),
            CURLOPT_HTTPHEADER => array(                
                'Content-Type: application/x-www-form-urlencoded',
                'X-Merchant-Id: ' . base64_encode($this->mu_merchant_id)
            )
        );
        
        curl_setopt_array($curl, $curl_options);
        
        $response = curl_exec($curl);
        $arrResponse = json_decode($response, true);        
        curl_close($curl);
        
        if(!empty($arrResponse)) 
        {   
            $responseData = isset($arrResponse['DATA']) ? $arrResponse['DATA'] : null;
        }
        return $responseData;
    }
    
    //This function insert data to merchant dashboard from wocommerce
    function insert_into_merchant_dashboard($record) 
    {   
        $curl = curl_init(); 
        $url = $this->merchant_api_url_mu.'/save_transaction_details';
        
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($record),
            CURLOPT_HTTPHEADER => array(                
                'Content-Type: application/x-www-form-urlencoded',
                'X-Merchant-Id: ' . base64_encode($this->mu_merchant_id)
            )
        );
        
        curl_setopt_array($curl, $curl_options);
        
        $response = curl_exec($curl);
        $arrResponse = json_decode($response, true);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);        
        curl_close($curl);
        
        if($http_code == 200 && $arrResponse['SUCCESS'] == 1)
        {
            return true;
        }
        return false;
    }
}