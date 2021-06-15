<?php 
/**
 * Class DataProcessor
 */
namespace Alddesign\Crudkit\Classes;

use \Illuminate\Http\Request;
use \DateTime;
use \Exception;
	 
/**
 * Holds a set of usefull methods.
 * 
 * In most cases the static methods are all you need. An instance of this Object is only needed by CRUDKit itself.
 * Thats a little bit messy but its ok for now.
 */
class DataProcessor
{
	/** @ignore */ private $table = null;
	/** @ignore */ private $columns = null;
	/** @ignore */ private $columnNames = null;
	/** @ignore */ private $allColumns = null;
	/** @ignore */ private $primaryKeyColumns = null;
	/** @ignore */ private $primaryKeyColumnNames = null;
	/** @ignore */ private $language = '';
	/** @ignore @var array<float>  */ private static $startTimes = [];
	
	/** @ignore*/ private $dbconf = null; 

	/**
	 * An instance is needed when dealing with data (pre/postprocessing).
	 * 
	 * @param TableDescriptor $table Table for later pre/postprocessing
	 * @internal
	 */
	public function __construct(TableDescriptor $table)
	{
		$this->table = $table;
		
		//We need these table properties very often, so we preload them...
		$this->columns = $table->getColumns();
		$this->columnNames = $table->getColumns(true);
		$this->primaryKeyColumns = $table->getPrimaryKeyColumns();
		$this->primaryKeyColumnNames = $table->getPrimaryKeyColumns(true); 
		
		$this->dbconf = self::getCrudkitDbConfig();
	}

	#region PRE PROCESSING ##############################################################################################################################################
	/**
	* Processing data from request before INSERT, UPDATE it to the database
	* 
	* Considering the four important things:
	* 	-Request data for field exists
	*	-Request data for field has value or is NULL
	*	-Field has SQL default value in Databse
	*	-Field is set as NOT NULL in Database
	*
	* @param array $requestData
	* @param string $actionType 'update'|'create'  
	* @return array $recordData
	* @internal
	*/
	public function preProcess($requestData, bool $insert)
	{
		$this->table->fetchAllColumns(); //intensive workload
		$this->allColumns = $this->table->getAllColumns();

		$recordData = [];
				
		$dataExists = false; //[boolean] Per definition --> data exist, if data is in request, even if NULL
		$dataIsNull = false; //[boolean] Per definition --> data === null, no information about if it exists in request
		$dbDefault = false; //[boolean] Field has a default value in database
		$dbNotNull = false; //[boolean] Field has NOT NUL set in database 

		foreach($this->allColumns as $columnName => $options)
		{	
			$data = array_key_exists($columnName, $requestData) ? $requestData[$columnName] : null;
			
			$isPrimaryKey = in_array($columnName, $this->table->getPrimaryKeyColumns(true), true);

			$dataExists = array_key_exists($columnName, $requestData);
			$dataIsNull = $data === null;
			$dbHasDefault = $options['default'] !== null; //The default value is always provided as string from Doctrine DBAL
			$dbNotNull = $options['notnull'] === true;
			$deleteBlob = isset($requestData[$columnName.'___DELETEBLOB']) && $requestData[$columnName.'___DELETEBLOB'] === 'on';
			$i = false; //Include this column in the INSERT statement
			$u = false; //Include this column in the UPDATE statement

			//$datatype = $options['datatype'];
			$datatype = isset($this->columns[$columnName]) ? $this->columns[$columnName]->type : $options['datatype'];
			
			if($dataExists 	&& $dataIsNull 	&& $dbNotNull 	&& $dbHasDefault	) {$i=false; $u=true; $this->setEmpty($data, $datatype, $columnName);	}
			if($dataExists 	&& $dataIsNull 	&& $dbNotNull 	&& !$dbHasDefault	) {$i=true; $u=true; $this->setEmpty($data, $datatype, $columnName);	}
			if($dataExists 	&& $dataIsNull 	&& !$dbNotNull 	&& $dbHasDefault	) {$i=false; $u=true; $this->setNull($data, $datatype, $columnName);	}
			if($dataExists 	&& $dataIsNull 	&& !$dbNotNull 	&& !$dbHasDefault	) {$i=true; $u=true; $this->setNull($data, $datatype, $columnName); 	}
			if($dataExists 	&& !$dataIsNull && true			&& true				) {$i=true; $u=true; $this->setData($data, $datatype, $columnName); 	}
			
			if(!$dataExists && true 		&& $dbNotNull 	&& $dbHasDefault	) {$i=false; $u=false; $this->setNull($data, $datatype, $columnName);	}
			if(!$dataExists && true 		&& $dbNotNull 	&& !$dbHasDefault	) {$i=true; $u=false; $this->setEmpty($data, $datatype, $columnName);	}
			if(!$dataExists && true 		&& !$dbNotNull 	&& $dbHasDefault	) {$i=false; $u=false; $this->setNull($data, $datatype, $columnName);	}
			if(!$dataExists && true 		&& !$dbNotNull 	&& !$dbHasDefault	) {$i=true; $u=false; $this->setNull($data, $datatype, $columnName);	}			

			$this->addData($recordData, $columnName, $data, $deleteBlob, $insert, $i, $u);
		}

		return($recordData);
	}
	
