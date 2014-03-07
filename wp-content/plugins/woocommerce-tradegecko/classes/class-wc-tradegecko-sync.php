<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class WC_TradeGecko_Sync
 * Class to handle all sync actions. 
 * 
 * @since 1.0
 */
class WC_TradeGecko_Sync {
        
	/** TradeGecko order IDs */
	public $tg_order_ids = array();

	/** Holds WC order IDs of not exported orders */
	public $not_exported_order_ids = array();

	/** TradeGecko order IDs to WC order IDs mapping  */
	public $tg_id_to_order_id_mapping = array();
	
	public function __construct() {
		
		$this->inventory_sync           = WC_TradeGecko_Init::get_setting( 'inventory_sync' );
		$this->orders_sync              = WC_TradeGecko_Init::get_setting( 'orders_sync' );
		$this->enable                   = WC_TradeGecko_Init::get_setting( 'enable' );
		$this->product_price_sync	= WC_TradeGecko_Init::get_setting( 'product_price_sync' );
		$this->product_title_sync	= WC_TradeGecko_Init::get_setting( 'product_title_sync' );

		add_action( 'wc_tradegecko_synchronization', array( $this, 'wc_tradegecko_automatic_sync_processes' ) );

		// Sync the customer address when it is updated
		add_action( 'woocommerce_customer_save_address', array( $this, 'process_update_customer_addresses' ) );

		if ( $this->orders_sync ) {
			// Action on creating new order
			add_action( 'wc_tradegecko_export_new_orders', array( $this, 'process_new_order_export' ) );

			// Action on successful payment
			add_action( 'woocommerce_payment_complete', array( $this, 'process_order_update' ) );
			add_action( 'wc_tradegecko_update_order', array( $this, 'process_order_update' ) );

			add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'process_order_update' ) );
			add_action( 'woocommerce_order_status_on-hold_to_completed',  array( $this, 'process_order_update' ) );
			add_action( 'woocommerce_order_status_failed_to_processing', array( $this, 'process_order_update' ) );
			add_action( 'woocommerce_order_status_failed_to_completed',  array( $this, 'process_order_update' ) );
		}

	}
	
	/*=============================
	 * Action Hook Functions
	 ==============================*/
	
	/**
	 * Process order update
	 * 
	 * @since 1.0
	 * @access public
	 * @param type $order_id
	 */
	public function process_order_update( $order_id ) {

		try {
			if ( $this->orders_sync ) {
				$this->update_orders( $order_id );
			}

		} catch( Exception $e ) {

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );

		}   
	}

	/**
	 * Process the export of the new order to TG.<br />
	 * It will process the customer info and the order export
	 * 
	 * @access public
	 * @since 1.0
	 * @param int $order_id The new order ID
	 * @param array $posted Array of the posted order form data
	 */
	public function process_new_order_export( $order_id ) {

		$order = new WC_Order( $order_id );
		$tg_customer_id = get_user_meta( $order->user_id, 'wc_tradegecko_customer_id', true );
		
		try {
			if ( ! empty( $order->user_id ) ) {

				// If no customer create one
				if ( ! $tg_customer_id ) {
					$this->export_customer( $order->user_id );
				}
				
			} else {
				$tg_guest_id = get_post_meta( $order->id, WC_TradeGecko_Init::$meta_prefix .'guest_customer_id', true );
				
				// Export Guest, if he was not already exported
				if ( empty( $tg_guest_id ) ) {
					$this->export_customer( $order->user_id, $order->id );
				}
			}

			if ( $this->orders_sync ) {
				
				$this->export_order( $order->id );
			}

		} catch( Exception $e ) {

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );

		}
	}

	/**
	 * Check and initiate all processes involved in the automatic synchronization.
	 * 
	 * @since 1.0
	 * @access public
	 */
	public function wc_tradegecko_automatic_sync_processes() {

		try{

			
			// Add/Update the orders
			if ( $this->orders_sync ) {
				// Sync the customers first
				$this->customers_sync();

				$this->update_orders();
			}

			// Sync product inventory
			if ( $this->inventory_sync ) {
				$this->sync_inventory();
			}

		} catch( Exception $e ) {

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );

		}

	}

	/**
	 * Process customer address update
	 * 
	 * @since 1.0
	 * @access public
	 * @param type $customer_id
	 */
	public function process_update_customer_addresses( $customer_id ) {
		
		try{

			$this->update_customer_addresses( $customer_id );

		} catch( Exception $e ) {

			WC_TradeGecko_Init::add_sync_log( 'Error', $e->getMessage() );

		}

	}

	/**
	 * Perform a product inventory sync.
	 * 
	 * @since 1.0
	 * @access public
	 * @throws Exception Exception is thrown in case of an API arror
	 */
	private function sync_inventory() {

		// Pull all variants
		$all_variants = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'variants' ) );

		if ( isset( $all_variants->error ) ) {
			throw new Exception( sprintf( __( 'Inventory variants could not be pulled from the TradeGecko system. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $all_variants->error, $all_variants->error_description ) );
		} else {

			if ( 0 < count( $all_variants->variants ) ) {
				$processed_product = array();
				$processed_skus = array();

				foreach ( $all_variants->variants as $variant ) {
					if ( false == $variant->is_online ) {
						continue;
					}
					
					// Process SKU only once
					if ( in_array( $variant->sku, $processed_skus ) ) {
						continue;
					}

					// Check if we can find the product id from the sku
					$valid_product_id = $this->get_product_by_sku( $variant->sku );
					
					$processed_skus[] = $variant->sku;

					// If we found the product in the system, we can now update its info
					if ( $valid_product_id ) {

						// Get the product object
						if ( function_exists( 'get_product' ) ) {
							$product = get_product( $valid_product_id );
							if ( $product instanceof WC_Product_Variation ) {
								$prod_id = $product->get_variation_id();
							} else {
								$prod_id = $product->id;
							}
						} else {
							throw new Exception( sprintf( __( 'WooCommerce essential function "get_product" is missing. You are either using a version older than WooCommerce 2.0 or modified the WooCommerce plugin in someway.', WC_TradeGecko_Init::$text_domain ) ) );
						}

						// Add log
						WC_TradeGecko_Init::add_log( 'Updating product #' . $prod_id );
						WC_TradeGecko_Init::add_log( 'Product data from TG: ' . print_r( $variant, true ) );

						// Add the TG variant id to the product meta
						update_post_meta( $prod_id, WC_TradeGecko_Init::$meta_prefix .'variant_id', $variant->id );

						// Update stock of the product respecting the max_online parameter
						if ( false == $variant->manage_stock && $product->managing_stock() ) {
							update_post_meta($prod_id, '_manage_stock', 'no');
						} elseif( true == $variant->manage_stock && ! $product->managing_stock() ) {
							update_post_meta($prod_id, '_manage_stock', 'yes');
						} 

						// Only update stock, if we manage stock
						if ( true == $variant->manage_stock ) {
							if ( empty( $variant->max_online ) ) {
								$product->set_stock ( $variant->stock_on_hand );
							} elseif ( $variant->max_online < $variant->stock_on_hand ) {
								$product->set_stock ( $variant->max_online );
							} else {
								$product->set_stock ( $variant->stock_on_hand );
							}
						}

						// Allow backorders if the 'keep_selling' is enabled
						if ( false == $variant->keep_selling && $product->backorders_allowed() ) {
							update_post_meta($prod_id, '_backorders', 'no');
						} elseif( true == $variant->keep_selling && ! $product->backorders_allowed() ) {
							update_post_meta($prod_id, '_backorders', 'yes');
						} 

						// Update price of the product
						if ( $this->product_price_sync ) {
							update_post_meta($prod_id, '_regular_price', $variant->retail_price);
						}

						// Update product info, if enabled and the product is not precessed already
						if ( $this->product_title_sync && ! in_array( $variant->product_id, $processed_product ) ) {

							// Get the info parent product
							$product_data = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'products', null, $variant->product_id ) );
							if ( isset( $product_data->error ) ) {
								throw new Exception( sprintf( __( 'Could not retrieve main product info of %s. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $prod_id, $product_data->error, $product_data->error_description ) );
							} else{
								if( ! empty( $product_data->product ) ) {

									WC_TradeGecko_Init::add_log( 'Updating product title to '. $product_data->product->name );

									// Update the product name
									$post_data = array();
									$post_data['ID'] = $prod_id;
									$post_data['post_title'] = $product_data->product->name;

									wp_update_post( $post_data );

									$processed_product[] = $variant->product_id;

								}
							}

						}

					}

				}

			}

		}

		WC_TradeGecko_Init::add_sync_log( 'Message', __( 'Inventory Sync Completed Successfully', WC_TradeGecko_Init::$text_domain ) );

	}

	/**
	 * Perform a customer sync. If customers are not already exported to TG it will export them.
	 * 
	 * @since 1.0
	 * @access private
	 * @throws Exception Exception is thrown in case of an API arror or http error code
	 */
	private function customers_sync() {

		// We want to sync Customers only
		$get_customers = get_users( array( 'role' => 'customer' ) );

		if ( 0 < count( $get_customers ) ) {

			foreach ( $get_customers as $customer ) {

				// If customer is synced, then the TG customer id will be present
				$tg_customer_id = get_user_meta( $customer->ID, 'wc_tradegecko_customer_id', true );

				if ( $tg_customer_id ) {

					$tg_customer_data = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'companies', null, $tg_customer_id ) );

					// If error occurred end the process and log the error
					if ( isset( $tg_customer_data->error ) ) {
						throw new Exception( sprintf( __( 'Could not retrieve the customer info for user "%s". Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $customer->user_login, $tg_customer_data->error, $tg_customer_data->error_description ) );
					}

					// Update main customer info
					$tg_company = isset( $tg_customer_data->company ) ? $tg_customer_data->company : $tg_customer_data->companies;
					$this->update_customer_main_info( $tg_company, $tg_customer_id, $customer );

					// Update customer address info
					$this->update_customer_addresses( $customer->id );

				} else {

					// Export the customer to TG
					$this->export_customer( $customer->ID );

				}

			}

		}

		WC_TradeGecko_Init::add_sync_log( 'Message', __( 'Customer Export Completed Successfully', WC_TradeGecko_Init::$text_domain ) );
	}

	/**
	 * Update the customer/company main information.
	 * 
	 * @since 1.0
	 * @access public
	 * @param object $customer_data
	 * @param int $customer_id
	 * @param object $customer
	 * @access private
	 * @throws Exception Exception is thrown in case of an API arror
	 */
	private function update_customer_main_info( $customer_data, $customer_id, $customer ) {

		$user_meta = array_map( array( $this, 'map_user_array' ), get_user_meta( $customer->ID ) );

		// Check if the main info is different than the one we have
		$main_info_to_update = array();
		if ( ! empty( $customer->user_email ) && $customer->user_email != $customer_data->email ) {
			$main_info_to_update['company']['email'] = $customer->user_email;
		}

		if ( ! empty( $customer->user_url ) && $customer->user_url != $customer_data->website ) {
			$main_info_to_update['company']['website'] = $customer->user_url;
		}

		// Update company name only if it is different than the TG one and it is not empty.
		if ( ! empty( $user_meta['billing_company'] ) && $user_meta['billing_company'] != $customer_data->name ) {
			$main_info_to_update['company']['name'] = $user_meta['billing_company'];
		} elseif ( ( ! empty( $user_meta['billing_first_name'] ) || ! empty( $user_meta['billing_last_name'] ) ) &&
			( $user_meta['billing_first_name'] .' '. $user_meta['billing_last_name'] ) != $customer_data->name ) {
			$main_info_to_update['company']['name'] = $user_meta['billing_first_name'] .' '. $user_meta['billing_last_name'];
		} else {
			$main_info_to_update['company']['name'] = $customer->user_email; // Field is required for each customer
		}

		// Update the main info only if needed
		if ( ! empty( $main_info_to_update ) ) {

			$update_company = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'PUT', 'companies', $main_info_to_update, $customer_id ) );
			// If error occurred end the process and log the error
			if ( isset( $update_company->error ) ) {
				throw new Exception( sprintf( __( 'Customer info for user "%s" could not be updated. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $customer->user_login, $update_company->error, $update_company->error_description ) );
			}

		}

	}

	/**
	 * Update the customer Shipping/Billing address in TG system. If the customer is not exported to TG it will export him.
	 * 
	 * @since 1.0
	 * @access private
	 * @param int $customer_id
	 * @throws Exception Exception is thrown in case of an API arror
	 */
	private function update_customer_addresses( $customer_id ) {

		$customer = get_userdata( $customer_id );

		$tg_customer_id = get_user_meta( $customer->ID, 'wc_tradegecko_customer_id', true );

		// Add log
		WC_TradeGecko_Init::add_log( 'Update address for customer # ' . $customer->ID );

		if ( $tg_customer_id ) {

			// Gather and update customer address
			$address_data = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'addresses', null, null, array( 'company_id' => $tg_customer_id ) ) );

			// Add log
			WC_TradeGecko_Init::add_log( 'Customer address data response: ' . print_r( $address_data, true ) );

			// If error occurred end the process and log the error
			if ( isset( $address_data->error ) ) {
				throw new Exception( sprintf( __( 'Could not retieve the addresses for user "%s". Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $customer->user_login, $address_data->error, $address_data->error_description ) );
			}

			$user_meta = array_map( array( $this, 'map_user_array' ), get_user_meta( $customer->ID ) );

			foreach ( $address_data->addresses as $address ) {

				// Save the billing address ID in the user meta
				$shipping_id = get_user_meta( $customer->ID, 'wc_tradegecko_customer_shipping_address_id', true );
				$billing_id = get_user_meta( $customer->ID, 'wc_tradegecko_customer_billing_address_id', true );

				// Update the shipping address, if needed
				if ( $shipping_id == $address->id ) {

					$fields_to_update = array();

					// If the address1 is different then we have a different address, so generate the query.
					// There is little to no chance that the customer will change any other part of the address without the address1 part.
					if ( ! empty( $user_meta['shipping_address_1'] ) && $address->address1 != $user_meta['shipping_address_1'] ) {
						$fields_to_update = array( 
							'address' => array( 
								'address1'	=> $user_meta['shipping_address_1'],
								'address2'	=> $user_meta['shipping_address_2'],
								'city'		=> $user_meta['shipping_city'],
								'country'	=> $user_meta['shipping_country'],
								'zip_code'	=> $user_meta['shipping_postcode'],
								'state'		=> $user_meta['shipping_state'],
							)
						);
					}

					if ( ! empty( $fields_to_update ) ) {
						// Add log
						WC_TradeGecko_Init::add_log( 'Update shipping address data: ' . print_r( $fields_to_update, true ) );

						$update_shipping_address = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'PUT', 'addresses', $fields_to_update, $address->id ) );

						// Add log
						WC_TradeGecko_Init::add_log( 'Update shipping address response: ' . print_r( $update_shipping_address, true ) );
						// If error occurred end the process and log the error
						if ( isset( $update_shipping_address->error ) ) {
							throw new Exception( sprintf( __( 'Shipping address for user "%s" could not be updated. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $customer->user_login, $update_shipping_address->error, $update_shipping_address->error_description ) );
						}
					}

				}

				// Update the billing address, if needed
				if ( $billing_id == $address->id ) {

					$bill_fields_to_update = array();
					if ( ! empty( $user_meta['billing_address_1'] ) && $address->address1 != $user_meta['billing_address_1'] ) {
						$bill_fields_to_update = array( 
							'address' => array( 
								'address1'	=> $user_meta['billing_address_1'],
								'address2'	=> $user_meta['billing_address_2'],
								'city'		=> $user_meta['billing_city'],
								'country'	=> $user_meta['billing_country'],
								'zip_code'	=> $user_meta['billing_postcode'],
								'state'		=> $user_meta['billing_state'],
								'phone_number'	=> $user_meta['billing_phone'],
								'email'		=> $user_meta['billing_email'],
							)
						);
					}

					if ( ! empty( $bill_fields_to_update ) ) {

						// Add log
						WC_TradeGecko_Init::add_log( 'Update billing address data: ' . print_r( $bill_fields_to_update, true ) );

						$update_billing_address = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'PUT', 'addresses', $bill_fields_to_update, $address->id ) );

						// Add log
						WC_TradeGecko_Init::add_log( 'Update billing address response: ' . print_r( $update_billing_address, true ) );

						// If error occurred end the process and log the error
						if ( isset( $update_billing_address->error ) ) {
							throw new Exception( sprintf( __( 'Billing address for user "%s" could not be updated. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $customer->user_login, $update_billing_address->error, $update_billing_address->error_description ) );
						}

					}

				}

			}

		} else {

			// Export the customer to TG
			$this->export_customer( $customer->ID );

		}

	}

	/**
	 * Export and create customers in TG system.
	 * Customers Shipping and Billing addresses will be created as well, if present.
	 * 
	 * @since 1.0
	 * @access private
	 * @param int $customer_id
	 * @throws Exception Exception is thrown in case of an API arror
	 */
	private function export_customer( $customer_id, $order_id = null ) {
		
		// Build the customer info
		$customer = $this->build_customer_info_array( $customer_id, $order_id );
		
		$order = new WC_Order( (int) $order_id );

		// Add log
		$exp_cus = ( ! empty( $customer['customer_id'] ) ) ? $customer['customer_id'] : $customer['name'];
		WC_TradeGecko_Init::add_log( 'Export customer ' . $exp_cus );

		$main_info['company'] = array( 
			'name'		=> $customer['name'],
			'email'		=> $customer['email'],
			'company_type'	=> 'consumer',
		);

		// Add URL, if needed
		if ( ! empty( $customer['website'] ) ) {
			$main_info['company']['website'] = $customer['website'];
		}

		// Add log
		WC_TradeGecko_Init::add_log( 'Export main info data: ' . print_r( $main_info, true ) );

		$create_customer = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'companies', $main_info ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Export main info data response: ' . print_r( $create_customer, true ) );

		// If error occurred end the process and log the error
		if ( isset( $create_customer->error ) ) {
			throw new Exception( sprintf( __( 'Customer "%s" could not be exported. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $customer['customer_login'], $create_customer->error, $create_customer->error_description ) );
		}

		// Save the newly created ID
		if ( ! empty( $order->id ) ) {
			update_post_meta( $order->id, WC_TradeGecko_Init::$meta_prefix .'guest_customer_id', $create_customer->company->id );
		} else {
			update_user_meta( $customer['customer_id'], 'wc_tradegecko_customer_id', $create_customer->company->id );
		}
		
		// Build the billing address export
		$billing_address['address'] = $customer['billing_address'];
		$billing_address['address']['company_id'] = $create_customer->company->id;
		$billing_address['address']['label'] = 'Billing';

		// Add log
		WC_TradeGecko_Init::add_log( 'Export billing address data: ' . print_r( $billing_address, true ) );

		// Export the billing address
		$create_billing_address = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'addresses', $billing_address ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Export billing address data response: ' . print_r( $create_billing_address, true ) );

		// If error occurred end the process and log the error
		if ( isset( $create_billing_address->error ) ) {
			throw new Exception( sprintf( __( 'Could not create Billing address for user "%s". Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $customer['customer_login'], $create_billing_address->error, $create_billing_address->error_description ) );
		}
		
		if ( ! empty( $order->id ) ) {
			// Save the billing address ID to the order
			update_post_meta( $order->id, WC_TradeGecko_Init::$meta_prefix .'guest_billing_address_id', $create_billing_address->address->id );
		} else {
			// Save the billing address ID in the user meta
			update_user_meta( $customer['customer_id'], 'wc_tradegecko_customer_billing_address_id', $create_billing_address->address->id );
		}

		// Build the Shipping address for export
		$shipping_address['address'] = $customer['shipping_address'];
		$shipping_address['address']['company_id'] = $create_customer->company->id;
		$shipping_address['address']['label'] = 'Shipping';

		// Add log
		WC_TradeGecko_Init::add_log( 'Export shipping address data: ' . print_r( $shipping_address, true ) );

		// Export the shipping address
		$create_shipping_address = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'addresses', $shipping_address ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Export shipping address data response: ' . print_r( $create_shipping_address, true ) );

		// If error occurred end the process and log the error
		if ( isset( $create_shipping_address->error ) ) {
			throw new Exception( sprintf( __( 'Could not create Shipping address for user "%s". Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $customer['customer_login'], $create_shipping_address->error, $create_shipping_address->error_description ) );
		}

		if ( ! empty( $order->id ) ) {
			// Save the billing address ID to the order
			update_post_meta( $order->id, WC_TradeGecko_Init::$meta_prefix .'guest_shipping_address_id', $create_shipping_address->address->id );
		} else {
			// Save the billing address ID in the user meta
			update_user_meta( $customer['customer_id'], 'wc_tradegecko_customer_shipping_address_id', $create_shipping_address->address->id );
		}

	}
	
	public function build_customer_info_array( $customer_id = null, $order_id = null ) {
		
		$user_data = array();
		
		// If we have the order, we have a Guest
		if ( ! empty( $order_id ) ) {
			
			$order = new WC_Order( (int) $order_id );
			
			if ( ! empty( $order->billing_company ) ) {
				$name = $order->billing_company;
			} elseif ( ! empty( $order->billing_first_name ) || ! empty( $order->billing_last_name ) ) {
				$name = $order->billing_first_name .' '. $order->billing_last_name;
			} else {
				$name = $order->billing_email; // Field should required
			}
			
			$user_data['customer_id'] = 0;
			$user_data['customer_login'] = $name;
			$user_data['name'] = $name;
			$user_data['email'] = $order->billing_email;
			
			// Create the customer addresses
			if ( ! empty( $order->billing_address_1 ) ) {
				$user_data['billing_address'] = array(
					'address1'	=> $order->billing_address_1,
					'address2'	=> $order->billing_address_2,
					'city'		=> $order->billing_city,
					'country'	=> $order->billing_country,
					'zip_code'	=> $order->billing_postcode,
					'state'		=> $order->billing_state,
					'phone_number'	=> $order->billing_phone,
					'email'		=> $order->billing_email,
				);
			} else {
				$user_data['billing_address'] = array(
					'address1'	=> 'empty',
				);
			}
			
			if ( ! empty( $order->shipping_address_1 ) ) {
				$user_data['shipping_address'] = array(
					'address1'	=> $order->shipping_address_1,
					'address2'	=> $order->shipping_address_2,
					'city'		=> $order->shipping_city,
					'country'	=> $order->shipping_country,
					'zip_code'	=> $order->shipping_postcode,
					'state'		=> $order->shipping_state,
				);
			} elseif ( ! empty( $order->billing_address_1 ) ) {
				// Add the billing address as shipping
				$user_data['shipping_address'] = array( 
					'address1'	=> $order->billing_address_1,
					'address2'	=> $order->billing_address_2,
					'city'		=> $order->billing_city,
					'country'	=> $order->billing_country,
					'zip_code'	=> $order->billing_postcode,
					'state'		=> $order->billing_state,
				);
			} else {
				$user_data['shipping_address'] = array( 
					'address1'	=> 'empty',
				);
			}
			
			
		} else {
			
			$customer = get_userdata( (int) $customer_id );

			$user_meta = array_map( array( $this, 'map_user_array' ), get_user_meta( $customer->ID ) );
			
			if ( ! empty( $user_meta['billing_company'] ) ) {
				$name = $user_meta['billing_company'];
			} elseif ( ! empty( $user_meta['billing_first_name'] ) || ! empty( $user_meta['billing_last_name'] ) ) {
				$name = $user_meta['billing_first_name'] .' '. $user_meta['billing_last_name'];
			} else {
				$name = $customer->user_email; // field is required for each customer
			}
			
			$user_data['customer_id'] = $customer->ID;
			$user_data['customer_login'] = $customer->user_login;
			$user_data['name'] = $name;
			$user_data['email'] = $customer->user_email;
			
			// Add URL, if needed
			if ( ! empty( $customer->user_url ) ) {
				$user_data['website'] = $customer->user_url;
			}
			
			// Create the customer addresses
			if ( ! empty( $user_meta['billing_address_1'] ) ) {
				$user_data['billing_address'] = array(
					'address1'	=> $user_meta['billing_address_1'],
					'address2'	=> $user_meta['billing_address_2'],
					'city'		=> $user_meta['billing_city'],
					'country'	=> $user_meta['billing_country'],
					'zip_code'	=> $user_meta['billing_postcode'],
					'state'		=> $user_meta['billing_state'],
					'phone_number'	=> $user_meta['billing_phone'],
					'email'		=> $user_meta['billing_email'],
				);
			} else {
				$user_data['billing_address'] = array(
					'address1'	=> 'empty',
				);
			}
			
			if ( ! empty( $user_meta['shipping_address_1'] ) ) {
				$user_data['shipping_address'] = array(
					'address1'	=> $user_meta['shipping_address_1'],
					'address2'	=> $user_meta['shipping_address_2'],
					'city'		=> $user_meta['shipping_city'],
					'country'	=> $user_meta['shipping_country'],
					'zip_code'	=> $user_meta['shipping_postcode'],
					'state'		=> $user_meta['shipping_state'],
				);
			} elseif ( ! empty( $user_meta['billing_address_1'] ) ) {
				// Add the billing address as shipping
				$user_data['shipping_address'] = array(
					'address1'	=> $user_meta['billing_address_1'],
					'address2'	=> $user_meta['billing_address_2'],
					'city'		=> $user_meta['billing_city'],
					'country'	=> $user_meta['billing_country'],
					'zip_code'	=> $user_meta['billing_postcode'],
					'state'		=> $user_meta['billing_state'],
				);
			} else {
				$user_data['shipping_address'] = array(
					'address1'	=> 'empty',
				);
			}

		}
		
		return apply_filters( 'wc-tradegecko_customer_info' , $user_data, $customer_id, $order_id );
	}

	/**
	 * Export order to TradeGecko
	 * 
	 * @global object $wpdb
	 * @param type $order_id
	 * @throws Exception
	 */
	private function export_order( $order_id ) {
		
		// Get order object
		$order = new WC_Order( (int) $order_id );
		
		$order_number = str_replace( '#', '', $order->get_order_number() );

		// Add log
		WC_TradeGecko_Init::add_log( 'Exporting order #' . $order_number );

		// If the customer is Guest, we will get the billing info from the order
		if ( empty( $order->user_id ) ) {
			$billing_address_id = get_post_meta( $order->id, WC_TradeGecko_Init::$meta_prefix .'guest_billing_address_id', true );
			$shipping_address_id = get_post_meta( $order->id, WC_TradeGecko_Init::$meta_prefix .'guest_shipping_address_id', true );
			$company_id = get_post_meta( $order->id, WC_TradeGecko_Init::$meta_prefix .'guest_customer_id', true );
		} else {
			// Get the customer info
			$customer = get_userdata( $order->user_id );
			$billing_address_id = get_user_meta( $customer->ID, 'wc_tradegecko_customer_billing_address_id', true );
			$shipping_address_id = get_user_meta( $customer->ID, 'wc_tradegecko_customer_shipping_address_id', true );
			$company_id = get_user_meta( $customer->ID, 'wc_tradegecko_customer_id', true );
		}
		
		// Build the new order query
		$order_info = array( 
			'order' => array( 
				'billing_address_id'	=> $billing_address_id,
				'company_id'		=> $company_id,
				'due_at'		=> $order->order_date,
				'email'			=> $order->billing_email,
				'fulfillment_status'	=> 'unshipped',
				'invoice_number'	=> $order_number,
				'issued_at'		=> $order->order_date,
				'notes'			=> $order->customer_note,
				'order_number'		=> WC_TradeGecko_Init::get_setting( 'order_number_prefix' ) . $order_number,
				'payment_status'	=> ( 'completed' == $order->status || 'processing' == $order->status ) ? 'paid' : 'unpaid',
				'phone_number'		=> $order->billing_phone,
				'shipping_address_id'	=> $shipping_address_id,
				'status'		=> 'active',
				'tax_type'		=> ( $order->prices_include_tax ) ? 'inclusive' : 'exclusive',
				'source'		=> 'woocommerce',
				'url'			=> admin_url( 'post.php?post=' . absint( $order->id ) . '&action=edit' ),
			)
		);

		// Add the line items to the query
		$items = $order->get_items();

		foreach ( $items as $item ) {

			$_product = $order->get_product_from_item( $item );
			if ( $_product instanceof WC_Product_Variation ) {
				$prod_id = $_product->get_variation_id();
			} else {
				$prod_id = $_product->id;
			}

			// Cost of the item before discount
			$CostPerUnit = number_format( ($item['line_subtotal'] + $item['line_subtotal_tax']) / $item['qty'], 2, '.', '');

			// Get the tax rate for the line item
			$taxes = $order->get_taxes();
			foreach ( $taxes as $tax ) {
				global $wpdb;
				$rate = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %s", $tax['rate_id'] ) );
				$tax_rate = $rate->tax_rate;
			}

			// Get the variant ID from the exported and synced items
			$variant_id = get_post_meta($prod_id, WC_TradeGecko_Init::$meta_prefix .'variant_id', true);
			
			if ( empty( $variant_id ) ) {
				
				// Fail order export if product is not synced with TG
				throw new Exception( sprintf( __( 'Order export failed for order with order number #%s and ID: %s. Cannot export orders with products, which do not exist in TradeGecko. Product with ID: %s and SKU: %s, does not exist in TradeGecko.', WC_TradeGecko_Init::$text_domain ), $order_number, $order->id, $prod_id, $_product->get_sku() ) );
				
			} else {
				$order_info['order']['order_line_items'][] = array(
					'quantity'	=> (int) $item['qty'],
					'discount'	=> '',
					'price'		=> $CostPerUnit,
					'tax_rate'	=> $tax_rate,
					'variant_id'	=> $variant_id,
				);
			}

		}

		// Add another item for the discount as freeform 
		if ( 0 < $order->get_total_discount() ) {
			$order_info['order']['order_line_items'][] = array(
				'quantity'	=> 1,
				'price'		=> '-'.number_format( $order->get_total_discount(), 2, '.', ''),
				'freeform'	=> 'true',
				'line_type'	=> 'Discount',
				'label'		=> 'Discount'
			);
		}


		// Allow for the parameters to be changed or added to
		$order_info = apply_filters( 'wc_tradegecko_new_order_query', $order_info, $order->id );

		// Add log
		WC_TradeGecko_Init::add_log( 'Order Info to export: ' . print_r( $order_info, true ) );

		$export_order = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'POST', 'orders', $order_info ) );
		
		// Add log
		WC_TradeGecko_Init::add_log( 'Response for the exported order: ' . print_r( $export_order, true ) );

		// If error occurred end the process and log the error
		if ( isset( $export_order->error ) ) {
			throw new Exception( sprintf( __( 'Could not export order# "%s". Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $order_number, $export_order->error, $export_order->error_description ) );
		}

		update_post_meta( $order->id, WC_TradeGecko_Init::$meta_prefix .'synced_order_id', $export_order->order->id );

	}

	/**
	 * Sync orders that are already exported to TG.
	 * 
	 * @since 1.0
	 * @access public
	 * @param array $order_ids
	 */
	private function update_orders( $order_ids = array() ) {
		
		// Filter the ids to be update
		$this->filter_order_ids_to_update( $order_ids );

		// Add log
		WC_TradeGecko_Init::add_log( 'Update Orders IDs: ' . print_r( $this->tg_order_ids, true ) );

		if ( ! empty ( $this->tg_order_ids ) ) {
			// Now that we filtered all open orders, sync the information with TG
			$tg_open_orders = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'orders', null, null, array( 'ids' => $this->tg_order_ids ) ) );

			// Add log
			WC_TradeGecko_Init::add_log( 'Update orders data response: ' . print_r( $tg_open_orders, true ) );

			// If error occurred end the process and log the error
			if ( isset( $tg_open_orders->error ) ) {
				throw new Exception( sprintf( __( 'Could not retrieve the open orders from TradeGecko. Error Code: %s. Error Message: %s.', WC_TradeGecko_Init::$text_domain ), $tg_open_orders->error, $tg_open_orders->error_description ) );
			}

			$tg_orders =  isset( $tg_open_orders->order ) ? $tg_open_orders->order : $tg_open_orders->orders;
			foreach ( $tg_orders as $tg_open_order ) {

				$order_id = $this->tg_id_to_order_id_mapping[ $tg_open_order->id ];

				if ( $order_id ) {
					$order = new WC_Order( (int) $order_id );

					// Sync the WC system
					if ( ! empty( $tg_open_order->fulfillment_ids ) ) {

						// Get the fullfilment info and update with shipping time and tracking info
						$ff_data = array();
						foreach ( $tg_open_order->fulfillment_ids as $fulfillment_id ) {
							$tg_fulfillment = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'fulfillments', null, $fulfillment_id ) );

							// Make sure we have the correct node
							$fulfillment = isset( $tg_fulfillment->fulfillment ) ? $tg_fulfillment->fulfillment : $tg_fulfillment->fulfillments ;
							// Get the fulfillment line items
							$tg_fulfillment_products = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'order_line_items', null, null, array( 'ids' => $fulfillment->order_line_item_ids ) ) );

							// Get the correct node
							$tg_line_items = isset( $tg_fulfillment_products->order_line_item ) ? $tg_fulfillment_products->order_line_item : $tg_fulfillment_products->order_line_items;

							// Filter the line items and match them to a WC product
							$product_ids = array();
							foreach ( $tg_line_items as $tg_line_item ) {
								$id = $this->get_product_by_tg_id( $tg_line_item->variant_id );

								if ( $id ) {
									$product_ids[] = $id;
								}
							}

							$ff_data[ $fulfillment->id ] = array(
								'shipped_at'		=> $fulfillment->shipped_at,
								'received_at'		=> $fulfillment->received_at,
								'delivery_type'		=> $fulfillment->delivery_type,
								'tracking_number'	=> $fulfillment->tracking_number,
								'tracking_message'	=> ( ! empty( $fulfillment->tracking_message ) ) ? $fulfillment->tracking_message : '',
								'products'		=> $product_ids,
							);

							update_post_meta( $order->id, WC_TradeGecko_Init::$meta_prefix .'order_fulfillment', $ff_data );

						}

					} else {
						// This should be skipped all the time, but do it just in case
						if ( 'unpaid' == $tg_open_order->payment_status &&
							( 'completed' == $order->status || 'processing' == $order->status ) ) {
							// Update TG order to paid
							$update_info = array(
								'order' => array(
									'payment_status' => 'paid'
								)
							);

							$tg_update_order = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'PUT', 'orders', $update_info, $tg_open_order->id ) );

						}
					}

					// After order info is added and updated
					// Complete the order, if the fulfillment status is shipped
					// This will trigger all emails and notification to the customer
					if ( 'completed' != $order->status && 'shipped' == $tg_open_order->fulfillment_status ) {
						$order->update_status( 'completed' );
					}
				}
			}
		} 

		if ( ! empty( $this->not_exported_order_ids ) ) {

			// Add log
			WC_TradeGecko_Init::add_log( 'Not Exported Orders IDs: ' . print_r( $this->not_exported_order_ids, true ) );

			foreach ( $this->not_exported_order_ids as $order_id ) {
				do_action( 'wc_tradegecko_export_new_orders', $order_id );
			}
		}

		// Unset the variables, just in case
		unset( $this->tg_order_ids );
		unset( $this->not_exported_order_ids );
		unset( $this->tg_id_to_order_id_mapping );

		WC_TradeGecko_Init::add_sync_log( 'Message', __( 'Orders Sync Completed Successfully', WC_TradeGecko_Init::$text_domain ) );

		// Add log
		WC_TradeGecko_Init::add_log( 'Update orders sync completed successfully' );


	}

	/**
	 * Filter and order ids and put them as exported or unexported.
	 * 
	 * @since 1.0
	 * @access public
	 * @param array $order_ids
	 */
	private function filter_order_ids_to_update( $order_ids = array() ) {

		// We want to sync specific orders
		if ( ! empty( $order_ids ) ) {

			// Add log
			WC_TradeGecko_Init::add_log( 'Filter order IDs given specific IDs: ' . print_r( $order_ids, true ) );

			// Make sure the parameter is an array
			if ( ! is_array( $order_ids ) ) {
				$order_ids = array( (int) $order_ids );
			}

			// Get the TG order ids for each order. Skip the order that a TG order id is not found
			$i = 0;
			foreach( $order_ids as $id ) {
				if ( $tg_id = get_post_meta( $id, WC_TradeGecko_Init::$meta_prefix .'synced_order_id', true ) ) {
					$this->tg_order_ids[] = $tg_id;
					$this->tg_id_to_order_id_mapping[ $tg_id ] = $id;

					$i++;
				} else {
					$this->not_exported_order_ids[] = $id;
				}
			}
		} else {
			// Sync all paid exported orders ( Processing )
			$args = array(
				'numberposts'           => -1,
				'orderby'               => 'post_date',
				'order'                 => 'DESC',
				'post_type'             => 'shop_order',
				'post_status'           => 'publish' ,
				'suppress_filters'      => false,
				'tax_query'             => array(
				    array(
					    'taxonomy' => 'shop_order_status',
						    'terms' => apply_filters( 'wc_tradegecko_query_orders_status', array( 'processing' ) ),
						    'field' => 'slug',
						    'operator' => 'IN'
					    )
				)
			);
			$open_orders = get_posts( $args );

			// Filter each order and get the TG order id
			// If order is not exported save it and export it later
			$i = 0;
			foreach ( $open_orders as $open_order ) {
				if ( $tg_id = get_post_meta( $open_order->ID, WC_TradeGecko_Init::$meta_prefix .'synced_order_id', true ) ) {
					$this->tg_order_ids[] = $tg_id;
					$this->tg_id_to_order_id_mapping[ $tg_id ] = $open_order->ID;
				} else {
					$this->not_exported_order_ids[] = $open_order->ID;
				}

				$i++;
			}

		}
	}

	/**
	 * Get the product ID from the SKU
	 *
	 * @since 1.0
	 * @access private
	 * @global object $wpdb DB object
	 * @param string $sku The product SKU
	 * @return boolean|int The product ID or False
	 */
	private function get_product_by_sku( $sku ) {
		global $wpdb;
		$product =
			"SELECT post_id
			FROM   $wpdb->postmeta
			WHERE  meta_key = '_sku'
			AND    meta_value = %s";
		$product_id = $wpdb->get_var( $wpdb->prepare( $product, $sku ) );
		if ( $product_id ) {
			return $product_id;
		} else {
			return false;
		}
	}

	/**
	 * Get the product id from the TG variant id
	 * 
	 * @access public
	 * @since 1.0
	 * @global object $wpdb
	 * @param type $line_item_id
	 * @return boolean
	 */
	private function get_product_by_tg_id( $line_item_id ) {
		global $wpdb;
		$product =
			"SELECT post_id
			FROM   $wpdb->postmeta
			WHERE  meta_key = '%s'
			AND    meta_value = %s";
		$product_id = $wpdb->get_var( $wpdb->prepare( $product, WC_TradeGecko_Init::$meta_prefix .'variant_id', $line_item_id ) );
		if ( $product_id ) {
			return $product_id;
		} else {
			return false;
		}	
	}

	/**
	 * Map User meta array and return the first of each field.
	 * 
	 * @since 1.0
	 * @access public
	 * @param array $a
	 * @return mixed
	 */
	public function map_user_array ( $a ) { 
		return $a[0];
	}
        
} new WC_TradeGecko_Sync();