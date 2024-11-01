=== Plugin Name ===

Contributors: vesicash
Plugin Name: Vesicash Escrow Plugin for WooCommerce
Plugin URI: https://www.vesicash.com/plugins/woocommerce/
Tags: wp, vesicash escrow, payment
Author URI: https://vesicash.com/
Author: vesicash
Requires WordPress Version: 4.0 or higher
Compatible up to: 6.0
Requires PHP Version: 5.6 or higher 
Tested up to: 6.0
Stable tag: 1.7.1


== Description ==

Vesicash provides secure, instant, digital escrow payment option for your website, marketplace and classifieds so that you can do your business seamlessly and not worry about chargebacks, ever.

Vesicash Escrow is important in situations where buyers and sellers do not know or trust each other and needs a way to guarantee payment security for their transaction. 

This plugin allows you to add Vesicash Escrow as a payment option on your checkout page in WooCommerce.


== Installation ==

You are expected to have created a vesicash business account (www.vesicash.com/signup) in order to successfully install this plugin.

    Installation of the Vesicash Escrow Plugin for WooCommerce can be done directly via the WordPress plugin directory or download the latest from our plugin page on WordPress.org and upload the woo-vesicash-gateway.zip file via the WordPress plugin upload page.
    Activate the plugin through the Plugins menu in WordPress.
    Once the plugin has been activated, navigate to the settings page under WooCommerce / Settings / Payments / Vesicash Escrow.
    The settings page allows you to configure the plugin to your liking.
        Make sure to copy the redirect and webhook url generated for you and paste them in corresponding fields in your Vesicash settings > Business page
        Supply your API secret key
        Supply your Business ID in your vesicash dashboard
        Make sure to select the right API Environment URL (and set the equivalent API secret key) where all orders are to be sent to. 
            For example: 
                Set the API Environment URL to sandbox (and set the Sanbox secret key) to use the sandbox environment.
                Set the API Environment URL to live (and set the Live secret key) use the live environment.
        Make sure to select your website type whether Marketplace or Ecommerce.
    Enable the Vesicash Escrow payment option on your checkout page by checking the Enable Vesicash Escrow Payment Method check box on the settings page.
    Click Save at the bottom of the screen.


== Upgrade Notice ==
All vendors who integrate the Vesicash Escrow Plugin will receive email notification in their vesicash business email whenever there is an upgrade to the Vesicash Escrow Plugin for Woocommerce.


== Changelog ==

= 1.6 =
* Added Redirect and Webhook notices.

= 1.5 =
* Added dashboard to allow marketplaces disburse funds.

= 1.4 =
* Added webhook to update order status some

= 1.3 =
* Fixed some issues with user billing at checkout.

= 1.2 =
* Added a dashboard to allow merchants perform transaction action easily.

= 1.1 =
* New Updates that fixes the checkout error because of customer country issue.

= 1.0.0 =
* First Release.

== Upgrade Notice ==
= 1.5 =
* No new feature updates

= 1.5 =
* Marketplaces can now disburse to vendors

= 1.4 =
* Orders will now be automatically updated upon successful payment on vesicash.

= 1.3 =
* User billing issues should no longer happen during checkout.

= 1.2 =
* Included new dashboard to allow merchants perform transaction action easily.

= 1.1 =
* Updates that fixes the customer country issue that prevents user from successful checkout.

= 1.0.0 =
* First Release.

== Frequently Asked Questions ==

What kind of order can be carried out using this plugin?

For now, our plugin supports only product transaction for purchase of physical goods.

Who is the buyer, seller and broker?

As a WooCommerce store owner, you are automatically the seller in the transaction wethwer your platform is a single-vendor platform or a multi-vendor/marketplace platform. In both scenarios, you will be required to complete each order from the provided Vesicash Order Page on your wordpress website or from the Vesicash Admin area in your Wordpress Dashoard. In both scenarios, the customer who checkouts using this plugin is always the buyer. 

What happens after an order has been placed?

Once an order has been placed using the Vesicash Escrow Payment option, the buyer will be instructed to complete the payment by logging into vesicash.com using the email and phone number used in placing the order. The seller is also notified about the new payment via email.

When do I get the funds?

For single-vendor/ecommerce platform type, funds for each order is released to the WooCommerce store owner within 24hours after which the store owner clicks "Order is Shipped" button on the Vesicash Order area on the wordpress dashboard. 
For Multivendor/Markteplaces platform type, funds for each order is automatically released to the WooCommerce store owner' vesicash wallet from which the store owner can then manually inititate disbursements to her vendors. A Disbursement tab has been provided on the Vesicash Order area for this purpose.

What if there is an error during checkout using the Vesicash Escrow payment option.

If an error occurs when a user places an order with the Vesicash Escrow payment option selected, open the WooCommerce admin and navigate to Status and then Logs. This plugin writes debug and error messages to these logs. They may be under fatal- or log-. If a 401 or 403 error is being returned from the Vesicash API, then there is a problem with the Vesicash Escrow credentials. If a 500 error is being returned, there is a problem on the Vesicash server. When that happens, please email techsupport@vesicash.com and it will be investigated.
