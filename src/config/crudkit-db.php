<?php

return [
	'mysql' =>
	[
		'delimiters' => ['`', '`'],
		'formats' => 	
		[
			'datetime' => 'Y-m-d H:i:s',
			'date' => 'Y-m-d',
			'time' => 'H:i:s'
		],
		'empty_values' => 	
		[
			'datetime' => '0000-00-00 00:00:00',
			'date' => '0000-00-00',
			'time' => '00:00:00'
		],
	],
	'sqlite' =>
	[
		'delimiters' => ['"', '"'],
		'formats' => 	
		[
			'datetime' => 'Y-m-d H:i:s',
			'date' => 'Y-m-d',
			'time' => 'H:i:s'
		],
		'empty_values' => 	
		[
			'datetime' => '0000-00-00 00:00:00',
			'date' => '0000-00-00',
			'time' => '00:00:00'
		],
	],
	'sqlsrv' =>
	[
		'delimiters' => ['[', ']'],
		'formats' => 	
		[
			'datetime' => 'Y-m-d H:i:s',
			'date' => 'Y-m-d',
			'time' => 'H:i:s'
		],
		'empty_values' => 	
		[
			'datetime' => '1900-01-01 00:00:00',
			'date' => '1900-01-01',
			'time' => '00:00:00'
		],
	],
	'__default__' => //do not change or delete the default configuration
	[
		'delimiters' => ['"'. '"'],
		'formats' => 	
		[
			'datetime' => 'Y-m-d H:i:s',
			'date' => 'Y-m-d',
			'time' => 'H:i:s'
		],
		'empty_values' => 	
		[
			'datetime' => '0000-00-00 00:00:00',
			'date' => '0000-00-00',
			'time' => '00:00:00'
		],
	]
];