	/**
	 * In the Update From there is the special "delete" checkbox for blob/image fields.
	 *
	 * If the user doesnt check this checkbox, doesnt provide a file, and there is already data in the DB, we have to ensure not to delete the existing data.
	 * This will bypass the above maxtrix with all the logic of null,empty,value... 
	 * 
	 * @internal 
	 */
	private function addData(array &$recordData, $columnName, $data, bool $deleteBlob, bool $insert, bool $i, bool $u)
	{	
		if(!$insert)
		{
			//Special treatment for blob/binary when updating
			if($deleteBlob)
			{
				$recordData[$columnName] = $data; //$data should be null or empty here (logic before found this out). Should do the job, except someone checks the "delete" checkbox, and adds a file. --> Then the file will be stored,...
				return;
			}
			if($u)
			{
				$recordData[$columnName] = $data; //Just Set the value
			}
		}	
		
		if($insert && $i)
		{
			$recordData[$columnName] = $data; //Just Set the value
		}	
	}
	
	/** 
	 * Setting the empty or lets say database specific value for each datatype
	 * 
	 * @internal
	 */
	private function setEmpty(&$fieldValue, string $fieldDatatype, string $columnName = '')
	{
		switch($fieldDatatype)
		{
			case 'string' 	: $fieldValue = ''; break;
			case 'text' 	: $fieldValue = ''; break;
			case 'integer' 	: $fieldValue = 0; break;
			case 'decimal' 	: $fieldValue = 0.0; break;
			case 'boolean' 	: $fieldValue = false; break;
			case 'datetime' : $fieldValue = $this->dbconf['empty_values']['datetime']; break;
			case 'date' 	: $fieldValue = $this->dbconf['empty_values']['date']; break;
			case 'time' 	: $fieldValue = $this->dbconf['empty_values']['time']; break;
			case 'blob' 	: $fieldValue = ''; break;
			case 'binary' 	: $fieldValue = ''; break;
			case 'enum' 	: $fieldValue = ''; break;
			default 		: throw new Exception(sprintf('Unsupported SQL datatype "%s". (column "%s", table "%s")', $fieldDatatype, $columnName, $this->table->getName()));
		}
	}
	
	/** @internal */
	private function setNull(&$fieldValue, string $fieldDatatype, string $columnName = '')
	{
		$fieldValue = null;
	}
	
