<?php
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;

/**
 * Defines a single permission or restriction.
 * 
 * A RestrictionSetEntry belongs to a RestrictionSet. Based of $type from parent PermissionSet.
 * 
 * Example:
 * ```php
 * new RestrictionSetEntry('update', 'books'); //Either permit or restrict updating book records.
 * ``` 	
 */
class RestrictionSetEntry
{	
	/**
	 * @var string[] ALLOWED_ACTIONS
	 * @internal
	 */
	private const ALLOWED_ACTIONS = ['list', 'card', 'create', 'update', 'delete', 'export', 'chart', '']; 

	/** @internal */ public $action = '';
	/** @internal */ public $pageId = '';

	/**
	 * Constructor
	 * @param string $action Valid actions: 'list', 'card', 'create', 'update', 'delete', 'export', 'chart', ''. Empty = all
	 * @param string $pageId
	 */
	public function __construct(string $action, string $pageId)
    {
		if(!in_array($action, self::ALLOWED_ACTIONS ,true))
		{
			dp::crudkitException('Invalid action: "%s".', __CLASS__, __FUNCTION__, $action);
		}

		$this->action = $action;
		$this->pageId = $pageId;
    }
}