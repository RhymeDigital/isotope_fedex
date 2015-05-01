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


/**
 * Table tl_iso_products
 */
$GLOBALS['TL_DCA']['tl_iso_product']['fields']['package_dimensions'] = array
(
	'label'					=> &$GLOBALS['TL_LANG']['tl_iso_product']['package_dimensions'],
	'inputType'				=> 'text',
	'attributes'			=> array('legend' => 'shipping_legend'),
	'eval'					=> array('tl_class'	=> 'clr', 'multiple'=>true, 'size'=>3),
	'sql'					=> "varchar(255) NOT NULL default ''",
);