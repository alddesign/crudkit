<?php
namespace Alddesign\Crudkit\Classes;

use Alddesign\Crudkit\Classes\DataProcessor as dp;

class Filter
{
	public $field;
	public $operator;
	public $value;
	
	const VALID_OPERATORS = ['>','<','>=','<=','=','!=','contains','startswith','endswith'];
	
    public function __construct(string $field, string $operator, string $value)
    {
		if(!in_array($operator, self::VALID_OPERATORS, true))
		{
			throw new Exception(sprintf('Filter Definition: invalid operator "%s".', $operator));
		}
		
		if(dp::e($field))
		{
			throw new Exception('Filter Definition: Field needed.');
		}
		
		$this->field = $field;
		$this->operator = $operator;
		$this->value = $value;
    }
	
	/**
	* Adds a filter to an url param array.
	*
	* @param [] urlParams (pass by reference) Url Params array (laravel).
	* @param int filterNo Filter no in the url params array.
	*/
	public function appendToUrlParams(array &$urlParams, int $filterNo = 0)
	{
		$urlParams['ff-'.$filterNo] = $this->field;
		$urlParams['fo-'.$filterNo] = $this->operator;
		$urlParams['fv-'.$filterNo] = $this->value;
	}
}