<?php

defined('ABSPATH') or die('You should not be here');


class WC_Vesicash_Gateway extends WC_Payment_Gateway {

// Define API Settings.
private $v_private_key        = "";
private $api_url        = "";

// Define Checkout Settings.
private $enable_when           = "";

// Define Transaction Settings.
// private $currency              = "";
private $escrow_charge_bearer      = "";
private $inspection_period     = "";
private $shipping_fee           = null;
private $transaction_type      = "";
private $due_date      = "";
private $platform_type      = "";
private $trans_details;
private $business_id = "";

/**
 * Class constructor, more about it in Step 3
*/
public function __construct() {

    $this->id = 'vesicash'; // payment gateway plugin ID
    $this->icon = 'https://vesicash.s3.us-east-2.amazonaws.com/company-documents/v-wordpress-logo.png'; // URL of the icon that will be displayed on checkout page near your gateway name
    // $this->icon = 'https://vesicash.ams3.digitaloceanspaces.com/images/vesicash-logo.png'; // URL of the icon that will be displayed on checkout page near your gateway name
    $this->has_fields = false; // in case you need a custom credit card form
    $this->method_title = 'Vesicash Escrow';
    $this->method_description = 'No more pay-on-delivery, reach more customers with Vesicash Escrow. Copy the Redirect and Webhook links below and paste them in Redirect and Webhook URL fields on the Vesicash Dashboard settings page.' ; // will be displayed on the options page
    $this->title = $this->get_option( 'title' );
    $this->description = $this->get_option( 'description' );
    $this->enabled = $this->get_option( 'enabled' );
    $this->enable_when           = $this->get_option('enable_when', $this->enable_when);
    
    // Values to be configured from the vesicash settings page.
    $this->api_url        = $this->get_option('api_url', $this->api_url);  
    $this->business_id        = $this->get_option('business_id', $this->business_id);  
    $this->v_private_key        = $this->get_option('v_private_key', $this->v_private_key);
    // $this->currency= $this->get_option('currency', $this->currency);
    $this->escrow_charge_bearer      = $this->get_option('escrow_charge_bearer', $this->escrow_charge_bearer);
    $this->inspection_period     = $this->get_option('inspection_period', $this->inspection_period);
    $this->shipping_fee           = $this->get_option('shipping_fee', $this->shipping_fee);
    $this->transaction_type      = $this->get_option('transaction_type', $this->transaction_type);
    $this->due_date     = $this->get_option('due_date', $this->due_date);
    $this->platform_type     = $this->get_option('platform_type', $this->platform_type);

    // add_action('update_option_platform_type', 'update_disbursement_type', 10, 2);
    // do_action('update_option_platform_type');

    // This action hook saves the settings
    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
 
    // You can also register a webhook here
    add_action( 'woocommerce_api_wc_vesicash_escrow_gateway_webhook', array( $this, 'process_webhook' ) );
    add_action( 'admin_notices', array( $this, 'vesicash_admin_notice') );
    
    // Method with all the options fields
    $this->init_form_fields();
 
    // Load the settings.
    $this->init_settings();

}
function vesicash_admin_notice() {
    
    ?>
    <div class="notice notice-info is-dismissible">
        <p><?php _e( 'Vesicash Plugin Notice : Make sure you copy the Redirect and Webhook links generated in this plugin settings page and paste them in the corresponding Redirect and Webhook URL fields in your Vesicash Dashboard settings page.' , 'textdomain'); ?></p>
    </div>
    <?php
}

/*
 * Returns setting indicating when to display Vesicash payment option on checkout page.
 *
 */
public function get_enable_when() {
    return $this->enable_when;
}


/**
 * Plugin setting options
*/
public function init_form_fields(){

    // $this->form_fields = array(
    $this->form_fields = apply_filters('wc_escrow_form_fields', array(

        'vs_redirect_url' => array(
            'title'       => __('Your Redirect URL: '),
            'type'        => 'text',
            'default'     => get_home_url().'/checkout/order-received/',
            'description' => 'Copy this value and paste it as the value of the Redirect URL field in your Vesicash Dashboard settings page.',
            'css'         => 'color:#329a4f;',
            'desc_tip' => true,
        ),
        'vs_webhook_url' => array(
            'title'       => __('Your Webhook URL:'),
            'type'        => 'text',
            'default'     => WC()->api_request_url('WC_Vesicash_Escrow_Gateway_Webhook'),
            'description' => 'Copy this value and paste it as the value of the Webhook URL field in your Vesicash Dashboard settings page.',
            'css'         => 'color:#329a4f;',
            'desc_tip' => true,
        ),
        'enabled' => array(
            'title'       => 'Enable/Disable',
            'label'       => 'Enable Vesicash Escrow',
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no'
        ),
        'enable_when' => array(
            'title' => 'Enable When',
            'type' => 'select',
            'description' => 'Determines whether to show this payment option on the checkout page. \'Enable Always\' shows the Vesicash payment option at all times. \'Enable Only When All Items can be escrowed\' shows the Vesicash payment option when all items in the cart have the \'escrowable\' custom product attribute set to \'true\'.',
            'default' => 'always',
            'desc_tip' => true,
            'options' => array(
                'always' => 'Enable Always',
                'all_items' => 'Enable Only When All Items can be Escrowed'
            )
        ),
        'title' => array(
            'title'       => 'Title',
            'type'        => 'text',
            'description' => 'This controls the title which your customer sees during checkout.',
            'default'     => 'Pay with Vesicash Escrow',
            'desc_tip'    => true,
        ),
        'description'     => array(
            'title'       => 'Description',
            'type'        => 'textarea',
            'css'         => 'height:100px; width:400px;',
            'description' => 'Payment method description that the customer will see on your checkout page.',
            'default'     => 'When you pay with Vesicash Escrow, the seller does not receive the fund untill you have received your order. When you place your order, an account will be created on Vesicash through which you will be able to complete the payment. Click on link below to continue your order.',
            'desc_tip'    => true
        ),
        'v_private_key'   => array(
            'title'       => 'Vesicash Private API Key', 
            'type'        => 'password',
            'description' => 'Enter your Vesicash Private API Key for the configured environment. If you have configured the plugin to call production, use a production API Key. If you have configured the plugin to call sandbox, use a sandbox API Key.',
           'desc_tip'     => true
        ),
        'business_id'   => array(
            'title'       => 'Vesicash Business ID', 
            'type'        => 'text',
            'description' => 'Enter your Vesicash Account ID. You can find it in the dashboard when you login to Vesicash.com.',
           'desc_tip'     => true
        ),
        'api_url' => array(
            'title'       => 'API Environment URL',
            'type'        => 'select',
            'description' => 'Select the version of the Vesicash API that you wish to use. URLs with api.Vesicash.com are for production use. URLs with sandbox.api.vesicash.com are for testing. Make sure you update the vesicash Email and vesicash API Key to match the selected environment.',
            'default'     => 'https://sandbox.api.vesicash.com/v1/',
            'desc_tip'    => true,
            'options'     => array(
                'https://api.vesicash.com/v1/' => 'https://api.vesicash.com/v1/',
                'https://sandbox.api.vesicash.com/v1/' => 'https://sandbox.api.vesicash.com/v1/'
            )
        ),
        // 'currency' => array(
        //     'title' => 'Currency',
        //     'type' => 'select',
        //     'description' => 'Select the currency you wish to use for all transactions created via this plugin.',
        //     'default' => 'NGN',
        //     'desc_tip' => true,
        //     'options' => array(
        //         'NGN' => 'NGN',
        //         'USD' => 'USD',
        //     )
        // ),
        'escrow_charge_bearer' => array(
            'title' => 'Vesicash Escrow Fee Paid By',
            'type' => 'select',
            'description' => 'Select whether the vesicash fee is to be paid by the buyer, or the seller (that is you).',
            'default' => 'seller',
            'desc_tip' => true,
            'options' => array(
                'buyer' => 'Buyer',
                'seller' => 'Seller',
            )
        ),
        'inspection_period' => array(
            'title' => 'Inspection Period',
            'type' => 'select',
            'description' => 'Expected number of days for your customers to inspect the item when they receive it. This will be communicated to the customer.',
            'default' => '1 day',
            'desc_tip' => true,
            'options' => array(
                '1' => '1 day',
                '2' => '2 days',
                '5' => '5 days',
                '7' => '7 days',
                '10' => '10 days',
                '15' => '15 days',
                '20' => '20 days',
                '30' => '30 days'
            )
        ),
        'shipping_fee' => array(
            'title' => 'Shipping Fee',
            'type' => 'number',
            'description' => 'This shipping fee would be added to orders created via this plugin. Do not enter value if you do not wish to charge your customers any shipping fee.',
            'desc_tip' => true,
        ),
        'transaction_type' => array(
            'title' => 'Transaction Type',
            'type' => 'select',
            'description' => 'Select the transaction type for all transactions created via this plugin.',
            'default' => 'product',
            'desc_tip' => true,
            'options' => array(
                'product' => 'Product'
            )
        ),
        'due_date' => array(
            'title' => 'Expected Delivery Time',
            'type' => 'select',
            'description' => 'Expected number of days it usually takes for your products to be delivered. This will be communicated to the Buyer.',
            'default' => '1 day',
            'desc_tip' => true,
            'options' => array(
                '1 day'   => '1 day',
                '2 days'  => '2 days',
                '5 days'  => '5 days',
                '7 days'  => '7 days',
                '10 days' => '10 days',
                '15 days' => '15 days',
                '20 days' => '20 days',
                '30 days' => '30 days'
            )
            ),
            'platform_type' => array(
                'title' => 'Website Type',
                'type' => 'select',
                'description' => 'Select the type or website you operate. If you select marketplace, you automatically become the seller for every order made using this plugin. This means funds would be disbursed to your Vesicash wallet and you can then disburse to your vendors using the Disbursement menu provided in this plugin.',
                'default' => 'ecommerce',
                'desc_tip' => true,
                'options' => array(
                    'ecommerce' => 'Ecommerce shop',
                    'marketplace' => 'Marketplace'
                )
            )
    ));

}

/**
 * Process the payment and return order receipt success page redirect.
 */
public function process_payment($order_id) {

    // Get the order from the given ID.
    $order = wc_get_order($order_id);
    // Get the single-vendor non-broker request for the order.
    $request = $this->get_store_order($order);
    
    // Create a draft transaction on vesicash API.
    $response = $this->call_vesicash_api($request, 'transactions/create');

    if ($response == false && !$this->trans_details) {
        return;
    } elseif( !$response && @$this->trans_details->status == "error" ) {
        
        // Return related API error to user
        $errmsg = current( current( $this->trans_details ) );

    	$this->processError($this->trans_details->data, 'Payment error:');

        //wc_add_notice( sprintf( "%s %s", __('Payment error:', 'vesicash'), $errmsg ), 'error' );
        return;
    }
    
    //Update status of the woocommerce order to processing.
    // $order->update_status('processing', 'Payment is being confirmed by Vesicash.');

    // Submit the order to Vesicash API with to update the order status.
    // $this->post_process_order($order, $response);
    
    // // Reduce the stock levels and clear the cart.
    $this->post_process_cart($order_id);



    //Redirect to appropriate checkout
    if ($this->api_url == 'https://api.vesicash.com/v1/') {
        return array(
            'result' => 'success',
            'redirect' => sprintf( "%s%s", 'https://vesicash.com/checkout/', $this->trans_details->data->transaction->transaction_id )
        );
    }
    if ($this->api_url == 'https://sandbox.api.vesicash.com/v1/') {
        return array(
            'result' => 'success',
            'redirect' => sprintf( "%s%s", 'https://sandbox.vesicash.com/checkout/', $this->trans_details->data->transaction->transaction_id )
        );
    } 

}


/**
 * Gets transaction request that will be posted to the vesicash API.
 *
 */
private function get_store_order($order) {

    $v_private_key = $this->v_private_key;
    $api_url = $this->api_url;
    $seller_id = $this->business_id;
    $buyer_id = '';
    $charge_bearer = '';
    $shipping_charge_bearer =  '';
    
    // Get properties from the order.
    $customer_email = $order->get_billing_email();
    $products = $order->get_items();

    $product_title  = get_bloginfo( 'title' ) . ' order ' . $order->get_order_number();

    // Build items array.
    $item_array = [];
    foreach ($products as $item_id => $item_data) {
        
        // Get the properties of the current item.
        $product       = wc_get_product($item_data['product_id']);
        $item_name     = $product->get_title();
        $item_quantity = wc_get_order_item_meta($item_id, '_qty', true);
        $item_total    = wc_get_order_item_meta($item_id, '_line_total', true);

        array_push($item_array, array(
            'quantity' => (int) $item_quantity,
            'amount'    => (float) $item_total,
            'title' => $item_name,
            )
        );
    }

    // Capture buyer details.
    $buyer_details = array(
        'email_address' => $customer_email,
        'firstname'    => $order->get_billing_first_name(),
        'lastname'     => $order->get_billing_last_name(),
        'phone_number'  => $order->get_billing_phone(),
        'country' => WC()->customer->get_shipping_country(),
        'seckey' => true
    );

    $reg_buyer = wp_remote_post( $api_url . 'auth/signup', array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
            'V-PRIVATE-KEY'=> $v_private_key
        ),
        // 'sslverify' => true,
        // 'timeout' => 15,
        'body' => json_encode($buyer_details)
    ));

    $reg_buyer = json_decode($reg_buyer['body']);

    if( isset($reg_buyer->status) && $reg_buyer->status == "ok" ) {
        $buyer_id = $reg_buyer->data->user->account_id;
    } else {
    	$this->processError($reg_buyer->data, 'Billing details error:');
    	return false;
    }

    if ($this->escrow_charge_bearer === 'buyer') {
        $charge_bearer = $buyer_id;
    }
    if ($this->escrow_charge_bearer === 'seller') {
        $charge_bearer = $seller_id;
    }

    if (isset($this->shipping_fee)) {
        $shipping_charge_bearer = $buyer_id;
    }else{
        $shipping_charge_bearer = $seller_id;
    }
    
    // var_dump($buyer_id, $charge_bearer); die;

    // Build parties array for the transaction.
    $parties = array(
            "buyer" => (int) $buyer_id,
            "sender" => (int) $seller_id,
            "seller" => (int) $seller_id,
            "recipient" => (int) $buyer_id,
            "charge_bearer" => (int) $charge_bearer,
            "shipping_charge_bearer" => (int)$shipping_charge_bearer
    );

    //Calculate the due date
    $today = date('m/d/Y');
    $number_of_days = $this->due_date;
    $new_due_date = date('m/d/Y', strtotime("$today + $number_of_days"));

    // Build request.
    $request = array(
        'title' => $product_title,
        'description' => $product_title,
        'type' => $this->transaction_type,
        'products' => $item_array,
        'parties' => $parties,
        // 'currency' => $this->currency,
        'currency' => get_woocommerce_currency(),
        'inspection_period' => (int) $this->inspection_period,
        'shipping_fee' => (int) $this->shipping_fee,
        'due_date' => $new_due_date,
        'return_url' => $order->get_view_order_url()
    );

    // Return populated request.
    return $request;
}

