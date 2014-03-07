<?php
/*
 * Plugin Name: WooCommerce TradeGecko Integration
 * Plugin URI: http://woothemes.com/products/woocommerce-tradegecko/
 * Description: This plugin integrates your TradeGecko Account with your WooCommerce store.
 * Version: 1.0.1
 * Author: TradeGecko Pte Ltd
 * Author URI: http://tradegecko.com/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once('woo-includes/woo-functions.php');

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '21da7811f7fc1f13ee19daa7415f0ff3', 245960 );


/**
 * Main Class to LeadSpring
 *
 * @since 1.0
 */
class WC_TradeGecko_Init {

	/** Plugin prefix for options */
	public static $prefix = 'wc_tradegecko_';

	/** Plugin prefix for options */
	public static $meta_prefix = '_wc_tradegecko_';

	/** Plugin text domain */
	public static $text_domain = 'wc_tradegecko_lang';

	/** Settings page tag name */
	public static $settings_page = 'tradegecko';

	/** Plugin Directory Path */
	public static $plugin_dir;

	/** Plugin Directory URL */
	public static $plugin_url;

	/** Plugin settings holder */
	public static $settings;

	/** Plugin settings holder */
	public static $api;

	/** Debug log */
	public static $log;

	const VERSION = '1.0.1';

	public function __construct() {

		// Install settings
		if ( is_admin() && ! defined('DOING_AJAX') ) $this->install();

		/** Plugin Directory Path */
		self::$plugin_dir = plugin_dir_path( __FILE__ );

		/** Plugin Directory URL */
		self::$plugin_url = plugin_dir_url( __FILE__ );

		self::$settings = $this->get_settings();

		// Include required files
		$this->includes();

		if ( is_admin() ) {
			add_action( 'woocommerce_init', array( $this, 'admin_includes' ) );
		}

		// Init the TG API
		$this->init_api();

		// Actions
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_init', array( $this, 'admin_init_listner' ) );

		// Add a 'Settings' link to the plugin action links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'settings_link' ), 10, 4 );
	}

	/**
	 * Admin Init listener. Call all "Admin Init" specific actions.
	 */
	public function admin_init_listner() {

		// Capture the new Auth Code
		$this->save_new_auth_code();

	}

	/**
	 * Capture and save the Authorization Code to the settings
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function save_new_auth_code() {
		$new_code = self::get_get( 'code' );
		if ( ! empty( $new_code ) ) {

			$api = get_option( self::$prefix .'settings_api' );

			$api[ 'auth_code' ] = $new_code;

			update_option( self::$prefix .'settings_api', $api );

			wp_redirect( admin_url('/admin.php?page='. self::$settings_page .'&tab=api&new-auth-code-obtained=true') );
			exit;
		}
	}

	public static function add_sync_log( $log_type = 'Message', $message = '' ) {

		if ( ! $message ) {
			return;
		}

		// Get the sync log
		$sync_log = get_option( self::$prefix . 'sync_log', array() );

		// Add new message/error to the log
		$sync_log[] = array( 'timestamp' => time(), 'log_type' => $log_type, 'action' => $message );

		// Remove the oldest messeges from the log
		if ( 30 < count( $sync_log ) ) {
			array_shift( $sync_log );
		}

		$error_count = get_option( self::$prefix . 'error_count', 0 );
		if ( 'error' == strtolower( $log_type ) ) {
			$error_count++;

			update_option( self::$prefix . 'error_count', $error_count );
		}

		update_option( self::$prefix . 'sync_log', $sync_log );
	}

	/**
	 * Include plugin files
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function includes() {

		if ( WC_TradeGecko_Init::get_setting( 'enable' ) ) {
			include_once( 'classes/class-wc-tradegecko-sync.php' ); // Sync class
			include_once( 'classes/class-wc-tradegecko-cron.php' ); // Cron schedule class
			include_once( 'classes/class-wc-tradegecko-admin.php' ); // Sync logs class
		}

	}

	/**
	 * Include admin specific plugin files
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	function admin_includes() {

		include_once( 'admin/wc-tradegecko-register-settings.php' );
		include_once( 'admin/wc-tradegecko-settings.php' );
		include_once( 'classes/class-wc-tradegecko-list-table.php' ); // Sync logs class

		if ( WC_TradeGecko_Init::get_setting( 'enable' ) ) {
			include_once( 'classes/class-wc-tradegecko-ajax.php' ); // Ajax manipulations class
		}

	}

	/**
	 * Include and init the API class
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function init_api() {

		include_once( 'classes/class-wc-tradegecko-api.php' );

		self::$api = new WC_TradeGecko_API();

	}

	/**
	 * Run on plugin installation
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function install() {
		register_activation_hook(__FILE__, array($this, 'activate') );
	}

	/**
	 * Init
	 *
	 * @since 1.0
	 */
	public function init() {

		load_plugin_textdomain( self::$text_domain, false, dirname( plugin_basename( __FILE__ ) ).'/languages' );

	}

