<?php

    /* 
    Plugin Name: NMI Gateway for WooCommerce
    Plugin URI: http://www.patsatech.com 
    Description: WooCommerce Plugin for accepting payment through NMI Gateway.
    Author: PatSaTECH
    Version: 1.6 
    Author URI: http://www.patsatech.com 
    */  

add_action('plugins_loaded', 'init_woocommerce_nmipay', 0);
 
function init_woocommerce_nmipay() {
 
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
class woocommerce_nmipay extends WC_Payment_Gateway {


    protected $acceptableCards	= null;
		
	public function __construct() { 
		global $woocommerce;
		
        $this->id			= 'nmipay';
        $this->method_title = __( 'Network Merchants Gateway', 'woocommerce' );
        $this->icon 		= plugins_url() . '/woonmipay/nmipay.png';
        $this->has_fields 	= false;
        $this->liveurl 		= 'https://secure.networkmerchants.com/api/transact.php';
        
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
		$this->title 		= $this->settings['title'];
		$this->description 	= $this->settings['description'];
		$this->username 	= $this->settings['username'];
		$this->password		= $this->settings['password'];
				
		// Actions
		add_action('woocommerce_receipt_nmi', array(&$this, 'receipt_page'));
		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
		
		if ( !$this->is_valid_for_use() ) $this->enabled = false;
    } 
    
     /**
     * Check if this gateway is enabled and available in the user's country
     */
    function is_valid_for_use() {
        if (!in_array(get_option('woocommerce_currency'), array('AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP'))) return false;

        return true;
    }
    
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e('NMI Pay', 'woothemes'); ?></h3>
    	<p><?php _e('NMI works by processing the Credit Card Payments on your site without enter their payment information.', 'woothemes'); ?></p>
    	<table class="form-table">
    	<?php
    		if ( $this->is_valid_for_use() ) :
    	
    			// Generate the HTML For the settings form.
    			$this->generate_settings_html();
    		
    		else :
    		
    			?>
            		<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woothemes' ); ?></strong>: <?php _e( 'NMI does not support your store currency.', 'woothemes' ); ?></p></div>
        		<?php
        		
    		endif;
    	?>
		</table><!--/.form-table-->
    	<?php
    } // End admin_options()
    
	/**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
    
    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woothemes' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable NMI Payment', 'woothemes' ), 
							'default' => 'yes'
						), 
			'title' => array(
							'title' => __( 'Title', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
							'default' => __( 'Network Merchants Gateway', 'woothemes' )
						),
			'description' => array(
							'title' => __( 'Description', 'woothemes' ), 
							'type' => 'textarea', 
							'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ), 
							'default' => __("Pay via NMI; you can pay with your credit card.", 'woothemes')
						),
			'username' => array(
							'title' => __( 'NMI UserName', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your NMI UserName; this is needed in order to take payment.', 'woothemes' ), 
							'default' => ''
						),
			'password' => array(
							'title' => __( 'NMI Password', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your NMI Password; this is needed in order to take payment.', 'woothemes' ), 
							'default' => ''
						)
			);
    
    } // End init_form_fields()
    
    /**
	 * There are no payment fields for nmi, but we want to show the description if set.
	 **/
    function payment_fields() {
    	if ($this->description) echo wpautop(wptexturize($this->description)); ?>
				
		<p class="form-row" style="width:200px;">
		    <label>Card Number <span class="required">*</span></label>

		    <input class="input-text" style="width:180px;" type="text" size="16" maxlength="16" name="nmi_credircard" />
		</p>
		<div class="clear"></div>
		<p class="form-row form-row-first" style="width:200px;">
		    <label>Expiration Month <span class="required">*</span></label>
		    <select name="nmi_expdatemonth">
		        <option value=01> 1 - January</option>
		        <option value=02> 2 - February</option>
		        <option value=03> 3 - March</option>
		        <option value=04> 4 - April</option>
		        <option value=05> 5 - May</option>
		        <option value=06> 6 - June</option>
		        <option value=07> 7 - July</option>
		        <option value=08> 8 - August</option>
		        <option value=09> 9 - September</option>
		        <option value=10>10 - October</option>
		        <option value=11>11 - November</option>
		        <option value=12>12 - December</option>
		    </select>
		</p>
		<p class="form-row form-row-second" style="width:150px;">
		    <label>Expiration Year  <span class="required">*</span></label>
		    <select name="nmi_expdateyear">
		<?php
		    $today = (int)date('Y', time());
			$today1 = (int)date('Y', time());
		    for($i = 0; $i < 8; $i++)
		    {
		?>
		        <option value="<?php echo $today; ?>"><?php echo $today1; ?></option>
		<?php
		        $today++;
				$today1++;
		    }
		?>
		    </select>
		</p>
		<p class="form-row" style="width:200px;">
		    <label>Card CVV <span class="required">*</span></label>

		    <input class="input-text" style="width:100px;" type="text" size="5" maxlength="5" name="nmi_cvv" />
		</p>
		<div class="clear"></div>
		<?php
    }
	
	
    public function validate_fields()
    {
        global $woocommerce;

        if (!$this->isCreditCardNumber($_POST['nmi_credircard'])) 
            $woocommerce->add_error($field['label'] . __('(Credit Card Number) is not valid.', 'woothemes')); 
        
        if (!$this->isCorrectExpireDate($_POST['nmi_expdatemonth'], $_POST['nmi_expdateyear']))    
            $woocommerce->add_error($field['label'] . __('(Card Expire Date) is not valid.', 'woothemes')); 

        if (!$_POST['nmi_cvv'])
			$woocommerce->add_error($field['label'] . __('(Card CVV) is not entered.', 'woothemes'));
    }
	
    		
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;
			
		$order = new WC_Order( $order_id );
		        	
		$nmi_adr = $this->liveurl . '?';	
		
		$nmi_args['type'] = 'sale';
		$nmi_args['payment'] = 'creditcard';
		$nmi_args['ipaddress'] = $_SERVER['REMOTE_ADDR'];
		$nmi_args['username'] = $this->username;
		$nmi_args['password'] = $this->password;
		$nmi_args['currency'] = get_option('woocommerce_currency');
		$nmi_args['ccnumber'] = $_POST["nmi_credircard"];
		$nmi_args['cvv'] = $_POST["nmi_cvv"];
		$nmi_args['ccexp'] = $_POST["nmi_expdatemonth"].'/'.$_POST["nmi_expdateyear"];
				
		$nmi_args['orderid'] = $order_id;
								
		$nmi_args['firstname'] = $order->billing_first_name;
		$nmi_args['lastname'] = $order->billing_last_name;
		$nmi_args['company'] = $order->billing_company;
		$nmi_args['address1'] = $order->billing_address_1;
		$nmi_args['address2'] = $order->billing_address_2;
		$nmi_args['city'] = $order->billing_city;
		$nmi_args['state'] = $order->billing_state;
		$nmi_args['zip'] = $order->billing_postcode;
		$nmi_args['country'] = $order->billing_country;
		$nmi_args['email'] = $order->billing_email;
				
		$nmi_args['invoice'] = $order->order_key;
					
		$AmountInput = number_format($order->order_total, 2, '.', '');
		
		$nmi_args['amount'] = $AmountInput;
		
		$name_value_pairs = array();
		foreach ($nmi_args as $key => $value) {
			$name_value_pairs[] = $key . '=' . urlencode($value);
		}
		$gateway_values =  implode('&', $name_value_pairs);		
		
		$response = wp_remote_post($nmi_adr.$gateway_values);
		
        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) { 											
	        parse_str($response['body'], $response);	
	
		    if($response['response']== '1')
		    {					
		         // Payment completed
				$order->add_order_note(__('The NMI Payment transaction is successful. The Transaction Id is '.$response["transactionid"].'.', 'woothemes'));
				
		        $order->payment_complete();
				
				return array(
					'result' 	=> 'success',
					'redirect'	=>  $this->get_return_url($order)
					);		
			}
			else
			{
			  	$woocommerce->add_error($field['label'] . __('Transaction Failed. '.$response['responsetext'], 'woothemes'));
			}
        }
		else{
		  	$woocommerce->add_error($field['label'] . __('Gateway Error. Please Notify the Store Owner about this error.'. ' '.$statusMessage, 'woothemes'));
			
		}
		  		
	}
	
	/**
	 * receipt_page
	 **/
	function receipt_page( $order ) {
		
		echo '<p>'.__('Please enter your details and click the button below to pay with NMI.', 'woothemes').'</p>';
		
		echo $this->generate_nmipay_form( $order );
		
	}

	private function isCreditCardNumber($toCheck)
    {
        if (!is_numeric($toCheck))
            return false;

        $number = preg_replace('/[^0-9]+/', '', $toCheck);
        $strlen = strlen($number);
        $sum    = 0;

        if ($strlen < 13)
            return false;

        for ($i=0; $i < $strlen; $i++)
        {
            $digit = substr($number, $strlen - $i - 1, 1);
            if($i % 2 == 1)
            {
                $sub_total = $digit * 2;
                if($sub_total > 9)
                {
                    $sub_total = 1 + ($sub_total - 10);
                }
            }
            else
            {
                $sub_total = $digit;
            }
            $sum += $sub_total;
        }

        if ($sum > 0 AND $sum % 10 == 0)
            return true;

        return false;
    }

	private function isCorrectExpireDate($month, $year)
    {
        $now       = time();
        $result    = false;
        $thisYear  = (int)date('y', $now);
        $thisMonth = (int)date('m', $now);

        if (is_numeric($year) && is_numeric($month))
        {
            if($thisYear == (int)$year)
	        {
	            $result = (int)$month >= $thisMonth;
	        }			
			else if($thisYear < (int)$year)
			{
				$result = true;
			}
        }

        return $result;
    }
}

/**
 * Add the gateway to WooCommerce
 **/
function add_nmipay_gateway( $methods ) {
	$methods[] = 'woocommerce_nmipay'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_nmipay_gateway' );

}
