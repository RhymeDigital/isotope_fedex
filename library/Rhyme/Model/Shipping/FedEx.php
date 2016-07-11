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

namespace Rhyme\Model\Shipping;

use Contao\Cache;
use Contao\Model;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Interfaces\IsotopeShipping;
use Isotope\Isotope;
use Isotope\Model\Shipping as Iso_Shipping;
use stdClass;
use Haste\Units\Mass\Scale;
use Haste\Units\Mass\Weighable;
use Haste\Units\Mass\WeightAggregate;
use FedExAPI\FedExAPI;
use FedExAPI\FedExAPIRatesAndService;
use FedExAPI\FedExAPIShipping;
use FedExAPI\FedExAPITracking;


/**
 * Class FedEx
 *
 * @copyright  HB Agency 2009-2012
 * @author     Blair Winans <bwinans@hbagency.com>
 * @author     Adam Fisher <afisher@hbagency.com>
 */
class FedEx extends Iso_Shipping implements IsotopeShipping
{

    /**
     * Return true or false depending on if shipping method is available
     * @return  bool
     * @todo must check availability for a specific product collection (and not hardcoded to the current cart)
     */
    public function isAvailable()
    {
    	$blnIsAvailable = parent::isAvailable();
    	$fltPrice = $this->getPrice();
    	
    	if ($blnIsAvailable && floatval($fltPrice) == 0)
    	{
	    	return false;
    	}

        return $blnIsAvailable;
    }