	/** @internal */
	private function setData(&$fieldValue, string $fieldDatatype, string $columnName = '')
	{
		$formatsUi = config('crudkit.formats_ui');
		
		switch($fieldDatatype)
		{
			case 'string' 	: $fieldValue = $fieldValue; break;
			case 'text' 	: $fieldValue = $fieldValue; break;
			case 'integer' 	: $fieldValue = intval($fieldValue); break;
			case 'decimal' 	: $fieldValue = floatval($fieldValue); break;
			case 'boolean' 	: $fieldValue = ($fieldValue == true); break;
			case 'datetime' : $fieldValue = DateTime::createFromFormat($formatsUi['datetime'], $fieldValue)->format('Y-m-d H:i:s'); break;
			case 'date' 	: $fieldValue = DateTime::createFromFormat($formatsUi['date'], $fieldValue)->format('Y-m-d'); break;
			case 'time' 	: $fieldValue = DateTime::createFromFormat($formatsUi['time'], $fieldValue)->format('H:i:s'); break;
			case 'blob' 	: $fieldValue = $this->getUploadFileData($fieldValue); break;
			case 'binary' 	: $fieldValue = $this->getUploadFileData($fieldValue); break;
			case 'image' 	: $fieldValue = $this->getUploadFileData($fieldValue); break;
			case 'enum' 	: $fieldValue = $fieldValue; break;
			default 		: throw new Exception(sprintf('Unsupported SQL datatype "%s". (column "%s", table "%s")', $fieldDatatype, $columnName, $this->table->getName()));
		}		
	}
	#endregion

	#region POST PROCESSING #############################################################################################################################################
	/**
	* Processing data after SELECTing it from the database, before sending it to the view.
	* 
	* @param $records
	* @param bool $singleRecord
	* @return array $records
	* @internal
	*/
	public function postProcess($records, bool $singleRecord = false, bool $formatDateAndTime = true, $formatBool = true, $formatDec = true, $formatBinary = true)
	{		
		$records = $this->formatRecords($records, $singleRecord, $formatDateAndTime, $formatBool, $formatDec, $formatBinary);
		
		//Converting Object to Array,... dont ask me about this...
		$helper = [];
		$records = $singleRecord ? [$records] : $records;//Can now be used in foreach
		foreach($records as $record)
		{
			$helper[] = ((array) $record);
		}
		$records = $helper;

		return $singleRecord ? $records[0] : $records;
	}

	public function formatRecords($records, bool $singleRecord = false, bool $formaDateAndTime = true, $formatBool = true, $formatDec = true, $formatBinary = true)
	{
		$records = $singleRecord ? [$records] : $records;//Can now be used in foreach

		foreach($records as &$record)
		{
			foreach($record as $columnName => &$columnValue)
			{
				//Processing
				if($formatBinary){ $this->processBinaryData($columnName, $columnValue, $singleRecord); }
				//$this->mapEnum($columnName, $columnValue);
				if($formaDateAndTime){ $this->formatDateAndTime($columnName, $columnValue); }
				if($formatDec){ $this->formatDecimal($columnName, $columnValue); }
				if($formatBool){ $this->formatBoolean($columnName, $columnValue); }
			}
			unset($columnValue);	
		}
		unset($record);	

		return $singleRecord ? $records[0] : $records;
	}

	/** @internal */
	private function formatBoolean($columnName, &$columnValue)
	{
		$datatype = isset($this->columns[$columnName]) ? $this->columns[$columnName]->type : '';

		if($datatype === 'boolean')
		{
			$columnValue = ($columnName === 1 || $columnValue === '1' || $columnValue === true) ? [true, self::text('yes')] : [false, self::text('no')];
		}
	}

	/** @internal */
	private function formatDecimal($columnName, &$columnValue)
	{
		$datatype = isset($this->columns[$columnName]) ? $this->columns[$columnName]->type : '';

		if($datatype === 'decimal' && !self::e($columnValue))
		{
			$columnValue = number_format(floatval($columnValue), 2, ',', '.');
		}
	}
	
