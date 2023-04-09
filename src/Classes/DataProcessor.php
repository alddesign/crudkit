<?php 
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\CHelper;
use \DateTime;
use \Exception;
	 
/**
 * Entity for formatting and processing data coming eiter from, or going into the Database.
 * 
 * Mostly needed by CRUDKit internally.
 */
class DataProcessor
{
	/** @ignore */ private $table = null;
	/** @ignore @var array<float>  */ private static $startTimes = [];
	
	/** @ignore*/ private array $dbconf = [];
	/** @ignore*/ private array $formatsUi = []; 


	/**
	 * An instance is needed when dealing with data (pre/postprocessing).
	 * 
	 * @param TableDescriptor $table Table for later pre/postprocessing
	 * @internal
	 */
	public function __construct(TableDescriptor $table)
	{
		$this->table = $table;
		
		$this->dbconf = CHelper::getCrudkitDbConfig();
		$this->formatsUi = config('crudkit.formats_ui');
	}

	#region PRE PROCESSING ##############################################################################################################################################
	/**
	 * Processing data from request before INSERT, UPDATE it to the database.
	 * 
	 * Considering the 5 important things:  
	 * * Request data for the field exists
	 * * Request data for the field has value or is NULL
	 * * Field has a DEFAULT value in the DB
	 * * Field is set to NOT NULL in the DB
	 * * Field is set to AUTOINCREMENT in the DB
	 *
	 * @param array $requestData
	 * @param string $isInsert TRUE for insert, FALSE for update
	 * @return array $recordData
	 * @internal
	 */
	public function preProcess($requestData, bool $isInsert)
	{
		$isUpdate = !$isInsert;

		//The resulting record
		$recordData = [];
				
		/** @var mixed The field data */
		$data = null;
		/** @var string The datatype of the field based on crudkit, or doctrine/dbal (there might be fields which are not defined in crudkit, but are still in the DB)  */
		$datatype = '';
		/** @var bool Per definition: data exist, if the field is present in request, even if NULL */
		$dataExists = false;
		/** @var bool Per definition: data === null. This doesnt indicate if its NULL in the request or set to NULL here, because is doesnt exist in the request. */
		$dataIsNull = false;
		/** @var bool Field is defined as NOT NULL in the database */
		$dbNotNull = false;
		/** @var bool Field has a DEFAULT value in database */
		$dbHasDefault = false;
		/** @var bool Field is defined as AUTOINCREMTN in the database */
		$dbAi = false;
		/** @var bool For blob fields: was the '___DELETEBLOB' checkbox for this field checked by the user.*/
		$deleteBlobIsSet = false;
		/** @var bool Specifies if we ulitmately want to include this field in the SQL INSERT statement. */
		$includeInInsertStatement = false;
		/** @var bool Specifies if we ulitmately want to include this field in the SQL UPDATE statement. */
		$includeInUpdateStatement = false;

		$this->table->fetchAllColumns(); //intensive workload
		$columns = $this->table->getColumns();
		$allColumns = $this->table->getAllColumns();
		foreach($allColumns as $columnName => $column)
		{	
			//Prepare the necessarry infos for the matrix
			$data = array_key_exists($columnName, $requestData) ? $requestData[$columnName] : null;

			$datatype = isset($columns[$columnName]) ? $columns[$columnName]->type : $column['datatype'];
			$datatype = SQLColumn::mapDatatype($datatype, $columnName);
			$dataExists = array_key_exists($columnName, $requestData);
			$dataIsNull = $data === null;
			$dbNotNull = $column['notnull'] == true;
			$dbHasDefault = $column['default'] !== null; //The default value is always provided as string from Doctrine DBAL
			$dbAi = $column['autoincrement'] == true; //auto increment
			$deleteBlobIsSet = isset($requestData[$columnName.'___DELETEBLOB']) && $requestData[$columnName.'___DELETEBLOB'] === 'on';

			$includeInInsertStatement = false;
			$includeInUpdateStatement = false;

			//If the ___DELETEBLOB field is set, we set the data to NULL and specify it its present in th request.
			//This way we ensure it will be deleted. It might happen, that someone clicks delete, and selects a file to upload(!)
			if($deleteBlobIsSet)
			{
				$dataExists = true;
				$data = null;
			}
			
			//Lets go
			$this->matrix($data, $datatype, $dataExists, $dataIsNull, $dbNotNull, $dbHasDefault, $dbAi, $includeInInsertStatement, $includeInUpdateStatement);

			//Finally add the data to the final record:
			if(($isInsert && $includeInInsertStatement) || ($isUpdate && $includeInUpdateStatement))
			{
				$recordData[$columnName] = $data;
			}
		}

		return($recordData);
	}

