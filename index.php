<?php
/*
 * Plugin Name: WooCommerce EndPay Gateway
 * Plugin URI: https://endpay.cl
 * Description: Payment solutions.
 * Author: EndPay
 * Author URI: http://endpay.cl
 * Version: 1.0.0
 *
 
 
 /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'add_gateway_class' );
function add_gateway_class( $gateways ) {
	$gateways[] = 'WC_EndPay_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'init_gateway_class' );
function init_gateway_class() {
 
	class WC_EndPay_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
          public function __construct() {
 
            $this->id = 'endpay'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Endpay Gateway';
            $this->method_description = 'Payment Solutions'; // will be displayed on the options page
         
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );
         
            // Method with all the options fields
            $this->init_form_fields();
         
            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->instructions  = $this->get_option( 'instructions' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->commerceId = $this->testmode ? $this->get_option( 'test_commerce_id' ) : $this->get_option( 'commerce_id' );
            $this->apiKey = $this->testmode ? $this->get_option( 'test_apiKey' ) : $this->get_option( 'api_key' );
         
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
         
            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
         }
        
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
          public function init_form_fields(){
 
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable EndPay Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Credit Card',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => __( 'Pay with your credit card via our super-cool payment gateway.', 'woocommerce'),
                ),
                'instructions'       => array(
                    'title'       => __( 'Instructions', 'woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce' ),
                    'default'     => __( 'Pay with cash upon deliversy.', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'test_commerce_id' => array(
                    'title'       => 'Test Commerce ID',
                    'type'        => 'text'
                ),
                'test_apiKey' => array(
                    'title'       => 'Test API Key',
                    'type'        => 'password',
                ),
                'commerce_id' => array(
                    'title'       => 'Live Commerce ID',
                    'type'        => 'text'
                ),
                'api_key' => array(
                    'title'       => 'Live API Key',
                    'type'        => 'password'
                )
            );
        }
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
 
		
 
		}
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function payment_scripts() {
 
		
 
	 	}
 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
 
		
 
		}
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
 
            global $woocommerce;
            
            // we need it to get any order detailes
            $order = wc_get_order( $order_id );
         
            $amount = $order->get_total();

            /*
              * Array with parameters for API interaction
             */
            $data = array(
                'subject' => 'Pago woocommerce',
                'amount' => $amount,
                'return_url' => 'http://localhost:8000/?page_id=8',
                'cancel_url' => 'http://localhost:8000/?page_id=8',
                'notify_url' => 'http://localhost:8000/?page_id=8'
            );
            $args = array(        
                'headers' => array(
                    'X-Commerce-Id' => $this->commerceId,
                    'X-Api-Key' => $this->apiKey
                ),
                'body'    => $data,
            );
         
            /*
             * Your API interaction could be built with wp_remote_post()
              */
             $response = wp_remote_post( 'http://localhost:3000/api/1.0/payments/create', $args );
         
         
            if( is_wp_error( $response ) ) {
                wc_add_notice(  'Connection error.', 'error' );
                return;
            }

            $body = json_decode( $response['body'], true );
    
            // it could be different depending on your payment processor
             // if ( $body['response']['responseCode'] == 'APPROVED' ) {
    
            // we received the payment
            // $order->payment_complete();
            // $order->reduce_order_stock();
    
            // some notes to customer (replace true with false to make it private)
            // $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
    
            // Empty cart
            // $woocommerce->cart->empty_cart();
    
            // Redirect to the thank you page
            error_log( print_r($body, TRUE) );
            return array(
                'result' => 'success',
                'redirect' => $body['url']
            );
    
            /* 
             } else {
            wc_add_notice(  'Please try again.', 'error' );
            return;
           }*/
         
        }
        
 
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {
 
		
 
	 	}
 	}
}
