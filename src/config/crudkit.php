<?php

return [

	//### Admin Login ###
	'username' => 'admin',
	'password' => 'admin',
	
	//### General ###
	'language' => 'de',
	'app_name' => 'CRUDKit',
	'app_name_url'=> 'app', //Your Laravel APP_URL plus this name is the URL to your crudkit. Do not use 'crudkit' or the name of another laravel app.
	'skin' => 'blue', //blue,blue-light,yellow,yellow-light,green,green-light,purple,purple-light,red,red-light,black,black-light
	'records_per_page' => 5, //How many record are shown in lists 
	'pagination_limit' => 2, //How many page does the pagination show (in each direction)
	'fontsize' => '16px', //Overall font size. Provide a CSS compatible value (Example: 14px, 2em, 80%,...).
	'show_qrcode' => true,
	'qrcode_size' => 1.0,
	
	//### Technical ###
	'version' => 'dev-master',
	'use_custom_error_page' => false, //Displays a fancy Error Page
	'csv_export_with_bom' => true, //Add Byte Order Mark (BOM) to CSV Files
	'export_enum_label' => true,//true = Exports the Enum label, false = Exports the actual value
	'export_all_columns' => true, //true = Exports all columns defined for the table, false = exports only summary columns (list)
	'startpage' =>
	[
		'page-id' => 'orders', 
		'type' => 'list',
		'parameters' => []
	],
	'formats_ui' =>
	[
		'datetime' => 'd.m.Y H:i:s',
		'date' => 'd.m.Y',
		'time' => 'H:i:s'
	]
];
