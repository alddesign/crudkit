<?php
/**
 * Class Restriction Set
 */
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;
use \Exception;

/**
 * Defines a set of restrictions or rights to access pages and specific actions on pages. The $type 'allow-all' or 'deny-all' defines its behavior. A RestrictionSet belongs to a User.
 */
class RestrictionSet
{	
	/** @internal */ const ALLOWED_TYPES = ['allow-all', 'deny-all'];
	/** @internal */ private $type = 'allow-all'; 
	/** @internal */ private $entries = [];
	
	/**
	 * Creates new set of restrictions (permissions).
	 * 
	 * @param string $type 'allow-all' = allow everything except the entries you define, 'deny-all' = deny everything except the entries you define
	 * @param RestrictionSetEntry[] $entries Array of entires
	 */
    public function __construct(string $type, array $entries = [])
    {
		if(!in_array($type, self::ALLOWED_TYPES, true))
		{
			throw new CException('Invalid restriction set type "%s".', $type);
		}

		foreach($entries as $entry)
		{
			if(gettype($entry) !== 'object' || get_class($entry) !== 'Alddesign\Crudkit\Classes\RestrictionSetEntry')
			{
				throw new CException('Array of "Alddesign\Crudkit\Classes\RestrictionSetEntry" objects expected.');
			}
		}
		
		$this->type = $type;
		$this->entries = $entries;
    }
	
	/**
	 * Checks if this permission set has access to a specific page/action
	 * @param string $action
	 * @param string $pageId 
	 * @internal 
	 */
	public function hasAccessTo(string $action, string $pageId)
	{
		$entryFound = false;
		
		foreach($this->entries as $entry)
		{
			if( ($action === $entry->action || $entry->action === '' || $action === '') && ($pageId === $entry->pageId || $entry->pageId  === '') )
			{
				$entryFound = true;
			}
		}

		return($this->type === 'allow-all' ? !$entryFound : $entryFound);
	}
}