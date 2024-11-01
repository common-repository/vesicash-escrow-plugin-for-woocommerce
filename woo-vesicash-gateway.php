<?php
/**
 * Plugin Name: Vesicash Escrow Plugin for WooCommerce
 * Plugin URI: https://www.vesicash.com/plugins/woocommerce
 * Description: Take secure escrow payments on your store using Vesicash.
 * Version: 1.7.1
 * Author: vesicash
 * Author URI: https://www.vesicash.com/
 * Developer: vesicash
 * Text Domain: vesicash-gateway
 * Copyright: @ 2019 Vesicash.com
 */

// Prevent plugin from being accessed outside of WordPress.
defined('ABSPATH') or die('Pls ensure wordpress is installed');

// Ensures WooCommerce is installed and active.
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}


/*
* This action hook registers our PHP class as a WooCommerce payment gateway
*/
add_filter( 'woocommerce_payment_gateways', 'add_vesicash_gateway_class' );
function add_vesicash_gateway_class( $gateways ) {
    $gateways[] = 'WC_Vesicash_Gateway'; // your class name is here
    return $gateways;
}

/**
 * Handle plugin activation.
 */
add_action('activated_plugin', 'detect_vesicash_plugin_activated', 10, 2);
function detect_vesicash_plugin_activated($plugin, $network_activation) {
    if (strpos($plugin, 'woo-vesicash-gateway') !== false) {
        vesicash_customer_notification('activate');
    }
}

/**
 * Handle plugin deactivation.
 */
add_action('deactivated_plugin', 'detect_vesicash_plugin_deactivated', 10, 2);
function detect_vesicash_plugin_deactivated($plugin, $network_activation) {
    if (strpos($plugin, 'woo-vesicash-gateway') !== false) {
        vesicash_customer_notification('deactivate');
    }
}

/**
 * Notify Vesicash team of plugin status.
 */
function vesicash_customer_notification($event) {

    try {     
        // Get logged in user.
        $current_user = wp_get_current_user();

        // Get the user agent for this plugin.
        $user_agent = "VesicashPlugin/WooCommerce/3.8.0 WooCommerce/" . WC()->version . " WordPress/" . get_bloginfo('version') . " PHP/" . PHP_VERSION;
        
        // Build request.
        $request = array(
            'url'            => get_home_url(),
            'email'          => $current_user->user_email,
            'event'          => $event,
            'plugin_name'    => 'WooCommerce',
            'plugin_details' => $user_agent
        );
        // Send the notification to us.
        $send_notice = wp_remote_post( 'https://api.vesicash.com/v1/notifications/plugins/plugin_event', array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
        // 'sslverify' => true,
        'timeout' => 15,
        'body' => json_encode($request)
        ));

        if ( is_wp_error( $send_notice ) ) {
            wc_add_notice( __('Plugin Events error:', 'vesicash') . $send_notice->get_error_message(), 'error' );
            return false;
        }

        $send_notice = json_decode($send_notice['body']);

        if( isset($send_notice->status) && $send_notice->status == "ok" ) {
            return true;
        }
    
    } catch (Exception $e) {
        // If it fails, do other things.
    }
}

/*
 * Initialize the plugin class for the gateway.
 */
add_action('plugins_loaded', 'wc_vesicash_gateway_init', 11);
function wc_vesicash_gateway_init() {
    if ( class_exists( 'WC_Payment_Gateway' ) ) {
        require_once dirname( __FILE__ ) . '/admin/class-wc-vesicash-escrow-gateway.php';
    }
   
}

// add_action( 'wp_enqueue_scripts', 'ajax_test_enqueue_scripts' );
// function ajax_test_enqueue_scripts() {
//     wp_enqueue_script( 'test', plugins_url( '/assets/js/custom.js', __FILE__ ), array('jquery'), '1.0', true );
//     wp_localize_script( 'test', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
// }

/**
 * Vesicash Admin Settings Area
 */