/**
 * Make a post request to the Vesicash API.
 *
 */
private function call_vesicash_api($request, $endpoint) {
    
    // Get properties relevant to the API call.
    $v_private_key = $this->v_private_key;
    $api_url = $this->api_url;
    
    $response = wp_remote_post( $api_url . $endpoint, array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
            'V-PRIVATE-KEY'=> $v_private_key,
        ),
        // 'sslverify' => true,
        'body' => json_encode($request)
    ));
    
    if ( is_wp_error( $response ) ) {
        wc_add_notice( __('Transaction Request error:', 'vesicash') . $response->get_error_message(), 'error' );
        return false;
    }

   $body = json_decode($response['body']);

   $this->trans_details = $body;

   if( isset($body->status) && $body->status == "ok" ) {
        return true;
   } else {
   	    // $this->processError($body->data, 'Transaction Error');
   }

   return false;
}

/**
 * Reduces stock levels and clears cart since order was successfully created.
 */
private function post_process_cart($order_id) {

    wc_reduce_stock_levels($order_id);
    WC()->cart->empty_cart();
}

/** TO-DO - 
 * Get results from API response to update order status in WC order.
 */
private function post_process_order($order, $pay_response) {

    // var_dump($pay_response, $order_id);die;
    return true;

}


private function processError($data, $title = 'Transaction Request error') {
	$error_data = $data;
	$error = [];
	foreach($error_data as $item) {
		$error[] = $item;
	}

	$formatted_error = '<br>'.implode('<br>', $error[0]);

    wc_add_notice( __($title, 'vesicash') . $formatted_error, 'error' );
    return false;
}



