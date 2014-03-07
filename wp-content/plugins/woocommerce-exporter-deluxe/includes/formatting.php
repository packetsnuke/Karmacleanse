<?php
function woo_cd_convert_product_ids( $product_ids = null ) {

	global $export;

	$output = '';
	if( $product_ids ) {
		if( is_array( $product_ids ) ) {
			$size = count( $product_ids );
			for( $i = 0; $i < $size; $i++ )
				$output .= $product_ids[$i] . $export->category_separator;
			$output = substr( $output, 0, -1 );
		} else if( strstr( $product_ids, ',' ) ) {
			$output = str_replace( ',', $export->category_separator, $product_ids );
		}
	}
	return $output;

}

function woo_cd_format_discount_type( $discount_type = '' ) {

	$output = $discount_type;
	switch( $discount_type ) {

		case 'fixed_cart':
			$output = __( 'Cart Discount', 'woo_cd' );
			break;

		case 'percent':
			$output = __( 'Cart % Discount', 'woo_cd' );
			break;

		case 'fixed_product':
			$output = __( 'Product Discount', 'woo_cd' );
			break;

		case 'percent_product':
			$output = __( 'Product % Discount', 'woo_cd' );
			break;

	}
	return $output;

}
?>