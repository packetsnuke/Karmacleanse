<?php if (!defined('ABSPATH')) exit; ?>

<?php woocommerce_get_template('emails/email-header.php', array( 'email_heading' => $email_heading )); ?>

<?php echo $message_from_sender; ?>

<p><?php echo sprintf(__("You have received a Karmic Coupon that can be applied to your next order.  Use the following coupon code during checkout:", 'wc_smart_coupons'), $blogname); ?></p>

<strong style="margin: 10px 0; font-size: 2em; line-height: 1.2em; font-weight: bold; display: block; text-align: center;"><?php echo $coupon_code; ?></strong>

<center><a href="<?php echo $url; ?>"><?php echo sprintf(__("Visit store",'wc_smart_coupons') ); ?></a></center>

<p><?php echo sprintf(__("We use glass bottles because we care about the earth, but collecting and sanitizing them for reuse is time consuming and costly. Your cleanse included a bottle deposit that is being returned to you via a <b>Karmic Coupon</b> good toward your next purchase. Just remember, next time you pick up your Order be sure to return your carefully rinsed bottles, after all what comes around goes around... it's Karma. ", 'wc_smart_coupons'), $blogname); ?></p>

<div style="clear:both;"></div>

<?php woocommerce_get_template('emails/email-footer.php'); ?>