<?php

/**
 * FedEx Integration for Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2015 Rhyme Digital, LLC.
 *
 * @author		Blair Winans <blair@rhyme.digital>
 * @author		Adam Fisher <adam@rhyme.digital>
 * @link		http://rhyme.digital
 * @license		http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace FedExAPI;

use FedExAPI\FedExAPI;
 
/**
 * Handles the sending, receiving, and processing of tracking data
 * 
 * @author Russell Winans <russ@wnanscreative.com>
 * @package php_fedex_api
 */
class FedExAPIRatesAndService extends FedExAPI 
{

 	/**
	 * Node name for the Monetary Value
	 * 
	 * @var string
	 */
	const NODE_NAME_MONETARY_VALUE = 'MonetaryValue';
	
	/**
	 * Node name for the Rated Shipment Node
	 * 
	 * @var string
	 */
	const NODE_NAME_RATED_SHIPMENT = 'RatedShipment';
	
	/**
	 * Node name for the root node
	 * 
	 * @var string
	 */
	const NODE_NAME_ROOT_NODE = 'RatingServiceSelectionResponse';
	
	/**
	 * Destination (ship to) data
	 * 
	 * Should be in the format:
	 * $destination = array(
	 * 	'name' => '',
	 * 	'attn' => '',
	 * 	'phone' => '1234567890',
	 * 	'address' => array(
	 * 		'street1' => '',
	 * 		'street2' => '',
	 * 		'city' => '',
	 * 		'state' => '**',
	 * 		'zip' => 12345,
	 * 		'country' => '',
	 * 	),
	 * );
	 * 
	 * @access protected
	 * @var array
	 */
	protected $destination = array();
	
	/**
	 * Shipment data
	 * 
	 * @access protected
	 * @var array
	 */
	protected $shipment = array();
	
	/**
	 * Ship from data
	 * 
	 * @access protected
	 * @var array
	 */
	protected $ship_from = array();
	
	/**
	 * Shipper data
	 * 
	 * @access protected
	 * @var array
	 */
	protected $shipper = array();
	
	/**
	 * Constructor for the Object
	 * 
	 * @access public
	 * @param array $shipment array of shipment data
	 * @param array $shipper array of shipper data
	 * @param array $ship_from array of ship from data
	 * @param array $desination array of destination data
	 */
	public function __construct($shipment, $shipper, $ship_from, $desination) {
	parent::__construct();
		// set object properties
		$this->shipment = $shipment;
		$this->shipper = $shipper;
		$this->fedex_service = $shipment['service'];
		$this->fedex_packaging = $shipment['packaging_type'];
		$this->ship_from = $ship_from;
		$this->destination = $desination;
	} // end function __construct()
	
	/**
	 * Returns charges for each package
	 * 
	 * @return array
	 */
	public function getPackageCharges() {
		$return_value = array();
		
		// iterate over the packages
		$packages = $this->xpath->query(self::NODE_NAME_RATED_SHIPMENT.
			'/RatedPackage', $this->root_node);
			
		foreach ($packages as $package) {
			$return_value[] = array(
				'currency_code' => $this->xpath->query(
					'TotalCharges/CurrencyCode',
					$package)->item(0)->nodeValue,
				'transportation' => $this->xpath->query(
					'TransportationCharges/'.self::NODE_NAME_MONETARY_VALUE,
					$package)->item(0)->nodeValue,
				'service_options' => $this->xpath->query(
					'ServiceOptionsCharges/'.self::NODE_NAME_MONETARY_VALUE,
					$package)->item(0)->nodeValue,
				'total' => $this->xpath->query(
					'TotalCharges/'.self::NODE_NAME_MONETARY_VALUE,
					$package)->item(0)->nodeValue,
			); // end $return_value
		} // end for each package
		
		return $return_value;
	} // end function getPackageCharges()
	
	/**
	 * Returns charges for each package
	 * 
	 * @return array
	 */
	public function getPackageWeight() {
		$return_value = array();
		
		// iterate over the packages
		$packages = $this->xpath->query(self::NODE_NAME_RATED_SHIPMENT.
			'/RatedPackage', $this->root_node);
		foreach ($packages as $package) {
			$return_value[] = array(
				'weight' => $this->xpath->query(
					'BillingWeight/Weight', $package)
					->item(0)->nodeValue,
				'units' => $this->xpath->query(
					'BillingWeight/UnitOfMeasurement/Code', $package)
					->item(0)->nodeValue,
			); // end $return_value
		} // end for each package
		
		return $return_value;
	} // end function getPackageCharges()
	
