<?php
/**
 * Class FilterDefinition
 */
namespace Alddesign\Crudkit\Classes;

/** 
 * Represents a filter definition on a record (no acutaly filter)
 */
class FilterDefinition
{
	/** @var string The field to filter */
	public $field = '';
	/** @var string The comparison operator */
	public $operator = '';
	/** @var string Type of the filter ('const' or 'field')*/
	public $type = '';
	/** @var string The value, or the reference field name */
	public $fieldnameOrValue = null;
	
	const VALID_OPERATORS = ['>','<','>=','<=','=','!=','contains','startswith','endswith'];
	const VALID_TYPES = ['const','field'];
	
	/**
	 * Constructor.
	 * @param string $field The field to apply the filter
	 * @param string $operator ['>','<','>=','<=','=','!=','contains','startswith','endswith']
	 * @param string $type ['const','field']. 'const' = fixed value, 'field' = the fieldname which provides the value.
	 * @param string $fieldnameOrValue
	 */
    public function __construct(string $field, string $operator, string $type, $fieldnameOrValue)
    {
		if(!in_array($operator, self::VALID_OPERATORS, true))
		{
			throw new CException('Filter Definition: invalid operator "%s".', $operator);
		}
		if(!in_array($type, self::VALID_TYPES, true))
		{
			throw new CException('Filter Definition: invalid type "%s".', $type);
		}
		
		$this->field = $field;
		$this->operator = $operator;
		$this->type = $type;
		$this->fieldnameOrValue = $fieldnameOrValue;
    }
	
	/**
	* Converts FilterDefinition array plus record data into ready to use array.
	*
	* @param array record The record for FilterDefinitions with type = 'field'
	* @return Filter Returns ready to use filter.
	*/
	public function toFilter($record = [])
	{
		if($this->type === 'const')
		{
			return new Filter($this->field, $this->operator, $this->fieldnameOrValue);
		}
		
		if($this->type === 'field')
		{
			if(CHelper::e($record))
			{
				throw new CException('Cannot convert FilterDefinition to Filter. Reference record needed for "field" typ filters.');
			}

			if(!array_key_exists($this->fieldnameOrValue, $record))
			{
				throw new CException('Cannot convert FilterDefinition to Filter. Invalid field name "%s" in FilterDefinition.', $this->fieldnameOrValue);
			}
		
			return new Filter($this->field, $this->operator, $record[$this->fieldnameOrValue]);
		}
	}
}