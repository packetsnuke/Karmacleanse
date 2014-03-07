<?php
/*
 * @package    AuthorizeNet Gateway for WooCoomerce
 * @subpackage AuthorizeNet DPM
 * 
 */

class WC_Authorize_DPM extends WC_Payment_Gateway {
	
	/**
	 * notify url
	 */
	var $notify_url;
	
	public function __construct() { 
		global $woocommerce;
		
        $this->id			= 'authorize_dpm';
        $this->icon 		=  apply_filters('woocommerce_authorize_dpm_icon', plugins_url('images/authorize-net-co.png', __FILE__));
        $this->has_fields 	= false;
		$this->method_title = "Authorize.Net DPM";
		
		// Load the form fields
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();

		// Get setting values
		//$this->enabled 		= $this->settings['enabled'];
		$this->title 		= $this->settings['title'];
		$this->description	= $this->settings['description'];
		$this->login_id		= $this->settings['login_id'];
		$this->tran_key		= $this->settings['tran_key'];
		$this->md5_hash		= $this->settings['md5_hash'];
		$this->type			= $this->settings['type'];
		$this->cvv			= $this->settings['cvv'];
		$this->shipping		= $this->settings['shipping'];
		$this->tran_mode	= $this->settings['tran_mode'];
		$this->testmode		= $this->settings['testmode'];
		$this->debug		= $this->settings['debug'];
		
		// Logs
		if ($this->debug=='yes') $this->log = $woocommerce->logger();
		
		$this->notify_url = home_url('/');;
		
		// Hooks
		if($this->enabled == 'yes') {
			add_action('wp_enqueue_scripts', array(&$this, 'add_enqueue_scripts'), 10);
			
			add_action( 'init', array(&$this, 'response_handler') );
			add_action('woocommerce_receipt_authorize_dpm', array(&$this, 'receipt_page'));
			//add_action('admin_notices', array(&$this,'ssl_check'));
		}
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) ) {
			add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
		} else {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_api_wc_authorize_dpm', array( $this, 'response_handler' ) );
			$this->notify_url   = add_query_arg('wc-api', 'WC_Authorize_DPM', $this->notify_url);
		}
    } 


	/**
 	* Check if SSL is enabled and notify the user
 	**/
	function ssl_check() {
	     
	     if (get_option('woocommerce_force_ssl_checkout')=='no' && $this->enabled=='yes') :
	     
	     	echo '<div class="error"><p>'.sprintf(__('Authorize.net DPM is enabled and the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'woocommerce'), admin_url('admin.php?page=woocommerce')).'</p></div>';
	     
	     endif;
	}
	
	/**
     * Initialize Gateway Settings Form Fields
     */
    function init_form_fields() {
    
    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woocommerce' ), 
							'label' => __( 'Enable Authorize.Net DPM Payment Module', 'woocommerce' ), 
							'type' => 'checkbox', 
							'description' => '', 
							'default' => 'no'
						), 
			'title' => array(
							'title' => __( 'Title' ), 
							'type' => 'text', 
							'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ), 
							'default' => __( 'Authorize.net DPM', 'woocommerce' ),
							'css' => "width: 300px;"
						), 
			'description' => array(
							'title' => __( 'Description', 'woocommerce' ), 
							'type' => 'textarea', 
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ), 
							'default' => 'Pay with your credit card via Authorize.net.'
						),  
			'login_id' => array(
							'title' => __( 'API Login ID', 'woocommerce' ), 
							'type' => 'text', 
							'description' => __( 'This is API Lgoin supplied by Authorize.', 'woocommerce' ), 
							'default' => ''
						), 
			'tran_key' => array(
							'title' => __( 'Transaction Key', 'woocommerce' ), 
							'type' => 'text', 
							'description' => __( 'This is Transaction Key supplied by Authorize.', 'woocommerce' ), 
							'default' => ''
						),
			'md5_hash' => array(
							'title' => __( 'MD5 Hash', 'woocommerce' ), 
							'type' => 'text', 
							'description' => __( 'The MD5 hash value to verify transactions', 'woocommerce' ), 
							'default' => ''
						),
			'type' => array(
							'title' => __( 'Sale Method', 'woocommerce' ), 
							'type' => 'select', 
							'description' => __( 'Select which sale method to use. Authorize Only will authorize the customers card for the purchase amount only.  Authorize &amp; Capture will authorize the customer\'s card and collect funds.', 'woocommerce' ), 
							'options' => array(
								'AUTH_CAPTURE'=>'Authorize &amp; Capture',
								'AUTH_ONLY'=>'Authorize Only'
							),
							'default' => 'AUTH_CAPTURE'
						),
			'cvv' => array(
							'title' => __( 'CVV', 'woocommerce' ), 
							'label' => __( 'Require customer to enter credit card CVV code', 'woocommerce' ), 
							'type' => 'checkbox', 
							'default' => 'yes'
						),
			'shipping' => array(
							'title' => __( 'Send shipping information', 'woocommerce' ), 
							'label' => __( 'Enable send shipping', 'woocommerce' ), 
							'type' => 'checkbox', 
							'description' => __('Store shipping in vault', 'woocommerce'), 
							'default' => 'no'
						),
			'tran_mode' => array(
							'title' => __( 'Transaction Mode', 'woocommerce' ), 
							'type' => 'select', 
							'description' => __( 'Transaction mode used for processing orders', 'woocommerce' ), 
							'options' => array('live'=>'Live', 'sandbox'=>'Sandbox'),
							'default' => 'live'
						),
			'testmode' => array(
							'title' => __( 'Authorize.Net test mode', 'woocommerce' ), 
							'label' => __( 'Test Mode allows you to submit test transactions to the payment gateway', 'woocommerce' ), 
							'type' => 'checkbox', 
							'description' => __( 'You may want to set to true if testing against production', 'woocommerce' ), 
							'default' => 'no'
						), 
			'debug' => array(
						'title' => __( 'Debug', 'woocommerce' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable logging (<code>woocommerce/logs/authorize_dpm.txt</code>)', 'woocommerce' ), 
						'default' => 'no'
					)
			);
    }
	
	
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 **/
	public function admin_options() {
?>
		<h3><?php _e('Authorize.Net DPM', 'woocommerce'); ?></h3>
    	<p><?php _e('Authorize.Net DPM works by sending the user to Authorize to enter their payment information.', 'woocommerce'); ?></p>
    	
    	<table class="form-table">
    		<?php $this->generate_settings_html(); ?>
		</table><!--/.form-table-->    	
<?php
    }
	
	/**
	 * Add script
	 */ 
	function add_enqueue_scripts(){
		wp_enqueue_style( 'authorize-dpm-style', plugins_url( 'style.css' , __FILE__ ));
	}
	    
    /**
	 * There are no payment fields for authorize_dpm, but we want to show the description if set.
	 **/
	function payment_fields() {
?>
		<?php if ($this->tran_mode=='sandbox') : ?><p><?php _e('TEST MODE/SANDBOX ENABLED', 'woocommerce'); ?></p><?php endif; ?>
		<?php if ($this->description) : ?><p><?php echo wpautop(wptexturize($this->description)); ?></p><?php endif; ?>
<?php
	}
	
	/**
	 * Get args for passing
	 **/
	function get_params( $order) {
		// Create request
		
		$params = array (
			"x_amount" 			=> $order->get_order_total(),
			"x_type" 			=> $this->type,
			"x_first_name" 		=> $order->billing_first_name,
			"x_last_name" 		=> $order->billing_last_name,
			"x_address" 		=> $order->billing_address_1,
			"x_city" 			=> $order->billing_city,
			"x_state" 			=> $order->billing_state,
			"x_zip" 			=> $order->billing_postcode,
			"x_country" 		=> $order->billing_country,
			"x_phone" 			=> $order->billing_phone,
			"x_email"			=> $order->billing_email,
			"x_cust_id" 		=> $order->user_id,
			"x_customer_ip" 	=> $this->get_user_ip(),
			"x_invoice_num" 	=> $order->id,
			"x_fp_sequence"		=> $order->order_key,
			"x_test_request" 	=> ($this->testmode == 'yes') ? 'TRUE' : 'FALSE',			
			'x_relay_response'	=> "TRUE",
            //'x_relay_url'     	=> add_query_arg('authorizeListenerDPM', 'relay', trailingslashit(get_permalink(woocommerce_get_page_id('cart')))),
            'x_relay_url'     	=> add_query_arg('authorizeListenerDPM', 'relay', $this->notify_url),
		);
		
		//Store shipping information
		if($this->shipping == 'yes') {
			$shipping = array(
				"x_ship_to_first_name" 		=> $order->shipping_first_name,
				"x_ship_to_last_name" 		=> $order->shipping_last_name,
				"x_ship_to_address" 		=> $order->shipping_address_1,
				"x_ship_to_city" 			=> $order->shipping_city,
				"x_ship_to_state" 			=> $order->shipping_state,
				"x_ship_to_zip" 			=> $order->shipping_postcode,
				"x_ship_to_country" 		=> $order->shipping_country,
				"x_ship_to_company" 		=> $order->shipping_company,
			);
			
			$params = wp_parse_args($params, $shipping);
		}
		
		return $params;
	}
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );
				
		// Return thank you redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
		);
	
	}
	
	/**
	 * Validate payment form fields
	**/
	public function validate_fields() {
		return true;
	}
	
	/**
	 * receipt_page
	 **/
	function receipt_page( $order_id ) {
		echo '<p>'.__('Thank you for your order.', 'woocommerce').'</p>';
		
		$order = new WC_Order( $order_id );
		$params = $this->get_params( $order );
		
		if ($this->debug=='yes') 
			$this->log->add( 'authorize_dpm', "Sending request:" . print_r($params,true));
		
		$request = new authorize_dpm_request($this->tran_mode, $this->md5_hash);
		
		$hidden_string = $request->get_hidden_string($this->login_id, $this->tran_key, $params);
		
		$url = $request->get_url();
?>
<form action="<?php echo $url ?>" method="post" id="authorize_dpm_payment_form">
	<?php echo $hidden_string; ?>
	<input id="x_exp_date" type="hidden" name="x_exp_date" value="" />
	<fieldset id="woo-gateway-authorize-dpm">
		<label for="x_card_num"><?php echo __("Credit Card number", 'woocommerce') ?> <span class="required">*</span></label>
		<p>
			<input type="text" class="input-text required creditcard valid" name="x_card_num" />
			<?php if ($this->cvv=='yes') { ?>
			<input type="text" class="input-text required number" name="x_card_code" maxlength="4" placeholder="<?php _e('CVV', 'woocommerce') ?>" style="width: 40px;" />
			<span class="help"><?php _e('3 or 4 digits.', 'woocommerce') ?></span>
			<?php } ?>
		</p>
		<div class="clear"></div>
		<label><?php echo __("Expiration date", 'woocommerce') ?> <span class="required">*</span></label>
		<p>
			<select id="authorize-dpm-cc-month" name="authorize_dpm_cc_month" class="woocommerce-select authorize-dpm-cc-month required">
				<option value=""><?php _e('Month', 'woocommerce') ?></option>
				<?php
					$months = array();
					for ($i = 1; $i <= 12; $i++) {
					    $timestamp = mktime(0, 0, 0, $i, 1);
					    $months[date('n', $timestamp)] = date('F', $timestamp);
					}
					foreach ($months as $num => $name) {
			            printf('<option value="%02d">%s</option>', $num, $name);
			        }
				?>
			</select>
			<select id="authorize-dpm-cc-year" name="authorize_dpm_cc_year" class="woocommerce-select authorize-dpm-cc-year required">
				<option value=""><?php _e('Year', 'woocommerce') ?></option>
				<?php for($y=date('Y');$y<=date('Y')+10;$y++){?>
		          <option value="<?php echo $y;?>"><?php echo $y;?></option>
		        <?php }?>
			</select>
		</p>
		<div class="clear"></div>
	</fieldset>
	<input type="submit" class="button button-alt" id="submit_authorize_dpm_payment_form" value="<?php _e('Pay via Authorize.net DPM', 'woocommerce') ?>" />
	<a class="button cancel" href="<?php echo $order->get_cancel_order_url() ?>"><?php _e('Cancel order &amp; restore cart', 'woocommerce') ?></a>
	<script type="text/javascript" src="<?php echo plugins_url( 'js/jquery.validate.min.js' , __FILE__ ) ?>"></script>
	<script type="text/javascript" src="<?php echo plugins_url( 'js/authrize.net.js' , __FILE__ ) ?>"></script>
</form>
<?php
	}
	
	/**
	 * Check response data
	 */
	public function response_handler() {
		global $woocommerce;
		
		if (isset($_GET['authorizeListenerDPM'])) {
			$hdl= $_GET['authorizeListenerDPM']; // handle value

			if($hdl == 'relay') {
				@ob_clean();
				
				$this->notify_url = get_permalink(woocommerce_get_page_id('cart'));
				
				if ($this->debug=='yes') { 
					$this->log->add( 'authorize_sim', "Relay response:" . print_r($_POST,true));
					$this->log->add( 'authorize_sim', "Login ID:" . $this->login_id . "; md5: " . $this->md5_hash);
				}
				
				$request = new authorize_dpm_request($this->tran_mode, $this->md5_hash);
				$response = $request->send(array('x_login'=> $this->login_id));

				if ($response->isAuthorizeNet()) {
					if ($response->approved) {
						$order_id = isset($_POST['x_invoice_num']) ? $_POST['x_invoice_num'] : '';
						if(!empty($order_id)) {
							$order = new WC_Order( $order_id );
													
							$order->add_order_note( __('Authorize.Net DPM Commerce payment completed', 'woocommerce') . ' (Transaction ID: ' . $response->transaction_id . ')' );
							
							if ($this->debug=='yes') 
								$this->log->add( 'authorize_dpm', 'Authorize.Net DPM Commerce payment completed (Transaction ID: ' . $response->transaction_id . ')');
							
							$order->payment_complete();
							$woocommerce->cart->empty_cart();
							
							$redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))));
						} else {
							if ($this->debug=='yes') 
								$this->log->add( 'authorize_dpm', 'Empty Order ID');
							
							$redirect = add_query_arg('authorizeListenerDPM', 'error', $this->notify_url);
							$redirect = add_query_arg('reason_text', __('Error: Empty Order ID', 'woocommerce'), $redirect); // add reeson text
						}
					} else {

						if ($this->debug=='yes') 
							$this->log->add( 'authorize_dpm', sprintf("Error %s: %s", $response->response_reason_code, $response->response_reason_text));
						
						$redirect = add_query_arg('authorizeListenerDPM', 'error', $this->notify_url);
						$redirect = add_query_arg('reason_code', $response->response_reason_code, $redirect); // add reeson code
						$redirect = add_query_arg('reason_text', $response->response_reason_text, $redirect); // add reeson text
						$redirect = add_query_arg('code', $response->response_code, $redirect); // add error code
						
					}

				} else {
					if ($this->debug=='yes') 
						$this->log->add( 'authorize_dpm', "MD5 Hash failed. Check to make sure your MD5 Setting matches the one in admin option");
					
					$redirect = add_query_arg('authorizeListenerDPM', 'error', $this->notify_url);
					$redirect = add_query_arg('reason_text', __("MD5 Hash failed. Check to make sure your MD5 Setting matches the one in admin option", "woocommerce"), $redirect); // add reeson text
				}
				
				$redirect = remove_query_arg('wc-api', $redirect);
				echo $request->get_response_snippet($redirect);
				exit;
			} else { // if error
				$message = $_REQUEST['reason_text'];
				$woocommerce->add_error($message);
			}
		}
	}
	
	
	/**
     * Get user's IP address
     */
	function get_user_ip() {
		if($_SERVER['SERVER_NAME'] == 'localhost') {
			return '127.0.0.1';
		}
		return $_SERVER['REMOTE_ADDR'];
	}
}