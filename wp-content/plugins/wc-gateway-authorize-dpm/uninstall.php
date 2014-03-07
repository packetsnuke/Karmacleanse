<?php
/**
 * WooCommerce Authorize.net DPM Gateway
 * By Buif.Dw <support@browsepress.com>
 * 
 * Uninstall - removes all Authorize.net options from DB when user deletes the plugin via WordPress backend.
 * @since 1.0.0
 **/
 
if ( !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}

delete_option( 'woocommerce_authorize_dpm_settings' );		
