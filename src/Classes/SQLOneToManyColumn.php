<?php

namespace Alddesign\Crudkit\Classes;

use \Exception;
use Alddesign\Crudkit\Classes\DataProcessor as DP;
use DB;
use URL;

/**
* Alllows to define One to Many Columns
* The scenarios requiring such a coumn are very rare.
* Example: Table 'author', Column 'number_of_books' would be a one to many column, but this is bad practice.
*/
class SQLOneToManyColumn extends SQLColumn
{
    private $foreignTableName = null;
	private $filterDefinitions = [];

	/**
	* Creates a new One to Many Column.
	* @param FilterDefinition filterDefinitions.
	*/
    public function __construct(string $name, string $label, string $type, string $foreignTableName, array $filterDefinitions, array $options = [])
    {
		if(in_array($type, ['textlong', 'image', 'blob'], true))
		{
			throw new Exception(sprintf('Datentyp "%s" kann nicht als Foreign Key definiert werden!'));
		}
		
        parent::__construct($name, $label, $type, $options);
		
		$this->foreignTableName = $foreignTableName;
		$this->filterDefinitions = $filterDefinitions;
		
		$this->relationType = 'onetomany';
    }
	
	public function getListUrl($record, $pageStore)
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
				if(!dp::e($this->filterDefinitions))
				{
					foreach($this->filterDefinitions as $index => $filterDefinition)
					{
						$filter = $filterDefinition->toFilter($record);
						$filter->appendToUrlParams($urlParameters, $index);
					}
				}
				return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@listView', $urlParameters));
			}
		}

		throw new Exception(sprintf('Keine Page mit Tabelle "%s" vorhanden.', $this->foreignTableName));	
	}
	
	//Override Base Class Method
	public function getRelationType()
	{
		return 'onetomany';
	}
}