add_action('admin_menu', 'vesicash_admin_area');  
function vesicash_admin_area(){    
    $page_title = 'Vesicash Escrow Admin';   
    $menu_title = 'Vesicash Orders';   
    $capability = 'manage_options';   
    $menu_slug  = 'vesicash-admin';   
    $function   = 'vesicashAdminPage';
    // $icon_url = plugin_dir_url( __FILE__ ).'/assets/img/logo1.svg';
    $icon_url   = 'https://vesicash.s3.us-east-2.amazonaws.com/images/vesicash-logo-16.svg';   
    $position   = 4;    
    add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url,$position ); 
    add_submenu_page(
        'vesicash-admin',
        'Vesicash Admin Settings',
        'Settings',
        'manage_options',
        'wc-settings&tab=checkout&section=vesicash', 
        'vesicashAdminSettingsPage'
    );
    add_submenu_page(
        'vesicash-admin',
        'Vesicash Admin Disbursements',
        'Disbursements',
        'manage_options',
        'wc-vesicash-disbursements',
        'vesicashAdminDisbursementsPage'
    );
} 


function vesicashAdminSettingsPage() {
    // require_once dirname( __FILE__ ) . '/admin/class-wc-vesicash-gateway.php';
    // $sett = new WC_Vesicash_Gateway();
    // $s2 = json_decode(json_encode($sett), true);
    // echo $s2['settings']['v_private_key'];
    // echo "---Again---";
    // echo $s2->settings->v_private_key;
}

function vesicashAdminPage() {
  global $wpdb;

  ?>
  <div class="wrap">
    <h2>All Payments</h2>
    <table class="wp-list-table widefat striped">
      <thead>
        <tr>
          <th width="25%">Title</th>
          <th width="10%">Buyer's Email</th>
          <th width="10%">Amount</th>
          <th width="15%">Status</th>
          <th width="10%">Created At</th>
          <th width="15%">Delivery Due On</th>
          <th width="15%">Action</th>
        </tr>
      </thead>
      <tbody>

        <?php

        $tranx_list = vesicash_get_business_transactions();

        foreach($tranx_list as $transaction) {

            $new_created_date = date('m/d/Y', strtotime($transaction->created_at) );
            $new_due_date = date('m/d/Y', $transaction->due_date);
            $tx_status = $transaction->status;
            $tx_id  = $transaction->transaction_id;
            $title  = isset($transaction->title) ? $transaction->title : '';
            $amount = isset($transaction->amount) ? $transaction->amount : '';
            $buyer  = isset($transaction->parties->buyer->user->email_address) ? $transaction->parties->buyer->user->email_address : '';

            echo "
                <tr>
                    <td>$title</td>
                    <td>$buyer</td>
                    <td>$transaction->currency $amount</td>
                    <td>$transaction->status</td>
                    <td>$new_created_date</td>
                    <td>$new_due_date </td>
                    <td>
                ";
                //Check the status
                vesicash_check_tranx_status($tx_status, $tx_id, $title);
  
            echo "
                    </td>
                </tr>
            ";
            }
        ?>
      </tbody>  
    </table>
  </div>
  <?php
}

