<?php

namespace Alddesign\Crudkit\Classes;

use \Exception;
use Alddesign\Crudkit\Classes\DataProcessor as DP;
use DB;
use URL;

/**
 * !!! EXPERIMENTAL !!!
 * 
 * Allows to define One to Many Columns
 * The scenarios requiring such columns are very rare.
 * Example: Table 'author' -> column 'number_of_books' (int) would be a one to many column, but this is bad practice.
 * @internal
 */
class SQLOneToManyColumn extends SQLColumn
{
    private $foreignTableId = null;
	private $filterDefinitions = [];

	/**
	 * Creates a new One to Many Column.
	 * @param FilterDefinition[] $filterDefinitions 
	 */
    public function __construct(string $name, string $label, string $type, string $foreignTableId, array $filterDefinitions, array $options = [])
    {
		if(in_array($type, ['textlong', 'image', 'blob'], true))
		{
			dp::crudkitException('Column of type "%s" cannot be defined One to Many Column.', __CLASS__, __FUNCTION__, $type);
		}
		
        parent::__construct($name, $label, $type, $options);
		
		$this->foreignTableId = $foreignTableId;
		$this->filterDefinitions = $filterDefinitions;
		
		$this->relationType = 'onetomany';
    }
	
	/**
	 * Gets the url to the list page of the related records.
	 * 
	 * @param array $record Record data
	 * @param PageStore $pageStore
	 * @return string
	 */
	public function getListUrl($record, $pageStore)
	{
		$pageDescriptors = $pageStore->getPageDescriptors();
		foreach($pageDescriptors as $pageDescriptor)
		{
			//Found a Page!
			if($pageDescriptor->getTable()->getName() === $this->foreignTableId)
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

		dp::crudkitException('Table "%s" not found.', __CLASS__, __FUNCTION__, $this->foreignTableId);	
	}
}

