<?php


class Brisqq_Carrier_Core extends WC_Shipping_Method {

	protected $_code = 'brisqq_shipping';

	protected $_brisqqAPIEndpoint = 'http://core-staging.brisqq.com/eapi/';

	protected $_checkCoverageUrl = 'checkCoverage';

	public $_deliveryData = null;

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function __construct() {

		$this->id = 'brisqq_shipping_method';
		$this->method_title = __('Brisqq', 'woocommerce');

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');

		//need to call WooCommerce method to save settings from admin
		add_action('woocommerce_update_options_shipping_'. $this->id, array($this, 'process_admin_options'));

	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function _isProduction() {

		if (brisqq_sm_production()) {
			$this->_brisqqAPIEndpoint = 'https://core.brisqq.com/eapi/';
		}
	}

	public function init_form_fields () {

		$this->form_fields = array(

			'enabled' => array(
				'title' => __('Enable/Disable', 'woocommerce'),
				'type' => 'checkbox',
				'label' => __('Enable Brisqq', 'woocommerce'),
				'default' => 'yes'
			),

			'title' => array(
				'title' => __( 'Method Title', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default' => __( 'Brisqq - convenient same (or future) day delivery in a 1-hour timeslot of your choice', 'woocommerce' )
			),

			'accountID' => array(
				'title' => __( 'Brisqq Merchant ID', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default' => __( '559280e2fed0ed03000900be', 'woocommerce' )

			),

			'coveredCities' => array(
				'title' => __( 'Covered cities', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the cities for which the method will be available.', 'woocommerce' ),
				'default' => __( 'London', 'woocommerce' )
			),

		);
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function is_available ($package=array()) {
		global $woocommerce;
		$customerData = WC()->session->get('brisqq_customer_info');


		$accountId = $this->settings['accountID'];
		$dropoffPostCode = WC()->customer->get_shipping_postcode();

		$all_cart_items = WC()->cart->get_cart_contents_count();

		$cart_items = $woocommerce->cart->get_cart();


		if (empty($dropoffPostCode) || empty($accountId)) {
			return false;
		}

		$selected_country = $customerData['selectedCountry'];

		if ( $selected_country !== "GB" ) {

			return false;
		}

		$this->_isProduction();

		$postcoderegex = '/^([g][i][r][0][a][a])$|^((([a-pr-uwyz]{1}([0]|[1-9]\d?))|([a-pr-uwyz]{1}[a-hk-y]{1}([0]|[1-9]\d?))|([a-pr-uwyz]{1}[1-9][a-hjkps-uw]{1})|([a-pr-uwyz]{1}[a-hk-y]{1}[1-9][a-z]{1}))(\d[abd-hjlnp-uw-z]{2})?)$/i';

		$postcode2check = str_replace(' ','',$dropoffPostCode);

		if (!preg_match($postcoderegex, $postcode2check)) {
			return false;
		}

		$this->_deliveryData = json_decode($this->check_coverage($accountId, $dropoffPostCode), true);

		if (!empty($this->_deliveryData['error']) || empty($this->_deliveryData) || !$this->_deliveryData['covered']) {
			return false;
		}

		//it's available, but nothing else to do since we're not on checkout page
		if (!is_checkout()) {
			return true;
		}

		if (!isset($_COOKIE['brisqq_compatible']) || $_COOKIE['brisqq_compatible'] == "false") {
			return false;
		}

		$this->clear_session();


		$cart_total = new WC_cart;

		$price_only = filter_var($woocommerce->cart->get_cart_subtotal(false), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

		$price_only = str_replace('-', '', $price_only);

		$cartGrossTotal = $price_only;

        foreach($cart_items as $item => $values) {
            $_product = $values['data']->post;

        }


		$deliverySize = 'L';

		if ($cartGrossTotal > 60) {
			$deliverySize = 'L';
		}

		$this->delivery = array(
			"price"=>$this->_deliveryData['price'],
			"additionalPackagePrice"=>$this->_deliveryData['additionalPackagePrice'],
			"distance"=>$this->_deliveryData['distance'],
			"matchedDistance"=>$this->_deliveryData['matchedDistance'],
			"dropoff"=>array(
				"company"=>$customerData['company'],
				"contactName"=>$customerData['contactName'],
				"contactPhone"=>$customerData['contactPhone'],
				"contactEmail"=>$customerData['contactEmail'],
				"address"=>WC()->customer->get_shipping_address().'; '.WC()->customer->get_shipping_address_2(),
				"postCode"=>WC()->customer->get_shipping_postcode(),
				"coordinates"=>$this->_deliveryData['dropoff']['coordinates']
			),
			"packageCount"=>$all_cart_items,
			"size"=>$deliverySize,
			"meta"=>array(
				"orderValue"=>$cartGrossTotal
			)
		);

		setrawcookie('brisqq_delivery', rawurlencode(json_encode($this->delivery)));
		setrawcookie('brisqq_account_id', $accountId);

		return true;
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	protected function check_coverage ($accountId, $dropoffPostCode) {


		$url = $this->_brisqqAPIEndpoint.$this->_checkCoverageUrl;

			if (brisqq_sm_function_checker('curl_version')) {
				$ch = curl_init();

			    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);

			    curl_setopt($ch, CURLOPT_POST, TRUE);
			    curl_setopt($ch, CURLOPT_URL, $url);
			    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('accountId' => $accountId, 'dropoffPostCode' => $dropoffPostCode)));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			    $data = curl_exec($ch);



			    curl_close($ch);
			} elseif (brisqq_sm_function_checker('file_get_contents')) {
				$postdata = http_build_query(
					array('accountId' => $accountId, 'dropoffPostCode' => $dropoffPostCode)
				);

				$opts = array('http' =>
					array(
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => $postdata
					)
				);

				$context = stream_context_create($opts);

				$data = file_get_contents($url, false, $context);



			}

		return $data;

	}


	///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function clear_session () {

		if (isset(WC()->session)) {

			WC()->session->set('brisqq_delivery', null);
			WC()->session->set('brisqq_delivery_id', null);
			WC()->session->set('brisqq_account_id', null);

			setCookie('brisqq_delivery_id', null);
			setCookie('brisqq_delivery', null);
			setCookie('brisqq_account_id', null);

		}
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////

}


# Declare class with custom code
brisqq_sm_custom_class_carrier();

class WC_Brisqq_Shipping_Method extends Brisqq_Carrier_Custom_Code {

	# Loading core + custom code

}



?>