function vesicashAdminDisbursementsPage() {
  global $wpdb;
    //   wp_enqueue_style( 'style', 'assets/css/vesicash-admin.css' );

  //Get the active tab from the $_GET param
  $default_tab = null;
  $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

  ?>
    <div class="wrap">
        <h2>Disburse Funds</h2>
        <p>This feature allows marketplace operators to easily disburse funds from their vesicash wallet to a customer's bank account.</p>
        <nav class="nav-tab-wrapper">
            <a href="?page=wc-vesicash-disbursements" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">New Disbursement</a>
            <a href="?page=wc-vesicash-disbursements&tab=all" class="nav-tab <?php if($tab==='all'):?>nav-tab-active<?php endif; ?>">All</a>
       </nav>
    </div>
  <?php
    $account_details = vesicash_get_business_balance();
    
    //For businesses with multiple wallets
    // $wallet_details = $account_details->wallets;
    // foreach($wallet_details as $wallet_detail) {
    //     echo "
    //         <strong><p>Your Vesicash Wallet Balance: $wallet_detail->currency $wallet_detail->available </p></strong>"
    //         ;
    // }

    echo "
        <strong><p>Your Vesicash Wallet Balance: $account_details->currency $account_details->balance </p></strong>
    ";
    ?>
        <div class="wrap">
            <div class="tab-content">
            <?php switch($tab) :
            case 'all':
                ?>
                <div class="wrap">
                    <h2>All Disbursement</h2>
                    <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                        <th width="15%">Beneficiary</th>
                        <th width="15%">Account Number</th>
                        <th width="15%">Bank</th>
                        <th width="10%">Amount</th>
                        <th width="15%">Status</th>
                        <th width="15%">Initiated On</th>
                        <th width="15%">Completed On</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php

                // echo 'All'; //Put your HTML here
                        $all_disbursements = get_disbursements();
                        foreach($all_disbursements as $disbursement) {
                            $date_initiated = $disbursement['created_at'];
                            $date_completed = $disbursement['payment_released_at'];
                            $beneficiary  = isset($disbursement['beneficiary_name']) ? $disbursement['beneficiary_name'] : '';
                            $beneficiary_account_no  = isset($disbursement['bank_account_number']) ? $disbursement['bank_account_number'] : '';
                            $beneficiary_bank  = isset($disbursement['bank_name']) ? $disbursement['bank_name'] : '';
                            $amount = isset($disbursement['amount']) ? $disbursement['amount'] : '';
                            $currency = isset($disbursement['currency']) ? $disbursement['currency'] : '';
                            $status = isset($disbursement['status']) ? $disbursement['status'] : '';
                            echo "
                                <tr>
                                    <td>$beneficiary</td>
                                    <td>$beneficiary_account_no</td>
                                    <td>$beneficiary_bank</td>
                                    <td>$currency $amount</td>
                                    <td>$status</td>
                                    <td>$date_initiated</td>
                                    <td>$date_completed </td>
                                </tr>
                                ";
                        }
                        ?>
                        </tbody>  
                        </table>
                    </div>
                    <?php
                break;
            default:
                    if($account_details->balance > 50){
                        ?>
                        <div class="wrap">
                            <p style="color:#329a4f;"">Disbursement will only be done using your wallet currency. If you wish to disburse in another currency, contact <a href="mailto:techsupport@vesicash.com">techsupport@vesicash.com.</a></p>
                            <p style="color: #A82723;">Note: Please endeavor to confirm these details with the Beneficiary because this action cannot be reversed.</p>
                            <form method="post" action="<?php echo get_admin_url()."admin.php?page=wc-vesicash-disbursements"; ?>" class="vesicash_form">
                                <p><label for="">Amount to disburse:  </label> <input type="text" placeholder="E.g 40000 (no comma please)" name="amount" required> </p>
                                <p><label for="">Beneficiary Name: </label> <input type="text" placeholder="Full bank account name" name='beneficiary_name' required> </p>
                                <p><label for="">Beneficiary Email: </label> <input type="email" placeholder="Beneficiary email address" name="email" required> </p>
                                <p><label for="">Account Number: </label> <input type="text" placeholder="Beneficiary bank account number" name="bank_account_number" required> </p>
                                <p>
                                    <label for="">Beneficiary's Bank: </label> 
                                    <select name="bank_code">
                                        <option value="044">ACCESS BANK</option>
                                        <option value="023">CITIBANK</option>
                                        <option value="050">ECOBANK NIGERIA</option>
                                        <option value="070">FIDELITY BANK</option>
                                        <option value="011">FIRST BANK OF NIGERIA</option>
                                        <option value="214">FIRST CITY MONUMENT BANK</option>
                                        <option value="085">FIRST INLAND BANK</option>
                                        <option value="058">GUARANTY TRUST BANK</option>
                                        <option value="030">HERITAGE BANK</option>
                                        <option value="069">INTERCONTINENTAL BANK</option>
                                        <option value="301">JAIZ BANK</option>
                                        <option value="082">KEYSTONE BANK</option>
                                        <option value="561">NEW PRUDENTIAL BANK</option>
                                        <option value="056">OCEANIC BANK</option>
                                        <option value="101">PROVIDUS BANK</option>
                                        <option value="076">SKYE BANK</option>
                                        <option value="221">STANBIC IBTC BANK</option>
                                        <option value="068">STANDARD CHARTERED BANK</option>
                                        <option value="232">STERLING BANK</option>
                                        <option value="032">UNION BANK OF NIGERIA</option>
                                        <option value="033">UNITED BANK FOR AFRICA</option>
                                        <option value="215">UNITY BANK</option>
                                        <option value="035">WEMA BANK</option>
                                        <option value="057">ZENITH BANK</option>
                                    </select>
                                </p>
                                <!-- <button type='submit' value='$tx_id' name='mark_as_shipped'>Order is Shipped</button> -->
                                <p><input class="button" type="submit" name="process_disbursement_form" value="Submit" /></p>
                            </form>
                        </div>
                        <?php
                    }else{
                        echo "Disbursement is only possible when your wallet balance is greater than 50.";
                    }
                break;
            endswitch; ?>
            </div>
        </div>
    <?php
    /*
    if($account_details->balance > 50){
        ?>
        <div class="wrap">
            <p style="color:#329a4f;"">Disbursement will only be done using your wallet currency</p>
            <p style="color: #A82723;">Note: Please endeavor to confirm these details with the Beneficiary because this action cannot be reversed.</p>
            <form method="post" action="<?php echo get_admin_url()."admin.php?page=wc-vesicash-disbursements"; ?>" class="vesicash_form">
                <p><label for="">Amount to disburse:  </label> <input type="text" placeholder="E.g 40000 (no comma please)" name="amount" required> </p>
                <p><label for="">Beneficiary Name: </label> <input type="text" placeholder="Full bank account name" name='beneficiary_name' required> </p>
                <p><label for="">Beneficiary Email: </label> <input type="email" placeholder="Beneficiary email address" name="email" required> </p>
                <p><label for="">Account Number: </label> <input type="text" placeholder="Beneficiary bank account number" name="bank_account_number" required> </p>
                <p>
                    <label for="">Beneficiary's Bank: </label> 
                    <select name="bank_code">
                        <option value="044">ACCESS BANK</option>
                        <option value="023">CITIBANK</option>
                        <option value="050">ECOBANK NIGERIA</option>
                        <option value="070">FIDELITY BANK</option>
                        <option value="011">FIRST BANK OF NIGERIA</option>
                        <option value="214">FIRST CITY MONUMENT BANK</option>
                        <option value="085">FIRST INLAND BANK</option>
                        <option value="058">GUARANTY TRUST BANK</option>
                        <option value="030">HERITAGE BANK</option>
                        <option value="069">INTERCONTINENTAL BANK</option>
                        <option value="301">JAIZ BANK</option>
                        <option value="082">KEYSTONE BANK</option>
                        <option value="561">NEW PRUDENTIAL BANK</option>
                        <option value="056">OCEANIC BANK</option>
                        <option value="101">PROVIDUS BANK</option>
                        <option value="076">SKYE BANK</option>
                        <option value="221">STANBIC IBTC BANK</option>
                        <option value="068">STANDARD CHARTERED BANK</option>
                        <option value="232">STERLING BANK</option>
                        <option value="032">UNION BANK OF NIGERIA</option>
                        <option value="033">UNITED BANK FOR AFRICA</option>
                        <option value="215">UNITY BANK</option>
                        <option value="035">WEMA BANK</option>
                        <option value="057">ZENITH BANK</option>
                    </select>
                </p>
                <!-- <button type='submit' value='$tx_id' name='mark_as_shipped'>Order is Shipped</button> -->
                <p><input class="button" type="submit" name="process_disbursement_form" value="Submit" /></p>
            </form>
        </div>
        <?php
    }else{
        echo "Disbursement is only possible when your wallet balance is greater than 50.";
    }*/
    
    //Send the disbursement form.
    if(isset($_POST['process_disbursement_form']))
    {
        $business_account = new WC_Vesicash_Gateway();
        $biz = json_decode(json_encode($business_account), true);

        $business_id = $biz['settings']['business_id'];
        $v_private_key = $biz['settings']['v_private_key'];
        $api_url = $biz['settings']['api_url'];

        //Get form details
        $request = array(
            'account_id'         => $business_id,
            'amount'             => $_POST['amount'],
            'currency'           => $account_details->currency,
            'debit_currency'     => $account_details->currency,
            'beneficiary_name'   => $_POST['beneficiary_name'],
            'email'              => $_POST['email'],
            'bank_account_number'=> $_POST['bank_account_number'],
            'bank_code'          => $_POST['bank_code']
        );

        $inititate_disbursement = wp_remote_post( $api_url . 'payment/disbursement/wallet/withdraw', array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'V-PRIVATE-KEY'=> $v_private_key
            ),
            // 'sslverify' => true,
            'timeout' => 15,
            'body' => json_encode($request)
        ));
    
        $disbursement = json_decode($inititate_disbursement['body']);
    
        if( isset($disbursement->status) && $disbursement->status == "ok" ) {
            $disbursement_details = $disbursement->data;
            echo '<div class="notice notice-info is-dismissible">Disbursement queued successfully!</div>';
        }else{
            echo '<div class="notice notice-info is-dismissible">Disbursement could not be completed. Please ensure the details you supplied are correct or contact our technical support team if it persists.</div>';
        }
    
        return $disbursement_details;
    }

}

