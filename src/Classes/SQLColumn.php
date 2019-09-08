<?php

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
 * ['readonly'] (bool)		--> This field is readonly for the user.
 * ['hidden'] (bool)			--> This field is hidden on all pages.
 * ['hidden-on-list'] (bool)	--> This field is hidden on list pages.
 * ['hidden-on-card'] (bool)	--> This field is hidden on card pages.
 * ['hidden-on-create'] (bool)	--> This field is hidden create card page.
 * ['hidden-on-update'] (bool)	--> This field is hidden update card page.
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
	/** @var string */ public $relationType = 'none';
	
	/** @var string[] Mapping datatye <> Datatype in database. Exampe dec = decimal, float = decimal */
	private static $types =
	[
		'text' 		=> 'text',
		'string' 	=> 'text',
		'textlong' 	=> 'textlong',
		'email' 	=> 'email',
		'integer' 	=> 'integer',
		'bigint' 	=> 'integer',
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
		'blob' 		=> 'blob',
		'binary' 	=> 'blob',
		'bin' 		=> 'blob',
		'image' 	=> 'image',
		'picture' 	=> 'image'
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
		$type = mb_strtolower($type, 'UTF-8');
		
		if(!isset(self::$types[$type]))
		{
			dp::ex('Invalid datatype "%s".', $type);
		}
		
        $this->name = $name;
        $this->label = $label;
        $this->type = self::$types[$type];
        $this->options = $options;
		
		if($type === 'decimal' && empty($this->options['step']))
		{
			$this->options['step'] = 0.01;
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
		
		$hidden 		= (isset($this->options['hidden']) && $this->options['hidden'] == true);
		$hiddenOnList 	= (isset($this->options['hidden-on-list']) && $this->options['hidden-on-list'] == true);
		$hiddenOnCard 	= (isset($this->options['hidden-on-card']) && $this->options['hidden-on-card'] == true);
		$hiddenOnCreate	= (isset($this->options['hidden-on-create']) && $this->options['hidden-on-create'] == true);
		$hiddenOnUpdate = (isset($this->options['hidden-on-update']) && $this->options['hidden-on-update'] == true);
		
		switch($pageType)
		{
			case '' 		: return($hidden); break;
			case 'list' 	: return($hidden || $hiddenOnList); break;
			case 'card' 	: return($hidden || $hiddenOnCard); break;
			case 'create'	: return($hidden || $hiddenOnCreate); break;
			case 'update'	: return($hidden || $hiddenOnUpdate); break;
			default 		: return(false);
		}
	}
}