    /**
     * Return calculated price for this shipping method
     * @param IsotopeProductCollection
     * @return float
     */
    public function getPrice(IsotopeProductCollection $objCollection = null)
    {
        if (null === $objCollection) {
            $objCollection = Isotope::getCart();
        }
        
        $strPrice = $this->arrData['price'];

		if ($this->isPercentage())
		{
			$fltSurcharge = (float) substr($strPrice, 0, -1);
			$fltPrice = $objCollection->subTotal / 100 * $fltSurcharge;
		}
		else
		{
			$fltPrice = (float) $strPrice;
		}
		
		//Make Call to UPS API to retrieve pricing
		$fltPrice += $this->getLiveRateQuote($objCollection);
        
        return Isotope::calculatePrice($fltPrice, $this, 'fedex', $this->arrData['tax_class']);
    }
    
    
    /**
     * Return calculated price for this shipping method
     * @param IsotopeProductCollection
     * @return float
     */
    protected function getLiveRateQuote(IsotopeProductCollection $objCollection)
    {
        $fltPrice = 0.00;
    
        //get a hash for the cache
		$strService = $this->fedex_enabledService ? : 'FEDEX_GROUND';
		$strPackagingType = $this->fedex_PackingService ?: 'YOUR_PACKAGING';
        $strHash = static::makeHash($objCollection, array($strService,$strPackagingType));
    
        if(!Cache::has($strHash)) {
        
			$arrReturn = $this->buildShipment($objCollection);
			
			if (empty($arrReturn) || count($arrReturn) != 3) {
	            Cache::set($strHash, $fltPrice);
		        return Cache::get($strHash);
			}

			list($arrOrigin, $arrDestination, $arrShipment) = $arrReturn;
			
			//Cache the request so we don't have to run it again as the API is slow
			$strRequestHash = md5(implode('.',$arrDestination) . $arrShipment['service'] . $arrShipment['weight'] . implode('.',$this->Shipment['productids']));
			
			// Construct FEDEX Object: For now, Origin is assumed to be the same for origin and shipping info
			$objFEDEXAPI = new FedExAPIRatesAndService($arrShipment, $arrOrigin, $arrOrigin, $arrDestination); 
			
			$strRequestXML = $objFEDEXAPI->buildRequest('RatingServiceSelectionRequest');
			
			// What the...?
			unset($_SESSION['CHECKOUT_DATA']['FEDEX'][$strRequestHash]);
				
			if( $_SESSION['CHECKOUT_DATA']['FEDEX'][$strRequestHash])
			{
				$arrResponse = $_SESSION['CHECKOUT_DATA']['FEDEX'][$strRequestHash];
			}
			else
			{
				$arrResponse = $objFEDEXAPI->sendRequest($strRequestXML);
				$_SESSION['CHECKOUT_DATA']['FEDEX'][$strRequestHash] = $arrResponse;
			}
			
			if($arrResponse['RateReply']['Notifications']['Severity']=='SUCCESS')
			{
				$fltPrice = floatval($arrResponse['RateReply']['RateReplyDetails']['RatedShipmentDetails'][0]['ShipmentRateDetail']['TotalNetCharge']['Amount']);
			}
			else
			{
				$strLogMessage = sprintf('Error in shipping digest: %s - %s',$arrResponse['RatingServiceSelectionResponse']["Response"]["ResponseStatusDescription"], $arrResponse['RatingServiceSelectionResponse']["Response"]["Error"]["ErrorDescription"]);
				$strMessage = sprintf('%s - %s',$arrResponse['RatingServiceSelectionResponse']["Response"]["ResponseStatusDescription"], $arrResponse['RatingServiceSelectionResponse']["Response"]["Error"]["ErrorDescription"]);
				log_message($strLogMessage, 'error.log');
				//$_SESSION['ISO_ERROR'][] = $strMessage;
				//\System::log($strLogMessage, __METHOD__, TL_ERROR);	
			}
            
            Cache::set($strHash, $fltPrice);
        }
        
        return Cache::get($strHash);
    }
	
	
	/**
	 * Function to build an array for Origin, Destination, and Shipment based on ProductCollection
	 *
	 * @access protected
	 * @param IsotopeProductCollection
	 * @return array
	 */
	protected function buildShipment(IsotopeProductCollection $objCollection)
	{
		if (TL_MODE !== 'FE')
		{
			return array();
		}
		
		$arrItems = $objCollection->getItems();
		$arrProductIds = array();
		$arrProducts = array();
		
		foreach ($arrItems as $objItem)
		{
			$arrProductIds[] = $objItem->id;
			$arrProducts[] = $objItem->getProduct();
		}
		
		//todo account for multiple packages per order
		
		$arrShipment = array(
			'address' => $objCollection->getShippingAddress()->row(),
			'products'	=> $arrProducts,
			'productids' => $arrProductIds
		);
		
		//Build a Shipments array from passed data or the current cart
		$this->Shipment = $arrShipment;
		
		$arrSubDivisionShipping = explode('-',$arrShipment['address']['subdivision']);
	
		$arrDestination = array
		(
			'name'			=> $arrShipment['address']['firstname'] . ' ' . $arrShipment['address']['lastname'],
			'company'		=> $arrShipment['address']['company'],
			'street'		=> strtoupper($arrShipment['address']['street_1']),
			'street2'		=> strtoupper($arrShipment['address']['street_2']),
			'street3'		=> strtoupper($arrShipment['address']['street_3']),
			'city'			=> strtoupper($arrShipment['address']['city']),
			'state'			=> $arrSubDivisionShipping[1],
			'zip'			=> $arrShipment['address']['postal'],
			'country'		=> strtoupper($arrShipment['address']['country'])
		);

		$arrSubDivisionStore = explode('-',Isotope::getConfig()->subdivision);

		$arrOrigin = array
		(
			'name'			=> Isotope::getConfig()->company, //Isotope::getConfig()->firstname . ' ' . Isotope::getConfig()->lastname,
			'phone'			=> Isotope::getConfig()->phone,
			'company'		=> Isotope::getConfig()->company,
			'street'		=> strtoupper(Isotope::getConfig()->street_1),
			'street2'		=> strtoupper(Isotope::getConfig()->street_2),
			'street3'		=> strtoupper(Isotope::getConfig()->street_3),
			'city'			=> strtoupper(Isotope::getConfig()->city),
			'state'			=> $arrSubDivisionStore[1],
			'zip'			=> Isotope::getConfig()->postal,
			'country'		=> strtoupper(Isotope::getConfig()->country),
			'number'		=> Isotope::getConfig()->FedExAccountNumber
		);

		$arrShipment['service'] = (strlen($this->fedex_enabledService) ? $this->fedex_enabledService : 'FEDEX_GROUND');		//Ground for now
		$arrShipment['packaging_type'] = (strlen($this->fedex_PackingService) ? $this->fedex_PackingService : 'YOUR_PACKAGING');

		$arrShipment['pickup_type']	= array
		(
			'code'			=> '03',		//default to one-time, but needs perhaps to be chosen by store admin.
			'description'	=> ''
		);
		
		
		//todo - option for one package per product
		foreach($objCollection->getItems() as $objItem)
		{
			$product = $objItem->getProduct();
			$arrDimensions = deserialize($product->package_dimensions, true);
			$fltWeight = $this->getShippingWeight($objItem, 'lb');
			$fltInsurance = round($this->insuranceIsPercentage() ? ($this->getInsurancePercentage()/100)*$product->getPrice()->getAmount(1) : floatval($this->arrData['fedex_insurance']), 2);
	
            for ($i = 0; $i < $objItem->quantity; $i++) {

				$arrShipment['packages'][] = array
				(
					'packaging'		=> array
					(
						'code'			=> '02',	//counter
						'description'	=> 'Customer Supplied'
					),		
					'insuredValue' => $fltInsurance,
					'units'		=> 'LBS',
					'weight'	=> ceil($fltWeight ?: 1),
					'dimensions'=> array('length'=>floatval($arrDimensions[0]) ?: 1, 'width'=>floatval($arrDimensions[1]) ?: 1, 'height'=>floatval($arrDimensions[2]) ?: 1)
				);
			}
		}

		return array($arrOrigin, $arrDestination, $arrShipment);
	}


