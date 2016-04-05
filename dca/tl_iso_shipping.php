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
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_iso_shipping']['palettes']['fedex_single']	= '{title_legend},type,name,label;{note_legend:hide},note;{price_legend},price,tax_class;{fedex_legend},fedex_enabledService,fedex_PackagingService,fedex_insurance;{config_legend},weight_unit,countries,subdivisions,minimum_total,maximum_total,minimum_weight,maximum_weight,product_types,product_types_condition,config_ids;{expert_legend:hide},guests,protected;{enabled_legend},enabled';
$GLOBALS['TL_DCA']['tl_iso_shipping']['palettes']['fedex_multiple']	= '{title_legend},type,name,label;{note_legend:hide},note;{price_legend},price,tax_class;{fedex_legend},fedex_enabledService,fedex_PackagingService,fedex_insurance;{config_legend},weight_unit,countries,subdivisions,minimum_total,maximum_total,minimum_weight,maximum_weight,product_types,product_types_condition,config_ids;{expert_legend:hide},guests,protected;{enabled_legend},enabled';


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_iso_shipping']['fields']['fedex_enabledService'] = array
(
	'label'					  => &$GLOBALS['TL_LANG']['tl_iso_shipping']['fedex_enabledService'],
	'exclude'				  => true,
	'inputType'			  	  => 'select',
	'options'				  => $GLOBALS['TL_LANG']['tl_iso_shipping']['fedex_service'],
	'eval'					  => array('mandatory'=>true),
	'sql'					  => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_iso_shipping']['fields']['fedex_PackagingService'] = array
(
	'label'					  => &$GLOBALS['TL_LANG']['tl_iso_shipping']['fedex_PackagingService'],
	'exclude'				  => true,
	'inputType'			  	  => 'select',
	'options'				  => $GLOBALS['TL_LANG']['tl_iso_shipping']['fedex_packing'],
	'eval'					  => array('mandatory'=>true),
	'sql'					  => "varchar(255) NOT NULL default ''",
);

$GLOBALS['TL_DCA']['tl_iso_shipping']['fields']['fedex_insurance'] = array
(
	'label'					  => &$GLOBALS['TL_LANG']['tl_iso_shipping']['fedex_insurance'],
	'exclude'				  => true,
	'inputType'			  	  => 'text',
	'default'			  	  => '0',
    'eval'                    => array('maxlength'=>16, 'rgxp'=>'discount', 'tl_class'=>'clr w50'),
	'sql'					  => "varchar(255) NOT NULL default ''",
);