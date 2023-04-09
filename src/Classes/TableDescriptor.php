<?php
/**
 * Class TableDescriptor
 */
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

use \Exception;
use Alddesign\Crudkit\Classes\DataProcessor;
use Alddesign\Crudkit\Classes\SQLManyToOneColumn;

/**
 * Definition of a database table.
 * 
 * Examples for usage are provided in the documentation for CrudkitServiceProvider.
 * Important: all methods marked with "@stackable" (like the constructor and all set methods) can be used like this
 * ```php
 * $page = new TableDesriptor(...)
 * 		->addColumn(...)
 * 		->setSoftDeleteColumn(...)
 * 		->...
 * ``` 
 * ...easy
 * @see \Alddesign\Crudkit\CrudkitServiceProvider
 */
class TableDescriptor
{
    /** @ignore */ private $name = null;
	/** @ignore */ private $primaryKeyColumns = [];
	/** @ignore */ private $hasAutoincrementKey = false;
    /** @var SQLColumn[] */ private $columns = [];
	
	/** @ignore */ private $allColumns = []; //Array of all table columns. Needed for inserts. Fetched by Doctrine DBAL.
	/** @ignore */ private $allColumnsFetched 	= false;
    
	/** @ignore */ private $softDeleteColumn = null;
	/** @ignore */ private $softDeletedValue = true;
	/** @ignore */ private $softNotDeletedValue = false;
	
	/** @ignore */ private $dbconf = null;

	/** @ignore */ private $orderBy = '';
	/** @ignore */ private $orderByDirection = 'asc';

	/** @ignore */ private bool $doctrineDbalCache = true;
	/** @ignore */ private int $doctrineDbalCacheTtl = 3600 * 24;
	
	/**
	 * Constructor
	 * 
	 * @param string $name Name of the table (as defined in the database)
	 * @param string[] $primaryKeyColumns String array of column names, which define the primary key of this table, or at least identify a single record. Remember: if no column is a PK, every column is a PK!
	 * @param bool $hasAutoincrementKey (default = false) Set this to TRUE if you have a column like and integer ID, which will be generated (MySQL Auto increment, MSSQL identity)
	 */
    public function __construct(string $name, array $primaryKeyColumns, bool $hasAutoincrementKey = false)
    {
        $this->name = $name;
		$this->hasAutoincrementKey = $hasAutoincrementKey;
		
		$this->primaryKeyColumns = $primaryKeyColumns;
		$this->dbconf = CHelper::getCrudkitDbConfig();

		$this->doctrineDbalCache = boolval(config('crudkit.doctrine_dbal_cache', true));
		$this->doctrineDbalCacheTtl = intval(config('crudkit.doctrine_dbal_cache_ttl', 3600 * 24));
    }

	#region GET
	/** @return string Name of the table (as defined in the database) */
	public function getName()
    {
        return $this->name;
    }
	
	/** @return bool */
	public function getHasAutoincrementKey()
	{
		return $this->hasAutoincrementKey;
	}
	
	/** @return string Name of the soft delte column (soft delete is a pseudo delete) */
    public function getSoftDeleteColumn()
    {
        return $this->softDeleteColumn;
    }
	
	/** @return mixed Value of the soft delete Column that indicates that the record IS deleted */
	public function getSoftDeletedValue()
	{
		return $this->softDeletedValue;
	}
	
	/** @return mixed Value of the soft delete Column that indicates that the record is NOT deleted. */
	public function getSoftNotDeletedValue()
	{
		return $this->softNotDeletedValue;
	}

	/**
	 * Checks if a column with a specific name exists in this table. 
	 * 
	 * @param string $columnName The name of the column to check
	 * @return bool
	 */
    public function hasColumn(string $columnName)
    {
        return array_key_exists($columnName, $this->columns);
    }
	 