	/** @internal */
	private function processBinaryData($columnName, &$columnValue, $singleRecord)
	{
		$datatype = isset($this->columns[$columnName]) ? $this->columns[$columnName]->type : '';
		
		if($singleRecord)
		{
			if($datatype === 'image') //Single record and Image --> we got the blob data from DB, we will show the image.
			{
				$columnValue = self::e($columnValue) ? $columnValue : 'data:image;base64,'.base64_encode($columnValue);
			}
			if($datatype === 'blob') //here we just got the size in kB from the DB
			{
				$columnValue = $this->formatBytes($columnValue);
			}
		}
		else
		{
			if($this->columns[$columnName]->type === 'blob' || $this->columns[$columnName]->type === 'image') //here we just got the size in kB from the DB
			{
				$columnValue = $this->formatBytes($columnValue);
			}
		}
	}

	/** @internal */	
	private function formatBytes($bytes, int $precision = 2) 
	{ 
		$bytes = self::e($bytes) ? 0 : $bytes;
		
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
	private function formatDateAndTime($columnName, &$columnValue)
	{		
		$formatsUi = config('crudkit.formats_ui');
		$formatsDb = $this->dbconf['formats'];
		$empty = $this->dbconf['empty_values'];
		
		if(!self::e($columnValue))
		{		
			//PHP SQL Zero Date Problem (0000-00-00...): 
			switch($this->columns[$columnName]->type)
			{
				case 'datetime' : $columnValue = ($columnValue === $empty['datetime'])	? '' : DateTime::createFromFormat($formatsDb['datetime'], $columnValue)->format($formatsUi['datetime']); break;
				case 'date' 	: $columnValue = ($columnValue === $empty['date']) 		? '' : DateTime::createFromFormat($formatsDb['date'], $columnValue)->format($formatsUi['date']); break;
				case 'time' 	: $columnValue = ($columnValue === $empty['time'])		? '' : DateTime::createFromFormat($formatsDb['time'], $columnValue)->format($formatsUi['time']); break;
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
		if(self::e($uploadedFile))
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
	/**
	 * Gets a single text constant for the specified language form the config (crudkit-texts.php)
	 * 
	 * @param string $name The name of the test constant.
	 * @param string $language The language code. If not set, the default language is used.
	 * 
	 * @return string
	 * @internal
	 */
	public static function text(string $name, string $language = '')
	{
		$language = $language === '' ? config('crudkit.language', 'en') : $language;
		if(isset(config('crudkit-texts', '')[$language][$name]))
		{
			return config('crudkit-texts')[$language][$name] ;
		}
		else
		{
			throw new Exception(sprintf('Text "%s" not found (language "%s").', $name, $language));
		}		
	}
	
	/** 
	 * Gets all text constants for the specified language form the config (crudkit-texts.php)
	 * 
	 * @param string $language The language code. If not set, the default language is used.
	 * 
	 * @return string
	 * @internal 
	 */
	public static function getTexts(string $language = '')
	{
		$language = $language === '' ? config('crudkit.language', 'en') : $language;
		
		if(isset(config('crudkit-texts', '')[$language]))
		{
			return config('crudkit-texts')[$language];
		}
		else
		{
			throw new Exception(sprintf('Text for language "%s" not found.', $language));
		}
	}
	
	/** @internal */
	public static function noop()
	{}
	
	/** 
	 * A better implementation of PHP function empty(); - See code itself for details.
	 * 
	 * @param mixed $var The variable to check
	 * @return boolean
	 */
	public static function e($var)
	{
		$type = gettype($var);
		switch($type)
		{
			case 'boolean'	: return false;
			case 'integer'	: return false;
			case 'double'	: return false;
			case 'string'	: return $var === '' ? true : false;
			case 'array'	: return $var === [] ? true : false;
 			case 'object'	: return false;
			case 'resource'	: return false;
			case 'NULL'		: return true;
			default			: return false;
		}
	}
	
	/** 
	 * Shorthand call of throw new Exception(); with up to 8 placeholders 
	 * 
	 * ```
	 * //Example usage:
	 * DataProcessor::ex("%d errors while trying to delete user '%s'.", 4, "admin"); 
	 * ```
	 * 
	 * @param string $message The Exception message to show
	 * @param mixed $p1 (optional) placeholder
	 * @param mixed $p2 (optional) placeholder
	 * @param mixed $p3 (optional) placeholder
	 * @param mixed $p4 (optional) placeholder
	 * @param mixed $p5 (optional) placeholder
	 * @param mixed $p6 (optional) placeholder
	 * @param mixed $p7 (optional) placeholder
	 * @param mixed $p8 (optional) placeholder
	 */
	public static function ex(string $message, $p1 = '', $p2 = '', $p3 = '', $p4 = '', $p5 = '', $p6 = '', $p7 = '', $p8 = '')
	{
		throw new Exception(sprintf($message, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8));
	}

	/** 
	 * Shorthand call of throw new Exception(); with up to 8 placeholders, plus classname and method.
	 * 
	 * ```
	 * //Example usage:
	 * DataProcessor::crudkitException("%d errors while trying to delete user '%s'.", __CLASS__, __FUNCTION__ 4, "admin"); 
	 * ```
	 * 
	 * @param string $message The Exception message to show
	 * @param string $classOrFilename Classname or Filename
	 * @param string $methodOrFunctionName Method or Function name
	 * @param mixed $p1 (optional) placeholder
	 * @param mixed $p2 (optional) placeholder
	 * @param mixed $p3 (optional) placeholder
	 * @param mixed $p4 (optional) placeholder
	 * @param mixed $p5 (optional) placeholder
	 * @param mixed $p6 (optional) placeholder
	 * @param mixed $p7 (optional) placeholder
	 * @param mixed $p8 (optional) placeholder
	 * @internal
	 */
	public static function crudkitException(string $message, string $classOrFilename, string $methodOrFunctionName, $p1 = '', $p2 = '', $p3 = '', $p4 = '', $p5 = '', $p6 = '', $p7 = '', $p8 = '')
	{
		throw new Exception
		(
			sprintf("%s %s\n%s\n%s",
				'Crudkit Error:',
				sprintf($message, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8),
				sprintf('Class/File name: %s', $classOrFilename),
				sprintf('Method/Function name: %s', $methodOrFunctionName)
			)
		);
	}

	/** 
	 * A better implementation of PHP function var_dump();
	 * 
	 * Provides syntax-highlighted insight even into nested objects,arrays, etc.
	 * 
	 * ```
	 * //Example usage:
	 * DataProcessor::xout(['cars' => ['audi','bmw'], 'nothing' => (object)['name' => 'Mario', 'age' => 34]]);  
	 * ```
	 * 
	 * @param mixed $value The variable to print out
	 * @param bool $dontDie Default = false. If set to true the script will not be aborted after execution of this function.
	 * @param bool $initialCall dont change this parameter xout will use it as it calls itself
	 */
	public static function xout($value, bool $dontDie = false, bool $initCall = true)
	{
		//You can define your own syntax coloring here.
		$baseColor = 'black';
		$objectClassColor = 'gray';
		$arrayTypeColor = 'blue';
		$objectTypeColor = 'blue';
		$stringTypeColor = 'red';
		$integerTypeColor = 'orange';
		$doubleTypeColor = 'teal';
		$resourceTypeColor = 'purple';
		$resourceClosedTypeColor = 'plum';
		$booleanTypeColor = 'green';
		$nullTypeColor = 'gray';
	
		$result = $initCall ? '<div id="xout-container" style="font-family: Courier New; font-weight: bold; font-size: 15px; color:'.$baseColor.';">' : '';
	
		$isSimpleVar = false;
		$valueType = gettype($value);
		switch($valueType)
		{
			case 'array' : $result .= '<span>ARRAY</span><br />'.htmlspecialchars('['); break;
			case 'object' : $result .= '<span>OBJECT</span> <span style="color:'.$objectClassColor.';">' . get_class($value) . '</span><br />'.htmlspecialchars('('); break;
			default : $value = [$value]; $isSimpleVar = true; break;
		}
	
		$result .= '<ul style="list-style-type: none; margin: 0;">';
	
		foreach ($value as $key => $val)
		{
			$valType = gettype($val);
			if ($valType === 'array' || $valType === 'object')
			{
				if ($valueType === 'array')
				{
					$result .= '<li><span style="color:'.$arrayTypeColor.';">[' . htmlspecialchars(strval($key)) . ']</span><b style="color:'.$baseColor.';"> '.htmlspecialchars('=>').' </b><span>' . self::xout($val, $dontDie, false) . '</span></li>';
				}
				if ($valueType === 'object')
				{
					$result .= '<li><span style="color:'.$objectTypeColor.';">' . htmlspecialchars(strval($key)) . '</span><b style="color:'.$baseColor.';"> '.htmlspecialchars('->').' </b><span>' . self::xout($val, $dontDie, false) . '</span></li>';
				}
			}
			else
			{
				$color = 'black';
				switch($valType)
				{
					case 'string' : $color = $stringTypeColor; $val = htmlspecialchars('\'').$val.htmlspecialchars('\''); break;
					case 'integer' : $color = $integerTypeColor; $val = strval($val); break;
					case 'double' : $color = $doubleTypeColor; $val = strval($val); break;
					case 'resource' : $color = $resourceTypeColor; $val = 'resource ('.get_resource_type($val).')'; break;
					case 'resource (closed)' : $color = $resourceClosedTypeColor; $val = 'resource (closed)'; break;
					case 'boolean' : $color = $booleanTypeColor; $val = ($val === true) ? 'TRUE' : 'FALSE'; break;
					case 'NULL' : $color = $nullTypeColor; $val = 'NULL'; break;
				}
	
				$result .= '<li>';
				if(!$isSimpleVar)
				{
					if($valueType === 'array')
					{
						$result .= '<span style="color:'.$arrayTypeColor.';">[' . htmlspecialchars(strval($key)) . ']</span><b style="color:'.$baseColor.';"> '.htmlspecialchars('=>').' </b>';
					}
					if($valueType === 'object')
					{
						$result .= '<span style="color:'.$objectTypeColor.';">' . htmlspecialchars(strval($key)) . '</span><b style="color:'.$baseColor.';"> '.htmlspecialchars('->').' </b>';
					}
				}
				$result .= '<span style="color:'.$color.';">' . htmlspecialchars($val) . '</span></li>';
			}
		}
	
		$result .= '</ul>';
	
		if(!$isSimpleVar)
		{
			switch($valueType)
			{
				case 'array' : $result .= htmlspecialchars(']'); break;
				case 'object' : $result .= htmlspecialchars(')'); break;
			}
		}
	
		$result .= $initCall ? '</div>' : '';
	
		if($initCall) //Finished
		{
			echo($result);
			if(!$dontDie)
			{
				die();
			}
		}
		else //End of recursive call
		{
			return $result; 
		}
	}

	/** @ignore */
	

	/** @internal */
	public static function getCrudkitDbConfig()
	{
		$dbtype = config('database.default','__default__');
		$dbconfig = config('crudkit-db');
		
		return isset($dbconfig[$dbtype]) ? $dbconfig[$dbtype] : $dbconfig['__default__'];
	}

	/** 
	 * Swaps the values of two variables.
	 * @internal
	*/
	public static function swap(&$var1, &$var2) 
	{
		$helper=$var1;
		$var1=$var2;
		$var2=$helper;
	}

	/**
	 * @param mixed $value The value to append
	 * @param array $array The array (as reference)
	 * @param bool $appendIfEmpty
	 * 
	 * @return void
	 * @internal
	 */
	public static function appendToArray($value, array &$array, bool $appendIfEmpty = true)
	{
		if($appendIfEmpty || !self::e($value))
		{
			$array[] = $value;
		}
	}

	/**
	 * Formats 3620 seconds into 1h 0m 20s
	 * @param int $seconds
	 * 
	 * @return string
	 * @internal
	 */
	public static function formatSeconds(int $seconds)
	{
		$dt1 = new \DateTime();
		$dt2 = clone $dt1;

		$dt2->add(new \DateInterval('PT'.$seconds.'S'));
		$i = $dt1->diff($dt2);

		if($seconds >= 3600){return $i->format('%hh %im %ss');};
		if($seconds >= 60){return $i->format('%im %ss');};
		return $i->format('%ss');
	}

	/**
	 * This is upt to 2000 times faster than array_merge().  
	 * @param array $array the array which will be merged into $intoArray
	 * @param array $intoArray the new "result" array (this one will be modified)
	 * 
	 * @return void
	 * @internal
	 */
	public static function fastArrayMerge(array &$array, array &$intoArray) 
	{
		foreach($array as $i) 
		{
			$intoArray[] = $i;
		}
	}

	
	/**
	 * Handy method for measuring execution time. Use DataProcessor::start() and DataProcessor::end() 
	 * 
	 * @param int $index Use different indexes if there are multiple DataProcessor::start() points, befor there is and DataProcessor::end().
	 * 
	 * @return void
	 * @internal
	 */
	public static function start(int $index = 0)
	{
		self::$startTimes[$index] = microtime(true);
	}

	
	/**
	 * Retruns the time in seconds betweetn DataProcessor::start() and DataProcessor::end()
	 * 
	 * @param int $index The index for the corresponding DataProcessor::start()
	 * 
	 * @return float
	 * @internal
	 */
	public static function end(int $index = 0)
	{
		return microtime(true) - self::$startTimes[$index];
	}
	
	/** @ignore */
	public static function getUrlParameters(string $pageId, int $pageNumber = null, string $searchText = '', string $searchColumnName = '', array $filters = [], array $primaryKeyValues = [], array $primaryKeyColumns = [], array $record = [])
	{
		$urlParameters = [];
		$urlParameters['page-id'] = $pageId; 
		if(!self::e($pageNumber))
		{ 
			$urlParameters['page-number'] = $pageNumber; 
		}
		if(!self::e($searchText))			
		{ 
			$urlParameters['st'] = $searchText; 
		} 
		if(!self::e($searchColumnName))
		{ 
			$urlParameters['sc'] = $searchColumnName; 
		}
		
		if(!self::e($filters))
		{
			foreach($filters as $index => $filter)
			{
				$urlParameters['ff-'.$index] = $filter->field;
				$urlParameters['fo-'.$index] = $filter->operator;
				$urlParameters['fv-'.$index] = $filter->value;
			}
		}
		
		if(!self::e($primaryKeyValues))
		{
			foreach($primaryKeyValues as $primaryKeyNumber => $primaryKeyValue)
			{
				$urlParameters['pk-'.((int)$primaryKeyNumber)] = $primaryKeyValue;
			}
		}
		
		if(!self::e($primaryKeyColumns))
		{
			foreach($primaryKeyColumns as $primaryKeyNumber => $primaryKeyColumn)
			{
				$urlParameters['pk-'.((int)$primaryKeyNumber)] = $record[$primaryKeyColumn]; 
			}
		}
		
		return $urlParameters;
	}

	/**
	 * Gets a JSON string which can be used for CRUDKit custom ajax error results.
	 * 
	 * @param mixed $data which will be logged by the JS console on the client: console.log(data);
	 * @param string $message A simple message for the user
	 * @param string $p1 p1 to p8 are placeholders for the message
	 * 
	 * @return string JSON string you can return to the client
	 */
	public static function getAjaxErrorResult($data, string $message = '', $p1 = '', $p2 = '', $p3 = '', $p4 = '', $p5 = '', $p6 = '', $p7 = '', $p8 = '')
	{
		$message = sprintf($message, $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8);

		return json_encode((object)['type' => 'error', 'message' => $message, 'data' => $data]);
	}

	/**
	 * Gets a JSON string which can be used for CRUDKit custom ajax results.
	 * 
	 * @param array $data is an array of objects:
	 * 
	 * ```php
	 * //Example 
	 * $data =
	 * [
	 * 		(object)['id' => 1, 'text' => 'john doe', 'img' => 'data:image/png;base64,R0lG...'],
	 * 		(object)['id' => 2, 'text' => 'jane doe', 'img' => 'data:image/png;base64,R0ax...']
	 * ];
	 * ```
	 * 
	 * @return string JSON string
	 */
	public static function getAjaxResult(array $data)
	{
		return json_encode((object)['type' => 'result', 'data' => (object)['results' => $data]]);
	}

	/**
	 * @param string $data The binary image data as string (mostly it comes like this from the DB)
	 * @param int $scaleWidthTo If you want to scale the image width
	 * @return string The base64 encoded image as a string (PNG format)
	 * @internal
	 */
	public static function binaryStringToBase64Png(string $data, int $scaleWidthTo = -1)
	{
		$image = imagecreatefromstring($data);
		if($scaleWidthTo > 0)
		{
			$image = imagescale($image, $scaleWidthTo);
		}

		ob_start();
		imagepng($image);
		$imageBase64 = base64_encode(ob_get_clean());
		imagedestroy($image);

		return $imageBase64;
	}
	#endregion

	/**
	 * Gets an array of CRUDKit primary keys from the CRUDKit request
	 * 
	 * @param Request $request
	 * @return array
	 * @internal
	 */
	public static function getPrimaryKeyValuesFromRequest(Request $request)
	{
		$primaryKeyValues = [];
		$index = 0;

		while ($request->has('pk-' . $index)) {
			$primaryKeyValues[] = $request->input(sprintf('pk-%d', $index));
			$index = $index + 1;
		}

		return $primaryKeyValues;
	}

	
	/**
	 * Gets an array of filters from the CRUDKit request
	 * 
	 * @param Request $request
	 * @return Filter[]
	 * @internal
	 */
	public static function getFiltersFromRequest(Request $request)
	{
		$filters = [];
		$index = 0;

		while ($request->has('ff-' . $index)) 
		{
			$filters[] = new Filter(request('ff-' . $index), request('fo-' . $index), request('fv-' . $index));
			$index += 1;
		}

		return $filters;
	}

	/**
	 * Gets an array of filters from a given CRUDKit URL
	 * 
	 * @param string $url
	 * @return Filter[]
	 * @internal
	 */
	public static function getFiltersFromUrl(string $url)
	{
		$filters = [];
		$params = self::getParamsFromUrl($url);

		$index = 0;
		while(isset($params['ff-'.$index]))
		{
			$filters[] = new Filter($params['ff-' . $index], $params['fo-' . $index], $params['fv-' . $index]);
			$index += 1;
		}

		return $filters;
	}

	/**
	 * Gets an array with primary key values from a given CRUDKit URL
	 * @param string $url
	 * @return array
	 * @internal
	 */
	public static function getPrimaryKeyValuesFromUrl(string $url)
	{
		$primaryKeyValues = [];
		$params = self::getParamsFromUrl($url);

		$index = 0;
		while(isset($params['pk-'.$index]))
		{
			$primaryKeyValues[] = $params['pk-'.$index];
			$index += 1;
		}

		return $primaryKeyValues;
	}

	/**
	 * Gets an array of the URL params $key => $value
	 * @param string $url
	 * 
	 * @return array
	 * @internal
	 */
	public static function getParamsFromUrl(string $url)
	{
		$parts = parse_url($url);
		if(!$parts || !isset($parts['query']) || self::e($parts['query']))
		{
			return [];
		}

		$params = [];
		parse_str($parts['query'], $params);

		return $params;
	}
}

