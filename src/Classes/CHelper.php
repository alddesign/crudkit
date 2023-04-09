<?php
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use \Exception;
use \Illuminate\Http\Request;

/**
 * Helper Class for Crudkit. Yes, i know, classes like "Helper" are bad design...
 * @internal
 */
abstract class CHelper
{
	private function __construct()
	{
		//prevent calling constructor
	}

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
	 * A better implementation of PHP function empty();
	 * 
	 * @param mixed $var The variable to check
	 * @param bool $zeroIsEmpty Indicates whether a int/doube zero is considered empty
	 * @return boolean
	 */
	public static function e($var, bool $zeroIsEmpty = false)
	{
		return
		(
			($zeroIsEmpty && $var === 0) ||
			($zeroIsEmpty && $var === 0.0) ||
			$var === '' ||
			$var === [] ||
			$var === (object)[] ||
			$var === null
		);
	}

	/** 
	 * A better implementation of PHP function var_dump();
	 * 
	 * Provides syntax-highlighted insight even into nested objects,arrays, etc.
	 * 
	 * ```
	 * //Example usage:
	 * CHelper::xout(['cars' => ['audi','bmw'], 'nothing' => (object)['name' => 'Mario', 'age' => 34]]);  
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
		$crudkitDbConfig = config('crudkit-db');
		
		return isset($crudkitDbConfig[$dbtype]) ? $crudkitDbConfig[$dbtype] : $crudkitDbConfig['__default__'];
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
	 * @param \Illuminate\Http\Request $request
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
	 * @param \Illuminate\Http\Request $request
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