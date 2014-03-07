<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * WC TradeGecko Cron Class
 *
 * Handles scheduling for WooCommerce to TradeGecko Synchronization.
 */

class WC_TradeGecko_Cron {

	/**
	 * Adds hooks and filters
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {
		
		$this->automatic_sync		= WC_TradeGecko_Init::get_setting( 'automatic_sync' );
		$this->sync_time_interval	= (int) WC_TradeGecko_Init::get_setting( 'sync_time_interval' );
		$this->sync_time_period		= WC_TradeGecko_Init::get_setting( 'sync_time_period' );

		// Add sync custom schedule
		add_filter( 'cron_schedules', array( $this, 'add_sync_schedules' ) );

		// Schedule 
		add_action( 'init', array( $this, 'add_scheduled_syncs' ) );

		if ( 'HOUR_IN_SECONDS' == $this->sync_time_period ) {
			$this->time_in_seconds = HOUR_IN_SECONDS;
			$this->time_to_display = 'hours';
		} elseif ( 'DAY_IN_SECONDS' == $this->sync_time_period ) {
			$this->time_in_seconds = DAY_IN_SECONDS;
			$this->time_to_display = 'days';
		} else {
			$this->time_in_seconds = MINUTE_IN_SECONDS;
			$this->time_to_display = 'minutes';
		}
	}

	/**
	 * Adds custom schedule from admin setting
	 *
	 * @access public
	 * @since  1.0
	 * @param array $schedules - existing WP recurring schedules
	 * @return array
	 */
	public function add_sync_schedules( $schedules ) {
		
		if ( '1' == $this->automatic_sync ) {
			
			if ( $this->sync_time_interval ) {
				$schedules[ WC_TradeGecko_Init::$prefix . 'automatic_sync' ] = array(
					'interval' => $this->sync_time_interval * $this->time_in_seconds,
					'display'  => sprintf( __( 'Every %d %s', WC_TradeGecko_Init::$text_domain ), $this->sync_time_interval, $this->time_to_display )
				);
			}
			
		}

		return $schedules;
	}

	/**
	 * Add scheduled events to wp-cron if not already added
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function add_scheduled_syncs() {

		if ( '1' == $this->automatic_sync ) {

			// Schedule inventory update
			if ( ! wp_next_scheduled( 'wc_tradegecko_synchronization' ) ) {
				wp_schedule_event( strtotime( $this->sync_time_interval .' '. $this->time_to_display ), WC_TradeGecko_Init::$prefix . 'automatic_sync', 'wc_tradegecko_synchronization' );
			}

		} else {
			// If sync is disabled then clear the cron schedule
			wp_clear_scheduled_hook( 'wc_tradegecko_synchronization' );
		}

	}

} new WC_TradeGecko_Cron(); 