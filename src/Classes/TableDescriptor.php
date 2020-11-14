<?php
/**
 * Class TableDescriptor
 */
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use \Exception;
use \DateTime;
use Alddesign\Crudkit\Classes\DataProcessor as dp;
use Alddesign\Crudkit\Classes\SQLManyToOneColumn;
use Alddesign\Crudkit\Classes\SQLOneToManyColumn;

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
    /** @var array<SQLColumn> */ private $columns = [];
	
	/** @ignore */ private $allColumns = []; //Array of all table columns. Needed for inserts. Fetched by Doctrine DBAL.
	/** @ignore */ private $allColumnsFetched 	= false;
    
	/** @ignore */ private $softDeleteColumn = null;
	/** @ignore */ private $softDeletedValue = true;
	/** @ignore */ private $softNotDeletedValue = false;
	
	/** @ignore */ private $dbconf = null;
	
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
		$this->dbconf = dp::getCrudkitDbConfig();
    }

	/* #region GET */
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
			dp::crudkitException('Please call fetchAllColumns() first. (Note: performance impact)', __CLASS__, __FUNCTION__);
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
	public function getManyToOneColumnValues($record = [])
	{
		$result = [];
		foreach($this->columns as $name => $column)
		{
			if($column->relationType === 'manytoone')
			{
				/** @var SQLManyToOneColumn */
				$manyToOneColumn = $column;
				$result[$name] = $manyToOneColumn->getManyToOneValues($record);
			}
		}
		
		return $result;
	}
	
	/**
	 * This method fetches $allColumns via Doctrine\DBAL (get extended information about columns)
	 * 
	 * This is a compute intensive workload - so it should only be used by CRUDKit internally, and only when needed.
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
		
		$this->allColumns = [];
		
		\Alddesign\Crudkit\Classes\EnumType::registerDoctrineEnumMapping();
		
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
		
		$this->allColumnsFetched = true;
	}
	/* #endregion */

	/* #region SET and ADD Methods */
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
			dp::crudkitException('Cannot add column "%s" to talbe "%s". Column already exists.', __CLASS__, __FUNCTION__, $name, $this->name);
		}
		
        $this->columns[$name] = new SQLColumn($name, $label, $type, $options);

        return $this;
    }
	
	/**
	 * Defines an existing column as a many-to-on relation to another Table.
	 * 
	 * Example: book.author_id is a many to one column. Related table.field is author.id
	 * Example code: see CrudkitServiceProvider doc
	 * 
	 * @param string $name Name of the column
	 * @param string $relationTableName The name of the related table
	 * @param string $relationColumnName The name of the column in the related table
	 * @param FilterDefinition[] $filterDefinitions (optional) Array of FilterDefinitions that is beeing applied to the related table (relation only to a subset of records)
	 * @param bool $clickable (default = true) Show this column as clickable link (to a card page)
	 * 
	 * @see \Alddesign\Crudkit\CrudkitServiceProvider
	 */
	public function defineManyToOneColumn(string $name, string $relationTableName, string $relationColumnName, array $filterDefinitions = [], bool $clickable = true)
	{
		if(!isset($this->columns[$name]))
		{
			dp::crudkitException('Cannot define column "%s" as many-to-one column. Column was not found in table "%s".', __CLASS__, __FUNCTION__, $name, $this->name);
		}
		
		$column = $this->columns[$name];
		$this->columns[$name] = new SQLManyToOneColumn($column->name, $column->label, $column->type, $relationTableName, $relationColumnName, $filterDefinitions, $column->options, $clickable);
		
		return $this;
	}
	
	/**
	 * !!! Experimental !!!
	 * 
	 * @param string $name Name of the column
	 * @param string $relationTableName The name of the related table
	 * @param FilterDefinition[] $filterDefinitions Array of FilterDefinitions that is beeing applied to the related table.
	 * @internal
	 */
	public function defineOneToManyColumn(string $name, string $relationTableName, array $filterDefinitions)
	{
		if(!isset($this->columns[$name]))
		{
			dp::crudkitException('Cannot define column "%s" as one-to-many column. Column was not found in table "%s".', __CLASS__, __FUNCTION__, $name, $this->name);
		}
		
		$column = $this->columns[$name];
		$this->columns[$name] = new SQLOneToManyColumn($column->name, $column->label, $column->type, $relationTableName, $filterDefinitions, $column->options);
		
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
			dp::crudkitException('Cannot set "%s" as soft delete column. It is already a normal column of table "%s".', __CLASS__, __FUNCTION__, $name, $this->name);
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
			dp::crudkitException('Column (to move) "%s" was not found in table "%s".', __CLASS__, __FUNCTION__, $columnName, $this->name);
		}
		if(!isset($this->columns[$referenceColumnName]))
		{
			dp::crudkitException('Reference column "%s" was not found in table "%s".', __CLASS__, __FUNCTION__, $referenceColumnName, $this->name);
		}
	
		$helper = ($this->columns[$columnName]);
		unset($this->columns[$columnName]);
		
		$this->array_insert($columnName, $helper, $where, $referenceColumnName, $this->columns);
		
		return $this;
	}
	/* #endregion */

	/* #region CRUD  */
	/**
	 * Reads a record from the DB
	 * @param string[] $primaryKeyValues
	 * @param Filter[] $filters
	 * @return array Record data as array
	 */
	public function readRecord(array $primaryKeyValues, array $filters = [])
	{
		//Test Parameters
		if(dp::e($primaryKeyValues) && dp::e($filters))
		{
			dp::crudkitException('Please specify primary key values and/or filters. Table "%s".', __CLASS__, __FUNCTION__, $this->name);
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
		
		if(!dp::e($primaryKeyValues)) //Use Primary Key Values
		{
			foreach($primaryKeyValues as $index => $value)
			{
				$query->where($this->primaryKeyColumns[$index], $value);
			}
		}
		if(!dp::e($filters)) //Use Filters --> the have to result in a single record (mostly they are primary keys :) )
		{
			foreach($filters as $filter)
			{
				$query->where($filter->field, $filter->operator, $filter->value);
			}
		}
		//$query = $query->limit(1);
		$record = $query->get();
		
		if(count($record) !== 1)
		{
			dp::crudkitException('Read record query returned %d records (only one expected). Check primary key and filter parameters. Table "%s".', __CLASS__, __FUNCTION__, count($record), $this->name);
		}
		else
		{
			$record = (new DataProcessor($this))->postProcess($record[0], true);
			return $record;
		}
	}
	
	/**
	 * Reads multiple records from the DB
	 * @param int $pageNumber Pagination offset
	 * @param string $searchColumnName The column name to apply the $searchText (if existing)
	 * @param string $searchText The search text (if existing)
	 * @param Filter[] $filters
	 * @param bool $trimText Trim text >50 chars
	 * @param bool $rawData Get the data unprocessed from the DB (watch out here!)
	 */
	public function readRecords(int $pageNumber = 1, string $searchColumnName = '', string $searchText = '', array $filters = [], bool $trimText = true, bool $rawData = false)
	{
		//To load all records (within search and filter) set $pageNumber to 0
		$records = []; //Result
        $itemsPerPage = config('crudkit.records_per_page', 5);
        $searchText = mb_strtolower($searchText,'UTF-8');
		$searchColumn = !dp::e($searchColumnName) && isset($this->columns[$searchColumnName]) ? $this->columns[$searchColumnName] : '';

        //Build search term
        $hasBooleanSearchTerm = false;
        $hasWordSearchTerm = false;
        $searchTerm = '';
		$searchType = '';
        if(!dp::e($searchText) && !dp::e($searchColumn))
        {
			switch($searchColumn->type)
			{
				case 'blob' : 
				case 'image' :
					break;
				case 'boolean' 	:
					$searchType = 'boolean';
					$searchTerm = in_array($searchText, [dp::text('yes'), 'yes', 'true', '1'], true) ? true : false;
					break;
				case 'enum' :
					$searchType = 'exact';
					$searchTerm = $searchText;
				default :
					$searchType = 'contains';
					$searchTerm = $this->getDbSpecificLikeCondition('contains',$searchText); 
			}
        }
		
        $query = DB::table( $this->name );		
		foreach($this->columns as $column)
		{
			$columnNameWithDelimiter = $this->getDbSpecificColumnNameWithDelimiter($column->name);
			$lengthFunction = $this->getDbSpecificBlobLengthFunction($columnNameWithDelimiter);
			
			//Trim Text
			$trimLength = 50;
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
			
		$records['total'] = $query->count();
			
		if($pageNumber > 0)
		{
			$query->offset(($pageNumber-1)*$itemsPerPage)
				  ->limit($itemsPerPage);	
		}
		$records['records'] = $query->get();
		$records['records'] = (new DataProcessor($this))->postProcess($records['records'], false, $rawData);  
        return $records;
	}
	
	/**
	 * @param bool $setDbDefaultValues Set the default values which are definded in the Database
	 * @param bool $allColumns Get all columns or just those definded in CRUDKit
	 * 
	 * @return array
	 */
	public function getEmptyRecord($setDbDefaultValues = true, $allColumns = false)
	{
		$record = [];
		if($allColumns === true || $setDbDefaultValues === true)
		{	
			$this->fetchAllColumns();
		}

		foreach($this->allColumns as $columnName => $options)
		{
			if($allColumns === true || $this->hasColumn($columnName))
			{
				$record[$columnName] = $setDbDefaultValues === true ? $options['default'] : null;
			}
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

		if(!dp::e($this->softDeleteColumn))
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
			$primaryKeyValues[$index] = $columnValues[$primaryKeyColumnName];
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
		
        if(dp::e($this->softDeleteColumn))
        {
			$query->update([$this->softDeleteColumn => $this->softDeletedValue]);
        }
        else 
		{
			$query->delete(); 
        }
		
		return true;
    }
	/* #endregion */
	
	/* #region Helpers */
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
	/* #endregion */
}