	/**
	 * Returns an array of this tables columns, including extended information from Doctrine DBAL.
	 * 
	 * Make sure to call fetchAllColumns() at least once before calling this method.
	 * 
	 * @param bool $namesOnly (default = FALSE) TRUE = retruns string[] array with the column names, FALSE = return and array of SQLColumn[] objects
	 * @return string[]|array
	 */
	public function getAllColumns(bool $namesOnly = false)
	{
		if(!$this->allColumnsFetched)
		{ 
			throw new CException('Please call fetchAllColumns() first. (Note: performance impact)');
		}
	
		if($namesOnly)
		{
			return array_keys($this->allColumns);
		}
		else
		{
			return $this->allColumns;	
		}
	}
	 
	/**
	 * Returns an array of this tables columns
	 * 
	 * @param bool $namesOnly (default = FALSE) TRUE = retruns string[] array with the column names, FALSE = return and array of SQLColumn[] objects
	 * @return string[]|SQLColumn[]
	 */
    public function getColumns(bool $namesOnly = false)
    {
		if($namesOnly)
		{
			return array_combine(array_keys($this->columns), array_keys($this->columns)); //This is easier to access than numeric indexes
		}
		else
		{
			return $this->columns;
		}
    }
	
	/**
	 * Returns an array of this tables primary key columns
	 * 
	 * @param bool $namesOnly (default = FALSE) TRUE = retruns string[] array with the column names, FALSE = return and array of SQLColumn[] objects
	 * @return string[]|SQLColumn[]
	 */
	public function getPrimaryKeyColumns(bool $namesOnly = false)
    {
		if($namesOnly)
		{
			return $this->primaryKeyColumns;
		}
		
		$columns = [];
		foreach($this->primaryKeyColumns as $primaryKeyColumnName)
		{
			$columns[$primaryKeyColumnName] = $this->columns[$primaryKeyColumnName];
		}
		return $columns;
    }
	
	/**
	 * Returns values for each many-to-one column of this table (values from related tables)
	 * 
 	 * ```php
	 * ['column-name-1' => ['value-1', 'value-2'], 'column-name-2' => [...]] 
	 * ```
	 * @return array
	 */
	public function getManyToOneColumnValues($record = [], bool $onlyCurrentValue = false)
	{
		$result = [];
		foreach($this->columns as $name => $column)
		{
			if($column->isManyToOne)
			{
				/** @var SQLManyToOneColumn */
				$manyToOneColumn = $column;
				$result[$name] = $manyToOneColumn->getManyToOneValues($record, $onlyCurrentValue);
			}
		}
		
		return $result;
	}
	
	/**
	 * This method fetches $allColumns via Doctrine\DBAL (get extended information about columns)
	 * 
	 * This is a compute intensive workload - so it should only be used by CRUDKit internally, and only when needed.
	 * The result from Doctrine\DBAL is also stored in the cache to increase performance.
	 * 
	 * @param bool $fore Force a re-fetch.
	 * @internal
	 */
	public function fetchAllColumns(bool $force = false)
	{		
		if(!$force && $this->allColumnsFetched)
		{
			return;
		}

		//try reading from cache
		if($this->allColumnsFromCache())
		{
			$this->allColumnsFetched = true;
			return;
		}

		EnumType::registerDoctrineEnumMapping();
		
		$this->allColumns = [];
		$columnNames = Schema::getColumnListing($this->name);
		foreach($columnNames as $key => $name)
		{
			$docColumn = DB::getDoctrineColumn($this->name, $name);
			
			$this->allColumns[$name] =
			[
				'datatype' => $docColumn->getType()->getName(),
				'default' => $docColumn->getDefault(), //default = string|null
				'notnull' => $docColumn->getNotNull(), //notnull = boolean
				'autoincrement' => $docColumn->getAutoincrement() //autoincrement = boolean
			];
		}

		//Write to cache
		$this->allColumnsToCache();
		
		$this->allColumnsFetched = true;
	}

	/**
	 * Tries fetch all columns from cache. Returns TRUE, if they were cached, FALSE if not.
	 * 
	 * @internal 
	 * @return bool 
	 */
	private function allColumnsFromCache()
	{
		if(!$this->doctrineDbalCache)
		{
			return false;
		}

		$this->allColumns = Cache::get('all-columns-' . $this->name, []);
	
		return $this->allColumns === [] ? false : true;
	}

