<?php
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;
use Exception;

/** 
 * Represents a column in a table.
 * 
 * Valid $option values:
 * ```
 * ['required'] (bool) 		--> Makes this field marked as required for the user.
 * ['enum'] (array)			--> Value/Label array for enum values
 * ['max'] (int) 			--> Maximum text lenght/number value.            
 * ['min'] (int) 			--> Minimum text lenght/number value.
 * ['tooltip'] (string)		--> Shows a tooltip with this info to the user.
 * ['description'] 			--> Adds an extended description to the label
 * ['suffix'] (string)		--> Appends this suffix after the actual value in views. (Example "€" sign behind prices)
 * ['step'] (string)		--> Digits for decimal columns. Default = 0.01
 * ['email'] (bool)			--> Shows field als mailto: link
 * ['link'] (bool)			--> Shows field as <a> link
 * ['textarea'] (bool)		--> Displays the fields as a textarea if possible
 * ['readonly'] (bool)		--> This field is readonly for the user.
 * ['hidden'] (bool)		--> This field is hidden on all pages [default: false]
 * ['list'] (bool)			--> Show this field on list pages [default: true]
 * ['card'] (bool)			--> Show this field on card pages [default: true]
 * ['create'] (bool)		--> Show this field on create card pages [default: true]
 * ['update'] (bool)		--> Show this field update card pages [default: true]
 * ['imageUrl'] (bool)		--> Show this field as an image (per hyperlink)
 * ```
 * 
 * @see TableDescriptor
 * @internal
 */
class SQLColumn
{
    /** @var string */ public $name = null;
    /** @var string */ public $label = null;
	/** @var string */ public $type = null;

	/** @var array See class description above for options available */
	public $options = [];
	/** @var bool */ 
	public $isManyToOne = false;
	/** @var bool */ 
	public $isCustomAjax = false;
	/** @var AjaxOptions */
	protected $ajaxOptions = null;
	/** @var callable */
	public $onLoadCallback = null;
	/** @var callable */
	public $onSearchCallback = null;

	/** @var string[] Mapping datatye <> Datatype in database. Exampe dec = decimal, float = decimal */
	const VALID_TYPES =
	[
		'text' 		=> 'text',
		'string' 	=> 'text',
		'integer' 	=> 'integer',
		'bigint' 	=> 'integer',
		'biginteger'=> 'integer',
		'smallint'	=> 'integer',
		'int' 		=> 'integer',
		'decimal' 	=> 'decimal',
		'dec' 		=> 'decimal',
		'double' 	=> 'decimal',
		'float'		=> 'decimal',
		'enum' 		=> 'enum',
		'datetime' 	=> 'datetime',
		'date' 		=> 'date',
		'time' 		=> 'time',
		'boolean' 	=> 'boolean',
		'bool' 		=> 'boolean',
		'tinyint'	=> 'boolean',
		'blob' 		=> 'blob',
		'binary' 	=> 'blob',
		'bin' 		=> 'blob',
		'image' 	=> 'image',
		'picture' 	=> 'image'
	];

	const VALID_OPTIONS =
	[
		'required',
		'enum',
		'max',    
		'min',
		'tooltip',
		'description',
		'suffix',
		'step',
		'email',
		'link',
		'textarea',
		'readonly',
		'hidden',
		'list',
		'card',
		'create',
		'update',
		'url',
		'imageUrl',
	];
	
	/**
	 * Constructor
	 * @param string $name
	 * @param string $label
	 * @param string $type
	 * @param array $options (optional) 
	 */
    public function __construct(string $name, string $label, string $type, array $options = [])
    {
		//Checking type	
        $this->name = $name;
        $this->label = $label;
		$this->type = self::mapDatatype($type, $name);
		$this->options = $options;
		
		if($type === 'decimal' && empty($this->options['step']))
		{
			$this->options['step'] = 0.01;
		}

		$this->checkOptions();
    }

	/**
	 * Maps various names for datatypes to a uniform name.
	 * `Example: string, text = text`  
	 * `Example: int, integer, bigint = integer`  
	 * 
	 * @param string $datatype The name of the datatype
	 * @param string $columnName The name of the column for a more precise exception message, if needed.
	 * @throws \Exception if $datatype is not recognized as a valid datatype
	 * @return string the uniform datatype name
	 * @internal
	 */
	public static function mapDatatype(string $datatype, string $columnName = '')
	{
		$datatype = mb_strtolower($datatype, 'UTF-8');
		if(!isset(self::VALID_TYPES[$datatype]))
		{
			if(CHelper::e($columnName))
			{
				throw new CException('Invalid datatype "%s".', $datatype);
			}
			else
			{
				throw new CException('Column "%s": invalid datatype "%s".', $columnName, $datatype);
			}
		}

		return self::VALID_TYPES[$datatype];
	}
	
	private function checkOptions()
	{
		foreach($this->options as $name => $value)
		{
			if(!in_array($name, self::VALID_OPTIONS, true))
			{
				throw new CException('Invalid SQLColumn option "%s".', __CLASS__, __FUNCTION__, $name);
			}
		}
	}

	/**
	 * Checks if the column is hidden (in general or on a specific page type.)
	 * 
	 * @param string $pageType Valid page types are: '','list','card','create','update' ('' = all pages)
	 * @return bool
	 */
	public function isHidden(string $pageType = '')
	{
		$pageType = in_array($pageType, ['','list','card','create','update'], true) ? $pageType : '';
		
		if(isset($this->options['hidden']) && $this->options['hidden'] == true)
		{
			return true;
		}

		if(isset($this->options[$pageType]) && $this->options[$pageType] == false)
		{
			return true;
		}

		return false;
	}

	public function triggerOnLoadEvent(&$value, $record, TableDescriptor $table, PageDescriptor $page, SQLColumn $column)
	{
		$text = '';
		call_user_func_array($this->onLoadCallback, array(&$value, &$text, $record, $table, $page, $column));
		$value = [$value, $text];
	}

	public function triggerOnSearchEvent(&$results, $input, TableDescriptor $table, PageDescriptor $page, SQLColumn $column)
	{
		call_user_func_array($this->onSearchCallback, array(&$results, $input, $table, $page, $column));
	}

	/** @return void */
	public function setAjaxOptions(AjaxOptions $ajaxOptions = null)
	{
		if($ajaxOptions === null)
		{
			$this->ajaxOptions = new AjaxOptions('', [], 1, 0);
		}
		else
		{
			$this->ajaxOptions = $ajaxOptions;
		}
	}

	/**
	 * @return AjaxOptions
	 */
	public function getAjaxOptions()
	{
		return $this->ajaxOptions;
	}
}