	/**
	 * Returns charges for the entire shipment
	 * 
	 * @return array
	 */
	public function getShipmentCharges() {
		$rated_shipment = $this->xpath->query(
			self::NODE_NAME_RATED_SHIPMENT, $this->root_node)->item(0);
		
		$return_value = array(
			'currency_code' => $this->xpath->query(
				'TotalCharges/CurrencyCode',
				$rated_shipment)->item(0)->nodeValue,
			'transportation' => $this->xpath->query(
				'TransportationCharges/'.self::NODE_NAME_MONETARY_VALUE,
				$rated_shipment)->item(0)->nodeValue,
			'service_options' => $this->xpath->query(
				'ServiceOptionsCharges/'.self::NODE_NAME_MONETARY_VALUE,
				$rated_shipment)->item(0)->nodeValue,
			'total' => $this->xpath->query(
				'TotalCharges/'.self::NODE_NAME_MONETARY_VALUE,
				$rated_shipment)->item(0)->nodeValue,
		); // end $return_value
		
		return $return_value;
	} // end function
	
	/**
	 * Returns billing weight for the entire shipment
	 * 
	 * @return array
	 */
	public function getShipmentWeight() {
		$rated_shipment = $this->xpath->query(
			self::NODE_NAME_RATED_SHIPMENT, $this->root_node)->item(0);
		
		$return_value = array(
			'weight' => $this->xpath->query(
				'BillingWeight/Weight', $rated_shipment)->item(0)->nodeValue,
			'units' => $this->xpath->query(
				'BillingWeight/UnitOfMeasurement/Code', $rated_shipment)
				->item(0)->nodeValue,
		); // end $return_value
		
		return $return_value;
	} // end function getShipmentWeight()
	
	/**
	 * Returns any warnings from the response
	 * 
	 * @return array
	 */
	public function getWarnings() {
		$warnings = $this->xpath->query(self::NODE_NAME_RATED_SHIPMENT.
			'/RatedShipmentWarning', $this->root_node);
		
		// iterate over the warnings
		$return_value = array();
		foreach ($warnings as $warning) {
			$return_value[] = $warning->nodeValue;
		} // end for each warning
		
		return $return_value;
	} // end function getWarnings()
	
	/**
	 * Builds the XML used to make the request
	 * 
	 * If $customer_context is an array it should be in the format:
	 * $customer_context = array('Element' => 'Value');
	 * 
	 * @access public
	 * @param array|string $cutomer_context customer data
	 * @return string $return_value request XML
	 */
	public function buildRequest($customer_context = null) {
		// create the new dom document
		$xml = new \DOMDocument('1.0','UTF-8');
		
		$track = $xml->appendChild($xml->createElementNS("http://fedex.com/ws/rate/v7",'ns:RateRequest'));
		$track->setAttributeNode(new \DOMAttr('xmlns:xsi',"http://www.w3.org/2001/XMLSchema-instance"));

		$auth = $track->appendChild($xml->createElement('ns:WebAuthenticationDetail'));

		$credentials = $auth->appendChild($xml->createElement('ns:UserCredential'));
		$credentials->appendChild($xml->createElement('ns:Key',$this->access_key));
		$credentials->appendChild($xml->createElement('ns:Password',$this->password));
		
		$details = $track->appendChild($xml->createElement('ns:ClientDetail'));
		$details->appendChild($xml->createElement('ns:AccountNumber',$this->accountNumber));
		$details->appendChild($xml->createElement('ns:MeterNumber',$this->meterNumber));	
	
		$version = $track->appendChild($xml->createElement('ns:Version'));
		$version->appendChild($xml->createElement('ns:ServiceId','crs'));
		$version->appendChild($xml->createElement('ns:Major',7));
		$version->appendChild($xml->createElement('ns:Intermediate',0));
		$version->appendChild($xml->createElement('ns:Minor',0));

		$shipment = $track->appendChild($xml->createElement('ns:RequestedShipment'));	
		$shipment->appendChild($xml->createElement('ns:DropoffType','REQUEST_COURIER'));

		$shipment->appendChild($xml->createElement('ns:ServiceType', $this->fedex_service));	
		$shipment->appendChild($xml->createElement('ns:PackagingType',$this->fedex_packaging));

		$this->buildRequest_Shipper($shipment,$xml);
		$this->buildRequest_Destination($shipment,$xml);
		
		$shipment = $this->buildRequest_Shipment($shipment,$xml);
		
		return $xml->saveXML();
	} // end function buildRequest()
	
