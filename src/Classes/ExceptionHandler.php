<?php

namespace Alddesign\Crudkit\Classes;

use Illuminate\Http\Request;
use Alddesign\Crudkit\Classes\DataProcessor as dp;
use \Exception;
use View;

abstract class ExceptionHandler
{
	/* 
	* Should replace default error handling. Take a look at /app/Exceptions/Handler.php -> render() 
	* Best way is to return a view.
	*/
	public static function render(Request $request, Exception $exception)
	{
		try
		{
			$view = view('crudkit::message', 
			[
				'title' => 'Ein Fehler ist aufgetreten...',
				'type' => 'danger',
				'pageTitleText' => '',
				'pageId' => '___MESSAGE___',
				'pageType' => 'message',
				'pageName' => 'Fehler',
				'message' => $exception->getMessage(),
				'pageMap' => []
			])->render();
		}
		catch(Exception $x)
		{
			echo($x->getMessage());
			die();
		}
		
		return response($view);
	}
}