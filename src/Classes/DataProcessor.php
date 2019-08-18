<?php 
namespace Alddesign\Crudkit\Classes;

use \DateTime;
use \Exception;
	 
class DataProcessor
{
	/** @ignore */ private $table = null;
	/** @ignore */ private $columns = null;
	/** @ignore */ private $columnNames = null;
	/** @ignore */ private $allColumns = null;
	/** @ignore */ private $primaryKeyColumns = null;
	/** @ignore */ private $primaryKeyColumnNames = null;
	
	/** @ignore */ private $dbconf = null; 

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
	public function preProcess($requestData, string $actionType)
	{
		$this->table->fetchAllColumns(); //intensive workload
		$this->allColumns = $this->table->getAllColumns();

		if($actionType !== 'update' && $actionType !== 'create')
		{
			throw new Exception(sprintf('Pre process: invalid action type "%s".', $actionType));
		}
		
		$recordData = [];
				
		$dataExists = false; //[boolean] Per definition --> data exist, if data is in request, even if NULL
		$dataIsNull = false; //[boolean] Per definition --> data === null, no information about if it exists in request
		$dbDefault = false; //[boolean] Field has a default value in database
		$dbNotNull = false; //[boolean] Field has NOT NUL set in database 
		
		foreach($this->allColumns as $columnName => $options)
		{	
			$data = array_key_exists($columnName, $requestData) ? $requestData[$columnName] : null;
			
			$dataExists = array_key_exists($columnName, $requestData);
			$dataIsNull = $data === null;
			$dbDefault = $options['default'] !== null; //The default value is always provided as string from Doctrine DBAL
			$dbNotNull = $options['notnull'] === true;
			$useDbDefault = false; 
			
			//$datatype = $options['datatype'];
			$datatype = $this->columns[$columnName]->type;
			
			if($dataExists 	&& $dataIsNull 	&& $dbNotNull 	&& $dbDefault	) { /*Set DEFAULT*/ $this->setDefault($useDbDefault, $data, $options['default'], $datatype, $columnName);	}
			if($dataExists 	&& $dataIsNull 	&& $dbNotNull 	&& !$dbDefault	) { /*Set EMPTY*/ 	$this->setEmpty($data, $datatype, $columnName);							}
			if($dataExists 	&& $dataIsNull 	&& !$dbNotNull 	&& $dbDefault	) { /*Set DEFAULT*/ $this->setDefault($useDbDefault, $data, $options['default'], $datatype, $columnName);	}
			if($dataExists 	&& $dataIsNull 	&& !$dbNotNull 	&& !$dbDefault	) { /*Set NULL*/ 	$this->setNull($data, $datatype, $columnName); 							}
			if($dataExists 	&& !$dataIsNull && true			&& true			) { /*Set DATA*/ 	$this->setData($data, $datatype, $columnName); 							}
			
			if(!$dataExists && true 		&& $dbNotNull 	&& $dbDefault	) { /*Set DEFAULT*/ $this->setDefault($useDbDefault, $data, $options['default'], $datatype, $columnName);	}
			if(!$dataExists && true 		&& $dbNotNull 	&& !$dbDefault	) { /*Set EMPTY*/ 	$this->setEmpty($data, $datatype, $columnName);							}
			if(!$dataExists && true 		&& !$dbNotNull 	&& $dbDefault	) { /*Set DEFAULT*/ $this->setDefault($useDbDefault, $data, $options['default'], $datatype, $columnName);	}
			if(!$dataExists && true 		&& !$dbNotNull 	&& !$dbDefault	) { /*Set NULL*/ 	$this->setNull($data, $datatype, $columnName);							}			
							
			$this->addData($useDbDefault, $recordData, $columnName, $data, $options, $requestData, $actionType, $dataExists);
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
	private function addData(bool $useDbDefault, array &$recordData, $columnName, $data, array $options, array $requestData, string $actionType, bool $dataExists)
	{	
		if($actionType === 'update')
		{
			//Special treatment for blob/binary when updating
			if($options['datatype'] === 'blob' || $options['datatype'] === 'binary')
			{
				if(isset($requestData[$columnName.'___DELETEBLOB']) && $requestData[$columnName.'___DELETEBLOB'] === 'on')
				{
					$recordData[$columnName] = $data; //$data should be null or empty here (logic before found this out). Should do the job, except someone checks the "delete" checkbox, and adds a file. --> Then the file will be stored,...
					return;
				}
				if($dataExists)
				{
					$recordData[$columnName] = $data; //Just Set the value
					return;
				}

				return; //The field will be left out in th UPDATE...
			}
			//If it is a update, we dont need to specify unexisting data
			if(!$dataExists)
			{
				return;
			}
		}	
		
		if($actionType === 'insert')
		{
			//Exclude the field from SQL statement, if there is a DB defualt value and not data:
			if($useDbDefault && !$dataExists)
			{
				return;
			}
		}	
	

		$recordData[$columnName] = $data; //Default
	}
	
	/**
	 *
	 * We can get the default value here, but most important: 
	 * we set $useDbDefault to TRUE, so we can exclude this field form INSERT, UPDATE statement...
	 * 
	 * @internal 
	 */
	private function setDefault(bool &$useDbDefault, &$fieldValue, string $defaultValue, string $fieldDatatype, string $columnName = '')
	{
		$useDbDefault = true;
		
		//The $defaultValue is always provided as string from Doctrine DBAL
		switch($fieldDatatype)
		{
			case 'string' 	: $fieldValue = $defaultValue; break;
			case 'text' 	: $fieldValue = $defaultValue; break;
			case 'integer' 	: $fieldValue = intval($defaultValue); break;
			case 'decimal' 	: $fieldValue = floatval($defaultValue); break;
			case 'boolean' 	: $fieldValue = ($defaultValue == true); break;
			case 'datetime' : $fieldValue = $defaultValue; break;
			case 'date' 	: $fieldValue = $defaultValue; break;
			case 'time' 	: $fieldValue = $defaultValue; break;
			case 'blob' 	: $fieldValue = $defaultValue; break; //Cannot have default value in MySQL...
			case 'binary' 	: $fieldValue = $defaultValue; break; //Cannot have default value in MySQL...
			case 'enum' 	: $fieldValue = $defaultValue; break;
			default 		: throw new Exception(sprintf('Unsupported SQL datatype "%s". (column "%s", table "%s")', $fieldDatatype, $columnName, $this->table->getName()));
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
	
	// ### POST PROCESSING #############################################################################################################################################
	/**
	* Processing data after SELECTing it from the database, before sending it to the view.
	* 
	* @param $records
	* @param bool $singleRecord
	* @return array $records
	* @internal
	*/
	public function postProcess($records, bool $singleRecord = false, bool $rawData = false)
	{		

		$records = $singleRecord ? [$records] : $records;//Can now be used in foreach
		
		foreach($records as &$record)
		{
			foreach($record as $columnName => &$columnValue)
			{
				//Processing
				$this->processBinaryData($columnName, $columnValue, $singleRecord, $rawData);
				//$this->mapEnum($columnName, $columnValue);
				$this->formatDateAndTime($columnName, $columnValue);
				if(!$rawData)
				{
					$this->formatDecimal($columnName, $columnValue);
					$this->formatBoolean($columnName, $columnValue);
				}
			}
			unset($columnValue);	
		}
		unset($record);		
		
		//Converting Object to Array,... dont ask me about this...
		$helper = [];
		foreach($records as $record)
		{
			$helper[] = ((array) $record);
		}
		$records = $helper;

		return $singleRecord ? $records[0] : $records;
	}

	/** @internal */
	private function formatBoolean($columnName, &$columnValue)
	{
		if($this->columns[$columnName]->type === 'boolean' && !self::e($columnValue))
		{
			$columnValue = $columnValue == true ? self::text('yes') : self::text('no');
		}
	}

	/** @internal */
	private function formatDecimal($columnName, &$columnValue)
	{
		if($this->columns[$columnName]->type === 'decimal' && !self::e($columnValue))
		{
			$columnValue = number_format(floatval($columnValue), 2, ',', '.');
		}
	}
	
	/** @internal */
	private function processBinaryData($columnName, &$columnValue, $singleRecord, $rawData)
	{
		if($singleRecord)
		{
			if($this->columns[$columnName]->type === 'image') //Single record and Image --> we got the blob data from DB, we will show the image.
			{
				$columnValue = self::e($columnValue) ? $columnValue : base64_encode($columnValue);
			}
			if($this->columns[$columnName]->type === 'blob') //here we just got the size in kB from the DB
			{
				$columnValue = $this->formatBytes($columnValue, $rawData);
			}
		}
		else
		{
			if($this->columns[$columnName]->type === 'blob' || $this->columns[$columnName]->type === 'image') //here we just got the size in kB from the DB
			{
				$columnValue = $this->formatBytes($columnValue, $rawData);
			}
		}
	}

	/** @internal */	
	private function formatBytes($bytes, bool $rawData, int $precision = 2) 
	{ 
		$bytes = self::e($bytes) ? 0 : $bytes;
		
		$units = array('B', 'KB', 'MB', 'GB', 'TB'); 

		$bytes = max($bytes, 0); 
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
		$pow = min($pow, count($units) - 1); 

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);
		// $bytes /= (1 << (10 * $pow)); 

		return $rawData ? round($bytes, $precision) : round($bytes, $precision) . ' ' . $units[$pow]; 
	} 

	/** @internal */
	private function mapEnum($columnName, &$columnValue)
	{					
		if($this->columns[$columnName]->type === 'enum')
		{
			if(isset($this->columns[$columnName]->options['enum'][$columnValue]))
			{				
				$columnValue = [ 'value' => $columnValue, 'label' => $this->columns[$columnName]->options['enum'][$columnValue]];	
			}
			else
			{
				$columnValue = ['value' => '', 'label' => ''];
			}
		}
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
	private function getUploadFileData($uploadedFile) // Parameter is \Illuminate\Http\UploadedFile Object or NULL
	{
		if(self::e($uploadedFile))
		{
			return null;
		}
		
		$binaryData = null;
				
		$handle = fopen($uploadedFile->getPathname(), 'rb');
		$binaryData = fread($handle, $uploadedFile->getClientSize());
		fclose($handle);
		
		return($binaryData);
	}
	
	// ### HELPER FUNCTIONS ##########################################################################################################################################
	/** 
	 * Gets a single text constant for the specified language form the config (crudkit-texts.php)
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
	 * A better implementation of PHP function empty();
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
	 */
	public static function xout($value, bool $dontDie = false)
	{
		self::xoutHelper($value, $dontDie, true);
	}

	/** @ignore */
	private static function xoutHelper($value, bool $dontDie, bool $initCall)
	{
		$result = $initCall ? '<div id="xout-container" style="font-family: Courier New; font-weight: bold; font-size: 15px;">' : '';
		
		$isSimpleVar = false;
		switch(gettype($value))
		{
			case 'array' : $result .= '<span>ARRAY</span><br />'.htmlspecialchars('['); break;
			case 'object' : $result .= '<span>OBJECT</span> <span style="color:grey;">' . get_class($value) . '</span><br />'.htmlspecialchars('('); break;
			default : $value = [$value]; $isSimpleVar = true; break;
		}
		
		$result .= '<ul style="list-style-type: none; margin: 0;">';
		
		foreach ($value as $key => $val)
		{
			if (gettype($val) === 'array' || gettype($val) === 'object')
			{
				if (gettype($val) === 'array')
				{
					$result .= '<li><span style="color:blue;">[' . htmlspecialchars($key) . ']</span><b style="color:black;"> '.htmlspecialchars('=>').' </b><span>' . self::xoutHelper($val, $dontDie, false) . '</span></li>';
				}
				if (gettype($val) === 'object')
				{
					$result .= '<li><span style="color:blue;">' . htmlspecialchars($key) . '</span><b style="color:black;"> '.htmlspecialchars('->').' </b><span>' . self::xoutHelper($val, $dontDie, false) . '</span></li>';
				}
			}
			else
			{
				$color = 'black';
				switch(gettype($val))
				{
					case 'string' : $color = 'red'; $val = htmlspecialchars('\'').$val.htmlspecialchars('\''); break;
					case 'integer' : $color = 'orange'; break;
					case 'double' : $color = 'teal'; break;
					case 'resource' : $color = 'black'; break;
					case 'boolean' : $color = 'green'; $val = ($val === true) ? 'TRUE' : 'FALSE'; break;
					case 'NULL' : $color = 'grey'; $val = 'NULL'; break;
				}
					
				$result .= '<li>';
				if(!$isSimpleVar)
				{
					if(gettype($value) === 'array')
					{
						$result .= '<span style="color:blue;">[' . htmlspecialchars($key) . ']</span><b style="color:black;"> '.htmlspecialchars('=>').' </b>';
					}
					if(gettype($value) === 'object')
					{
						$result .= '<span style="color:blue;">' . htmlspecialchars($key) . '</span><b style="color:black;"> '.htmlspecialchars('->').' </b>';
					}
				}
				$result .= '<span style="color:'.$color.';">' . htmlspecialchars($val) . '</span></li>';
			}
		}
		
		$result .= '</ul>';
		
		if(!$isSimpleVar)
		{
			switch(gettype($value))
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

	/** @internal */
	public static function getCrudkitDbConfig()
	{
		$dbtype = config('database.default','___generic___');
		$dbconfig = config('crudkit-db');
		
		return isset($dbconfig[$dbtype]) ? $dbconfig[$dbtype] : $dbconfig['___generic___'];
	}
}