    /**
     * Return true if the shipping has a percentage (not fixed) amount
     * @return bool
     */
    public function insuranceIsPercentage()
    {
        return (substr($this->arrData['fedex_insurance'], -1) == '%') ? true : false;
    }

    /**
     * Return percentage amount (if applicable)
     * @return float
     * @throws \UnexpectedValueException
     */
    public function getInsurancePercentage()
    {
        if (!$this->insuranceIsPercentage()) {
            throw new \UnexpectedValueException('Shipping method does not have a insured percentage amount.');
        }

        return (float) substr($this->arrData['fedex_insurance'], 0, -1);
    }
	
	
	
	/**
	 * Calculate the weight of all products in the cart in a specific weight unit
	 *
	 * @access public
	 * @param array
	 * @param string
	 * @return string
	 */
	public function getShippingWeight($objItem, $unit)
	{
        if (null === $objScale) {
            $objScale = new Scale();
        }

        if (!$objItem->hasProduct()) {
            return 0.0;
        }

        $objProduct = $objItem->getProduct();

        if ($objProduct instanceof WeightAggregate) {
            $objWeight = $objProduct->getWeight();

            if (null !== $objWeight) {
            	// Quantity will be taken into account when building packages
                //for ($i = 0; $i < $objItem->quantity; $i++) {
                    $objScale->add($objWeight);
                //}
            }

        } elseif ($objProduct instanceof Weighable) {
        	// Quantity will be taken into account when building packages
            //for ($i = 0; $i < $objItem->quantity; $i++) {
                $objScale->add($objProduct);
            //}
        }

        return $objScale->amountIn($unit);
	}
	
	
	/**
	 * Compile content for printing a shipping label
	 *
	 * @access protected
	 * @param array
	 * @param string
	 * @return string
	 */
	protected function printShippingLabel($strImagePath, $strTitle, $blnOutput=true)
	{
		$objTemplate = new IsotopeTemplate('be_iso_fedexlabel');
		$objTemplate->label = \Environment::get('base') . '/' . $strImagePath;
		$objTemplate->title = $strTitle;

		$this->generatePDF($strTitle, $objTemplate->parse(), true);
	}
	
	
	/**
	 * Return a GIF image from shipping label data or cached image
	 *
	 * @access protected
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	protected function getShippingLabelImage($strImageData, $strCacheName, $width='', $height='', $mode='')
	{
		$strImage = '';
		
		//Check for existing file
		if (file_exists(TL_ROOT . '/' . $strCacheName))
		{
			$strImage = \Image::get($strCacheName, $width, $height, $mode);
		}
		else
		{
			//Create a new one
			$data = base64_decode($strImageData);
			$img = imagecreatefromstring($data);
			if ($img !== false) 
			{
				$img = imagegif($img, TL_ROOT . '/' . $strCacheName);
				$strImage = \Image::get($img, $width, $height, $mode);
			}
		}
	
		return $strImage;
	}
	
	

	/**
	 * Generate a PDF from precompiled HTML content
	 *
	 * @access protected
	 * @param array
	 * @param string
	 * @return string
	 */
	protected function generatePDF($strTitle, $strHTML, $blnOutput=true, $pdf=NULL)
	{
		if (!is_object($pdf))
		{
			// TCPDF configuration
			$l['a_meta_dir'] = 'ltr';
			$l['a_meta_charset'] = $GLOBALS['TL_CONFIG']['characterSet'];
			$l['a_meta_language'] = $GLOBALS['TL_LANGUAGE'];
			$l['w_page'] = 'page';

			// Include library
			require_once(TL_ROOT . '/system/config/tcpdf.php');
			require_once(TL_ROOT . '/plugins/tcpdf/tcpdf.php');

			// Create new PDF document
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);

			// Set document information
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor(PDF_AUTHOR);

			// Remove default header/footer
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);

