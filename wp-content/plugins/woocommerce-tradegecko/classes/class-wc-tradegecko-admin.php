<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class WC_TradeGecko_Sync
 * Class to handle all sync actions. 
 * 
 * @since 1.0
 */
class WC_TradeGecko_Admin {

	public function __construct() {
		
		$this->client_id	= WC_TradeGecko_Init::get_setting( 'client_id' );
		$this->client_secret	= WC_TradeGecko_Init::get_setting( 'client_secret' );
		$this->redirect_uri	= WC_TradeGecko_Init::get_setting( 'redirect_uri' );
		$this->auth_code	= WC_TradeGecko_Init::get_setting( 'auth_code' );

		// Add meta box
		add_action( 'add_meta_boxes', array( $this, 'wc_tradegecko_meta_boxes' ) );

		// Save meta box
		add_action('woocommerce_process_shop_order_meta', array( $this, 'wc_tradegecko_save_meta_boxes' ), 10, 2 );

		// Show Shipping and tracking to View Order and Tracking pages
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'show_shipping_tracking_to_view_and_track_order_pages' ) );

		// Add the order actions, only if all credentials are filled in
		if ( $this->client_id && $this->client_secret && $this->redirect_uri && $this->auth_code ) {
			// Add AJAX order actions to the orders panel 
			add_action( 'woocommerce_admin_order_actions_end', array( $this, 'add_order_actions' ) );

			// Add Sync with TradeGecko order action
			add_filter( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_actions' ) );
		}
		
		// Add Product Icon in the Product List Table
		add_filter( 'manage_edit-product_columns', array( $this, 'add_synced_product_column_title'), 20 );
		add_action( 'manage_product_posts_custom_column', array( $this, 'add_synced_product_column_content'), 10, 2 );
		
		// Add notification next to the SKU field
		add_action( 'woocommerce_product_options_sku', array( $this, 'add_product_sku_notification' ) );
		
	}

	/**
	 * Add the TG info meta box
	 * 
	 * @access public
	 * @since 1.0
	 */
	public function wc_tradegecko_meta_boxes() {
		add_meta_box( 
			'tradegecko-fulfillment-details',
			__('TradeGecko Fulfillment Details', WC_TradeGecko_Init::$text_domain ),
			array( $this, 'wc_tradegecko_fulfillment_details_meta_box' ),
			'shop_order',
			'normal',
			'default'
		);
	 }

	/**
	 * Save the metabox info
	 * 
	 * @access public
	 * @since 1.0
	 * @param type $post_id
	 * @param type $post
	 */ 
	public function wc_tradegecko_save_meta_boxes( $post_id, $post ) {

		$ff_data = $_POST[ WC_TradeGecko_Init::$meta_prefix .'order_fulfillment' ];

		if ( $ff_data ) {
			update_post_meta( 
				$post_id,
				WC_TradeGecko_Init::$meta_prefix .'order_fulfillment',
				$_POST[ WC_TradeGecko_Init::$meta_prefix .'order_fulfillment' ]
			);
		}

	}

	/**
	 * Init the meta box to the Admin Order page
	 *
	 * @access public
	 * @since 1.0
	 * @param mixed $post Current Post data
	 * @return void
	 */
	public function wc_tradegecko_fulfillment_details_meta_box($post) {
		$ff_data = get_post_meta($post->ID, WC_TradeGecko_Init::$meta_prefix .'order_fulfillment' );

	?>
		<div class="totals_group">
		<h4><?php _e('TradeGecko Fulfillment Details', WC_TradeGecko_Init::$text_domain); ?></h4>
	<?php	
		if ( ! empty( $ff_data ) ) {

			foreach ( $ff_data[0] as $key => $data ) {

				if ( ! empty( $data['products'] ) ) {
				?>
					<h4><?php _e('Shipped Products:', WC_TradeGecko_Init::$text_domain); ?></h4>
				<?php
					$i = 0;
					foreach ( $data['products'] as $id ) {

						$product = get_product( $id );
					?>	
						<p><?php echo $product->get_title() ?></p>
						<input type="hidden" 
						id="wc_tradegecko_products" 
						name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][products][]'; ?>" 
						value="<?php if ( isset( $data['products'][$i] ) ) echo $data['products'][$i]; ?>"
						class="first" />
					<?php
					$i++;
					}
				}
			?>
			<ul class="totals">
				<li class="left">
					<label><?php _e('Shipped At:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('The time and date the order was shipped at.', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text" 
					       id="wc_tradegecko_shipped_at" 
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][shipped_at]'; ?>" 
					       placeholder="<?php _e('2013-01-09T00:00:00Z', WC_TradeGecko_Init::$text_domain); ?>"  
					       value="<?php if ( isset( $data['shipped_at'] ) ) echo $data['shipped_at']; ?>"
					       class="first" />
				</li>

				<li class="right">
					<label><?php _e('Received At:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('The time and date the order was received at.', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text" 
					       id="wc_tradegecko_received_at" 
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][received_at]'; ?>" 
					       placeholder="<?php _e('2013-01-09T00:00:00Z', WC_TradeGecko_Init::$text_domain); ?>"  
					       value="<?php if ( isset( $data['received_at'] ) ) echo $data['received_at']; ?>"
					       class="first" />
				</li>

				<li class="left">
					<label><?php _e('Tracking Number:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('The shippment order tracking number, if provided.', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text" 
					       id="wc_tradegecko_tracking_number" 
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][tracking_number]'; ?>" 
					       placeholder="<?php _e('Tracking Number', WC_TradeGecko_Init::$text_domain); ?>"  
					       value="<?php if ( isset( $data['tracking_number'] ) ) echo $data['tracking_number']; ?>" 
					       class="first" />
				</li>
				<li class="right">
					<label><?php _e('Delivery Type:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('The type of the delivery service (ie: Courier, Pickup).', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text" 
					       id="wc_tradegecko_delivery_type" 
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][delivery_type]'; ?>" 
					       placeholder="<?php _e('Delivery Type', WC_TradeGecko_Init::$text_domain); ?>"  
					       value="<?php if ( isset( $data['delivery_type'] ) ) echo $data['delivery_type']; ?>" 
					       class="first" />
				</li>
				<li class="wide">
					<label><?php _e('Tracking Message:', WC_TradeGecko_Init::$text_domain); ?><a class="tips" data-tip="<?php _e('Informational message about the order tracking.', WC_TradeGecko_Init::$text_domain); ?>" href="#">[?]</a></label>
					<input type="text" 
					       id="wc_tradegecko_tracking_message" 
					       name="<?php echo WC_TradeGecko_Init::$meta_prefix .'order_fulfillment['.$key.'][tracking_message]'; ?>" 
					       placeholder="<?php _e('Tracking Message', WC_TradeGecko_Init::$text_domain); ?>"  
					       value="<?php if ( isset( $data['tracking_message'] ) ) echo $data['tracking_message']; ?>" 
					       class="first" />
				</li>
			</ul>
	<?php
			}
		} else {
		?>
			<p>There is no Fulfillment data, yet.</p>
		<?php	
		}
		?>
		</div>
	<?php
	}

	/**
	 * Show the shipping info to the view order and track your order pages
	 * 
	 * @access public
	 * @since 1.0
	 * @param type $order
	 */
	public function show_shipping_tracking_to_view_and_track_order_pages( $order ) {
		$ff_data = get_post_meta($order->id, WC_TradeGecko_Init::$meta_prefix .'order_fulfillment' );

		if ( ! empty( $ff_data ) ) {
			$k = 1;
			foreach ( $ff_data[0] as $key => $data ) {
			?>
			<header>
				<h2><?php _e('Shipping Details '. $k, WC_TradeGecko_Init::$text_domain); ?></h2>
			</header>

			<dl class="customer_details">
			<?php
			if ( ! empty( $data['products'] ) ) {

				echo '<dt>'. __( 'Products Shipped:', WC_TradeGecko_Init::$text_domain ) .'</dt><dd>';

				$prod_titles = '';
				$i = 0;
				foreach ( $data['products'] as $id ) {

					$product = get_product( $id );
					$prod_titles .= $product->get_title() .', ';

				$i++;
				}
				$prod_titles = substr($prod_titles, 0, -2 );

				echo $prod_titles.'</dd>';
			}
			?>
			<?php
				if ( $data['shipped_at'] ) {
					echo '<dt>'.apply_filters( 'wc_tradegecko_shipped_at_label', __( 'Order Shipped at:', WC_TradeGecko_Init::$text_domain ) ).'</dt><dd>'. $data['shipped_at'] .'</dd>';
				}
				if ( $data['tracking_number'] ) {
					echo '<dt>'.apply_filters( 'wc_tradegecko_tracking_number_label', __( 'Tracking Number:', WC_TradeGecko_Init::$text_domain ) ).'</dt><dd>'. $data['tracking_number'] .'</dd>';
				}
				if ( $data['delivery_type'] ) {
					echo '<dt>'.apply_filters( 'wc_tradegecko_delivery_type_label', __( 'Delivery Type:', WC_TradeGecko_Init::$text_domain ) ).'</dt><dd>'. $data['delivery_type'] .'</dd>';
				}
				if ( $data['tracking_message'] ) {
					echo '<dt>'.apply_filters( 'wc_tradegecko_tracking_message_label', __( 'Tracking Message:', WC_TradeGecko_Init::$text_domain ) ).'</dt><dd>'. $data['tracking_message'] .'</dd>';
				}
			?>
			</dl>
		<?php
				$k++;
			}
		}
	}

	/**
	 * Add Sync to TradeGecko action in the orders panel
	 * 
	 * @access public
	 * @since 1.0
	 * @param object $order
	 */
	public function add_order_actions( $order ) {

		$is_synced = get_post_meta( $order->id, WC_TradeGecko_Init::$meta_prefix .'synced_order_id', true );


		echo sprintf( '<a class="button tips %s" id="wc_tradegecko_sync_action_button" href="%s" data-tip="%s"><img src="%s" alt="%s" class="wc_tradegecko_sync_icon" /></a>',
			($is_synced) ? 'synced' : '',
			wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_tradegecko_update_order&order_id=' . $order->id ), 'wc_tradegecko_sync_order' ),
			( $is_synced ) ? __( 'Update from TradeGecko', WC_TradeGecko_Init::$text_domain ) : __( 'Export to TradeGecko', WC_TradeGecko_Init::$text_domain ),
			WC_TradeGecko_Init::$plugin_url . 'assets/images/wc-tradegecko-sync-icon.png',
			( $is_synced ) ? 'Update' : 'Export'
		);
	}

	/**
	 * Add a Sync with TradeGecko action to the order settings.
	 * 
	 * @access public
	 * @since 1.0
	 * @global type $theorder
	 * @param array $actions The actions to be set to the order settings page
	 * @return array
	 */
	public function add_order_meta_box_actions( $actions ) {
		global $theorder;

		$is_synced = get_post_meta( $theorder->id, WC_TradeGecko_Init::$meta_prefix .'synced_order_id', true );

		$actions['wc_tradegecko_update_order'] = ( $is_synced ) ? __( 'Update from TradeGecko', WC_TradeGecko_Init::$text_domain ) : __( 'Export to TradeGecko', WC_TradeGecko_Init::$text_domain );

		return $actions;
	}
	
	/*=======================================================
	 * Product List Table View - Add Synced Product Column
	 ========================================================*/
	
	/**
	 * Add the Title in the List Table
	 * 
	 * @since 1.0.1
	 * @param type $columns
	 * @return string
	 */
	public function add_synced_product_column_title ( $columns ) {

		$new_columns = array();

		foreach( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'date' == $key ) {
				$new_columns[ 'wc-tg-synced' ] = '<span class="tips" data-tip="' . __('Products Synced with TradeGecko', WC_TradeGecko_Init::$text_domain) . '">' . __( 'Synced' , WC_TradeGecko_Init::$text_domain ) . '</span>';
			}
		}

		return $new_columns;
	}

	/**
	 * Add Product Synced Icon in the List Table
	 * 
	 * @since 1.0.1
	 * @param type $column_name
	 * @param type $post_id
	 */
	public function add_synced_product_column_content( $column_name, $post_id ) {

		switch ( $column_name ) {

			case 'wc-tg-synced' :

				$is_synced = false;
				$attention = false;
				
				// Get the product object
				$product = get_product( $post_id );
				
				if ( 'variable' == $product->product_type ) {
					
					$variations = $product->get_available_variations();
					
					foreach ( $variations as $variation ) {
						$tg_variant_id = get_post_meta( $variation['variation_id'], WC_TradeGecko_Init::$meta_prefix .'variant_id', true );
						
						// If we have a single variant synced then show the whole product as synced
						if ( ! empty( $tg_variant_id ) ) {
							$is_synced = true;
						} else {
							$attention = true;
						}
						
						// We have both of our conditions
						if ( $is_synced && $attention ) {
							break;
						}
						
					}
					
				} else {
					
					$tg_variant_id = get_post_meta( $post_id, WC_TradeGecko_Init::$meta_prefix .'variant_id', true );
						
					if ( ! empty( $tg_variant_id ) ) {
						$is_synced = true;
					} else {
						$attention = true;
					}
					
				}
				
				echo sprintf( '<span class="tips %s" id="wc_tradegecko_sync_product" data-tip="%s"><img src="%s" alt="%s" class="wc_tradegecko_sync_icon" /></span>',
					( $is_synced ) ? 'synced' : 'not_synced',
					( $is_synced ) ? ( $attention ) ? __( 'Synced but not all variations', WC_TradeGecko_Init::$text_domain ) : __( 'Synced', WC_TradeGecko_Init::$text_domain ) : __( 'Not Synced', WC_TradeGecko_Init::$text_domain ),
					WC_TradeGecko_Init::$plugin_url . 'assets/images/wc-tradegecko-product.png',
					( $is_synced ) ? ( $attention ) ? __( 'Synced but not all variations', WC_TradeGecko_Init::$text_domain ) : __( 'Synced', WC_TradeGecko_Init::$text_domain ) : __( 'Not Synced', WC_TradeGecko_Init::$text_domain )
				);

			break;

		}

	}
	
	/**
	 * Check if the SKU is used and used only ones in TG.
	 * 
	 * @global type $post
	 * @since 1.0.1
	 */
	public function add_product_sku_notification() {
		global $post;
		
		$sku = get_post_meta( $post->ID, '_sku', true );
		$product = get_product( $post->ID );
		
		// For variable products the parent product is not required to be synced
		if ( 'variable' != $product->product_type ) {
			
			try {
		
				if ( empty( $sku ) ) {
					echo '<span class="sku_warning">'. __( 'Warning: SKU is Required to sync the product with TG.', WC_TradeGecko_Init::$text_domain) .'</span>';
				} else {
					$tg_sku = json_decode( WC_TradeGecko_Init::$api->process_api_request( 'GET', 'variants', null, null, array( 'sku' => $sku ) ) );

					if ( empty( $tg_sku->variants ) ) {
						echo '<span class="sku_warning">'. sprintf( __( 'Warning: Product with SKU %s does not exist in TradeGecko', WC_TradeGecko_Init::$text_domain), $sku ) .'</span>';
					} elseif ( 1 < count( $tg_sku->variants ) ) {
						echo '<span class="sku_warning">'. sprintf( __( 'Warning: There are more than one matches of this SKU ( %s ) in the TradeGecko system, please make sure SKUs are unique.', WC_TradeGecko_Init::$text_domain), $sku ) .'</span>';
					}
				}
				
			} catch( Exception $e ) {

				echo '<span class="sku_warning">'. sprintf( __( 'We could not obtain connection to TradeGecko System. Please check the %sTradeGecko Sync Log%s for more information.', WC_TradeGecko_Init::$text_domain), '<a href="'. admin_url( 'admin.php?page=tradegecko&tab=sync-log' ) .'" >', '</a>' ) .'</span>';

			}
		
		}
		
	}

} new WC_TradeGecko_Admin();
