<?php

return [
	//### Admin Login ###
	'username' => 'admin',
	'password' => 'admin',
	
	//### General ###
	'language' => 'de',
	'app_name' => 'CRUDKit',
	'app_name_url'=> 'app', //Your Laravel APP_URL plus this name is the URL to your crudkit. Do not use 'crudkit' or the name of another laravel app.
	'icons' => ['32x32' => 'crudkit/img/crudkit_logo_32.png', '128x128' => 'crudkit/img/crudkit_logo_128.png', '192x192' => 'crudkit/img/crudkit_logo_192.png'], //icon paths. relative to the /public folder. format ['32x32' => 'path/to/image32px.png', '...']
	'skin' => 'blue', //blue,blue-light,yellow,yellow-light,green,green-light,purple,purple-light,red,red-light,black,black-light
	'accent' => 'blue', //blue,yellow,green,purple,red,black
	'theme_selector' => true, //Show the theme selector in menu
	'records_per_page' => 8, //How many record are shown in lists 
	'pagination_limit' => 2, //How many page does the pagination show (in each direction)
	'fontsize' => '16px', //Overall font size. Provide a CSS compatible value (Example: 14px, 2em, 80%,...).
	'show_qrcode' => true,
	'timezone' => 'Europe/Vienna', //Attention: this is only for certain outputs like export files. All Datetime/Time values will be stored "as is" in the DB. See https://www.php.net/manual/en/timezones.php
	
	//### Technical ###
	'records_text_trim_length' => 50,
	'use_custom_error_page' => false, //Displays a fancy Error Page
	'csv_export_with_bom' => true, //Add Byte Order Mark (BOM) to CSV Files
	'export_all_columns' => true, //true = Exports all columns defined for the table, false = exports only summary columns (list)
	'export_enum_label' => true, //true = Exports the Enum label, false = Exports the actual value
	'export_boolean_label' => true, //true = export "yes" or "no", false = export 1 or 0
	'export_lookups' => true,
	'startpage' =>
	[
		'page-id' => 'contact', 
		'type' => 'list',
		'parameters' => []
	],
	'formats_ui' =>
	[
		'datetime' => 'd.m.Y H:i:s',
		'date' => 'd.m.Y',
		'time' => 'H:i:s'
	],

	//### Backup ###
	'daily_backup' => true,
	'daily_backup_src_folder' => base_path('/vendor/alddesign/'), //use absolute filesystem paths
	'daily_backup_dest_folder' => base_path('../cheri-crm-backup/daily/'), //use absolute filesystem paths
	'daily_backup_max_backups' => 90,
	'monthly_backup' => true,
	'monthly_backup_src_folder' => base_path('/vendor/alddesign/'), //use absolute filesystem paths
	'monthly_backup_dest_folder' => base_path('../cheri-crm-backup/monthly/'), //use absolute filesystem paths
	'monthly_backup_max_backups' => 12,
	'backup_key' => 'r9snq5Az7gQwS0hOgMot' //Change this vaule! Backup is started via Cronjob http://yourhost.com/<app_name_url>/api/backup?key=<backup_key>
];