	/**
	 * Ah yes, the "Matrix". The core logic of preprocessing data, based on the paramters.  
	 * It decides if a field has its data, is null, empty of if its even included in the final SQL statement. 
	 * 
	 * The syntax of this code may not be the most efficent but its easier to oversee all possibilities.
	 * 
	 * @param mixed $data The field data
	 * @param string $datatype The datatype of the field based on crudkit, or doctrine/dbal (there might be fields which are not defined in crudkit, but are still in the DB)
	 * @param bool $d Per definition: data exist, if the field is present in request, even if NULL
	 * @param bool $n Per definition: data === null. This doesnt indicate if its NULL in the request or set to NULL here, because is doesnt exist in the request.
	 * @param bool $nn Field is defined as NOT NULL in the database
	 * @param bool $df Field has a DEFAULT value in database
	 * @param bool $ai Field is defined as AUTOINCREMTN in the database
	 * @param bool $i Specifies if we ulitmately want to include this field in the SQL INSERT statement.
	 * @param bool $u Specifies if we ulitmately want to include this field in the SQL UPDATE statement.
	 * 
	 * @return void
	 * @internal
	 */
	private function matrix(&$data, string $datatype, bool $d, bool $n, bool $nn, bool $df, bool $ai, bool &$i, bool &$u)
	{
			//Some things to consider:
			//DB DEFAULT is only applied by the db on INSERT, if we exclude that field
			//DB AUTOINCREMENT is only applied by the db on INSERT, if we exclude that field (in some DBs also if null or so i think...)

			$i = false;
			$u = false;

			//When data exists in request. (we dont care about Ai, when data exists, your fault when you try do set data to an ai column)
			if($d 	&& $n 	&& $nn 	&& $df	&& true) {$i=false;	$u=true; $this->setEmpty($data, $datatype);	return;	}
			if($d 	&& $n 	&& $nn 	&& !$df	&& true) {$i=true; 	$u=true; $this->setEmpty($data, $datatype); return;	}
			if($d 	&& $n 	&& !$nn && $df	&& true) {$i=false; $u=true; $this->setNull($data, $datatype); 	return;	}
			if($d 	&& $n 	&& !$nn && !$df	&& true) {$i=true; 	$u=true; $this->setNull($data, $datatype); 	return; }
			if($d 	&& !$n 	&& true	&& true	&& true) {$i=true; 	$u=true; $this->setData($data, $datatype);	return; }
			//When NO data exists in request, and columun is not Ai
			if(!$d 	&& true && $nn 	&& $df	&& !$ai) {$i=false; $u=false; /*noop*/ 							return;	}
			if(!$d 	&& true && $nn 	&& !$df	&& !$ai) {$i=true; 	$u=false; $this->setEmpty($data, $datatype);return;	}
			if(!$d 	&& true && !$nn && $df	&& !$ai) {$i=false; $u=false; /*noop*/ 							return;	}
			if(!$d 	&& true && !$nn && !$df	&& !$ai) {$i=true; 	$u=false; $this->setNull($data, $datatype); return;	}
			//When NO data exists in request. When column is Ai, we definitly dont want it in insert or update
			if(!$d 	&& true && true	&& true	&&	$ai) {$i=false; $u=false; /*noop*/ 							return;	}

			//In case i miss something
			throw new Exception('Undefined preprocessing matrix state.');
	}
	
	/** 
	 * Setting the empty or the DB specific value empty for some datatypes
	 * 
	 * @internal
	 */
	private function setEmpty(&$data, string $datatype)
	{
		switch($datatype)
		{
			case 'text' 	: $data = ''; break;
			case 'integer' 	: $data = 0; break;
			case 'decimal' 	: $data = 0.0; break;
			case 'enum' 	: $data = ''; break;
			case 'datetime' : $data = $this->dbconf['empty_values']['datetime']; break;
			case 'date' 	: $data = $this->dbconf['empty_values']['date']; break;
			case 'time' 	: $data = $this->dbconf['empty_values']['time']; break;
			case 'boolean' 	: $data = false; break;
			case 'blob' 	: $data = ''; break;
			case 'image' 	: $data = ''; break;
			default 		: throw new CException('Invalid datatype "%s".', $datatype) ;
		}
	}
	
	/** @internal */
	private function setNull(&$data)
	{
		$data = null;
	}
	
	/** @internal */
	private function setData(&$data, string $datatype)
	{
		switch($datatype)
		{
			case 'text' 	: $data = $data; break;
			case 'integer' 	: $data = intval($data); break;
			case 'decimal' 	: $data = floatval($data); break;
			case 'enum' 	: $data = $data; break;
			case 'datetime' : $data = DateTime::createFromFormat($this->formatsUi['datetime'], $data)->format('Y-m-d H:i:s'); break;
			case 'date' 	: $data = DateTime::createFromFormat($this->formatsUi['date'], $data)->format('Y-m-d'); break;
			case 'time' 	: $data = DateTime::createFromFormat($this->formatsUi['time'], $data)->format('H:i:s'); break;
			case 'boolean' 	: $data = ($data == true); break;
			case 'blob' 	: $data = $this->getUploadFileData($data); break;
			case 'image' 	: $data = $this->getUploadFileData($data); break;
			default 		: throw new CException('Invalid datatype "%s".', $datatype) ;
		}		
	}
	#endregion

