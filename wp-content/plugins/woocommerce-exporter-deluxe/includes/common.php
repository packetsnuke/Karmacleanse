<?php
/*

Filename: common.php
Description: common.php loads commonly accessed functions across the Visser Labs suite.

- woo_is_admin_icon_valid

- woo_vl_plugin_update_prepare

*/

if( is_admin() ) {

	/* Start of: WordPress Administration */

	include_once( 'common-update.php' );
	include_once( 'common-dashboard_widgets.php' );

	if( !function_exists( 'woo_is_admin_icon_valid' ) ) {
		function woo_is_admin_icon_valid( $icon = 'tools' ) {

			switch( $icon ) {

				case 'index':
				case 'edit':
				case 'post':
				case 'link':
				case 'comments':
				case 'page':
				case 'users':
				case 'upload':
				case 'tools':
				case 'plugins':
				case 'themes':
				case 'profile':
				case 'admin':
					return $icon;
					break;

			}

		}
	}

	if( !function_exists( 'woo_vl_plugin_update_prepare' ) ) {

		function woo_vl_plugin_update_prepare( $action, $args ) {

			global $wp_version;

			return array(
				'body' => array(
					'action' => $action,
					'request' => serialize( $args ),
					'api-key' => md5( get_bloginfo( 'url' ) ),
					'site' => get_bloginfo( 'url' )
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
			);	

		}

	}

	/* End of: WordPress Administration */

}