//Get all disbursements by this business
function get_disbursements(){

    $business_account = new WC_Vesicash_Gateway();
    $biz = json_decode(json_encode($business_account), true);

    $business_id = $biz['settings']['business_id'];
    $v_private_key = $biz['settings']['v_private_key'];
    $api_url = $biz['settings']['api_url'];

    //Get form details
    $all_disbursement = wp_remote_post( $api_url . 'payment/disbursement/user/'. $business_id, array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
            'V-PRIVATE-KEY'=> $v_private_key
        ),
        // 'sslverify' => true,
        'timeout' => 15
    ));

    $disbursement_list = json_decode(wp_remote_retrieve_body($all_disbursement), true);

    if( isset($disbursement_list['status']) && $disbursement_list['status'] == "ok" ) {
        $all_disbursements = $disbursement_list['data'];
    }else{
        echo 'You have not initiated any disbursements ';
        return false;
    }

    return $all_disbursements;
}

// add_action( 'admin_post_process_disbursement_form', 'submit_disbursement_form' );
// function submit_disbursement_form(){

//     //Get Business details
//     $business_account = new WC_Vesicash_Gateway();
//     $biz = json_decode(json_encode($business_account), true);

//     $business_id = $biz['settings']['business_id'];
//     $v_private_key = $biz['settings']['v_private_key'];
//     $api_url = $biz['settings']['api_url'];