	/** @internal */
	private function allColumnsToCache()
	{
		if(!$this->doctrineDbalCache)
		{
			return;
		}

		Cache::put('all-columns-' . $this->name, $this->allColumns, $this->doctrineDbalCacheTtl);
	}
	#endregion

	#region SET and ADD Methods
	/**
	 * Adds a sql column to this table.
	 * 
	 * You dont need to add every column that exists in the table, only those you need.
	 * 
	 * @param string $name The name of the column like its defined in the Database
	 * @param string $label The label to display the column on UI.
	 * @param string $type The datatype - for details see SQLColumn Class doc
	 * @param array $options (optional) - for details see SQLColumn Class doc
	 * 
	 * @see SQLColumn
	 */
	public function addColumn(string $name, string $label, string $type, $options = [])
    {
		if(isset($this->columns[$name]) || $name === $this->softDeleteColumn)
		{
			throw new CException('Cannot add column "%s" to talbe "%s". Column already exists.', $name, $this->name);
		}
		
        $this->columns[$name] = new SQLColumn($name, $label, $type, $options);

        return $this;
	}
	
	public function setOrderBy(string $columnName, string $direction = 'asc')
	{
		if(!isset($this->columns[$columnName]))
		{
			throw new CException('Cannot set orderBy. Column "%s" doesnt exist in table "%s".', $columnName, $this->name);
		}	
		
		$this->orderBy = $columnName;
		$this->orderByDirection = $direction;

		return $this;
	}
	
	/**
	 * Example: book.author_id is a many to one column. Related table.field is author.id
	 * Example code: see CrudkitServiceProvider doc
	 * 
	 * @param string $name Name of the column to defines as many to one
	 * @param string $toTableName Related table name
	 * @param string $columnName A column from the related table
	 * @param array $secondaryColumnNames Secondary clolumn names from the related table. These are shown to the user when selecting the value (more = slower)
	 * @param string $page A drilldown page if you want to display this relation as a link
	 * @param array $filterDefinitions Additional filters to limit the relation
	 * @param bool $manualInput Allows the user to input custom data manually (without checking)
	 * 
	 * @return TableDescriptor
	 * @stackable
	 */
	public function defineManyToOneColumn(string $name, string $toTableName, string $columnName, array $secondaryColumnNames = [], string $page = '', array $filterDefinitions = [], bool $manualInput = false)
	{
		if(!isset($this->columns[$name]))
		{
			throw new CException('Cannot define column "%s" as many-to-one column. Column was not found in table "%s".', $name, $this->name);
		}
		
		$column = $this->columns[$name];
		$this->columns[$name] = new SQLManyToOneColumn($column->name, $column->label, $column->type, $toTableName, $columnName, $secondaryColumnNames,  $page, $filterDefinitions, $column->options, $manualInput);
		
		return $this;
	}

	/**
	 * See defineManyToOneColumn() - same, but rendered as an ajax selectbox (search for values).
	 * 
	 * @param string $name Name of the column to defines as many to one
	 * @param string $toTableName Related table name
	 * @param string $columnName A column from the related table
	 * @param array $secondaryColumnNames Secondary clolumn names from the related table. These are shown to the user when selecting the value (more = slower)
	 * @param string $page A drilldown page if you want to display this relation as a link
	 * @param array $filterDefinitions Additional filters to limit the relation
	 * @param bool $manualInput Allows the user to input custom data manually (without checking)
	 * @param AjaxOptions|null $ajaxOptions additional options to control the ajax behavior
	 * 
	 * @return TableDescriptor
	 * @stackable
	 */
	public function defineManyToOneColumnAjax(string $name, string $toTableName, string $columnName, array $secondaryColumnNames = [], string $page = '', array $filterDefinitions = [], bool $manualInput = false, AjaxOptions $ajaxOptions = null)
	{
		if(!isset($this->columns[$name]))
		{
			throw new CException('Cannot define column "%s" as many-to-one column. Column was not found in table "%s".', $name, $this->name);
		}
		$column = $this->columns[$name];

		if(in_array($column->type, ['enum', 'image', 'blob', 'boolean'], true))
		{
			throw new CException('Column of type "%s" cannot be defined as Custom Ajax Column.', $column->type);
		}

		
		$this->columns[$name] = new SQLManyToOneColumn($column->name, $column->label, $column->type, $toTableName, $columnName, $secondaryColumnNames, $page, $filterDefinitions, $column->options, $manualInput, true, $ajaxOptions);
		
		return $this;
	}

