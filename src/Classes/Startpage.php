<?php 
/** @ignore */
namespace Alddesign\Crudkit\Classes;

/** @ignore */
use Alddesign\Crudkit\Classes\DataProcessor as dp;
use \Exception;
use Response;

/** @ignore */
class Startpage
{	
	private $pageId = null;
	private $type = null;
	private $parameters = [];
	
	/** @ignore */
    public function __construct(string $pageId, string $type, array $parameters = [])
    {
		if(dp::e($pageId))
		{
			dp::ex('Startpage - __construct: no page ID provided.');
		}
		
		if(!in_array($type, ['list', 'card', 'create', 'update'], true))
		{
			dp::ex('Startpage - __construct: invalid type "%s"', $type);
		}
		
		$this->pageId = $pageId;
		$this->type = $type;
		$this->parameters = $parameters;
    }
	
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
			
		return;
	}
}