<?php
namespace Alddesign\Crudkit\Classes;

use DB;
use Exception;
use Schema;
use Response;

use \DateTime;
use Alddesign\Crudkit\Classes\DataProcessor as dp;

class TableDescriptor
{
    private $name = null;
	private $primaryKeyColumns = [];
	private $hasAutoincrementKey = false;
    private $columns = [];
	
	private $allColumns = []; //Array of all table columns. Needed for inserts. Fetched by Doctrine DBAL.
	private $allColumnsFetched 	= false;
    
	private $softDeleteColumn = null;
	private $softDeletedValue = true;
	private $softNotDeletedValue = false;
	
	private $dbconf = null;
	
	// ### INITIAL ###################################################################################################################################################################################
	//AD You must specify the primary key columns
    public function __construct(string $name, array $primaryKeyColumns, bool $hasAutoincrementKey = false)
    {
        $this->name = $name;
		$this->hasAutoincrementKey = $hasAutoincrementKey;
		
		$this->primaryKeyColumns = $primaryKeyColumns;
		$this->dbconf = dp::getCrudkitDbConfig();
    }

	// ### GET / SET ###################################################################################################################################################################################
    public function getName()
    {
        return $this->name;
    }
	
	public function getHasAutoincrementKey()
	{
		return $this->$hasAutoincrementKey;
	}
	
	public function setHasAutoincrementKey(bool $hasAutoincrementKey = false)
	{
		$this->$hasAutoincrementKey = $hasAutoincrementKey;
	}
	
    public function getSoftDeleteColumn()
    {
        return $this->softDeleteColumn;
    }
	
	public function getSoftDeletedValue()
	{
		return $this->softDeletedValue;
	}
	
	public function getSoftNotDeletedValue()
	{
		return $this->softNotDeletedValue;
	}

    public function hasColumn($columnName)
    {
        return array_key_exists($columnName, $this->columns);
    }
	 
