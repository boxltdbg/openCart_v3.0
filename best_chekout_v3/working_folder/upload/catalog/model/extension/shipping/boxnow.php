<?php
class ModelExtensionShippingBoxnow extends Model {
	function getQuote($address) {
		$this->load->language('extension/shipping/boxnow');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_boxnow_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if (!$this->config->get('shipping_boxnow_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}
	
		$method_data = array();

		$locker = '';

		if ($status) {
			$quote_data = array();
			
			if( 
			isset($this->session->data['boxnow_address'])
			&& 
			$this->session->data['boxnow_address']
			&&		
			isset($this->session->data['boxnow_name'])
			&& 
			$this->session->data['boxnow_name']			
			) {
				$locker = $this->language->get('selected_boxnow').' '.$this->session->data['boxnow_address'].' ['.$this->session->data['boxnow_name'];
			};

		
            //Shipping cost calculation based on user-defined weight ranges
            $weight = $this->cart->getWeight(); // Get the total weight of the cart

            $shipping_boxnow_weight_value = $this->config->get('shipping_boxnow_weight_value');

            // Initialize the cost
            $cost = 0;

            // Loop through weight ranges and calculate cost
           foreach ($shipping_boxnow_weight_value as $weight_range) {
           $from = (float)$weight_range['from'];
           $to = (float)$weight_range['to'];
           $price = (float)$weight_range['price'];

          // Check if the total weight falls within this range
           if ($weight >= $from && $weight <= $to) {
           $cost = $price;
           break; // Exit the loop once a matching range is found
    }
}
			

			 if ($this->config->get('shipping_boxnow_free_shipping') && $this->cart->getSubTotal() >= $this->config->get('shipping_boxnow_free_shipping')) {
			 	$cost = 0;
			 }

			$quote_data['boxnow'] = array(
				'code'         => 'boxnow.boxnow',
				'title'        => $this->language->get('text_description'),
				'cost'         => $cost,
				'tax_class_id' => $this->config->get('shipping_boxnow_tax_class_id'),
				'boxnow_weight'=> $this->config->get('shipping_boxnow_weight_value'),
				'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_boxnow_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
			);

			$method_data = array(
				'code'       => 'boxnow',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_boxnow_sort_order'),
				'error'      => false
			);
		}
		
		return $method_data;
	}
	
	function setRequest($order = array(), $request =  array()) {		
		if($order && isset($request['locker_id'])) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "boxnow_requests SET order_id = '" . (int)$order['order_id'] . "',locker_id='".(int)$request['locker_id']."', status='2' ");
		};
	}
	
}