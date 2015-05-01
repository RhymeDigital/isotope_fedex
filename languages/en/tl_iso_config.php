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
 * Fields
 */
$GLOBALS['TL_LANG']['tl_iso_config']['enableFedEx']			= array('Enable FedEx API','Enable the FedEx Shipping Tools API.');
$GLOBALS['TL_LANG']['tl_iso_config']['FedExAccessKey']		= array('Access Key','Please provide the access key supplied by FedEx.');
$GLOBALS['TL_LANG']['tl_iso_config']['FedExUsername']			= array('User name','Please provide your FedEx account user name.');
$GLOBALS['TL_LANG']['tl_iso_config']['FedExPassword']			= array('Password','Please provide your FedEx API password.');
$GLOBALS['TL_LANG']['tl_iso_config']['FedExAccountNumber']	= array('Account number','Please provide your FedEx Account number (for shipping/label generation. To obtain, log into FedEx.com and then click "Account Summary". The value is listed as a FedEx Account Number.)');
$GLOBALS['TL_LANG']['tl_iso_config']['FedExMeterNumber']	= array('Meter number','Please provide your FedEx Meter number (for shipping/label generation.)');
$GLOBALS['TL_LANG']['tl_iso_config']['FedExMode']				= array('FedEx API Mode','Use test to avoid real shipping requests!');

/** 
 * Legends
 */
$GLOBALS['TL_LANG']['tl_iso_config']['fedex_legend']		= 'FedEx API';