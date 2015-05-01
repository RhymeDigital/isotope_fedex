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
 * Table tl_iso_config
 */
$GLOBALS['TL_DCA']['tl_iso_config']['palettes']['__selector__'][] = 'enableFedEx';
$GLOBALS['TL_DCA']['tl_iso_config']['palettes']['default'] .= ';{fedex_legend},enableFedEx';
$GLOBALS['TL_DCA']['tl_iso_config']['subpalettes']['enableFedEx'] = 'FedExAccessKey,FedExUsername,FedExPassword,FedExAccountNumber,FedExMeterNumber,FedExMode';

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_iso_config']['fields']['enableFedEx'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_config']['enableFedEx'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'					  => array('doNotCopy'=>true, 'submitOnChange'=>true),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_config']['fields']['FedExAccessKey'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_config']['FedExAccessKey'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "varchar(255) NOT NULL default ''"
);
		
$GLOBALS['TL_DCA']['tl_iso_config']['fields']['FedExUsername'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_config']['FedExUsername'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50 clr'),
	'sql'                     => "varchar(255) NOT NULL default ''"
);
		
$GLOBALS['TL_DCA']['tl_iso_config']['fields']['FedExPassword'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_config']['FedExPassword'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'hideInput'=>true),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_config']['fields']['FedExAccountNumber'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_config']['FedExAccountNumber'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_config']['fields']['FedExMeterNumber'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_config']['FedExMeterNumber'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_config']['fields']['FedExMode'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_config']['UpsMode'],
	'exclude'                 => true,
	'default'				  => 'test',
	'inputType'               => 'select',
	'options'				  => array('test','live'),
	'eval'					  => array('doNotCopy'=>true, 'tl_class'=>'w50'),
	'reference'				  => &$GLOBALS['TL_LANG']['MSC']['apiMode'],
	'sql'                     => "varchar(8) NOT NULL default ''"
);