	/**
	 * Add the admin scripts and styles
	 *
	 * @since 1.0
	 * @access public
	 */
	public function admin_scripts() {
		wp_register_script( 'wc-tradegecko-chosen', self::$plugin_url . 'assets/js/chosen/chosen.jquery.min.js', array( 'jquery' ), '0.9.8', false );
		wp_register_style( 'wc-tradegecko-chosen-styles', self::$plugin_url . 'assets/css/chosen.css' );
		wp_register_style( 'wc-tradegecko-admin-styles', self::$plugin_url . 'assets/css/admin.css' );


		wp_enqueue_script( 'wc-tradegecko-chosen' );
		wp_enqueue_style( 'wc-tradegecko-chosen-styles' );
		wp_enqueue_style( 'wc-tradegecko-admin-styles' );
	}

	/**
	 * Add default settings on activation
	 *
	 * @since 1.0
	 * @access public
	 * @return void
	 */
	public function activate() {

		$general_settings = array(
			'enable'		=> '1',
			'enable_debug'		=> '0',
			'inventory_sync'	=> '1',
			'product_price_sync'	=> '1',
			'product_title_sync'	=> '1',
			'orders_sync'		=> '1',
			'order_number_prefix'	=> 'WooCommerce-',

		);

		$get_general = get_option( self::$prefix .'settings_general' );
		if ( empty( $get_general ) ) {
			update_option( self::$prefix .'settings_general', $general_settings );
		}

		$api_settings = array(
			'client_id'	=> '',
			'client_secret' => '',
			'redirect_uri'	=> '',
			'auth_code'	=> '',
			'client_id'	=> '',
		);

		$get_api = get_option( self::$prefix .'settings_api' );
		if ( empty( $get_api ) ) {
			update_option( self::$prefix .'settings_api', $api_settings );
		}

		$sync_settings = array(
			'automatic_sync'	=> '',
			'sync_time_interval'	=> '30',
			'sync_time_period'	=> 'minutes',
		);

		$get_sync = get_option( self::$prefix .'settings_sync' );
		if ( empty( $get_sync ) ) {
			update_option( self::$prefix .'settings_sync', $sync_settings );
		}

	}

	/**
	 * Retrieves all plugin settings and returns them
	 * as a combined array.
	 *
	 * @since 1.0
	 * @access public
	 * @return array
	 */
	public function get_settings() {
		$general_settings = is_array(get_option(self::$prefix .'settings_general')) ? get_option(self::$prefix .'settings_general') : array();
		$api_settings	 = is_array(get_option(self::$prefix .'settings_api')) ? get_option(self::$prefix .'settings_api') : array();
		$sync_settings	 = is_array(get_option(self::$prefix .'settings_sync')) ? get_option(self::$prefix .'settings_sync') : array();

		return array_merge($general_settings, $api_settings, $sync_settings);
	}

	/**
	 * Add Debug Log
	 *
	 * @param string $message
	 */
	public static function add_log( $message ) {
		if ( '1' == self::get_setting( 'enable_debug' ) ) {

			self::get_logger_object();

			self::$log->add( 'tradegecko', $message );

		}

	}

	/**
	 * Get the WC logger object
	 *
	 * @global type $woocommerce
	 * @return type
	 */
	public function get_logger_object() {
		global $woocommerce;

		if ( is_object( self::$log ) ) {
			return self::$log;
		} else {
			return self::$log = $woocommerce->logger();
		}
	}

	/**
	 * Add 'Settings' link to the plugin actions links
	 *
	 * @since 1.0
	 * @return array associative array of plugin action links
	 */
	public function settings_link( $actions, $plugin_file, $plugin_data, $context ) {
		return array_merge( array( 'settings' => '<a href="' . admin_url( 'admin.php?page='. self::$settings_page .'">' . __( 'Settings', self::$text_domain ) . '</a>' ) ), $actions );
	}

	/**
	 * Helper, Savely retrieve GET variables
	 *
	 * @since 1.0
	 * @param string Get variable name
	 * @return string The variable value
	 **/
	public static function get_get( $name ) {
		if ( isset( $_GET[$name] ) ) {
			return $_GET[$name];
		}
		return null;
	}

	/**
	 * Helper, Savely retrieve settings
	 *
	 * @since 1.0
	 * @param string Setting name
	 * @return string The setting value
	 **/
	public static function get_setting( $name ) {
		if ( isset( self::$settings[ $name ] ) ) {
			return self::$settings[ $name ];
		}
		return null;
	}

	/**
	 * Format the date and time from a timestamp string
	 *
	 * @since 1.0
	 * @param int $timestamp
	 * @return string formatted datetime
	 */
	public static function get_formatted_datetime( $timestamp ) {

		if ( ! $timestamp ) {
			return __( 'N/A', self::$text_domain );
		}

		// Return the date and time
		return date_i18n( woocommerce_date_format() . ' ' . get_option( 'time_format' ), $timestamp + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) .' '. date_default_timezone_get();

	}

} new WC_TradeGecko_Init;