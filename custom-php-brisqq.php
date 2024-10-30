<?php

function brisqq_sm_custom_class_carrier() {
	class Brisqq_Carrier_Custom_Code extends Brisqq_Carrier_Core {

		public function calculate_shipping ($package=array()) {

			// send the final rate to the user.
			$this->add_rate( array(
				'id' => $this->id,
				'label' => $this->title,
				'cost' => $this->_deliveryData['price']
			));

		}
	}
}


function brisqq_sm_custom_class_observer() {
	class Brisqq_Observer_Custom_Code extends Brisqq_Observer {

	}
}


?>