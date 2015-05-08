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
 * Register PSR-0 namespace
 */
NamespaceClassLoader::add('Rhyme', 'system/modules/isotope_fedexshipping/library');
NamespaceClassLoader::add('FedExAPI', 'system/modules/isotope_fedexshipping/vendorapi');


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_iso_fedextimeintransit'   => 'system/modules/isotope_fedexshipping/templates',
	'mod_iso_fedextracking'        => 'system/modules/isotope_fedexshipping/templates',
	'mod_iso_fedexratesandservice' => 'system/modules/isotope_fedexshipping/templates',
	'be_iso_fedexlabel'            => 'system/modules/isotope_fedexshipping/templates',
	'be_iso_fedex'                 => 'system/modules/isotope_fedexshipping/templates',
));