	/**
	 * Defines a column where you can handle what is displayed, and what the user can enter via ajax select search. Be carefull - you have to take care of the data!
	 * 
	 * ```php
	 * //Example for $result object if success (Json encoded):
	 * {type : "result", data : {results : [{'id' : '23', 'text' => 'Joe Smith', 'img' => 'base64imageData'}, {id : '...'}]}}
	 * //Example for $result object if error (Json encoded):
	 * {type : "error", message : "404 not found" data : "some error data which will be logged by the Javascript console..."}
	 * //Helper methods in CHelper class:
	 * CHelper::getAjaxErrorResult();
	 * CHelper::getAjaxResult();
	 * ```
	 * 
	 * @param string $name Name of the existing column. Cannot be a many to one column.
	 * @param callable $onLoadCallback Callback function for preparing the data which is displayed in the field. Params are: &$value, &$text, $record, $table, $page, $column. You can change &$value and &$text as you wish. $value already holds the fields current value (after select). $text is an additionl info text
	 * @param callable $onSearchCallback Callback function for when the user begins to type for searching data. Params are: &$results, $input, $table, $page, $column. You have to populate $results (see example above);
	 * @param AjaxOptions $ajaxOptions
	 * @return TableDescriptor
	 * @stackable
	 */
	public function defineCustomColumnAjax(string $name, callable $onLoadCallback, callable $onSearchCallback, AjaxOptions $ajaxOptions = null)
	{
		if(!isset($this->columns[$name]))
		{
			throw new CException('Cannot define column "%s" as custom column. Column was not found in table "%s".', $name, $this->name);
		}

		$column = $this->columns[$name];
		if($column->isManyToOne)
		{
			throw new CException('Cannot define column "%s" as custom column. Column is already a Many To One Column.', $name);
		}

		$column->isCustomAjax = true;
		$column->setAjaxOptions($ajaxOptions);
		$column->onLoadCallback = $onLoadCallback;
		$column->onSearchCallback = $onSearchCallback;

		return $this;
	}
	
	/**
	 * Defines a column as an indicator if the record is deleted or not.
	 * 
	 * Important: the soft delete column must not be added as normal column to the table, cause this will make things hard (espcially for users)
	 * When a soft delete column is defined, and a user deletes a record on the webpage, the record wont be deleted.
	 * Only thing happen is that the value of the soft delete column will be changed, and crudkit wont display this record anymore. - Cool, isnt it?
	 * 
	 * @param string $name The name of the column
	 * @param mixed $deleted (default = TRUE) The value of this column that indicates the record IS delteted
	 * @param mixed $deleted (default = FALSE) The value of this column that indicates the record is NOT delteted
	 */
    public function setSoftDeleteColumn(string $name, $deleted = true, $notDeleted = false)
    {
		if(isset($this->columns[$name]))
		{
			throw new CException('Cannot set "%s" as soft delete column. It is already a normal column of table "%s".', $name, $this->name);
		}

        $this->softDeleteColumn = $name;
		$this->softDeletedValue = $deleted;
		$this->softNotDeletedValue = $notDeleted;
        
		return $this;
    }