	#region POST PROCESSING #############################################################################################################################################
	/**
	 * Formats the data in record(s) so that they can be displayed to the user or consumed by views.
	 * 
	 * @param mixed $records Raw data from the DB. Obtained by readRecordsRaw() or readRecordRaw() 
	 * @param bool $singleRecord TRUE if the records is a single record, FALSE if its an array of multiple records.
	 * @param bool $formaDateAndTime
	 * @param bool $formatBool
	 * @param bool $formatDec
	 * @param bool $formatBinary
	 * 
	 * @return array $records
	 */
	public function postProcess($records, bool $singleRecord = false, bool $formaDateAndTime = true, $formatBool = true, $formatDec = true, $formatBinary = true)
	{
		$records = $singleRecord ? [$records] : $records;//Can now be used in foreach

		$yes = CHelper::text('yes');
		$no = CHelper::text('no');
		$columns = $this->table->getColumns();
		foreach($records as &$record)
		{
			foreach($record as $columnName => &$columnValue)
			{
				$datatype = isset($columns[$columnName]) ? $columns[$columnName]->type : '';

				//Processing
				if($formatBinary && ($datatype === 'blob' || $datatype === 'image'))		
				{ 
					$this->processBinaryData($columnValue, $singleRecord, $datatype); 
				}
				//$this->mapEnum($columnName, $columnValue); ???
				if($formaDateAndTime && in_array($datatype, ['datetime', 'date', 'time'], true))	
				{ 
					$this->formatDateAndTime($columnValue, $datatype); 
				}
				if($formatDec && $datatype === 'decimal' && !CHelper::e($columnValue))			
				{ 
					$columnValue = number_format(floatval($columnValue), $this->formatsUi['decimal_places'], $this->formatsUi['decimal_separator'], $this->formatsUi['thousands_separator']); 
				}
				if($formatBool && $datatype === 'boolean')			
				{ 
					$columnValue = in_array($columnValue, [1, '1', true], true) ? 1 : 0; 
				}
			}
			unset($columnValue);	
		}
		unset($record);	

		return $singleRecord ? $records[0] : $records;
	}
	
	/** @internal */
	private function processBinaryData(&$columnValue, $singleRecord, string $datatype)
	{
		if($singleRecord)
		{
			if($datatype === 'image') //Single record and Image --> we got the blob data from DB, we will show the image.
			{
				$columnValue = CHelper::e($columnValue) ? $columnValue : 'data:image;base64,'.base64_encode($columnValue);
			}
			if($datatype === 'blob') //here we just got the size in kB from the DB
			{
				$columnValue = $this->formatBytes($columnValue);
			}
		}
		else
		{
			$columnValue = $this->formatBytes($columnValue);
		}
	}

	/** @internal */	
	private function formatBytes($bytes, int $precision = 2) 
	{ 
		$bytes = CHelper::e($bytes) ? 0 : $bytes;
		
		$units = array('B', 'KB', 'MB', 'GB', 'TB'); 

		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);
		// $bytes /= (1 << (10 * $pow)); 

		return (round($bytes, $precision) . ' ' . $units[$pow]); 
	} 

	/** @internal */
	private function formatDateAndTime(&$columnValue, string $datatype)
	{		
		$formatsDb = $this->dbconf['formats'];
		$empty = $this->dbconf['empty_values'];
		
		if(!CHelper::e($columnValue))
		{		
			//PHP SQL Zero Date Problem (0000-00-00...): 
			switch($datatype)
			{
				case 'datetime' : $columnValue = ($columnValue === $empty['datetime'])	? '' : DateTime::createFromFormat($formatsDb['datetime'], $columnValue)->format($this->formatsUi['datetime']); break;
				case 'date' 	: $columnValue = ($columnValue === $empty['date']) 		? '' : DateTime::createFromFormat($formatsDb['date'], $columnValue)->format($this->formatsUi['date']); break;
				case 'time' 	: $columnValue = ($columnValue === $empty['time'])		? '' : DateTime::createFromFormat($formatsDb['time'], $columnValue)->format($this->formatsUi['time']); break;
				default			: return;
			}
		}
	}
	
	/** @internal */
	/**
	 * Gets the binary data from an uploaded file.
	 * 
	 * @param \Illuminate\Http\UploadedFile $uploadedFile
	 * 
	 * @return mixed
	 * @internal
	 */
	private function getUploadFileData($uploadedFile) // Parameter is \Illuminate\Http\UploadedFile Object or NULL
	{
		if(CHelper::e($uploadedFile))
		{
			return null;
		}
		
		$d2 = $uploadedFile->openFile()->fread($uploadedFile->getSize());

		$binaryData = null;
				
		$handle = fopen($uploadedFile->getPathname(), 'rb');
		$binaryData = fread($handle, $uploadedFile->getSize());
		fclose($handle);
		
		return($binaryData);
	}
	#endregion
	
	#region HELPER FUNCTIONS ##########################################################################################################################################

}

