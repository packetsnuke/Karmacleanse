jQuery(document).ready(function($) {
	$('#authorize-dpm-cc-month, #authorize-dpm-cc-year').change(function(){
		$('#x_exp_date').val($('#authorize-dpm-cc-month').val() + '/' + $('#authorize-dpm-cc-year').val());
	});
	$("#authorize_dpm_payment_form").validate({
		messages: {
			x_card_num: '',
			x_card_code: '<span class="required">*</span>',
			authorize_dpm_cc_month: '',
			authorize_dpm_cc_year: '',  
		}
	});
});