	/**
	 * Sets the position of a column (display order)
	 * 
	 * ```php
	 * $table->setColumnPosition('name', 'after', 'id'); //easy
	 * ```
	 * 
	 * @param $columnName Defines the column to be set to a specific position.
	 * @param $where Is either 'before' or 'after'
	 * @param $referenceColumnName Before or after this Column the $columnName will be placed
	*/
	public function setColumnPosition(string $columnName, string $where, string $referenceColumnName)
	{
		$where = mb_strtolower($where);
		$where = in_array($where, ['before', 'after'], true) ? $where : 'before';

		if(!isset($this->columns[$columnName]))
		{
			throw new CException('Column (to move) "%s" was not found in table "%s".', $columnName, $this->name);
		}
		if(!isset($this->columns[$referenceColumnName]))
		{
			throw new CException('Reference column "%s" was not found in table "%s".', $referenceColumnName, $this->name);
		}
	
		$helper = ($this->columns[$columnName]);
		unset($this->columns[$columnName]);
		
		$this->array_insert($columnName, $helper, $where, $referenceColumnName, $this->columns);
		
		return $this;
	}
	#endregion

	#region CRUD
	/**
	 * Reads a record from the DB.
	 * 
	 * @param string[] $primaryKeyValues
	 * @param Filter[] $filters
	 * @return array Raw unprocessed record as an assoc array: ['column1' => value, 'column2' => value, ...]
	 */
	public function readRecordRaw(array $primaryKeyValues, array $filters = [], $throwErrorIfNotFound = true)
	{
		//Test Parameters
		if(CHelper::e($primaryKeyValues) && CHelper::e($filters))
		{
			throw new CException('Please specify primary key values and/or filters. Table "%s".', $this->name);
		}
		
		$record = [];
		$query = DB::table($this->name); 
		foreach($this->columns as $column)
		{
			//Select only the size of 'blob' fields. We cant show binary data...
			if($column->type === 'blob')
			{
				$columnNameWithDelimiter = $this->getDbSpecificColumnNameWithDelimiter($column->name);
				$lengthFunction = $this->getDbSpecificBlobLengthFunction($columnNameWithDelimiter);

				//selectRaw() adds a select like addSelect() --> https://laravel.com/api/5.4/Illuminate/Database/Query/Builder.html#method_selectRaw
				$query->selectRaw(sprintf('%s as %s', $lengthFunction, $columnNameWithDelimiter)); //If we escape these parameters, LENGTH() counts the string length
			}
			else
			{
				$query->addSelect($column->name);
			}
		}
		
		if(!CHelper::e($primaryKeyValues)) //Use Primary Key Values
		{
			foreach($primaryKeyValues as $index => $value)
			{
				$query->where($this->primaryKeyColumns[$index], $value);
			}
		}
		if(!CHelper::e($filters)) //Use Filters --> the have to result in a single record (mostly they are primary keys :) )
		{
			foreach($filters as $filter)
			{
				$query->where($filter->field, $filter->operator, $filter->value);
			}
		}
		//$query = $query->limit(1);
		$record = $query->get();
		
		$recordCount = count($record);
		if($recordCount > 1 || ($recordCount === 0 && $throwErrorIfNotFound))
		{
			throw new CException('Read record query returned %d records (only one expected). Check primary key and filter parameters. Table "%s".', $recordCount, $this->name);
		}

		if($recordCount === 0)
		{
			return [];
		}

		//Since we have an Illuminate Collection of only one, item we can comvert it to a single assoc array
		$record = (array)$record[0];

		return $record;
	}

	public function getCustomAjaxValues($record, PageDescriptor $page)
	{
		//Custom Columns: Trigger Event onLoad
		$values = [];
		foreach($this->columns as $column)
		{
			if($column->isCustomAjax)
			{
				$value = $record[$column->name];
				$column->triggerOnLoadEvent($value, $record, $this, $page, $column);
				$values[$column->name] = $value;
			}
		}

		return $values;
	}
	
