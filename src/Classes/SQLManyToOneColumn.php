<?php
/**
 * Class SQLManyToOneColumn
 */
declare(strict_types=1);
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
	/** @var string */ 
	public $toTableName = '';
	/** @var string */ 
	public $columnName = '';
	/** @var string[] */ 
	public $secondaryColumnNames = [];
	/** @var FilterDefinition[] */ 
	public $filterDefinitions = [];
	/** @var string */ 
	public $pageId = '';
	/** @var bool */
	public $manualInput = false;
	/** @var bool */
	public $ajax = false;

	/**
	 * Creates a new Many to One Column
	 * 
	 * @param string $name
	 * @param string $label
	 * @param string $type See Class SQLColumn for types
	 * @param string $toTableName Curdkit table name
	 * @param string $columnName Primary relations column
	 * @param string[] $secondaryColumnNames Secondary columns which will be shown to the user (more = slower)
	 * @param string $pageId The drilldown page id
	 * @param FilterDefinition[] $filterDefinitions (optional) Array of filter definitions to describe the relation.
	 * @param bool $manualInput Allows the user to input custom data manually (without checking)
	 * @param array $options (optional) See Class SQLColumn for options
	 * @param bool $ajax Ajax powered value selection
	 * @param array $ajaxOptions Options for the ajax
	 * @see SQLColumn
	 */
    public function __construct(string $name, string $label, string $type, string $toTableName, string $columnName, array $secondaryColumnNames = [], string $pageId = '', array $filterDefinitions = [], array $options = [], bool $manualInput = false, bool $ajax = false, AjaxOptions $ajaxOptions = null)
    {		
		parent::__construct($name, $label, $type, $options);
		
		if(in_array($this->type, ['enum', 'image', 'blob', 'boolean'], true))
		{
			dp::crudkitException('Column of type "%s" cannot be defined Many to One Column. (Foreign key)', __CLASS__, __FUNCTION__, $this->type);
		}
		
		$this->isManyToOne = true;
		$this->toTableName = $toTableName;
		$this->columnName = $columnName;
		$this->secondaryColumnNames = $secondaryColumnNames;
		$this->filterDefinitions = $filterDefinitions;
		$this->manualInput = $manualInput;
		$this->pageId = $pageId;
		$this->ajax = $ajax;

		if($this->ajax)
		{
			$this->setAjaxOptions($ajaxOptions);
		}
	}
	
	/**
	 * Gets array of values.
	 * 
	 * Will be shown as <select> on create/update pages.
	 * 
	 * @param array $record Record data
	 * @return array
	 */
    public function getManyToOneValues(array $record = [], bool $onlyCurrentValue = false)
    {
		/** @var \Illuminate\Database\Query\Builder $query */
		$query = DB::table($this->toTableName);
		$query->select($this->getColumnsForSelect(false));
		if($this->ajax || $onlyCurrentValue)
		{
			$query->where($this->columnName, '=', $record[$this->name]);
		}
		foreach($this->filterDefinitions as $filterDefinition)
		{
			$filter = $filterDefinition->toFilter($record);
			$query->where($filter->field, $filter->operator, $filter->value);
		}
		$rows = $query->get();

		$results = [];
		foreach($rows as $row)
		{
			$row = (array)$row;

			$result = ['',''];
			$result[0] = $row[$this->columnName];
			foreach($this->secondaryColumnNames as $index => $name)
			{
				$result[1] .= $index === 0 ? '' : ' ';
				$result[1] .= $row[$name];
			}

			$results[] = $result;	
		}

		return $results;
    }
	
	/**
	 * Gets the url to the card page of the related record.
	 * 
	 * @param PageStore $pageStore
	 * @param array $record Record data
	 * @return string
	 */
	public function getCardUrl(array $record)
	{	
		if(dp::e($this->pageId))
		{
			return '';
		}

		$urlParameters = [];
		$urlParameters['page-id'] = $this->pageId;
		$urlParameters['pk-0'] = $record[$this->name];
		foreach($this->filterDefinitions as $index => $filterDefinition)
		{
			$filter = $filterDefinition->toFilter($record);
			$filter->appendToUrlParams($urlParameters, $index);
		}
		return(URL::action('\Alddesign\Crudkit\Controllers\CrudkitController@cardView', $urlParameters));

		dp::crudkitException('Table "%s" not found.', __CLASS__, __FUNCTION__, $this->foreignTableId);
	}

	/** @return string[] */
	public function getColumnsForSelect(bool $includeAjaxColumns)
	{
		$columns = [];
		dp::appendToArray($this->columnName, $columns, false);
		foreach($this->secondaryColumnNames as $name)
		{
			dp::appendToArray($name, $columns, false);
		}
		if($includeAjaxColumns && $this->ajax && $this->getAjaxOptions() !== null)
		{
			dp::appendToArray($this->getAjaxOptions()->imageFieldname, $columns, false);
		}

		return $columns;
	}

	/** @return void */
	public function setAjaxOptions(AjaxOptions $ajaxOptions = null)
	{
		if($ajaxOptions === null)
		{
			$this->ajaxOptions = new AjaxOptions('', [$this->columnName], 1, 0);
		}
		else
		{
			$this->ajaxOptions = $ajaxOptions;
		}
	}
}

