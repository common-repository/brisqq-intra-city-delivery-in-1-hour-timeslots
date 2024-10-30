<?php
/////////////////////////////////////////////////////////////////////////////////////

$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

/////////////////////////////////////////////////////////////////////////////////////

Class Brisqq_Observer {

	public function brisqq_update_delivery_info ($rawData) {
		$data = array();
		parse_str($rawData, $data);

		if ($data['shipping_first_name'] == '') {
			$first_name = $data['billing_first_name'];
		} else {
			$first_name = $data['shipping_first_name'];
		}

		if ($data['shipping_last_name'] == '') {
			$last_name = $data['billing_last_name'];
		} else {
			$last_name = $data['shipping_last_name'];
		}

		$name = $first_name . ' ' .  $last_name;

		if ($data['shipping_country'] == '') {
			$selected_country = $data['billing_country'];
		} else {
			$selected_country = $data['shipping_country'];
		}

		$company = $data['billing_company'];
		if (!empty($data['shipping_company']) && $data['shipping_company'] != "") {
			$company = $data['shipping_company'];
		}

		$customerData = array(
			"contactName" => $name,
			"company" => $company,
			"contactPhone" => $data['billing_phone'],
			"contactEmail" => $data['billing_email'],
			"selectedCountry" => $selected_country
		);

		WC()->session->set('brisqq_customer_info', $customerData);


	}

	/////////////////////////////////////////////////////////////////////////////////////

	public function brisqq_setup_additional_fields ($array) {
		global $brisqq_sm_integration_name;
		// Brisqq_SM_ChromePhp::log($brisqq_sm_integration_name);
		echo '<script src="https://brisqq-assets.s3.amazonaws.com/eapi/woocommerce/' . $brisqq_sm_integration_name . '/brisqq-loader.js" type="text/javascript" data-brisqq></script>';
	}

	/////////////////////////////////////////////////////////////////////////////////////

	public function brisqq_confirm_delivery ($orderId, $postedData) {

		$chosenShipping = WC()->session->get( 'chosen_shipping_methods');

		if ($chosenShipping[0] != "brisqq_shipping_method") {
			return;
		};

		$token = $_COOKIE['brisqq_token'];

		if (brisqq_sm_production()) {
			$url = 'https://core.brisqq.com/eapi/confirm';
		} else {
			$url = 'http://core-staging.brisqq.com/eapi/confirm';
		}

		$deliveryId = $_COOKIE['brisqq_delivery_id'];
		$accountId = $_COOKIE['brisqq_account_id'];

		if (empty($deliveryId)) {
			return false;
		}

		if (brisqq_sm_function_checker('curl_version')) {

		    $ch = curl_init();

		    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
		    curl_setopt($ch, CURLOPT_POST, TRUE);
		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		    curl_setopt($ch, CURLOPT_HTTPHEADER,array('Authorization: Bearer ' . $token));
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('deliveryId' => $deliveryId, 'orderId' => $orderId, 'partner' => $accountId)));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		    $datat = curl_exec($ch);
		    curl_close($ch);
		    $result = $datat;

		} elseif (brisqq_sm_function_checker('file_get_contents')) {
			$postdata = http_build_query(
				array('deliveryId' => $deliveryId, 'orderId' => $orderId, 'partner' => $accountId, 'test'=>null)
			);

			$opts = array('http' =>
				array(
					'method'  => 'POST',
					'header'  => "Authorization: Bearer " . $token, 'Content-type: application/x-www-form-urlencoded',
					'content' => $postdata,
					'timeout' => 60
				)
			);

			$context = stream_context_create($opts);

			$result = file_get_contents($url, false, $context);

		}

		return $result;

	}

	/////////////////////////////////////////////////////////////////////////////////////

	public function clear_wc_shipping_rates_cache () {

		global $wpdb;
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%wc_ship%'");
	}

	/////////////////////////////////////////////////////////////////////////////////////

	public function add_brisqq_shipping_method ($methods) {
		global $brisqq_woocommerce_dir;
		require_once $brisqq_woocommerce_dir . 'carrier.php';
		$methods[] = 'WC_Brisqq_Shipping_Method';
		return $methods;
	}

	public function update_delivery_info () {
		global $brisqq_woocommerce_dir;
		require_once $brisqq_woocommerce_dir . 'carrier.php';
		$update = new WC_Brisqq_Shipping_Method;
		$update->is_available($package=array());
	}


}



brisqq_sm_custom_class_observer();

$Brisqq_Observer_Instance = new Brisqq_Observer_Custom_Code;

/////////////////////////////////////////////////////////////////////////////////////


if (in_array('woocommerce/woocommerce.php', $active_plugins)) {

	add_filter('woocommerce_shipping_methods', array($Brisqq_Observer_Instance,'add_brisqq_shipping_method'));
	add_filter('woocommerce_checkout_update_order_review', array($Brisqq_Observer_Instance,'clear_wc_shipping_rates_cache'));
	add_filter('woocommerce_checkout_update_order_review', array($Brisqq_Observer_Instance,'brisqq_update_delivery_info'));
	add_filter('woocommerce_checkout_update_order_review', array($Brisqq_Observer_Instance,'update_delivery_info'));
	add_action('woocommerce_checkout_shipping', array($Brisqq_Observer_Instance,'brisqq_setup_additional_fields'));
	add_action('woocommerce_checkout_order_processed', array($Brisqq_Observer_Instance,'brisqq_confirm_delivery'));

}

/////////////////////////////////////////////////////////////////////////////////////
?>