	/**
	 * Builds the destination elements
	 * 
	 * @access protected
	 * @param DOMElement $dom_element
	 * @return DOMElement
	 */
	protected function buildRequest_Destination(&$dom_element,$xml) {
		/** build the destination element and its children **/
		$destination = $dom_element->appendChild($xml->createElement('ns:Recipient'));
		
		$address = $destination->appendChild($xml->createElement('ns:Address'));
		
		/** build the address elements children **/
		$address->appendChild($xml->createElement('ns:StreetLines',$this->destination['street']));
		
		// check to see if there is a second steet line
		if (isset($this->destination['street2']) &&
			!empty($this->destination['street2'])) {
			$address->appendChild($xml->createElement('ns:StreetLines',$this->destination['street2']));
		} // end if there is a second street line
		
		// build the rest of the address
		$address->appendChild($xml->createElement('ns:City',$this->destination['city']));
		$address->appendChild($xml->createElement('ns:StateOrProvinceCode',$this->destination['state']));
		$address->appendChild($xml->createElement('ns:PostalCode',$this->destination['zip']));
		$address->appendChild($xml->createElement('ns:CountryCode',$this->destination['country']));
		
		return $destination;
	} // end function buildRequest_Destination()
	
	/**
	 * Buildes the package elements
	 * 
	 * @access protected
	 * @param DOMElement $dom_element
	 * @param array $package
	 * @return DOMElement
	 * 
	 * @todo determine if the package description is needed
	 */
	protected function buildRequest_Package(&$dom_element, $package, $i, $xml) {
		/** build the package and packaging type **/
		$package_element = $dom_element->appendChild($xml->createElement('ns:RequestedPackageLineItems'));
		$package_element->appendChild($xml->createElement('ns:SequenceNumber', $i));

		$package_insured = $package_element->appendChild($xml->createElement('ns:InsuredValue'));		
		$package_insured->appendChild($xml->createElement('ns:Currency', 'USD'));		
		$package_insured->appendChild($xml->createElement('ns:Amount', $package['insuredValue']));		
	
		$package_weight = $package_element->appendChild($xml->createElement('ns:Weight'));
		$package_weight->appendChild($xml->createElement('ns:Units', 'LB'));
		$package_weight->appendChild($xml->createElement('ns:Value', $package['weight']));
		
		$package_dimensions = $package_element->appendChild($xml->createElement('ns:Dimensions'));		
		$package_length = $package_dimensions->appendChild($xml->createElement('ns:Length',$package['dimensions']['length']));				
		$package_width = $package_dimensions->appendChild($xml->createElement('ns:Width',$package['dimensions']['width']));
		$package_height = $package_dimensions->appendChild($xml->createElement('ns:Height',$package['dimensions']['height']));
		$package_dimensions->appendChild($xml->createElement('ns:Units', 'IN'));	

		return $package_element;
	} // end function buildRequest_Package()
	
	/**
	 * Builds the shipment elements
	 * 
	 * @access protected
	 * @param DOMElement $dom_element
	 * @return DOMElement
	 */
	protected function buildRequest_Shipment(&$shipment,$xml) {
		                
		$shipment->appendChild($xml->createElement('ns:RateRequestTypes','ACCOUNT'));                 
		$shipment->appendChild($xml->createElement('ns:PackageCount',sizeof($this->shipment['packages'])));                 
		$shipment->appendChild($xml->createElement('ns:PackageDetail','INDIVIDUAL_PACKAGES')); 
		$i = 0;
		// iterate over the pacakges to create the package element
		foreach ($this->shipment['packages'] as $package) {
			$i++;
			$this->buildRequest_Package($shipment, $package, $i, $xml);
		} // end for each package
		
		//$this->buildRequest_ServiceOptions($shipment);
		return $shipment;
	} // end function buildRequest_Shipment()
	
	/**
	 * Builds the shipper elements
	 * 
	 * @access protected
	 * @param DOMElement $dom_element
	 * @return DOMElement
	 */
	protected function buildRequest_Shipper(&$dom_element,$xml) {	
		/** build the destination element and its children **/
		$ship = $dom_element->appendChild($xml->createElement('ns:Shipper'));

		$address = $ship->appendChild($xml->createElement('ns:Address'));
		/** build the address elements children **/
		$address->appendChild($xml->createElement('ns:StreetLines',$this->shipper['street']));
		
		// check to see if there is a second steet line
		if (isset($this->shipper['street2']) &&
			!empty($this->shipper['street2'])) {
			$address->appendChild($xml->createElement('ns:StreetLines',$this->shipper['street2']));
		} // end if there is a second street line
		
		
		// build the rest of the address
		$address->appendChild($xml->createElement('ns:City',$this->shipper['city']));
		$address->appendChild($xml->createElement('ns:StateOrProvinceCode',$this->shipper['state']));
		$address->appendChild($xml->createElement('ns:PostalCode',$this->shipper['zip']));
		$address->appendChild($xml->createElement('ns:CountryCode',$this->shipper['country']));
		
		return $ship;
	} // end function buildRequest_Shipper()
	
	/**
	 * Returns the name of the servies response root node
	 * 
	 * @access protected
	 * @return string
	 * 
	 * @todo remove after phps self scope has been fixed
	 */
	protected function getRootNodeName() {
		return self::NODE_NAME_ROOT_NODE;
	} // end function getRootNodeName()
}