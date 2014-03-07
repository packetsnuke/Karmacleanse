<?php
/*
Plugin Name: WC Authorize.net DPM
Plugin URI: http://codecanyon.net/item/authorizenet-dpm-payment-gateway-for-woocommerce/3595103
Description: Extends WooCommerce. Provides a Authorize.net DPM gateway for WooCommerce.
Version: 1.1.1
Author: Buif.Dw <support@browsepress.com>
Author URI: http://codecanyon.net/user/browsepress
*/

add_action('plugins_loaded', 'init_authorize_dpm_gateway', 0);

function init_authorize_dpm_gateway() {
	if ( ! class_exists( 'Woocommerce' ) ) { return; }

	$plugin_dir = plugin_dir_path(__FILE__);
	
	/**
 	 * Localication
	 */
	load_textdomain( 'woocommerce', $plugin_dir.'langs/authorize-dpm-'.get_locale().'.mo' );
	
	if(!defined('AUTHORIZE_NET_SDK')) {
		define('AUTHORIZE_NET_SDK', 1);
		require_once $plugin_dir . 'includes/authorize-net.php';
	}
	
	require_once($plugin_dir . 'includes/gateway-request.php');
	require_once $plugin_dir . 'gateway-authorize-dpm.php';
	
	/**
 	* Add the Gateway to WooCommerce
 	**/
	function add_authorize_dpm_gateway($methods) {
		$methods[] = 'WC_Authorize_DPM';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_authorize_dpm_gateway' );
	
	if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
		if(empty($_GET['wc-api']) && !empty($_GET['authorizeListenerDPM'])) {
			$adpm = new WC_Authorize_DPM;
		}
	}
}



?>
