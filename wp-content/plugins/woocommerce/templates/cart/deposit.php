<?php
/**
 * Bottle Deposit
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce_loop, $woocommerce, $product;

$depositamount = array(
		'No' => 1,
		'10 Bottles' => 2,
		'20 Bottles' => 3
);



function generateSelect($name = '', $options = array()) {
	$html = '<select name="'.$name.'">';
	foreach ($options as $option => $value) {
		$html .= '<option value='.$value.'>'.$option.'</option>';
	}
	$html .= '</select>';
	return $html;
}

$numBottles = 1;
$cashRefund = 0;


if( isset($_POST['submit']))
{
	$numBottles = $_POST['depositSelect'];


	if ($numBottles = 1)
	{
		$cashRefund = 0;
	}
	if ($numBottles = 2)
	{
		$cashRefund = 10;
	}
	if ($numBottles = 3)
	{
		$cashRefund = 30;
	}
}

$woocommerce_loop['columns'] 	= 1;
?>

	<div class="deposit">

		<h2><?php _e( 'Do you have bottles to return?', 'woocommerce' ) ?></h2>
		<form name="deposit_form" method="post" action="<?php echo $_SERVER['QUERY_STRING']; ?>">
			<?php echo $html = generateSelect('depositSelect', $depositamount);  ?>
			<input type="submit" value="Refund!"/>
		</form>
		
		<div class="refundAmount">
			<?php if ($cashRefund > 0) echo "You will receive a \$ $cashRefund coupon code towards your next purchase upon bottle return"?>
		</div>

	</div>


