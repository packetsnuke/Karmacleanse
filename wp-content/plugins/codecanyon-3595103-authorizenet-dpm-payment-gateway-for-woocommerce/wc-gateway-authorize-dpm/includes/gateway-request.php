<?php

/**
 * Gateway API request class - sends given POST data to Gateway server
 **/
class authorize_dpm_request {
	
	var $_url;
	
	/** constructor */
	public function __construct($tran_mode = '', $md5_hash='') {
		$this->_url = ($tran_mode=="sandbox") ? AuthorizeNetDPM::SANDBOX_URL : AuthorizeNetDPM::LIVE_URL;
		if(!empty($md5_hash)) {
			define("AUTHORIZENET_MD5_SETTING", $md5_hash);
		}
	}
	
	/**
	 * Get url
	 */
	public function get_url(){
		return $this->_url;
	}

	/**
	 * Create and send the request
	 *
	 * @param array $options array of options to be send in POST request
	 * @return gateway_response response object
	 *
	 */
	public function send($options='') {
		$response = new AuthorizeNetSIM($options['x_login']);
		return $response;
	}
   
    /**
	 * Get card form information
	 */
	public function get_hidden_string($login_id, $tran_key, $options=array()){
		
		$time = time();
        $fp_hash = AuthorizeNetSIM_Form::getFingerprint($login_id, $tran_key, $options['x_amount'], $options['x_fp_sequence'], $time);
        
		$defaults = array(
			'x_login'         	=> $login_id,
			'x_delim_char' 		=> ',',
			'x_delim_data' 		=> 'TRUE',
			'x_relay_response' 	=> 'FALSE',
			'x_encap_char' 		=> '|',
			'x_fp_hash'       	=> $fp_hash,
            'x_fp_timestamp'  	=> $time,
		);
		
		$options = wp_parse_args($options, $defaults);
		$sim = new AuthorizeNetSIM_Form($options);
		
        $hidden_fields = $sim->getHiddenFieldString();
		
		return $hidden_fields;
	}
	
	/**
	 * getRelayResponseSnippet
	 */
	public function get_response_snippet($return_url) {
		return AuthorizeNetDPM::getRelayResponseSnippet($return_url);
	}
}
?>