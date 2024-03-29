<?php
/** 
 * Defines the routes (links).
 * 
 * @internal
*/

use Illuminate\Support\Facades\Route;
use Alddesign\Crudkit\Classes\DataProcessor as dp;

Route::group(['middleware' => 'web', 'namespace' => 'Alddesign\Crudkit\Controllers'], function () 
{
	$appNameUrl = strval(config('crudkit.app_name_url', 'app'));
	$appNameUrl = $appNameUrl === '' ? '' : $appNameUrl . '/';

	Route::get('/'.$appNameUrl, 'CrudkitController@index');
	Route::get('/'.$appNameUrl.'list-view', 'CrudkitController@listView');
	Route::get('/'.$appNameUrl.'card-view', 'CrudkitController@cardView');
	Route::get('/'.$appNameUrl.'update-view', 'CrudkitController@updateView');
	Route::get('/'.$appNameUrl.'create-view', 'CrudkitController@createView');
	Route::get('/'.$appNameUrl.'chart-view', 'CrudkitController@chartView');
	Route::get('/'.$appNameUrl.'message-view', 'CrudkitController@messageView');
	Route::get('/'.$appNameUrl.'login-view', 'CrudkitController@loginView');
	Route::get('/'.$appNameUrl.'export-records-csv', 'CrudkitController@exportRecordsCsv');
	Route::get('/'.$appNameUrl.'export-records-xml', 'CrudkitController@exportRecordsXml');
	Route::get('/'.$appNameUrl.'auto-generate', 'CrudkitController@autoGenerate');
	
	Route::get('/'.$appNameUrl.'api/delete-record', 'CrudkitController@deleteRecord');
	Route::post('/'.$appNameUrl.'api/create-record', 'CrudkitController@createRecord');
	Route::post('/'.$appNameUrl.'api/update-record', 'CrudkitController@updateRecord');
	Route::post('/'.$appNameUrl.'api/login', 'CrudkitController@login'); 
	Route::get('/'.$appNameUrl.'api/logout', 'CrudkitController@logout');
	Route::post('/'.$appNameUrl.'api/action', 'CrudkitController@action');
	Route::post('/'.$appNameUrl.'api/get-chart-data', 'CrudkitController@getChartData');
	Route::post('/'.$appNameUrl.'api/set-theme', 'CrudkitController@setTheme');
	//Route::get('/'.$appNameUrl.'api/backup', 'CrudkitController@backup'); #Functionallity removed

	Route::post('/'.$appNameUrl.'api/ajax-many-to-one', 'CrudkitController@ajaxManyToOne');
	Route::post('/'.$appNameUrl.'api/ajax-custom', 'CrudkitController@ajaxCustom');
});