<?php
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;

class RestrictionSetEntry
{	
	private const ALLOWED_ACTIONS = ['list', 'card', 'create', 'update', 'delete', 'export', 'chart', '']; 

	public $action = '';
	public $pageId = '';

	public function __construct(string $action, string $pageId)
    {
		if(!in_array($action, self::$ALLOWED_ACTIONS ,true))
		{
			dp::crudkitException('Invalid action: "%s".', __CLASS__, __FUNCTION__, $action);
		}

		$this->action = $action;
		$this->pageId = $pageId;
    }
}