			// Set margins
			$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

			// Set auto page breaks
			$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

			// Set image scale factor
			$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

			// Set some language-dependent strings
			$pdf->setLanguageArray($l);

			// Initialize document and add a page
			$pdf->AliasNbPages();

			// Set font
			$pdf->SetFont(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN);
		}

		// Start new page
		$pdf->AddPage();

		// Write the HTML content
		$pdf->writeHTML($strHTML, true, 0, true, 0);

		if ($blnOutput)
		{
			// Close and output PDF document
			// @todo $strInvoiceTitle is not defined
			$pdf->lastPage();
			$pdf->Output(standardize(ampersand($strTitle, false), true) . '.pdf', 'D');

			// Stop script execution
			exit;
		}

		return $pdf;
	}

	
	/**
	 * Button Callback for the backend interface for label generation
	 *
	 * @access public
	 * @param int
	 * @return string
	 */
	public function backendInterface($intOrderId, $blnMultiple=false, $intPackageId=0)
	{	
		if($blnMultiple)
		{
			return $this->backendInterfaceMultiple($intOrderId, $intPackageId);
		}
		
		$objOrder = IsotopeOrder::findByPk($intOrderId);
		$strFormId = 'fedex_backend_interface';
		
		//Check for valid order
		if($objOrder === null)
		{
			\System::log('Invalid order id.', __METHOD__, TL_ERROR);	
			$this->redirect('contao/main.php?act=error');
		}
		
		//Get the order's products
		$arrProducts = array();
		$arrItems = (array)$objOrder->getItems();
		
		foreach ($arrItems as $objItem)
		{
			$arrProducts[] = $objItem->getProduct();
		}

		//Build the initial compiled package data array
		$arrPackage = array(
			'id' => $objorder->id,
			'address' => $objOrder->getShippingAddress(),
			'formattedaddress' => $objOrder->getShippingAddress()->generateText(),
			'status' => $GLOBALS['TL_LANG']['ISO']['multipleshipping'][$objOrder->shipping_status],
			'formid' => $strFormId . '_' . $objOrder->id
		);
		
		//Check for an existing label thumbnail and create one if it has not been created
		if($objOrder->fedex_label)
		{
			//Set a cache name
			$strCacheName = 'system/tmp/fedex_label_' . $objOrder->document_number . '_' . $objPackage->id . substr(md5($arrPackage['formattedaddress']), 0, 8) . '.gif';
			$arrPackage['label'] = $this->getShippingLabelImage($objOrder->fedex_label, $strCacheName, 75, 75, 'exact');
			$arrPackage['labelLink'] = \Environment::get('request') . '&printLabel=' . $arrPackage['formid'];
			
			//Now that we have the label created check for request to output to PDF
			if(\Input::get('printLabel') == $arrPackage['formid'])
			{
				$this->printShippingLabel($strCacheName, 'order_' . $objOrder->document_number . '_' . $intPackageId, true);
			}
		}
		
		//Add tracking number	
		if(strlen($objOrder->fedex_tracking_number))
			$arrPackage['tracking'] = $objOrder->fedex_tracking_number;
		
		//Add package products
		$arrPackage['products'] = $arrProducts;
		
		//Data has been submitted. Send request for tracking numbers and label
		if(\Input::post('FORM_SUBMIT')==$arrPackage['formid'])
		{
			$this->Shipment = $arrPackage;
			
			list($arrOrigin, $arrDestination, $arrShipment) = $this->buildShipment();
			
			$objFEDEXAPI = new FedExAPIShipping($arrShipment, $arrOrigin, $arrOrigin, $arrDestination);
			$xmlShip = $objFEDEXAPI->buildRequest();
			$arrResponse = $objFEDEXAPI->sendRequest($xmlShip);
			
			//Request was successful - add the new data to the package
			if((int)$arrResponse['ShipmentAcceptResponse']['Response']['ResponseStatusCode']==1)
			{				
				$objOrder->fedex_tracking_number = $arrResponse['ShipmentAcceptResponse']['ShipmentResults']['ShipmentIdentificationNumber'];
				$objOrder->fedex_label = $arrResponse['ShipmentAcceptResponse']['ShipmentResults']['PackageResults']['LabelImage']['GraphicImage'];
				$objOrder->save();
				
				$strCacheName = 'system/tmp/fedex_label_' . $objOrder->document_number . '_' . $objPackage->id . substr(md5($arrPackage['formattedaddress']), 0, 8) . '.gif';
				$arrPackage['label'] = $this->getShippingLabelImage($objOrder->fedex_label, $strCacheName);
				$arrPackage['tracking'] = $objOrder->fedex_tracking_number;
			}
			else
			{
				//Request returned an error
				$strDescription = $arrResponse['ShipmentAcceptResponse']["Response"]["ResponseStatusDescription"];
				$strError = $arrResponse['ShipmentAcceptResponse']["Response"]["Error"]["ErrorDescription"];
				$_SESSION['TL_ERROR'][] = $strDescription . ' - ' . $strError;
				\System::log(sprintf('Error in shipping digest: %s - %s',$strDescription, $strError), __METHOD__, TL_ERROR);
				$this->redirect('contao/main.php?act=error');
			}
		}
		
		//Set template data
		$objTemplate = new IsotopeTemplate('be_iso_fedex');
		$objTemplate->setData($arrPackage);
		$objTemplate->message = $strMessage ? $strMessage : '';
		$objTemplate->labelHeader = $GLOBALS['TL_LANG']['MSC']['labelLabel'];
		$objTemplate->trackingHeader = $GLOBALS['TL_LANG']['MSC']['trackingNumberLabel'];
		$objTemplate->addressHeader = $GLOBALS['TL_LANG']['MSC']['shippingAddress'];
		$objTemplate->statusHeader = $GLOBALS['TL_LANG']['MSC']['shippingStatus'];
		$objTemplate->submitLabel = $objOrder->shipping_status != 'not_shipped' ? $GLOBALS['TL_LANG']['MSC']['re-ship'] : $GLOBALS['TL_LANG']['MSC']['ship'];
		
		return '<div id="tl_buttons">
	<a href="'.ampersand(str_replace('&key=shipping', '', \Environment::get('request'))).'" class="header_back" title="'.$GLOBALS['TL_LANG']['MSC']['backBT'].'">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>

<h2 class="sub_headline">'.sprintf($GLOBALS['TL_LANG']['ISO']['multipleshipping_backend'], $objOrder->document_number).'</h2>

<div class="tl_formbody_edit">' . 
		
		$objTemplate->parse() .

'</div>

<div class="tl_formbody_submit">
<div class="tl_submit_container">
<input type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c" value="Go back">
</div>
</div>';
			
	}
	
	
	/**
	 * Button Callback for the MultipleShipping backend interface for label generation
	 *
	 * @access public
	 * @param int
	 * @return string
	 */
	public function backendInterfaceMultiple($intOrderId, $intPackageId=0)
	{
		$objOrder = IsotopeOrder::findByPk($intOrderId);
		$strFormId = 'fedex_backend_interface';
		
		//Check for valid order
		if($objOrder === null)
		{
			\System::log('Invalid order id.', __METHOD__, TL_ERROR);	
			$this->redirect('contao/main.php?act=error');
		}
		
		//Get the order's products
		$arrProducts = array();
		$arrItems = (array)$objOrder->getItems();
		
		foreach ($arrItems as $objItem)
		{
			$arrProducts[] = $objItem->getProduct();
		}
		
		//Get the package data
		$objPackage = \Database::getInstance()->execute("SELECT * FROM tl_iso_packages WHERE id=$intPackageId");
		
		if(!$objPackage->numRows) 
			return '<p class="tl_gerror">'.$GLOBALS['TL_LANG']['ISO']['backendShippingNotFound'].'</p>';

		//Build the initial compiled package data array
		$arrPackage = array(
			'id' => $objPackage->id,
			'address' => deserialize($objPackage->order_address, true),
			'formattedaddress' => $objOrder->getShippingAddress()->generateText(),
			'status' => $GLOBALS['TL_LANG']['ISO']['multipleshipping'][$objPackage->status],
			'formid' => $strFormId . '_' . $objPackage->id
		);
		
		//Check for an existing label thumbnail and create one if it has not been created
		if($objPackage->fedex_label)
		{
			//Set a cache name
			$strCacheName = 'system/tmp/fedex_label_' . $objOrder->document_number . '_' . $objPackage->id . substr(md5($arrPackage['formattedaddress']), 0, 8) . '.gif';
			$arrPackage['label'] = $this->getShippingLabelImage($objPackage->fedex_label, $strCacheName, 75, 75, 'exact');
			$arrPackage['labelLink'] = \Environment::get('request') . '&printLabel=' . $arrPackage['formid'];
			
			//Now that we have the label created check for request to output to PDF
			if(\Input::get('printLabel') == $arrPackage['formid'])
			{
				$this->printShippingLabel($strCacheName, 'order_' . $objOrder->document_number . '_' . $intPackageId, true);
			}
		}
		
		//Add tracking number	
		if(strlen($objPackage->fedex_tracking_number))
			$arrPackage['tracking'] = $objPackage->fedex_tracking_number;

		
		//Add package products
		$arrShipmentProducts = \Database::getInstance()->execute("SELECT product_id FROM tl_iso_product_collection_item WHERE package_id=$objPackage->id")->fetchEach('product_id');
		
		foreach($arrProducts as $objProduct)
		{
			if(in_array($objProduct->id, $arrShipmentProducts))
				$arrPackage['products'][] = $objProduct;
		}
		
		//Data has been submitted. Send request for tracking numbers and label
		if(\Input::post('FORM_SUBMIT')==$arrPackage['formid'])
		{
			$this->Shipment = $arrPackage;
			
			list($arrOrigin, $arrDestination, $arrShipment) = $this->buildShipment();
			
			$objFEDEXAPI = new FedExAPIShipping($arrShipment, $arrOrigin, $arrOrigin, $arrDestination);
			$xmlShip = $objFEDEXAPI->buildRequest();
			$arrResponse = $objFEDEXAPI->sendRequest($xmlShip);
			
			//Request was successful - add the new data to the package
			if((int)$arrResponse['ShipmentAcceptResponse']['Response']['ResponseStatusCode']==1)
			{				
				$objOrder->fedex_tracking_number = $arrResponse['ShipmentAcceptResponse']['ShipmentResults']['ShipmentIdentificationNumber'];
				$objOrder->fedex_label = $arrResponse['ShipmentAcceptResponse']['ShipmentResults']['PackageResults']['LabelImage']['GraphicImage'];
				$objOrder->save();
				
				if(\Database::getInstance()->tableExists('tl_iso_packages') && $arrPackage['formid'] != $strFormId . '_' . 'order')
				{
					\Database::getInstance()->prepare("UPDATE tl_iso_packages SET fedex_tracking_number=?, fedex_label=?, status='shipped' WHERE id=?")
								  			->execute($objOrder->fedex_tracking_number, $objOrder->fedex_label, $arrPackage['id']);
				}
				
				$strCacheName = 'system/tmp/fedex_label_' . $objOrder->document_number . '_' . $objPackage->id . substr(md5($arrPackage['formattedaddress']), 0, 8) . '.gif';
				$arrPackage['label'] = $this->getShippingLabelImage($objOrder->fedex_label, $strCacheName);
				$arrPackage['tracking'] = $objOrder->fedex_tracking_number;
			}
			else
			{
				//Request returned an error
				$strDescription = $arrResponse['ShipmentAcceptResponse']["Response"]["ResponseStatusDescription"];
				$strError = $arrResponse['ShipmentAcceptResponse']["Response"]["Error"]["ErrorDescription"];
				$_SESSION['TL_ERROR'][] = $strDescription . ' - ' . $strError;
				\System::log(sprintf('Error in shipping digest: %s - %s',$strDescription, $strError), __METHOD__, TL_ERROR);
				$this->redirect('contao/main.php?act=error');
			}
		}
		
		//Set template data
		$objTemplate = new IsotopeTemplate('be_iso_fedex');
		$objTemplate->setData($arrPackage);
		$objTemplate->labelHeader = $GLOBALS['TL_LANG']['MSC']['labelLabel'];
		$objTemplate->trackingHeader = $GLOBALS['TL_LANG']['MSC']['trackingNumberLabel'];
		$objTemplate->addressHeader = $GLOBALS['TL_LANG']['MSC']['shippingAddress'];
		$objTemplate->statusHeader = $GLOBALS['TL_LANG']['MSC']['shippingStatus'];
		$objTemplate->submitLabel = $objPackage->status != 'not_shipped' ? $GLOBALS['TL_LANG']['MSC']['re-ship'] : $GLOBALS['TL_LANG']['MSC']['ship'];
		
		return $objTemplate->parse();
	}
    

    /**
     * Build a Hash string based on the shipping address
     * @param IsotopeProductCollection
     * @return string
     */
     protected static function makeHash(IsotopeProductCollection $objCollection, $arrExtras=array())
     {
         $strBase = get_called_class();
         $strBase .= !empty($arrExtras) ? implode(',', $arrExtras) : '';
         $objShippingAddress = $objCollection->getShippingAddress();
         $strBase .= $objShippingAddress->street_1;
         $strBase .= $objShippingAddress->city;
         $strBase .= $objShippingAddress->subdivision;
         $strBase .= $objShippingAddress->postal;
         
         // Hash the cart too
         foreach ($objCollection->getItems() as $item)
         {
	         $strBase .= $item->quantity;
	         $strBase .= $item->id;
	         $strBase .= implode(',', $item->getOptions());
         }
         
         return md5($strBase);
     }

	/**
	 * Use output buffer to var dump to a string
	 * 
	 * @param	string
	 * @return	string 
	 */
	public static function varDumpToString($var)
	{
		ob_start();
		var_dump($var);
		$result = ob_get_clean();
		return $result;
	}
     

	
}

