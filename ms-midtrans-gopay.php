<?php
/**
 * Plugin Name: MS Midtrans-Gopay
 * Version: 1.0.0
 * Description: Payment method unofficial Midtrans-Gopay for WooCommerce | Support: tom.wpdev@gmail.com | Phone: 08113644664 
 */

// check
if ( !function_exists( 'add_action' ) ) {
	die();
}

// constant
$version = '1.0.0';
define( 'MS_MGP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MS_MGP_PLUGIN_ENV', 'staging' );
if ( MS_MGP_PLUGIN_ENV === 'staging' ) {
	$version = time();
}
define( 'MS_MGP_PLUGIN_VERSION', $version );

// Init
add_action( 'plugins_loaded', 'ms_midtrans_gopay_class_init' );
function ms_midtrans_gopay_class_init() {
	
	class MS_Midtrans_GoPay_Gateway extends WC_Payment_Gateway {
		/**
		 * Class constructor, more about it in Step 3
		 */
		public function __construct() {
			$this->id 					= 'ms_midtrans_gopay';
			$this->icon 				= ''; 
			$this->has_fields 			= false; // in case you do not need a custom credit card form
			$this->method_title 		= __( 'MS Midtrans GoPay', 'ms-midtrans-gopay' );
			$this->method_description 	= __( 'GoPay payement gateway using Midtrans', 'ms-midtrans-gopay' );
		 
			/**
			 * gateways can support subscriptions, refunds, saved payment methods,
			 * but in this tutorial we begin with simple payments
			 */
			$this->supports = array(
				'products'
			);
		 
			// Method with all the options fields
			$this->init_form_fields();
		 
			// Load the settings.
			$this->init_settings();
			$this->title 			= $this->get_option( 'title' );
			$this->description 		= $this->get_option( 'description' );
			$this->instructions 	= $this->get_option( 'instructions' );
			$this->enabled 			= $this->get_option( 'enabled' );
			$this->sandbox_mode 	= 'yes' === $this->get_option( 'sandbox_mode' );
			$this->client_key 		= $this->get_option( 'client_key' );
			$this->server_key 		= $this->get_option( 'server_key' );
			$this->expiry_time 		= $this->get_option( 'expiry_time' );
			$this->expiry_unit 		= $this->get_option( 'expiry_unit' );
			$this->callback_url 	= $this->get_option( 'callback_url' );
			$this->notification_url = '';
			$this->api_url 			= $this->sandbox_mode ? 'https://api.sandbox.midtrans.com' : 'https://api.midtrans.com';

			// This action hook saves the settings
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			// Print QR Code in Thank You Page
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'payment_instructions' ) );
		 
			/**
			 * We do not need custom JavaScript to obtain a token
			 * add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
			 */
		 
			/**
			 * You can also register a webhook here
			 * woocommerce_api_{webhook name}
			 * url must be http://domain/wc-api/ms-midtrans-gopay-status/
			 */
			add_action( 'woocommerce_api_ms-midtrans-gopay-status', array( $this, 'check_payment_status' ) );
		}
 
		/**
		 * Plugin options, we deal with it in Step 3 too
		 */
		public function init_form_fields(){
			$this->form_fields = array(
				'enabled' => array(
					'title' 		=> __( 'Enable/Disable', 'ms-midtrans-gopay' ),
					'label'       	=> __( 'Enable MS Midtrans-GoPay', 'ms-midtrans-gopay' ),
					'type'        	=> 'checkbox',
					'description' 	=> '',
					'default'     	=> 'no'
				),
				'title' => array(
					'title'       	=> __( 'Title', 'ms-midtrans-gopay' ),
					'type'        	=> 'text',
					'description' 	=> __( 'This controls the title which the user sees during checkout.', 'ms-midtrans-gopay' ),
					'default'     	=> 'GoPay',
					'desc_tip'    	=> true,
				),
				'description' => array(
					'title'       	=> __( 'Description', 'ms-midtrans-gopay' ),
					'type'        	=> 'textarea',
					'description' 	=> __( 'This controls the description which the user sees during checkout.', 'ms-midtrans-gopay' ),
					'default'     	=> __( 'Pay with GoPay via our super-cool payment gateway.', 'ms-midtrans-gopay' ),
				),
				'instructions' => array(
					'title'       	=> __( 'Instructions', 'ms-midtrans-gopay' ),
					'type'        	=> 'textarea_html',
					'description' 	=> __( 'This controls the instructions which the user sees during checkout.', 'ms-midtrans-gopay' ),
					'default'     	=> __( 'Pay with GoPay via our super-cool payment gateway.', 'ms-midtrans-gopay' ),
					'desc_tip'    	=> true,
				),
				'sandbox_mode' => array(
					'title'       	=> __( 'Sandbox mode', 'ms-midtrans-gopay' ),
					'label'       	=> __( 'Enable Sandbox Mode', 'ms-midtrans-gopay' ),
					'type'        	=> 'checkbox',
					'description' 	=> __( 'Place the payment gateway in sandbox mode.', 'ms-midtrans-gopay' ),
					'default'     	=> 'yes',
					'desc_tip'    	=> true,
				),
				'merchant_id'	 	=> array(
					'title'		  	=> __( 'Merchant ID', 'ms-midtrans-gopay' ),
					'type'		  	=> 'text'	
				),
				'client_key' => array(
					'title'       	=> __( 'Client Key', 'ms-midtrans-gopay' ),
					'type'        	=> 'text'
				),
				'server_key' => array(
					'title'       	=> __( 'Server Key', 'ms-midtrans-gopay' ),
					'type'        	=> 'text',
				),
				'expiry_time' => array(
					'title'		  	=> __( 'Payment Expiry Duration', 'ms-midtrans-gopay' ),
					'type'		  	=> 'number',
					'default'	  	=> 30,
					'step'		  	=> 1,
					'description' 	=> __( 'If blank default expiry time form Midtrans will be used.', 'ms-midtrans-gopay' )
				),
				'expiry_unit' => array(
					'title'		  	=> __( 'Payment Expiry Unit', 'ms-midtrans-gopay' ),
					'type'		  	=> 'select',
					'options'	  	=> array(
						'second'  	=> __( 'Second', 'ms-midtrans-gopay' ),
						'minute'  	=> __( 'Minute', 'ms-midtrans-gopay' ),
						'hour'	  	=> __( 'Hour', 'ms-midtrans-gopay' ),
						'day'	  	=> __( 'Day', 'ms-midtrans-gopay' )
					),
					'default'	  	=> 'minute'		
				),
				'callback_url' => array(
					'title'       	=> __( 'Callback URL', 'ms-midtrans-gopay' ),
					'type'        	=> 'text',
					'description' 	=> __( 'If blank default callback will be disabled.', 'ms-midtrans-gopay' )
				),
				'notifcation_url' => array(
					'title'		  		=> 'Notification URL',
					'type'		  		=> 'hidden',
					'custom_attributes' => array(
						'disabled' => 'true'	
					),
					'description' 		=> '<code>' . home_url( '/wc-api/ms-midtrans-gopay-status/' ) . '</code><br/>Please make sure permalink already set to %postname%'
				)
			);
		}
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 * public function payment_fields() {}
		 */
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 * public function payment_scripts() {}
		 */
 
		/*
		 * Fields validation
		 * public function validate_fields() {}
		 */
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
			global $woocommerce;
			// we need it to get any order detailes
			$order 	= wc_get_order( $order_id );
			$url 	= $this->api_url . "/v2/charge";

			/*
			 * Array with parameters for API interaction
			 */
			$body = array(
				'payment_type' => 'gopay',
				'transaction_details' 	=> array(
					'order_id' 			=> $order_id,
					'gross_amount' 		=> $order->get_total()
				),
				'customer_details' => array(
					'first_name' 	=> $order->get_billing_first_name(),
					'last_name' 	=> $order->get_billing_last_name(),
					'email' 		=> $order->get_billing_email(),
					'phone' 		=> $order->get_billing_phone()
				)
			);

			// items 
			$items = $order->get_items();
			$body['item_details'] = array();
			foreach( $items as $item ) {
				$body['item_details'][] = array(
					'id' 		=> $item->get_id(),
					'price' 	=> ceil($order->get_item_subtotal( $item, false )),
					'quantity' 	=> $item->get_quantity(),
					'name' 		=> $item->get_name()
				);
			}

			// shipping
			if ( $order->get_total_shipping() > 0 ) {
				$body['item_details'][] = array(
					'id' 		=> 'shipping',
					'price' 	=> ceil($order->get_total_shipping()),
					'quantity' 	=> 1,
					'name' 		=> __( 'shipping', 'ms-midtrans-gopay' )
				);
			}

			// tax
			if ( $order->get_total_tax() > 0 ) {
				$body['item_details'][] = array(
					'id' 		=> 'tax',
					'price' 	=> ceil( $order->get_total_tax() ),
					'quantity' 	=> 1,
					'name' 		=> __( 'tax', 'ms-midtrans-gopay' )
				);
			}

			// discount
			if ( $order->get_total_discount() > 0 ) {
				$body['item_details'][] = array(
					'id' 		=> 'discount',
					'price' 	=> ceil( $order->get_total_discount() ) *-1,
					'quantity' 	=> 1,
					'name' 		=> __( 'discount', 'ms-midtrans-gopay' )
				);
			}

			// fees
			if ( sizeof( $order->get_fees() ) > 0 ) {
				$fees = $order->get_fees();
				$i = 0;
				foreach( $fees as $item ) {
					$body['item_details'][] = array(
						'id' 		=> 'fee' . $i,
						'price' 	=>  ceil( $item['line_total'] ),
						'quantity' 	=> 1,
						'name' 		=> $item['name'],
					);
				$i++;
			  }
			}

			// recalculate gross amount
			$data_items = $body['item_details'];
			$total_amount = 0;
			foreach( $data_items as $dataitem ) {
				$total_amount+=($dataitem ['price']*$dataitem['quantity']);
			}

			// set new gross amount
			$body['transaction_details']['gross_amount'] = $total_amount;

			// callback
			if ( '' != $this->callback_url ) {
				$body['gopay'] = array(
					'enable_callback' 	=> true,
					'callback_url' 		=> $this->callback_url
				);
			}

			// expiry
			if ( '' != $this->expiry_time && $this->expiry_time != '0' ) {
				$body['custom_expiry'] = array(
					'expiry_duration' 	=> $this->expiry_time,
					'unit' 				=> $this->expiry_unit
				);
			}

			// parameter
			$args = array(
				'headers' => array(
					'Accept' 		=> 'application/json',
					'Content-Type' 	=> 'application/json',
					'Authorization' => 'Basic ' . base64_encode( $this->server_key . ':' )
				),
				'body' => json_encode( $body )
			);


			/*
			 * Your API interaction could be built with wp_remote_post()
			 */

			$response = wp_remote_post( $url, $args );

			if ( !is_wp_error( $response ) ) {
				$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $response_body['actions'] ) && !empty( $response_body['actions'] ) ) {
					// note
					$order->add_order_note( __( 'Order placed.', 'ms-midtrans-gopay' ) );

					// save to meta
					update_post_meta( $order_id, '_ms_gopay_charge_details', maybe_serialize( $response_body['actions'] ) );

					// note
					$order->add_order_note( __( 'Midtrans HTTP notifications received: ', 'ms-midtrans-gopay' ) . $response_body['status_message'] . '. Midtrans-GoPay' );

					// Empty cart
					$woocommerce->cart->empty_cart();

					// note
					$order->add_order_note( __( 'Order status Pending.', 'ms-midtrans-gopay' ) );
		 
					// Redirect to the thank you page
					return array(
						'result' 	=> 'success',
						'redirect' 	=> $this->get_return_url( $order )
					);
				} else {
					// Note
					$order->add_order_note( __( 'Midtrans HTTP notifications received: ', 'ms-midtrans-gopay' ) . $response_body['status_message'] . '. Midtrans-GoPay' );
					wc_add_notice( $response_body['status_message'], 'error' );
					return;
				}
			} else {
				wc_add_notice(  'Connection error.', 'error' );
				return;
			}
		}

		/**
		 * Thank you
		 */
		public function payment_instructions( $order_id ) {
			$actions 			= get_post_meta( $order_id, '_ms_gopay_charge_details', true );
			$order 				= wc_get_order( $order_id );
			$payment_gateway 	= wc_get_payment_gateway_by_order( $order );

			// only show in pending, processing
			if ( $actions && !in_array( $order->get_status(), array( 'completed', 'cancelled', 'refunded' ) ) ) {
				$actions = maybe_unserialize( $actions );

				// includ html
				include( MS_MGP_PLUGIN_DIR . '/views/thankyou.php' );

				// enqueue the style
				wp_enqueue_style( 'ms-midtrans-gopay-css', plugins_url( 'assets/css/style.css', MS_MGP_PLUGIN_DIR . '/ms-midtrans-gopay' ), array(), '1.0.0' );
			}
		}
 
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function check_payment_status() {
			// get post data
			$body 		= file_get_contents('php://input');
			if ( $body ) {
				$response	= json_decode( $body ); 
				$order_id 	= $response->order_id;
				$order 		= wc_get_order( $order_id );

				if ( $response->status_code == '200' && $response->transaction_status == 'settlement' ) {
					// note
					$order->add_order_note( __( 'Midtrans HTTP notifications received: ', 'ms-midtrans-gopay' ) . $response->transaction_status . '. Midtrans-GoPay' );
					
					// processing with note
					wc_reduce_stock_levels( $order_id );

					// $order->payment_complete(); with note
					$order->update_status( 'processing' );

					// completed with note
					$order->update_status( 'completed' );
				} else if ( $response->status_code == '201' && $response->transaction_status == 'pending' ) {
					// pending
					$order->add_order_note( __( 'Midtrans HTTP notifications received: ', 'ms-midtrans-gopay' ) . $response->transaction_status . '. Midtrans-GoPay' );
				} else {

					// note
					$order->add_order_note( __( 'Midtrans HTTP notifications received: ', 'ms-midtrans-gopay' ) . $response->transaction_status . '. Midtrans-GoPay' );
					
					// cancelled
					$order->update_status( 'cancelled', __( 'The order was cancelled due to no payment from customer.', 'ms-midtrans-gopay') );
					
					// note
					$order->add_order_note( __( 'Order status changed from Pending payment to Cancelled.', 'ms-midtrans-gopay' ) );
				}
				/**
				 * $this->write_log( 'Ping from midtrans' );
				 * $this->write_log( $response );
				 */
			}
			exit;
		}

		/**
		 * Custom settings fields
		 */
		public function generate_textarea_html_html( $key, $data ) {
			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'class'             => 'button-secondary',
				'css'               => '',
				'custom_attributes' => array(),
				'desc_tip'          => false,
				'description'       => '',
				'title'             => '',
			);

			$data = wp_parse_args( $data, $defaults );
			ob_start();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
					<?php echo $this->get_tooltip_html( $data ); ?>
				</th>
				<td class="forminp">
					<?php 
					$content = $this->instructions;
					$editor_id = $field;
					$settings = array( 
						'textarea_name' => $field,
						'textarea_rows' => 10,
					);
					wp_editor( $content, $editor_id, $settings ); ?>
				</td>
			</tr>
			<?php
			return ob_get_clean();
		}

		/*
		 * Helper
		 * Writing log & debug
		 */
		public function write_log($log) {
			if (true === WP_DEBUG) {
				if (is_array($log) || is_object($log)) {
					error_log(print_r($log, true));
				} else {
					error_log($log);
				}
			}
		}
	}
}

// Add
function ms_midtrans_gopay_payment_gateway( $methods ) {
	$methods[] = 'MS_Midtrans_GoPay_Gateway'; 
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'ms_midtrans_gopay_payment_gateway' );

// Misc
if ( !function_exists( 'wp_is_mobile' ) ) {
	function wp_is_mobile() {
		return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
	}
}