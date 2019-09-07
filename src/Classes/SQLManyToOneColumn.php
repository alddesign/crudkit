<?php

namespace Alddesign\Crudkit\Classes;

use \Exception;
use Alddesign\Crudkit\Classes\DataProcessor as dp;
use DB;
use URL;

/**
* Allows to define Many to One Columns.
* Example: Book <> Author. Field "author_id" in table "books" is the Many to One Column. 
*/
class SQLManyToOneColumn extends SQLColumn
{
    private $foreignTableName = null;
    private $foreignColumnName = null;
	private $filterDefinitions = [];
	private $clickable = null;
	
	/**
	* Creates a new One to Many Column.
	* @param FilterDefinition[] $filterDefinitions.
	*/
    public function __construct(string $name, string $label, string $type, string $foreignTableName, string $foreignColumnName, array $filterDefinitions = [], array $options = [], bool $clickable = true)
    {
		if(in_array($type, ['textlong', 'image', 'blob'], true))
		{
			throw new Exception(sprintf('SQLManyToOneColumn - construct: column of type "%s" cannot be defined as foreign key.'));
		}
		
        parent::__construct($name, $label, $type, $options);
		
		$this->foreignTableName = $foreignTableName;
		$this->foreignColumnName = $foreignColumnName;
		$this->clickable = $clickable;
		$this->filterDefinitions = $filterDefinitions;
		
		$this->relationType = 'manytoone';
    }
	
    public function getManyToOneValues(array $record = [])
    {
		$query = DB::table($this->foreignTableName)->select($this->foreignColumnName);
		foreach($this->filterDefinitions as $filterDefinition)
		{
			$filter = $filterDefinition->toFilter($record);
			$query->where($filter->field, $filter->operator, $filter->value);
		}
		$rows = $query->get();

		$result = [];
		foreach($rows as $row)
		{
			$row = (array)$row;
			$result[] = $row[$this->foreignColumnName];	
		}

		return $result;
    }
	
	public function getCardUrl(array $record, $pageStore)
	{		
		$pageDescriptors = $pageStore->getPageDescriptors();
		foreach($pageDescriptors as $pageDescriptor)
		{
			//Found a Page!
			if($pageDescriptor->getTable()->getName() === $this->foreignTableName)
			{
				$c = 0;
				$urlParameters = [];
				$urlParameters['page-id'] = $pageDescriptor->getId();
				$urlParameters['pk-0'] = $record[$this->name];
				foreach($this->filterDefinitions as $index => $filterDefinition)
				{
					$filter = $filterDefinition->toFilter($record);
					$filter->appendToUrlParams($urlParameters, $index);
				}
				return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@cardView', $urlParameters));
			}
		}
		
		throw new Exception(sprintf('Keine Page mit Tabelle "%s" vorhanden.', $this->foreignTableName));
	}
	
	// ### GETTERS ###
	public function getClickable()
	{
		return $this->relationClickable;
	}
	
	//Override Base Class Method
	public function getRelationType()
	{
		return 'manytoone';
	}
}

