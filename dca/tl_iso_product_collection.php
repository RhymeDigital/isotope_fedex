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


$GLOBALS['TL_DCA']['tl_iso_product_collection']['palettes']['default'] .= ';{fedex_legend},fedex_tracking_number';

$GLOBALS['TL_DCA']['tl_iso_product_collection']['fields']['fedex_tracking_number'] = array
(
	'label'					=> &$GLOBALS['TL_LANG']['tl_iso_product_collection']['fedex_tracking_number'],
	'input_field_callback'	=> array('tl_iso_product_collection_fedex','createTrackingLink'),
	'sql'					=> "varchar(255) NOT NULL default ''",
);
$GLOBALS['TL_DCA']['tl_iso_product_collection']['fields']['fedex_label'] = array
(
	'label'					=> &$GLOBALS['TL_LANG']['tl_iso_product_collection']['fedex_label'],
	'sql'					=> "blob NULL",
);

class tl_iso_product_collection_fedex extends Backend
{
	public function createTrackingLink($dc, $xlabel)
	{
		return '<div class="fedex_tracking_number">'.($dc->activeRecord->fedex_tracking_number ?: '<no shipment has been created>').'</div>';
	}
}