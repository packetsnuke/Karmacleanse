<?php

$orddd_lang = 'en';
$orddd_translations = array(
	'en' => array(
	
		'common.date-settings'     => "Date Settings", 
		'common.time-settings'     => "Time Settings",
		'common.holidays'		   => "Holidays",
		'common.appearance'		   => "Appearance",
		'common.delivery-dates'    => "Delivery Dates",

	),
	
	);
	
	
global $orddd_translations, $orddd_lang;

function orddd_t($str)
{
	global $orddd_translations, $orddd_lang;
	
	return $orddd_translations[$orddd_lang][$str];
}


?>