public function logWebhook($log) {
	file_put_contents("post.log", $log, FILE_APPEND);
}

/**
 * Listen for the webhook for each order.
*/
public function process_webhook(){
 
    if(( strtoupper( $_SERVER['REQUEST_METHOD'] ) != 'POST' ) || ! array_key_exists('HTTP_X_VESICASH_WEBHOOK_SECRET', $_SERVER)){
        // $this->logWebhook("Signature Header is missing");
        exit;
    }

    $body = file_get_contents("php://input");

    $v_private_key = $this->v_private_key;
    $business_id = $this->business_id;

    $headerSignature = $v_private_key.':'.$business_id;

    $local_signature = hash_hmac('sha256', $headerSignature, $v_private_key);

    if($_SERVER['HTTP_X_VESICASH_WEBHOOK_SECRET'] !== $local_signature){
        // $this->logWebhook("Signature Header Does Not Match");
        exit;
    }
    // $this->logWebhook("Authenticated");
    $response = json_decode($body);

    /** Get the response payload where the payment_status success, 
    *strip out the order_id from the title,
    *update the woocommerce order record to processing since payment was successful.
    */

    if($response->event == 'payment' && $response->data->payment_status == 'success'){

        $order_title = $response->data->transaction_title;
        $split_order_title = explode(" ",  $order_title);
        $order_id = $split_order_title[count($split_order_title)-1];
        $order = wc_get_order( $order_id );
        $order->update_status('processing', 'Payment for this order has been received by Vesicash.');
        // $this->logWebhook("Order status has been updated to Processing");
   }

}