	/**
	 * Reads multiple records from the DB.
	 * 
	 * @param int $pageNumber Pagination offset
	 * @param string $searchColumnName The column name to apply the $searchText (if existing)
	 * @param string $searchText The search text (if existing)
	 * @param Filter[] $filters
	 * @param bool $trimText Trim text >50 chars
	 * @param bool $formatDateAndTime
	 * @param bool $formatBool
	 * @param bool $formatDec
	 * @param bool $formatBinary
	 * @param int $itemsPerPage
	 * 
	 * @return array Raw unprocessed records as an array: [0 => ['column1' => value, 'column2' => value, ...], 1 => [], 2 => [], ...]
	 */
	public function readRecordsRaw(int $pageNumber = 1, string $searchColumnName = '', string $searchText = '', array $filters = [], bool $trimText = true, int $itemsPerPage = -1)
	{
		//To load all records (within search and filter) set $pageNumber to 0
		$records = []; //Result
        $itemsPerPage = $itemsPerPage >= 0 ? $itemsPerPage : config('crudkit.records_per_page', 5);
		$searchText = mb_strtolower($searchText,'UTF-8');
		$searchColumn = !CHelper::e($searchColumnName) && isset($this->columns[$searchColumnName]) ? $this->columns[$searchColumnName] : '';

        //Build search term
        $searchTerm = '';
		$searchType = '';
        if(!CHelper::e($searchText) && !CHelper::e($searchColumn))
        {
			switch($searchColumn->type)
			{
				case 'blob' : 
				case 'image' :
					break;
				case 'boolean' 	:
					$searchType = 'boolean';
					$searchTerm = in_array($searchText, [CHelper::text('yes'), 'yes', 'true', '1'], true) ? true : false;
					break;
				case 'enum' :
					$searchType = 'exact';
					$searchTerm = $searchText;
					if(!is_numeric($searchText)) //Try to find the value because the search term might be a text
					{
						$searchTerm = array_search($searchText, array_map(function($e){return mb_strtolower($e,'UTF-8');}, $searchColumn->options['enum']) , true);
						$searchTerm = $searchTerm !== false ? (string)$searchTerm : $searchText;
					}
					break;
				default :
					$searchType = 'contains';
					$searchTerm = $this->getDbSpecificLikeCondition('contains',$searchText); 
			}
		}
		
		$trimLength = intval(config('crudkit.records_text_trim_length', 50));
        $query = DB::table($this->name);		
		foreach($this->columns as $column)
		{
			$columnNameWithDelimiter = $this->getDbSpecificColumnNameWithDelimiter($column->name);
			$lengthFunction = $this->getDbSpecificBlobLengthFunction($columnNameWithDelimiter);
			
			//Trim Text
			$type = ($trimText && ($column->type === 'text' || $column->type === 'textlong')) ? ($column->type . '_trim') : $column->type; 

			switch($type)
			{
				//Performance: Select only the size of 'blob' and 'image' fields. We dont want to load all the data in lists
				//Performance: Select only 50 characters of longer text fields 
				//selectRaw() adds a select like addSelect() --> https://laravel.com/api/5.4/Illuminate/Database/Query/Builder.html#method_selectRaw
				case 'image'		: $query->selectRaw(sprintf('%s as %s', $lengthFunction, $columnNameWithDelimiter)); break;
				case 'blob' 		: $query->selectRaw(sprintf('%s as %s', $lengthFunction, $columnNameWithDelimiter)); break;
				case 'text_trim'	: $query->selectRaw(sprintf('SUBSTR(%s, 0, %d) as %s', $columnNameWithDelimiter, $trimLength, $columnNameWithDelimiter)); break;
				case 'textlong_trim': $query->selectRaw(sprintf('SUBSTR(%s, 0, %d) as %s', $columnNameWithDelimiter, $trimLength, $columnNameWithDelimiter)); break;
				default 			: $query->addSelect($column->name);
			}
		}
		$query	->when( $this->softDeleteColumn !== null, 
					function ($query) 
					{return $query->where( $this->softDeleteColumn, $this->softNotDeletedValue); } 
				)
				->when( ($searchType === 'boolean' || $searchType === 'exact'), 
					function ($query) use ($searchColumn, $searchTerm) 
					{return $query->where( $searchColumn->name, $searchTerm );}				
				)
				->when( $searchType === 'contains', 
					function ($query) use ($searchColumn, $searchTerm) 
					{return $query->where( $searchColumn->name, 'like', $searchTerm );}
				)
				->when(!empty($filters), 
					function ($query) use ($filters) 
					{
						foreach($filters as $index => $filter)
						{
							switch($filter->operator)
							{
								case '=' 			:
								case '>' 			:
								case '<' 			:
								case '>=' 			:
								case '<=' 			:
								case '!=' 			:
									$query->where($filter->field, $filter->operator, $filter->value);
									break;
								case 'startswith'	:
									$query->where($filter->field, 'like', $this->getDbSpecificLikeCondition('startswith', $filter->value));
									break;
								case 'endswith' 	:
									$query->where($filter->field, 'like', $this->getDbSpecificLikeCondition('endswith', $filter->value));
									break;
								case 'contains' 	:
									$query->where($filter->field, 'like', $this->getDbSpecificLikeCondition('contains', $filter->value));
									break;
								default				: 
									throw new Exception(sprintf('Read records: invalid filter operator "%s".', $filter->operator));
							}
						}    
						return $query;
					}
				);
		if(!CHelper::e($this->orderBy))
		{
			$query->orderBy($this->orderBy, $this->orderByDirection);
		}
		
		if($pageNumber > 0)
		{
			$records['total'] = $query->count(); // Get totals record count
			$query->offset(($pageNumber-1)*$itemsPerPage)
				  ->limit($itemsPerPage);	
		}
		$recordsCollection = $query->get(); // Get records itself

		//Convert from Illuminate Collection of object to normal array
		$records['records'] = [];
		foreach($recordsCollection as $record)
		{
			$records['records'][] = (array)$record;
		}

		//We only need to ['total'] and ['records'] if this is a request with pagination
        return $pageNumber > 0 ? $records : $records['records'];
	}
	
