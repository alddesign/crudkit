<?php
/**
 * Class SQLManyToOneColumn
 */
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * Allows to define Many to One Columns.
 * 
 * Example: Book <> Author. Field "author_id" in table "books" is the Many to One Column. 
 * @internal
 */
class SQLManyToOneColumn extends SQLColumn
{
    /** @var string */ private $foreignTableId = null;
    /** @var string */ private $foreignColumnName = null;
	/** @var FilterDefinition[] */ private $filterDefinitions = [];
	/** @var bool */ public $clickable = true;
	
	/**
	 * Creates a new Many to One Column
	 * 
	 * @param string $name
	 * @param string $label
	 * @param string $type See Class SQLColumn for types
	 * @param string $foreignTableId Curdkit table id
	 * @param string $foreignColumnName
	 * @param FilterDefinition[] $filterDefinitions (optional) Array of filter definitions to describe the relation.
	 * @param array $options (optional) See Class SQLColumn for options
	 * @param bool $clickable (optional) display as clickabel link
	 * @see SQLColumn
	 */
    public function __construct(string $name, string $label, string $type, string $foreignTableId, string $foreignColumnName, array $filterDefinitions = [], array $options = [], bool $clickable = true)
    {
		if(in_array($type, ['textlong', 'image', 'blob'], true))
		{
			dp::crudkitException('Column of type "%s" cannot be defined Many to One Column. (Foreign key)', __CLASS__, __FUNCTION__, $type);
		}
		
        parent::__construct($name, $label, $type, $options);
		
		$this->foreignTableId = $foreignTableId;
		$this->foreignColumnName = $foreignColumnName;
		$this->clickable = $clickable;
		$this->filterDefinitions = $filterDefinitions;
		
		$this->relationType = 'manytoone';
    }
	
	/**
	 * Gets array of values.
	 * 
	 * Will be shown as <select> on create/update pages.
	 * 
	 * @param array $record Record data
	 * @return array
	 */
    public function getManyToOneValues(array $record = [])
    {
		$query = DB::table($this->foreignTableId)->select($this->foreignColumnName);
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
	
	/**
	 * Gets the url to the card page of the related record.
	 * 
	 * @param PageStore $pageStore
	 * @param array $record Record data
	 * @return string
	 */
	public function getCardUrl(array $record, PageStore $pageStore)
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
				$urlParameters['pk-0'] = $record[$this->name];
				foreach($this->filterDefinitions as $index => $filterDefinition)
				{
					$filter = $filterDefinition->toFilter($record);
					$filter->appendToUrlParams($urlParameters, $index);
				}
				return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@cardView', $urlParameters));
			}
		}

		dp::crudkitException('Table "%s" not found.', __CLASS__, __FUNCTION__, $this->foreignTableId);
	}
}

