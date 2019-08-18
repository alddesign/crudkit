<?php
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;
use \Exception;


class RestrictionSet
{
	private $type = 'allow-all'; //'allow-all' = allow all, deny with entries | 'deny-all' = deny all, allow with entries
	private $entries = []; //Format = [0 => ['action' => <'list,card,c,u,d' | ''>, 'page-id' => <'page-id' | ''>], 1 => ...]
	static $allowedActions = ['list', 'card', 'create', 'update', 'delete', 'export', 'chart', '']; 
	static $allowedTypes =['allow-all', 'deny-all'];
	
    public function __construct(string $type, array $entries = [])
    {
		if(!in_array($type, self::allowedTypes, true))
		{
			throw new Exception(sprintf('Restriction Set constructor: invalid type "%s".', $type));
		}
		
		foreach($entries as $entry)
		{
			if(!isset($entry['action']) || !isset($entry['page-id']) || !in_array($entry['action'], self::$allowedActions ,true))
			{
				$locAction = isset($entry['action']) ? $entry['action'] : '';
				$locPageId = isset($entry['page-id']) ? $entry['page-id'] : '';
				throw new Exception(sprintf('Restriction Set constructor: invalid entry. Action "%s", Page ID "%s".', $locAction, $locPageId));
			}
		}
		
		$this->type = $type;
		$this->entries = $entries;
    }
	
	public function hasAccessTo(string $action, string $pageId)
	{
		$entryFound = false;
		
		foreach($this->entries as $entry)
		{
			if( ($action === $entry['action'] || $entry['action'] === '' || $action === '') && ($pageId === $entry['page-id'] || $entry['page-id'] === '') )
			{
				$entryFound = true;
			}
		}

		return($this->type === 'allow-all' ? !$entryFound : $entryFound);
	}
}