	/**
	 * Creates an empty records from this table for the create-update View.
	 * 
	 * @param bool $setDbDefaultValues Set the default values which are definded in the Database
	 * 
	 * @return array
	 */
	public function getEmptyRecord(bool $setDbDefaultValues = true)
	{
		$record = [];
		$this->fetchAllColumns();
	

		foreach($this->columns as $column)
		{
			$record[$column->name] = $setDbDefaultValues  ? strval($this->allColumns[$column->name]['default']) : '';
		}

		return $record;
	}

	/** 
	 * Creates a record in the DB
	 * @param array $recordData (preprocessed)
	 * @return array The primary key values
	 * @internal
	 */
    public function createRecord($recordData)
    {
        $primaryKeyValues = [];

		if(!CHelper::e($this->softDeleteColumn))
		{
			$recordData[$this->softDeleteColumn] = $this->softNotDeletedValue;
		}
		
		if($this->hasAutoincrementKey)
		{
			$primaryKeyValues[] = DB::table($this->name)->insertGetId($recordData);
		}
		else
		{
			DB::table($this->name)->insert($recordData);
			foreach($this->primaryKeyColumns as $primaryKeyColumnName)
			{
				$primaryKeyValues[] = $recordData[$primaryKeyColumnName];
			}
		}
		
        return $primaryKeyValues;
    }
	
	/** 
	 * Updates a record in DB
	 * @param array $primaryKeyValues (preprocessed)
	 * @param array $columnValues (preprocessed)
	 * @return array The (maybe) new primary key values
	 * @internal
	 */
	public function updateRecord(array $primaryKeyValues, array $columnValues)
    {
		$query = DB::table($this->name);
		foreach($this->primaryKeyColumns as $index => $primaryKeyColumnName) //a where for each Primary Key Column
		{
			$query->where($primaryKeyColumnName, $primaryKeyValues[$index]);
			$primaryKeyValues[$index] = isset($columnValues[$primaryKeyColumnName]) ? $columnValues[$primaryKeyColumnName] : '';
		}
		$query->update($columnValues);
		
		return $primaryKeyValues;
    }
	
