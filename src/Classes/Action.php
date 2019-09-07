<?php
declare(strict_types=1);
namespace Alddesign\Crudkit\Classes;

/**
 * Defines a custom action on a page. 
 * 
 * Actions are buttons on a page which execute user definded functions.
 */
class Action
{
    /** @internal */
    private const POSITIONS = ['top','bottom','both'];
    /** @internal */
    private const BTNCLASSES = ['default','primary','info','success','danger','warning'];

    /** @var string $name Unique name of the action. */
    public $name = '';
    /** @var string $label Label of the action button. */
    public $label = '';
    /** @var string $columnLabel Label of the action column of list pages. */
    public $columnLabel = '';
    /** @var callable $callback The callback function. */ 
    public $callback = null;
    /** @var bool $onList  Show action on list pages.*/
    public $onList = true;
    /** @var bool $onCard  Show action on card pages.*/
    public $onCard = true;
    /** @var string $faIcon Font awesome icon name */
    public $faIcon = '';
    /** @var string $btnClass Admin LTE button type. ''|'default'|'primary'|'info'|'success'|'danger'|'warning' */
    public $btnClass = '';
    /** @var string $position Position on card page: 'top'|'bottom'|'both' */
    public $position = '';
    /** @var bool $enabled Default = true. Disabled actions are gray buttons and cannot be clicked.*/
    public $enabled = true; 
    /** @var bool $visible Default = true.*/
    public $visible = true;
    /** @var array $data Additional data which can be set when creatig the action. (Can be accessed in the callback function) */
    public $data = [];

    /**
     * Constructor.
     * 
     * Params basically the properies of this class.
     */
    public function __construct(string $name, string $label, string $columnLabel, callable $callback, bool $onList = true, bool $onCard = true, string $faIcon = '', string $btnClass = 'default', string $position = 'both')
    {	
        if(!in_array($position, self::POSITIONS, true))
		{
			$position = 'both';
        }

        if(!in_array($btnClass, self::BTNCLASSES, true))
		{
			$btnClass = 'default';
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
    }
}