//     // Get the form details
//     
//      $account_id = $business_id;
        // $amount = $_POST['amount'];
        // $currency = $account->currency;
        // $debit_currency = $account->currency ;
        // $beneficiary_name = $_POST['beneficiary_name'];
        // $email = $_POST['email'];
        // $bank_account_number = $_POST['bank_account_number'];
        // $bank_code = $_POST['bank_code'] ;

//     $data = array(
//         'account_id'         => $account_id,
//         'amount'             => $amount,
//         'currency'           => $currency,
//         'debit_currency'     => $debit_currency,
//         'beneficiary_name'   => $beneficiary_name,
//         'email'              => $email,
//         'bank_account_number'=> $bank_account_number,
//         'bank_code'          => $bank_code
//     );


 
//     die;
// }

/**
 * Get the business' balance
 */
function vesicash_get_business_balance(){
    $business_account = new WC_Vesicash_Gateway();
    $biz = json_decode(json_encode($business_account), true);

    $business_id = $biz['settings']['business_id'];
    $v_private_key = $biz['settings']['v_private_key'];
    $api_url = $biz['settings']['api_url'];

    $account_details = wp_remote_post( $api_url . 'admin/account/wallet', array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
            'V-PRIVATE-KEY'=> $v_private_key
        ),
        // 'sslverify' => true,
        'timeout' => 15,
        'body' => json_encode(
                array(
                    'account_id' => $business_id
                )
            )
    ));

    $account = json_decode($account_details['body']);

    if( isset($account->status) && $account->status == "ok" ) {
        $account_details = $account->data;
    }else{
        return "No Records Found";
    }

    return $account_details;

}

