<?php
/** 
 * Defines the routes (links) 
 * 
 * @internal
*/

Route::group(['middleware' => 'web', 'namespace' => 'Alddesign\Crudkit\Controllers'], function () 
{
	$appNameUrl = config('crudkit.app_name_url', 'app');
	if(gettype($appNameUrl) != 'string' || empty($appNameUrl) || $appNameUrl == 'crudkit')
	{
		$appNameUrl = 'app';
	}

	Route::get('/'.$appNameUrl, 'CrudkitController@index');
	Route::get('/'.$appNameUrl.'/list-view', 'CrudkitController@listView');
	Route::get('/'.$appNameUrl.'/card-view', 'CrudkitController@cardView');
	Route::get('/'.$appNameUrl.'/update-view', 'CrudkitController@updateView');
	Route::get('/'.$appNameUrl.'/create-view', 'CrudkitController@createView');
	Route::get('/'.$appNameUrl.'/chart-view', 'CrudkitController@chartView');
	Route::get('/'.$appNameUrl.'/message-view', 'CrudkitController@messageView');
	Route::get('/'.$appNameUrl.'/login-view', 'CrudkitController@loginView');
	Route::get('/'.$appNameUrl.'/export-records-csv', 'CrudkitController@exportRecordsCsv');
	Route::get('/'.$appNameUrl.'/export-records-xml', 'CrudkitController@exportRecordsXml');
	Route::get('/'.$appNameUrl.'/auto-generate', 'CrudkitController@autoGenerate');
	
	Route::get('/'.$appNameUrl.'/api/delete-record', 'CrudkitController@deleteRecord');
	Route::post('/'.$appNameUrl.'/api/create-record', 'CrudkitController@createRecord');
	Route::post('/'.$appNameUrl.'/api/update-record', 'CrudkitController@updateRecord');
	Route::post('/'.$appNameUrl.'/api/login', 'CrudkitController@login');
	Route::get('/'.$appNameUrl.'/api/logout', 'CrudkitController@logout');
	Route::post('/'.$appNameUrl.'/api/action', 'CrudkitController@action');
	Route::post('/'.$appNameUrl.'/api/get-chart-data', 'CrudkitController@getChartData');
});