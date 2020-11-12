<?php 
/**
 * Class Startpage
 */
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;
use Illuminate\Support\Facades\Response;

/**
 * Defines a startpage for users.
 * 
 * Startpages can be assigned to users to override the default startpage.
 * 
 * @see AuthHelper
 * @see User
 */
class Startpage
{	

	/** @internal */ const ALLOWED_TYPES = ['list', 'card', 'create', 'update']; 

	/** @internal */ private $pageId = "";
	/** @internal */ private $type = "";
	/** @internal */ private $parameters = [];
	
	/**
	 * Constructor
	 * 
	 * @param string $pageId Page to display.
	 * @param string $type Which specific type of the page (list, card, create, update)
	 * @param array $parameters Parameters when calling this page. For example primary key values when calling a card page.
	 */
    public function __construct(string $pageId, string $type, array $parameters = [])
    {
		if(dp::e($pageId))
		{
			dp::crudkitException('No page id provided.', __CLASS__, __FUNCTION__);
		}
		
		if(!in_array($type, self::ALLOWED_TYPES, true))
		{
			dp::crudkitException('Invalid type "%s".', __CLASS__, __FUNCTION__, $type);
		}
		
		$this->pageId = $pageId;
		$this->type = $type;
		$this->parameters = $parameters;
    }
	
	/**
	 * Performs a redirect to the Startpage.
	 * 
	 * @internal
	 */
	public function redirectTo()
	{
		$controller = '\Alddesign\Crudkit\Controllers\CrudkitController@';
		
		$action = '';
		switch($this->type)
		{
			case 'list' : $action = 'listView'; break;
			case 'card' : $action = 'cardView'; break;
			case 'create' : $action = 'createView'; break;
			case 'update' : $action = 'updateView'; break;
			default : $action = 'list';
		}
		
		$params = array_merge(['page-id' => $this->pageId], $this->parameters);
		
		Response::redirectToAction($controller.$action, $params)->send();
	}
}