/**
 * Processes and saves options.
 * @return bool was anything saved?
 * if marketplace, Update this business' disbursement type to wallet.
 */
public function process_admin_options() {
    $saved = parent::process_admin_options();
    $platform_type = $this->get_option('platform_type', $this->platform_type);

    $v_private_key = $this->v_private_key;
    $business_id = $this->business_id;
    $api_url = $this->api_url;

    if ($platform_type === 'marketplace'){

        $response = wp_remote_post( $api_url . 'admin/business/profile/update', array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'V-PRIVATE-KEY'=> $v_private_key,
            ),
            // 'sslverify' => true,
            'body' => json_encode(
                array(
                    'business_id' => $business_id,
                    'updates' => array(
                        'disbursement_settings' => 'wallet'
                    )
                )
            )
        ));

        $body = json_decode($response['body']);
        if( isset($body->status) && $body->status == "ok" ) {
            return true;
        } 
        return false;
    }
    if ($platform_type === 'ecommerce'){

        $response = wp_remote_post( $api_url . 'admin/business/profile/update', array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'V-PRIVATE-KEY'=> $v_private_key,
            ),
            // 'sslverify' => true,
            'body' => json_encode(
                array(
                    'business_id' => $business_id,
                    'updates' => array(
                        'disbursement_settings' => 'instant'
                    )
                )
            )
        ));

        $body = json_decode($response['body']);

        // var_dump($body);die;
        if( isset($body->status) && $body->status == "ok" ) {
            return true;
        } 
        return false;
    }

    return $saved;
}



}