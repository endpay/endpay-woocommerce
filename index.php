<?php
/**
 * @package endpay
 */
 /*
 * Plugin Name: EndPay
 * Plugin URI: https://endpay.cl
 * Description: Payment solutions.
 * Author: EndPay
 * Author URI: http://endpay.cl
 * Version: 1.0.0
 * License: BSD-3 
 * Text Domain: endpay
 
 /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'add_gateway_class' );
function add_gateway_class( $gateways ) {
	$gateways[] = 'WC_EndPay_Gateway'; // your class name is here
	return $gateways;
}

/**
 * Reemplaza el ícono
 */
function endpay_replace_icon( $icon_html, $id ) {
    if($id === 'endpay'){
        return '<img src="' . plugins_url( 'assets/webpay.png', __FILE__ ) . '" width="200" >'; 
    }
    return $icon_html;
}
add_filter( 'woocommerce_gateway_icon', 'endpay_replace_icon', 10, 2 );

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
            $this->icon = apply_filters( 'woocommerce_gateway_icon', 'endpay_replace_icon', 10, 2); // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Endpay Gateway';
            $this->method_description = 'Payment Solutions'; // will be displayed on the options page
            $this->isProd = true;
            $this->host = $this->isProd ? 'https://api.miu.cl/api/1.0' : 'http://localhost:3000/api/1.0';
         
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );
         
            // Method with all the options fields
            $this->init_form_fields();
         
            // Load the settings.
            $this->title = $this->get_option( 'title', 'Webpay' );
            $this->init_settings();
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
            add_action( 'woocommerce_api_webhook_status_'.$this->id, array( $this, 'webhook' ) );

            if (!$this->is_valid_for_use()) {
                $this->enabled = false;
            }

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
                /*
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => '',
                    'default'     => 'Webpay',
                    'desc_tip'    => false,
                    'hidden' => false
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
                */
                'commerce_id' => array(
                    'title'       => 'Código de comercio',
                    'type'        => 'text',
                    'description' => 'Indica tu código de comercio para el ambiente de producción. Este se te entregará al registrarte en https://endpay.cl',
                    'placeholder' => 'Ej: 11234'
                ),
                'api_key' => array(
                    'title'       => 'API Key',
                    'type'        => 'text',
                    'description' => 'Esta llave privada te la entregará EndPay en la configuración de tu cuenta.',
                    'placeholder' => 'Ej: XXXXXXX-XXXXXXX-XXXXXXX-XXXXXXX'
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
            global $wp;
            $current_url = home_url( add_query_arg( array(), $wp->request ) );

            // we need it to get any order detailes
            $order = wc_get_order( $order_id );
         
            $amount = (int) number_format($order->get_total(), 0, ',', '');

            /*
              * Array with parameters for API interaction
             */
            $data = array(
                'subject' => 'Pago woocommerce',
                'amount' => $amount,
                'return_url' => $current_url,
                'cancel_url' => $current_url,
                'notify_url' => $current_url
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
              // TODO use prod
             $response = wp_remote_post( $this->host . '/payments/create', $args );
         
         
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
        
        /**
         * Comprueba configuración de moneda (Peso Chileno).
         **/
        public static function is_valid_for_use()
        {
            return in_array(get_woocommerce_currency(), ['CLP']);
        }
 
		/*
		 * In case you need a webhook
		 */
		public function webhook() {
            $order = wc_get_order( $_GET['id'] );
            $order->payment_complete();
            $order->reduce_order_stock();
         
            update_option('webhook_debug', $_GET);

            @ob_clean();
            if (isset($_POST)) {
                header('HTTP/1.1 200 OK');

                return (new ResponseController($this->config))->response($_POST);
            } else {
                echo 'Ocurrió un error al procesar su compra';
            }

	 	}
 	}
}
