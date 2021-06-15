<?php
/**
 * Class ExceptionHandler
 */
namespace Alddesign\Crudkit\Classes;

use Illuminate\Support\Facades\Request;
use Alddesign\Crudkit\Classes\DataProcessor as dp;
use \Exception;
use Illuminate\Support\Facades\View;

/**
 * I forgot the purpose of this class... Should replace default error handling or so.
 * Take a look at /app/Exceptions/Handler.php -> render(). Best way is to return a view.
 * 
 * @internal
 */
abstract class ExceptionHandler
{
	/** 
	 * Replaces Handler.php -> render()
     * @return \Illuminate\Http\Response
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