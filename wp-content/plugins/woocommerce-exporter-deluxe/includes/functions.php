<?php
if( is_admin() ) {

	/* Start of: WordPress Administration */

	/* WordPress Administration menu */
	function woo_cd_admin_menu() {

		if( !function_exists( 'woo_ce_admin_init' ) )
			add_submenu_page( 'woocommerce', __( 'Store Exporter Deluxe', 'woo_cd' ), __( 'Store Export', 'woo_cd' ), 'manage_options', 'woo_ce', 'woo_cd_html_page' );

	}
	add_action( 'admin_menu', 'woo_cd_admin_menu', 11 );

	function woo_cd_template_header( $title = '', $icon = 'tools' ) {

		global $woo_cd;

		if( $title )
			$output = $title;
		else
			$output = $woo_cd['menu'];
		$icon = woo_is_admin_icon_valid( $icon ); ?>
<div class="wrap">
	<div id="icon-<?php echo $icon; ?>" class="icon32"><br /></div>
	<h2><?php echo $output; ?></h2>
<?php
	}

	function woo_cd_template_footer() { ?>
</div>
<?php
	}

	function woo_cd_template_header_title() {

		$output = __( 'Store Exporter Deluxe', 'woo_cd' );
		return $output;

	}
	add_filter( 'woo_ce_template_header', 'woo_cd_template_header_title' );

	function woo_cd_orders_date() {

		$order_dates_from = woo_cd_get_order_first_date();
		$order_dates_to = date( 'd/m/Y' );

		$output = '
<tr>
	<th>
		<label for="delimiter">' . __( 'Order Dates', 'woo_ce' ) . '</label>
	</th>
	<td>
		<input type="text" size="10" maxlength="10" id="order_dates_from" name="order_dates_from" value="' . $order_dates_from . '" class="text datepicker" /> to <input type="text" size="10" maxlength="10" id="order_dates_to" name="order_dates_to" value="' . $order_dates_to . '" class="text datepicker" />
		<p class="description">' . __( 'Filter the dates of Orders to be included in the export. Default is the date of the first order to today.', 'woo_ce' ) . '</p>
	</td>
</tr>
';
		echo $output;

	}
	add_action( 'woo_ce_export_options', 'woo_cd_orders_date' );

	function woo_cd_get_order_first_date() {

		$output = date( 'd/m/Y', mktime( 0, 0, 0, date( 'n' ), 1 ) );
		$post_type = 'shop_order';
		$args = array(
			'post_type' => $post_type,
			'orderby' => 'post_date',
			'order' => 'ASC',
			'numberposts' => 1
		);
		$orders = get_posts( $args );
		if( $orders ) {
			$order = strtotime( $orders[0]->post_date );
			$output = date( 'd/m/Y', $order );
		}
		return $output;

	}

	function woo_cd_order_fields( $fields ) {

		$custom_orders = woo_ce_get_option( 'custom_orders' );
		if( $custom_orders ) {
			foreach( $custom_orders as $custom_order ) {
				$fields[] = array(
					'name' => $custom_order,
					'label' => $custom_order,
					'default' => 1
				);
			}
		}

		return $fields;

	}
	add_filter( 'woo_ce_order_fields', 'woo_cd_order_fields' );

	function woo_cd_get_orders( $export_type = 'orders', $export_args = array() ) {

		global $export;

		$limit_volume = -1;
		$offset = 0;
		if( $export_args ) {
			$limit_volume = $export_args['limit_volume'];
			$offset = $export_args['offset'];
			$export_args['order_dates_from'] = strtotime( $export_args['order_dates_from'] );
			$export_args['order_dates_to'] = strtotime( $export_args['order_dates_to'] );
		}
		$output = '';
		$post_type = 'shop_order';
		$args = array(
			'post_type' => $post_type,
			'numberposts' => $limit_volume,
			'offset' => $offset,
			'post_status' => woo_ce_post_statuses()
		);
		$orders = get_posts( $args );
		if( $orders ) {
			foreach( $orders as $key => $order ) {
				if( $export_args['order_dates_from'] && $export_args['order_dates_to'] ) {
					if( ( strtotime( $orders[$key]->post_date ) > $export_args['order_dates_from'] ) && ( strtotime( $orders[$key]->post_date ) < $export_args['order_dates_to'] ) ) {
						// Do nothing
					} else {
						unset( $orders[$key] );
						continue;
					}
				}
				$orders[$key]->purchase_total = get_post_meta( $order->ID, '_order_total', true );
				$orders[$key]->payment_status = woo_cd_get_order_status( $order->ID );
				$orders[$key]->user_id = get_post_meta( $order->ID, '_customer_user', true );
				$orders[$key]->user_name = woo_cd_get_username( $orders[$key]->user_id );

				$orders[$key]->billing_first_name = get_post_meta( $order->ID, '_billing_first_name', true );
				$orders[$key]->billing_last_name = get_post_meta( $order->ID, '_billing_last_name', true );
				$orders[$key]->billing_full_name = $order->billing_first_name . ' ' . $order->billing_last_name;
				$orders[$key]->billing_company = get_post_meta( $order->ID, '_billing_company', true );
				$orders[$key]->billing_address = get_post_meta( $order->ID, '_billing_address_1', true );
				$orders[$key]->billing_address_alt = get_post_meta( $order->ID, '_billing_address_2', true );
				if( $order->billing_address_alt )
					$orders[$key]->billing_address .= ' ' . $order->billing_address_alt;
				$orders[$key]->billing_city = get_post_meta( $order->ID, '_billing_city', true );
				$orders[$key]->billing_postcode = get_post_meta( $order->ID, '_billing_postcode', true );
				$orders[$key]->billing_state = get_post_meta( $order->ID, '_billing_state', true );
				$orders[$key]->billing_country = get_post_meta( $order->ID, '_billing_country', true );
				$orders[$key]->billing_state_full = woo_ce_expand_state_name( $orders[$key]->billing_country, $orders[$key]->billing_state );
				$orders[$key]->billing_country_full = woo_ce_expand_country_name( $orders[$key]->billing_country );
				$orders[$key]->billing_phone = get_post_meta( $order->ID, '_billing_phone', true );
				$orders[$key]->billing_email = get_post_meta( $order->ID, '_billing_email', true );
				$orders[$key]->shipping_first_name = get_post_meta( $order->ID, '_shipping_first_name', true );
				$orders[$key]->shipping_last_name = get_post_meta( $order->ID, '_shipping_last_name', true );
				$orders[$key]->shipping_full_name = $order->shipping_first_name . ' ' . $order->shipping_last_name;
				$orders[$key]->shipping_company = get_post_meta( $order->ID, '_shipping_company', true );
				$orders[$key]->shipping_address = get_post_meta( $order->ID, '_shipping_address_1', true );
				$orders[$key]->shipping_address_alt = get_post_meta( $order->ID, '_shipping_address_2', true );
				if( $order->shipping_address_alt )
					$orders[$key]->shipping_address .= ' ' . $order->shipping_address_alt;
				$orders[$key]->shipping_city = get_post_meta( $order->ID, '_shipping_city', true );
				$orders[$key]->shipping_postcode = get_post_meta( $order->ID, '_shipping_postcode', true );
				$orders[$key]->shipping_state = get_post_meta( $order->ID, '_shipping_state', true );
				$orders[$key]->shipping_country = get_post_meta( $order->ID, '_shipping_country', true );
				$orders[$key]->shipping_state_full = woo_ce_expand_state_name( $orders[$key]->shipping_country, $orders[$key]->shipping_state );
				$orders[$key]->shipping_country_full = woo_ce_expand_country_name( $orders[$key]->shipping_country );
				$orders[$key]->shipping_phone = get_post_meta( $order->ID, '_shipping_phone', true );
				switch( $export_type ) {

					case 'orders':
						// Order
						$orders[$key]->purchase_id = $order->ID;
						$orders[$key]->order_discount = get_post_meta( $order->ID, '_order_discount', true );
						$orders[$key]->order_shipping_tax = get_post_meta( $order->ID, '_order_shipping_tax', true );
						$orders[$key]->payment_gateway = get_post_meta( $order->ID, '_payment_method', true );
						$orders[$key]->shipping_method = get_post_meta( $order->ID, '_shipping_method', true );
						$orders[$key]->order_key = get_post_meta( $order->ID, '_order_key', true );
						$orders[$key]->purchase_date = mysql2date( 'd/m/Y H:i:s', $order->post_date );
						$orders[$key]->customer_note = $order->post_excerpt;
						$args = array(
							'post_id' => $order->ID,
							'approve' => 'approve',
							'comment_type' => 'order_note'
						);
						$order_notes = get_comments( $args );
						$orders[$key]->order_notes = '';
						if( $order_notes ) {
							foreach( $order_notes as $order_note ) {
								$orders[$key]->order_notes .= $order_note->comment_content . $export->category_separator;
							}
							$orders[$key]->order_notes = $substr( $orders[$key]->order_notes, 0, -1 );
						} 
						if( $orders[$key]->order_items = woo_cd_get_order_items( $order->ID ) ) {
							$orders[$key]->order_items_sku = '';
							$orders[$key]->order_items_name = '';
							$orders[$key]->order_items_quantity = '';
							foreach( $orders[$key]->order_items as $order_item ) {
								if( empty( $order_item->sku ) )
									$order_item->sku = '-';
								$orders[$key]->order_items_sku .= $order_item->sku . $export->category_separator;
								$orders[$key]->order_items_name .= $order_item->name . $export->category_separator;
								if( empty( $order_item->quantity ) )
									$order_item->quantity = '-';
								$orders[$key]->order_items_quantity .= $order_item->quantity . $export->category_separator;
							}
							$orders[$key]->order_items_sku = substr( $orders[$key]->order_items_sku, 0, -1 );
							$orders[$key]->order_items_name = substr( $orders[$key]->order_items_name, 0, -1 );
							$orders[$key]->order_items_quantity = substr( $orders[$key]->order_items_quantity, 0, -1 );
						}

						// Custom
						$custom_orders = woo_ce_get_option( 'custom_orders' );
						if( $custom_orders ) {
							foreach( $custom_orders as $custom_order )
								$orders[$key]->$custom_order = get_post_meta( $order->ID, $custom_order, true );
						}
						break;

				}
			}
		}
		if( $export_type == 'customers' ) {
			$customers = array();
			foreach( $orders as $key => $order ) {
				if( $duplicate_key = woo_cd_duplicate_customer( $customers, $order ) ) {
					$customers[$duplicate_key]->total_spent = $customers[$duplicate_key]->total_spent + $order->purchase_total;
					$customers[$duplicate_key]->total_orders++;
					if( $order->payment_status == 'completed' )
						$customers[$duplicate_key]->completed_orders++;
				} else {
					$customers[$order->ID] = $order;
					$customers[$order->ID]->total_spent = $order->purchase_total;
					$customers[$order->ID]->completed_orders = 0;
					if( $order->payment_status == 'completed' )
						$customers[$order->ID]->completed_orders = 1;
					$customers[$order->ID]->total_orders = 1;
				}
			}
			$output = $customers;
		} else {
			$output = $orders;
		}
		return $output;

	}

	function woo_cd_get_username( $user_id = 0 ) {

		$output = '';
		if( $user_id ) {
			$user = get_userdata( $user_id );
			if( $user )
				$output = $user->user_login;
		}
		return $output;

	}

	function woo_ce_expand_state_name( $country_prefix, $state_prefix ) {

		$output = $state_prefix;
		if( $output ) {
			$countries = new WC_Countries();
			$states = $countries->get_states( $country_prefix );
			if( $state = $states[$state_prefix] )
				$output = $state;
		}
		return $output;

	}

	function woo_ce_expand_country_name( $country_prefix ) {

		$output = $country_prefix;
		if( $output ) {
			$countries = new WC_Countries();
			if( $country = $countries->countries[$country_prefix] )
				$output = $country;
		}
		return $output;

	}

	function woo_cd_get_order_items( $order_id = 0 ) {

		global $wpdb;

		$output = array();
		if( $order_id ) {
			$order_items_sql = sprintf( "SELECT `order_item_id` as id, `order_item_name` as name FROM `" . $wpdb->prefix . "woocommerce_order_items` WHERE `order_id` = '%d'", $order_id );
			$order_items = $wpdb->get_results( $order_items_sql );
			if( $order_items ) {
				foreach( $order_items as $key => $order_item ) {
					$qty_sql = sprintf( "SELECT `meta_value` FROM `" . $wpdb->prefix . "woocommerce_order_itemmeta` WHERE `order_item_id` = '%d' AND `meta_key` = '_qty' LIMIT 1", $order_item->id );
					$order_items[$key]->quantity = $wpdb->get_var( $qty_sql );
					$product_id_sql = sprintf( "SELECT `meta_value` FROM `" . $wpdb->prefix . "woocommerce_order_itemmeta` WHERE `order_item_id` = '%d' AND `meta_key` = '_product_id' LIMIT 1", $order_item->id );
					$order_items[$key]->product_id = $wpdb->get_var( $product_id_sql );
					$order_items[$key]->sku = get_post_meta( $order_items[$key]->product_id, '_sku', true );
				}
				$output = $order_items;
			}
		}
		return $output;

	}

	function woo_cd_duplicate_customer( $customers = array(), $order = array() ) {

		foreach( $customers as $key => $customer ) {
			if( $customer->user_id == $order->user_id || $customer->billing_email == $order->billing_email ) {
				return $key;
				break;
			}
		}
		return 0;

	}

	function woo_cd_get_order_status( $order_id ) {

		global $export;

		$output = '';
		$term_taxonomy = 'shop_order_status';
		$status = wp_get_object_terms( $order_id, $term_taxonomy );
		if( $status ) {
			$size = count( $status );
			for( $i = 0; $i < $size; $i++ ) {
				$term = get_term( $status[$i]->term_id, $term_taxonomy );
				if( $term )
					$output .= $term->name . $export->category_separator;
			}
			$output = substr( $output, 0, -1 );
		}
		return $output;

	}

	function woo_cd_get_coupons() {

		$output = '';
		$post_type = 'shop_coupon';
		$args = array(
			'post_type' => $post_type,
			'numberposts' => -1,
			'post_status' => woo_ce_post_statuses()
		);
		$coupons = get_posts( $args );
		if( $coupons ) {
			foreach( $coupons as $key => $coupon ) {
				$coupons[$key]->coupon_code = $coupon->post_title;
				$coupons[$key]->discount_type = woo_cd_format_discount_type( get_post_meta( $coupon->ID, 'discount_type', true ) );
				$coupons[$key]->coupon_description = $coupon->post_excerpt;
				$coupons[$key]->coupon_amount = get_post_meta( $coupon->ID, 'coupon_amount', true );
				$coupons[$key]->individual_use = woo_ce_format_switch( get_post_meta( $coupon->ID, 'individual_use', true ) );
				$coupons[$key]->apply_before_tax = woo_ce_format_switch( get_post_meta( $coupon->ID, 'apply_before_tax', true ) );
				$coupons[$key]->exclude_sale_items = woo_ce_format_switch( get_post_meta( $coupon->ID, 'exclude_sale_items', true ) );
				$coupons[$key]->minimum_amount = get_post_meta( $coupon->ID, 'minimum_amount', true );
				$coupons[$key]->product_ids = woo_cd_convert_product_ids( get_post_meta( $coupon->ID, 'product_ids', true ) );
				$coupons[$key]->exclude_product_ids = woo_cd_convert_product_ids( get_post_meta( $coupon->ID, 'exclude_product_ids', true ) );
				$coupons[$key]->product_categories = woo_cd_convert_product_ids( get_post_meta( $coupon->ID, 'product_categories', true ) );
				$coupons[$key]->exclude_product_categories = woo_cd_convert_product_ids( get_post_meta( $coupon->ID, 'exclude_product_categories', true ) );
				$coupons[$key]->customer_email = woo_cd_convert_product_ids( get_post_meta( $coupon->ID, 'customer_email', true ) );
				$coupons[$key]->usage_limit = get_post_meta( $coupon->ID, 'usage_limit', true );
				$coupons[$key]->expiry_date = get_post_meta( $coupon->ID, 'expiry_date', true );
				if( $coupons[$key]->expiry_date )
					$coupons[$key]->expiry_date = mysql2date( 'd/m/Y', $coupons[$key]->expiry_date );
			}
			$output = $coupons;
		}
		return $output;

	}

	function woo_cd_export_dataset( $datatype = null, $export = null ) {

		global $wpdb, $woo_cd, $export;

		include_once( 'formatting.php' );

		$csv = '';
		$separator = $export->delimiter;
		switch( $datatype ) {

			case 'orders':
				$fields = woo_ce_get_order_fields( 'summary' );
				$export->fields = array_intersect_assoc( $fields, $export->fields );
				if( $export->fields ) {
					foreach( $export->fields as $key => $field )
						$export->columns[] = woo_ce_get_order_field( $key );
				}
				$size = count( $export->columns );
				for( $i = 0; $i < $size; $i++ ) {
					if( $i == ( $size - 1 ) )
						$csv .= '"' . $export->columns[$i] . "\"\n";
					else
						$csv .= '"' . $export->columns[$i] . '"' . $separator;
				}
				$orders = woo_cd_get_orders( 'orders', $export->args );
				if( $orders ) {
					foreach( $orders as $order ) {
						foreach( $export->fields as $key => $field ) {
							if( isset( $order->$key ) ) {
								if( is_array( $value ) ) {
									foreach( $value as $array_key => $array_value ) {
										if( !is_array( $array_value ) )
											$csv .= escape_csv_value( $array_value );
									}
								} else {
									$csv .= escape_csv_value( $order->$key );
								}
							}
							$csv .= $separator;
						}
						$csv .= "\n";
					}
					unset( $orders, $order );
				}
				break;

			case 'customers':
				$fields = woo_ce_get_customer_fields( 'summary' );
				$export->fields = array_intersect_assoc( $fields, $export->fields );
				if( $export->fields ) {
					foreach( $export->fields as $key => $field )
						$export->columns[] = woo_ce_get_customer_field( $key );
				}
				$size = count( $export->columns );
				for( $i = 0; $i < $size; $i++ ) {
					if( $i == ( $size - 1 ) )
						$csv .= escape_csv_value( $export->columns[$i] ) . "\n";
					else
						$csv .= escape_csv_value( $export->columns[$i] ) . $separator;
				}
				$customers = woo_cd_get_orders( 'customers', $export->args );
				if( $customers ) {
					foreach( $customers as $customer ) {
						foreach( $export->fields as $key => $field ) {
							if( isset( $customer->$key ) )
								$csv .= escape_csv_value( $customer->$key );
							$csv .= $separator;
						}
						$csv .= "\n";
					}
				}
				break;

			case 'coupons':
				$fields = woo_ce_get_coupon_fields( 'summary' );
				$export->fields = array_intersect_assoc( $fields, $export->fields );
				if( $export->fields ) {
					foreach( $export->fields as $key => $field )
						$export->columns[] = woo_ce_get_coupon_field( $key );
				}
				$size = count( $export->columns );
				for( $i = 0; $i < $size; $i++ ) {
					if( $i == ( $size - 1 ) )
						$csv .= escape_csv_value( $export->columns[$i] ) . "\n";
					else
						$csv .= escape_csv_value( $export->columns[$i] ) . $separator;
				}
				$coupons = woo_cd_get_coupons();
				if( $coupons ) {
					foreach( $coupons as $coupon ) {
						foreach( $export->fields as $key => $field ) {
							if( isset( $coupon->$key ) )
								$csv .= escape_csv_value( $coupon->$key );
							$csv .= $separator;
						}
						$csv .= "\n";
					}
					unset( $coupons, $coupon );
				}
				break;

		}
		return $csv;

	}
	add_filter( 'woo_ce_export_dataset', 'woo_cd_export_dataset', 10, 2 );

	/* End of: WordPress Administration */

}
?>