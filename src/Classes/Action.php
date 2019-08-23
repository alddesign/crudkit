<?php
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

/**
 * Defines a custom action on a page. 
 * 
 * Actions are buttons on a page.
 * @internal
 */
class Action
{
    public const POSITIONS = ['top','bottom','both'];

    public $name = '';
    public $label = '';
    public $columnLabel = '';
    /** @var callable $callback The callback function. */ 
    public $callback = null;
    public $onList = true;
    public $onCard = true;
    /** @var string Font awesome icon name */
    public $faIcon = '';
    /** @var string $btnClass Admin LTE button type. ''|'default'|'primary'|'info'|'success'|'danger'|'warning' */
    public $btnClass = '';
    /** @var string $position Position on card page: 'top'|'bottom'|'both' */
    public $position = '';
    public $enabled = true; 
    
    /** @var array $data Additional data which can be set when creatig the action. */
    public $data = [];

    public function __construct($name, string $label, string $columnLabel, callable $callback, bool $onList = true, bool $onCard = true, string $faIcon = '', string $btnClass = '', string $position = 'both', bool $enabled = true)
    {	
        if(!in_array($position, self::POSITIONS, true))
		{
			$position = 'both';
        }
        	
        $this->name = $name;
        $this->label = $label;
        $this->columnLabel = $columnLabel;
        $this->callback = $callback;
        $this->onList = $onList;
        $this->onCard = $onCard;
        $this->faIcon = $faIcon;
        $this->btnClass = $btnClass;
        $this->position = $position;
        $this->enabled = $enabled;
    }
}
