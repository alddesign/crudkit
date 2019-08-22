<?php
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;
use \Exception;

class RestrictionSet
{	
	const ALLOWED_TYPES = ['allow-all', 'deny-all'];

	private $type = 'allow-all'; //'allow-all' = allow all, deny with entries | 'deny-all' = deny all, allow with entries
	private $entries = [];
	
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
			dp::crudkitException('Invalid restriction set type "%s".', __CLASS__, __FUNCTION__, $type);
		}

		foreach($entries as $entry)
		{
			if(gettype($entry) !== 'object' || get_class($entry) !== 'RestrictionSetEntry')
			{
				dp::crudkitException('Array of RestrictionSetEntry objects expected.', __CLASS__, __FUNCTION__);
			}
		}
		
		$this->type = $type;
		$this->entries = $entries;
    }
	
	/** @internal */
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