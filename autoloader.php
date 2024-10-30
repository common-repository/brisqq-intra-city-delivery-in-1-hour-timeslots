<?php
require_once(plugin_dir_path( __FILE__ ) . 'ChromePhp.php');
require_once(plugin_dir_path( __FILE__ ) . 'custom-php-brisqq.php');

$brisqq_woocommerce_dir = plugin_dir_path( __FILE__ );
##############################################
## PRODUCTION -- STAGING SWITCH
##############################################
## When the $production value is false, the plugin is targeting staging server.
## Give this variable true value if you want to use this plugin for your production website.

function brisqq_sm_production() {
	## Change $production variable to true if you want to run this plugin on the production server
	$production = true;

	return $production;
}

$brisqq_sm_integration_name = 'default_production';


#################################################
## Autoloader logs
#################################################
function brisqq_sm_chromePhp_logs_autoloader() {
	$brisqq_sm_chromePhp_logs_autoloader = true;

	return $brisqq_sm_chromePhp_logs_autoloader;
}
function brisqq_sm_file_name ($fileN) {
	$link = explode(("/"), $fileN);
	return end($link);
}


##############################################################################
### PHP settings checker function
### Checking are curl, file_get_contents or file_put_contents enabled/disabled
##############################################################################
function brisqq_sm_function_checker($function_name) {

	$checker = function_exists($function_name);
	if (!$checker) {
		Mage::log($function_name . ' is disabled.');
	}

	if (brisqq_sm_chromePhp_logs_autoloader()) {
		Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . '- function checker fired');
		Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . '- ' . $function_name . ' is ' . $checker);
	}

	return $checker;

	## return false;
}



###################################################################
## Output remote php file in a variable as a string -- CURL METHOD
###################################################################
function brisqq_sm_curl_method($url) {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);

	## curl_setopt($ch, CURLOPT_URL, "http:##hkhh657587.com");

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = curl_exec($ch);
	curl_close($ch);

	if (brisqq_sm_chromePhp_logs_autoloader()) {
		Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . '- brisqq_sm_curl_method function fired!');
		Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . '- URL: ' . $url);
		Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . ' Server response: ' . isset($data));
	}

	return $data;
}

################################################################################
## Output remote php file in a variable as a string -- FILE GET CONTENTS METHOD
################################################################################
function brisqq_sm_file_get_contents_method($url) {

	$result = file_get_contents($url, false);

	if (brisqq_sm_chromePhp_logs_autoloader()) {
		Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . ' - brisqq_sm_file_get_contents_method function fired!');
		Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . ' - URL: ' . $url);
		Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . ' - Results: ' . $result);
	}

	return $result;
}

################################################################################
################################################################################

function brisqq_sm_save_remote_file($file_name) {
	$brisqq_woocommerce_dir = plugin_dir_path( __FILE__ );
	$brisqq_sm_integration_name = 'default_production';

	if ($file_name == 'carrier.php') {
		$url = 'http://s3-eu-west-1.amazonaws.com/brisqq-assets/eapi/core_v2/woocommerce_store/carrier.php';
	} elseif ($file_name == 'observer.php') {
		$url = 'http://s3-eu-west-1.amazonaws.com/brisqq-assets/eapi/core_v2/woocommerce_store/observer.php';
	} elseif ($file_name == 'custom-php-brisqq.php') {
		$url = 'http://brisqq-assets.s3.amazonaws.com/eapi/woocommerce/' . $brisqq_sm_integration_name . '/custom-php-brisqq.php';
	}

	if (brisqq_sm_chromePhp_logs_autoloader()) {
		Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . ' - save_remote_file function fired!');
	}

	## if cURL is enabled, call our server with cURL
	if (brisqq_sm_function_checker('curl_version')) {
		if (brisqq_sm_chromePhp_logs_autoloader()) {
			Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . ' - curl is enabled, getting ready for http request');
		}

		$code = brisqq_sm_curl_method($url);

		if (brisqq_sm_chromePhp_logs_autoloader()) {
			Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . ' - requesting carrier.php from s3, http request results: ' . isset($code));
		}

		## update the file only if it was succesfullt loaded from server, otherwise use the one previously loaded
		if ($code && brisqq_sm_function_checker('file_put_contents')) {
			file_put_contents($brisqq_woocommerce_dir . $file_name, $code);
		}

	} elseif (brisqq_sm_function_checker('file_get_contents')) {

		if (brisqq_sm_chromePhp_logs_autoloader()) {
			Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . ' - CURL IS DISABLED, getting ready for http request via file_get_contents');
		}

		$code = brisqq_sm_file_get_contents_method($url);

		if (brisqq_sm_chromePhp_logs_autoloader()) {
			Brisqq_SM_ChromePhp::log(brisqq_sm_file_name(__FILE__) . '/' . __LINE__ . ' - requesting carrier.php from s3, http request results: ' . isset($code));
		}

		## update the file only if it was succesfullt loaded from server, otherwise use the one previously loaded
		if ($code && brisqq_sm_function_checker('file_put_contents')) {
			file_put_contents($brisqq_woocommerce_dir . $file_name, $code);
		}
	}
}



?>