	public function getAllColumns(bool $namesOnly = false)
	{
		if(!$this->allColumnsFetched)
		{ 
			throw new Exception(sprintf('Table Descriptor - get all columns: please call function fetchAllColumns() first. (Notice: fetchAllColumns() has a major performance impact.)'));
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
	 
    public function getColumns(bool $namesOnly = false)
    {
		if($namesOnly)
		{
			return array_keys($this->columns);
		}
		else
		{
			return $this->columns;
		}
    }
	
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
	
	public function getManyToOneColumnValues()
	{
		$result = [];
		foreach($this->columns as $name => $column)
		{
			if($column->getRelationType() == 'manytoone')
			{
				$result[$name] = $column->getManyToOneValues();
			}
		}
		
		return $result;
	}
	
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
	
	// ### METHODS ################################################################################################################################################################################
    public function addColumn(string $name, string $label, string $type, $options = [])
    {
		if($name === $this->softDeleteColumn)
		{
			dp::ex('TableDescriptor - addColumn: You cannot add colum "%s" to table "%s". It is set as soft delete column.', $name, $this->name);
		}
		
        $this->columns[$name] = new SQLColumn($name, $label, $type, $options);

        return $this;
    }
	
	public function defineManyToOneColumn(string $name, string $relationTableName, string $relationColumnName, array $filters = [], bool $clickable = true)
	{
		if(!isset($this->columns[$name]))
		{
			throw new Exception(sprintf('Table Descriptor - define many to one column: column "%s" was not found in table "%s".', $name, $this->name));
		}
		
		$column = $this->columns[$name];
		$this->columns[$name] = new SQLManyToOneColumn($column->name, $column->label, $column->type, $relationTableName, $relationColumnName, $filters, $column->options, $clickable);
		
		return $this;
	}
	
	public function defineOneToManyColumn(string $name, string $relationTableName, array $filters)
	{
		if(!isset($this->columns[$name]))
		{
			throw new Exception(sprintf('Table Descriptor - define one to many column: column "%s" was not found in table "%s".', $name, $this->name));
		}
		
		$column = $this->columns[$name];
		$this->columns[$name] = new SQLOneToManyColumn($column->name, $column->label, $column->type, $relationTableName, $filters, $column->options);
		
		return $this;
	}
	
    public function setSoftDeleteColumn(string $name, $softDeletedValue = true, $softNotDeletedValue = false)
    {
		if(isset($this->columns[$name]))
		{
			dp::ex('TableDescriptor - addColumn: You cannot set "%s" as soft delete column. It is already a normal column of table "%s".', $name, $this->name);
		}

        $this->softDeleteColumn = $name;
		$this->softDeletedValue = $softDeletedValue;
		$this->softNotDeletedValue = $softNotDeletedValue;
        
		return $this;
    }

	/**
	* Sets the position of a Column
	* @param $columnName Defines the column to be set to a specific position.
	* @param $where Is either 'before' or 'after'
	* @param $referenceColumnName Before or after this Column the $columnName will be placed
	*/
	public function setColumnPosition(string $columnName, string $where, string $referenceColumnName)
	{
		if(!isset($this->columns[$columnName]))
		{
			throw new Exception(sprintf('Set column position: column "%s" was not found in table "%s".', $columnName, $this->name));
		}
		if(!isset($this->columns[$referenceColumnName]))
		{
			throw new Exception(sprintf('Set column position: column "%s" was not found in table "%s".', $referenceColumnName, $this->name));
		}
	
		$helper = ($this->columns[$columnName]);
		unset($this->columns[$columnName]);
		
		$this::array_insert($columnName, $helper, $where, $referenceColumnName, $this->columns);
		
		return $this;
	}
	
	// ### CRUD OPERATIONS ###########################################################################################
	/**
	* @param Filter[] $filters
	*/
	public function readRecord(array $primaryKeyValues, array $filters = [])
	{
		//Test Parameters
		if((dp::e($primaryKeyValues) && dp::e($filters)) || (!dp::e($primaryKeyValues) && !dp::e($filters)))
		{
			throw new Exception('Read record: Please specify either primary key values or filters');
		}
		
		//Test Primary Keys
		if(!dp::e($primaryKeyValues))
		{
			$c1 = count($this->primaryKeyColumns);
			$c2 = count($primaryKeyValues); 
			if($c1 !== $c2)
			{
				throw new Exception(sprintf('Read record: The table "%s" has "%d" primary key fields. "%d" given.', $this->name, $c1, $c2));
			}
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
		else //Use Filters --> the have to result in a single record (mostly they are primary keys :) )
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
			throw new Exception(sprintf('Read record: Query return more than one record. %d records returned. Check primary key and filter parameters.', count($record)));
		}
		else
		{
			$record = (new DataProcessor($this))->postProcess($record[0], true);
			return((array)$record);
		}
	}
	
	/**
	* @param Filter[] filters
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
					$searchTerm = $this->getDbSpecificLikeCondition($searchText); 
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
									$query->where($filter->field, 'like', $this->getDbSpecificLikeCondition($filter->value, 'startswith'));
									break;
								case 'endswith' 	:
									$query->where($filter->field, 'like', $this->getDbSpecificLikeCondition($filter->value, 'endswith'));
									break;
								case 'contains' 	:
									$query->where($filter->field, 'like', $this->getDbSpecificLikeCondition($filter->value, 'contains'));
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
		//dp::xout($query->toSql());
		$records['records'] = $query->get();
		$records['records'] = (new DataProcessor($this))->postProcess($records['records'], false, $rawData);  
        return $records;
	}
	
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

	// ### HELPER METHODS #############################################################################################################################################	
	private function getDbSpecificColumnNameWithDelimiter(string $columnName)
	{
		$d = $this->dbconf['delimiters'];
		return sprintf("%s%s%s", $d[0], str_replace($d[1], $d[1].$d[1], $columnName), $d[1]); //enclose with delimiters, and mask delimiters inside.
	}

	private function getDbSpecificBlobLengthFunction(string $columnNameWithDelimiter)
	{
		$dbtype = config('database.default','');
		switch($dbtype)
		{
			case 'sqlite' 	: return(sprintf('(LENGTH(HEX(%s))/2)', $columnNameWithDelimiter));
			default			: return(sprintf('LENGTH(%s)', $columnNameWithDelimiter)); 	
		}
	}

	private function getDbSpecificLikeCondition(string $expression, string $type = '')
	{
		$dbtype = config('database.default','');
		$pre = false;
		$post = false;
		
		switch($type)
		{
			case 'startswith' 	: $pre = true; $post = false; break;
			case 'endswith' 	: $pre = false; $post = true; break;
			default				: $pre = true; $post = true; break;
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
}
