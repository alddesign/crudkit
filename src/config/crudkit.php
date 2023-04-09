<?php

return [
	//### Admin Login ###
	'username' => 'admin',
	'password' => 'admin',
	
	//### General ###
	'language' => 'de',
	'app_name' => 'CRUDKit',
	'app_name_url'=> 'app', //Your Laravel APP_URL plus this name is the URL to your crudkit. Do not use 'crudkit' or the name of another laravel app.
	'icons' => //Favicon icon paths. Relative to the laravel public folder. Its recommended to provide 32,64,128 and 192px (multidevice support)
	[
		'32x32' => 'crudkit/img/crudkit_logo_32.png',
		'64x64' => 'crudkit/img/crudkit_logo_64.png', 
		'128x128' => 'crudkit/img/crudkit_logo_128.png', 
		'192x192' => 'crudkit/img/crudkit_logo_192.png'
	],
	'skin' => 'blue', //blue,blue-light,yellow,yellow-light,green,green-light,purple,purple-light,red,red-light,black,black-light
	'accent' => 'blue', //blue,yellow,green,purple,red,black
	'theme_selector' => true, //Show or hide the theme selector in the menu
	'records_per_page' => 10, //How many record are shown in lists per page
	'pagination_limit' => 2, //How many pages the pagination shows (Example 2 looks like this: 1,2,[3],4,5)
	'fontsize' => '16px', //Overall font size. Need to be a css compatible value.
	'show_qrcode' => false, //Show QR codes with link to individual pages
	
	//### Technical ###
	'local_timezone' => 'Europe/Vienna', //Used mainly for dates/times in output filenames (xml, csv). This does not change the PHP default timezone - that should be done in laravel (config/app.php)
	'records_text_trim_length' => 50, //Max. length of texts shown in list pages.
	'csv_export_with_bom' => true, //Add Byte Order Mark (BOM) to CSV Files. You should enable this if you want to view CSVs in Microsoft Excel.
	'csv_export_field_separator' => ';', //The separator/delimiter for fields in csv.
	'export_all_columns' => true, //true = Exports all columns defined for the table, false = exports only summary columns (list)
	'export_enum_label' => true, //true = Exports the Enum label, false = Exports the actual value
	'export_lookups' => true,
	'startpage' =>
	[
		'page-id' => 'contact', 
		'type' => 'list',
		'parameters' => []
	],
	'formats_ui' => //Defines how certain datatypes should be displayed
	[
		'datetime' => 'd.m.Y H:i:s',
		'date' => 'd.m.Y',
		'time' => 'H:i:s',
		'decimal_places' => 2,
		'thousands_separator' => '.',
		'decimal_separator' => ','
	],
	'doctrine_dbal_cache' => true, //Enable or disable caching for DB::getDoctrineColumn(); (very time consuming operation). For certain operations Doctrine\DBAL fetches all columns of a DB table, to get extended informations like default-value, not-null...
	'doctrine_dbal_cache_ttl' => 3600 * 24, //Time before the cache has to be refreshed (in seconds),
];
