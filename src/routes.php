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

	Route::get('/'.$appNameUrl, 'AdminPanelController@index');
	Route::get('/'.$appNameUrl.'/list-view', 'AdminPanelController@listView');
	Route::get('/'.$appNameUrl.'/card-view', 'AdminPanelController@cardView');
	Route::get('/'.$appNameUrl.'/update-view', 'AdminPanelController@updateView');
	Route::get('/'.$appNameUrl.'/create-view', 'AdminPanelController@createView');
	Route::get('/'.$appNameUrl.'/chart-view', 'AdminPanelController@chartView');
	Route::get('/'.$appNameUrl.'/message-view', 'AdminPanelController@messageView');
	Route::get('/'.$appNameUrl.'/login-view', 'AdminPanelController@loginView');
	Route::get('/'.$appNameUrl.'/export-records-csv', 'AdminPanelController@exportRecordsCsv');
	Route::get('/'.$appNameUrl.'/export-records-xml', 'AdminPanelController@exportRecordsXml');
	Route::get('/'.$appNameUrl.'/auto-generate', 'AdminPanelController@autoGenerate');
	
	Route::get('/'.$appNameUrl.'/api/delete-record', 'AdminPanelController@deleteRecord');
	Route::post('/'.$appNameUrl.'/api/create-record', 'AdminPanelController@createRecord');
	Route::post('/'.$appNameUrl.'/api/update-record', 'AdminPanelController@updateRecord');
	Route::post('/'.$appNameUrl.'/api/login', 'AdminPanelController@login');
	Route::get('/'.$appNameUrl.'/api/logout', 'AdminPanelController@logout');
	Route::post('/'.$appNameUrl.'/api/action', 'AdminPanelController@action');
	Route::post('/'.$appNameUrl.'/api/get-chart-data', 'AdminPanelController@getChartData');
});