/**
 * List transactions
 */
function vesicash_get_business_transactions(){

    $trnx = new WC_Vesicash_Gateway();
    $biz = json_decode(json_encode($trnx), true);

    $business_id = $biz['settings']['business_id'];
    $v_private_key = $biz['settings']['v_private_key'];
    $api_url = $biz['settings']['api_url'];

    $tranx = wp_remote_post( $api_url . 'transactions/listByUser', array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
            'V-PRIVATE-KEY'=> $v_private_key
        ),
        // 'sslverify' => true,
        'timeout' => 15,
        'body' => json_encode(
                array(
                    'account_id' => $business_id,
                    "role"       => "seller"
                )
            )
    ));

    $tranx_list = json_decode($tranx['body']);

    if( isset($tranx_list->status) && $tranx_list->status == "ok" ) {
        $tranx_list = $tranx_list->data;
    }else{
        return "No Records Found";
    }

    return $tranx_list;
}

/**
 * Check tranx status and show button if In Progress
 */
function vesicash_check_tranx_status($tx_status, $tx_id, $title){
    if(isset($_POST['mark_as_shipped'])) {
        $tx_id = $_POST['mark_as_shipped'];
        vesicash_mark_as_shipped($tx_id, $title);
    }
    if ($tx_status == 'In Progress'){
        echo "
            <form method='post'>
                <button type='submit' value='$tx_id' name='mark_as_shipped'>Order is Shipped</button>
            </form>
        ";
        return $tx_status;
    }
    
}

// function call_vesicash_api_endpoints($request, $endpoint){
//     $initial_class = new WC_Vesicash_Gateway();
//     $biz = json_decode(json_encode($initial_class), true);

//     $v_private_key = $biz['settings']['v_private_key'];
//     $api_url = $biz['settings']['api_url'];

//     $response = wp_remote_post( $api_url . $endpoint, array(
//         'method' => 'POST',
//         'headers' => array(
//             'Content-Type' => 'application/json',
//             'V-PRIVATE-KEY'=> $v_private_key,
//         ),
//         // 'sslverify' => true,
//         'body' => json_encode($request)
//     ));

//     var_dump($response); die;

//     $body = json_decode($response['body']);

//    return $body;
// }

/*
* Update order to completed on the woocommerce order page
Update order to delivered on the vesicash database 
*/
// add_action( 'wp_ajax_my_action', 'vesicash_mark_as_shipped' );
function vesicash_mark_as_shipped($tx_id, $title){
    //Update order to completed on the woocommerce order page
    $split_order_title = explode(" ",  $title);
    $order_id = $split_order_title[count($split_order_title)-1];
    $order = wc_get_order( $order_id );
    $order->update_status('completed', 'Order has been marked as delivered on Vesicash Order page.');
    
    //Mark order as shipped on vesicash.
    $initial_class = new WC_Vesicash_Gateway();
    $biz = json_decode(json_encode($initial_class), true);

    $v_private_key = $biz['settings']['v_private_key'];
    $api_url = $biz['settings']['api_url'];

    $mark_shipped = wp_remote_post( $api_url . 'transactions/delivered', array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
            'V-PRIVATE-KEY'=> $v_private_key
        ),
        'timeout' => 15,
        'body' => json_encode(
                array(
                    'transaction_id' => $tx_id
                )
            )
    ));
    $shipped = json_decode($mark_shipped['body']);

    if( isset($shipped->status) && $shipped->status == "ok" ) {
        header("Refresh:0");
        echo '<div class="notice notice-info is-dismissible">Order updated successfully!</div>';
        die;
        return true;
    }

    return false;

}