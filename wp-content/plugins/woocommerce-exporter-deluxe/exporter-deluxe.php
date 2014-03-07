<?php
/*
Plugin Name: WooCommerce - Store Exporter Deluxe
Plugin URI: http://www.visser.com.au/woocommerce/plugins/exporter-deluxe/
Description: Unlocks business focused e-commerce features within Store Exporter.
Version: 1.2.5
Author: Visser Labs
Author URI: http://www.visser.com.au/about/
License: GPL2
*/

load_plugin_textdomain( 'woo_cd', null, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

include_once( 'includes/functions.php' );

include_once( 'includes/common.php' );

$woo_cd = array(
	'filename' => basename( __FILE__ ),
	'dirname' => basename( dirname( __FILE__ ) ),
	'abspath' => dirname( __FILE__ ),
	'relpath' => basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ),
	'file' => __FILE__
);

$woo_cd['prefix'] = 'woo_cd';
$woo_cd['name'] = __( 'WooCommerce Exporter Deluxe', 'woo_cd' );
$woo_cd['menu'] = __( 'Store Exporter Deluxe', 'woo_cd' );

if( is_admin() ) {

	/* Start of: WordPress Administration */

	function woo_cd_enqueue_styles() {

		wp_enqueue_style( 'woo_vm_styles', plugins_url( '/templates/admin/woo-admin_dashboard_vm-plugins.css', __FILE__ ) );

	}
	add_action( 'admin_enqueue_scripts', 'woo_cd_enqueue_styles' );

	function woo_cd_add_settings_link( $links, $file ) {

		static $this_plugin;
		if( !$this_plugin ) $this_plugin = plugin_basename( __FILE__ );
		if( $file == $this_plugin ) {
			$settings_link = sprintf( '<a href="%s">' . __( 'Export', 'woo_cd' ) . '</a>', add_query_arg( 'page', 'woo_ce', 'admin.php' ) );
			array_unshift( $links, $settings_link );
		}
		return $links;

	}
	add_filter( 'plugin_action_links', 'woo_cd_add_settings_link', 10, 2 );

	function woo_cd_admin_init() {

		// Do nothing

	}

	function woo_cd_html_page() {

		global $wpdb, $woo_cd;

		$woo_ce_url = 'http://wordpress.org/extend/plugins/woocommerce-exporter/';
		$woo_ce_search = add_query_arg( array( 'tab' => 'search', 's' => 'WooCommerce+Store+Exporter' ), admin_url( 'plugin-install.php' ) );

		woo_cd_template_header();
		include_once( 'templates/admin/woo-admin_cd-export.php' );
		woo_cd_template_footer();

	}

	/* End of: WordPress Administration */

}
?>