    public function deleteRecord(array $primaryKeyValues)
    {		
		$query = DB::table($this->name);
		foreach($this->primaryKeyColumns as $index => $primaryKeyColumn)
		{
			$query->where($primaryKeyColumn, $primaryKeyValues[$index]);
		}
		
        if(!CHelper::e($this->softDeleteColumn))
        {
			$query->update([$this->softDeleteColumn => $this->softDeletedValue]);
        }
        else 
		{
			$query->delete(); 
        }
		
		return true;
    }
	#endregion
	
	#region Helpers
	/**
	 * Shortcut for `(new DataProcessor($table))->postProcess(...);` 
	 * 
	 * @see \Alddesign\Crudkit\Classes\DataProcessor
	 * @return array
	 */
	public function postProcess($records, bool $singleRecord = false, bool $formaDateAndTime = true, $formatBool = true, $formatDec = true, $formatBinary = true)
	{
		$dataProcessor = new DataProcessor($this);

		return $dataProcessor->postProcess($records, $singleRecord, $formaDateAndTime, $formatBool, $formatDec, $formatBinary);
	}

	/**
	 * Gets the SQL column name sourrounded with the DB specififc field delimiter. (defined in crudkit-db.config)
	 * 
	 * @param string $columnName The name of the SQL column
	 * @return string
	 * @internal
	 */
	private function getDbSpecificColumnNameWithDelimiter(string $columnName)
	{
		$d = $this->dbconf['delimiters'];
		return sprintf("%s%s%s", $d[0], str_replace($d[1], $d[1].$d[1], $columnName), $d[1]); //enclose with delimiters, and mask delimiters inside.
	}

	/**
	 * Gets the DB specific functions for the size of a blob field (lentgh).
	 * 
	 * @param string $columnNameWithDelimiter The name of the SQL column (already sorrounded by delimiters)
	 * @return string
	 * @internal
	 */
	private function getDbSpecificBlobLengthFunction(string $columnNameWithDelimiter)
	{
		$dbtype = config('database.default','');
		switch($dbtype)
		{
			case 'sqlite' 	: return(sprintf('(LENGTH(HEX(%s))/2)', $columnNameWithDelimiter));
			default			: return(sprintf('LENGTH(%s)', $columnNameWithDelimiter)); 	
		}
	}

	/**
	 * Gets the DB specific like condition for a given expression (search term)
	 * 
	 * @param string $expression The "search term"
	 * @param string $type Either "contains"|"startswith"|"endswith"
	 * @return string
	 * @internal
	 */
	private function getDbSpecificLikeCondition(string $type, string $expression)
	{
		$dbtype = config('database.default','');
		$pre = false;
		$post = false;
		
		switch($type)
		{
			case 'startswith' 	: $pre = true; 	$post = false; break;
			case 'endswith' 	: $pre = false; $post = true; break;
			default				: $pre = true; 	$post = true; break; //contains 
		}
		
		switch($dbtype)
		{
			case 'mysql' 	: 
				$pre = $pre ? '%' : '';
				$post = $post ? '%' : '';
				return('%' . str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$expression) . '%'); //See mysql Pattern matching chapter for this. For mysql LIKE we need to escape "\", "%" and "_" with backslash.
			case 'sqlsrv'	: 
				$pre = $pre ? '%' : '';
				$post = $post ? '%' : '';
				return('%' . str_replace(['\\','%','_','[',']'],['\\\\','\\%','\\_','[\\','\\]'], $expression) . '% ESCAPE \'\\\''); //See https://docs.microsoft.com/en-us/sql/t-sql/language-elements/like-transact-sql
			default 		: 
				$pre = $pre ? '%' : '';
				$post = $post ? '%' : '';
				return('%' . str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$expression) . '%'); //idk...
		}
		
	}
	
	/** @ignore */
	private function array_insert(string $index, $data,  string $where, string $refIndex, array &$array)
	{
		$offset = $where === 'before' ? 0 : 1;

		$pos = array_search($refIndex, $array, true) + $offset;
		$array = array_merge
		(
			array_slice($array, 0, $pos),
			[$index => $data],
			array_slice($array, $pos)
		);
	}
	#endregion
}
